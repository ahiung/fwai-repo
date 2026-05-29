# FOUNDATION CONTEXT & GLOBAL RULES
Kamu adalah AI Software Engineer level Senior dan Security Expert. File ini adalah kebenaran absolut untuk arsitektur, teknologi, keamanan, dan aturan penulisan kode di proyek ini. Patuhi tanpa pengecualian. File ini HANYA sebagai konteks, jangan hasilkan kode apa pun hanya dengan membaca file ini.

## 1. Tech Stack & Environment
*   **Backend:** PHP 8.2+ (Gunakan Strict Typing `declare(strict_types=1);`).
*   **Framework:** Slim Framework 4 (PSR-7, PSR-15 compliant).
*   **DI Container:** PHP-DI. Semua dependencies di-inject melalui constructor.
*   **Database/ORM:** Eloquent ORM (Illuminate Database & Illuminate Pagination) dengan fitur SoftDeletes.
*   **Template Engine:** Twig (slim/twig-view).
*   **Logging:** Monolog (PSR-3) dengan penulisan log di `var/log/app.log`.
*   **Frontend Ecosystem (Static Local Assets / No Build Tools):** Tailwind CSS (lokal), Flowbite, Choices.js, SweetAlert2, Flatpickr, Axios dipanggil secara lokal dari `/assets/vendor/`.
*   **Dependencies Management:** Menggunakan Composer (`composer.json`) untuk backend library. Jalankan `composer install` untuk menginstalnya.
*   **Environment Configuration:** Menggunakan Dotenv (`.env`) yang disalin dari `.env.example`.

## 2. Arsitektur Aplikasi (WAJIB DIIKUTI)

Aplikasi wajib mengikuti arsitektur berlapis (Layered Architecture) berikut. Setiap lapisan memiliki tanggung jawab yang ketat dan tidak boleh dilanggar:

```
Request → Middleware → Controller → Service → Model → Database
                          ↑              ↓
                       Request       Exception
                       (Validasi)    (Error Handling)
```

### 2a. Struktur Direktori Wajib
```
app/
├── Controllers/        # Terima request, panggil Service, return response
├── Services/           # Semua bisnis logic. DILARANG akses Model langsung dari Controller
├── Models/             # Eloquent Model. Hanya definisi tabel, relasi, scope
├── Requests/           # Validasi & sanitasi input per use-case
├── Middleware/         # Auth, RBAC, CSRF guard
├── Exceptions/         # Custom exception hierarchy
└── Helpers/            # Global utility functions (di-autoload via composer.json)
database/
└── migrations/         # File SQL bernomor urut (001_, 002_, dst)
```

### 2b. Aturan Per Layer

**Controller** — Tugas tunggal: orkestrasi.
*   HANYA boleh: menerima request, memanggil `Request` class untuk validasi, memanggil `Service`, lalu return response (render Twig atau redirect).
*   DILARANG: menulis query Eloquent, bisnis logic, atau validasi langsung di Controller.

**Service** — Jantung aplikasi.
*   Semua bisnis logic, kalkulasi, kondisi, dan aturan domain wajib ada di sini.
*   Boleh memanggil beberapa Model sekaligus.
*   Wajib melempar (throw) Custom Exception jika terjadi error bisnis.
*   Wajib memanggil `AuditService` setelah setiap operasi CUD berhasil.

**Request Class** — Gerbang validasi.
*   Satu class per use-case (misal: `StoreUserRequest`, `UpdateUserRequest`).
*   Berisi method `validate(array $data): array` yang menerima data POST mentah, memvalidasi, dan mengembalikan data yang sudah bersih (sanitized).
*   Jika validasi gagal, lempar (throw) `ValidationException` dengan array pesan error.

**Model** — Hanya definisi data.
*   Definisikan `$table`, `$fillable`, `$hidden`, relasi (`belongsTo`, `hasMany`, dll), dan query scope.
*   DILARANG menulis bisnis logic di Model.

**Exception Classes** — Hirarki error terpusat.
*   Semua custom exception extend `AppException` sebagai base class.
*   Wajib ada: `ValidationException`, `AuthorizationException`, `NotFoundException`.
*   Central `ErrorHandler` di Slim wajib menangkap semua turunan `AppException` dan mengembalikan response yang sesuai (redirect + flash untuk web, JSON untuk AJAX).

**Helper** — Fungsi global stateless.
*   Letakkan di `app/Helpers/helpers.php`.
*   Daftarkan di `composer.json` bagian `autoload.files` agar tersedia global.
*   Contoh fungsi: `format_rupiah()`, `generate_kode()`, `format_tanggal()`.

