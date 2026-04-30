# Survey Kepuasan Pelanggan — Waschen Alora

Aplikasi web survey kepuasan pelanggan untuk **PT Waschen Alora Indonesia**. Mengumpulkan data **CSAT**, **NPS**, dan **feedback teks** berdasarkan nomor nota transaksi laundry.

---

## Fitur

- **3-step survey flow**: CSAT (1–5) → NPS (0–10) → Feedback teks & tag
- **Duplikasi dicegah**: Setiap nomor nota hanya bisa mengisi survey satu kali
- **Navigasi balik**: Pelanggan bisa kembali dan mengubah jawaban sebelumnya
- **Desain responsif**: Mobile-friendly, tema brand Waschen (ungu)
- **Poppins font** + brand color system (#5B005F primary)

---

## Prasyarat

| Komponen | Versi Minimum |
|----------|---------------|
| PHP      | 7.4+          |
| MySQL    | 5.7+ / MariaDB 10.3+ |
| Apache   | 2.4+ (dengan `mod_rewrite`) |
| ext-mysqli | Aktif |

---

## Instalasi

### 1. Clone / Upload project

```bash
git clone <repo-url> /var/www/html/satisfaction
```

### 2. Setup database

Buat database dan jalankan skema:

```bash
mysql -u root -p < schema.sql
```

### 3. Konfigurasi koneksi database

Salin template konfigurasi:

```bash
cp config/db.example.php config/db.php
```

Edit `config/db.php` dan isi kredensial database:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
```

> **Penting:** `config/db.php` sudah masuk `.gitignore` dan tidak akan ter-commit ke repository.

### 4. Aktifkan mod_rewrite (Apache)

```bash
a2enmod rewrite
service apache2 restart
```

Pastikan `AllowOverride All` aktif di konfigurasi virtual host:

```apache
<Directory /var/www/html/satisfaction>
    AllowOverride All
</Directory>
```

### 5. Set permission

```bash
chmod 755 /var/www/html/satisfaction
chmod 644 /var/www/html/satisfaction/*.php
chmod 600 /var/www/html/satisfaction/config/db.php
```

---

## Struktur Direktori

```
satisfaction/
├── index.php          # Halaman masuk nomor nota
├── csat.php           # Step 1: Customer Satisfaction Score (1–5)
├── nps.php            # Step 2: Net Promoter Score (0–10)
├── feedback.php       # Step 3: Tag & teks feedback, simpan ke DB
├── thankyou.php       # Halaman konfirmasi selesai
├── schema.sql         # Skema tabel database
├── .htaccess          # URL rewriting, security headers
├── .gitignore
├── config/
│   ├── db.php         # Kredensial DB (TIDAK di-commit)
│   ├── db.example.php # Template kredensial
│   └── .htaccess      # Block direct HTTP access ke /config
└── image/
    └── waschen.png    # Logo brand
```

---

## Alur Survey

```
index.php
   │  (masukkan nomor nota, cek duplikat di DB)
   ↓
csat.php  [Step 1/3]
   │  (pilih 😭😞😐😊🤩 → simpan ke session)
   ↓
nps.php   [Step 2/3]
   │  (pilih skor 0–10 → simpan ke session)
   ↓
feedback.php  [Step 3/3]
   │  (pilih tag opsional + komentar → INSERT ke DB)
   ↓
thankyou.php
   │  (tampilkan ringkasan & hapus session)
   ↓
(selesai)
```

Navigasi **kembali** tersedia di setiap step tanpa kehilangan progress.

---

## Skema Database

Tabel utama: `tr_customer_satisfaction_waschen`

| Kolom           | Tipe          | Keterangan                         |
|-----------------|---------------|------------------------------------|
| id              | INT, PK, AUTO | Primary key                        |
| no_nota         | VARCHAR(100)  | Nomor nota (unik)                  |
| csat_score      | TINYINT       | Skor CSAT 1–5                      |
| csat_label      | VARCHAR(50)   | Label teks CSAT                    |
| nps_score       | TINYINT       | Skor NPS 0–10                      |
| nps_category    | VARCHAR(20)   | Detractor / Passive / Promoter     |
| feedback_tags   | TEXT          | Tag yang dipilih (dipisah koma)    |
| feedback_text   | TEXT          | Komentar bebas pelanggan           |
| ip_address      | VARCHAR(100)  | IP address pengisi                 |
| user_agent      | VARCHAR(500)  | Browser/device info                |
| created_at      | TIMESTAMP     | Waktu pengisian (default NOW())    |

---

## Color Palette (Waschen Brand)

| Token            | Hex       | Penggunaan                              |
|------------------|-----------|-----------------------------------------|
| Primary          | `#5B005F` | Button utama, active state, highlight   |
| Primary Dark     | `#430046` | Hover/pressed state                     |
| Primary Light    | `#F3E6F5` | Background lembut, badge, chip aktif    |
| Primary Soft     | `#8A4A8D` | Accent, background gradient             |
| Secondary/Accent | `#C7A1C9` | Aksen card, ilustrasi ringan            |
| Success          | `#10B981` | Status selesai / checklist              |
| Warning          | `#F59E0B` | NPS Passive / express tag               |
| Danger           | `#EF4444` | Error, cancel, NPS Detractor            |

---

## Keamanan

- `config/db.php` dilindungi dari akses HTTP langsung via `config/.htaccess`
- Input di-sanitasi dengan `htmlspecialchars` dan prepared statements (mencegah SQL injection)
- Semua redirect POST menggunakan PRG pattern (Post-Redirect-Get)
- Double-submit dicegah dengan flag JavaScript `submitted`
- Session cookie menggunakan `httponly` dan `SameSite=Lax`

---

## Lisensi

Internal use only — PT Waschen Alora Indonesia.
