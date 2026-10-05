# 📱 WhatsApp Setup Guide — Meta Cloud API

## TiT Online Education — WhatsApp Integration Setup

இந்த guide-ஐ follow பண்ணி Meta WhatsApp Cloud API setup பண்ணலாம்.
Code changes already done — நீங்கள் இந்த steps மட்டும் பண்ணா போதும்!

---

## Step 1: Facebook Business Account Create பண்ணுங்க

1. **[business.facebook.com](https://business.facebook.com)** — open பண்ணுங்க
2. **"Create Account"** click பண்ணுங்க
3. Business Name: **"TiT Online Education"** (or your business name)
4. Your name and business email enter பண்ணுங்க
5. **Create** click பண்ணுங்க

> **TIP:** Already personal Facebook account இருந்தா அதே login use பண்ணலாம்.

---

## Step 2: Meta Developer Account Create பண்ணுங்க

1. **[developers.facebook.com](https://developers.facebook.com)** — open பண்ணுங்க
2. Login பண்ணுங்க (same Facebook account)
3. **"My Apps"** → **"Create App"** click பண்ணுங்க
4. App type: **"Business"** select பண்ணுங்க
5. App name: **"TiT WhatsApp"**
6. Business account: Step 1-ல create பண்ண account select பண்ணுங்க
7. **"Create App"** click பண்ணுங்க

---

## Step 3: WhatsApp Product Add பண்ணுங்க

1. உங்கள் new app-ன் dashboard-ல, left sidebar-ல **"Add Products"** click பண்ணுங்க
2. **"WhatsApp"** find பண்ணி **"Set Up"** click பண்ணுங்க
3. Business account connect பண்ணுங்க

---

## Step 4: Phone Number Register பண்ணுங்க

இரண்டு options இருக்கு:

### Option A: Meta Test Number Use (Testing-க்கு)
- Dashboard-ல automatically ஒரு test phone number கிடைக்கும்
- இது 5 verified numbers-க்கு மட்டும் message அனுப்ப முடியும்
- **Testing-க்கு மட்டும் use பண்ணுங்க**

### Option B: உங்கள் Own Number Register (Production-க்கு) ⭐
1. **WhatsApp** → **"Getting Started"** page-ல
2. **"Add Phone Number"** click பண்ணுங்க
3. **Display name:** "TiT Online Education"
4. **Phone number:** உங்கள் Sri Lankan number enter பண்ணுங்க (e.g. +94XXXXXXXXX)
5. Verification code SMS/call-ல வரும் — enter பண்ணுங்க

> **IMPORTANT:** இந்த number-ல WhatsApp personal account இருந்தா, அதை remove பண்ணணும் (business API-க்கு separate number best).

---

## Step 5: Permanent API Token Generate பண்ணுங்க

### Temporary Token (Testing - 24 hours valid)
1. **WhatsApp** → **"Getting Started"** page-ல
2. **"Temporary access token"** — copy பண்ணுங்க
3. இது 24 hours-க்கு மட்டும் valid

### Permanent Token (Production) ⭐
1. **Meta Business Suite** → **"Settings"** → **"Business Settings"**
2. **"System Users"** → **"Add"** click பண்ணுங்க
3. Name: **"TiT WhatsApp API"**, Role: **"Admin"**
4. **"Add Assets"** → உங்கள் WhatsApp app select பண்ணுங்க → **Full Control** enable
5. **"Generate New Token"** click பண்ணுங்க
6. Permissions-ல select பண்ணுங்க:
   - `whatsapp_business_management`
   - `whatsapp_business_messaging`
7. **"Generate Token"** click பண்ணி copy பண்ணுங்க

> **CAUTION:** Token-ஐ safely save பண்ணுங்க! இது again show ஆகாது.

---

## Step 6: Phone Number ID கண்டுபிடிங்க

1. **[developers.facebook.com](https://developers.facebook.com)** → உங்கள் app
2. **WhatsApp** → **"Getting Started"** or **"API Setup"**
3. **"Phone Number ID"** — ஒரு numeric ID இருக்கும் (e.g. `123456789012345`)
4. இதை copy பண்ணுங்க

---

## Step 7: `.env` File Update பண்ணுங்க

உங்கள் project-ன் `backend/.env` file open பண்ணி, இந்த values update பண்ணுங்க:

```env
WHATSAPP_DRIVER=meta

# Meta WhatsApp Cloud API (Primary)
META_WHATSAPP_TOKEN=EAAxxxxxxx_YOUR_REAL_TOKEN_HERE
META_WHATSAPP_PHONE_NUMBER_ID=123456789012345
META_WHATSAPP_API_VERSION=v21.0
```

> **NOTE:** Production server-லும் (cPanel) இதே values `.env`-ல add பண்ணுங்க!

---

## Step 8: Message Templates Create பண்ணுங்க ⭐

> **IMPORTANT:** Meta WhatsApp Cloud API-ல, நீங்கள் student-க்கு FIRST message அனுப்ப (business-initiated), **approved template** வேணும். Student reply பண்ணா 24-hour window open ஆகும், அதுக்குள் free-text அனுப்பலாம்.

### Templates Create பண்ற method:
1. **[business.facebook.com](https://business.facebook.com)** → **WhatsApp Manager**
2. **"Message Templates"** → **"Create Template"**

### உங்கள் Project-க்கு Recommended Templates:

#### Template 1: `zoom_reminder` (Zoom Class Reminder)
- **Category:** UTILITY
- **Language:** English (or Tamil)
- **Body:**
```
🔔 *Reminder: Your Zoom class starts in 15 mins!*

📝 *Class:* {{1}}
⏰ *Time:* {{2}}

🔗 *Join:* {{3}}
```
- Variables: `{{1}}` = class name, `{{2}}` = time, `{{3}}` = zoom link

#### Template 2: `payment_reminder` (Payment Due Reminder)
- **Category:** UTILITY
- **Body:**
```
Dear {{1}},

This is a reminder from TiT Online Education.
Your payment for {{2}} is due.

Please pay by the 5th to avoid account deactivation.

If already paid, please ignore.
```
- Variables: `{{1}}` = student name, `{{2}}` = month name

#### Template 3: `welcome_student` (Registration Welcome)
- **Category:** UTILITY
- **Body:**
```
Welcome {{1}} to TiT Online Education! 🎓

Your registration is almost complete.
Please proceed to payment to activate your account.

Your username: {{2}}
```
- Variables: `{{1}}` = full name, `{{2}}` = username

#### Template 4: `payment_success` (Payment Confirmation)
- **Category:** UTILITY
- **Body:**
```
Thank you {{1}}! ✅

Your payment has been received successfully.
Our team will verify and activate your account shortly.
```
- Variables: `{{1}}` = student name

#### Template 5: `account_deactivated` (Account Deactivation)
- **Category:** UTILITY
- **Body:**
```
Dear {{1}},

Your account has been deactivated due to non-payment for {{2}}.
To reactivate, please complete your payment and contact Admin.
```
- Variables: `{{1}}` = student name, `{{2}}` = month

> **NOTE:** Templates Meta team approve பண்ணும் — usually 1-24 hours duration. UTILITY templates fast-ஆ approve ஆகும்.

---

## Step 9: Test பண்ணுங்க! 🧪

### Test 1: Text Message (24h window-க்குள்)
```bash
php artisan app:test-whatsapp 07XXXXXXXX "Hello! This is a test from TiT Education"
```

### Test 2: Template Message
```bash
php artisan app:test-whatsapp 07XXXXXXXX "John|Mathematics|https://zoom.us/j/123" --template=zoom_reminder
```

### Test 3: Check Logs
```bash
# Check if message was sent successfully
tail -f storage/logs/laravel.log
```

### Test 4: Verify Config
```bash
php artisan tinker
# Then type:
config('services.meta_whatsapp')
# Should show your token, phone_number_id, and api_version
```

---

## Step 10: Production Deploy (cPanel) 🚀

1. cPanel-ல `.env` file-ல same Meta credentials add பண்ணுங்க
2. `php artisan config:cache` run பண்ணுங்க
3. Scheduler setup பண்ணுங்க (cPanel Cron Job):
```
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```
4. இது automatically Zoom reminders, payment checks எல்லாம் run பண்ணும்

---

## 💰 Pricing Summary

| Item | Cost |
|---|---|
| First 1000 service conversations/month | **FREE** |
| Utility conversations (Sri Lanka) | ~$0.0173 each |
| Marketing conversations | ~$0.0454 each |
| **Estimated monthly (600 students)** | **~$35/month** |

---

## ❓ Common Issues & Solutions

| Issue | Solution |
|---|---|
| "Token expired" | Permanent token generate பண்ணுங்க (Step 5) |
| "Template not found" | Template approve ஆகும் வரை wait பண்ணுங்க |
| "Phone number not verified" | Step 4 again check பண்ணுங்க |
| "Message failed to send" | `storage/logs/laravel.log` check பண்ணுங்க |
| "Outside 24h window" | Template message use பண்ணுங்க (free-text won't work) |
