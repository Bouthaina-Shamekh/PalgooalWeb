import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/css/**/*.css',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        cairo: ['var(--font-Cairo)', 'Cairo', 'Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
      },
      colors: {
        primary: 'var(--color-primary)',
        secondary: 'var(--color-secondary)', 
        tertiary: 'var(--color-tertiary)',
        background: 'var(--color-background)',
        hazem: 'var(--color-hazem)',
        primary1: {
          50: '#eff6ff',
          100: '#dbeafe', 
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        }
      },
      fontSize: {
        'title-h2': 'var(--text-title-h2)',
        'title-h3': 'var(--text-title-h3)',
        'suptitle': 'var(--text-suptitle)',
      }
    },
  },

  plugins: [forms],
  
  darkMode: 'class',
};