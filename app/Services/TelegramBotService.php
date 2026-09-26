<?php

namespace App\Services;

use App\Models\EvidenceCategory;
use App\Models\StorageLocation;
use App\Enums\ItemCategory;
use App\Enums\TelegramAccessRole;
use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Enums\VerdictStatus;
use App\Models\LegalCase;
use App\Models\Prosecutor;
use App\Models\PhysicalUnit;
use App\Models\TelegramConversation;
use App\Models\TelegramWhitelist;
use App\Models\UnitItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TelegramBotService
{
    /**
     * @var array<string, mixed>
     */
    private array $replyContext = [];

    public function __construct(
        private readonly TelegramService $telegram,
        private readonly QrCodeService $qrCode,
        private readonly WarehouseService $warehouse,
        private readonly PackContentsParser $packContents,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $this->replyContext = [];

        if (isset($update['my_chat_member']) && is_array($update['my_chat_member'])) {
            $this->handleMyChatMember($update['my_chat_member']);

            return;
        }

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        if (isset($update['message']) && is_array($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }

    /**
     * @param  array<string, mixed>  $memberUpdate
     */
    private function handleMyChatMember(array $memberUpdate): void
    {
        $chat = is_array($memberUpdate['chat'] ?? null) ? $memberUpdate['chat'] : [];
        $newMember = is_array($memberUpdate['new_chat_member'] ?? null) ? $memberUpdate['new_chat_member'] : [];
        $status = (string) ($newMember['status'] ?? '');
        $chatId = $chat['id'] ?? null;
        $title = (string) ($chat['title'] ?? 'grup ini');

        if ($chatId === null || ! $this->isGroupChat($chat)) {
            return;
        }

        if (! in_array($status, ['member', 'administrator'], true)) {
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            "✅ Bot <b>SIBATA-BB</b> sudah masuk {$this->e($title)}.\n\n"
            ."ID grup: <code>{$chatId}</code>\n\n"
            ."Agar bot membaca <b>foto QR</b> di grup, set privacy BotFather ke <b>Disable</b> dan jadikan bot admin.\n\n"
            .'Ketik /id untuk melihat ID grup.'
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleMessage(array $message): void
    {
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];
        $telegramId = isset($from['id']) ? (int) $from['id'] : 0;
        $chatId = $chat['id'] ?? $telegramId;
        $isGroup = $this->isGroupChat($chat);

        $this->captureReplyContext($message);

        if ($telegramId === 0) {
            return;
        }

        $text = $this->normalizeCommand(trim((string) ($message['text'] ?? $message['caption'] ?? '')));

        if (in_array($text, ['/id', '/grup'], true)) {
            $this->reply(
                $chatId,
                $isGroup
                    ? "ID grup ini: <code>{$chatId}</code>\nID Telegram Anda: <code>{$telegramId}</code>"
                    : "ID Telegram Anda: <code>{$telegramId}</code>"
            );

            return;
        }

        if ($this->isLicenseCommand($text)) {
            if ($isGroup) {
                $this->reply($chatId, 'Aktifkan lisensi di chat pribadi bot, bukan di grup.');

                return;
            }

            $daftar = TelegramConversation::query()
                ->where('telegram_user_id', $telegramId)
                ->where('action', 'daftar')
                ->first();

            if (str_starts_with($text, '/lisensi')) {
                $this->redeemLicense($telegramId, $chatId, $text, $from);

                return;
            }

            if ($daftar?->step === 'awaiting_name') {
                $this->reply($chatId, 'Masukkan <b>nama akun</b> di portal terlebih dahulu, baru kode lisensi.');

                return;
            }

            $payload = is_array($daftar?->payload) ? $daftar->payload : [];
            $expectedUserId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $displayName = (string) ($payload['display_name'] ?? '');

            $this->redeemLicense($telegramId, $chatId, $text, $from, $displayName !== '' ? $displayName : null, $expectedUserId);

            return;
        }

        $actor = $this->authorize($telegramId, $chatId, $isGroup, $from);

        if ($actor === null) {
            if (! $isGroup) {
                $this->handleGuestMessage($telegramId, $chatId, $text);
            }

            return;
        }

        $hasConversation = TelegramConversation::query()->where('telegram_user_id', $telegramId)->exists();
        $hasPhoto = isset($message['photo']) && is_array($message['photo']);

        if ($isGroup && ! $hasConversation && ! $hasPhoto && ! str_starts_with($text, '/') && ! $this->looksLikeUnitCode($text)) {
            return;
        }

        if (in_array($text, ['/batal', '/batalkan', '/cancel'], true)) {
            $this->clearConversation($telegramId);
            $this->reply($chatId, 'Proses dibatalkan. Ketik /start untuk menu.');

            return;
        }

        if ($text === '/start') {
            $this->clearConversation($telegramId);
            $this->sendMainMenu($chatId, $this->welcomeText($actor, $isGroup));

            return;
        }

        if (in_array($text, ['/tambah', '/baru'], true)) {
            $this->startTambah($actor, $chatId);

            return;
        }

        if (preg_match('/^\/cari(?:\s+(.+))?$/u', $text, $searchMatch) === 1) {
            $query = trim((string) ($searchMatch[1] ?? ''));
            if ($query === '') {
                $this->startSearch($actor, $chatId);
            } else {
                $this->sendSearchResults($chatId, $query);
            }

            return;
        }

        if (preg_match('/^\/pinjam(?:\s+(.+))?$/u', $text, $loanMatch) === 1) {
            $this->startPinjam($actor, $chatId, trim((string) ($loanMatch[1] ?? '')));

            return;
        }

        if (preg_match('/^\/kembali(?:\s+(.+))?$/u', $text, $returnMatch) === 1) {
            $this->startKembali($actor, $chatId, trim((string) ($returnMatch[1] ?? '')));

            return;
        }

        if (preg_match('/^\/eksekusi(?:\s+(.+))?$/u', $text, $execMatch) === 1) {
            $this->startEksekusi($actor, $chatId, trim((string) ($execMatch[1] ?? '')));

            return;
        }

        if ($text === '/rekap') {
            $this->sendRekap($chatId);

            return;
        }

        $conversation = TelegramConversation::query()->where('telegram_user_id', $telegramId)->first();

        if ($conversation !== null) {
            $this->continueConversation($actor, $chatId, $conversation, $message, $text);

            return;
        }

        if ($hasPhoto) {
            $this->handlePhotoScan($chatId, $message['photo']);

            return;
        }

        $code = $this->warehouse->parseUnitCode($text);
        if ($code !== null) {
            $this->respondWithUnit($chatId, $this->warehouse->findByCode($code));

            return;
        }

        $this->reply(
            $chatId,
            "Perintah SIBATA-BB:\n/tambah — daftar perkara & BB\n/cari kata — cari\n/pinjam KODE — pinjam sidang\n/kembali KODE — kembali gudang\n/eksekusi KODE — catat putusan\n/rekap — ringkasan gudang\n\nAtau kirim foto stiker QR / kode unit."
        );
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function handleCallback(array $callback): void
    {
        $from = is_array($callback['from'] ?? null) ? $callback['from'] : [];
        $message = is_array($callback['message'] ?? null) ? $callback['message'] : [];
        $chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];
        $telegramId = isset($from['id']) ? (int) $from['id'] : 0;
        $chatId = $chat['id'] ?? $telegramId;
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $isGroup = $this->isGroupChat($chat);

        $this->captureReplyContext($message);

        if ($telegramId === 0 || $callbackId === '') {
            return;
        }

        $actor = $this->authorize($telegramId, $chatId, $isGroup, $from);

        if ($actor === null) {
            $this->telegram->answerCallbackQuery($callbackId, 'Akses ditolak.');

            return;
        }

        $conversation = TelegramConversation::query()->where('telegram_user_id', $telegramId)->first();

        if ($data === 'menu_tambah') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->startTambah($actor, $chatId);

            return;
        }

        if ($data === 'menu_cari') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->startSearch($actor, $chatId);

            return;
        }

        if ($data === 'menu_rekap') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->sendRekap($chatId);

            return;
        }

        if (preg_match('/^pick_(\d+)$/', $data, $pick) === 1) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->respondWithUnit($chatId, PhysicalUnit::query()->with(['legalCase', 'items'])->find((int) $pick[1]));

            return;
        }

        if ($conversation !== null && $conversation->action === 'tambah') {
            $this->handleTambahCallback($actor, $chatId, $callbackId, $conversation, $data, $this->callbackMessageId($message));

            return;
        }

        if ($conversation !== null && $conversation->action === 'pinjam') {
            $this->handlePinjamCallback($actor, $chatId, $callbackId, $conversation, $data, $this->callbackMessageId($message));

            return;
        }

        if ($conversation !== null && $conversation->action === 'eksekusi') {
            $this->handleEksekusiCallback($actor, $chatId, $callbackId, $conversation, $data);

            return;
        }

        if ($conversation !== null && $conversation->action === 'kembali') {
            $this->handleKembaliCallback($actor, $chatId, $callbackId, $conversation, $data);

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    private function startTambah(TelegramWhitelist $actor, int|string $chatId): void
    {
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_case_number');
        $this->reply(
            $chatId,
            "📝 <b>Daftar perkara & BB</b>\n\nKirim <b>nomor perkara</b> (contoh: <code>REG-012/PID.SUS/2026</code>).\nKetik /batal untuk membatalkan."
        );
    }

    private function startSearch(TelegramWhitelist $actor, int|string $chatId): void
    {
        $this->putConversation((int) $actor->telegram_chat_id, 'search', 'awaiting_query');
        $this->reply($chatId, '🔎 Kirim nama terdakwa, nomor perkara, atau kode unit.');
    }

    private function startPinjam(TelegramWhitelist $actor, int|string $chatId, string $argument): void
    {
        $unit = $argument !== '' ? $this->warehouse->findByCode($argument) : null;

        if ($unit === null) {
            $this->putConversation((int) $actor->telegram_chat_id, 'pinjam', 'awaiting_code');
            $this->reply($chatId, "📤 <b>Pinjam sidang</b>\nKirim kode unit (<code>BB-2026-001</code> / <code>PKT-2026-002</code>) atau foto stiker QR.");

            return;
        }

        $this->promptLoanDetails($actor, $chatId, $unit);
    }

    private function startKembali(TelegramWhitelist $actor, int|string $chatId, string $argument): void
    {
        $unit = $argument !== '' ? $this->warehouse->findByCode($argument) : null;

        if ($unit === null) {
            $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_code');
            $this->reply($chatId, "📥 <b>Kembali gudang</b>\nKirim kode unit atau foto stiker QR.");

            return;
        }

        $this->promptReturnDetails($actor, $chatId, $unit);
    }

    private function startEksekusi(TelegramWhitelist $actor, int|string $chatId, string $argument): void
    {
        $unit = $argument !== '' ? $this->warehouse->findByCode($argument) : null;

        if ($unit === null) {
            $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_code');
            $this->reply($chatId, "⚖️ <b>Eksekusi putusan</b>\nKirim kode unit atau foto stiker QR.");

            return;
        }

        $this->promptEksekusiItems($chatId, $actor, $unit);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function continueConversation(
        TelegramWhitelist $actor,
        int|string $chatId,
        TelegramConversation $conversation,
        array $message,
        string $text,
    ): void {
        match ($conversation->action) {
            'tambah' => $this->continueTambah($actor, $chatId, $conversation, $message, $text),
            'search' => $this->continueSearch($chatId, $conversation, $text),
            'pinjam' => $this->continuePinjam($actor, $chatId, $conversation, $message, $text),
            'kembali' => $this->continueKembali($actor, $chatId, $conversation, $message, $text),
            'eksekusi' => $this->continueEksekusi($actor, $chatId, $conversation, $message, $text),
            default => $this->reply($chatId, 'Sesi tidak dikenali. Ketik /batal.'),
        };
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function continueTambah(
        TelegramWhitelist $actor,
        int|string $chatId,
        TelegramConversation $conversation,
        array $message,
        string $text,
    ): void {
        $payload = $conversation->payload ?? [];

        if ($conversation->step === 'awaiting_case_number') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim nomor perkara, atau /batal.');

                return;
            }

            $existing = LegalCase::query()->where('case_number', $text)->first();
            $payload['case_number'] = $text;

            if ($existing !== null) {
                $payload['case_id'] = $existing->id;
                $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_type', null, $payload);
                $this->reply(
                    $chatId,
                    "✅ Perkara <b>{$this->e($existing->case_number)}</b> sudah terdaftar.\nTerdakwa: {$this->e($existing->defendant_name)}\nJPU: {$this->e($existing->prosecutor_name)}\n\nBB akan ditambahkan ke perkara ini. Pilih jenis unit fisik:",
                    $this->typeKeyboard()
                );

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_defendant', null, $payload);
            $this->reply($chatId, 'Kirim <b>nama terdakwa</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_defendant') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim nama terdakwa, atau /batal.');

                return;
            }

            $payload['defendant_name'] = $text;
            $payload['prosecutor_ids'] = [];
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_prosecutors', null, $payload);
            $this->promptProsecutorPicker($chatId, 'Pilih <b>JPU</b> (boleh lebih dari satu), lalu tekan <b>Selesai pilih JPU</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_prosecutors') {
            $this->reply($chatId, 'Pilih JPU dari tombol di atas. Boleh lebih dari satu, lalu tekan <b>Selesai pilih JPU</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_single_desc') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim deskripsi BB, atau /batal.');

                return;
            }

            [$name, $qty] = $this->parseNameAndQty($text);
            $payload['item_name'] = $name;
            $payload['quantity'] = $qty;

            if (($payload['edit_mode'] ?? false) === true) {
                unset($payload['edit_mode']);
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_category', null, $payload);
            $this->reply($chatId, $this->categoryPrompt(), $this->categoryKeyboard());

            return;
        }

        if ($conversation->step === 'awaiting_single_category') {
            $category = $this->resolveCategoryFromText($text);
            if ($category === null) {
                $this->reply($chatId, $this->categoryPrompt(), $this->categoryKeyboard());

                return;
            }

            $payload['category'] = $category;
            if (($payload['edit_mode'] ?? false) === true) {
                unset($payload['edit_mode']);
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_location', null, $payload);
            $this->reply($chatId, 'Pilih <b>tempat penyimpanan</b>:', $this->locationKeyboard());

            return;
        }

        if ($conversation->step === 'awaiting_single_location') {
            if (! $this->applyTypedStorageLocation($payload, $text)) {
                $this->reply($chatId, 'Pilih tempat penyimpanan dari tombol, atau ketik nama lokasi yang terdaftar.', $this->locationKeyboard());

                return;
            }

            if (array_key_exists('photo_path', $payload)) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_photo', null, $payload);
            $this->reply($chatId, 'Kirim <b>foto BB</b> lewat kamera Telegram, atau ketik <code>/skip</code> jika tanpa foto.');

            return;
        }

        if ($conversation->step === 'awaiting_single_photo') {
            $photoPath = $this->optionalPhotoPath($message, $text, 'units');
            if ($photoPath === false) {
                $this->reply($chatId, 'Kirim foto, atau ketik /skip.');

                return;
            }

            $payload['photo_path'] = $photoPath ?: null;
            $this->showDraftReview($actor, $chatId, $payload);

            return;
        }

        if ($conversation->step === 'awaiting_pack_meta') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim deskripsi wadah/segel.');

                return;
            }

            $payload['pack_description'] = $text;
            if (is_array($payload['children'] ?? null) || array_key_exists('photo_path', $payload)) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_location', null, $payload);
            $this->reply($chatId, 'Pilih <b>tempat penyimpanan</b>:', $this->locationKeyboard());

            return;
        }

        if ($conversation->step === 'awaiting_pack_location') {
            if (! $this->applyTypedStorageLocation($payload, $text)) {
                $this->reply($chatId, 'Pilih tempat penyimpanan dari tombol, atau ketik nama lokasi yang terdaftar.', $this->locationKeyboard());

                return;
            }

            if (array_key_exists('photo_path', $payload) || is_array($payload['children'] ?? null)) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_photo', null, $payload);
            $this->reply($chatId, 'Kirim <b>foto wadah/segel</b>, atau ketik <code>/skip</code>.');

            return;
        }

        if ($conversation->step === 'awaiting_pack_photo') {
            $photoPath = $this->optionalPhotoPath($message, $text, 'units');
            if ($photoPath === false) {
                $this->reply($chatId, 'Kirim foto wadah, atau ketik /skip.');

                return;
            }

            $alreadyDrafted = is_array($payload['children'] ?? null);
            $payload['photo_path'] = $photoPath ?: null;

            if ($alreadyDrafted) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            $payload['children'] = [];
            $payload['child_index'] = 1;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_name', null, $payload);
            $this->reply($chatId, "Draft wadah siap. Belum disimpan.\n\n".$this->childNamePrompt(1));

            return;
        }

        if ($conversation->step === 'awaiting_child_name') {
            if ($this->isSkipCommand($text) && ! isset($payload['edit_index'])) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            if ($text === '') {
                $this->reply($chatId, $this->childNamePrompt((int) ($payload['child_index'] ?? 1)));

                return;
            }

            [$name, $qty] = $this->parseNameAndQty($text);
            $payload['child_name'] = $name;
            $payload['child_qty'] = $qty;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_category', null, $payload);
            $this->reply($chatId, "Pilih <b>jenis</b> untuk <b>{$this->e($name)}</b>:", $this->categoryKeyboard());

            return;
        }

        if ($conversation->step === 'awaiting_child_category') {
            $category = $this->resolveCategoryFromText($text);
            if ($category === null) {
                $this->reply($chatId, "Pilih <b>jenis</b> untuk <b>{$this->e((string) ($payload['child_name'] ?? 'barang ini'))}</b>:", $this->categoryKeyboard());

                return;
            }

            $this->storeDraftChild($actor, $chatId, $payload, $category);

            return;
        }

        if ($conversation->step === 'awaiting_review') {
            $this->reply($chatId, 'Periksa dulu data di atas. Tekan <b>Setujui & Simpan</b> atau <b>Ubah</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_edit_menu') {
            $this->reply($chatId, 'Pilih bagian yang ingin diubah dari tombol, atau tekan <b>Kembali ke ringkasan</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_pack_loop') {
            $this->reply($chatId, 'Tekan <b>Tambah Barang Lain</b> atau <b>Selesai & Periksa</b>. Data belum disimpan.');

            return;
        }

        $this->reply($chatId, 'Gunakan tombol yang tersedia, atau ketik /batal.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleTambahCallback(
        TelegramWhitelist $actor,
        int|string $chatId,
        string $callbackId,
        TelegramConversation $conversation,
        string $data,
        ?int $messageId = null,
    ): void {
        $payload = $conversation->payload ?? [];

        if ($this->handleProsecutorCallback($actor, $chatId, $callbackId, $conversation, $data, $messageId, 'tambah')) {
            return;
        }

        if ($data === 'type_SINGLE' && $conversation->step === 'awaiting_type') {
            $this->telegram->answerCallbackQuery($callbackId);
            $payload['unit_type'] = UnitType::Single->value;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_desc', null, $payload);
            $this->reply($chatId, "Kirim deskripsi BB mandiri dan jumlah/satuan.\nContoh: <code>HP Vivo Y21 | 1 unit</code>");

            return;
        }

        if ($data === 'type_PACK' && $conversation->step === 'awaiting_type') {
            $this->telegram->answerCallbackQuery($callbackId);
            $payload['unit_type'] = UnitType::Pack->value;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_meta', null, $payload);
            $this->reply($chatId, "Kirim deskripsi wadah/segel.\nContoh: <code>Kantong plastik segel</code>");

            return;
        }

        if (str_starts_with($data, 'cat_') && in_array($conversation->step, ['awaiting_single_category', 'awaiting_child_category'], true)) {
            $category = EvidenceCategory::activeCode(substr($data, 4)) ?? $this->resolveCategoryFromText(substr($data, 4));
            if ($category === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Jenis BB tidak valid.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);

            if ($conversation->step === 'awaiting_single_category') {
                $payload['category'] = $category;
                if (($payload['edit_mode'] ?? false) === true) {
                    unset($payload['edit_mode']);
                    $this->showDraftReview($actor, $chatId, $payload);

                    return;
                }

                $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_location', null, $payload);
                $this->reply($chatId, 'Pilih <b>tempat penyimpanan</b>:', $this->locationKeyboard());

                return;
            }

            $this->storeDraftChild($actor, $chatId, $payload, $category);

            return;
        }

        if ($data === 'add_more') {
            $this->telegram->answerCallbackQuery($callbackId);
            unset(
                $payload['item_name'], $payload['quantity'], $payload['category'],
                $payload['storage_location'], $payload['storage_location_id'], $payload['unit_id'],
                $payload['child_name'], $payload['child_qty'], $payload['pack_description'],
                $payload['children'], $payload['photo_path'], $payload['child_index'], $payload['edit_index'], $payload['edit_mode']
            );
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_type', null, $payload);
            $this->reply($chatId, 'Pilih jenis unit berikutnya:', $this->typeKeyboard());

            return;
        }

        if ($data === 'add_done') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, 'Sesi pencatatan selesai. Ketik /rekap atau /start.');

            return;
        }

        if ($data === 'pack_more') {
            $this->telegram->answerCallbackQuery($callbackId);
            $index = count($payload['children'] ?? []) + 1;
            $payload['child_index'] = $index;
            unset($payload['child_name'], $payload['child_qty'], $payload['edit_index']);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_name', null, $payload);
            $this->reply($chatId, $this->childNamePrompt($index));

            return;
        }

        if ($data === 'pack_done') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->showDraftReview($actor, $chatId, $payload);

            return;
        }

        if ($data === 'draft_save' && $conversation->step === 'awaiting_review') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->persistDraft($actor, $chatId, $payload);

            return;
        }

        if ($data === 'draft_edit' && $conversation->step === 'awaiting_review') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->showDraftEditMenu($actor, $chatId, $payload);

            return;
        }

        if ($data === 'draft_back') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->showDraftReview($actor, $chatId, $payload);

            return;
        }

        if ($data === 'edit_name') {
            $this->telegram->answerCallbackQuery($callbackId);
            $payload['edit_mode'] = true;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_desc', null, $payload);
            $this->reply($chatId, 'Kirim nama/deskripsi BB yang baru.');

            return;
        }

        if ($data === 'edit_cat') {
            $this->telegram->answerCallbackQuery($callbackId);
            $payload['edit_mode'] = true;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_category', null, $payload);
            $this->reply($chatId, $this->categoryPrompt(), $this->categoryKeyboard());

            return;
        }

        if ($data === 'edit_desc') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_meta', null, $payload);
            $this->reply($chatId, 'Kirim deskripsi wadah/segel yang baru.');

            return;
        }

        if ($data === 'edit_loc') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', $payload['unit_type'] === UnitType::Pack->value ? 'awaiting_pack_location' : 'awaiting_single_location', null, $payload);
            $this->reply($chatId, 'Pilih tempat penyimpanan yang baru:', $this->locationKeyboard());

            return;
        }

        if ($data === 'edit_photo') {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', $payload['unit_type'] === UnitType::Pack->value ? 'awaiting_pack_photo' : 'awaiting_single_photo', null, $payload);
            $this->reply($chatId, 'Kirim foto yang baru, atau ketik /skip.');

            return;
        }

        if (preg_match('/^edc_(\d+)$/', $data, $edit) === 1) {
            $this->telegram->answerCallbackQuery($callbackId);
            $payload['edit_index'] = (int) $edit[1];
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_name', null, $payload);
            $this->reply($chatId, 'Kirim nama barang yang baru untuk item ke-'.((int) $edit[1] + 1).'.');

            return;
        }

        if (preg_match('/^del_(\d+)$/', $data, $delete) === 1) {
            $this->telegram->answerCallbackQuery($callbackId);
            $children = $payload['children'] ?? [];
            unset($children[(int) $delete[1]]);
            $payload['children'] = array_values($children);
            $this->showDraftReview($actor, $chatId, $payload);

            return;
        }

        if (str_starts_with($data, 'loc_') && in_array($conversation->step, ['awaiting_single_location', 'awaiting_pack_location'], true)) {
            $location = $this->resolveStorageLocation($data);
            if ($location === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Lokasi tidak valid.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);
            $payload['storage_location'] = $location->name;
            $payload['storage_location_id'] = $location->id;

            if (array_key_exists('photo_path', $payload) || is_array($payload['children'] ?? null)) {
                $this->showDraftReview($actor, $chatId, $payload);

                return;
            }

            if ($conversation->step === 'awaiting_single_location') {
                $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_photo', null, $payload);
                $this->reply($chatId, 'Kirim <b>foto BB</b> lewat kamera Telegram, atau ketik <code>/skip</code> jika tanpa foto.');

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_photo', null, $payload);
            $this->reply($chatId, 'Kirim <b>foto wadah/segel</b>, atau ketik <code>/skip</code>.');

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function showDraftReview(TelegramWhitelist $actor, int|string $chatId, array $payload): void
    {
        unset($payload['edit_mode'], $payload['edit_index'], $payload['child_name'], $payload['child_qty']);
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_review', null, $payload);
        $this->reply($chatId, $this->draftSummary($payload), [
            [
                ['text' => '✅ Setujui & Simpan', 'callback_data' => 'draft_save'],
                ['text' => '✏️ Ubah', 'callback_data' => 'draft_edit'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function draftSummary(array $payload): string
    {
        $lines = [
            '📋 <b>Periksa data sebelum disimpan</b>',
            'Data ini masih draf. Tekan <b>Setujui & Simpan</b> jika sudah benar, atau <b>Ubah</b> jika ada yang salah.',
            '',
        ];

        if (($payload['unit_type'] ?? '') === UnitType::Pack->value) {
            $lines[] = 'Jenis: <b>Segel / paket</b>';
            $lines[] = 'Wadah: '.$this->e((string) ($payload['pack_description'] ?? '-'));
            $lines[] = 'Lokasi: '.$this->e((string) ($payload['storage_location'] ?? '-'));
            $lines[] = 'Foto: '.(filled($payload['photo_path'] ?? null) ? 'ada' : 'tidak ada');
            $lines[] = '';
            $children = $payload['children'] ?? [];
            if ($children === []) {
                $lines[] = 'Isi paket: <i>belum ada barang</i>';
            } else {
                $lines[] = '<b>Isi paket</b>';
                foreach ($children as $index => $child) {
                    $jenis = EvidenceCategory::labelFor((string) ($child['category'] ?? ''));
                    $lines[] = ($index + 1).'. '.$this->e((string) ($child['item_name'] ?? '-'))
                        .' — '.$this->e($jenis)
                        .' ('.$this->e((string) ($child['quantity'] ?? '1')).')';
                }
            }
        } else {
            $lines[] = 'Jenis: <b>BB satuan</b>';
            $lines[] = 'Nama: '.$this->e((string) ($payload['item_name'] ?? '-'));
            $lines[] = 'Jenis BB: '.$this->e(EvidenceCategory::labelFor((string) ($payload['category'] ?? '')));
            $lines[] = 'Jumlah: '.$this->e((string) ($payload['quantity'] ?? '1 unit'));
            $lines[] = 'Lokasi: '.$this->e((string) ($payload['storage_location'] ?? '-'));
            $lines[] = 'Foto: '.(filled($payload['photo_path'] ?? null) ? 'ada' : 'tidak ada');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function showDraftEditMenu(TelegramWhitelist $actor, int|string $chatId, array $payload): void
    {
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_edit_menu', null, $payload);
        $rows = [];

        if (($payload['unit_type'] ?? '') === UnitType::Pack->value) {
            $rows[] = [['text' => 'Ubah deskripsi wadah', 'callback_data' => 'edit_desc']];
            $rows[] = [
                ['text' => 'Ubah lokasi', 'callback_data' => 'edit_loc'],
                ['text' => 'Ubah foto', 'callback_data' => 'edit_photo'],
            ];

            foreach ($payload['children'] ?? [] as $index => $child) {
                $label = mb_strimwidth((string) ($child['item_name'] ?? 'barang'), 0, 28, '…');
                $rows[] = [
                    ['text' => '✏️ '.($index + 1).'. '.$label, 'callback_data' => 'edc_'.$index],
                    ['text' => '🗑 Hapus', 'callback_data' => 'del_'.$index],
                ];
            }

            $rows[] = [['text' => '+ Tambah barang', 'callback_data' => 'pack_more']];
        } else {
            $rows[] = [['text' => 'Ubah nama/deskripsi', 'callback_data' => 'edit_name']];
            $rows[] = [['text' => 'Ubah jenis BB', 'callback_data' => 'edit_cat']];
            $rows[] = [
                ['text' => 'Ubah lokasi', 'callback_data' => 'edit_loc'],
                ['text' => 'Ubah foto', 'callback_data' => 'edit_photo'],
            ];
        }

        $rows[] = [['text' => '⬅️ Kembali ke ringkasan', 'callback_data' => 'draft_back']];
        $this->reply($chatId, 'Pilih bagian yang ingin diubah:', $rows);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistDraft(TelegramWhitelist $actor, int|string $chatId, array $payload): void
    {
        if (($payload['unit_type'] ?? '') === UnitType::Pack->value) {
            $this->persistDraftPack($actor, $chatId, $payload);

            return;
        }

        $this->saveSingleFromPayload($actor, $chatId, $payload, $payload['photo_path'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveSingleFromPayload(TelegramWhitelist $actor, int|string $chatId, array $payload, ?string $photoPath): void
    {
        $case = LegalCase::query()->find((int) ($payload['case_id'] ?? 0));
        $category = EvidenceCategory::activeCode((string) ($payload['category'] ?? ''));

        if ($case === null || $category === null) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, 'Data sesi tidak lengkap. Mulai ulang dengan /tambah.');

            return;
        }

        $unit = $this->warehouse->createSingleUnit(
            $case,
            (string) $payload['item_name'],
            $category,
            (string) ($payload['quantity'] ?? '1 unit'),
            (string) $payload['storage_location'],
            $photoPath,
            $actor->user_name,
            isset($payload['asset_type_id']) ? (int) $payload['asset_type_id'] : null,
            isset($payload['storage_location_id']) ? (int) $payload['storage_location_id'] : null,
        );

        $this->sendUnitSummary($chatId, $unit, $actor);
        $payload['unit_id'] = $unit->id;
        $this->askMoreAfterSave($actor, $chatId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistDraftPack(TelegramWhitelist $actor, int|string $chatId, array $payload): void
    {
        $case = LegalCase::query()->find((int) ($payload['case_id'] ?? 0));

        if ($case === null) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, 'Perkara tidak ditemukan. Mulai ulang /tambah.');

            return;
        }

        $children = array_values(array_filter(
            $payload['children'] ?? [],
            fn ($child) => filled($child['item_name'] ?? null) && filled($child['category'] ?? null),
        ));

        $unit = $this->warehouse->createPackUnit(
            $case,
            (string) $payload['storage_location'],
            filled($payload['photo_path'] ?? null) ? (string) $payload['photo_path'] : null,
            $actor->user_name,
            $children,
            isset($payload['asset_type_id']) ? (int) $payload['asset_type_id'] : null,
            isset($payload['storage_location_id']) ? (int) $payload['storage_location_id'] : null,
        );

        $this->sendUnitSummary($chatId, $unit, $actor);
        $payload['unit_id'] = $unit->id;
        $this->askMoreAfterSave($actor, $chatId, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function askMoreAfterSave(TelegramWhitelist $actor, int|string $chatId, array $payload): void
    {
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_more', null, $payload);
        $this->reply($chatId, 'Data sudah disimpan. Tambah unit lain untuk perkara yang sama?', [
            [
                ['text' => '+ Tambah Lagi', 'callback_data' => 'add_more'],
                ['text' => 'Selesai', 'callback_data' => 'add_done'],
            ],
        ]);
    }

    private function childNamePrompt(int $index): string
    {
        return "Kirim <b>barang bukti ke-{$index}</b> di dalam segel (nama dan jumlah).\n"
            ."Contoh: <code>2 sachet sabu 0,5 gram</code> atau <code>HP Vivo Y21 | 1 unit</code>\n\n"
            .'Setelah itu pilih jenisnya (Narkotika, Elektronik, dst). Ketik <code>/skip</code> jika isi dilengkapi nanti.';
    }

    private function categoryPrompt(string $target = 'barang bukti'): string
    {
        return "Pilih <b>jenis {$target}</b>:\nNarkotika, Elektronik, Kendaraan, Senjata, Dokumen, atau Lainnya.";
    }

    private function resolveCategoryFromText(string $text): ?string
    {
        $value = trim($text);
        if ($value === '') {
            return null;
        }

        $fromMaster = EvidenceCategory::activeCode(strtoupper($value));
        if ($fromMaster !== null) {
            return $fromMaster;
        }

        $match = EvidenceCategory::active()
            ->get()
            ->first(fn (EvidenceCategory $category) => mb_strtolower($category->name) === mb_strtolower($value)
                || mb_strtolower($category->code) === mb_strtolower($value));

        if ($match !== null) {
            return $match->code;
        }

        foreach (ItemCategory::cases() as $category) {
            if (mb_strtolower($category->value) === mb_strtolower($value)
                || mb_strtolower($category->label()) === mb_strtolower($value)) {
                return $category->value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeDraftChild(TelegramWhitelist $actor, int|string $chatId, array $payload, string $category): void
    {
        $child = [
            'item_name' => (string) ($payload['child_name'] ?? ''),
            'category' => $category,
            'quantity' => (string) ($payload['child_qty'] ?? '1'),
        ];

        $children = $payload['children'] ?? [];
        if (isset($payload['edit_index'])) {
            $editIndex = (int) $payload['edit_index'];
            if (isset($children[$editIndex])) {
                $children[$editIndex] = $child;
            } else {
                $children[] = $child;
            }
            $payload['children'] = array_values($children);
            unset($payload['edit_index'], $payload['child_name'], $payload['child_qty']);
            $this->showDraftReview($actor, $chatId, $payload);

            return;
        }

        $children[] = $child;
        $payload['children'] = $children;
        $payload['child_index'] = count($children);
        unset($payload['child_name'], $payload['child_qty']);

        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_loop', null, $payload);
        $jenis = EvidenceCategory::labelFor($category);
        $this->reply($chatId, "Draft: <b>{$this->e((string) $child['item_name'])}</b> sebagai <b>{$this->e($jenis)}</b>.\nBelum disimpan. Tambah barang berikutnya?", [
            [
                ['text' => '+ Tambah Barang Lain', 'callback_data' => 'pack_more'],
                ['text' => 'Selesai & Periksa', 'callback_data' => 'pack_done'],
            ],
        ]);
    }

    private function continueSearch(int|string $chatId, TelegramConversation $conversation, string $text): void
    {
        if ($text === '') {
            $this->reply($chatId, 'Kirim kata kunci pencarian.');

            return;
        }

        $conversation->delete();
        $this->sendSearchResults($chatId, $text);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function continuePinjam(
        TelegramWhitelist $actor,
        int|string $chatId,
        TelegramConversation $conversation,
        array $message,
        string $text,
    ): void {
        $payload = $conversation->payload ?? [];

        if ($conversation->step === 'awaiting_code') {
            $unit = $this->resolveUnitFromInput($message, $text);
            if ($unit === null) {
                $this->reply($chatId, 'Unit tidak ditemukan. Kirim kode atau foto QR yang valid.');

                return;
            }

            $this->promptLoanDetails($actor, $chatId, $unit);

            return;
        }

        if (in_array($conversation->step, ['awaiting_borrower', 'awaiting_loan_prosecutors'], true)) {
            $this->reply($chatId, 'Pilih JPU peminjam dari tombol. Boleh lebih dari satu, lalu tekan <b>Selesai pilih JPU</b>.');

            return;
        }

        if ($conversation->step === 'awaiting_court_date') {
            $date = $this->parseDate($text);
            if ($date === null) {
                $this->reply($chatId, 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.');

                return;
            }

            $payload['court_date'] = $date;
            $this->putConversation((int) $actor->telegram_chat_id, 'pinjam', 'awaiting_loan_photo', null, $payload);
            $this->reply($chatId, 'Kirim <b>foto kondisi BB saat dipinjam</b> (wajib).');

            return;
        }

        if ($conversation->step === 'awaiting_loan_photo') {
            $photoPath = $this->optionalPhotoPath($message, $text, 'loans');
            if ($photoPath === false || $photoPath === null) {
                $this->reply($chatId, 'Foto wajib. Kirim foto kondisi BB saat dipinjam.');

                return;
            }

            $unit = PhysicalUnit::query()->find((int) ($payload['unit_id'] ?? 0));
            if ($unit === null) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, 'Unit hilang dari sesi. Ulangi /pinjam.');

                return;
            }

            try {
                $this->warehouse->loan(
                    $unit,
                    (string) $payload['borrower_name'],
                    (string) ($payload['court_date'] ?? ''),
                    $actor->user_name,
                    null,
                    $photoPath,
                );
            } catch (RuntimeException $exception) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, $exception->getMessage());

                return;
            }

            $unit->refresh()->load(['legalCase', 'items']);
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "✅ Dipinjam sidang.\n{$this->unitHeader($unit)}\nJPU: {$this->e((string) $payload['borrower_name'])}\nSidang: {$this->e((string) ($payload['court_date'] ?? ''))}");
            $this->broadcastGroup("📤 Pinjam sidang {$this->e($unit->unit_code)} — {$this->e((string) $payload['borrower_name'])}");
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function continueKembali(
        TelegramWhitelist $actor,
        int|string $chatId,
        TelegramConversation $conversation,
        array $message,
        string $text,
    ): void {
        $payload = $conversation->payload ?? [];

        if ($conversation->step === 'awaiting_code') {
            $unit = $this->resolveUnitFromInput($message, $text);
            if ($unit === null) {
                $this->reply($chatId, 'Unit tidak ditemukan. Kirim kode atau foto QR yang valid.');

                return;
            }

            $this->promptReturnDetails($actor, $chatId, $unit);

            return;
        }

        if ($conversation->step === 'awaiting_location') {
            if (! $this->applyTypedStorageLocation($payload, $text)) {
                $this->reply($chatId, 'Pilih tempat penyimpanan dari tombol, atau ketik nama lokasi yang terdaftar.', $this->locationKeyboard());

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_notes', null, $payload);
            $this->reply($chatId, 'Kirim catatan kondisi fisik (atau ketik <code>-</code> jika tidak ada).');

            return;
        }

        if ($conversation->step === 'awaiting_notes') {
            $payload['notes'] = $text === '-' ? null : $text;
            $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_return_photo', null, $payload);
            $this->reply($chatId, 'Kirim <b>foto kondisi BB saat dikembalikan</b> (wajib).');

            return;
        }

        if ($conversation->step === 'awaiting_return_photo') {
            $photoPath = $this->optionalPhotoPath($message, $text, 'loans');
            if ($photoPath === false || $photoPath === null) {
                $this->reply($chatId, 'Foto wajib. Kirim foto kondisi BB saat dikembalikan.');

                return;
            }

            $unit = PhysicalUnit::query()->find((int) ($payload['unit_id'] ?? 0));
            if ($unit === null) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, 'Unit hilang dari sesi. Ulangi /kembali.');

                return;
            }

            try {
                $this->warehouse->returnToWarehouse(
                    $unit,
                    (string) $payload['storage_location'],
                    $actor->user_name,
                    $payload['notes'] ?? null,
                    isset($payload['storage_location_id']) ? (int) $payload['storage_location_id'] : null,
                    $photoPath,
                );
            } catch (RuntimeException $exception) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, $exception->getMessage());

                return;
            }

            $unit->refresh()->load(['legalCase', 'items']);
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "✅ Kembali gudang.\n{$this->unitHeader($unit)}");
            $this->broadcastGroup("📥 Kembali gudang {$this->e($unit->unit_code)}");
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function continueEksekusi(
        TelegramWhitelist $actor,
        int|string $chatId,
        TelegramConversation $conversation,
        array $message,
        string $text,
    ): void {
        $payload = $conversation->payload ?? [];

        if ($conversation->step === 'awaiting_code') {
            $unit = $this->resolveUnitFromInput($message, $text);
            if ($unit === null) {
                $this->reply($chatId, 'Unit tidak ditemukan. Kirim kode atau foto QR yang valid.');

                return;
            }

            $this->promptEksekusiItems($chatId, $actor, $unit);

            return;
        }

        if ($conversation->step === 'awaiting_ba') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim nomor BA eksekusi, atau ketik -.');

                return;
            }

            $payload['ba_number'] = $text === '-' ? null : $text;
            $verdict = VerdictStatus::tryFrom((string) ($payload['verdict'] ?? ''));

            if ($verdict === VerdictStatus::Dikembalikan) {
                $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_recipient', null, $payload);
                $this->reply($chatId, 'Kirim nama penerima dan NIK, dipisah | (contoh: <code>Andi Rahman | 7301010101010001</code>).');

                return;
            }

            $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_exec_photo', null, $payload);
            $this->reply($chatId, 'Kirim foto bukti eksekusi, atau ketik /skip.');

            return;
        }

        if ($conversation->step === 'awaiting_recipient') {
            [$name, $nik] = $this->parseTwoNames($text);
            if ($name === '') {
                $this->reply($chatId, 'Kirim nama penerima (dan NIK jika ada).');

                return;
            }

            $payload['recipient'] = $name;
            $payload['nik'] = $nik !== '' ? $nik : null;
            $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_exec_photo', null, $payload);
            $this->reply($chatId, 'Kirim foto bukti eksekusi, atau ketik /skip.');

            return;
        }

        if ($conversation->step === 'awaiting_exec_photo') {
            $photoPath = $this->optionalPhotoPath($message, $text, 'executions');
            if ($photoPath === false) {
                $this->reply($chatId, 'Kirim foto, atau ketik /skip.');

                return;
            }

            $item = UnitItem::query()->with('physicalUnit')->find((int) ($payload['item_id'] ?? 0));
            $verdict = VerdictStatus::tryFrom((string) ($payload['verdict'] ?? ''));

            if ($item === null || $verdict === null) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, 'Sesi eksekusi tidak lengkap. Ulangi /eksekusi.');

                return;
            }

            $this->warehouse->executeItem(
                $item,
                $verdict,
                $actor->user_name,
                $payload['ba_number'] ?? null,
                $payload['recipient'] ?? null,
                $payload['nik'] ?? null,
                $photoPath ?: null,
            );

            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "✅ Eksekusi tercatat: {$this->e($item->item_name)} — {$verdict->label()}");
            $this->broadcastGroup("⚖️ Eksekusi {$this->e($item->physicalUnit?->unit_code ?? '')} — {$verdict->label()}");
        }
    }

    private function handleEksekusiCallback(
        TelegramWhitelist $actor,
        int|string $chatId,
        string $callbackId,
        TelegramConversation $conversation,
        string $data,
    ): void {
        $payload = $conversation->payload ?? [];

        if (preg_match('/^ei_(\d+)$/', $data, $match) === 1) {
            $item = UnitItem::query()->find((int) $match[1]);
            if ($item === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Item tidak ditemukan.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);
            $payload['item_id'] = $item->id;
            $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_verdict', null, $payload);
            $this->reply($chatId, "Pilih putusan untuk:\n<b>{$this->e($item->item_name)}</b>", $this->verdictKeyboard($item->id));

            return;
        }

        if (preg_match('/^ev_(\d+)_([A-Z]+)$/', $data, $match) === 1) {
            $map = [
                'MUS' => VerdictStatus::Dimusnahkan,
                'KMB' => VerdictStatus::Dikembalikan,
                'LEL' => VerdictStatus::DirampasNegaraLelang,
                'PSP' => VerdictStatus::Psp,
            ];
            $verdict = $map[$match[2]] ?? null;

            if ($verdict === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Putusan tidak valid.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);
            $payload['item_id'] = (int) $match[1];
            $payload['verdict'] = $verdict->value;
            $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_ba', null, $payload);
            $this->reply($chatId, "Putusan: <b>{$verdict->label()}</b>\nKirim nomor BA eksekusi, atau ketik <code>-</code>.");

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    private function promptLoanDetails(TelegramWhitelist $actor, int|string $chatId, PhysicalUnit $unit): void
    {
        if ($unit->current_status !== UnitStatus::TersimpanGudang) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "Unit {$this->e($unit->unit_code)} tidak tersimpan di gudang (status: {$unit->current_status->label()}).");

            return;
        }

        $this->putConversation((int) $actor->telegram_chat_id, 'pinjam', 'awaiting_loan_prosecutors', null, [
            'unit_id' => $unit->id,
            'prosecutor_ids' => [],
        ]);
        $this->promptProsecutorPicker(
            $chatId,
            "📤 <b>Pinjam sidang</b>\n{$this->unitHeader($unit)}\n\nPilih <b>JPU peminjam</b> (boleh lebih dari satu), lalu tekan <b>Selesai pilih JPU</b>."
        );
    }

    private function promptReturnDetails(TelegramWhitelist $actor, int|string $chatId, PhysicalUnit $unit): void
    {
        if ($unit->current_status !== UnitStatus::DipinjamSidang) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "Unit {$this->e($unit->unit_code)} tidak sedang dipinjam sidang.");

            return;
        }

        $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_location', null, ['unit_id' => $unit->id]);
        $this->reply(
            $chatId,
            "📥 <b>Kembali gudang</b>\n{$this->unitHeader($unit)}\n\nPilih <b>tempat penyimpanan</b> (sekarang: {$this->e($unit->storageLocation?->name ?? $unit->storage_location)}).",
            $this->locationKeyboard()
        );
    }

    private function handleKembaliCallback(
        TelegramWhitelist $actor,
        int|string $chatId,
        string $callbackId,
        TelegramConversation $conversation,
        string $data,
    ): void {
        $payload = $conversation->payload ?? [];

        if (str_starts_with($data, 'loc_') && $conversation->step === 'awaiting_location') {
            $location = $this->resolveStorageLocation($data);
            if ($location === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Lokasi tidak valid.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);
            $payload['storage_location'] = $location->name;
            $payload['storage_location_id'] = $location->id;
            $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_notes', null, $payload);
            $this->reply($chatId, 'Kirim catatan kondisi fisik (atau ketik <code>-</code> jika tidak ada).');

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    private function promptEksekusiItems(int|string $chatId, TelegramWhitelist $actor, PhysicalUnit $unit): void
    {
        $pending = $unit->items->filter(fn (UnitItem $item) => $item->verdict_status === VerdictStatus::MenungguPutusan);

        if ($pending->isEmpty()) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "Tidak ada item menunggu putusan pada {$this->e($unit->unit_code)}.");

            return;
        }

        $rows = $pending->map(fn (UnitItem $item) => [[
            'text' => mb_strimwidth($item->item_name, 0, 40, '…'),
            'callback_data' => 'ei_'.$item->id,
        ]])->values()->all();

        $this->putConversation((int) $actor->telegram_chat_id, 'eksekusi', 'awaiting_item', null, ['unit_id' => $unit->id]);
        $this->reply($chatId, "⚖️ Pilih item untuk dieksekusi pada <code>{$this->e($unit->unit_code)}</code>:", $rows);
    }

    private function sendSearchResults(int|string $chatId, string $query): void
    {
        $units = PhysicalUnit::query()
            ->with(['legalCase', 'items'])
            ->search($query)
            ->latest()
            ->limit(8)
            ->get();

        if ($units->isEmpty()) {
            $this->reply($chatId, 'Tidak ada hasil untuk: '.$this->e($query));

            return;
        }

        $rows = $units->map(fn (PhysicalUnit $unit) => [[
            'text' => $unit->unit_code.' · '.$unit->legalCase?->defendant_name,
            'callback_data' => 'pick_'.$unit->id,
        ]])->all();

        $this->reply($chatId, 'Hasil pencarian:', $rows);
    }

    private function sendRekap(int|string $chatId): void
    {
        $gudang = PhysicalUnit::query()->where('current_status', UnitStatus::TersimpanGudang)->count();
        $pinjam = PhysicalUnit::query()->where('current_status', UnitStatus::DipinjamSidang)->count();
        $selesai = PhysicalUnit::query()->where('current_status', UnitStatus::Selesai)->count();
        $unprinted = PhysicalUnit::query()->where('is_printed', false)->count();
        $cases = LegalCase::query()->count();

        $loans = PhysicalUnit::query()
            ->with('legalCase')
            ->where('current_status', UnitStatus::DipinjamSidang)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (PhysicalUnit $unit) => '• '.$unit->unit_code.' — '.$unit->legalCase?->defendant_name)
            ->implode("\n");

        $loanBlock = $loans !== '' ? "\n\n<b>Pinjaman sidang aktif</b>\n{$loans}" : "\n\nTidak ada pinjaman sidang aktif.";

        $this->reply(
            $chatId,
            "📊 <b>Rekap gudang SIBATA-BB</b>\n"
            ."Perkara: <b>{$cases}</b>\n"
            ."Tersimpan gudang: <b>{$gudang}</b>\n"
            ."Dipinjam sidang: <b>{$pinjam}</b>\n"
            ."Selesai: <b>{$selesai}</b>\n"
            ."Antrean cetak label: <b>{$unprinted}</b>"
            .$loanBlock
        );
    }

    /**
     * @param  list<array<string, mixed>>  $photos
     */
    private function handlePhotoScan(int|string $chatId, array $photos): void
    {
        try {
            $relative = $this->telegram->downloadPhoto($this->largestPhotoFileId($photos), 'telegram/scans');
            $absolute = Storage::disk('public')->path($relative);
            $decoded = $this->qrCode->decodeFromFile($absolute);
            Storage::disk('public')->delete($relative);
        } catch (Throwable $exception) {
            Log::warning('Telegram QR scan failed.', ['message' => $exception->getMessage()]);
            $this->reply($chatId, 'Foto QR tidak dapat dibaca. Kirim ulang dengan pencahayaan yang lebih baik, atau ketik kode unit.');

            return;
        }

        $code = $this->warehouse->parseUnitCode((string) $decoded) ?? $this->warehouse->parseUnitCode((string) parse_url((string) $decoded, PHP_URL_PATH));
        $this->respondWithUnit($chatId, $code !== null ? $this->warehouse->findByCode($code) : null);
    }

    private function respondWithUnit(int|string $chatId, ?PhysicalUnit $unit): void
    {
        if ($unit === null) {
            $this->reply($chatId, 'Unit fisik tidak ditemukan di SIBATA-BB.');

            return;
        }

        $unit->loadMissing(['legalCase', 'items']);
        $this->reply($chatId, $this->unitCard($unit));
    }

    private function sendUnitSummary(int|string $chatId, PhysicalUnit $unit, TelegramWhitelist $actor): void
    {
        $unit->loadMissing(['legalCase', 'items']);
        $printUrl = url('/print-labels?ids='.$unit->id);
        $viewUrl = $unit->publicViewUrl();

        $this->reply(
            $chatId,
            "✅ Tercatat atas nama {$this->e($actor->user_name)}.\n{$this->unitCard($unit)}\n\nCetak label: {$this->e($printUrl)}"
        );

        try {
            $this->telegram->sendPhoto(
                $chatId,
                $this->qrCode->pngBinary($viewUrl, 10),
                "QR {$unit->unit_code}"
            );
        } catch (Throwable $exception) {
            Log::warning('Failed sending SIBATA-BB QR photo.', ['message' => $exception->getMessage()]);
        }
    }

    private function unitCard(PhysicalUnit $unit): string
    {
        $items = $unit->items->map(fn (UnitItem $item) => '• '.$item->item_name.' ('.$item->categoryLabel().')')->implode("\n");

        return implode("\n", [
            "<b>{$this->e($unit->unit_code)}</b> · {$unit->unit_type->label()}",
            'Perkara: '.$this->e((string) $unit->legalCase?->case_number),
            'Terdakwa: '.$this->e((string) $unit->legalCase?->defendant_name),
            'Lokasi: '.$this->e($unit->storage_location),
            'Status: <b>'.$unit->current_status->label().'</b>',
            $items !== '' ? "Isi:\n{$items}" : 'Isi: -',
        ]);
    }

    private function unitHeader(PhysicalUnit $unit): string
    {
        $unit->loadMissing('legalCase');

        return "<code>{$this->e($unit->unit_code)}</code> — {$this->e((string) $unit->legalCase?->defendant_name)}\n".$this->e($unit->itemsSummary());
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function resolveUnitFromInput(array $message, string $text): ?PhysicalUnit
    {
        if (isset($message['photo']) && is_array($message['photo'])) {
            try {
                $relative = $this->telegram->downloadPhoto($this->largestPhotoFileId($message['photo']), 'telegram/scans');
                $decoded = $this->qrCode->decodeFromFile(Storage::disk('public')->path($relative));
                Storage::disk('public')->delete($relative);
                $code = $this->warehouse->parseUnitCode((string) $decoded);

                return $code !== null ? $this->warehouse->findByCode($code) : null;
            } catch (Throwable) {
                return null;
            }
        }

        return $this->warehouse->findByCode($text !== '' ? $text : null);
    }

    private function isSkipCommand(string $text): bool
    {
        return in_array(strtolower(trim($text)), ['/skip', 'skip', '-'], true);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return string|false|null false = invalid input, null = skipped, string = path
     */
    private function optionalPhotoPath(array $message, string $text, string $directory): string|false|null
    {
        if ($this->isSkipCommand($text)) {
            return null;
        }

        if (! isset($message['photo']) || ! is_array($message['photo'])) {
            return false;
        }

        return $this->telegram->downloadPhoto($this->largestPhotoFileId($message['photo']), $directory);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseTwoNames(string $text): array
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if (str_contains($normalized, '|')) {
            [$left, $right] = array_pad(array_map('trim', explode('|', $normalized, 2)), 2, '');

            return [$left, $right];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $normalized)), fn ($line) => $line !== ''));

        return [$lines[0] ?? '', $lines[1] ?? ''];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseNameAndQty(string $text): array
    {
        if (str_contains($text, '|')) {
            [$name, $qty] = array_pad(array_map('trim', explode('|', $text, 2)), 2, '');

            return [$name !== '' ? $name : $text, $qty !== '' ? $qty : '1'];
        }

        return [trim($text), '1'];
    }

    /**
     * @param  array<int, int>  $selectedIds
     * @return array<int, array<int, array<string, string>>>
     */
    private function prosecutorKeyboard(array $selectedIds): array
    {
        $rows = [];

        foreach (Prosecutor::active()->get() as $prosecutor) {
            $mark = in_array((int) $prosecutor->id, $selectedIds, true) ? '✓ ' : '';
            $label = $mark.$prosecutor->name;

            if (mb_strlen($label) > 60) {
                $label = mb_substr($label, 0, 59).'…';
            }

            $rows[] = [['text' => $label, 'callback_data' => 'jpu_'.$prosecutor->id]];
        }

        $rows[] = [['text' => 'Selesai pilih JPU', 'callback_data' => 'jpu_done']];

        return $rows;
    }

    private function promptProsecutorPicker(int|string $chatId, string $intro): void
    {
        if (Prosecutor::active()->doesntExist()) {
            $this->reply($chatId, 'Belum ada JPU di data master. Tambah dulu di portal: Data Master → JPU. Ketik /batal.');

            return;
        }

        $this->reply($chatId, $intro, $this->prosecutorKeyboard([]));
    }

    /**
     * @return array<int, int>
     */
    private function selectedProsecutorIds(array $payload): array
    {
        return array_values(array_unique(array_map('intval', $payload['prosecutor_ids'] ?? [])));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function selectedProsecutorNames(array $payload): string
    {
        $ids = $this->selectedProsecutorIds($payload);

        if ($ids === []) {
            return 'belum ada';
        }

        $names = Prosecutor::query()->whereIn('id', $ids)->orderBy('name')->pluck('name');

        return $names->isEmpty() ? 'belum ada' : $names->implode('; ');
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function callbackMessageId(array $message): ?int
    {
        return isset($message['message_id']) ? (int) $message['message_id'] : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function prosecutorPickerText(string $context, array $payload): string
    {
        return $context."\n\nDipilih: <b>{$this->e($this->selectedProsecutorNames($payload))}</b>\n\nCentang JPU, lalu tekan <b>Selesai pilih JPU</b>.";
    }

    /**
     * @return bool true if the callback was handled
     */
    private function handleProsecutorCallback(
        TelegramWhitelist $actor,
        int|string $chatId,
        string $callbackId,
        TelegramConversation $conversation,
        string $data,
        ?int $messageId,
        string $action,
    ): bool {
        $step = $conversation->step;
        $allowed = $action === 'tambah'
            ? ['awaiting_prosecutors']
            : ['awaiting_loan_prosecutors', 'awaiting_borrower'];

        if (! in_array($step, $allowed, true)) {
            return false;
        }

        $payload = $conversation->payload ?? [];

        if (preg_match('/^jpu_(\d+)$/', $data, $match) === 1) {
            $id = (int) $match[1];
            $selected = $this->selectedProsecutorIds($payload);

            if (in_array($id, $selected, true)) {
                $selected = array_values(array_filter($selected, fn (int $item): bool => $item !== $id));
                $this->telegram->answerCallbackQuery($callbackId, 'Dilepas');
            } else {
                $selected[] = $id;
                $this->telegram->answerCallbackQuery($callbackId, 'Ditandai');
            }

            $payload['prosecutor_ids'] = $selected;
            $this->putConversation((int) $actor->telegram_chat_id, $action, $step === 'awaiting_borrower' ? 'awaiting_loan_prosecutors' : $step, null, $payload);

            $context = $action === 'tambah'
                ? 'Pilih <b>JPU</b> (boleh lebih dari satu).'
                : "📤 <b>Pinjam sidang</b>\nPilih <b>JPU peminjam</b> (boleh lebih dari satu).";

            if ($messageId !== null) {
                $this->telegram->editMessage($chatId, $messageId, $this->prosecutorPickerText($context, $payload), $this->prosecutorKeyboard($selected));
            }

            return true;
        }

        if ($data !== 'jpu_done') {
            return false;
        }

        $ids = $this->selectedProsecutorIds($payload);
        $prosecutors = Prosecutor::query()->whereIn('id', $ids)->where('is_active', true)->orderBy('name')->get();

        if ($prosecutors->isEmpty()) {
            $this->telegram->answerCallbackQuery($callbackId, 'Pilih minimal satu JPU.');

            return true;
        }

        $this->telegram->answerCallbackQuery($callbackId);
        $names = $prosecutors->pluck('name')->implode('; ');

        if ($action === 'tambah') {
            $case = LegalCase::query()->create([
                'case_number' => (string) $payload['case_number'],
                'defendant_name' => (string) $payload['defendant_name'],
                'prosecutor_name' => $names,
            ]);
            $case->prosecutors()->sync($prosecutors->pluck('id')->all());

            $payload['case_id'] = $case->id;
            $payload['prosecutor_name'] = $names;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_type', null, $payload);
            $this->reply($chatId, "JPU: <b>{$this->e($names)}</b>\n\nPilih jenis unit fisik yang akan dicatat:", $this->typeKeyboard());

            return true;
        }

        $payload['borrower_name'] = $names;
        $this->putConversation((int) $actor->telegram_chat_id, 'pinjam', 'awaiting_court_date', null, $payload);
        $this->reply($chatId, "JPU peminjam: <b>{$this->e($names)}</b>\n\nKirim tanggal sidang (<code>YYYY-MM-DD</code>).");

        return true;
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function handlePinjamCallback(
        TelegramWhitelist $actor,
        int|string $chatId,
        string $callbackId,
        TelegramConversation $conversation,
        string $data,
        ?int $messageId,
    ): void {
        if ($this->handleProsecutorCallback($actor, $chatId, $callbackId, $conversation, $data, $messageId, 'pinjam')) {
            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function typeKeyboard(): array
    {
        return [
            [['text' => '+ Tambah BB Mandiri (Satuan)', 'callback_data' => 'type_SINGLE']],
            [['text' => '+ Tambah BB Paket (Wadah/Segel)', 'callback_data' => 'type_PACK']],
        ];
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function categoryKeyboard(): array
    {
        $categories = EvidenceCategory::active()->get();
        if ($categories->isEmpty()) {
            $categories = collect(ItemCategory::cases())->map(fn (ItemCategory $category) => (object) [
                'name' => $category->label(),
                'code' => $category->value,
            ]);
        }

        $row = [];
        $rows = [];

        foreach ($categories as $category) {
            $row[] = ['text' => $category->name, 'callback_data' => 'cat_'.$category->code];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function locationKeyboard(): array
    {
        $row = [];
        $rows = [];

        foreach (StorageLocation::active()->get() as $location) {
            $row[] = ['text' => $location->name, 'callback_data' => 'loc_'.$location->id];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function resolveStorageLocation(string $dataOrName): ?StorageLocation
    {
        if (preg_match('/^loc_(\d+)$/', $dataOrName, $match) === 1) {
            return StorageLocation::query()->where('is_active', true)->find((int) $match[1]);
        }

        $name = trim($dataOrName);
        if ($name === '') {
            return null;
        }

        return StorageLocation::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyTypedStorageLocation(array &$payload, string $text): bool
    {
        $location = $this->resolveStorageLocation($text);
        if ($location === null) {
            return false;
        }

        $payload['storage_location'] = $location->name;
        $payload['storage_location_id'] = $location->id;

        return true;
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function verdictKeyboard(int $itemId): array
    {
        return [
            [
                ['text' => 'Dimusnahkan', 'callback_data' => 'ev_'.$itemId.'_MUS'],
                ['text' => 'Dikembalikan', 'callback_data' => 'ev_'.$itemId.'_KMB'],
            ],
            [
                ['text' => 'Dirampas/Lelang', 'callback_data' => 'ev_'.$itemId.'_LEL'],
                ['text' => 'PSP', 'callback_data' => 'ev_'.$itemId.'_PSP'],
            ],
        ];
    }

    private function sendMainMenu(int|string $chatId, string $text): void
    {
        $this->telegram->sendMessage($chatId, $text, [
            'keyboard' => [
                [['text' => '/tambah'], ['text' => '/cari']],
                [['text' => '/pinjam'], ['text' => '/kembali']],
                [['text' => '/eksekusi'], ['text' => '/rekap']],
            ],
            'resize_keyboard' => true,
        ], $this->replyContext);

        $this->reply($chatId, 'Pilih aksi cepat:', [
            [
                ['text' => '➕ Tambah BB', 'callback_data' => 'menu_tambah'],
                ['text' => '🔎 Cari', 'callback_data' => 'menu_cari'],
            ],
            [['text' => '📊 Rekap gudang', 'callback_data' => 'menu_rekap']],
        ]);
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function authorize(int $telegramId, int|string $chatId, bool $silent = false, array $from = []): ?TelegramWhitelist
    {
        $linked = User::query()->where('telegram_id', $telegramId)->first();

        if ($linked === null || ! $linked->is_active || ! $linked->hasValidLicense()) {
            return null;
        }

        return TelegramWhitelist::findActive($telegramId);
    }

    private function handleGuestMessage(int $telegramId, int|string $chatId, string $text): void
    {
        if (in_array($text, ['/batal', '/batalkan', '/cancel'], true)) {
            $this->clearConversation($telegramId);
            $this->reply($chatId, 'Pendaftaran dibatalkan. Ketik /start untuk mendaftar lagi.');

            return;
        }

        $conversation = TelegramConversation::query()->where('telegram_user_id', $telegramId)->first();

        if ($text === '/start' || $conversation === null || $conversation->action !== 'daftar') {
            $this->startDaftar($telegramId, $chatId);

            return;
        }

        $this->continueDaftar($telegramId, $chatId, $conversation, $text);
    }

    private function startDaftar(int $telegramId, int|string $chatId): void
    {
        $this->putConversation($telegramId, 'daftar', 'awaiting_name');
        $this->reply(
            $chatId,
            "Selamat datang di Bot <b>SIBATA-BB</b>.\n"
            ."Sistem Informasi Barang Bukti dan Barang Rampasan — Seksi PB3R Kejari Wajo.\n\n"
            .'Masukkan <b>nama akun</b> Anda sesuai yang tercatat di portal.'
        );
    }

    private function continueDaftar(int $telegramId, int|string $chatId, TelegramConversation $conversation, string $text): void
    {
        $payload = $conversation->payload ?? [];

        if ($conversation->step === 'awaiting_name') {
            $name = trim($text);
            if ($name === '' || str_starts_with($name, '/')) {
                $this->reply($chatId, 'Masukkan nama akun di portal, bukan perintah. Contoh: <code>Syawal</code>');

                return;
            }

            $matches = $this->findPortalUsersByName($name);

            if ($matches->isEmpty()) {
                $this->putConversation($telegramId, 'daftar', 'awaiting_name');
                $this->reply(
                    $chatId,
                    "Nama <b>{$this->e($name)}</b> belum ada di portal.\n\n"
                    .'Minta administrator menambahkan Anda di menu <b>Pengguna &amp; Lisensi</b>, lalu ketik /start lagi.'
                );

                return;
            }

            if ($matches->count() > 1) {
                $this->reply($chatId, 'Nama itu dipakai lebih dari satu akun. Minta admin merapikan nama di portal, atau kirim nama yang unik.');

                return;
            }

            $user = $matches->first();

            if (! $user->is_active) {
                $this->reply($chatId, "Akun <b>{$this->e($user->name)}</b> nonaktif. Minta administrator mengaktifkannya di portal.");

                return;
            }

            if (! $user->hasValidLicense()) {
                $this->reply($chatId, "Akun <b>{$this->e($user->name)}</b> ditemukan, tetapi lisensinya belum berlaku. Minta administrator menerbitkan lisensi di portal.");

                return;
            }

            $payload['display_name'] = $user->name;
            $payload['user_id'] = $user->id;
            $this->putConversation($telegramId, 'daftar', 'awaiting_license', null, $payload);
            $this->reply(
                $chatId,
                "Nama <b>{$this->e($user->name)}</b> ditemukan.\n\n"
                .'Masukkan <b>kode lisensi</b> dari menu Pengguna &amp; Lisensi.\nContoh: <code>SIBATA-XXXX-XXXX-XXXX</code>'
            );

            return;
        }

        if ($conversation->step === 'awaiting_license') {
            $expectedUserId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $this->redeemLicense($telegramId, $chatId, $text, [], $payload['display_name'] ?? null, $expectedUserId);

            return;
        }

        $this->startDaftar($telegramId, $chatId);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function findPortalUsersByName(string $name)
    {
        $needle = mb_strtolower(trim($name), 'UTF-8');

        return User::query()
            ->get()
            ->filter(fn (User $user): bool => mb_strtolower(trim((string) $user->name), 'UTF-8') === $needle)
            ->values();
    }

    private function isLicenseCommand(string $text): bool
    {
        return str_starts_with($text, '/lisensi') || $this->extractLicenseKey($text) !== null;
    }

    private function extractLicenseKey(string $text): ?string
    {
        $normalized = UserLicense::normalize($text);
        $normalized = (string) preg_replace('/^\/LISENSI/', '', $normalized);

        if (preg_match('/SI(?:TABA|BATA)(?:-[A-Z0-9]+){2,}/', $normalized, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function redeemLicense(int $telegramId, int|string $chatId, string $text, array $from, ?string $displayName = null, ?int $expectedUserId = null): void
    {
        $key = $this->extractLicenseKey($text);

        if ($key === null) {
            $this->reply($chatId, 'Kode lisensi belum terbaca. Kirim kode dari admin portal.\nContoh: <code>SIBATA-XXXX-XXXX-XXXX</code>');

            return;
        }

        $user = User::query()->where('license_key', $key)->first();

        if ($user === null || ! $user->hasValidLicense() || ! $user->is_active) {
            $this->reply($chatId, 'Kode lisensi tidak valid atau sudah dicabut. Hubungi administrator portal.');

            return;
        }

        if ($expectedUserId !== null && $user->id !== $expectedUserId) {
            $this->reply($chatId, 'Kode lisensi ini tidak sesuai dengan nama yang Anda masukkan. Minta kode milik akun Anda di portal.');

            return;
        }

        if ($user->telegram_id && (int) $user->telegram_id !== $telegramId) {
            $this->reply($chatId, 'Kode lisensi ini sudah terpasang di akun Telegram lain.');

            return;
        }

        $name = trim((string) $displayName);
        if ($name === '') {
            $name = $user->name;
        }

        $user->forceFill(['telegram_id' => $telegramId])->save();

        TelegramWhitelist::query()->updateOrCreate(
            ['telegram_chat_id' => (string) $telegramId],
            [
                'user_name' => $name,
                'role' => $user->role->telegramRole(),
                'is_active' => true,
            ]
        );

        $this->clearConversation($telegramId);
        $this->reply(
            $chatId,
            "✅ Selamat datang, <b>{$this->e($name)}</b>. Lisensi bot aktif ({$user->role->label()}).\n\nKetik /start untuk membuka menu."
        );
    }

    private function welcomeText(TelegramWhitelist $actor, bool $inGroup = false): string
    {
        $extra = $inGroup ? "\n/id — tampilkan ID grup ini" : '';

        return "Selamat datang di <b>SIBATA-BB</b>, {$this->e($actor->user_name)} ({$actor->role->label()}).\n\n"
            ."Sistem Informasi Barang Bukti dan Barang Rampasan — Seksi PB3R Kejari Wajo.\n\n"
            ."Perintah:\n/tambah /cari /pinjam /kembali /eksekusi /rekap\n/lisensi — aktifkan akses bot\n/batal — batalkan proses{$extra}";
    }

    /**
     * @param  array<int, array<int, array<string, string>>>|null  $inlineKeyboard
     */
    private function reply(int|string $chatId, string $text, ?array $inlineKeyboard = null): void
    {
        $this->telegram->sendMessage($chatId, $text, $inlineKeyboard === null ? null : [
            'inline_keyboard' => $inlineKeyboard,
        ], $this->replyContext);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function captureReplyContext(array $message): void
    {
        $this->replyContext = [];

        if (isset($message['message_id'])) {
            $this->replyContext['reply_to_message_id'] = $message['message_id'];
            $this->replyContext['allow_sending_without_reply'] = true;
        }

        if (isset($message['message_thread_id'])) {
            $this->replyContext['message_thread_id'] = $message['message_thread_id'];
        }
    }

    /**
     * @param  array<string, mixed>  $chat
     */
    private function isGroupChat(array $chat): bool
    {
        return in_array($chat['type'] ?? '', ['group', 'supergroup'], true);
    }

    private function normalizeCommand(string $text): string
    {
        return (string) preg_replace('/^(\/\w+)@[\w_]+/u', '$1', $text);
    }

    private function looksLikeUnitCode(string $text): bool
    {
        return $this->warehouse->parseUnitCode($text) !== null;
    }

    /**
     * @param  list<array<string, mixed>>  $photos
     */
    private function largestPhotoFileId(array $photos): string
    {
        usort($photos, fn (array $a, array $b): int => ((int) ($b['file_size'] ?? 0)) <=> ((int) ($a['file_size'] ?? 0)));
        $fileId = $photos[0]['file_id'] ?? null;

        if (! is_string($fileId) || $fileId === '') {
            throw new RuntimeException('Telegram photo has no file_id.');
        }

        return $fileId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function putConversation(int $telegramUserId, string $action, string $step, ?int $evidenceId = null, array $payload = []): void
    {
        TelegramConversation::query()->updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            [
                'action' => $action,
                'step' => $step,
                'evidence_id' => $evidenceId,
                'payload' => $payload,
            ]
        );
    }

    private function clearConversation(int $telegramUserId): void
    {
        TelegramConversation::query()->where('telegram_user_id', $telegramUserId)->delete();
    }

    private function parseDate(string $text): ?string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', trim($text))?->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function broadcastGroup(string $text): void
    {
        $groupId = config('services.telegram.group_pb3r_id');

        if ($groupId === null || $groupId === '') {
            return;
        }

        try {
            $this->telegram->sendMessage($groupId, $text);
        } catch (Throwable $exception) {
            Log::warning('Failed broadcasting to PB3R group.', ['message' => $exception->getMessage()]);
        }
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
