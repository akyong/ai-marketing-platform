# 04 - Actionable TODO & Phase 1 Checklist

## Phase 1 — Foundation Checklist (Target Minggu Ini)

- [x] **1. Docker Setup & Stability**
  - [x] Dockerfile (PHP 8.5-FPM + extensions)
  - [x] Nginx configuration (`docker/nginx/default.conf`)
  - [x] Docker Compose (`docker-compose.yml` dengan App, Nginx, PostgreSQL 17, Redis)
  - [x] Uji coba `docker compose up -d` & pastikan semua container running stabil (Nginx:8000, App, Postgres:5433, Redis:6379)

- [x] **2. Laravel & Authentication**
  - [x] Laravel 13 & Inertia Vue Breeze installed
  - [x] Fix Vite build issue (`bootstrap.js`)
  - [x] Migration database PostgreSQL selesai (`php artisan migrate`)
  - [x] Akses web & fungsi login di lingkungan Docker (`http://localhost:8000/login`) terverifikasi aktif

- [ ] **3. GitHub & CI/CD**
  - [x] Konfigurasi GitHub Actions CI Workflow ([ci.yml](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/.github/workflows/ci.yml))
  - [x] Konfigurasi GitHub Actions Auto-Deploy VPS Workflow ([deploy.yml](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/.github/workflows/deploy.yml))
  - [ ] Commit & Push repository ke GitHub
  - [ ] Verifikasi CI pipeline hijau di GitHub

- [ ] **4. Deployment ke VPS**
  - [x] Setup SSH auto-deploy workflow ke VPS saat push ke `main` branch
  - [ ] Tambahkan secrets (`VPS_HOST`, `VPS_USERNAME`, `VPS_SSH_KEY`) di repository GitHub Settings
  - [ ] Verifikasi app live di VPS

---

## Next Steps After Phase 1 Completed
1. Tinjau ulang & verifikasi checklist Phase 1 "semua hijau".
2. Tulis PRD mendalam khusus fitur **Workspace** (Phase 2).
