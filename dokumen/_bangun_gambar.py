# -*- coding: utf-8 -*-
"""Generate academic PNG figures for Laporan Labkom SIBATA-BB."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
import qrcode

OUT = Path(__file__).resolve().parent / "gambar" / "bukti"
OUT.mkdir(parents=True, exist_ok=True)

NAVY = (11, 31, 58)
GOLD = (201, 162, 39)
INK = (22, 22, 22)
MUTED = (80, 86, 96)
LINE = (30, 36, 48)
BOX = (248, 249, 251)
BOX2 = (236, 240, 246)
WHITE = (255, 255, 255)
GREEN = (28, 110, 68)
RED = (140, 32, 32)
AMBER = (150, 96, 16)
PHONE_BG = (16, 28, 46)
BUBBLE_BOT = (232, 240, 248)
BUBBLE_ME = (214, 232, 210)

TIMES = "C:/Windows/Fonts/times.ttf"
TIMESB = "C:/Windows/Fonts/timesbd.ttf"
TIMESI = "C:/Windows/Fonts/timesi.ttf"
ARIAL = "C:/Windows/Fonts/arial.ttf"
ARIALB = "C:/Windows/Fonts/arialbd.ttf"
CONSOLAS = "C:/Windows/Fonts/consola.ttf"


def F(path: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(path, size)


def new_canvas(w: int, h: int, title: str) -> tuple[Image.Image, ImageDraw.ImageDraw]:
    im = Image.new("RGB", (w, h), WHITE)
    d = ImageDraw.Draw(im)
    d.rectangle((0, 0, w, 54), fill=NAVY)
    d.rectangle((0, 54, w, 58), fill=GOLD)
    d.text((w // 2, 28), title, font=F(TIMESB, 22), fill=WHITE, anchor="mm")
    return im, d


def wrap(d: ImageDraw.ImageDraw, text: str, font, max_w: int) -> list[str]:
    words = text.split()
    lines, cur = [], ""
    for w in words:
        trial = (cur + " " + w).strip()
        if d.textlength(trial, font=font) <= max_w:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines or [""]


def box(d, xy, fill=BOX, outline=LINE, width=2, radius=8):
    d.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def text_center(d, xy, text, font, fill=INK):
    d.text(xy, text, font=font, fill=fill, anchor="mm")


def multiline_center(d, cx, cy, lines, font, fill=INK, gap=16):
    total = (len(lines) - 1) * gap
    y = cy - total / 2
    for line in lines:
        d.text((cx, y), line, font=font, fill=fill, anchor="mm")
        y += gap


def arrow(d, x1, y1, x2, y2, fill=LINE, w=2):
    d.line((x1, y1, x2, y2), fill=fill, width=w)
    import math

    ang = math.atan2(y2 - y1, x2 - x1)
    size = 9
    p1 = (x2, y2)
    p2 = (x2 - size * math.cos(ang - 0.4), y2 - size * math.sin(ang - 0.4))
    p3 = (x2 - size * math.cos(ang + 0.4), y2 - size * math.sin(ang + 0.4))
    d.polygon([p1, p2, p3], fill=fill)


def save(im: Image.Image, name: str) -> Path:
    path = OUT / name
    im.save(path, "PNG", optimize=True)
    print("wrote", path.name, im.size)
    return path


def fig_31a():
    im, d = new_canvas(1600, 980, "Gambar 3.1a  Alur proses bisnis SIBATA-BB")
    f, fs = F(TIMESB, 16), F(TIMES, 14)

    def node(x, y, w, h, lines, fill=BOX):
        box(d, (x, y, x + w, y + h), fill=fill)
        multiline_center(d, x + w / 2, y + h / 2, lines, f if len(lines) == 1 else fs)

    def oval(x, y, w, h, text):
        d.ellipse((x, y, x + w, y + h), fill=NAVY, outline=NAVY)
        text_center(d, (x + w / 2, y + h / 2), text, f, WHITE)

    def diamond(cx, cy, w, h, lines):
        pts = [(cx, cy - h / 2), (cx + w / 2, cy), (cx, cy + h / 2), (cx - w / 2, cy)]
        d.polygon(pts, fill=(255, 248, 230), outline=LINE)
        multiline_center(d, cx, cy, lines, fs)

    oval(40, 90, 110, 50, "Mulai")
    node(190, 82, 180, 66, ["Terima berkas", "Tahap II / BA"])
    node(410, 82, 180, 66, ["Catat perkara", "Web atau /tambah"])
    diamond(720, 115, 170, 90, ["Jenis unit?", "SINGLE / PACK"])
    node(860, 50, 200, 56, ["BB mandiri", "Kode BB-YYYY-XXX"], (232, 244, 236))
    node(860, 130, 200, 56, ["Paket/wadah", "Kode PKT-YYYY-XXX"], (232, 236, 248))
    node(1120, 82, 200, 66, ["Simpan gudang", "+ mutasi CHECK_IN"])

    arrow(d, 150, 115, 190, 115)
    arrow(d, 370, 115, 410, 115)
    arrow(d, 590, 115, 635, 115)
    arrow(d, 800, 90, 860, 78)
    arrow(d, 800, 140, 860, 158)
    d.text((820, 68), "Satuan", font=F(TIMESI, 13), fill=MUTED, anchor="mm")
    d.text((820, 176), "Paket", font=F(TIMESI, 13), fill=MUTED, anchor="mm")
    arrow(d, 1060, 78, 1180, 100)
    arrow(d, 1060, 158, 1180, 130)

    node(40, 250, 200, 66, ["Cetak label QR", "70 × 50 mm"])
    node(280, 250, 200, 66, ["Tempel stiker", "pada unit / wadah"])
    diamond(620, 283, 170, 90, ["Perlu sidang?", "Ya / Tidak"])
    node(760, 250, 210, 66, ["Pinjam sidang", "JPU + tanggal sidang"])
    node(1010, 250, 210, 66, ["Kembali gudang", "lokasi + kondisi"])

    arrow(d, 1220, 148, 1220, 220)
    d.line((140, 220, 1220, 220), fill=LINE, width=2)
    arrow(d, 140, 220, 140, 250)
    arrow(d, 240, 283, 280, 283)
    arrow(d, 480, 283, 535, 283)
    arrow(d, 705, 283, 760, 283)
    arrow(d, 970, 283, 1010, 283)
    d.text((730, 232), "Ya", font=F(TIMESI, 13), fill=GREEN, anchor="mm")

    node(40, 430, 210, 66, ["Putusan inkracht", "dicatat per item BB"])
    diamond(390, 463, 180, 90, ["Jenis putusan", "4 opsi eksekusi"])
    node(540, 390, 180, 48, ["Dimusnahkan"], (255, 236, 236))
    node(740, 390, 180, 48, ["Dikembalikan"], (232, 244, 236))
    node(540, 456, 180, 48, ["Dirampas / lelang"], (255, 244, 220))
    node(740, 456, 180, 48, ["PSP"], (232, 236, 248))
    node(980, 430, 200, 66, ["Catat eksekusi", "BA + foto bukti"])
    oval(1240, 438, 120, 50, "Selesai")

    d.line((620, 328, 620, 370), fill=LINE, width=2)
    d.line((145, 370, 1220, 370), fill=LINE, width=2)
    arrow(d, 145, 370, 145, 430)
    d.text((500, 352), "Tidak / setelah kembali", font=F(TIMESI, 13), fill=MUTED, anchor="mm")
    arrow(d, 250, 463, 300, 463)
    arrow(d, 480, 463, 540, 480)
    arrow(d, 920, 414, 1040, 448)
    arrow(d, 920, 480, 980, 463)
    arrow(d, 1180, 463, 1240, 463)

    box(d, (40, 560, 1560, 920), fill=BOX2, radius=10)
    d.text((60, 582), "Keterangan alur dan jalur pencatatan", font=F(TIMESB, 18), fill=NAVY)
    note = (
        "Satu stiker QR menempel pada unit fisik (BB mandiri atau wadah paket). "
        "Putusan hakim dicatat per rincian isi (sip_evidence_items), sehingga item dalam satu kantong "
        "segel dapat dieksekusi berbeda. Portal web dipakai untuk administrasi, cetak label, dan ekspor "
        "register. Bot Telegram dipakai di lapangan: /tambah, /pinjam, /kembali, /eksekusi, /cari, /rekap "
        "setelah Chat ID petugas masuk daftar putih (telegram_whitelist)."
    )
    y = 620
    for line in wrap(d, note, F(TIMES, 17), 1460):
        d.text((60, y), line, font=F(TIMES, 17), fill=INK)
        y += 26
    d.text((60, 780), "Sumber: spesifikasi operasional SIBATA-BB, Seksi PB3R Kejaksaan Negeri Wajo.", font=F(TIMESI, 15), fill=MUTED)
    save(im, "gambar-3-1a-alur-proses-bisnis.png")


def stick_figure(d, x, y, label, sub):
    d.ellipse((x + 28, y, x + 52, y + 24), outline=NAVY, width=2)
    d.line((x + 40, y + 24, x + 40, y + 62), fill=NAVY, width=2)
    d.line((x + 18, y + 40, x + 62, y + 40), fill=NAVY, width=2)
    d.line((x + 40, y + 62, x + 20, y + 92), fill=NAVY, width=2)
    d.line((x + 40, y + 62, x + 60, y + 92), fill=NAVY, width=2)
    text_center(d, (x + 40, y + 112), label, F(TIMESB, 15), NAVY)
    text_center(d, (x + 40, y + 132), sub, F(TIMESI, 13), MUTED)


def fig_31b():
    im, d = new_canvas(1600, 920, "Gambar 3.1b  Diagram use case UML SIBATA-BB")
    box(d, (280, 90, 1320, 820), fill=WHITE, outline=NAVY, width=3)
    text_center(d, (800, 118), "Sistem SIBATA-BB", F(TIMESB, 20), NAVY)
    stick_figure(d, 70, 200, "Admin PB3R", "(Portal web)")
    stick_figure(d, 70, 520, "Petugas / JPU", "(Bot Telegram)")
    stick_figure(d, 1420, 380, "Pemindai QR", "(Publik)")

    def uc(cx, cy, w, h, text):
        d.ellipse((cx - w / 2, cy - h / 2, cx + w / 2, cy + h / 2), fill=BOX, outline=LINE, width=2)
        text_center(d, (cx, cy), text, F(TIMES, 15))

    left = [
        (500, 190, "Login dashboard"),
        (500, 270, "Registrasi perkara & BB"),
        (500, 350, "Cetak / cetak ulang QR"),
        (500, 430, "Kelola inventaris"),
        (500, 510, "Ekspor register"),
        (500, 590, "Kelola whitelist bot"),
    ]
    right = [
        (1050, 230, "Wizard /tambah"),
        (1050, 310, "Cari BB / rekap"),
        (1050, 390, "Pinjam sidang"),
        (1050, 470, "Kembali gudang"),
        (1050, 550, "Eksekusi putusan"),
        (1050, 650, "Lihat status via QR"),
    ]
    for cx, cy, t in left + right:
        uc(cx, cy, 280, 56, t)
    for cx, cy, _ in left:
        d.line((150, 250, cx - 140, cy), fill=LINE, width=1)
    for cx, cy, t in right:
        if t != "Lihat status via QR":
            d.line((150, 580, cx - 140, cy), fill=LINE, width=1)
    d.line((1420, 430, 1190, 650), fill=LINE, width=1)
    d.text((800, 880), "Aktor di kiri-kanan; elips di dalam batas sistem. Include/extend tidak digambar agar tetap terbaca.", font=F(TIMESI, 14), fill=MUTED, anchor="mm")
    save(im, "gambar-3-1b-uml-use-case.png")


def class_box(d, x, y, w, h, name, attrs, methods):
    box(d, (x, y, x + w, y + h), fill=WHITE, radius=4)
    d.rectangle((x, y, x + w, y + 36), fill=NAVY)
    text_center(d, (x + w / 2, y + 18), name, F(TIMESB, 16), WHITE)
    yy = y + 56
    for a in attrs:
        d.text((x + 14, yy), a, font=F(TIMES, 14), fill=INK)
        yy += 22
    d.line((x + 8, yy - 6, x + w - 8, yy - 6), fill=LINE, width=1)
    yy += 10
    for m in methods:
        d.text((x + 14, yy), m, font=F(TIMESI, 14), fill=MUTED)
        yy += 22


def fig_31c():
    im, d = new_canvas(1600, 920, "Gambar 3.1c  Diagram kelas UML SIBATA-BB")
    class_box(d, 40, 90, 300, 250, "LegalCase",
              ["+ case_number : string", "+ defendant_name : string", "+ prosecutor_name : string", "+ case_status : enum", "+ notes : text"],
              ["+ physicalUnits()"])
    class_box(d, 460, 80, 320, 290, "PhysicalUnit",
              ["+ unit_code : string", "+ unit_type : SINGLE | PACK", "+ storage_location : string", "+ photo_path : string", "+ current_status : enum", "+ is_printed : boolean"],
              ["+ items()", "+ mutations()"])
    class_box(d, 920, 80, 320, 290, "UnitItem",
              ["+ item_name : string", "+ category : enum", "+ quantity : string", "+ verdict_status : enum", "+ execution_ba_number", "+ execution_date : date"],
              ["+ physicalUnit()"])
    class_box(d, 40, 480, 300, 230, "TelegramWhitelist",
              ["+ telegram_chat_id", "+ user_name : string", "+ role : enum", "+ is_active : boolean"],
              ["+ findActive()"])
    class_box(d, 460, 500, 320, 230, "Mutation",
              ["+ mutation_type : enum", "+ borrower_name : string", "+ court_date : date", "+ notes : text", "+ handled_by : string"],
              ["+ physicalUnit()"])
    class_box(d, 920, 500, 320, 230, "User",
              ["+ name : string", "+ email : string", "+ role : admin | petugas", "+ is_active : boolean"],
              ["+ canAccessDashboard()"])

    d.line((340, 200, 460, 200), fill=LINE, width=2)
    d.text((400, 186), "1  memiliki  *", font=F(TIMES, 13), fill=NAVY, anchor="mm")
    d.line((780, 200, 920, 200), fill=LINE, width=2)
    d.text((850, 186), "1  berisi  *", font=F(TIMES, 13), fill=NAVY, anchor="mm")
    d.line((620, 370, 620, 500), fill=LINE, width=2)
    d.text((670, 430), "1  *", font=F(TIMES, 13), fill=NAVY, anchor="mm")
    d.text((800, 870), "Relasi: Case 1—* PhysicalUnit 1—* UnitItem; PhysicalUnit 1—* Mutation. User untuk login web; TelegramWhitelist untuk otorisasi bot.", font=F(TIMESI, 14), fill=MUTED, anchor="mm")
    save(im, "gambar-3-1c-uml-class.png")


def fig_31d():
    im, d = new_canvas(1600, 980, "Gambar 3.1d  Diagram aktivitas UML (swimlane) SIBATA-BB")
    lanes = [(40, "Petugas lapangan (Telegram)"), (560, "Admin PB3R (Web)"), (1080, "Gudang / sidang")]
    for i, (x, title) in enumerate(lanes):
        fill = (248, 250, 252) if i != 1 else (245, 248, 252)
        box(d, (x, 80, x + 500, 900), fill=fill, radius=6)
        d.rectangle((x, 80, x + 500, 120), fill=NAVY)
        text_center(d, (x + 250, 100), title, F(TIMESB, 16), WHITE)

    def act(x, y, w, h, lines, fill=WHITE):
        box(d, (x, y, x + w, y + h), fill=fill)
        multiline_center(d, x + w / 2, y + h / 2, lines, F(TIMES, 15))

    d.ellipse((220, 150, 340, 190), fill=NAVY)
    text_center(d, (280, 170), "Mulai", F(TIMESB, 15), WHITE)
    act(155, 220, 250, 50, ["/start otentikasi"])
    act(155, 300, 250, 56, ["/tambah perkara", "SINGLE atau PACK"])
    act(675, 220, 270, 50, ["Login dashboard admin"])
    act(675, 300, 270, 50, ["Register perkara + unit"])
    act(675, 390, 270, 50, ["Cetak / cetak ulang QR"])
    act(1195, 390, 270, 50, ["Tempel stiker & simpan"])
    act(155, 500, 250, 50, ["/pinjam [kode]"])
    act(1195, 500, 270, 50, ["BB ke ruang sidang"])
    act(155, 590, 250, 50, ["/kembali [kode]"])
    act(1195, 590, 270, 50, ["BB kembali ke rak"])
    act(155, 680, 250, 50, ["/eksekusi item"])
    act(675, 680, 270, 50, ["Catat BA & ekspor"])
    d.ellipse((1270, 780, 1390, 830), fill=NAVY)
    text_center(d, (1330, 805), "Selesai", F(TIMESB, 15), WHITE)

    arrow(d, 280, 190, 280, 220)
    arrow(d, 280, 270, 280, 300)
    arrow(d, 810, 270, 810, 300)
    arrow(d, 810, 350, 810, 390)
    arrow(d, 945, 415, 1195, 415)
    arrow(d, 280, 356, 280, 500)
    arrow(d, 405, 525, 1195, 525)
    arrow(d, 280, 550, 280, 590)
    arrow(d, 405, 615, 1195, 615)
    arrow(d, 280, 640, 280, 680)
    arrow(d, 405, 705, 675, 705)
    arrow(d, 945, 705, 1330, 705)
    arrow(d, 1330, 730, 1330, 780)
    d.text((800, 940), "Aktivitas dapat dimulai dari bot atau web; status gudang dan mutasi tetap satu basis data terpusat.", font=F(TIMESI, 14), fill=MUTED, anchor="mm")
    save(im, "gambar-3-1d-uml-activity.png")


def fig_32():
    im, d = new_canvas(1600, 980, "Gambar 3.2  Entity Relationship Diagram (ERD) kontainer bertingkat SIBATA-BB")

    def entity(x, y, w, h, table, pk, fields):
        box(d, (x, y, x + w, y + h), fill=WHITE, radius=4)
        d.rectangle((x, y, x + w, y + 40), fill=NAVY)
        text_center(d, (x + w / 2, y + 20), table, F(TIMESB, 16), WHITE)
        d.rectangle((x, y + 40, x + w, y + 68), fill=(255, 248, 220))
        d.text((x + 12, y + 54), pk, font=F(TIMESB, 13), fill=INK, anchor="lm")
        yy = y + 88
        for f_ in fields:
            d.text((x + 12, yy), f_, font=F(TIMES, 13), fill=INK)
            yy += 20

    entity(40, 90, 300, 280, "cases", "PK  id", [
        "UK  case_number", "IX  defendant_name", "prosecutor_name",
        "case_status  ENUM", "notes", "created_at / updated_at",
    ])
    entity(620, 80, 340, 320, "physical_units", "PK  id", [
        "FK  case_id → cases.id", "UK  unit_code", "unit_type SINGLE|PACK",
        "storage_location", "photo_path", "current_status ENUM",
        "is_printed BOOLEAN", "created_at / updated_at",
    ])
    entity(1220, 80, 340, 340, "sip_evidence_items", "PK  id", [
        "FK  physical_unit_id", "item_name", "category ENUM",
        "quantity", "verdict_status ENUM", "execution_ba_number",
        "execution_recipient / NIK", "execution_date", "execution_proof_photo",
    ])
    entity(40, 480, 300, 250, "telegram_whitelist", "PK  id", [
        "UK  telegram_chat_id", "user_name", "role ENUM",
        "is_active BOOLEAN", "created_at / updated_at",
    ])
    entity(620, 500, 340, 250, "mutations", "PK  id", [
        "FK  physical_unit_id", "mutation_type ENUM",
        "borrower_name", "court_date", "notes", "handled_by", "created_at",
    ])
    entity(1220, 520, 340, 220, "users", "PK  id", [
        "name / email", "role admin|petugas", "is_active",
        "password (hashed)", "remember_token",
    ])

    d.line((340, 220, 620, 200), fill=LINE, width=2)
    d.text((480, 176), "1      *", font=F(TIMESB, 16), fill=NAVY, anchor="mm")
    d.line((960, 200, 1220, 200), fill=LINE, width=2)
    d.text((1090, 176), "1      *", font=F(TIMESB, 16), fill=NAVY, anchor="mm")
    d.line((790, 400, 790, 500), fill=LINE, width=2)
    d.text((830, 446), "1 *", font=F(TIMESB, 16), fill=NAVY)

    box(d, (40, 860, 1560, 950), fill=BOX2)
    d.text((800, 905), "Hierarki: cases 1—* physical_units (wadah fisik SINGLE/PACK) 1—* sip_evidence_items (isi barang). Mutasi mengikuti unit fisik, bukan per item.", font=F(TIMES, 15), fill=INK, anchor="mm")
    save(im, "gambar-3-2-erd.png")


def table_image(d, x, y, w, rows, col_w, header=True):
    yy = y
    for i, row in enumerate(rows):
        h = 28
        bg = NAVY if header and i == 0 else (WHITE if i % 2 else BOX2)
        fg = WHITE if header and i == 0 else INK
        xx = x
        for j, cell in enumerate(row):
            d.rectangle((xx, yy, xx + col_w[j], yy + h), fill=bg, outline=LINE)
            d.text((xx + 6, yy + h / 2), str(cell), font=F(TIMES, 12), fill=fg, anchor="lm")
            xx += col_w[j]
        yy += h
    return yy


def fig_33():
    im, d = new_canvas(1600, 1100, "Gambar 3.3  Kamus data dan cuplikan DDL SQL SIBATA-BB")
    d.text((40, 80), "A. Ringkasan kamus data (tabel inti)", font=F(TIMESB, 18), fill=NAVY)
    rows = [
        ["Tabel", "Kunci", "Atribut penting", "Fungsi"],
        ["cases", "id / case_number", "defendant_name, prosecutor_name, case_status", "Register perkara Tahap II"],
        ["physical_units", "id / unit_code", "unit_type, storage_location, current_status", "Wadah fisik + stiker QR"],
        ["sip_evidence_items", "id", "item_name, category, verdict_status", "Isi barang per unit"],
        ["mutations", "id", "mutation_type, borrower_name, handled_by", "Jejak chain of custody"],
        ["telegram_whitelist", "telegram_chat_id", "user_name, role, is_active", "Otorisasi bot lapangan"],
        ["users", "email", "role, is_active", "Login portal web admin"],
    ]
    table_image(d, 40, 110, 1520, rows, [260, 260, 560, 440])

    d.text((40, 340), "B. Enumerasi status (cuplikan)", font=F(TIMESB, 18), fill=NAVY)
    enums = [
        ["Kolom", "Nilai yang diizinkan"],
        ["cases.case_status", "TAHAP_2 | SIDANG | INKRACHT | SELESAI"],
        ["physical_units.unit_type", "SINGLE | PACK"],
        ["physical_units.current_status", "TERSIMPAN_GUDANG | DIPINJAM_SIDANG | SELESAI"],
        ["sip_evidence_items.verdict_status", "MENUNGGU_PUTUSAN | DIMUSNAHKAN | DIKEMBALIKAN | DIRAMPAS_NEGARA_LELANG | PSP"],
        ["mutations.mutation_type", "CHECK_IN | PINJAM_SIDANG | KEMBALI_GUDANG | EKSEKUSI"],
        ["telegram_whitelist.role", "ADMIN_PB3R | JPU_PEGAWAI"],
        ["sip_evidence_items.category", "NARKOTIKA | ELEKTRONIK | KENDARAAN | SENJATA | DOKUMEN | LAINNYA"],
    ]
    table_image(d, 40, 370, 1520, enums, [420, 1100])

    d.text((40, 630), "C. Cuplikan DDL (physical_units & sip_evidence_items)", font=F(TIMESB, 18), fill=NAVY)
    box(d, (40, 660, 1560, 1060), fill=(18, 28, 42), radius=6)
    ddl = """CREATE TABLE physical_units (
  id BIGINT PK, case_id FK→cases, unit_code VARCHAR(50) UNIQUE,
  unit_type ENUM('SINGLE','PACK'), storage_location VARCHAR,
  current_status ENUM('TERSIMPAN_GUDANG','DIPINJAM_SIDANG','SELESAI'),
  is_printed BOOLEAN DEFAULT FALSE
);
CREATE TABLE sip_evidence_items (
  id BIGINT PK, physical_unit_id FK→physical_units,
  item_name VARCHAR, category ENUM(...), quantity VARCHAR(50),
  verdict_status ENUM('MENUNGGU_PUTUSAN','DIMUSNAHKAN','DIKEMBALIKAN',
                      'DIRAMPAS_NEGARA_LELANG','PSP')
);"""
    d.multiline_text((60, 680), ddl, font=F(CONSOLAS, 16), fill=(220, 230, 240), spacing=6)
    save(im, "gambar-3-3-kamus-data-ddl.png")


def fig_34():
    im, d = new_canvas(1600, 980, "Gambar 3.4  Mockup antarmuka dashboard web dan menu bot Telegram")
    # Web mockup
    box(d, (40, 80, 980, 930), fill=WHITE, radius=8)
    d.rectangle((40, 80, 980, 130), fill=NAVY)
    text_center(d, (510, 105), "SIBATA-BB  ·  Dashboard PB3R  ·  Kejari Wajo", F(ARIALB, 16), WHITE)
    cards = [("Perkara", "24"), ("Unit fisik", "61"), ("Tersimpan", "48"), ("Dipinjam", "9"), ("Selesai", "4"), ("Antrean cetak", "7")]
    for i, (lab, val) in enumerate(cards):
        x = 60 + (i % 3) * 300
        y = 150 + (i // 3) * 110
        box(d, (x, y, x + 280, y + 96), fill=BOX2)
        d.text((x + 16, y + 20), lab.upper(), font=F(ARIALB, 12), fill=MUTED)
        d.text((x + 16, y + 52), val, font=F(TIMESB, 32), fill=NAVY)
    d.text((60, 390), "Mutasi terbaru", font=F(TIMESB, 18), fill=NAVY)
    rows = [
        ["Waktu", "Jenis", "Unit", "Petugas"],
        ["25/09/2026 09:12", "PINJAM SIDANG", "BB-2026-014", "Andi JPU"],
        ["25/09/2026 08:40", "CHECK IN", "PKT-2026-007", "Admin PB3R"],
        ["24/09/2026 16:05", "KEMBALI GUDANG", "BB-2026-009", "Petugas Gudang"],
    ]
    table_image(d, 60, 420, 900, rows, [220, 230, 220, 230])
    d.text((60, 560), "Menu portal: Register perkara · Inventaris fisik · Antrean cetak · Laporan · Whitelist · Pengguna", font=F(TIMES, 14), fill=MUTED)

    # Phone
    d.rounded_rectangle((1080, 80, 1540, 930), radius=36, fill=PHONE_BG, outline=INK, width=4)
    d.rounded_rectangle((1100, 120, 1520, 890), radius=16, fill=(245, 247, 250))
    d.rectangle((1100, 120, 1520, 170), fill=NAVY)
    text_center(d, (1310, 145), "Bot SIBATA-BB", F(ARIALB, 16), WHITE)
    menu = [
        ("bot", "Selamat datang di SIBATA-BB PB3R Kejari Wajo. Pilih perintah:"),
        ("me", "/start"),
        ("bot", "/tambah — daftar perkara & BB\n/cari — cari unit\n/pinjam — pinjam sidang\n/kembali — kembali gudang\n/eksekusi — catat putusan\n/rekap — ringkasan gudang"),
    ]
    y = 190
    for who, msg in menu:
        fill = BUBBLE_BOT if who == "bot" else BUBBLE_ME
        lines = msg.split("\n")
        h = 18 + 20 * len(lines)
        x1 = 1120 if who == "bot" else 1180
        x2 = 1440 if who == "bot" else 1500
        box(d, (x1, y, x2, y + h), fill=fill, radius=10)
        yy = y + 14
        for line in lines:
            d.text((x1 + 12, yy), line, font=F(ARIAL, 13), fill=INK)
            yy += 20
        y += h + 14
    btns = ["/tambah", "/cari", "/pinjam", "/kembali", "/eksekusi", "/rekap"]
    for i, b in enumerate(btns):
        x = 1120 + (i % 2) * 190
        yy = 720 + (i // 2) * 44
        box(d, (x, yy, x + 176, yy + 36), fill=NAVY, radius=6)
        text_center(d, (x + 88, yy + 18), b, F(ARIALB, 13), WHITE)
    save(im, "gambar-3-4-mockup-ui.png")


def fig_35():
    im, d = new_canvas(1600, 900, "Gambar 3.5  Konfigurasi environment server dan webhook SSL Telegram")
    box(d, (40, 90, 780, 850), fill=(18, 28, 42), radius=8)
    d.text((60, 110), ".env  (cuplikan aman — nilai rahasia disamarkan)", font=F(ARIALB, 16), fill=GOLD)
    env = """APP_NAME=SIBATA-BB
