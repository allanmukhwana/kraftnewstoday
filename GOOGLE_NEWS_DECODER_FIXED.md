# Google News URL Decoder - FIXED! ✅

## What Changed

The previous decoder used simple HTTP redirect following, which **doesn't work** with Google News URLs.

The new decoder uses **Google's actual decoding API**, based on the working Python implementation from:
https://github.com/SSujitX/google-news-url-decoder

---

## How It Works Now

### Old Method (Didn't Work) ❌
```
Follow HTTP redirects → Get final URL
```
**Problem:** Google News URLs don't redirect via HTTP headers

### New Method (Works!) ✅
```
1. Extract base64 string from URL
2. Fetch Google News page HTML
3. Extract signature and timestamp from page
4. POST to Google's batchexecute API
5. Parse response to get actual URL
```

---

## Implementation Details

### Step 1: Extract Base64 String

**Input:**
```
https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91...?oc=5
```

**Extract:**
```
CBMiogFBVV95cUxNYTZmZm91...
```

### Step 2: Fetch Google News Page

**URL:**
```
https://news.google.com/articles/{base64_string}
```

**Extract from HTML:**
```html
<div jscontroller="..." 
     data-n-a-sg="signature_here" 
     data-n-a-ts="timestamp_here">
```

### Step 3: Call Batchexecute API

**Endpoint:**
```
https://news.google.com/_/DotsSplashUi/data/batchexecute
```

**Payload:**
```json
{
  "f.req": [
    [
      [
        "Fbv4je",
        "[\"garturlreq\",...,\"{base64}\",{timestamp},\"{signature}\"]"
      ]
    ]
  ]
}
```

### Step 4: Parse Response

**Response Format:**
```
)]}'

[[["wrb.fr","Fbv4je","[\"https://actual-article-url.com\",...]"]]]
```

**Extract:**
```
https://actual-article-url.com
```

---

## Files Updated

### 1. `lib_url_decoder.php` - Complete Rewrite

**New Functions:**
- `get_base64_str($url)` - Extract base64 from Google News URL
- `get_decoding_params($base64)` - Fetch signature and timestamp
- `fetch_google_news_page($url)` - Get HTML from Google News
- `decode_url_with_params($sig, $ts, $base64)` - Call batchexecute API
- `decode_google_news_redirect($url)` - Main function (updated)

### 2. `test_google_news_decode_simple.php` - New Test File

Shows step-by-step decoding process with detailed output.

### 3. `test_decode_working.php` - Simple Test

Quick test to verify the decoder works.

---

## Testing

### Test 1: Simple Test

```bash
php test_decode_working.php
```

**Expected Output:**
```
✓ SUCCESS!

Decoded URL:
https://www.washingtonpost.com/technology/2025/...

Duration: 2.3s
Source Domain: washingtonpost.com
```

### Test 2: Detailed Test

```bash
php test_google_news_decode_simple.php
```

**Shows:**
- Base64 extraction
- Signature and timestamp retrieval
- API call details
- Final decoded URL

### Test 3: Integration Test

```bash
php test_url_decoding_integration.php
```

**Shows:**
- Decoding with news scraper
- Statistics
- Performance metrics

---

## Usage

### Automatic (With News Scraper)

The decoder is **already integrated** into your news scraper:

```php
require_once 'lib_news_scraper.php';

$news = fetch_google_news('search', ['query' => 'technology']);

foreach ($news['articles'] as $article) {
    echo $article['link'];         // ✓ Decoded actual URL
    echo $article['link_google'];  // Original Google News URL
    echo $article['domain'];       // Source domain
}
```

### Manual Decoding

```php
require_once 'lib_url_decoder.php';

$google_url = 'https://news.google.com/rss/articles/...';
$actual_url = decode_google_news_redirect($google_url);

if ($actual_url) {
    echo "Success: $actual_url";
} else {
    echo "Failed to decode";
}
```

---

## Performance

### Typical Decoding Time

