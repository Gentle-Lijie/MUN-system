import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const domain = env.url || env.VITE_APP_DOMAIN || env.VITE_PUBLIC_HOST
  const backendScheme = env.BACKEND_SCHEME || env.VITE_BACKEND_SCHEME || 'https'
  const backendPort = env.BACKEND_PORT || env.VITE_BACKEND_PORT || '8000'
  const inferredApiBase = domain ? `${backendScheme}://${domain}:${backendPort}` : 'http://localhost:8000'
  const proxyTarget = env.VITE_DEV_API_PROXY ?? env.VITE_API_BASE_URL ?? inferredApiBase
  const allowedHosts = Array.from(new Set(['.localhost', '.localdomain', 'zlj.hnrobert.space', domain, domain ? `.${domain}` : undefined].filter(Boolean)))

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5173,
      host: '0.0.0.0',
      hmr: true,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
        },
      },
      allowedHosts,
    },
  }
})
