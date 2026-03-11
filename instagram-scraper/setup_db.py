"""
Database Setup Script
SMAN 1 Baleendah - Instagram Scraper (Apify-based)

This script:
1. Creates scraper table (sc_raw_news_feeds)
2. Note: Using Apify API - token stored in Laravel database (settings table)
3. Logs stored in sc_scraper_logs table
"""

from models import Base, RawNewsFeed, create_db_engine, get_session
from sqlalchemy import inspect


def setup_database():
    """
    Initialize database tables
    """
    print("╔══════════════════════════════════════════════════════════════════════════════╗")
    print("║          Instagram Scraper Database Setup - SMAN 1 Baleendah                ║")
    print("║                        (Apify API Integration)                               ║")
    print("╚══════════════════════════════════════════════════════════════════════════════╝\n")
    
    try:
        # Step 1: Create engine and connect
        print("📡 Connecting to PostgreSQL database...")
        engine = create_db_engine()
        
        # Test connection
        with engine.connect() as conn:
            print("✅ Database connection successful!\n")
        
        # Step 2: Create tables
        print("📦 Creating scraper table...")
        Base.metadata.create_all(engine)
        
        inspector = inspect(engine)
        existing_tables = inspector.get_table_names()
        
        if 'sc_raw_news_feeds' in existing_tables:
            print("   ✓ Table 'sc_raw_news_feeds' created/verified")
        
        print()
        
        # Step 3: Check raw news feeds
        session = get_session()
        feed_count = session.query(RawNewsFeed).count()
        pending_count = session.query(RawNewsFeed).filter_by(is_processed=False).count()
        
        print("📰 News feeds status:")
        print(f"   Total scraped: {feed_count}")
        print(f"   Pending AI processing: {pending_count}")
        print()
        
        session.close()
        
        # Summary
        print("╔══════════════════════════════════════════════════════════════════════════════╗")
        print("║                         SETUP COMPLETED SUCCESSFULLY                         ║")
        print("╚══════════════════════════════════════════════════════════════════════════════╝\n")
        
        print("Next Steps:")
        print("  1️⃣  Login to Laravel Admin Panel → Instagram Settings")
        print("  2️⃣  Enter your Apify API token (stored in database)")
        print("  3️⃣  Click 'Run Scraper' button in admin panel to scrape posts")
        print("  4️⃣  Monitor logs in 'Scraper Logs' tab")
        print("  5️⃣  Or run manually: php artisan instagram:scrape sman1baleendah --max-posts=25 --apify")
        print()
        
        return True
        
    except Exception as e:
        print(f"\n❌ Setup failed: {e}")
        print("\nTroubleshooting:")
        print("  - Check .env file exists with correct DB credentials")
        print("  - Verify PostgreSQL is running (Docker: docker-compose up -d)")
        print("  - Test connection: psql -h 127.0.0.1 -p 5433 -U sman1_user -d sman1_baleendah")
        print()
        return False


def reset_database(confirm=False):
    """
    Drop all scraper tables (DESTRUCTIVE)
    Use with caution!
    """
    if not confirm:
        print("⚠️  WARNING: This will DELETE all scraper tables and data!")
        response = input("Type 'YES' to confirm: ")
        if response != 'YES':
            print("❌ Reset cancelled.")
            return
    
    print("🗑️  Dropping scraper tables...")
    engine = create_db_engine()
    Base.metadata.drop_all(engine)
    print("✅ Tables dropped successfully.")
    print("   Run setup again to recreate: python setup_db.py")


if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == '--reset':
        reset_database()
    else:
        setup_database()
