/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.html',
    './**/*.php',
    './**/*.js',

    // exclude the following
    '!./node_modules/**',
    '!./vendor/**',
  ],
  theme: {
    extend: {
      colors: {
        background: '#f9fafb',
        dark: '#0f172a',
      },
      foreground: {
        DEFAULT: '#0f172a',
        dark: '#f9fafb',
      },
      primary: {
        DEFAULT: '#2563eb',
        dark: '#60a5fa',
      },
    },
  },
  darkMode: 'class',
  plugins: [],
}

