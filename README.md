# Aviant Monitoring System

Production Monitoring System Berbasis Laravel yang digunakan untuk memonitor aktivitas produksi pabrik

**GitHub:** https://github.com/Andreas-Steven/AviantMonitoringSystem

## System Requirements

- Laravel 12
- PHP 8.3-FPM
- Nginx
- MySQL 8.4
- Docker Desktop 

## 1. Menjalankan dengan Docker

Copy file `.env.example` yang tersedia:

### Build dan jalankan

Jalankan via Terminal:

```powershell
docker compose up -d --build
```

Perintah ini membangun 3 service:

| Service | Image | Fungsi |
|---|---|---|
| `app` | `andreassteven/avian-monitoring-system:app` | PHP 8.3-FPM (Laravel) |
| `nginx` | `andreassteven/avian-monitoring-system:web` | Web server, port `8080` |
| `db` | `mysql:8.4` | Database, port host `3307` |

Generate `APP_KEY` di `.env` setelah container berjalan:

```powershell
docker compose exec app php artisan key:generate
```

## 2. Konfigurasi Environment (.env)

Variabel penting dalam file `.env` di antaranya:

| Variabel | Default | Keterangan |
|---|---|---|
| `APP_PORT` | `8080` | Port aplikasi di host |
| `MYSQL_PORT` | `3307` | Port MySQL di host (untuk Navicat/CLI) |
| `MYSQL_DATABASE` | `manufacturing_test` | Nama schema — harus sama dengan yang dibuat `dataset.sql` |
| `MYSQL_ROOT_PASSWORD` | `root` | Password root MySQL |
| `DB_HOST` / `DB_PORT` | `db` / `3306` | Koneksi Laravel ke MySQL *di dalam* jaringan Docker |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / `root` | Kredensial database |
| `LOCAL_DASHBOARD_BYPASS` | `true` | Buka dashboard & API tanpa login saat `APP_ENV=local` |

## 3. Import Database

Dataset tersedia di `database/database/dataset.sql`. 

### Opsi A — via Navicat

Buat koneksi MySQL:

- **Host:** `localhost`
- **Port:** `3307`
- **User:** `root`
- **Password:** `root`

Lalu **Run SQL File** pada koneksi tersebut dan pilih `database/database/dataset.sql`.

Catatan:
- File `Soal_1.sql` s/d `Soal_4.sql` merupakan query jawaban soal teknis

### Opsi B — via Docker CLI

Setelah container `db` hidup:

```powershell
Get-Content database\database\dataset.sql | docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
```

## 4. URL Aplikasi & Endpoint API

### 1. GET `/api/dashboard`

#### Request — Dashboard

```http
GET /api/dashboard
```

#### Result — Dashboard

```json
{
  "code": 200,
  "success": true,
  "message": "Production dashboard retrieved successfully.",
  "data": {
    "summary": {
      "total_machine": 24,
      "running_order": 157,
      "finished_order": 1243,
      "today_target": 8572,
      "today_good": 7811,
      "today_reject": 26,
      "achievement": 91.12
    },
    "trend_7_days": [
      {
        "date": "2026-12-25",
        "good_qty": 8919,
        "reject_qty": 63,
        "achievement": 60.46
      },
      ...
    ],
    "status_breakdown": [
      {
        "status": "RUNNING",
        "total": 157
      },
      ...
    ],
    "top_machines": [
      {
        "machine_code": "DSP02",
        "machine_name": "High Speed Disperser 02",
        "good_qty": 260210,
        "reject_qty": 2131,
        "target_qty": 295313,
        "achievement": 88.11
      },
      ...
    ]
  }
}
```

### 2. GET `/api/dashboard/machine/:id`

#### Request — Machine

```http
GET /api/dashboard/machine/:id
```

#### Result — Machine

```json
{
  "code": 200,
  "success": true,
  "message": "Machine performance retrieved successfully.",
  "meta": {
    "filter": {
      "machine_code": "WMX01"
    },
    "sort": {
      "by": "id",
      "dir": "desc"
    },
    "pagination": {
      "total": 1,
      "display": 1,
      "page": 1,
      "page_size": 10
    }
  },
  "data": {
    "machine_code": "WMX01",
    "machine_name": "Waterproof Mixer 01",
    "total_order": 76,
    "good_qty": 173307,
    "reject_qty": 1410,
    "downtime_minutes": 2223,
    "achievement": 87.11
  }
}
```

### 3. GET `/api/production-orders`

#### Request — Production Orders

```http
GET /api/production-orders?search=WO2026&status=RUNNING&sort_by=plan_start&sort_dir=desc&per_page=10&page=1
```

#### Result — Production Orders

```json
{
  "code": 200,
  "success": true,
  "message": "Production order list retrieved successfully.",
  "meta": {
    "filter": {
      "search": "WO2026",
      "status": "RUNNING"
    },
    "sort": {
      "by": "plan_start",
      "dir": "desc"
    },
    "pagination": {
      "total": 157,
      "display": 10,
      "page": 1,
      "page_size": 10
    }
  },
  "data": [
    {
      "wo_number": "WO2026000137",
      "product_code": "AVX0003",
      "product_name": "Avitex Interior Matt White 20 Kg",
      "machine_code": "WMX01",
      "machine_name": "Waterproof Mixer 01",
      "employee_no": "EMP0046",
      "employee_name": "Slamet Gunawan",
      "shift": "Shift 3",
      "target_qty": 1187,
      "plan_start": "2026-12-31 23:00:00",
      "plan_finish": "2027-01-01 07:00:00",
      "status": "RUNNING",
      "good_qty": 498,
      "reject_qty": 1
    },
    ...
  ]
}
```

### 4. POST `/api/production-results`

#### Request — Production Results

```http
POST /api/production-results
```

**Body:**

```json
{
  "wo_number": "WO2026000137",
  "production_date": "2026-09-27 16:42:43",
  "production_finish": "2026-09-28 10:32:45",
  "qty_good": 1500,
  "qty_reject": 25,
  "runtime_minutes": 420
}
```

#### Result — Success

```json
{
  "code": 201,
  "success": true,
  "message": "Production result created successfully.",
  "data": {
    "id": 1500,
    "wo_number": "WO2026000137",
    "production_date": "2026-09-27 16:42:43",
    "production_finish": "2026-09-28 10:32:45",
    "qty_good": 1500,
    "qty_reject": 25,
    "runtime_minutes": 420,
    "achievement": 126.37
  }
}
```

#### Result — Error: Production Order Not Running

```json
{
  "code": 422,
  "success": false,
  "message": "Validation failed. Please review the provided data",
  "errors": [
    {
      "field": "wo_number",
      "message": "The production order must have a status of RUNNING."
    }
  ]
}
```

#### Result — Error: Production Date

```json
{
  "code": 422,
  "success": false,
  "message": "Validation failed. Please review the provided data",
  "errors": [
    {
      "field": "production_date",
      "message": "The production date field must be a date before or equal to today."
    },
    {
      "field": "production_finish",
      "message": "The production finish field must be a date after or equal to production date."
    }
  ]
}
```
