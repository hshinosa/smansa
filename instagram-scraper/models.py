"""
Instagram Scraper Database Models
SMAN 1 Baleendah - News Feed Automation (Apify-based)

Tables use 'sc_' prefix to avoid conflicts with main Laravel application tables.
Note: Bot accounts no longer needed - using Apify API instead
"""

from sqlalchemy import Column, Integer, String, Text, Boolean, DateTime, JSON, create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from datetime import datetime
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

Base = declarative_base()


class BotAccount(Base):
    """
    Instagram Bot Accounts for scraper authentication.
    Table: sc_bot_accounts
    """
    __tablename__ = 'sc_bot_accounts'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    username = Column(String(100), unique=True, nullable=False, index=True)
    password = Column(String(255), nullable=False)
    is_active = Column(Boolean, default=True, nullable=False, index=True)
    last_used_at = Column(DateTime, nullable=True)
    notes = Column(Text, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


class RawNewsFeed(Base):
    """
    Raw scraped Instagram posts to be processed by AI News Generator.
    Table: sc_raw_news_feeds
    
    CRITICAL FIELDS:
    - post_shortcode: Unique identifier to prevent duplicate processing
    - is_processed: Flag for downstream AI processing (False=Pending, True=Done)
    """
    __tablename__ = 'sc_raw_news_feeds'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    post_shortcode = Column(String(50), unique=True, nullable=False, index=True,
                           comment='Instagram post shortcode (unique identifier)')
    source_username = Column(String(100), nullable=False, index=True,
                            comment='Instagram username of the post author')
    caption = Column(Text, nullable=True,
                    comment='Post caption/description text')
    image_paths = Column(JSON, nullable=True,
                        comment='Array of local file paths to downloaded images')
    likes_count = Column(Integer, nullable=True,
                        comment='Number of likes at scraping time')
    comments_count = Column(Integer, nullable=True,
                           comment='Number of comments at scraping time')
    post_date = Column(DateTime, nullable=True,
                      comment='Original post date from Instagram')
    scraped_at = Column(DateTime, default=datetime.utcnow, nullable=False,
                       comment='When this post was scraped')
    is_processed = Column(Boolean, default=False, nullable=False, index=True,
                         comment='CRITICAL: Has AI News Generator processed this? False=New, True=Done')
    processed_at = Column(DateTime, nullable=True,
                         comment='When AI processing completed')
    error_message = Column(Text, nullable=True,
                          comment='Error message if scraping failed')

    def __repr__(self):
        status = "✓ Processed" if self.is_processed else "⏳ Pending"
        return f"<RawNewsFeed(shortcode='{self.post_shortcode}', source='{self.source_username}', status='{status}')>"


# Database connection helper
def get_database_url():
    """Get PostgreSQL connection URL from environment variables"""
    db_user = os.getenv('DB_USERNAME', 'sman1_user')
    db_pass = os.getenv('DB_PASSWORD', 'sman1_password_2024')
    db_host = os.getenv('DB_HOST', '127.0.0.1')
    db_port = os.getenv('DB_PORT', '5433')
    db_name = os.getenv('DB_DATABASE', 'sman1_baleendah')
    
    return f"postgresql://{db_user}:{db_pass}@{db_host}:{db_port}/{db_name}"


def create_db_engine():
    """Create SQLAlchemy engine"""
    database_url = get_database_url()
    engine = create_engine(database_url, echo=False)
    return engine


def get_session():
    """Get a new database session"""
    engine = create_db_engine()
    Session = sessionmaker(bind=engine)
    return Session()


if __name__ == "__main__":
    # Test connection
    try:
        engine = create_db_engine()
        print(f"✅ Database connection successful!")
        print(f"   URL: {get_database_url().replace(os.getenv('DB_PASSWORD', ''), '***')}")
    except Exception as e:
        print(f"❌ Database connection failed: {e}")