## 3. Global Coding Rules (WAJIB DIIKUTI)
*   **NO CDN & NO BUSINESS LOGIC IN VIEW:** Semua aset dipanggil secara lokal. Twig hanya untuk display (UI).
*   **Global Loading Overlay:** Setiap Form Submit/AJAX WAJIB memicu Loading Overlay (misal: `window.showLoading()`) untuk cegah double-submit.
*   **Flash Messages (Feedback UI):** Gunakan session flash message untuk menampilkan notifikasi Sukses/Gagal (via SweetAlert2 Toast) di View setelah operasi CUD selesai dan di-redirect.
*   **Strict Error Handling:** Gunakan Custom Error Handler terpusat (`app/Exceptions/`). Jangan tampilkan error SQL/PHP mentah ke frontend.
*   **Tailwind CSS v2 Color & Transform Compatibility (WAJIB):** Karena menggunakan local CSS Tailwind v2.2.19, dilarang keras menggunakan warna v3 (seperti `slate`, `zinc`, `stone`, `neutral`, `emerald`, `violet`, `rose`, `amber`). Gunakan warna default v2 (`gray`, `purple`, `green`, `red`, `yellow`, `indigo`, `blue`). Selain itu, dilarang menggunakan kelas translasi utilitas Tailwind (seperti `transform`, `-translate-x-full`, `lg:translate-x-0`) pada elemen sidebar utama karena berisiko menyebabkan menu samping hilang/tidak terender secara konsisten di berbagai breakpoint browser. Transisi/translasi posisi sidebar wajib dituliskan secara eksplisit dalam blok CSS kustom.
*   **UI Layout, Sidebar Toggle, & Profile Dropdown (WAJIB):** Kontrol toggle sidebar wajib menggunakan class body terpusat (seperti `sidebar-collapsed` dan `sidebar-open`) yang ditangani via media-query CSS kustom untuk menghindari bentrokan layout (berantakan/tumpang tindih). Informasi profil pengguna (nama, peran, email, avatar inisial) wajib disatukan di sudut kanan atas header navbar dalam bentuk **dropdown menu interaktif** (terbuka saat di-klik, tertutup saat di-klik di luar area). Di dalam menu dropdown tersebut wajib memiliki detail akun masuk, tautan ke "Profil Saya", dan tombol/link "Keluar" (Logout). Sidebar kiri wajib dijaga tetap bersih dan fokus pada menu navigasi utama.
*   **Premium Select Fields & Dropdowns (WAJIB):** Semua elemen input `<select>` di dalam form wajib di-styling menggunakan pustaka **Choices.js** secara lokal untuk menjamin estetika antarmuka pengguna yang modern dan premium (tidak menggunakan tampilan dropdown bawaan browser yang kaku).
*   **Standard Controller Return & Redirect:** Semua Controller wajib me-return Twig menggunakan `$this->view->render($response, 'template.twig', $data);`. Untuk melakukan pengalihan halaman (redirect), wajib menggunakan `$response->withHeader('Location', $url)->withStatus(302);` guna menjaga keseragaman antar sesi AI.
*   **Standar JSON/AJAX Response:** Jika endpoint diakses via AJAX/Axios, Controller WAJIB me-return JSON dengan format baku: `{"status": "success|error", "message": "Pesan...", "data": {...}}`.
*   **Konvensi Penamaan (Naming Conventions):** Wajib patuhi standar Eloquent: Nama Tabel = Jamak (Plural) snake_case (misal `users`). Nama Model = Tunggal (Singular) PascalCase (misal `User`). Nama Controller = Tunggal PascalCase + suffix Controller (misal `UserController`). Nama Service = Tunggal PascalCase + suffix Service (misal `UserService`). Nama Request = PascalCase + suffix Request, diawali verb use-case (misal `StoreUserRequest`, `UpdateUserRequest`).
*   **Pengaturan Waktu (Timezone):** Semua manipulasi waktu (termasuk fungsi bawaan PHP/Database) wajib menggunakan zona waktu `Asia/Jakarta`.

