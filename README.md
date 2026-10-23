# PastelBloom Studio — Toko Buket (Bunga, Snack, Uang & Boneka)
### Mini Project 3 — Pertemuan 4: Relational Database + Advanced PHP
*Tema: Toko Buket dengan Palet Warna Pastel Cantik (Bunga, Snack, Uang, Boneka)*

---

## 🌸 Konsep & Tema Toko Buket

Proyek ini telah dikustomisasi secara khusus dengan tema **Toko Buket (PastelBloom Studio)** menggunakan palet warna **Pastel** (Dusty Pink, Soft Lilac, Peach Cream, dan Soft Mint) dengan 4 kategori utama produk:
1. 🌸 **Buket Bunga**: Mawar Pink Velvet, Korean Style Tulip Peach & Lilac Hydrangea, Baby Breath Rainbow.
2. 🍫 **Buket Snack**: Cokelat SilverQueen Chunky & Strawberry Pocky, Ferrero Rocher Golden Rose.
3. 💸 **Buket Uang**: Buket Uang Asli Pecahan 50K (Rp 500.000) & Tarik Tunai Surprise Box 100K (Rp 2.000.000).
4. 🧸 **Buket Boneka**: Teddy Bear Wisuda Toga Sarjana, Boneka Kelinci Soft Lilac.

---

## 📂 Struktur Project

```
pertemuan4-inventory-web/
├── index.html                    <-- Pintasan membuka Web Pembelajaran
├── web/
│   └── index.html                <-- Web Interaktif Lengkap (Katalog Buket, Custom Studio, Slide PPT, Live Simulator)
│
└── inventory-app/                <-- Aplikasi PHP Native Lengkap (Slide 34)
    ├── config/database.php       <-- Koneksi PDO (MySQL & SQLite fallback)
    ├── helpers/
    │   ├── auth.php              <-- Login, Session, RBAC (Owner vs Florist Staff)
    │   ├── validation.php        <-- Validasi Input & Allowlist Sorting
    │   └── escape.php            <-- Output Encoding htmlspecialchars
    ├── database/schema.sql       <-- Skema Database Relasi Toko Buket (1:1, 1:N, N:N) + Seed Data
    ├── views/
    │   ├── header.php            <-- Template Header Pastel
    │   └── footer.php            <-- Template Footer Pastel
    └── public/
        ├── login.php             <-- Login Florist
        ├── logout.php
        ├── dashboard.php         <-- 5 Widget Agregat & Audit Mutasi
        ├── products/             <-- Katalog Buket & Relasi Supplier Junction
        ├── categories/           <-- 4 Kategori Buket
        ├── suppliers/            <-- Supplier Bahan (Bunga, Snack, Boneka, Wrapping)
        ├── stock/                <-- Transaksi Masuk/Keluar (+IN / -OUT) & Histori
        └── reports/              <-- Laporan Agregasi SUM(CASE WHEN...)
```

---

## 🌐 1. Cara Membuka Web Interaktif (Katalog & Live Simulator)

Cukup **klik dua kali (double click)** file:
[`web/index.html`](file:///C:/Users/Cantika/.gemini/antigravity/scratch/pertemuan4-inventory-web/web/index.html) (atau [`index.html`](file:///C:/Users/Cantika/.gemini/antigravity/scratch/pertemuan4-inventory-web/index.html) di root) pada browser Anda.

**Fitur Web Interaktif:**
1. **🛍️ Katalog Buket Pastel**: Etalase produk buket bunga, snack, uang, dan boneka dengan filter momen (Wisuda 🎓, Ulang Tahun 🎂, Anniversary 💖, Valentine 💐) serta tombol Order langsung via WhatsApp.
2. **🎨 Custom Buket Studio**: Desainer interaktif untuk memilih jenis buket, warna kertas wrapping pastel (Dusty Pink, Soft Lilac, Peach, Mint), pita satin, dan kartu ucapan gratis.
3. **⚙️ Simulator Inventaris Buket**: Mesin simulator business logic toko buket di browser (Role Owner vs Florist, transaksi masuk/keluar, proteksi stok negatif, dan audit log).
4. **📊 Slide PPT (38 Slide Lengkap)**: Presentasi materi kuliah Pertemuan 4 yang dikaitkan dengan studi kasus Toko Buket.
5. **🗄️ Skema ERD**: Diagram relasi 1:1, 1:N, N:N junction table, dan aturan RESTRICT vs CASCADE.
6. **🛡️ Security Lab**: Uji coba SQL Injection & XSS.
7. **📝 Rubrik Penilaian**: Kalkulator nilai otomatis untuk evaluasi tugas mahasiswa.

---

## 🐘 2. Cara Menjalankan Aplikasi PHP Native

### Opsi Cepat (PHP Built-in Server):
```powershell
cd C:\Users\Cantika\.gemini\antigravity\scratch\pertemuan4-inventory-web\inventory-app
php -S localhost:8000 -t public
```
Lalu buka di browser: `http://localhost:8000`
*(Sistem otomatis membuat database lokal `toko_buket.sqlite` jika MySQL belum aktif).*

### Kredensial Login Demo:
- **Owner Admin**: `admin` | Password: `admin123`
- **Florist Staff**: `staff` | Password: `staff123`
