/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './admin/**/*.php', './partials/**/*.php', './app/**/*.php', './api/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
      colors: {
        ink: {
          DEFAULT: '#0B1220', 50: '#F5F7FA', 100: '#E9EDF3', 200: '#D3DAE4', 300: '#AEB9C8', 400: '#7D8CA3',
          500: '#55657E', 600: '#3C4A61', 700: '#28344A', 800: '#172033', 900: '#0B1220', 950: '#060A13'
        },
        brand: {
          DEFAULT: '#F25C05', 50: '#FFF4EC', 100: '#FFE4D1', 200: '#FFC59E', 300: '#FF9E61', 400: '#FF7A2E',
          500: '#F25C05', 600: '#D04A00', 700: '#A63B02', 800: '#7F2E05'
        }
      },
      boxShadow: { soft: '0 1px 2px rgba(11,18,32,.04), 0 8px 24px -8px rgba(11,18,32,.10)' }
    }
  },
  plugins: [require('@tailwindcss/forms')]
};
