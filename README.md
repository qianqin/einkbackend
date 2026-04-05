# TRMNL-13

Custom 13.3-inch e-ink dashboard built on [TRMNL](https://usetrmnl.com/) BYOS (Bring Your Own Server). Runs a family morning dashboard showing calendar events, school timetables, and weather on a 960x680 1-bit e-paper display driven by an ESP32-S3.

## What's in this repo

```
├── compose.yml                 Production Docker Compose
├── dashboard-api/              TRMNL plugin (Blade template + config)
│   ├── family-dashboard.blade.php
│   ├── custom-fields.yaml
│   └── config-values.md
├── firmware/                   ESP32 firmware (git submodule, forked from usetrmnl/trmnl-firmware)
├── scripts/
│   ├── deploy-firmware.sh      Build + OTA deploy script
│   ├── deploy-dashboard.sh     Deploy plugin template to server
│   └── .env.tt                 Deploy config template (copy to .env)
└── .github/workflows/
    └── deploy.yml              CI/CD for backend deploys
```

`laravel/` (TRMNL BYOS Laravel server) is gitignored — it tracks upstream at [usetrmnl/byos_laravel](https://github.com/usetrmnl/byos_laravel).

## Hardware

| Component | Part |
|---|---|
| MCU | LilyGo T7-S3 (ESP32-S3, PSRAM) |
| Display | GDEM133T91 13.3" e-paper (960x680, 1-bit, SSD1677) |
| Interface | SPI (SCK=5, MOSI=21, CS=8, DC=17, RST=47, BUSY=48) |

## Architecture

```
┌──────────────────────────────────────────────────┐
│  dashboard.qin.berlin                            │
│  ┌────────────────────────────────────────────┐  │
│  │  Docker: ghcr.io/usetrmnl/byos_laravel     │  │
│  │  ├─ Laravel (PHP-FPM + Nginx on :8080)     │  │
│  │  ├─ Puppeteer (Chromium, renders Blade→PNG) │  │
│  │  └─ SQLite                                  │  │
│  └────────────────────────────────────────────┘  │
│  Volumes: database, storage (images), firmware   │
│  Reverse proxy (Caddy/Nginx) → :2300             │
└──────────────┬────────────────┬──────────────────┘
               │                │
     GET /api/display    GET firmware.bin
               │                │
               ▼                ▼
       ┌──────────────────────────────┐
       │  ESP32-S3 (LilyGo T7-S3)    │
       │  ├─ Polls for new image      │
       │  ├─ Renders PNG/BMP to e-ink │
       │  └─ OTA update if flagged    │
       └──────────────────────────────┘
```

### Display refresh cycle

1. ESP32 wakes from deep sleep, connects to WiFi
2. Calls `GET /api/display` with headers: `access-token`, `id` (MAC), `fw-version`
3. Server renders the family dashboard Blade template → PNG via Puppeteer
4. Returns JSON with `image_url`, `refresh_rate`, `update_firmware`, `firmware_url`
5. ESP32 downloads the image, writes to e-paper, goes back to sleep
6. If `update_firmware` is true, downloads and flashes the `.bin` before sleeping

### Dashboard plugin

The family dashboard ([dashboard-api/family-dashboard.blade.php](dashboard-api/family-dashboard.blade.php)) is a TRMNL recipe that polls:

- **Weather**: BrightSky API (current conditions + temperature)
- **Calendars**: Up to 3 iCalendar feeds per adult (Google Calendar, etc.)
- **Timetables**: School schedule JSON per kid

It renders a 4-column layout (one per family member) with a weather/date bar at the bottom. After 16:00, kids' columns show the next school day.

Configure via `custom-fields.yaml` — see [config-values.md](dashboard-api/config-values.md) for details.

## Deploying the backend

Pushes to `main` auto-deploy via GitHub Actions on a self-hosted runner:

```
git push origin main
```

The workflow ([.github/workflows/deploy.yml](.github/workflows/deploy.yml)) pulls the latest `byos_laravel` image and restarts the container. The `.env` file lives on the server at `~/trmnl.env`.

### Manual deploy

```bash
ssh dashboard.qin.berlin
cd ~/trmnl-13
docker compose pull && docker compose up -d --remove-orphans
```

### Environment variables

| Variable | Default | Description |
|---|---|---|
| `APP_KEY` | — | Laravel encryption key (required) |
| `APP_PORT` | `2300` | Host port mapped to container |
| `APP_TIMEZONE` | `Europe/Berlin` | Server timezone |

The full list is in `laravel/.env.example`. The production compose.yml hardcodes `APP_URL=https://dashboard.qin.berlin` and disables registration.

## Deploy scripts

Both deploy scripts read credentials from `scripts/.env` (not committed). Copy the template to get started:

```bash
cp scripts/.env.tt scripts/.env
# Edit scripts/.env with your values
```

| Variable | Description |
|---|---|
| `DEPLOY_USER` | SSH user (required) |
| `DEPLOY_HOST` | SSH host (required) |
| `DASHBOARD_PLUGIN_UUID` | Plugin UUID for dashboard deploys (required) |
| `PIO_ENV` | PlatformIO build environment (default: `lilygo_t7_s3`) |

### Deploying the dashboard plugin

Push the Blade template and custom field schema to the server:

```bash
./scripts/deploy-dashboard.sh
```

This updates the plugin's `render_markup` and `configuration_template` in the database and clears the image cache. The dashboard re-renders on the next device refresh.

### Deploying firmware (OTA)

Build the firmware locally and push to the server for over-the-air update:

```bash
./scripts/deploy-firmware.sh
```

This will:
1. Read the version from `firmware/include/config.h` (e.g. `1.7.6`)
2. Build `pio run -e lilygo_t7_s3`
3. SCP the `.bin` to the server
4. Create a firmware record and flag all devices via `artisan tinker`
5. Devices OTA on next refresh

### Manual OTA

Use the interactive artisan command on the server:
```bash
docker compose exec app php artisan trmnl:firmware:update
```

## Building firmware locally

Requires [PlatformIO](https://platformio.org/).

```bash
cd firmware
pio run -e lilygo_t7_s3
```

Output: `.pio/build/lilygo_t7_s3/firmware.bin`

The firmware is a fork of [usetrmnl/trmnl-firmware](https://github.com/usetrmnl/trmnl-firmware) with these additions:
- LilyGo T7-S3 board definition and pin mapping
- SSD1677 display driver support (via custom [bb_epaper](https://github.com/qianqin/bb_epaper) fork)
- Resolution-agnostic BMP parser (not hardcoded to 800x480)
- BMP centering for undersized images
- Cold-boot full refresh to prevent ghosting

## Docker volumes

| Volume | Container path | Contents |
|---|---|---|
| `database` | `/var/www/html/database/storage` | SQLite database |
| `storage` | `/var/www/html/storage/app/public/images/generated` | Rendered screen images |
| `firmware` | `/var/www/html/storage/app/public/firmwares` | Firmware binaries for OTA |
