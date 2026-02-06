# MC Skin 3D Viewer

MC Skin 3D Viewer adalah sebuah **aplikasi web** untuk melihat Minecraft skin dalam bentuk **3D langsung di browser**.  
Pengguna dapat mengunggah file skin mereka dan melihat model 3D secara interaktif.

---

## 📂 Struktur Direktori

.
├── assets/ # Resource seperti skin default atau gambar pendukung

├── javascript/ # Script utama untuk menampilkan skin 3D

├── output/ # Hasil render / file yang ditampilkan setelah upload

├── uploads/ # Folder untuk menyimpan file skin yang di-upload

├── index.php # Halaman utama (form upload & viewer)

├── upload.php # Script backend untuk menghandle upload skin

└── README.md # Dokumentasi proyek


---

## 🔧 Fitur

- Upload skin Minecraft (.png)
- Tampilkan skin dalam model **3D** di browser
- Interaktif: bisa diputar / dilihat dari berbagai sudut
- Menyimpan skin yang diupload di folder `uploads/`
- Backend ringan dengan PHP
- Tampilan frontend menggunakan JavaScript

---

## 🛠️ Cara Menggunakan

1. **Clone / download repo ini**

```bash
git clone https://github.com/BangGarem/mc-skin-3d.git
Jalankan di web server lokal

Gunakan server seperti:

XAMPP / WAMP / MAMP
atau server built-in PHP:

php -S localhost:8000
Buka di browser

Lalu buka:

http://localhost:8000/index.php
Upload skin

Pilih file PNG skin Minecraft lalu klik Upload.
Model 3D akan tampil setelah proses selesai.