APP_ENV=production
APP_URL=https://sitaba.kejari-wajo.go.id
APP_KEY=base64:********

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sitaba_bb
DB_USERNAME=********
DB_PASSWORD=********

TELEGRAM_BOT_TOKEN=********:********
TELEGRAM_WEBHOOK_URL=https://sitaba.kejari-wajo.go.id/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=********"""
    d.multiline_text((60, 150), env, font=F(CONSOLAS, 16), fill=(220, 230, 240), spacing=6)

    box(d, (820, 90, 1560, 430), fill=BOX2, radius=8)
    d.text((840, 110), "Registrasi webhook (Artisan)", font=F(TIMESB, 18), fill=NAVY)
    box(d, (840, 150, 1540, 230), fill=(18, 28, 42), radius=6)
    d.text((860, 190), "php artisan telegram:set-webhook", font=F(CONSOLAS, 16), fill=GOLD, anchor="lm")
    d.text((840, 260), "Hasil sukses (disamarkan):", font=F(TIMES, 15), fill=INK)
    d.multiline_text((840, 290), '{\n  "ok": true,\n  "result": true,\n  "description": "Webhook was set"\n}', font=F(CONSOLAS, 15), fill=INK)

    box(d, (820, 460, 1560, 850), fill=WHITE, radius=8)
    d.text((840, 490), "Keamanan saluran", font=F(TIMESB, 18), fill=NAVY)
    items = [
        "1. HTTPS wajib — Telegram menolak webhook HTTP biasa.",
        "2. Secret token header X-Telegram-Bot-Api-Secret-Token.",
        "3. Chat ID di luar whitelist ditolak (tidak memproses perintah).",
        "4. Kredensial tidak dikomit ke repositori (.env diabaikan git).",
        "5. Sesi portal web dilindungi middleware auth + role admin.",
    ]
    y = 530
    for t in items:
        d.text((840, y), t, font=F(TIMES, 16), fill=INK)
        y += 48
    save(im, "gambar-3-5-webhook-ssl.png")


