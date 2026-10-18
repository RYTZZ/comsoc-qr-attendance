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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Manrope', ...defaultTheme.fontFamily.sans],
                promo: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                burgundy: {
                    DEFAULT: '#560E0F',
                    dark: '#3B090A',
                    accent: '#8F3033',
                    50: '#fdf2f2',
                    100: '#fae4e5',
                    200: '#f5cbce',
                    300: '#eda5a9',
                    400: '#df747a',
                    500: '#8F3033',
                    600: '#6d1416',
                    700: '#560E0F',
                    800: '#3B090A',
                    900: '#2c0607',
                    950: '#1b0304',
                },
                navy: {
                    DEFAULT: '#0B1120',
                    950: '#060a14',
                },
                surface: {
                    DEFAULT: '#172033',
                    dark: '#111827',
                    elevated: '#1E293B',
                },
                slate: {
                    ...defaultTheme.colors?.slate,
                    dark: '#303644',
                },
                brand: {
                    DEFAULT: '#560E0F',
                    dark: '#3B090A',
                    accent: '#8F3033',
                    50: '#fdf2f2',
                    100: '#fae4e5',
                    200: '#f5cbce',
                    300: '#eda5a9',
                    400: '#df747a',
                    500: '#8F3033',
                    600: '#6d1416',
                    700: '#560E0F',
                    800: '#3B090A',
                    900: '#2c0607',
                    950: '#1b0304',
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
