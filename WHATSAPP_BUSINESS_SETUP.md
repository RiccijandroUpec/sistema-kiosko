# WhatsApp Business API Integration Guide

## Overview
This guide explains how to set up the Meta WhatsApp Business API integration for the kiosko system.

## Prerequisites
1. A Meta Business Account
2. A WhatsApp Business Account
3. Developer access to Meta's API

## Step 1: Create a Meta Business Account
1. Go to [business.facebook.com](https://business.facebook.com)
2. Create a new Business Account if you don't have one
3. Add your business information

## Step 2: Set Up WhatsApp Business App
1. In Meta Business Manager, go to **Apps & Assets** → **Apps**
2. Create a new app or use an existing one
3. Select **WhatsApp Business** as the use case
4. Follow the setup wizard

## Step 3: Configure WhatsApp Business Account
1. In your Meta Business Account, go to **WhatsApp Manager**
2. Click **Create a test account** or link your existing number
3. You'll receive:
   - **Phone Number ID**: Used to send messages
   - **WABA ID**: WhatsApp Business Account ID
   - **Business Account ID**: Meta Business Account ID

## Step 4: Generate Access Token
1. Go to **App Settings** → **Basic**
2. Create an app password or system user token
3. The token must have `whatsapp_business_messaging` permission

## Step 5: Update Environment Variables
Add these variables to your `.env` file:

```env
WHATSAPP_BUSINESS_TOKEN=your_meta_access_token
WHATSAPP_BUSINESS_PHONE_ID=your_phone_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_waba_id
WHATSAPP_BUSINESS_PHONE_NUMBER=+1234567890
WHATSAPP_API_VERSION=v18.0
WHATSAPP_BASE_URL=https://graph.facebook.com
WHATSAPP_WEBHOOK_VERIFY_TOKEN=kiosko_webhook_token_2024
```

## Step 6: Configure Webhook
1. In your Meta App Dashboard, go to **WhatsApp** → **Configuration**
2. Set **Webhook URL** to: `https://yourdomain.com/webhook/whatsapp`
3. Set **Verify Token** to: `kiosko_webhook_token_2024` (or your custom token)
4. Subscribe to the following webhook fields:
   - `messages`
   - `message_status`

## Step 7: Test the Integration
Once configured, test by:
1. Sending a message to your WhatsApp Business number from your personal account
2. Check the application logs to see if the message is received
3. Verify that the response is sent back

## Features
- ✅ Send text messages
- ✅ Send documents (PDFs)
- ✅ Receive documents
- ✅ AI responses via Deepseek API
- ✅ Message status tracking
- ✅ Auto-read receipt

## File Structure
- `config/whatsapp-business.php` - Configuration file
- `app/Services/WhatsAppBusinessService.php` - Service class for API calls
- `app/Http/Controllers/WhatsAppController.php` - Webhook handler
- `routes/web.php` - Webhook route definition

## Troubleshooting

### Messages not sending
- Check the access token is valid and has the correct permissions
- Verify the Phone ID is correct
- Check that the receiving number has the correct format (+CC123456789)
- Review application logs for specific error messages

### Webhook not receiving messages
- Verify the webhook URL is publicly accessible
- Check that the Verify Token matches in Meta settings
- Ensure CSRF protection is disabled for the webhook route (already done)
- Check the application logs for webhook errors

### API Rate Limiting
Meta has rate limits on the WhatsApp Business API. If you encounter `429 Too Many Requests`:
- Implement rate limiting in your application
- Use message queuing for batch sends
- Contact Meta for higher rate limits if needed

## Security Notes
- Keep your access token secret - never commit it to version control
- Use environment variables for all credentials
- Rotate access tokens periodically
- Monitor webhook activity for suspicious patterns

## Meta API Documentation
- [WhatsApp Business API](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started)
- [Message Types](https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages)
- [Webhooks](https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/setup-webhooks)

## Support
For issues or questions:
1. Check Meta's official documentation
2. Review application error logs in `storage/logs/`
3. Test with cURL commands directly against the Meta API
