import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

const apiTarget = process.env.VITE_DEV_API_TARGET || 'http://127.0.0.1:8000';

const devProxy = {
  '/api': {
    target: apiTarget,
    changeOrigin: true,
    secure: false,
  },
  '/storage': {
    target: apiTarget,
    changeOrigin: true,
    secure: false,
  },
  '/receipts': {
    target: apiTarget,
    changeOrigin: true,
    secure: false,
  },
  '/sitemap.xml': {
    target: apiTarget,
    changeOrigin: true,
    secure: false,
  },
  '/robots.txt': {
    target: apiTarget,
    changeOrigin: true,
    secure: false,
  },
};

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 4000,
    open: true,
    allowedHosts: ['.ngrok-free.dev'],
    proxy: devProxy,
  },
  preview: {
    port: 4173,
    allowedHosts: true,
    proxy: devProxy,
  }
})
