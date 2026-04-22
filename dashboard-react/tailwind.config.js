/** @type {import('tailwindcss').Config} */
export default {
  darkMode: ['selector', '[data-pgm-theme="dark"]'],
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      keyframes: {
        'pgm-fade-in-up': {
          from: { opacity: '0', transform: 'translateY(6px)' },
          to: { opacity: '1', transform: 'translateY(0)' },
        },
        /* Mockup v2: dots pulsantes (webroot/pgm-servicedesk-v2-navegacao.html) */
        'pgm-pulse': {
          '0%, 100%': { opacity: '1', transform: 'scale(1)' },
          '50%': { opacity: '0.5', transform: 'scale(0.85)' },
        },
      },
      animation: {
        'pgm-fade-in-up': 'pgm-fade-in-up 0.25s ease-out',
        'pgm-pulse': 'pgm-pulse 2s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