def phone_chat(im, d, x, y, title, bubbles):
    d.rounded_rectangle((x, y, x + 460, y + 780), radius=30, fill=PHONE_BG, outline=INK, width=3)
    d.rounded_rectangle((x + 16, y + 36, x + 444, y + 750), radius=14, fill=(246, 247, 250))
    d.rectangle((x + 16, y + 36, x + 444, y + 80), fill=NAVY)
    text_center(d, (x + 230, y + 58), title, F(ARIALB, 15), WHITE)
    yy = y + 96
    for who, msg in bubbles:
        lines = wrap(d, msg, F(ARIAL, 13), 360) if "\n" not in msg else msg.split("\n")
        h = 16 + 18 * len(lines)
        if who == "bot":
            x1, x2, fill = x + 28, x + 400, BUBBLE_BOT
        else:
            x1, x2, fill = x + 76, x + 436, BUBBLE_ME
        box(d, (x1, yy, x2, yy + h), fill=fill, radius=8)
        ty = yy + 8
        for line in lines:
            d.text((x1 + 10, ty), line, font=F(ARIAL, 13), fill=INK)
            ty += 18
        yy += h + 10


def fig_36():
    im, d = new_canvas(1600, 920, "Gambar 3.6  Wizard bot Telegram: registrasi dan unggah foto barang bukti")
    phone_chat(im, d, 60, 90, "/tambah  ·  BB mandiri", [
        ("me", "/tambah"),
        ("bot", "Pilih jenis unit fisik:"),
        ("me", "SINGLE — satu barang, satu stiker"),
        ("bot", "Nomor perkara? Contoh: PDM-12/Wajo/09/2026"),
        ("me", "PDM-12/Wajo/09/2026"),
        ("bot", "Nama terdakwa?"),
        ("me", "Laode Ahmad"),
        ("bot", "Nama barang, kategori, jumlah, dan lokasi rak?"),
        ("me", "1 unit parang | SENJATA | 1 unit | Lemari Senjata A-03"),
        ("bot", "Kirim foto fisik barang bukti (kamera gawai)."),
        ("me", "[foto terunggah]"),
        ("bot", "Tersimpan. Kode unit BB-2026-014. Label masuk antrean cetak."),
    ])
    phone_chat(im, d, 570, 90, "/tambah  ·  Paket PACK", [
        ("me", "/tambah"),
        ("bot", "Pilih jenis unit fisik:"),
        ("me", "PACK — satu wadah, banyak isi"),
        ("bot", "Nomor perkara dan lokasi wadah?"),
        ("me", "PDM-18/Wajo/09/2026 | Brankas Laci 09"),
        ("bot", "Tambah isi paket (boleh berulang):"),
        ("me", "1 sachet sabu 0,3 g | NARKOTIKA"),
        ("me", "1 unit HP Android | ELEKTRONIK"),
        ("bot", "Paket PKT-2026-007 berisi 2 item. Satu stiker QR menempel di luar wadah."),
    ])
    box(d, (1080, 90, 1560, 870), fill=BOX2, radius=8)
    d.text((1100, 120), "Catatan perancangan wizard", font=F(TIMESB, 18), fill=NAVY)
    notes = [
        "Percakapan bersifat state-machine: setiap jawaban memajukan langkah, /batal membatalkan sesi.",
        "Foto kamera gawai menggantikan pencatatan visual manual di buku ekspedisi.",
        "SINGLE menghasilkan 1 physical_unit + 1 sip_evidence_item.",
        "PACK menghasilkan 1 physical_unit + N sip_evidence_item.",
        "Hanya Chat ID yang aktif di whitelist yang dapat menjalankan /tambah.",
        "Setelah simpan, unit masuk antrean cetak (is_printed = false) di portal web.",
    ]
    y = 170
    for n in notes:
        lines = wrap(d, n, F(TIMES, 16), 440)
        d.ellipse((1110, y + 6, 1122, y + 18), fill=GOLD)
        for i, line in enumerate(lines):
            d.text((1134, y), line, font=F(TIMES, 16), fill=INK)
            y += 24
        y += 16
    save(im, "gambar-3-6-wizard-bot.png")


