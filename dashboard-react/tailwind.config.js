/** @type {import('tailwindcss').Config} */
export default {
  darkMode: ['selector', '[data-pgm-theme="dark"]'],
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {},
  },
  plugins: [],
};
