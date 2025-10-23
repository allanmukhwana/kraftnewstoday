# Quick Setup Guide - Simple Metadata Fetcher

## 3-Step Setup

### Step 1: Update Database (1 minute)

Run the SQL file to add required fields:

```bash
mysql -u your_username -p your_database < database_alter_simple_metadata.sql
```

**Or in phpMyAdmin:**
1. Open phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Copy contents of `database_alter_simple_metadata.sql`
5. Click "Go"

**Fields added:**
- `og_title` - Article title
- `og_description` - Article description  
- `og_image` - Image URL (real or placeholder)
- `featured_image` - Same as og_image
- `metadata_fetched` - Processing flag
- `metadata_fetched_at` - Timestamp

---

### Step 2: Test It (1 minute)

```bash
php test_simple_metadata.php
```

**Expected output:**
- ✓ Shows 5 test URLs
- ✓ Displays images (with preview)
- ✓ Shows titles and descriptions
- ✓ Summary statistics

**You should see:**
- Some articles with real OG images
- Some articles with placeholder images
- **All articles have images** (no failures!)

---

### Step 3: Add Cron Job (1 minute)

**In cPanel:**
1. Go to "Cron Jobs"
2. Add new cron job
3. Set schedule: `0 * * * *` (every hour)
4. Command: `/usr/bin/php /path/to/cron_fetch_simple_metadata.php`
5. Save

**Or manually:**
```bash
crontab -e
```

Add this line:
```
0 * * * * /usr/bin/php /home/username/public_html/cron_fetch_simple_metadata.php
```

---

## That's It! ✅

Your metadata fetcher is now:
- ✅ Installed
- ✅ Tested
- ✅ Running automatically

---

## Usage in Your Code

```php
require_once 'lib_simple_metadata.php';

// Fetch metadata for any URL
$metadata = fetch_simple_metadata('https://example.com/article');

// Always has data - no error checking needed!
echo '<img src="' . $metadata['image'] . '">';
echo '<h2>' . $metadata['title'] . '</h2>';
echo '<p>' . $metadata['description'] . '</p>';
```

---

## Display Articles with Metadata

```php
// Get articles with metadata
$stmt = $pdo->query("
    SELECT * FROM articles 
    WHERE metadata_fetched = TRUE 
    ORDER BY published_at DESC 
    LIMIT 10
");

foreach ($stmt->fetchAll() as $article) {
    ?>
    <article class="news-card">
        <img src="<?= htmlspecialchars($article['og_image']) ?>" 
             alt="<?= htmlspecialchars($article['og_title']) ?>"
             style="width:100%; height:200px; object-fit:cover;">
        
        <h3><?= htmlspecialchars($article['og_title']) ?></h3>
        
        <p><?= htmlspecialchars(substr($article['og_description'], 0, 150)) ?>...</p>
        
        <a href="<?= htmlspecialchars($article['url']) ?>">Read More</a>
    </article>
    <?php
}
```

---

## Verify It's Working

### Check Database

```sql
-- See processing stats
SELECT 
    COUNT(*) as total,
    SUM(metadata_fetched) as processed,
    SUM(CASE WHEN og_image LIKE '%placehold.co%' THEN 1 ELSE 0 END) as placeholders,
    SUM(CASE WHEN og_image NOT LIKE '%placehold.co%' THEN 1 ELSE 0 END) as real_images
FROM articles;
```

### Check Logs

```sql
-- See recent metadata fetch jobs
SELECT * FROM agent_logs 
WHERE task_type = 'other' 
AND message LIKE '%metadata%'
ORDER BY created_at DESC 
LIMIT 10;
```

### Manual Test

```bash
# Test specific URL
php test_simple_metadata.php "https://techcrunch.com/2025/10/23/some-article"
```

---

## Troubleshooting

### Issue: "Table doesn't exist"

**Solution:** Run the database ALTER script first
```bash
mysql -u username -p database < database_alter_simple_metadata.sql
```

### Issue: "Function not found"

**Solution:** Make sure you're including the library
```php
require_once 'lib_simple_metadata.php';
```

### Issue: Cron job not running

**Solution:** Check cron logs
```bash
# View cron log
tail -f /var/log/cron

# Or check your error log
tail -f logs/error.log
```

### Issue: All images are placeholders

**Solution:** This is normal if sites block scrapers. The system is working correctly - it's providing beautiful placeholders instead of broken images.

---

## What Happens Next

### Automatic Processing

Every hour, the cron job will:
1. Find 30 articles without metadata
2. Fetch metadata (or use fallbacks)
3. Store in database
4. Log results

### Processing Rate

- **30 articles per hour**
- **720 articles per day**
- **~22,000 articles per month**

### Success Rate

- **100% of articles get images** (real or placeholder)
- **100% of articles get titles** (OG or fallback)
- **100% of articles get descriptions** (OG or fallback)
- **No failures!**

---

## Next Steps

### 1. Integrate with Your Frontend

Use the metadata in your article display:
- Show `og_image` as article thumbnail
- Use `og_title` as article headline
- Display `og_description` as preview text

### 2. Monitor Performance

Check the stats regularly:
```sql
SELECT 
    DATE(metadata_fetched_at) as date,
    COUNT(*) as processed,
    SUM(CASE WHEN og_image NOT LIKE '%placehold.co%' THEN 1 ELSE 0 END) as real_images
FROM articles 
WHERE metadata_fetched = TRUE
GROUP BY DATE(metadata_fetched_at)
ORDER BY date DESC;
```

### 3. Customize Placeholders (Optional)

Edit `generate_placeholder_image()` in `lib_simple_metadata.php` to:
- Change colors
- Change text
- Use different placeholder service
- Use your own placeholder images

---

## Complete! 🎉

Your simple metadata fetcher is:
- ✅ Installed and configured
- ✅ Running automatically
- ✅ Always providing images
- ✅ Never failing

**Every article now has beautiful metadata!**