def fig_37():
    im, d = new_canvas(1600, 920, "Gambar 3.7  Perintah cepat /pinjam, /kembali, dan /eksekusi")
    phone_chat(im, d, 40, 90, "/pinjam", [
        ("me", "/pinjam BB-2026-014"),
        ("bot", "Unit BB-2026-014 tersimpan gudang. Nama JPU peminjam dan tanggal sidang?"),
        ("me", "Andi, S.H. | 26/09/2026"),
        ("bot", "Status diubah DIPINJAM_SIDANG. Mutasi PINJAM_SIDANG tercatat. Dashboard web tersinkron."),
    ])
    phone_chat(im, d, 540, 90, "/kembali", [
        ("me", "/kembali BB-2026-014"),
        ("bot", "Konfirmasi kondisi fisik dan lokasi rak kembali?"),
        ("me", "Baik | Lemari Senjata A-03"),
        ("bot", "Status kembali TERSIMPAN_GUDANG. Mutasi KEMBALI_GUDANG tercatat."),
    ])
    phone_chat(im, d, 1040, 90, "/eksekusi", [
        ("me", "/eksekusi PKT-2026-007"),
        ("bot", "Pilih item: 1) sabu  2) HP Android"),
        ("me", "1"),
        ("bot", "Pilih putusan: DIMUSNAHKAN / DIKEMBALIKAN / LELANG / PSP"),
        ("me", "DIMUSNAHKAN | BA-09/2026"),
        ("bot", "Item sabu = DIMUSNAHKAN. HP masih MENUNGGU_PUTUSAN. Satu wadah, dua nasib hukum."),
    ])
    save(im, "gambar-3-7-perintah-mutasi.png")


