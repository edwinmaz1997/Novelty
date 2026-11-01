/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './admin/**/*.php', './partials/**/*.php', './app/**/*.php', './api/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Montserrat', 'system-ui', 'sans-serif'] },
      colors: {
        ink: {
          DEFAULT: '#0B1220', 50: '#F5F7FA', 100: '#E9EDF3', 200: '#D3DAE4', 300: '#AEB9C8', 400: '#7D8CA3',
          500: '#55657E', 600: '#3C4A61', 700: '#28344A', 800: '#172033', 900: '#0B1220', 950: '#060A13'
        },
        brand: Object.fromEntries(
          [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((k) => [k, `rgb(var(--brand-${k}) / <alpha-value>)`])
            .concat([['DEFAULT', 'rgb(var(--brand-500) / <alpha-value>)']])
        )
      },
      boxShadow: { soft: '0 1px 2px rgba(11,18,32,.04), 0 8px 24px -8px rgba(11,18,32,.10)' }
    }
  },
  plugins: [require('@tailwindcss/forms')]
};
