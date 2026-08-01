import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:  ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Source Serif 4', 'Georgia', 'serif'],
            },
            colors: {
                rojo:   { DEFAULT: '#8C1F2F', dark: '#6B1622' },
                dorado: { DEFAULT: '#B8912E', soft: '#E6D7B0' },
                tinta:  { DEFAULT: '#1E1B18', soft: '#302B26' },
                crema:  '#F6F1E7',
                paper:  '#FFFDF8',
                piedra: { DEFAULT: '#6B665D', soft: '#A8A196' },
                borde:  '#E4DCC9',
                ok:     '#4E7C4D',
                alerta: '#B8791E',
            },
        },
    },

    plugins: [forms],
};