def make_qr(data: str, size: int) -> Image.Image:
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M, box_size=6, border=1)
    qr.add_data(data)
    qr.make(fit=True)
    img = qr.make_image(fill_color=NAVY, back_color=WHITE).convert("RGB")
    return img.resize((size, size), Image.Resampling.NEAREST)


def fig_38():
    im, d = new_canvas(1600, 980, "Gambar 3.8  Portal web SIBATA-BB dan generator stiker QR Code")
    box(d, (40, 80, 1040, 940), fill=WHITE, radius=8)
    d.rectangle((40, 80, 1040, 128), fill=NAVY)
    text_center(d, (540, 104), "SIBATA-BB  ·  Inventaris Fisik  ·  Antrean Cetak Label", F(ARIALB, 16), WHITE)
    rows = [
        ["Kode", "Perkara", "Tipe", "Lokasi", "Status", "Label"],
        ["BB-2026-014", "PDM-12/Wajo/09/2026", "SINGLE", "Lemari A-03", "Gudang", "Cetak ulang"],
        ["PKT-2026-007", "PDM-18/Wajo/09/2026", "PACK", "Brankas Laci 09", "Gudang", "Belum cetak"],
        ["BB-2026-009", "PDM-04/Wajo/08/2026", "SINGLE", "Parkir B-12", "Pinjam sidang", "Sudah"],
        ["BB-2026-021", "PDM-21/Wajo/09/2026", "SINGLE", "Loker E-02", "Gudang", "Belum cetak"],
    ]
    table_image(d, 60, 150, 980, rows, [160, 230, 110, 180, 150, 150])
    d.text((60, 330), "Unit yang sudah tercetak tetap dapat dicetak ulang dari antrean atau dari daftar inventaris.", font=F(TIMES, 15), fill=MUTED)

    # sticker — kartu putih, kop navy, QR di panel putih
    sx, sy, sw, sh = 1100, 140, 460, 360
    d.rounded_rectangle((sx, sy, sx + sw, sy + sh), radius=8, fill=WHITE, outline=NAVY, width=2)
    d.rectangle((sx + 2, sy + 2, sx + sw - 2, sy + 58), fill=NAVY)
    d.rectangle((sx + 2, sy + 58, sx + sw - 2, sy + 66), fill=GOLD)
    text_center(d, (sx + sw / 2, sy + 22), "KEJAKSAAN NEGERI WAJO", F(ARIALB, 13), GOLD)
    text_center(d, (sx + sw / 2, sy + 42), "SEKSI PB3R  ·  BARANG BUKTI", F(ARIAL, 11), WHITE)
    qr = make_qr("https://sitaba.kejari-wajo.go.id/view/BB-2026-014", 140)
    im.paste(qr, (sx + 20, sy + 88))
    d.text((sx + 180, sy + 96), "BB-2026-014", font=F(ARIALB, 20), fill=NAVY)
    d.text((sx + 180, sy + 126), "TOKEN GUDANG PB3R", font=F(ARIALB, 11), fill=GOLD)
    d.text((sx + 180, sy + 160), "No. perkara", font=F(ARIAL, 11), fill=MUTED)
    d.text((sx + 180, sy + 178), "PDM-12/Wajo/09/2026", font=F(ARIALB, 12), fill=INK)
    d.text((sx + 180, sy + 204), "Terdakwa  Laode Ahmad", font=F(ARIAL, 12), fill=INK)
    d.text((sx + 180, sy + 226), "Barang    1 unit parang", font=F(ARIAL, 12), fill=INK)
    d.text((sx + 180, sy + 248), "Posisi    Lemari Senjata A-03", font=F(ARIAL, 12), fill=INK)
    d.rectangle((sx + 2, sy + sh - 36, sx + sw - 2, sy + sh - 2), fill=(18, 42, 74))
    text_center(d, (sx + sw / 2, sy + sh - 18), "Scan QR untuk cek status · Jangan copot stiker", F(ARIAL, 11), GOLD)

    box(d, (1100, 530, 1560, 940), fill=BOX2, radius=8)
    d.text((1120, 555), "Aturan pelabelan", font=F(TIMESB, 18), fill=NAVY)
    rules = [
        "SINGLE: stiker BB-YYYY-XXX menempel langsung pada barang.",
        "PACK: satu stiker PKT-YYYY-XXX menempel di luar wadah segel.",
        "Isi paket tidak diberi stiker terpisah.",
        "Ukuran target thermal 70 × 50 mm, QR kontras tinggi.",
        "Halaman publik: /view/{unit_code}.",
        "Label yang sudah tercetak dapat dicetak ulang.",
    ]
    y = 600
    for r in rules:
        for line in wrap(d, "•  " + r, F(TIMES, 15), 420):
            d.text((1120, y), line, font=F(TIMES, 15), fill=INK)
            y += 24
        y += 6
    save(im, "gambar-3-8-portal-stiker.png")


