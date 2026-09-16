import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                display: ['"Sora"', ...defaultTheme.fontFamily.sans],
                accent: ['"Michroma"', 'monospace', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                maroon: {
                    DEFAULT: '#7A1618',
                    50: '#fbf2f2',
                    100: '#f5e3e3',
                    200: '#eccbcd',
                    300: '#dfa6a9',
                    400: '#cc7478',
                    500: '#b4484d',
                    600: '#992d32',
                    700: '#7A1618',
                    800: '#671416',
                    900: '#561416',
                    950: '#300809',
                },
                slate: {
                    ...defaultTheme.colors?.slate,
                    dark: '#303644',
                },
                brand: {
                    50: '#fbf2f2',
                    100: '#f5e3e3',
                    200: '#eccbcd',
                    300: '#dfa6a9',
                    400: '#cc7478',
                    500: '#7A1618',
                    600: '#671416',
                    700: '#561416',
                    800: '#461113',
                    900: '#300809',
                    950: '#1e0506',
                },
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'pulse-once': 'pulseOnce 0.5s ease-in-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pulseOnce: {
                    '0%, 100%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.04)' },
                },
            },
        },
    },

    plugins: [forms],
};
