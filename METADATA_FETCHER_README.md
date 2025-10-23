# Article Metadata Fetcher

## Overview

Automatically fetches rich metadata from article URLs including:
- ✅ **Open Graph** metadata (og:title, og:image, og:description, etc.)
- ✅ **Meta Description** from meta tags
- ✅ **Featured Images** from article content
- ✅ **Twitter Card** metadata
- ✅ **Canonical URLs**
- ✅ **Author** and **Published Date**

---

## Database Changes

### New Fields Added to `articles` Table

Run the ALTER statements to add these fields:

```sql
-- Run this file to add all new fields
source database_alter_metadata.sql;
```

**New Fields:**
- `meta_description` - Meta description from `<meta name="description">`
- `og_title` - Open Graph title
- `og_description` - Open Graph description
- `og_image` - Open Graph image URL
- `og_type` - Open Graph type (article, website, etc.)
- `og_site_name` - Open Graph site name
- `featured_image` - Featured/hero image from article
- `twitter_card` - Twitter card type
- `twitter_title` - Twitter card title
- `twitter_description` - Twitter card description
- `twitter_image` - Twitter card image
- `canonical_url` - Canonical URL
- `metadata_fetched` - Boolean flag if metadata was fetched
- `metadata_fetched_at` - Timestamp of metadata fetch

---

## Files Created

### 1. `lib_metadata_fetcher.php`
Core library for fetching metadata from URLs.

### 2. `database_alter_metadata.sql`
ALTER statements to add new fields to the database.

### 3. `test_metadata_fetcher.php`
Test script to verify metadata fetching works.

### 4. `cron_fetch_metadata.php`
Cron job to automatically fetch metadata for articles.

---

## Usage

### Automatic (Recommended)

Set up a cron job to automatically fetch metadata:

```bash
# Run every hour
0 * * * * /usr/bin/php /path/to/cron_fetch_metadata.php
```

This will:
1. Find articles without metadata (`metadata_fetched = FALSE`)
2. Fetch metadata from their URLs
3. Store metadata in database
4. Process 20 articles per run

### Manual - Single Article

```php
require_once 'lib_metadata_fetcher.php';

$url = 'https://www.example.com/article';
$metadata = fetch_url_metadata($url);

if ($metadata['success']) {
    echo "Title: " . $metadata['og_title'] . "\n";
    echo "Image: " . $metadata['featured_image'] . "\n";
    echo "Description: " . $metadata['meta_description'] . "\n";
}
```

### Manual - Batch Processing

```php
require_once 'lib_metadata_fetcher.php';

// Process 50 articles without metadata
$processed = batch_fetch_article_metadata(50);
echo "Processed $processed articles\n";
```

### Manual - Store for Specific Article

```php
require_once 'lib_metadata_fetcher.php';

$article_id = 123;
$url = 'https://www.example.com/article';

if (fetch_and_store_article_metadata($article_id, $url)) {
    echo "Metadata stored successfully\n";
}
```

---

## Testing

### Test Metadata Fetching

```bash
php test_metadata_fetcher.php
```

**Or test specific URL:**
```bash
php test_metadata_fetcher.php "https://www.washingtonpost.com/technology/"
```

**Expected Output:**
```
=== Basic Metadata ===
Title: Technology News - The Washington Post
Meta Description: Breaking technology news and analysis...
Author: Washington Post Staff
Canonical URL: https://www.washingtonpost.com/technology/

=== Open Graph Metadata ===
OG Title: Technology News
OG Description: Latest technology news and updates...
OG Image: https://www.washingtonpost.com/wp-apps/imrs.php?...
OG Type: website
OG Site Name: The Washington Post

=== Twitter Card Metadata ===
Twitter Card: summary_large_image
Twitter Title: Technology News
Twitter Image: https://www.washingtonpost.com/...

=== Featured Image ===
Featured Image: https://www.washingtonpost.com/...

=== Summary ===
Fields found: 12 / 15
Completeness: 80.0%
```

---

## API Reference

### `fetch_url_metadata($url, $timeout = 10)`

Fetch all metadata from a URL.

**Parameters:**
- `$url` (string) - URL to fetch metadata from
- `$timeout` (int) - Timeout in seconds (default: 10)

**Returns:**
```php
[
    'success' => true,
    'url' => 'https://example.com/article',
    'meta_description' => 'Article description...',
    'og_title' => 'Article Title',
    'og_description' => 'OG description...',
    'og_image' => 'https://example.com/image.jpg',
    'og_type' => 'article',
    'og_site_name' => 'Example Site',
    'twitter_card' => 'summary_large_image',
    'twitter_title' => 'Article Title',
    'twitter_description' => 'Twitter description...',
    'twitter_image' => 'https://example.com/image.jpg',
    'canonical_url' => 'https://example.com/article',
    'featured_image' => 'https://example.com/featured.jpg',
    'title' => 'Page Title',
    'author' => 'John Doe',
    'published_date' => '2025-10-23T15:30:00Z'
]
```

---

### `fetch_and_store_article_metadata($article_id, $url)`

Fetch metadata and store it in the database.

**Parameters:**
- `$article_id` (int) - Article ID
- `$url` (string) - Article URL

**Returns:**
- `bool` - Success status

**Example:**
```php
if (fetch_and_store_article_metadata(123, $url)) {
    echo "Metadata stored!";
}
```

