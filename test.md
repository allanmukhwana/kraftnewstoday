
### Testing Workflow

**Test 1: User Registration & Onboarding**
1. Navigate to `http://localhost:8000`
2. Click "Sign Up"
3. Enter test email: `test@kraftnews.com`
4. Complete registration form
5. Verify email (check logs in test mode)
6. **Expected**: User account created, redirected to industry selection

**Test 2: Industry Selection**
1. After login, go to "Add Industries"
2. Select 2-3 industries (e.g., "Healthcare", "Fintech", "AI/ML")
3. Save preferences
4. **Expected**: Industries saved and displayed on dashboard

**Test 3: Free Tier - News Aggregation**
1. Trigger manual scrape: Visit `/agent/trigger_scrape.php` (admin access)
2. Or wait for scheduled cron job
3. Check dashboard for news headlines
4. **Expected**: Relevant news articles displayed without AI analysis

**Test 4: Premium Subscription**
1. Click "Upgrade to Premium"
2. Use Stripe test card: `4242 4242 4242 4242`
3. Expiry: Any future date, CVC: Any 3 digits
4. Complete payment
5. **Expected**: Subscription activated, confirmation shown

**Test 5: AI Agent Analysis (Premium)**
1. After upgrading, trigger agent analysis: `/agent/trigger_analysis.php`
2. Agent will:
   - Fetch recent articles for user's industries
   - Analyze each with Amazon Bedrock
   - Generate multi-dimensional insights
3. Check dashboard for analyzed articles with insights
4. **Expected**: Articles show detailed analysis sections:
   - Accuracy & Credibility
   - Industry Impact
   - Trend Detection
   - Strategic Implications
   - Technical Evaluation
   - Competitive Intelligence
   - Bias & Framing

**Test 6: Email Delivery**
1. Set timezone in profile settings
2. Manually trigger email: `/email/send_digest.php?user_id=1`
3. Check configured email inbox
4. **Expected**: Receive formatted email digest with analyzed articles

**Test 7: Scheduled Agent Execution**
```bash
# Test cron job manually
php cron/morning_digest.php
php cron/evening_digest.php
```
**Expected**: All active users receive digests based on their timezone

**Test 8: Multi-Industry Tracking**
1. Add 3+ different industries
2. Trigger scrape and analysis
3. Verify articles from all industries appear
4. Check that analysis is contextual to each industry
5. **Expected**: Diverse articles with industry-specific insights

### Admin Testing Panel

Access admin panel at `/admin/test_panel.php` (requires admin login):

- **Trigger Agent Tasks**: Manually run scraping/analysis
- **View Agent Logs**: See decision-making process
- **Test Email Delivery**: Send test digests
- **Monitor Bedrock Usage**: Track API calls and costs
- **View Analysis Quality**: Sample AI-generated insights

### Validation Checklist

- [ ] Users can register and select industries
- [ ] Free users receive news headlines
- [ ] Stripe payment flow completes successfully
- [ ] Premium users receive AI-analyzed articles
- [ ] Analysis covers all 7 dimensions
- [ ] Emails deliver at correct timezone
- [ ] Agent makes relevant article selections
- [ ] Multi-industry tracking works correctly
- [ ] Mobile responsive design functions properly
- [ ] Error handling works (invalid inputs, API failures)

### Troubleshooting

**Agent not analyzing articles:**
- Check `logs/agent.log` for errors
- Verify Bedrock API credentials in config
- Ensure sufficient Bedrock quota

**Emails not sending:**
- Verify email service credentials
- Check `logs/email.log`
- Test mode may log instead of sending

**Payment failing:**
- Confirm Stripe test mode is enabled
- Use correct test card numbers
- Check Stripe dashboard for webhook events

---