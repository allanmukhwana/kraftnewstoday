# How to Enable Real Bedrock API (Stop Using Mock Responses)

## Current Status

Your application is currently using **mock responses** because the Bedrock API calls are returning `null`.

## Why API Calls Are Failing

### Most Likely Reasons:

1. **API Key Expired** (30-day limit)
2. **Wrong Model ID** (you changed to `amazon.nova-micro-v1:0`)
3. **Model Not Available** in your region
4. **API Key Permissions** don't include the model

---

## Solution 1: Use Claude Model (Recommended)

Claude models are more widely available and reliable.

### Step 1: Update config.php

Change this line:
```php
define('BEDROCK_MODEL_ID', 'amazon.nova-micro-v1:0');
```

To:
```php
define('BEDROCK_MODEL_ID', 'us.anthropic.claude-3-5-haiku-20241022-v1:0');
```

### Step 2: Test

```bash
php test_bedrock.php
```

If tests 4 and 5 still fail, your API key is likely expired.

---

## Solution 2: Generate New API Key

### Step 1: Go to AWS Console

Visit: https://console.aws.amazon.com/bedrock

### Step 2: Navigate to API Keys

1. Sign in to AWS Console
2. Go to **Amazon Bedrock** service
3. Click **API keys** in left navigation
4. Click **Generate long-term API keys**

### Step 3: Generate Key

1. Select **30 days** expiration
2. Click **Generate**
3. **COPY THE KEY IMMEDIATELY** (shown only once)

### Step 4: Update config.php

```php
define('BEDROCK_API_KEY', 'YOUR-NEW-API-KEY-HERE');
```

### Step 5: Test

```bash
php test_bedrock.php
```

---

## Solution 3: Test Nova Model

If you want to use Nova instead of Claude:

### Step 1: Test if Nova is available

```bash
php test_nova_model.php
```

### Step 2: Check Results

If tests pass:
- ✓ Nova model works
- ✓ Keep current config

If tests fail:
- ✗ Nova not available in your region
- ✗ Switch back to Claude (Solution 1)

---

## Verify Real API is Working

### Test 1: Run Bedrock Test

```bash
php test_bedrock.php
```

**Look for:**
```
4. Testing simple API call...
   ✓ API call successful!
   Response: "Hello from AWS Bedrock!"
```

If you see `✗ API call failed`, the API is not working.

### Test 2: Check Error Logs

```bash
# Check logs directory
cat logs/error.log
```

**Look for:**
- "Bedrock API call failed"
- "HTTP 401" (expired key)
- "HTTP 403" (no permission)
- "HTTP 404" (wrong model ID)

### Test 3: Run Analysis Test

```bash
php test_full_scrape.php
```

Check if it says "using mock response" in logs.

---

## How to Tell if Real API is Working

### Mock Response (Current):
```
✓ Analysis completed!
Relevance Score: 0.85
Explanation: This article is highly relevant to the industry, 
covering recent developments and key trends.
```
*Always the same generic response*

### Real API Response:
```
✓ Analysis completed!
Relevance Score: 0.92
Explanation: This article discusses a breakthrough in AI-powered 
medical diagnosis systems, which directly impacts healthcare 
providers' ability to improve patient outcomes. The technology 
described has immediate practical applications...
```
*Specific to the actual article content*

---

## Troubleshooting

### Issue: API Key Expired

**Symptoms:**
- HTTP 401 error in logs
- "Unauthorized" message
- API calls return null

**Solution:**
Generate new API key (Solution 2)

### Issue: Wrong Model ID

**Symptoms:**
- HTTP 404 error in logs
- "Model not found"
- API calls return null

**Solution:**
Use Claude model (Solution 1)

### Issue: No Permissions

**Symptoms:**
- HTTP 403 error in logs
- "Forbidden" message

**Solution:**
1. Check API key has `AmazonBedrockLimitedAccess` policy
2. Generate new key with correct permissions

### Issue: Network/Firewall

**Symptoms:**
- cURL timeout errors
- Connection refused

**Solution:**
1. Test from production server (not local)
2. Check firewall allows HTTPS to AWS
3. Verify DNS resolves: `bedrock-runtime.us-east-1.amazonaws.com`

---

## Model Comparison

### Claude 3.5 Haiku (Recommended)
```php
define('BEDROCK_MODEL_ID', 'us.anthropic.claude-3-5-haiku-20241022-v1:0');
```

**Pros:**
- ✓ Widely available
- ✓ Excellent quality
- ✓ Good for analysis
- ✓ Reliable JSON output

**Cons:**
- ⚠ Slightly more expensive
- ⚠ Slightly slower

### Amazon Nova Micro
```php
define('BEDROCK_MODEL_ID', 'amazon.nova-micro-v1:0');
```

**Pros:**
- ✓ Faster
- ✓ Cheaper
- ✓ Good for simple tasks

**Cons:**
- ⚠ May not be available in all regions
- ⚠ Less sophisticated analysis
- ⚠ May need prompt adjustments

---

## Quick Fix Checklist

- [ ] Check if API key is set in config.php
- [ ] Verify API key is not expired (30-day limit)
- [ ] Use Claude model ID (more reliable)
- [ ] Run `php test_bedrock.php`
- [ ] Check error logs for specific errors
- [ ] Generate new API key if needed
- [ ] Test from production server if local fails

---

## Production Deployment

### Option A: Use Real Bedrock API

**Requirements:**
1. Valid API key (not expired)
2. Working model ID
3. Network access to AWS

**Setup:**
1. Generate fresh API key
2. Update config.php
3. Test on production server
4. Set up key rotation (before 30 days)

### Option B: Keep Using Mock Responses

**Current Status:**
- ✓ Already working
- ✓ No API costs
- ✓ No key management

**Limitations:**
- ✗ Not real AI analysis
- ✗ Generic responses
- ✗ Limited value for users

---

## Cost Considerations

### Claude 3.5 Haiku Pricing (Approximate)
- Input: $0.25 per 1M tokens
- Output: $1.25 per 1M tokens

**Estimated Cost for Your App:**
- 100 articles/day
- 7 dimensions per article
- ~500 tokens per analysis
- **~$5-10/month**

### Nova Micro Pricing (Approximate)
- Input: $0.035 per 1M tokens
- Output: $0.14 per 1M tokens

**Estimated Cost:**
- **~$1-2/month**

---

## Next Steps

1. **Test Current Setup:**
   ```bash
   php test_bedrock.php
   ```

2. **If Failed:**
   - Switch to Claude model (Solution 1)
   - OR generate new API key (Solution 2)

3. **Verify Working:**
   ```bash
   php test_nova_model.php  # If using Nova
   php test_full_scrape.php  # Test full flow
   ```

4. **Deploy:**
   - Upload to production
   - Test on live server
   - Monitor error logs

---

## Support

If you're still having issues:

1. **Check AWS Service Health:**
   https://status.aws.amazon.com

2. **Review Error Logs:**
   ```bash
   tail -f logs/error.log
   ```

3. **Test Direct API Call:**
   ```bash
   php test_bedrock_api_direct.php
   ```

This will show exact HTTP request/response for debugging.