---

### `batch_fetch_article_metadata($limit = 10)`

Batch fetch metadata for multiple articles.

**Parameters:**
- `$limit` (int) - Number of articles to process (default: 10)

**Returns:**
- `int` - Number of articles processed

**Example:**
```php
$processed = batch_fetch_article_metadata(50);
echo "Processed $processed articles";
```

---

## How It Works

### Step 1: Fetch HTML

```php
$html = fetch_url_html($url, $timeout);
```

Uses cURL to fetch the page HTML with proper headers.

### Step 2: Extract Metadata

**Open Graph Tags:**
```html
<meta property="og:title" content="Article Title">
<meta property="og:image" content="https://...">
<meta property="og:description" content="...">
```

**Meta Description:**
```html
<meta name="description" content="Page description">
```

**Featured Image:**
```html
<img class="featured-image" src="https://...">
<img class="hero-image" src="https://...">
<article><img src="https://..."></article>
```

**Twitter Cards:**
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="https://...">
```

### Step 3: Store in Database

```php
UPDATE articles SET
    meta_description = ?,
    og_title = ?,
    og_image = ?,
    featured_image = ?,
    metadata_fetched = TRUE
WHERE id = ?
```

---

## Integration with News Scraper

The metadata fetcher works alongside the news scraper:

**Workflow:**
1. News scraper fetches articles from Google News
2. Articles stored in database with `metadata_fetched = FALSE`
3. Cron job runs hourly
4. Fetches metadata for articles without it
5. Updates articles with rich metadata

**Query Articles with Metadata:**
```php
$stmt = $pdo->query("
    SELECT * FROM articles 
    WHERE metadata_fetched = TRUE 
    AND featured_image IS NOT NULL
    ORDER BY published_at DESC
    LIMIT 10
");
```

---

## Performance

### Fetching Speed

- **Average:** 1-3 seconds per URL
- **Timeout:** 10 seconds (configurable)
- **Batch processing:** Includes 0.5s delay between requests

### Optimization Tips

1. **Increase batch size** for faster processing:
   ```php
   batch_fetch_article_metadata(50);
   ```

2. **Run more frequently** (every 30 minutes):
   ```bash
   */30 * * * * php cron_fetch_metadata.php
   ```

3. **Increase timeout** for slow sites:
   ```php
   fetch_url_metadata($url, 20);
   ```

---

## Troubleshooting

### Issue: No Metadata Found

**Possible Causes:**
1. Website blocks scrapers
2. No Open Graph tags on page
3. JavaScript-rendered content
4. Timeout too short

**Solutions:**
```php
// Increase timeout
$metadata = fetch_url_metadata($url, 20);

// Check if URL is accessible
$html = fetch_url_html($url);
if (!$html) {
    echo "Cannot fetch URL";
}
```

### Issue: Featured Image Not Found

**Reason:** Different sites use different HTML structures

**Solution:**
The library tries multiple patterns:
- Images with class `featured`, `hero`, `main`
- First image in `<article>` tag
- First image in `<figure>` tag
- Falls back to OG image or Twitter image

### Issue: Slow Performance

**Solution:**
```php
// Reduce timeout
fetch_url_metadata($url, 5);

// Process fewer articles per run
batch_fetch_article_metadata(10);
```

---

## Example Output

### Article with Full Metadata

```php
[
    'id' => 123,
    'title' => 'AI Breakthrough in Healthcare',
    'url' => 'https://techcrunch.com/2025/10/23/ai-healthcare',
    'meta_description' => 'New AI system improves medical diagnosis...',
    'og_title' => 'AI Breakthrough in Healthcare | TechCrunch',
    'og_description' => 'Researchers have developed...',
    'og_image' => 'https://techcrunch.com/wp-content/uploads/2025/10/ai.jpg',
    'og_type' => 'article',
    'og_site_name' => 'TechCrunch',
    'featured_image' => 'https://techcrunch.com/wp-content/uploads/2025/10/ai.jpg',
    'twitter_card' => 'summary_large_image',
    'canonical_url' => 'https://techcrunch.com/2025/10/23/ai-healthcare',
    'metadata_fetched' => true,
    'metadata_fetched_at' => '2025-10-23 15:30:00'
]
```

---

## Cron Job Setup

### Add to cPanel Cron Jobs

**Fetch metadata every hour:**
```
0 * * * * /usr/bin/php /home/username/public_html/cron_fetch_metadata.php
```

**Or every 30 minutes:**
```
*/30 * * * * /usr/bin/php /home/username/public_html/cron_fetch_metadata.php
```

### Recommended Schedule

```bash
# Scrape news every 6 hours
0 */6 * * * php cron_scrape.php

# Fetch metadata every hour
0 * * * * php cron_fetch_metadata.php

# Analyze articles every hour
0 * * * * php cron_analyze.php

# Send digests twice daily
0 7,19 * * * php cron_digest.php
```

---

## Summary

✅ **Automatic metadata fetching** from article URLs  
✅ **Open Graph** tags extracted  
✅ **Featured images** detected  
✅ **Meta descriptions** captured  
✅ **Twitter Card** metadata included  
✅ **Database schema** updated  
✅ **Cron job** for automation  
✅ **Production ready**  

**Your articles now have rich metadata including featured images, descriptions, and Open Graph tags!**
