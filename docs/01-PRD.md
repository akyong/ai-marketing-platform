# 01 - Product Requirements Document (PRD)

## 1. Product Overview

AI Marketing Platform (AMP) adalah platform SaaS multi-workspace untuk mengelola dan memuatkan otomatisasi tim marketing berbasis AI.

**First Client**: `waperu.com` (Seafood E-Commerce / Business)

---

## 2. Workspace Core Architecture

Semua fitur dan aset di dalam platform terikat pada **Workspace**:

```
Workspace
    ├── Brand (e.g. Waperu)
    ├── Social Accounts (Instagram, FB, TikTok, X)
    ├── Knowledge Base (Katalog Produk, Stok, Tone of Voice, FAQ)
    ├── AI Agents (Content Planner, Analytics, Decision Engine)
    ├── Campaigns (Seasonal, Promo, Flash Sale)
    └── Analytics (Engagement, Conversion, Reach)
```

---

## 3. High-Level Flow & Components

1. **User Control Plane (Laravel 13 + Inertia Vue 3)**:
   - Authentication & User Management
   - Multi-Workspace Management
   - Brand & Settings Configuration
   - Dashboard & Content Approval Interface

2. **AI Brain (OpenClaw + Gemini)**:
   - Planning & Reasoning Engine
   - Integration via Telegram Bot Interface
   - Sub-agent Orchestration (Content Planner, Analytics, Knowledge Base, Decision Engine)

3. **Media & Storage (Cloudinary / S3)**:
   - Media asset management for images and videos

4. **Social Publishing Engine (Repliz)**:
   - Draft, Schedule, and Publish content across social channels (Instagram, Facebook, TikTok, X)

---

## 4. Simplified User Journey (MVP)

```
User -> Buat Workspace -> Hubungkan Telegram -> Hubungkan Gemini -> Hubungkan Repliz -> AI Membuat Konten -> User Review (via Telegram/Web) -> Publish -> Analytics
```

---

## 5. Non-Functional Requirements

- **Scalability**: Dockerized environment for PHP 8.5, Nginx, PostgreSQL 17, Redis.
- **Reliability**: Queue workers (Redis) for background tasks (AI jobs, scheduling).
- **CI/CD & Deployment**: GitHub Actions auto-deploy to VPS.
