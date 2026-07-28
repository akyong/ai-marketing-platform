# 02 - Project Roadmap

Roadmap bertahap dan terfokus untuk pembangunan AI Marketing Platform.

---

## Phase 1 — Foundation (Sedang Berjalan)

**Fokus Utama**: Fondasi infrastruktur dan aplikasi dasar (Sprint 0).

Target Deliverables:
- [ ] Laravel jalan & stabil
- [ ] Docker (Nginx, PHP 8.5, PostgreSQL 17, Redis) jalan & stabil
- [ ] Push ke GitHub repository
- [ ] GitHub Actions (CI) berjalan untuk build/test
- [ ] VPS bisa auto-deploy dari GitHub Actions

*Catatan*: Belum ada OpenClaw, Repliz, maupun Gemini pada fase ini.

---

## Phase 2 — Platform

**Fokus Utama**: Aplikasi dasar manajemen user & workspace.

Target Deliverables:
- [ ] Login & Register Authentication (Laravel Breeze / Fortify)
- [ ] Multi-Workspace Management (CRUD Workspace)
- [ ] Brand Profile & Settings per Workspace
- [ ] User Settings

*Catatan*: Belum ada integrasi AI.

---

## Phase 3 — AI Integration

**Fokus Utama**: Menghubungkan sistem dengan AI Brain & Chat Interface.

Target Deliverables:
- [ ] OpenClaw Setup (Brain)
- [ ] Gemini API Integration
- [ ] Telegram Bot Setup
- [ ] Workflow: Telegram → OpenClaw → Gemini → Balasan / Rekomendasi Konten

*Catatan*: Belum auto-publish ke social media.

---

## Phase 4 — Social Publishing

**Fokus Utama**: Integrasi engine posting media sosial.

Target Deliverables:
- [ ] Integrasi Engine Repliz
- [ ] Feature Draft Content
- [ ] Feature Schedule Content
- [ ] Feature Auto/Manual Publish ke Instagram, Facebook, TikTok, X

---

## Phase 5 — AI Marketing Operating System

**Fokus Utama**: Fitur autonomous marketing penuh (kasus pertama: Waperu.com).

Target Deliverables:
- [ ] Automated Campaigns & Product Trend Analysis
- [ ] Knowledge Base (Stok produk, katalog, tone of voice)
- [ ] Social Media Analytics & Performance Reporting
- [ ] Autonomous Daily AI Agent (e.g. jam 07.00 rekomendasi & pembuatan jadwal posting harian)