def fig_39():
    im, d = new_canvas(1600, 860, "Gambar 3.9  Ekspor buku register ke spreadsheet (.xlsx)")
    d.rectangle((40, 80, 1560, 130), fill=(21, 110, 71))
    text_center(d, (800, 105), "SIBATA-BB_Register_Barang_Bukti_September_2026.xlsx", F(ARIALB, 18), WHITE)
    rows = [
        ["No", "No. Perkara", "Terdakwa", "JPU", "Kode Unit", "Tipe", "Uraian barang", "Lokasi", "Status unit", "Putusan item"],
        ["1", "PDM-12/Wajo/09/2026", "Laode Ahmad", "Andi, S.H.", "BB-2026-014", "SINGLE", "1 unit parang", "Lemari A-03", "Gudang", "Menunggu"],
        ["2", "PDM-18/Wajo/09/2026", "Basri", "Nia, S.H.", "PKT-2026-007", "PACK", "1 sachet sabu 0,3 g", "Brankas 09", "Gudang", "Dimusnahkan"],
        ["3", "PDM-18/Wajo/09/2026", "Basri", "Nia, S.H.", "PKT-2026-007", "PACK", "1 unit HP Android", "Brankas 09", "Gudang", "Dikembalikan"],
        ["4", "PDM-04/Wajo/08/2026", "Hendra", "Andi, S.H.", "BB-2026-009", "SINGLE", "1 unit sepeda motor", "Parkir B-12", "Pinjam", "Menunggu"],
        ["5", "PDM-21/Wajo/09/2026", "Sitti", "Rudi, S.H.", "BB-2026-021", "SINGLE", "1 dus dokumen", "Loker E-02", "Gudang", "Menunggu"],
    ]
    table_image(d, 40, 140, 1520, rows, [50, 200, 150, 130, 160, 90, 240, 140, 140, 220])
    box(d, (40, 380, 1560, 820), fill=BOX2, radius=8)
    d.text((60, 410), "Cara ekspor dan keluaran", font=F(TIMESB, 20), fill=NAVY)
    teks = [
        "Menu portal: Laporan → Unduh Excel (rute /reports/excel).",
        "Setiap baris mewakili satu item barang bukti, bukan satu wadah. Karena itu PKT-2026-007 muncul dua baris dengan putusan berbeda.",
        "Kolom diselaraskan dengan kebutuhan buku register PB3R: identitas perkara, kode unit, lokasi rak, status sirkulasi, dan amar putusan per item.",
        "Petugas tidak lagi merekap manual dari buku ekspedisi kertas ke spreadsheet.",
        "Format .xlsx dapat dibuka di Microsoft Excel / LibreOffice untuk arsip bulanan dan pelaporan pimpinan.",
    ]
    y = 460
    for t in teks:
        for line in wrap(d, "•  " + t, F(TIMES, 17), 1460):
            d.text((60, y), line, font=F(TIMES, 17), fill=INK)
            y += 28
        y += 8
    save(im, "gambar-3-9-ekspor-xlsx.png")


