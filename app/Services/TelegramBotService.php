<?php

namespace App\Services;

use App\Models\EvidenceCategory;
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
            "✅ Bot <b>SITABA-BB</b> sudah masuk {$this->e($title)}.\n\n"
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

            $this->redeemLicense($telegramId, $chatId, $text, $from);

            return;
        }

        $actor = $this->authorize($telegramId, $chatId, $isGroup, $from);

        if ($actor === null) {
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
            "Perintah SITABA-BB:\n/tambah — daftar perkara & BB\n/cari kata — cari\n/pinjam KODE — pinjam sidang\n/kembali KODE — kembali gudang\n/eksekusi KODE — catat putusan\n/rekap — ringkasan gudang\n\nAtau kirim foto stiker QR / kode unit."
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
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_category', null, $payload);
            $this->reply($chatId, 'Pilih kategori barang:', $this->categoryKeyboard());

            return;
        }

        if ($conversation->step === 'awaiting_single_location') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim lokasi gudang (contoh: Brankas PB3R Laci 02).');

                return;
            }

            $payload['storage_location'] = $text;
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

            $this->saveSingleFromPayload($actor, $chatId, $payload, $photoPath ?: null);

            return;
        }

        if ($conversation->step === 'awaiting_pack_meta') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim deskripsi wadah dan lokasi, dipisah | atau baris baru.');

                return;
            }

            [$desc, $location] = $this->parseTwoNames($text);
            if ($location === '') {
                $location = $desc;
                $desc = 'Paket/wadah tersegel';
            }

            $payload['pack_description'] = $desc;
            $payload['storage_location'] = $location;
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

            $this->createPackShell($actor, $chatId, $payload, $photoPath ?: null);

            return;
        }

        if ($conversation->step === 'awaiting_child_name') {
            if ($text === '') {
                $this->reply($chatId, 'Kirim rincian isi paket.');

                return;
            }

            [$name, $qty] = $this->parseNameAndQty($text);
            $payload['child_name'] = $name;
            $payload['child_qty'] = $qty;
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_category', null, $payload);
            $this->reply($chatId, 'Pilih kategori isi paket:', $this->categoryKeyboard());

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
            $this->reply(
                $chatId,
                "Kirim deskripsi wadah dan lokasi gudang.\nContoh:\nKantong plastik segel\nBrankas PB3R Laci 02"
            );

            return;
        }

        if (str_starts_with($data, 'cat_') && in_array($conversation->step, ['awaiting_single_category', 'awaiting_child_category'], true)) {
            $category = EvidenceCategory::activeCode(substr($data, 4));
            if ($category === null) {
                $this->telegram->answerCallbackQuery($callbackId, 'Kategori tidak valid.');

                return;
            }

            $this->telegram->answerCallbackQuery($callbackId);

            if ($conversation->step === 'awaiting_single_category') {
                $payload['category'] = $category;
                $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_single_location', null, $payload);
                $this->reply($chatId, 'Kirim <b>lokasi gudang</b> (contoh: Parkiran BB No. 04).');

                return;
            }

            $this->savePackChild($actor, $chatId, $payload, $category);

            return;
        }

        if ($data === 'add_more') {
            $this->telegram->answerCallbackQuery($callbackId);
            unset($payload['item_name'], $payload['quantity'], $payload['category'], $payload['storage_location'], $payload['unit_id'], $payload['child_name'], $payload['child_qty'], $payload['pack_description']);
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
            $index = ((int) ($payload['child_index'] ?? 1)) + 1;
            $payload['child_index'] = $index;
            unset($payload['child_name'], $payload['child_qty']);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_name', null, $payload);
            $this->reply($chatId, "Rincian barang ke-{$index} di dalam paket:");

            return;
        }

        if ($data === 'pack_done') {
            $unit = PhysicalUnit::query()->with(['legalCase', 'items'])->find((int) ($payload['unit_id'] ?? 0));
            $this->telegram->answerCallbackQuery($callbackId);

            if ($unit === null || $unit->items->isEmpty()) {
                $this->reply($chatId, 'Paket masih kosong. Tambahkan minimal satu isi.');

                return;
            }

            $this->sendUnitSummary($chatId, $unit, $actor);
            $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_more', null, $payload);
            $this->reply($chatId, 'Paket selesai. Tambah unit lain untuk perkara yang sama?', [
                [
                    ['text' => '+ Tambah Lagi', 'callback_data' => 'add_more'],
                    ['text' => 'Selesai', 'callback_data' => 'add_done'],
                ],
            ]);

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);
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
        );

        $this->sendUnitSummary($chatId, $unit, $actor);
        $payload['unit_id'] = $unit->id;
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_more', null, $payload);
        $this->reply($chatId, 'Tambah unit lain untuk perkara yang sama?', [
            [
                ['text' => '+ Tambah Lagi', 'callback_data' => 'add_more'],
                ['text' => 'Selesai', 'callback_data' => 'add_done'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createPackShell(TelegramWhitelist $actor, int|string $chatId, array $payload, ?string $photoPath): void
    {
        $case = LegalCase::query()->find((int) ($payload['case_id'] ?? 0));

        if ($case === null) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, 'Perkara tidak ditemukan. Mulai ulang /tambah.');

            return;
        }

        $unit = $this->warehouse->createPackUnit(
            $case,
            (string) $payload['storage_location'],
            $photoPath,
            $actor->user_name,
            [],
        );

        $payload['unit_id'] = $unit->id;
        $payload['child_index'] = 1;
        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_child_name', null, $payload);
        $this->reply(
            $chatId,
            "✅ Wadah <code>{$this->e($unit->unit_code)}</code> tercatat.\n\nRincian barang ke-1 di dalam paket:"
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function savePackChild(TelegramWhitelist $actor, int|string $chatId, array $payload, string $category): void
    {
        $unit = PhysicalUnit::query()->find((int) ($payload['unit_id'] ?? 0));

        if ($unit === null) {
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, 'Paket tidak ditemukan. Mulai ulang /tambah.');

            return;
        }

        $this->warehouse->addPackChild(
            $unit,
            (string) $payload['child_name'],
            $category,
            (string) ($payload['child_qty'] ?? '1'),
        );

        $this->putConversation((int) $actor->telegram_chat_id, 'tambah', 'awaiting_pack_loop', null, $payload);
        $this->reply($chatId, 'Isi paket ditambahkan. Lanjut?', [
            [
                ['text' => '+ Tambah Isi Lain', 'callback_data' => 'pack_more'],
                ['text' => 'Selesai Paket Ini', 'callback_data' => 'pack_done'],
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

            $unit = PhysicalUnit::query()->find((int) ($payload['unit_id'] ?? 0));
            if ($unit === null) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, 'Unit hilang dari sesi. Ulangi /pinjam.');

                return;
            }

            try {
                $this->warehouse->loan($unit, (string) $payload['borrower_name'], $date, $actor->user_name);
            } catch (RuntimeException $exception) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, $exception->getMessage());

                return;
            }

            $unit->refresh()->load(['legalCase', 'items']);
            $this->clearConversation((int) $actor->telegram_chat_id);
            $this->reply($chatId, "✅ Dipinjam sidang.\n{$this->unitHeader($unit)}\nJPU: {$this->e((string) $payload['borrower_name'])}\nSidang: {$date}");
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
            if ($text === '') {
                $this->reply($chatId, 'Kirim lokasi gudang saat ini.');

                return;
            }

            $payload['storage_location'] = $text;
            $this->putConversation((int) $actor->telegram_chat_id, 'kembali', 'awaiting_notes', null, $payload);
            $this->reply($chatId, 'Kirim catatan kondisi fisik (atau ketik <code>-</code> jika tidak ada).');

            return;
        }

        if ($conversation->step === 'awaiting_notes') {
            $unit = PhysicalUnit::query()->find((int) ($payload['unit_id'] ?? 0));
            if ($unit === null) {
                $this->clearConversation((int) $actor->telegram_chat_id);
                $this->reply($chatId, 'Unit hilang dari sesi. Ulangi /kembali.');

                return;
            }

            $notes = $text === '-' ? null : $text;

            try {
                $this->warehouse->returnToWarehouse($unit, (string) $payload['storage_location'], $actor->user_name, $notes);
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
            "📥 <b>Kembali gudang</b>\n{$this->unitHeader($unit)}\n\nKonfirmasi lokasi gudang (sekarang: {$this->e($unit->storage_location)})."
        );
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
            "📊 <b>Rekap gudang SITABA-BB</b>\n"
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
            $this->reply($chatId, 'Unit fisik tidak ditemukan di SITABA-BB.');

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
            Log::warning('Failed sending SITABA-BB QR photo.', ['message' => $exception->getMessage()]);
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

    /**
     * @param  array<string, mixed>  $message
     * @return string|false|null false = invalid input, null = skipped, string = path
     */
    private function optionalPhotoPath(array $message, string $text, string $directory): string|false|null
    {
        if (in_array(strtolower($text), ['/skip', 'skip', '-'], true)) {
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
        $row = [];
        $rows = [];

        foreach (EvidenceCategory::active()->get() as $category) {
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
        $actor = TelegramWhitelist::findActive($telegramId);

        if ($actor !== null) {
            $linked = User::query()->where('telegram_id', $telegramId)->first();

            if ($linked !== null && (! $linked->is_active || ! $linked->hasValidLicense())) {
                if (! $silent) {
                    $this->reply(
                        $chatId,
                        'Lisensi bot Anda tidak berlaku. Hubungi admin portal untuk menerbitkan ulang, lalu ketik /lisensi KODE.'
                    );
                }

                return null;
            }

            return $actor;
        }

        if ($this->claimFirstAdmin($telegramId, $from)) {
            $actor = TelegramWhitelist::findActive($telegramId);

            if ($actor !== null && ! $silent) {
                $this->reply(
                    $chatId,
                    "✅ Akun Telegram Anda didaftarkan sebagai <b>Admin PB3R</b> pertama.\n"
                    ."ID: <code>{$telegramId}</code>\n\n"
                    .'Ketik /start untuk membuka menu.'
                );
            }

            return $actor;
        }

        if (! $silent) {
            $this->reply(
                $chatId,
                'Akses bot ditolak. Minta kode lisensi ke admin portal, lalu ketik:\n<code>/lisensi SITABA-XXXX-XXXX-XXXX</code>'
            );
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function claimFirstAdmin(int $telegramId, array $from): bool
    {
        if (! (bool) config('services.telegram.allow_first_admin', true)) {
            return false;
        }

        $hasRealAdmin = TelegramWhitelist::query()
            ->where('is_active', true)
            ->get()
            ->contains(fn (TelegramWhitelist $row): bool => strlen((string) $row->telegram_chat_id) >= 6);

        if ($hasRealAdmin) {
            return false;
        }

        $name = trim(((string) ($from['first_name'] ?? '')).' '.((string) ($from['last_name'] ?? '')));
        if ($name === '') {
            $name = (string) ($from['username'] ?? 'Admin PB3R');
        }

        TelegramWhitelist::query()->updateOrCreate(
            ['telegram_chat_id' => (string) $telegramId],
            [
                'user_name' => $name,
                'role' => TelegramAccessRole::AdminPb3r,
                'is_active' => true,
            ]
        );

        return true;
    }

    private function isLicenseCommand(string $text): bool
    {
        return str_starts_with($text, '/lisensi') || $this->extractLicenseKey($text) !== null;
    }

    private function extractLicenseKey(string $text): ?string
    {
        $normalized = UserLicense::normalize($text);
        $normalized = (string) preg_replace('/^\/LISENSI/', '', $normalized);

        if (preg_match('/SITABA(?:-[A-Z0-9]+){2,}/', $normalized, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function redeemLicense(int $telegramId, int|string $chatId, string $text, array $from): void
    {
        $key = $this->extractLicenseKey($text);

        if ($key === null) {
            $this->reply($chatId, 'Kirim kode lisensi dari admin portal.\nContoh: <code>/lisensi SITABA-XXXX-XXXX-XXXX</code>');

            return;
        }

        $user = User::query()->where('license_key', $key)->first();

        if ($user === null || ! $user->hasValidLicense() || ! $user->is_active) {
            $this->reply($chatId, 'Kode lisensi tidak valid atau sudah dicabut. Hubungi administrator portal.');

            return;
        }

        if ($user->telegram_id && (int) $user->telegram_id !== $telegramId) {
            $this->reply($chatId, 'Kode lisensi ini sudah terpasang di akun Telegram lain.');

            return;
        }

        $user->forceFill(['telegram_id' => $telegramId])->save();

        TelegramWhitelist::query()->updateOrCreate(
            ['telegram_chat_id' => (string) $telegramId],
            [
                'user_name' => $user->name,
                'role' => $user->role->telegramRole(),
                'is_active' => true,
            ]
        );

        $this->reply(
            $chatId,
            "✅ Lisensi bot aktif untuk <b>{$this->e($user->name)}</b> ({$user->role->label()}).\n\nKetik /start untuk membuka menu."
        );
    }

    private function welcomeText(TelegramWhitelist $actor, bool $inGroup = false): string
    {
        $extra = $inGroup ? "\n/id — tampilkan ID grup ini" : '';

        return "Selamat datang di <b>SITABA-BB</b>, {$this->e($actor->user_name)} ({$actor->role->label()}).\n\n"
            ."Sistem Informasi Tata Kelola Barang Bukti — Seksi PB3R Kejari Wajo.\n\n"
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
