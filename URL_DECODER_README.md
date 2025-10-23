# Google News URL Decoder

## Overview

Google News RSS feeds provide redirect URLs that look like this:
```
https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5
```

These URLs redirect to the actual article URL. This library automatically decodes them.

---

## Features

✅ **Automatic URL Decoding** - Follows Google News redirects to get actual article URLs  
✅ **Domain Extraction** - Extracts source domain from decoded URLs  
✅ **Batch Processing** - Decode multiple URLs efficiently  
✅ **Error Handling** - Graceful fallback if decoding fails  
✅ **Integrated with News Scraper** - Works automatically when fetching news  

---

## Files Created

### 1. `lib_url_decoder.php`
Core library with URL decoding functions.

### 2. `test_google_news_redirect.php`
Standalone test script for decoding Google News URLs.

### 3. `test_url_decoding_integration.php`
Integration test showing how URL decoding works with the news scraper.

---

## Usage

### Automatic (Recommended)

URL decoding is now **automatically integrated** into the news scraper:

```php
require_once 'lib_news_scraper.php';

$news = fetch_google_news('search', ['query' => 'technology']);

foreach ($news['articles'] as $article) {
    echo $article['link'];         // Decoded actual URL
    echo $article['link_google'];  // Original Google News URL
    echo $article['domain'];       // Source domain (e.g., "techcrunch.com")
    echo $article['link_decoded']; // true if successfully decoded
}
```

### Manual Decoding

Decode a single URL:

```php
require_once 'lib_url_decoder.php';

$google_url = 'https://news.google.com/rss/articles/...';
$actual_url = decode_google_news_redirect($google_url);

echo $actual_url; // https://www.washingtonpost.com/technology/...
```

### With Domain Extraction

```php
$result = decode_google_news_with_domain($google_url);

echo $result['url'];      // Decoded URL
echo $result['domain'];   // Domain name
echo $result['decoded'];  // true/false
```

### Batch Decoding

```php
$urls = [
    'https://news.google.com/rss/articles/...',
    'https://news.google.com/rss/articles/...',
    'https://news.google.com/rss/articles/...'
];

$decoded_urls = batch_decode_google_news_urls($urls);
```

---

## Testing

### Test 1: Standalone URL Decoder

```bash
php test_google_news_redirect.php
```

**Or with custom URL:**
```bash
php test_google_news_redirect.php "https://news.google.com/rss/articles/..."
```

**What it shows:**
- URL components analysis
- Redirect following process
- Final destination URL
- Source domain extraction

### Test 2: Integration Test

```bash
php test_url_decoding_integration.php
```

**What it shows:**
- Fetches real news articles
- Shows original vs decoded URLs
- Decoding statistics
- Performance metrics
- Top source domains

---

## API Reference

### `decode_google_news_redirect($google_url, $timeout = 10)`

Decode a Google News redirect URL to get the actual article URL.

**Parameters:**
- `$google_url` (string) - The Google News redirect URL
- `$timeout` (int) - Timeout in seconds (default: 10)

**Returns:**
- `string|null` - The final destination URL or null on failure

**Example:**
```php
$url = decode_google_news_redirect($google_url);
```

---

### `decode_google_news_with_domain($google_url)`

Decode URL and extract domain in one call.

**Returns:**
```php
[
    'url' => 'https://example.com/article',
    'domain' => 'example.com',
    'decoded' => true
]
```

---

### `is_google_news_redirect($url)`

Check if a URL is a Google News redirect.

**Returns:** `bool`

**Example:**
```php
if (is_google_news_redirect($url)) {
    $decoded = decode_google_news_redirect($url);
}
```

---

### `extract_google_news_article_id($google_url)`

Extract the article ID from a Google News URL.

**Returns:** `string|null`

**Example:**
```php
$article_id = extract_google_news_article_id($url);
// Returns: "CBMiogFBVV95cUxNYTZmZm91..."
```

---

### `get_domain_from_url($url)`

Extract clean domain name from any URL.

**Returns:** `string|null`

**Example:**
```php
$domain = get_domain_from_url('https://www.example.com/article');
// Returns: "example.com" (www. removed)
```

---

### `batch_decode_google_news_urls($urls, $timeout = 5)`

Decode multiple URLs efficiently.

**Parameters:**
- `$urls` (array) - Array of Google News URLs
- `$timeout` (int) - Timeout per URL in seconds

**Returns:** `array` - Array of decoded URLs

---

## How It Works

