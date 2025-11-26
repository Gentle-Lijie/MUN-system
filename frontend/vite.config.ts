import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_DEV_API_PROXY ?? env.VITE_API_BASE_URL ?? 'http://localhost:8000'

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
        '/attachments': {
          target: proxyTarget,
          changeOrigin: true,
        },
      },
      allowedHosts: ['.localhost', '.localdomain', 'zlj.hnrobert.space'],
    },
  }
})
