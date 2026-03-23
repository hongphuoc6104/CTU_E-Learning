/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './ELearning/**/*.php',
    './ELearning/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        primary: '#003366',
        'accent-green': '#10b981',
        'background-light': '#f5f7f8',
        'background-dark': '#0f1923',
        sidebar: '#001f40',
        accent: '#10b981',
        accentbg: '#064e3b'
      },
      fontFamily: {
        display: ['Inter', 'sans-serif']
      }
    }
  }
};
