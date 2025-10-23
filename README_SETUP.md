# Kraft News Today - Setup Instructions

## Quick Start Guide

### 1. Database Setup

```bash
# Create database and import schema
mysql -u root -p < database.sql
```

Or manually:
1. Open phpMyAdmin or MySQL client
2. Create database: `kraftnews_db`
3. Import `database.sql`

### 2. Configuration

Edit `config.php` and add your API keys:

```php
// Amazon Bedrock (API Key) - Optional for AI features
define('BEDROCK_API_KEY', 'your-bedrock-api-key');

// Brevo Email - Optional for email notifications
define('BREVO_API_KEY', 'your-brevo-api-key');

// Stripe Payment - Optional for premium subscriptions
define('STRIPE_PUBLISHABLE_KEY', 'your-stripe-publishable-key');
define('STRIPE_SECRET_KEY', 'your-stripe-secret-key');
define('STRIPE_PRICE_ID', 'your-stripe-price-id');

// News Fetching - Uses Google News RSS (No API Key Required!)
```

### 3. Start PHP Server

```bash
cd c:\kce\kraftnews\kraftnewstoday
php -S localhost:8000
```

### 4. Access Application

Open browser: `http://localhost:8000`

**Default Admin Login:**
- Email: admin@kraftnews.com
- Password: admin123 (CHANGE THIS!)

### 5. Test the Application

1. **Register New User**: Go to Sign Up page
2. **Select Industries**: Choose industries to track
3. **Trigger Scraping**: Visit admin panel and click "Trigger Scraping"
4. **Trigger Analysis**: Click "Trigger Analysis" (for premium users)
5. **View Dashboard**: See articles in your feed

## Manual Testing

### Scrape Articles
```bash
php cron_scrape.php
```

### Analyze Articles
```bash
php cron_analyze.php
```

### Send Digests
```bash
php cron_digest.php
```

## Production Setup

### Cron Jobs (Linux/Mac)

```bash
# Edit crontab
crontab -e

# Add these lines:
0 */6 * * * cd /path/to/kraftnews && php cron_scrape.php
0 * * * * cd /path/to/kraftnews && php cron_analyze.php
0 7,19 * * * cd /path/to/kraftnews && php cron_digest.php
```

### Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Set trigger (e.g., every 6 hours)
4. Action: Start a program
5. Program: `php.exe`
6. Arguments: `C:\path\to\kraftnewstoday\cron_scrape.php`

## API Keys Setup

### Amazon Bedrock (API Key Method - Recommended for Development)
1. Sign in to AWS Console
2. Go to Amazon Bedrock console: https://console.aws.amazon.com/bedrock
3. In left navigation, select **API keys**
4. Click **Generate long-term API keys**
5. Select **30 days** expiration
6. Click **Generate** and copy the API key
7. Add to config.php as `BEDROCK_API_KEY`

**Note:** API keys expire in 30 days. For production, use IAM roles instead.

### Brevo (Email)
1. Sign up at sendinblue.com
2. Go to SMTP & API
3. Create API key
4. Add to config.php

### Stripe (Payments)
1. Sign up at stripe.com
2. Get test API keys from Dashboard
3. Create product and price
4. Add keys to config.php

### News API
1. Sign up at newsapi.org
2. Get free API key
3. Add to config.php

## File Structure

```
kraftnewstoday/
├── config.php              # Configuration
├── database.sql            # Database schema
├── header.php              # Reusable header
├── footer.php              # Reusable footer
├── index.php               # Landing page
├── auth_login.php          # Login
├── auth_register.php       # Registration
├── auth_logout.php         # Logout
├── dashboard.php           # User dashboard
├── industries.php          # Industry management
├── profile.php             # User profile
├── payment.php             # Stripe payment
├── payment_process.php     # Payment backend
├── analysis_view.php       # AI analysis view
├── agent_bedrock.php       # Amazon Bedrock integration
├── agent_scraper.php       # News scraping
├── agent_analyzer.php      # Analysis orchestrator
├── email_sender.php        # Email delivery
├── cron_scrape.php         # Scraping cron job
├── cron_analyze.php        # Analysis cron job
├── cron_digest.php         # Digest cron job
├── admin_panel.php         # Admin testing panel
└── logs/                   # Log files (auto-created)
```

## Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in config.php
- Ensure database exists

### Articles Not Appearing
- Run `php cron_scrape.php` manually
- Check logs/agent.log
- Verify industries are selected

### Analysis Not Working
- Check AWS Bedrock credentials
- Run `php cron_analyze.php` manually
- Check logs/error.log

### Emails Not Sending
- Verify Brevo API key
- Check logs/email.log
- Test mode logs instead of sending

## Security Notes

1. Change default admin password immediately
2. Use environment variables for API keys in production
3. Enable HTTPS in production
4. Set `APP_ENV` to 'production' in config.php
5. Disable error display in production

## Support

For issues or questions, check:
- logs/error.log
- logs/agent.log
- logs/email.log
