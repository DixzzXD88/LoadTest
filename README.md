
# 📊 Web Traffic Load Tester

Sebuah web sederhana untuk memantau lalu lintas real-time ke website kamu. Cocok digunakan untuk simulasi load testing, melihat jumlah pengunjung, IP unik, total request, grafik RPS (Request Per Second), dan statistik koneksi secara langsung.

## 🚀 Fitur Utama

- 📈 **Grafik RPS Real-time** – Lihat fluktuasi request per detik secara langsung.
- 🌐 **Jumlah IP Unik** – Pantau berapa banyak pengunjung berbeda yang mengakses.
- 🔢 **Total Request** – Hitung seluruh request yang masuk ke server.
- 📡 **Koneksi Aktif** – Lihat berapa banyak koneksi yang sedang terbuka.
- 📱 **Tampilan Responsif** – Bisa diakses dari desktop maupun mobile.
- ⚡ **Ringan & Cepat** – Dibangun dengan Node.js dan WebSocket.

## 🛠️ Instalasi

1. Clone repository ini:

```bash
git clone https://github.com/DixzzXD88/tes.git
cd tes
```

2. Install dependencies:

```bash
npm install
```

3. Jalankan web:

```bash
npm start
```

1. Buka browser dan akses:

```
http://localhost:3000
```

📦 Cara Penggunaan

1. Jalankan aplikasi di server atau lokal.
2. Arahkan target load test ke URL ini http://localhost:3000/attack.
3. Buka dashboard di browser untuk melihat statistik secara real-time.
4. Gunakan tools seperti Apache JMeter, k6, atau Postman untuk mengirim banyak request.

📁 Struktur Proyek

```
tes/
├── public/
│   └── index.html          # Halaman dashboard
├── server.js               # Server utama dengan Express & WebSocket
├── package.json            # Dependencies & script start
└── README.md               # Dokumentasi proyek
```

🧪 Contoh Statistik yang Ditampilkan

· Total Request: 12,345
· IP Unik: 89
· Koneksi Aktif: 5
· RPS: 23.5 req/s

🧰 Teknologi yang Digunakan

· Node.js
· Express
· ws (WebSocket)
· Chart.js untuk grafik RPS

📄 Lisensi

Proyek ini dilisensikan di bawah MIT License.

🤝 Kontribusi

Pull request dipersilakan! Untuk perubahan besar, harap buka issue terlebih dahulu untuk mendiskusikan perubahan yang ingin dilakukan.

---

Dibuat dengan ❤️ untuk keperluan load testing dan monitoring sederhana.

```

---

Semoga membantu! Kalau mau nambahin fitur atau ganti warna tema dashboard, tinggal bilang ya.
