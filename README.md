<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Menjalankan dengan Docker

Stack Docker ini memakai PHP 8.3-FPM, Nginx, dan MariaDB 11.4. Versi Laravel 12.58.0 dikunci di `composer.lock`, sedangkan asset Vite dibuat saat image dibangun.

### Prasyarat dan konfigurasi

Pastikan Docker Desktop dengan Docker Compose v2 sudah tersedia. Jalankan perintah berikut dari PowerShell pada folder proyek. Perintah pertama hanya membuat `.env` bila file tersebut belum ada, sehingga `.env` yang sudah ada tidak tertimpa.

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Nilai default di `.env.example` ditujukan untuk penggunaan lokal. Compose mengarahkan aplikasi ke service `db`, menimpa pengaturan `DB_*` lama di `.env`, dan memakai driver session/cache berbasis file agar tidak perlu menambah tabel ke database yang disediakan. Pertahankan `MARIADB_DATABASE=manufacturing_test`, karena nama schema itu ditentukan di file SQL. `.env` dipasang ke container saat runtime dan tidak disalin ke image.

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

File SQL yang diberikan berada di `C:\Users\Andreas Steven\Downloads\Documents\dataset.sql`. Perintah berikut menyalin file ke container MariaDB lalu mengimpornya:

```powershell
docker compose cp "C:\Users\Andreas Steven\Downloads\Documents\dataset.sql" db:/tmp/dataset.sql
docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" < /tmp/dataset.sql'
```

**Perhatian:** script dataset menjalankan `DROP DATABASE IF EXISTS manufacturing_test` sebelum membuat ulang database. Import hanya ke database lokal yang memang boleh diganti. Jangan jalankan `php artisan migrate` terhadap database ini karena struktur yang disediakan tidak boleh diubah.

### URL aplikasi dan API

Aplikasi tersedia di [http://localhost:8080](http://localhost:8080). Port dapat diubah dengan `APP_PORT` di `.env`. Endpoint API yang ditentukan pada soal teknis:

- `GET /api/dashboard`
- `GET /api/dashboard/machine/{id}`
- `GET /api/production-orders`
- `POST /api/production-results`

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
