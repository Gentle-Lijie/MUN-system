import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Space Grotesk"', 'Inter', 'sans-serif'],
        body: ['"Inter"', 'system-ui', 'sans-serif'],
      },
    },
  },
  daisyui: {
    themes: [
      {
        munlight: {
          primary: '#2563eb',
          secondary: '#7c3aed',
          accent: '#f97316',
          neutral: '#1f2937',
          'base-100': '#f4f6fb',
          info: '#0ea5e9',
          success: '#22c55e',
          warning: '#facc15',
          error: '#ef4444',
        },
      },
      'business',
    ],
    darkTheme: 'business',
  },
  plugins: [daisyui],
}