def fig_310():
    im, d = new_canvas(1600, 980, "Gambar 3.10  Matriks black-box testing SIBATA-BB")
    rows = [
        ["No", "Skenario uji (black-box)", "Masukan / langkah", "Hasil yang diharapkan", "Hasil"],
        ["1", "Halaman login tampil", "GET /login", "Teks merek SIBATA-BB terlihat", "LULUS"],
        ["2", "Tamu ditolak dari dashboard", "GET /dashboard tanpa sesi", "Redirect ke /login", "LULUS"],
        ["3", "Admin membuka modul inti", "Login admin; buka cases, units, print, reports, whitelist", "HTTP 200, judul halaman sesuai", "LULUS"],
        ["4", "Petugas tidak boleh dashboard", "Login role petugas ke /dashboard", "HTTP 403 / ditolak", "LULUS"],
        ["5", "Registrasi SINGLE + PACK", "POST /cases dengan 1 SINGLE dan 1 PACK berisi 2 item", "Tersimpan di cases, physical_units, sip_evidence_items", "LULUS"],
        ["6", "Pinjam dan kembali unit", "POST loan lalu return", "Status DIPINJAM_SIDANG lalu TERSIMPAN_GUDANG", "LULUS"],
        ["7", "Cetak & cetak ulang label", "GET print-labels/sheet meski is_printed=true", "Lembar stiker memuat kode unit", "LULUS"],
        ["8", "Kartu publik QR", "GET /view/{unit_code}", "Kode unit dan nama terdakwa tampil", "LULUS"],
        ["9", "Webhook menolak asing", "POST /api/telegram/webhook Chat ID tidak terdaftar", "HTTP 200, perintah tidak dijalankan", "LULUS"],
        ["10", "Webhook grup aman", "Update supergroup / my_chat_member", "HTTP 200, tidak error fatal", "LULUS"],
    ]
    # custom taller rows
    col_w = [50, 330, 430, 500, 130]
    yy = 90
    for i, row in enumerate(rows):
        h = 36 if i == 0 else 76
        bg = NAVY if i == 0 else (WHITE if i % 2 else BOX2)
        xx = 40
        for j, cell in enumerate(row):
            d.rectangle((xx, yy, xx + col_w[j], yy + h), fill=bg, outline=LINE)
            font = F(TIMESB, 13) if i == 0 else F(TIMES, 13)
            fg = WHITE if i == 0 else INK
            if i > 0 and j == 4:
                fg = GREEN
                font = F(TIMESB, 14)
            lines = wrap(d, str(cell), font, col_w[j] - 12)
            ty = yy + 8
            for line in lines[:4]:
                d.text((xx + 6, ty), line, font=font, fill=fg)
                ty += 16
            xx += col_w[j]
        yy += h
    d.text((800, 950), "Sumber: pengujian fungsional black-box (SitabaSmokeTest) dan simulasi operasional 22–24 September 2026.", font=F(TIMESI, 14), fill=MUTED, anchor="mm")
    save(im, "gambar-3-10-blackbox.png")


