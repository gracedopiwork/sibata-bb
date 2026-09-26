# SIBATA-BB (Sistem Informasi Barang Bukti dan Barang Rampasan Kejari Wajo)
# Technical Specification & Architecture Blueprint for Cursor AI

You are a Senior Fullstack Laravel & Telegram Bot Engineer. Implement the following project specification strictly using the Laravel ecosystem.

## 1. Project Overview & Scope
- Project Name: SIBATA-BB (Sistem Informasi Barang Bukti dan Barang Rampasan)
- Institution: Seksi PB3R (Pengelolaan Barang Bukti dan Barang Rampasan), Kejaksaan Negeri Wajo
- Core Mission: Eliminate desk scanner limitations and manual paper books by introducing mobile QR-scanning via Telegram Bot, auto-generating standard printable labels, and maintaining end-to-end evidence logs from Tahap II to Inkracht execution.

## 2. Technology Stack & Packages
- Framework: Laravel 11 (PHP 8.3)
- Database: MySQL 8.0 / PostgreSQL 16
- Front-End / Dashboard: Laravel Blade + Tailwind CSS (or Livewire 3 / Filament 3)
- Telegram Engine: Telegram Bot API (Webhook mode, handling photo messages and inline callbacks)
- QR Generation: `simplesoftwareio/simple-qrcode` or `chillerlan/php-qrcode`
- QR Decoder (Server-side): `khanamiryan/qrcode-detector-decoder` or PHP ZXing wrapper
- PDF Engine: `barryvdh/laravel-dompdf` (for label sticker sheets and register books)
- Excel Import/Export: `maatwebsite/excel` (for initial warehouse bulk import and register export)

## 3. Database Architecture & Migrations

### A. Table: `users`
- `id` (bigint, primary)
- `name` (string)
- `nip` (string, nullable)
- `telegram_id` (bigint, unique, nullable) - for authorization whitelist
- `role` (enum: 'admin', 'petugas_pb3r', 'jpu')
- `is_active` (boolean, default: true)
- `timestamps`

### B. Table: `evidence_items`
- `id` (bigint, primary)
- `qr_token` (string, unique) - Format: `BB-WAJO-YYYY-XXXX`
- `no_reg_bb` (string, unique) - Nomor Register Barang Bukti (Form B-4)
- `no_reg_perkara` (string) - Nomor Register Perkara
- `nama_terdakwa` (string)
- `nama_barang` (text)
- `jumlah_satuan` (string) - e.g., "1 Unit", "3 Paket"
- `lokasi_rak` (string) - e.g., "Lemari Besi A-02"
- `status` (enum):
  - `TERSEDIA`
  - `DIPINJAM_SIDANG`
  - `PINJAM_PAKAI`
  - `UJI_LAB_FORENSIK`
  - `SELESAI - DIMUSNAHKAN`
  - `SELESAI - DIKEMBALIKAN`
  - `SELESAI - DILELANG_PNBP`
  - `SELESAI - LAMPIR_BERKAS / PSP`
- `timestamps`

### C. Table: `evidence_logs`
- `id` (bigint, primary)
- `evidence_id` (foreignId -> `evidence_items.id`, cascadeOnDelete)
- `user_id` (foreignId -> `users.id`, nullable) - Actor
- `action_type` (enum: 'REGISTER', 'PINJAM', 'KEMBALI', 'RELOKASI', 'EKSEKUSI')
- `borrower_name` (string, nullable) - JPU / pihak peminjam
- `purpose` (text, nullable) - e.g., "Sidang Pembuktian di PN Sengkang"
- `photo_proof_path` (string, nullable) - Local server storage path
- `notes` (text, nullable)
- `timestamps`

## 4. Telegram Bot Architecture (`TelegramWebhookController`)

### Security & Whitelist Middleware
- Receive incoming update from Telegram Webhook (`POST /api/telegram/webhook`).
- Validate incoming `update.message.from.id` or `update.callback_query.from.id`.
- Check against `users` table where `is_active = true`. If unauthorized, respond with polite access denied message and terminate.

### Vision & QR Code Recognition Flow
1. Petugas sends/snaps a photo of the QR sticker inside the Telegram chat.
2. The controller reads `photo` array (takes largest resolution), fetches image via Telegram `getFile` API.
3. Decodes the QR token directly from the image using the server-side QR reader.
4. Queries `evidence_items` using `qr_token`:
   - If not found: Respond: "Barang bukti tidak terdaftar di sistem SIBATA-BB."
   - If found:
     - Check current `status`:
       - If `status == 'TERSEDIA'`:
         - Return item summary (No. Reg BB, Terdakwa, Barang, Posisi Rak).
         - Attach Inline Keyboard:
           - `[📤 Pinjam Sidang]` (callback_data: `loan_{id}`)
           - `[🔬 Uji Lab]` (callback_data: `lab_{id}`)
           - `[📜 Cek Riwayat]` (callback_data: `hist_{id}`)
       - If `status == 'DIPINJAM_SIDANG'`:
         - Return notice: "BB sedang dipinjam oleh: {borrower_name} untuk keperluan: {purpose}."
         - Attach Inline Keyboard:
           - `[📥 Selesaikan & Kembalikan ke Gudang]` (callback_data: `return_{id}`)
5. Callback Query Handling:
   - `loan_{id}`: Prompts for JPU name & estimated return date -> Updates `status` to `DIPINJAM_SIDANG` -> Inserts record in `evidence_logs` -> Sends confirmation -> Broadcasts notification to PB3R Group (`TELEGRAM_GROUP_PB3R_ID`).
   - `return_{id}`: Prompts to take/send a return condition photo -> Downloads and stores image into `storage/app/evidence/returns/...` -> Updates `status` back to `TERSEDIA` -> Inserts return log with timestamp -> Broadcasts return confirmation to PB3R Group.

## 5. Web Admin Features (Laravel)

### A. Evidence Management
- Standard CRUD for `evidence_items`.
- Excel Bulk Import: Import initial warehouse inventory from `.xlsx`/`.csv` mapping to `evidence_items` and auto-generating `qr_token`.

### B. Printable Sticker Label Generator (DomPDF)
- Generate thermal/sticker sheet layout:
  - Header: "KEJAKSAAN NEGERI WAJO - SEKSI PB3R"
  - Inline Base64 QR Code image (encoding `qr_token`).
  - Text metadata: No. Reg BB, Terdakwa, Nama Barang, Posisi Rak.
- Support both single item print and bulk print.

### C. Register & Reporting Export
- Export Register Peminjaman BB & Form B-4 in PDF and Excel format.

## 6. Coding Standards & Conventions
- Adhere strictly to PSR-12 and standard Laravel 11 folder conventions.
- Use Eloquent ORM relationships (`evidence->logs()`, `log->evidence()`, `user->logs()`).
- Use dedicated Service classes (e.g., `TelegramService`, `QrCodeService`, `EvidenceLogService`).
- Never rely on Telegram CDN `file_id` for persistence; always download images locally via Laravel Storage (`Storage::disk('local')`).
- Keep environment keys inside `.env` (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_URL`, `TELEGRAM_GROUP_PB3R_ID`).