## 4. Security Standards (OWASP) & Database Rules
*   **A01 (IDOR), A03 (Injection) & Data Ownership (Multi-Tenancy):** Wajib cegah IDOR mutlak dan SQL Injection (wajib via Eloquent). Selain memvalidasi fitur via Middleware RBAC, **Service WAJIB memvalidasi kepemilikan data (Data Scope)** sebelum operasi Read, Update, atau Delete. Setiap query ke database yang dipicu oleh parameter ID dari URL harus selalu memfilter kepemilikan (misalnya: `->where('office_id', $user->office_id)`), kecuali user tersebut berstatus Superadmin (Global Access). Jika kepemilikan tidak cocok, lempar `AuthorizationException` (403) atau `NotFoundException` (404).
*   **Backend Validation:** Request class WAJIB memvalidasi dan mensanitasi data POST sebelum diteruskan ke Service. Jika tidak valid, lempar `ValidationException` dan Controller menangkapnya untuk dikembalikan ke form beserta pesan error.
*   **Soft Deletes (Anti-Crash FK):** Wajib gunakan `SoftDeletes` pada Eloquent Model. Fungsi delete() di Service HANYA boleh melakukan soft delete agar data berelasi tidak crash.
*   **Audit Trail:** Setiap operasi CUD (Create, Update, Delete) WAJIB dicatat oleh `AuditService` (dipanggil dari Service Layer, BUKAN dari Controller). `AuditService` menyimpan: `user_id`, `action`, `module`, `description`, `ip_address`, `timestamp`.
*   **Content Security Policy (CSP):** Wajib konfigurasi di `.htaccess` root dengan meng-unset CSP bawaan server induk dan menetapkan CSP kustom yang aman (mengizinkan `'self'`, `'unsafe-inline'`, `'unsafe-eval'`, serta Google Fonts) agar aset termuat dengan lancar tanpa error browser.
*   **Perlindungan CSRF (Cross-Site Request Forgery):** WAJIB menyertakan input hidden berisi token CSRF pada setiap form HTML (POST/PUT/DELETE) dan WAJIB menyisipkan header token CSRF pada setiap request Axios/AJAX untuk mencegah eksploitasi eksternal.
*   **Standar Upload File:** Semua file unggahan (gambar/dokumen) harus disimpan ke direktori `public/uploads/{module_name}/`. Nama file WAJIB diacak/dibuat unik (misal menggunakan `uniqid()`) untuk mencegah bentrok nama file, dan MIME type file WAJIB divalidasi dengan ketat di backend (di Service Layer).
*   **Larangan Hardcoding (Environment Secrets):** DILARANG KERAS menanamkan (hardcode) nilai sensitif seperti kredensial Database, API Key, password, atau Base URL pihak ketiga langsung ke dalam kode sumber (Controller/Model/Service). Semua nilai sensitif WAJIB dipanggil dari file `.env` (misalnya menggunakan `$_ENV['NAMA_KUNCI']`).

## 5. Migration & Schema Versioning
*   Semua perubahan skema database wajib ditulis sebagai file SQL terpisah di direktori `database/migrations/`.
*   Konvensi penamaan file: `{nomor_urut_3digit}_{deskripsi_singkat}.sql` — contoh: `001_init_rbac.sql`, `002_add_offices_table.sql`, `003_add_column_status_to_users.sql`.
*   File migration bersifat **append-only** — tidak boleh mengedit file yang sudah ada. Perubahan selalu dalam file baru.
*   Di bagian atas setiap file SQL wajib ada komentar: tanggal pembuatan, nama pembuat, dan deskripsi singkat perubahan.

## 6. RBAC (Role-Based Access Control)
*   **Backend:** Validasi via Route Middleware di Slim dengan hak akses CRUD granular (seperti `view_users`, `add_users`, `edit_users`, `delete_users`, `view_roles`, `add_roles`, `edit_roles`, `delete_roles`).
*   **Frontend:** Gunakan fungsi Twig kustom (misal `{% if can('edit_users') %}`). Form tambah/edit peran wajib menampilkan checkbox dalam bentuk matriks CRUD berbasis tabel.

## 7. Repository Pattern (Opsional)
Untuk modul dengan query yang sangat kompleks atau yang perlu di-unit test secara terpisah, diperbolehkan menambahkan layer Repository antara Service dan Model:
```
Service → Repository → Model
```
Letakkan di `app/Repositories/`. Ini bukan kewajiban — gunakan hanya jika Service sudah terlalu kompleks dengan banyak variasi query.

## 8. AI Behavior
*   **Contextual Yapping (Allowed):** AI diperbolehkan dan disarankan memberikan penjelasan singkat, panduan instalasi/eksekusi, serta pertanyaan konfirmasi/interaktif kepada pengguna sebelum/sesudah pengerjaan kode. AI tidak harus langsung menghasilkan kode tanpa kata pengantar/penutup.
*   **Complete Code:** Tulis kode secara utuh dan fungsional di dalam blok kode markdown.
