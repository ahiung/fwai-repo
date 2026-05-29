# 02 PROJECT SETUP (VIA GIT BOILERPLATE)

## CONTEXT & RULES
File ini menggantikan proses pembuatan boilerplate manual. Kita menggunakan template yang sudah matang dari Git. Tugas AI di sini hanyalah memandu proses instalasi dan memastikan lingkungan pengembangan (environment) siap digunakan sebelum masuk ke pembuatan modul.

## TAHAP 1: CLONE & DEPENDENCIES
Saat menerima prompt ini, JANGAN buat kode aplikasi. Lakukan hal berikut secara interaktif:
1. Sapa pengguna dengan singkat.
2. Instruksikan pengguna untuk melakukan clone repository boilerplate ke direktori lokal (misal: `git clone <url_repo_anda> .`).
3. Instruksikan pengguna untuk menjalankan `composer install` guna mengunduh semua dependensi backend (Slim 4, Eloquent, dll).
4. Berhenti dan tunggu konfirmasi dari pengguna bahwa tahap ini telah selesai.

---

## TAHAP 2: ENVIRONMENT & DATABASE
Setelah pengguna mengonfirmasi Tahap 1 selesai:
1. Instruksikan pengguna untuk menyalin (copy) file `.env.example` menjadi `.env`.
2. Minta pengguna untuk menyesuaikan kredensial database (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) di file `.env` tersebut.
3. Instruksikan pengguna untuk mengimpor skema database bawaan boilerplate (misalnya `database/init_rbac.sql`) ke dalam database MySQL lokal mereka.
4. Berhenti dan tunggu konfirmasi dari pengguna.

---

## TAHAP 3: FINISHING & HANDOVER
Setelah pengguna mengonfirmasi database siap:
1. Berikan ucapan selamat bahwa Boilerplate Sistem Core telah sukses diinstal dan siap dijalankan.
2. Ingatkan pengguna bahwa untuk membuat fitur atau tabel baru selanjutnya, mereka harus beralih menggunakan instruksi dari file **`03-module.md`**.
3. Sesi setup selesai.
