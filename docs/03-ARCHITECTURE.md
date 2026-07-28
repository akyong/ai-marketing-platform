# 03 - System Architecture

## Architecture Overview

AI Marketing Platform menggunakan pola arsitektur **Control Plane** (Laravel) dan **Execution Brain** (OpenClaw + AI Providers).

```
                 Telegram Interface
                         │
                         ▼
                  OpenClaw (Brain)
                         │
     ┌───────────────────┼───────────────────┐
     │                   │                   │
 Content Planner      Analytics        Knowledge Base
     │                   │                   │
     └───────────────────┼───────────────────┘
                         │
                   Decision Engine
                         │
      ┌──────────────────┼──────────────────┐
      ▼                  ▼                  ▼
   Repliz             Laravel           Cloudinary
(Social Engine)    (Control Plane)    (Media Storage)
      │
      ▼
 Social Channels (IG, FB, TikTok, X)
```

---

## Infrastructure Stack

- **Framework**: Laravel 13 (PHP 8.5-FPM)
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS
- **Database**: PostgreSQL 17
- **Cache & Queue**: Redis 7
- **Web Server**: Nginx
- **Containerization**: Docker & Docker Compose
- **CI/CD & Hosting**: GitHub Actions -> VPS Deployment
- **Social Engine**: Repliz
- **AI Engine**: OpenClaw + Gemini API
