# CORE SYSTEM BUILDER (MULTI-PART EXECUTION)

## CONTEXT & RULES
Ini adalah Master Blueprint untuk membangun mesin utama aplikasi.
**ATURAN KERJA SANGAT KETAT (WAJIB PATUHI):**
1. File ini terdiri dari 5 PART pengerjaan.
2. JANGAN PERNAH mengerjakan semuanya sekaligus!
3. Saat menerima prompt ini, kerjakan **HANYA PART 1**.
4. Berhenti, dan tunggu saya mengetik "Lanjut Part 2", barulah kamu kerjakan Part 2. Begitu seterusnya.
5. **Dukungan Sub-direktori (WAJIB):** Semua route redirects dan link aset di view/controller/middleware wajib menggunakan prefix variabel `$basePath` atau `{{ base_path }}` dinamis (diambil dari `$_SERVER['SCRIPT_NAME']`) agar aplikasi tidak pecah saat diletakkan di dalam subfolder XAMPP (seperti `/tes_ai/`).
6. **Kompatibilitas Tailwind CSS v2 & Translasi (WAJIB):** Karena aset CSS lokal menggunakan Tailwind v2.2.19, semua kode HTML/Twig dilarang keras menggunakan warna eksklusif Tailwind v3 (seperti `slate`, `zinc`, `stone`, `neutral`, `emerald`, `violet`, `rose`, `amber`). Gunakan warna standar Tailwind v2: `gray`, `purple`, `green`, `red`, `yellow`, `indigo`, `blue`, dsb. Selain itu, dilarang menggunakan kelas translasi utilitas Tailwind (seperti `transform`, `-translate-x-full`, `lg:translate-x-0`) pada sidebar utama untuk menghindari hilangnya menu samping; transisi/translasi harus diatur secara eksplisit via CSS kustom.
7. **Arsitektur Berlapis (WAJIB):** Setiap kode yang dihasilkan WAJIB mengikuti alur: `Controller → Service → Model`. Controller dilarang keras mengakses Model atau menulis bisnis logic secara langsung. Validasi input wajib melalui Request class sebelum diteruskan ke Service.

---

### PART 1: Directory & Engine Init
Hasilkan HANYA KODE LENGKAP untuk:
1. `composer.json` (Slim 4, PSR-7, PHP-DI, Eloquent, Twig-view, Flash, Dotenv, Monolog, Illuminate Pagination)
2. `.env.example`
3. `.htaccess` (Root level - alihkan semua request ke public/ dan override/unset CSP header bawaan server induk)
4. `public/.htaccess` (Public level - alihkan ke index.php)
5. `public/index.php` (Init Slim, Dotenv, dynamic base path detection, boot database, daftarkan Custom Error Handler)
6. `config/settings.php`
7. `config/dependencies.php` (DB, Twig, Flash Messages, Monolog via PHP-DI, daftarkan `base_path` ke Twig secara global, daftarkan semua Service ke container PHP-DI)
8. `app/Helpers/helpers.php` (Buat fungsi global awal: `format_rupiah()`, `format_tanggal()`, `generate_kode()`. Daftarkan file ini di `composer.json` bagian `autoload.files`.)
9. **WAJIB:** Jalankan perintah terminal (misal via PowerShell `Invoke-WebRequest` atau Bash `wget`/`curl`) secara proaktif untuk mengunduh file aset statis lokal ke dalam direktori `public/assets/vendor/` (seperti Tailwind CSS v2.2.19, SweetAlert2, Choices.js, Axios, Flatpickr) agar antarmuka tidak rusak karena ketiadaan CDN.

