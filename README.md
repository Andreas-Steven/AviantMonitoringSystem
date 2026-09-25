<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Menjalankan dengan Docker

Stack Docker ini memakai PHP 8.3-FPM, Nginx, dan MySQL 8.4. Versi Laravel 12.58.0 dikunci di `composer.lock`, sedangkan asset Vite dibuat saat image dibangun.

### Prasyarat dan konfigurasi

Pastikan Docker Desktop dengan Docker Compose v2 sudah tersedia. Jalankan perintah berikut dari PowerShell pada folder proyek. Perintah pertama hanya membuat `.env` bila file tersebut belum ada, sehingga `.env` yang sudah ada tidak tertimpa.

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Nilai default di `.env.example` ditujukan untuk pengujian lokal. Compose mengarahkan aplikasi ke service `db`, menimpa pengaturan `DB_*` lama di `.env`, dan memakai driver session/cache berbasis file agar tidak perlu menambah tabel ke database yang disediakan. Pertahankan `MYSQL_DATABASE=manufacturing_test`, karena nama schema itu ditentukan di file SQL. Untuk konfigurasi lokal ini, Laravel dan Navicat sama-sama memakai `root` dengan password `root`; jangan gunakan konfigurasi tersebut di production. Port MySQL untuk koneksi dari Navicat adalah `3307` pada host dan `3306` di dalam container. `LOCAL_DASHBOARD_BYPASS=true` membuka dashboard produksi dan API terkait tanpa login hanya ketika `APP_ENV=local`. Akses langsung ke `/login` diarahkan ke dashboard; ketika login diminta oleh modul lama, halaman login tetap tampil. Modul lain tetap memakai autentikasi; login legacy memerlukan tabel `app_users`, roles, dan permissions yang tidak ada di dataset manufacturing ini. Di production, bypass tidak aktif. Akun `root/root` ini hanya untuk pengujian lokal, jangan dipakai di production. Jika `.env` sudah ada, set `LOCAL_DASHBOARD_BYPASS=true`, `MYSQL_ROOT_PASSWORD=root`, `DB_USERNAME=root`, dan `DB_PASSWORD=root`; hapus `MYSQL_USER` dan `MYSQL_PASSWORD` karena keduanya hanya untuk membuat akun non-root. `.env` dipasang ke container saat runtime dan tidak disalin ke image.

### Build dan jalankan

```powershell
docker compose up -d --build
```

Untuk menjalankan kembali setelah image sudah dibuat:

```powershell
docker compose up -d
```

Jika `APP_KEY` di `.env` masih kosong, buat key setelah container hidup:

```powershell
docker compose exec app php artisan key:generate
```

### Import dataset

Jika hanya ingin menyalakan database untuk import, jalankan dari folder proyek:

```powershell
docker compose up -d db
```

Di Navicat, buat koneksi MySQL ke Host `127.0.0.1`, Port `3307`, User `root`, dan Password `root` (sesuai `MYSQL_ROOT_PASSWORD`). Gunakan fitur **Run SQL File** pada koneksi root tanpa database default, lalu pilih `C:\Users\Andreas Steven\Downloads\Documents\dataset.sql`. Script tersebut membuat database `manufacturing_test` sendiri.

**Peringatan:** file SQL menjalankan `DROP DATABASE IF EXISTS manufacturing_test`, sehingga database dengan nama itu akan dihapus lalu dibuat ulang. Import hanya ke database lokal yang boleh diganti atau backup dahulu. Jangan jalankan `php artisan migrate` terhadap database ini karena struktur yang disediakan tidak boleh diubah. Konfigurasi root/root ini khusus local development dan tidak aman untuk production.

### URL aplikasi dan API

Aplikasi tersedia di [http://localhost:8080](http://localhost:8080); path `/` membuka dashboard produksi. Port dapat diubah dengan `APP_PORT` di `.env`.

Response API sukses memakai `code`, `success`, `message`, dan `data`. Key `meta` hanya muncul pada endpoint yang memiliki metadata — untuk daftar production order, `meta` berisi filter, sorting, dan pagination. Error validasi API memakai `code`, `success`, `message`, dan array `errors` di level teratas; setiap item berisi `field` dan `message`.

```json
{
  "code": 200,
  "success": true,
  "message": "Production dashboard retrieved successfully.",
  "data": {
    "summary": {},
    "trend_7_days": [],
    "status_breakdown": [],
    "top_machines": []
  }
}
```

Error validasi API menggunakan format terpisah dengan daftar error di level teratas:

```json
{
  "code": 422,
  "success": false,
  "message": "Validation failed. Please review the provided data",
  "errors": [
    {
      "field": "production_finish",
      "message": "The production finish field must be a date after or equal to production date."
    }
  ]
}
```

Untuk endpoint daftar, metadata filter, sorting, dan pagination berada di `meta`, sedangkan daftar hasil berada di `data`:

```json
{
  "code": 200,
  "success": true,
  "message": "Production order list retrieved successfully.",
  "meta": {
    "filter": {},
    "sort": { "by": "plan_start", "dir": "desc" },
    "pagination": { "total": 0, "display": 0, "page": 1, "page_size": 10 }
  },
  "data": []
}
```

Endpoint API yang ditentukan pada soal teknis:

- `GET /api/dashboard` — KPI, trend tujuh hari, status work order, dan top 10 mesin dalam satu response tanpa `meta`.
- `GET /api/dashboard/machine/{id}` — detail performa mesin; `{id}` memakai `machine_code` dan dicantumkan pada `meta.filter.machine_code`.
- `GET /api/production-orders` — mendukung `search`, `product`, `machine`, `status`, `date`, `date_from`, `date_to`, `page`, `per_page`, `sort`/`sort_by`, dan `direction`/`sort_direction`.
- `POST /api/production-results` — menerima JSON:

```json
{
  "wo_number": "WO2026000001",
  "production_date": "2026-09-25 08:00:00",
  "production_finish": "2026-09-30 15:00:00",
  "qty_good": 1200,
  "qty_reject": 20,
  "runtime_minutes": 360
}
```

`production_date` dan `production_finish` menerima format `Y-m-d H:i:s`. `production_date` tidak boleh melewati hari ini; jam berapa pun pada hari ini tetap valid. `production_finish` bersifat opsional dan harus sama dengan atau setelah `production_date`; jika tidak dikirim, waktu selesai dihitung dari `runtime_minutes`. `runtime_minutes` juga bersifat opsional. API menolak kuantitas negatif dan work order yang bukan `RUNNING`.

Untuk melihat log atau menghentikan container tanpa menghapus data database:

```powershell
docker compose logs -f
docker compose down
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
