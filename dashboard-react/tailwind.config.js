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
      },
      animation: {
        'pgm-fade-in-up': 'pgm-fade-in-up 0.25s ease-out',
      },
    },
  },
  plugins: [],
};
