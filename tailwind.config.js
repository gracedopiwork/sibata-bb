import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Source Serif 4', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                navy: {
                    50: '#f4f7fb',
                    100: '#e6eef6',
                    200: '#c5d4e6',
                    950: '#07111f',
                    900: '#0b1f3a',
                    800: '#122a4a',
                    700: '#1a3a63',
                    600: '#245089',
                    500: '#3b6ea8',
                },
                gold: {
                    200: '#f4e7b0',
                    300: '#f0d77a',
                    400: '#e8c547',
                    500: '#c9a227',
                    600: '#a3841c',
                },
                parchment: '#f6f1e7',
            },
            boxShadow: {
                panel: '0 18px 40px -24px rgba(11, 31, 58, 0.45)',
            },
        },
    },
    plugins: [],
};
