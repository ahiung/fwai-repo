# MODULE GENERATION TASK (INTERACTIVE & FK-AWARE)

## CONTEXT
Ini adalah tugas pembuatan fitur/modul baru. Kamu WAJIB merujuk pada aturan keamanan dan arsitektur di `01-foundation.md`.

## TAHAP 1: BERHENTI & BERTANYA
Saat kamu menerima prompt ini, JANGAN buat kode apapun.
1. **WAJIB:** Sebelum melakukan hal lain, gunakan kemampuan pembacaan file untuk membaca secara utuh `01-foundation.md`. Selain itu, baca sekilas file `config/dependencies.php` dan satu file Service yang sudah ada agar kamu paham gaya pengkodean (code style) yang diterapkan oleh AI sebelumnya.
2. Sapa saya dengan singkat.
3. Minta saya untuk memberikan kode SQL (`CREATE TABLE`) dari tabel utama yang ingin dibuat modulnya. (Ingatkan saya untuk memastikan ada kolom `created_at`, `updated_at`, dan `deleted_at`).
4. Berhenti dan tunggu balasan saya.

---

## TAHAP 2: CEK RELASI (FOREIGN KEY)
Setelah saya memberikan kode SQL utama, analisis kolomnya:
1. Apakah ada kolom yang berakhiran `_id` atau berupa Foreign Key?
2. **JIKA ADA:** BERHENTI. Sebutkan kolom relasinya, dan minta saya mem-paste SQL dari tabel relasi tersebut (untuk data dropdown). Tunggu balasan saya.
3. **JIKA TIDAK ADA:** Langsung lanjut ke TAHAP 3.

---

## TAHAP 3: EKSEKUSI (Hasilkan Kode)
Hasilkan KODE LENGKAP untuk file berikut berdasarkan SQL yang diberikan (abaikan logika upload file).
Arsitektur WAJIB mengikuti alur: **Request → Controller → Service → Model**.

**A. Model (Eloquent)**
*   Buat `app/Models/[NamaEntitas].php`. Tentukan `$table`, `$fillable`, `$hidden`.
*   Gunakan trait `SoftDeletes`.
*   Buatkan method relasi `belongsTo` jika ada FK.
*   DILARANG menulis bisnis logic di Model.

**B. Request Classes**
*   Buat `app/Requests/[NamaEntitas]/Store[NamaEntitas]Request.php` dan `Update[NamaEntitas]Request.php`.
*   Setiap class wajib memiliki method `validate(array $data): array` yang:
    1. Memvalidasi rules wajib (tidak boleh kosong, format, panjang, dsb).
    2. Mensanitasi data (trim, htmlspecialchars, casting tipe).
    3. Mengembalikan array data yang sudah bersih jika valid.
    4. Melempar `ValidationException` dengan array pesan error jika tidak valid.

**C. Service**
*   Buat `app/Services/[NamaEntitas]Service.php`.
*   Inject `AuditService` via constructor.
*   Fungsi wajib: `getAll()`, `findById()`, `store()`, `update()`, `delete()`.
*   **Security (WAJIB di setiap fungsi findById/update/delete):** Validasi kepemilikan data (`Data Scope`) — filter by `office_id` atau scope yang relevan. Lempar `AuthorizationException` atau `NotFoundException` jika tidak cocok.
*   **Audit Trail (WAJIB):** Panggil `$this->auditService->log(...)` setelah setiap operasi `store`, `update`, dan `delete` berhasil.
*   **Soft Delete:** Fungsi `delete()` hanya memanggil `->delete()` Eloquent (soft delete).
*   Lempar exception yang sesuai (`NotFoundException`, `AuthorizationException`) untuk kondisi error bisnis. JANGAN return false/null untuk kondisi error.

**D. Controller (Slim 4)**
*   Buat `app/Controllers/[NamaEntitas]Controller.php`.
*   Inject Service via constructor (BUKAN Model langsung).
*   Fungsi: `index`, `create`, `store`, `edit`, `update`, `delete`.
*   **Pagination (WAJIB):** Fungsi `index` wajib menggunakan pagination (10 data per halaman) dengan mengonfigurasi `Paginator::currentPageResolver` dan `Paginator::currentPathResolver` secara dinamis menggunakan `$basePath`.
*   **Alur store/update (WAJIB):**
    1. Buat instance Request class, panggil `->validate($parsedBody)`.
    2. Tangkap `ValidationException` → kembalikan ke form dengan `$errors`.
    3. Jika valid, panggil Service.
    4. Tangkap `AppException` lainnya → set flash message error, redirect.
    5. Jika sukses → set flash message sukses, redirect.
*   **Flash Message:** Set flash message "Sukses" sebelum operasi redirect.
*   DILARANG menulis query Eloquent atau bisnis logic di Controller.

**E. Views (Twig - 4 File)**
Buatkan di `resources/views/[nama_entitas_huruf_kecil]/`:
1.  `list.twig`: Tabel data Tailwind (Tampilkan label relasi, bukan ID-nya) beserta kontrol navigasi pagination responsif (tampilan desktop detail dengan slider nomor halaman, tampilan mobile ringkas dengan tombol Sebelumnya/Selanjutnya) yang memicu `window.showLoading()` saat berpindah halaman. Tombol aksi (Detail, Edit, Hapus) WAJIB menggunakan tombol ikon SVG premium (ukuran `h-5 w-5` dibungkus padding `p-2` dan `rounded-xl`) dengan atribut `title` sebagai tooltip keterangan untuk menjamin antarmuka modern yang konsisten.
2.  `add.twig`: Form create (gunakan `<select>` + Choices.js jika ada FK). Tampilkan pesan error validasi per field jika `$errors` tersedia.
3.  `edit.twig`: Form update (value terisi). Tampilkan pesan error validasi per field jika `$errors` tersedia.
4.  `view.twig`: Form read-only.
*Catatan:* Tombol submit form WAJIB memicu `window.showLoading()`.

**F. Migration SQL**
*   Buat file `database/migrations/{nomor_urut}_{nama_tabel}.sql` dengan nomor urut berikutnya.
*   Sertakan komentar header: tanggal, nama file, deskripsi.
*   Isi dengan `CREATE TABLE` yang sudah diberikan (pastikan ada `deleted_at`).

**G. Route Snippet**
*   Berikan blok kode definisi Route (Group) untuk di-paste ke `config/routes.php`.
*   Sertakan juga instruksi singkat cara mendaftarkan Service baru ke `config/dependencies.php`.

**H. Integrasi Matriks RBAC (WAJIB)**
*   Daftarkan secara manual baris baru untuk hak akses modul (BACA, TAMBAH, UBAH, HAPUS) ke dalam tabel Matriks Otorisasi Granular di berkas formulir peran:
    1.  `resources/views/roles/add.twig`
    2.  `resources/views/roles/edit.twig`
    Hal ini penting agar izin (permission) baru tersebut dapat diatur dan dicentang oleh peran non-Superadmin lainnya.

## Execution Rules
*   **Contextual Yapping (Allowed):** AI diperbolehkan memberikan penjelasan singkat mengenai cara mengintegrasikan modul, cara memasang route, cara mendaftarkan Service di DI container, dsb.
*   **Complete Code:** Kode harus utuh dan fungsional.