### PART 2: UI Layout & Database Schema
Hasilkan HANYA KODE LENGKAP untuk:
1. `resources/views/layouts/base.twig` (Master layout, tangkap Flash Message lalu render sebagai SweetAlert2 Toast, prefix semua link CSS/JS/Nav dengan `{{ base_path }}`. Terapkan AdminLTE sidebar layout dengan centralized body-class toggle, serta buat menu dropdown profil interaktif di sudut kanan atas yang menyatukan info nama, email, tautan Profil Saya, dan tombol Keluar)
2. `public/assets/js/app.js` (Fungsi Global SweetAlert2 Loading)
3. `database/migrations/001_init_rbac.sql` (Skema awal: users, roles, permissions, role_permissions, settings, audit_trails).
   **WAJIB UNTUK FILE SQL:**
   * Semua tabel WAJIB memiliki `id` (PK), `created_at`, `updated_at`, dan `deleted_at` (untuk SoftDeletes).
   * Tabel `audit_trails` wajib memiliki kolom: `user_id`, `action`, `module`, `description`, `ip_address`, `created_at`.
   * Letakkan satu kerangka "Blueprint Tabel Ideal" sebagai KOMENTAR di baris paling atas file SQL ini, agar saya bisa menconteknya saat ingin membuat tabel baru nanti.
   * Sertakan komentar header file: tanggal pembuatan, nama file, dan deskripsi singkat.

### PART 3: Security, Middleware & Exception Handler
Hasilkan HANYA KODE LENGKAP untuk:
1. `app/Exceptions/AppException.php` (Base exception class — semua custom exception extend ini)
2. `app/Exceptions/ValidationException.php` (Membawa array `$errors`. Method `getErrors(): array`)
3. `app/Exceptions/AuthorizationException.php` (Default message: "Akses ditolak.")
4. `app/Exceptions/NotFoundException.php` (Default message: "Data tidak ditemukan.")
5. `app/Exceptions/ErrorHandler.php` (Central Slim Error Handler — tangkap semua turunan `AppException`. Jika request AJAX kembalikan JSON `{"status":"error",...}`, jika request biasa kembalikan redirect + flash message error. Jangan tampilkan stack trace di production.)
6. `app/Services/AuditService.php` (Method `log(int $userId, string $action, string $module, string $description, string $ip): void` — menyimpan ke tabel `audit_trails` via Eloquent)
7. `app/Middleware/AuthMiddleware.php` (Gunakan `$basePath` untuk redirect ke `/login`)
8. `app/Middleware/RbacMiddleware.php` (Gunakan `$basePath` untuk redirect ke `/login` atau `/dashboard`)
9. `app/Extensions/TwigAuthExtension.php`

### PART 4: Authentication & Dashboard
Hasilkan HANYA KODE LENGKAP untuk:
1. `app/Requests/Auth/LoginRequest.php` (Validasi: `username` dan `password` wajib tidak kosong)
2. `app/Services/AuthService.php` (Logic: cari user by username, verifikasi password, set session. Lempar `ValidationException` jika credential salah)
3. `app/Controllers/AuthController.php` (Panggil `LoginRequest` lalu `AuthService`. Tangkap exception untuk flash message. Gunakan `$basePath` untuk redirect)
4. `resources/views/auth/login.twig` (Form action menggunakan `{{ base_path }}/login`)
5. `app/Controllers/DashboardController.php` & `resources/views/dashboard/index.twig` (Semua link menggunakan `{{ base_path }}`)

### PART 5: IAM (Identity Access Management) & Profile
Hasilkan HANYA KODE LENGKAP untuk:
1. **Profile:**
   - `app/Requests/Profile/UpdateProfileRequest.php`
   - `app/Services/ProfileService.php`
   - `app/Controllers/ProfileController.php`
   - `resources/views/profile/index.twig`

2. **Roles:**
   - `app/Requests/Role/StoreRoleRequest.php` & `app/Requests/Role/UpdateRoleRequest.php`
   - `app/Services/RoleService.php`
   - `app/Controllers/RoleController.php`
   - `resources/views/roles/index.twig`, `add.twig`, `edit.twig` (Tampilkan matriks audit hak akses peran di halaman indeks, dan gunakan matriks CRUD berbasis tabel untuk checkbox pilihan di form tambah dan edit)

3. **Users:**
   - `app/Requests/User/StoreUserRequest.php` & `app/Requests/User/UpdateUserRequest.php`
   - `app/Services/UserService.php` (Inject `AuditService`. Panggil `$this->auditService->log(...)` setelah setiap operasi CUD)
   - `app/Controllers/UserController.php`
   - `resources/views/users/index.twig`, `add.twig`, `edit.twig` (Gunakan Choices.js untuk elemen pemilihan peran)

4. `config/routes.php` (Registrasikan route Part 1-5, tambahkan redirect root `/` ke `/dashboard` atau `/login` menggunakan `$basePath`).
