# INSTAGRAM SCRAPER KNOWLEDGE BASE

**Generated:** 2026-02-12
**Stack:** Python 3.9+, SQLAlchemy, Instaloader, PostgreSQL

## OVERVIEW
Standalone Python subsystem for scraping Instagram content to aggregate news feeds for the main school website. Runs independently but shares the PostgreSQL database.

## STRUCTURE
```
instagram-scraper/
├── scraper.py          # Main entry point & scraping logic
├── models.py           # SQLAlchemy ORM definitions (mirrors DB schema)
├── setup_db.py         # Database initialization & table creation
├── requirements.txt    # Python dependencies
└── downloads/          # Temporary storage for scraped media
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| **Scraping Logic** | `scraper.py` | Core Instaloader implementation |
| **Database Schema** | `models.py` | Defines `sc_bot_accounts`, `sc_raw_news_feeds` |
| **Configuration** | `.env` | Database credentials (same as Laravel) |
| **Session Mgmt** | `session-*` | Instaloader session files (gitignored) |

## KEY CONVENTIONS
- **Prefix Naming**: All database tables MUST use `sc_` prefix to avoid collisions with Laravel tables.
- **Session Reuse**: Always load/save session files to avoid Instagram bans.
- **Rate Limiting**: Random delays (10-20s) are mandatory between requests.
- **Isolation**: Does NOT import any Laravel code. Interacts only via shared Database.

## COMMANDS
```bash
# Setup
pip install -r requirements.txt
python setup_db.py      # Initialize tables

# Usage
python scraper.py --target sman1baleendah --max-posts 50
```

## NOTES
- **Anti-Ban**: Critical. Do not remove delay logic. Rotate bot accounts if needed.
- **Workflow**: Scraper inserts raw data -> Laravel Cron job processes it via AI -> Published as Post.