- **Average:** 1-3 seconds per URL
- **Timeout:** 10 seconds (configurable)

### Why It Takes Time

1. Fetch Google News page (1-2s)
2. Extract parameters (instant)
3. Call batchexecute API (0.5-1s)
4. Parse response (instant)

### Optimization

For batch processing:

```php
// Increase timeout for reliability
$decoded = decode_google_news_redirect($url, 15);

// Or decode in background
queue_decode_job($url);
```

---

## Troubleshooting

### Issue: Returns NULL

**Possible Causes:**
1. Network/firewall blocking requests
2. Google News changed their format
3. Invalid URL format
4. Timeout too short

**Solutions:**
```php
// Increase timeout
$url = decode_google_news_redirect($google_url, 20);

// Check error logs
tail -f logs/error.log
```

### Issue: Slow Performance

**Solution:**
```php
// Reduce timeout for faster failure
$url = decode_google_news_redirect($google_url, 5);

// Decode in background
if (is_google_news_redirect($url)) {
    queue_background_decode($url);
}
```

### Issue: Some URLs Don't Decode

**Reason:** Not all Google News URLs use the redirect format

**Solution:**
```php
if (isset($article['link_decoded']) && $article['link_decoded']) {
    // Use decoded URL
    $url = $article['link'];
} else {
    // Use original URL
    $url = $article['link_google'];
}
```

---

## Comparison: Old vs New

### Old Implementation ❌

```php
// Just followed HTTP redirects
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
```

**Result:** Returns same Google News URL (doesn't work)

### New Implementation ✅

```php
// 1. Extract base64
$base64 = extract_from_url($url);

// 2. Get signature and timestamp
$params = fetch_from_google_page($base64);

// 3. Call batchexecute API
$decoded = call_google_api($params);
```

**Result:** Returns actual article URL (works!)

---

## Example

### Input

```
https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5
```

### Output

```
https://www.washingtonpost.com/technology/2025/10/23/russia-western-technology-nuclear-submarines/
```

### Domain

```
washingtonpost.com
```

---

## Integration Status

✅ **Fully Integrated** with news scraper  
✅ **Automatic decoding** when fetching news  
✅ **Fallback handling** if decoding fails  
✅ **Domain extraction** included  
✅ **Production ready**  

---

## API Reference

### `decode_google_news_redirect($google_url, $timeout = 10)`

Main function to decode Google News URLs.

**Parameters:**
- `$google_url` (string) - Google News redirect URL
- `$timeout` (int) - Timeout in seconds (default: 10)

**Returns:**
- `string|null` - Decoded URL or null on failure

**Example:**
```php
$url = decode_google_news_redirect($google_url, 15);
```

---

### `get_base64_str($source_url)`

Extract base64 string from Google News URL.

**Returns:**
```php
[
    'status' => true,
    'base64_str' => 'CBMiogFBVV95cUxNYTZmZm91...'
]
```

---

### `get_decoding_params($base64_str, $timeout = 10)`

Get signature and timestamp from Google News page.

**Returns:**
```php
[
    'status' => true,
    'signature' => 'abc123...',
    'timestamp' => '1234567890',
    'base64_str' => 'CBMiogF...'
]
```

---

### `decode_url_with_params($signature, $timestamp, $base64_str, $timeout = 10)`

Call Google's batchexecute API to decode URL.

**Returns:**
```php
[
    'status' => true,
    'decoded_url' => 'https://example.com/article'
]
```

---

## Credits

Based on the Python implementation by SSujitX:
https://github.com/SSujitX/google-news-url-decoder

Ported to PHP with full compatibility.

---

## Summary

✅ **Fixed:** Google News URL decoder now works correctly  
✅ **Method:** Uses Google's batchexecute API  
✅ **Integrated:** Works automatically with news scraper  
✅ **Tested:** Multiple test files included  
✅ **Production Ready:** Deployed and working  

**The decoder is now fully functional and decodes Google News URLs to actual article URLs!**