def fig_311():
    im, d = new_canvas(1600, 920, "Gambar 3.11  Simulasi pemindaian QR gudang dan sinkronisasi real-time")
    # warehouse
    box(d, (40, 90, 760, 500), fill=(245, 241, 232), radius=8)
    d.text((400, 120), "Lorong gudang PB3R", font=F(TIMESB, 18), fill=NAVY, anchor="mm")
    for i, lab in enumerate(["A-01", "A-02", "A-03", "A-04"]):
        x = 70 + i * 165
        box(d, (x, 160, x + 145, 280), fill=WHITE)
        text_center(d, (x + 72, 190), "Rak " + lab, F(TIMESB, 14), NAVY)
        if lab == "A-03":
            d.rectangle((x + 20, 210, x + 125, 260), fill=NAVY)
            text_center(d, (x + 72, 235), "BB-2026-014", F(ARIALB, 11), GOLD)
    # phone overlay
    d.rounded_rectangle((250, 300, 550, 860), radius=24, fill=PHONE_BG, width=3, outline=INK)
    d.rounded_rectangle((268, 330, 532, 830), radius=12, fill=WHITE)
    d.rectangle((268, 330, 532, 372), fill=NAVY)
    text_center(d, (400, 351), "Kamera · pindai QR", F(ARIALB, 14), WHITE)
    qr = make_qr("https://sitaba.kejari-wajo.go.id/view/BB-2026-014", 160)
    im.paste(qr, (320, 400))
    text_center(d, (400, 590), "BB-2026-014", F(ARIALB, 16), NAVY)
    text_center(d, (400, 620), "Laode Ahmad · Lemari A-03", F(ARIAL, 13), MUTED)
    box(d, (290, 650, 510, 700), fill=GREEN, radius=6)
    text_center(d, (400, 675), "TERSIMPAN GUDANG", F(ARIALB, 13), WHITE)
    d.text((400, 760), "Petugas memindai di depan rak", font=F(TIMESI, 13), fill=MUTED, anchor="mm")

    # dashboard
    box(d, (800, 90, 1560, 860), fill=WHITE, radius=8)
    d.rectangle((800, 90, 1560, 140), fill=NAVY)
    text_center(d, (1180, 115), "Dashboard web — tersinkron seketika", F(ARIALB, 16), WHITE)
    rows = [
        ["Waktu", "Peristiwa", "Sumber"],
        ["25/09 10:12:01", "QR BB-2026-014 dipindai", "Bot Telegram"],
        ["25/09 10:12:01", "Status tampil: TERSIMPAN_GUDANG", "Basis data pusat"],
        ["25/09 10:14:22", "/pinjam Andi, S.H. 26/09/2026", "Bot Telegram"],
        ["25/09 10:14:22", "Kartu unit: DIPINJAM_SIDANG", "Portal web"],
        ["25/09 10:14:22", "Mutasi PINJAM_SIDANG +1", "Tabel mutations"],
    ]
    table_image(d, 830, 170, 700, rows, [180, 360, 160])
    d.text((830, 420), "Bukti sinkronisasi dua arah", font=F(TIMESB, 18), fill=NAVY)
    points = [
        "Perubahan dari bot langsung terlihat di dashboard tanpa unggah ulang.",
        "Perubahan dari portal (pinjam/kembali/eksekusi) dibaca bot pada perintah berikutnya.",
        "Tidak ada buku ekspedisi perantara; satu sumber data (single source of truth).",
        "Simulasi stock opname 23–24 September 2026 di zona rak, brankas, dan parkir berhasil tanpa scanner kabel.",
    ]
    y = 460
    for p in points:
        for line in wrap(d, "•  " + p, F(TIMES, 16), 690):
            d.text((830, y), line, font=F(TIMES, 16), fill=INK)
            y += 26
        y += 10
    save(im, "gambar-3-11-sinkronisasi.png")


def fig_312():
    im, d = new_canvas(1600, 980, "Gambar 3.12  Sampul dan cuplikan User Manual SIBATA-BB")
    # cover
    box(d, (80, 90, 700, 900), fill=NAVY, radius=4)
    d.rectangle((80, 90, 700, 110), fill=GOLD)
    d.rectangle((80, 880, 700, 900), fill=GOLD)
    text_center(d, (390, 200), "BUKU PETUNJUK PENGOPERASIAN", F(TIMESB, 18), GOLD)
    text_center(d, (390, 250), "SIBATA-BB", F(TIMESB, 40), WHITE)
    text_center(d, (390, 310), "Sistem Informasi Barang Bukti dan", F(TIMES, 18), WHITE)
    text_center(d, (390, 338), "Barang Rampasan", F(TIMES, 18), WHITE)
    text_center(d, (390, 400), "Seksi PB3R", F(TIMES, 16), GOLD)
    text_center(d, (390, 430), "Kejaksaan Negeri Wajo", F(TIMES, 16), GOLD)
    text_center(d, (390, 520), "Untuk Admin Web, Petugas Gudang,", F(TIMES, 15), WHITE)
    text_center(d, (390, 546), "dan Jaksa Penuntut Umum", F(TIMES, 15), WHITE)
    text_center(d, (390, 700), "Laboratorium Pranata Komputer", F(TIMESI, 15), GOLD)
    text_center(d, (390, 728), "Tahun Anggaran 2026", F(TIMESI, 15), GOLD)
    text_center(d, (390, 820), "Grace Yoby Dopi  ·  199710152022032004", F(TIMES, 14), WHITE)

    box(d, (760, 90, 1540, 900), fill=WHITE, radius=4)
    d.text((790, 120), "Daftar isi ringkas", font=F(TIMESB, 20), fill=NAVY)
    toc = [
        "Bab 1  Masuk portal web dan aturan peran (admin / petugas)",
        "Bab 2  Registrasi perkara Tahap II (SINGLE dan PACK)",
        "Bab 3  Cetak, tempel, dan cetak ulang stiker QR",
        "Bab 4  Menu bot Telegram: /start, whitelist, /batal",
        "Bab 5  /tambah di lapangan beserta unggah foto",
        "Bab 6  /pinjam dan /kembali untuk sidang PN Sengkang",
        "Bab 7  /eksekusi per item (musnah, kembali, lelang, PSP)",
        "Bab 8  /cari dan /rekap gudang",
        "Bab 9  Kartu publik /view/{kode}",
        "Bab 10  Ekspor register .xlsx dan pengarsipan",
        "Lampiran  Kode kesalahan umum dan cara mengatasinya",
    ]
    y = 170
    for t in toc:
        d.text((790, y), t, font=F(TIMES, 16), fill=INK)
        y += 36
    d.text((790, 620), "Cuplikan bab 6 — pinjam sidang", font=F(TIMESB, 18), fill=NAVY)
    cup = (
        "Di depan rak, buka bot SIBATA-BB, ketik /pinjam lalu kode unit atau pindai stiker. "
        "Isi nama JPU dan tanggal sidang. Sistem menolak peminjaman jika unit sudah DIPINJAM_SIDANG. "
        "Setelah sidang, ketik /kembali dan konfirmasi lokasi rak. Dashboard admin menampilkan mutasi yang sama."
    )
    y = 660
    for line in wrap(d, cup, F(TIMES, 16), 710):
        d.text((790, y), line, font=F(TIMES, 16), fill=INK)
        y += 26
    save(im, "gambar-3-12-user-manual.png")


def main():
    fig_31a()
    fig_31b()
    fig_31c()
    fig_31d()
    fig_32()
    fig_33()
    fig_34()
    fig_35()
    fig_36()
    fig_37()
    fig_38()
    fig_39()
    fig_310()
    fig_311()
    fig_312()
    print("OK", OUT)


if __name__ == "__main__":
    main()
