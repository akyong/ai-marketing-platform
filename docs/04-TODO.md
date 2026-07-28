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

- [x] **3. GitHub & CI/CD**
  - [x] Konfigurasi GitHub Actions CI Workflow ([ci.yml](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/.github/workflows/ci.yml))
  - [x] Konfigurasi GitHub Actions Auto-Deploy VPS Workflow ([deploy.yml](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/.github/workflows/deploy.yml))
  - [x] Commit & Push repository ke GitHub (`https://github.com/akyong/ai-marketing-platform`)
  - [x] Verifikasi CI pipeline hijau di GitHub

- [x] **4. Deployment ke VPS**
  - [x] Setup SSH auto-deploy workflow ke VPS saat push ke `main` branch
  - [x] SSH Keys & setup repo di VPS (`/var/www/ai-marketing-platform`)
  - [x] Node 20 LTS & Vite build di VPS
  - [x] App live & terverifikasi di VPS (`http://150.109.4.229:8000/login`)

---

## Phase 2 — Platform (Workspace) Checklist

- [x] **1. PRD & Database Schema untuk Workspace**
  - [x] Menyusun struktur tabel `workspaces` (id, name, slug, owner_id)
  - [x] Menyusun relasi `workspace_user` (pivot table roles & permissions)
  - [x] Menyusun skema `brands` terikat pada Workspace (first client: Waperu.com)

- [x] **2. Backend Workspace (Laravel)**
  - [x] Migration `create_workspaces_table` & `create_workspace_user_table`
  - [x] Model Eloquent `Workspace`, `Brand`, relasi `User->workspaces()`
  - [x] Shared data Inertia `HandleInertiaRequests` untuk Workspace aktif
  - [x] Controller CRUD Workspace & Brand (`WorkspaceController`, `BrandController`)

- [x] **3. UI Frontend Workspace (Vue 3 + Inertia)**
  - [x] Dropdown Switcher Workspace di Navbar ([WorkspaceSwitcher.vue](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/resources/js/Components/WorkspaceSwitcher.vue))
  - [x] Form "Create Workspace" di UI
  - [x] Halaman "Workspace & Brand Settings" ([Settings.vue](file:///Users/bobbyakyong/programming/php/laravel/ai-marketing-platform/resources/js/Pages/Workspace/Settings.vue))

---

## Phase 3 — AI Integration (Next Phase)

- [ ] **1. Telegram Integration Setup**
  - [ ] Telegram Bot webhook handler & command routing
- [ ] **2. OpenClaw Setup (AI Brain)**
  - [ ] OpenClaw reasoning & agent coordinator setup
- [ ] **3. Gemini AI Provider**
  - [ ] Gemini API key integration for prompt execution & target research
