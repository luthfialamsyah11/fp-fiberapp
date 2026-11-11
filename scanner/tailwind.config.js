/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        background: '#090D16',
        surface: '#111726',
        'surface-border': '#1F293D',
        primary: {
          DEFAULT: '#10B981',
          hover: '#059669',
          light: '#34D399',
        },
        cyber: {
          cyan: '#06B6D4',
          emerald: '#10B981',
          amber: '#F59E0B',
          crimson: '#EF4444',
          purple: '#8B5CF6',
        }
      },
    },
  },
  plugins: [],
};
