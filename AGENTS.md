# PROJECT KNOWLEDGE BASE

**Generated:** 2026-02-12
**Stack:** Laravel 12, Inertia.js (React), Tailwind CSS, Python (Scraper), PostgreSQL

## OVERVIEW
School management system for SMAN 1 Baleendah. Hybrid monorepo structure containing a Laravel PHP application for the main web/admin interface and a standalone Python-based Instagram scraper for content aggregation.

## STRUCTURE
```
.
├── app/                  # Core Laravel Business Logic (Services, Models, Jobs)
├── instagram-scraper/    # [Distinct Domain] Python scraper subsystem
├── resources/js/         # [Distinct Domain] React + Inertia Frontend
├── database/             # Migrations, Seeders, Factories
└── tests/                # Feature & Unit tests
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| **Frontend UI** | `resources/js/` | React components, Pages, Layouts |
| **Business Logic** | `app/Services/` | Heavy logic lives here (e.g., `RagService`, `ContentCreationService`) |
| **Database Models** | `app/Models/` | Eloquent ORM models |
| **Scraper Logic** | `instagram-scraper/` | Independent Python environment |
| **API/Routing** | `routes/web.php` | Main entry for Inertia routes |

## KEY CONVENTIONS
- **Service Pattern**: Business logic is encapsulated in `app/Services` (e.g., `ChatCacheService`, `GroqService`), not Controllers.
- **Hybrid AI**: Integrates Groq/LLM services (`app/Services/GroqService.php`) and RAG implementation (`app/Services/RagService.php`).
- **Scraper Isolation**: Scraper uses `sc_` prefix for its database tables (`sc_bot_accounts`, `sc_raw_news_feeds`) to avoid collision with Laravel tables.

## COMMANDS
```bash
# Development
composer run dev        # Starts Laravel + Vite + Queue + Logs (concurrently)

# Python Scraper
cd instagram-scraper
source venv/bin/activate
python scraper.py --target sman1baleendah

# Testing
php artisan test
```

## NOTES
- **Dependencies**: Requires both PHP 8.2+ and Python environment.
- **Asset Pipeline**: Vite handles CSS/JS compilation.
- **Queue**: Heavily relies on Queues for background tasks (`ProcessInstagramPost`, `ProcessRagDocument`). Ensure queue worker is running.
