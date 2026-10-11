# Deployment Guide

## Environment Variables Configuration

This project uses Vite to bundle the frontend application. We have configured environment-specific `.env` files to ensure that the correct API URL is automatically baked into the build for each environment.

### Local Development (`.env`)
The local `.env` file should contain your local API URL. This is used when you run `npm start` or `npm run dev`.

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

### Production (`.env.production`)
The `.env.production` file is used for production builds. Vite will automatically pick this file up and prioritize its variables when building in production mode.

```env
VITE_API_URL=https://your-live-domain.com/api
```
*(Make sure to update the URL inside `.env.production` to your actual live domain).*

## Building for Production

When deploying to the live server, you must **always** run the production build command. 
Do **NOT** just run `npm run build` with only the local `.env` active if your server does not pass the production mode flag automatically.

To ensure the production environment variables are used, run:

```bash
npm run build -- --mode production
```

Vite automatically uses `.env.production` when the `--mode production` flag is provided (or if `NODE_ENV=production` is set in the shell). This guarantees that the correct `VITE_API_URL` is baked into the production bundle, so you no longer need to manually edit the root path before every push or deploy!

## Firebase Storage (admin uploads)

To enable direct client-to-Firebase uploads for learning materials (Notes, Past Papers, Recordings) in the Admin panel:

1. **Enable Firebase Authentication**:
   - Go to Firebase Console (`https://console.firebase.google.com/`).
   - Navigate to **Authentication** and click **Get started** (required so `signInWithCustomToken` works).

2. **Publish Storage Security Rules**:
   - Deploy `firebase/storage.rules` to Firebase Storage (via Firebase CLI with `firebase deploy --only storage` or paste into Firebase Console → Storage → Rules).

3. **Generate Service Account Key**:
   - In Firebase Console, go to **Project Settings** → **Service accounts**.
   - Click **Generate new private key** and download the JSON file.

4. **Configure Environment Variables**:
   - In `backend/.env`, set:
     ```env
     FIREBASE_CLIENT_EMAIL="<client_email from service account JSON>"
     FIREBASE_PRIVATE_KEY="<private_key from service account JSON - single line with literal \n escapes>"
     FIREBASE_API_KEY="<web apiKey>"
     FIREBASE_AUTH_DOMAIN="<projectId>.firebaseapp.com"
     FIREBASE_PROJECT_ID="<projectId>"
     FIREBASE_STORAGE_BUCKET="<projectId>.firebasestorage.app"
     FIREBASE_MESSAGING_SENDER_ID="<messagingSenderId>"
     FIREBASE_APP_ID="<appId>"
     ```

5. **Apply Configuration & Migrations**:
   ```bash
   php artisan config:clear
   php artisan migrate
   ```
   *(Note: If `FIREBASE_*` variables are not set, the admin panel automatically falls back to local server disk uploads).*

