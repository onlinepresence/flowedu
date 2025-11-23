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
    extend: {},
  },
  darkMode: 'class',
  plugins: [],
}