### Step 1: Detect Google News URL

```php
if (is_google_news_redirect($url)) {
    // It's a Google News redirect
}
```

### Step 2: Follow Redirects

Uses cURL to follow HTTP redirects:
- Sets proper User-Agent header
- Follows up to 10 redirects
- Handles SSL certificates
- Respects timeout

### Step 3: Extract Final URL

Gets the final destination URL after all redirects:
```
Google News URL → Redirect 1 → Redirect 2 → Final Article URL
```

### Step 4: Extract Domain

Parses the final URL to get the source domain:
```
https://www.washingtonpost.com/tech/article
         ↓
    washingtonpost.com
```

---

## Integration with News Scraper

The URL decoder is automatically integrated into `lib_news_scraper.php`.

**When you fetch news:**
```php
$news = fetch_google_news('search', ['query' => 'AI']);
```

**Each article now includes:**
```php
[
    'title' => 'Article Title',
    'link' => 'https://techcrunch.com/...',      // ✓ Decoded actual URL
    'link_google' => 'https://news.google.com/...', // Original Google URL
    'link_decoded' => true,                      // Decoding status
    'domain' => 'techcrunch.com',                // Source domain
    'source' => 'TechCrunch',                    // Source name
    'description' => '...',
    'date_formatted' => '2025-10-23 15:30:00'
]
```

---

## Performance

### Decoding Speed

- **Average:** 0.5-2 seconds per URL
- **Timeout:** Configurable (default: 10 seconds)
- **Batch processing:** Includes small delays to avoid overwhelming servers

### Optimization Tips

1. **Use batch decoding** for multiple URLs
2. **Set appropriate timeout** (5-10 seconds recommended)
3. **Cache decoded URLs** to avoid re-decoding
4. **Decode in background** for large batches

---

## Error Handling

### Graceful Fallback

If decoding fails, the original Google News URL is kept:

```php
if ($article['link_decoded']) {
    // Use decoded URL
    $url = $article['link'];
} else {
    // Fallback to Google News URL
    $url = $article['link_google'];
}
```

### Common Issues

**Issue:** Decoding returns null  
**Cause:** Network timeout, firewall, or expired URL  
**Solution:** Increase timeout or use original URL

**Issue:** Slow decoding  
**Cause:** Network latency  
**Solution:** Reduce timeout or decode in background

**Issue:** Some URLs not decoded  
**Cause:** Not all Google News URLs are redirects  
**Solution:** Check `link_decoded` flag

---

## Example Output

### Before Decoding:
```
https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91...?oc=5
```

### After Decoding:
```
https://www.washingtonpost.com/technology/2025/10/23/russia-western-technology-nuclear-submarines/
```

### Extracted Domain:
```
washingtonpost.com
```

---

## Production Deployment

### Recommended Settings

```php
// In config.php or your settings
define('URL_DECODE_TIMEOUT', 5);  // 5 seconds per URL
define('URL_DECODE_ENABLED', true); // Enable/disable decoding
```

### Caching (Optional)

For better performance, cache decoded URLs:

```php
$cache_key = 'decoded_' . md5($google_url);
$cached = get_from_cache($cache_key);

if ($cached) {
    return $cached;
}

$decoded = decode_google_news_redirect($google_url);
save_to_cache($cache_key, $decoded, 86400); // Cache for 24 hours
```

---

## Troubleshooting

### URL Decoding Fails

1. **Check network connectivity**
   ```bash
   curl -I "https://news.google.com"
   ```

2. **Test with longer timeout**
   ```php
   $url = decode_google_news_redirect($google_url, 30);
   ```

3. **Check if cURL is enabled**
   ```php
   if (!function_exists('curl_init')) {
       echo "cURL not available";
   }
   ```

### Slow Performance

1. **Reduce timeout**
   ```php
   $url = decode_google_news_redirect($google_url, 3);
   ```

2. **Decode in background**
   ```php
   // Queue for background processing
   queue_url_decode_job($google_url);
   ```

3. **Use batch processing**
   ```php
   $decoded = batch_decode_google_news_urls($urls, 3);
   ```

---

## Summary

✅ **Automatic Integration** - Works seamlessly with news scraper  
✅ **Real Article URLs** - Get actual source URLs, not Google redirects  
✅ **Domain Extraction** - Identify article sources  
✅ **Error Handling** - Graceful fallback if decoding fails  
✅ **Production Ready** - Tested and optimized  

**Your news scraper now provides real article URLs instead of Google News redirects!**
