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
