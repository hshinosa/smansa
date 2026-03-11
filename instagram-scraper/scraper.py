"""
Instagram Scraper with Anti-Ban Session Management
SMAN 1 Baleendah - News Feed Automation

Features:
- Session-based authentication (persistent login)
- Duplicate detection (via post_shortcode)
- Random delays for human-like behavior
- Error handling with account deactivation
- Graceful rate limit handling
"""

import instaloader
import time
import random
import argparse
import os
import sys
from datetime import datetime
from pathlib import Path
from models import BotAccount, RawNewsFeed, get_session
from sqlalchemy.exc import IntegrityError

# Fix Unicode encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')


class InstagramScraper:
    def __init__(self):
        self.L = None
        self.bot_account = None
        self.session = get_session()
        self.download_dir = Path("./downloads")
        self.download_dir.mkdir(exist_ok=True)
        
    def get_active_bot_account(self):
        """Get first active bot account from database"""
        account = self.session.query(BotAccount).filter_by(is_active=True).first()
        
        if not account:
            print("❌ No active bot accounts found in database!")
            print("   Please configure a bot account first:")
            print("   1. Run: python setup_db.py")
            print("   2. Update credentials in sc_bot_accounts table")
            return None
        
        return account
    
    def initialize_loader(self):
        """Initialize Instaloader with optimal settings"""
        self.L = instaloader.Instaloader(
            download_videos=False,           # Skip videos (faster, less bandwidth)
            download_video_thumbnails=False,
            download_geotags=False,
            download_comments=False,         # Skip comments
            save_metadata=False,             # Skip JSON metadata files
            compress_json=False,
            post_metadata_txt_pattern='',    # No metadata txt
            max_connection_attempts=3,
            request_timeout=30,
        )
        
        # Set custom User-Agent to avoid detection
        self.L.context.user_agent = (
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
            'AppleWebKit/537.36 (KHTML, like Gecko) '
            'Chrome/120.0.0.0 Safari/537.36'
        )
    
    def login_with_session(self):
        """
        Login using session file or credentials
        
        Session Flow:
        1. Check if session file exists (session-{username})
        2. If exists: Load session (no login needed)
        3. If not: Perform login and save session
        
        Returns:
            bool: True if login successful, False otherwise
        """
        self.bot_account = self.get_active_bot_account()
        if not self.bot_account:
            return False
        
        username = self.bot_account.username
        password = self.bot_account.password
        session_file = f"session-{username}"
        
        print(f"🔐 Authenticating as: {username}")
        
        try:
            # Try to load existing session
            if os.path.exists(session_file):
                print(f"   ✓ Session file found, loading...")
                self.L.load_session_from_file(username, session_file)
                print(f"   ✅ Logged in via session (no password needed)")
            else:
                print(f"   ⚠️  No session file found, performing login...")
                self.L.login(username, password)
                
                # Save session for future use
                self.L.save_session_to_file(session_file)
                print(f"   ✅ Login successful, session saved to: {session_file}")
            
            # Update last_used_at timestamp
            self.bot_account.last_used_at = datetime.utcnow()
            self.session.commit()
            
            return True
            
        except instaloader.exceptions.TwoFactorAuthRequiredException:
            print(f"   ❌ 2FA required for {username}")
            print(f"   Please disable 2FA or use app-specific password")
            self._deactivate_account("2FA required")
            return False
            
        except instaloader.exceptions.BadCredentialsException:
            print(f"   ❌ Invalid credentials for {username}")
            self._deactivate_account("Invalid credentials")
            return False
            
        except instaloader.exceptions.ConnectionException as e:
            print(f"   ❌ Connection error: {e}")
            print(f"   Possible rate limit or Instagram blocking")
            self._deactivate_account(f"Connection error: {str(e)}")
            return False
            
        except Exception as e:
            print(f"   ❌ Unexpected error during login: {e}")
            return False
    
    def _deactivate_account(self, reason):
        """Mark bot account as inactive in database"""
        if self.bot_account:
            self.bot_account.is_active = False
            self.bot_account.notes = f"Deactivated: {reason} at {datetime.utcnow()}"
            self.session.commit()
            print(f"   ⚠️  Bot account deactivated in database")
    
    def is_already_scraped(self, shortcode):
        """Check if post already exists in database"""
        exists = self.session.query(RawNewsFeed).filter_by(post_shortcode=shortcode).first()
        return exists is not None
    
    def scrape_profile(self, target_username, max_posts=50):
        """
        Scrape posts from a target Instagram profile
        
        Args:
            target_username (str): Instagram username to scrape
            max_posts (int): Maximum number of posts to scrape
        """
        print(f"\n📸 Scraping Instagram profile: @{target_username}")
        print(f"   Max posts: {max_posts}")
        print(f"   Download directory: {self.download_dir.absolute()}\n")
        
        try:
            # Load profile
            profile = instaloader.Profile.from_username(self.L.context, target_username)
            
            print(f"✓ Profile loaded:")
            print(f"  - Full Name: {profile.full_name}")
            print(f"  - Posts: {profile.mediacount}")
            print(f"  - Followers: {profile.followers}")
            print(f"  - Following: {profile.followees}\n")
            
            # Create target-specific download directory
            target_dir = self.download_dir / target_username
            target_dir.mkdir(exist_ok=True)
            
            scraped_count = 0
            skipped_count = 0
            error_count = 0
            
            # Iterate through posts
            for i, post in enumerate(profile.get_posts(), 1):
                if i > max_posts:
                    print(f"\n⏸️  Reached max posts limit ({max_posts})")
                    break
                
                shortcode = post.shortcode
                
                # Check for duplicates
                if self.is_already_scraped(shortcode):
                    print(f"[{i:3d}] ⏭️  Skipped (duplicate): {shortcode}")
                    skipped_count += 1
                    continue
                
                try:
                    print(f"[{i:3d}] 📥 Downloading: {shortcode}...")
                    
                    # Download post
                    self.L.download_post(post, target=str(target_dir))
                    
                    # Find downloaded image paths
                    image_files = list(target_dir.glob(f"*{shortcode}*.jpg")) + \
                                 list(target_dir.glob(f"*{shortcode}*.png"))
                    
                    image_paths = [str(f.relative_to(self.download_dir)) for f in image_files]
                    
                    # Save to database
                    feed = RawNewsFeed(
                        post_shortcode=shortcode,
                        source_username=target_username,
                        caption=post.caption if post.caption else '',
                        image_paths=image_paths,
                        likes_count=post.likes,
                        comments_count=post.comments,
                        post_date=post.date_utc,
                        scraped_at=datetime.utcnow(),
                        is_processed=False  # Ready for AI processing
                    )
                    
                    self.session.add(feed)
                    self.session.commit()
                    
                    print(f"      ✅ Saved to database")
                    print(f"      📷 Images: {len(image_paths)}")
                    print(f"      ❤️  Likes: {post.likes}")
                    scraped_count += 1
                    
                    # Human-like delay (10-20 seconds)
                    delay = random.randint(10, 20)
                    print(f"      😴 Sleeping {delay}s...\n")
                    time.sleep(delay)
                    
                except IntegrityError:
                    self.session.rollback()
                    print(f"      ⚠️  Database constraint error (duplicate?)")
                    skipped_count += 1
                    
                except instaloader.exceptions.QueryReturnedNotFoundException:
                    print(f"      ❌ Post not found or deleted")
                    error_count += 1
                    
                except instaloader.exceptions.ConnectionException as e:
                    print(f"      ❌ Connection error: {e}")
                    print(f"      ⏸️  Pausing for 60 seconds...")
                    time.sleep(60)
                    error_count += 1
                    
                except Exception as e:
                    print(f"      ❌ Error: {e}")
                    error_count += 1
            
            # Summary
            print("\n╔══════════════════════════════════════════════════════════════════════════════╗")
            print("║                           SCRAPING COMPLETED                                 ║")
            print("╚══════════════════════════════════════════════════════════════════════════════╝\n")
            print(f"📊 Summary:")
            print(f"   ✅ Successfully scraped: {scraped_count}")
            print(f"   ⏭️  Skipped (duplicates): {skipped_count}")
            print(f"   ❌ Errors: {error_count}")
            print(f"   📁 Download directory: {target_dir.absolute()}")
            print()
            print(f"Next Steps:")
            print(f"   1️⃣  Check scraped data: SELECT * FROM sc_raw_news_feeds WHERE is_processed=false;")
            print(f"   2️⃣  Run AI News Generator to process feeds")
            print()
            
        except instaloader.exceptions.ProfileNotExistsException:
            print(f"❌ Profile @{target_username} does not exist")
            
        except instaloader.exceptions.LoginRequiredException:
            print(f"❌ Login required (session expired?)")
            print(f"   Delete session file and try again")
            
        except Exception as e:
            print(f"❌ Fatal error: {e}")
    
    def cleanup(self):
        """Close database session"""
        self.session.close()


def main():
    parser = argparse.ArgumentParser(
        description='Instagram Scraper for SMAN 1 Baleendah News Feed',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python scraper.py --target jokowi
  python scraper.py --target sman1baleendah --max-posts 100
  
Setup:
  1. Configure bot account: python setup_db.py
  2. Update credentials in database (sc_bot_accounts table)
  3. Run scraper with target username
        """
    )
    
    parser.add_argument(
        '--target',
        type=str,
        required=True,
        help='Target Instagram username to scrape'
    )
    
    parser.add_argument(
        '--max-posts',
        type=int,
        default=50,
        help='Maximum number of posts to scrape (default: 50)'
    )
    
    args = parser.parse_args()
    
    # Initialize scraper
    scraper = InstagramScraper()
    scraper.initialize_loader()
    
    # Login
    if not scraper.login_with_session():
        print("\n❌ Login failed. Cannot proceed with scraping.")
        scraper.cleanup()
        return
    
    # Start scraping
    try:
        scraper.scrape_profile(args.target, args.max_posts)
    except KeyboardInterrupt:
        print("\n\n⚠️  Scraping interrupted by user")
    finally:
        scraper.cleanup()
        print("👋 Goodbye!")


if __name__ == "__main__":
    main()
