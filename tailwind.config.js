import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
 	"./index.html",
    	"./src/**/*.{vue,js,ts,jsx,tsx}",
        "./node_modules/flowbite/**/*.js",
        "node_modules/flowbite/**/*.js"
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                color1: {
                  DEFAULT: '#7b003a', 
                  40: '#fcf2f5', 
                  50: '#ffe4ec',
                  100: '#fabacb',
                  200: '#f08aa3',
                  300: '#e8567c',
                  400: '#e02d59',
                  500: '#cc0042',
                  600: '#b3003b',
                  700: '#8f0030',
                  800: '#6b0026',
                  900: '#48001b',
                },
                color2: {
                  DEFAULT: '#005350',  
                  50: '#e0f6f5',
                  100: '#b3e6e2',
                  200: '#80d5cf',
                  300: '#4dc4bb',
                  400: '#26b5ad',
                  500: '#009b93',
                  600: '#007c77',
                  700: '#005d5a',
                  800: '#00403e',
                  900: '#002724',
                },
                color3: {
                  DEFAULT: '#5f5e5e',
                  40: '#fafafa', 
                  50: '#f0f0f0',
                  100: '#d9d9d9',
                  200: '#bfbfbf',
                  300: '#a6a6a6',
                  400: '#8c8c8c',
                  500: '#737373',
                  600: '#5f5e5e',
                  700: '#4b4b4b',
                  800: '#363636',
                  900: '#222222',
                },
                color4: {
                  DEFAULT: '#b4905e',  
                  50: '#f6e9de',       // Muy claro
                  100: '#edd2be',      // Claro
                  200: '#e2b99a',      // Claro medio
                  300: '#d9a17b',      // Medio
                  400: '#cf865b',      // Medio oscuro
                  500: '#b4905e',      // DEFAULT
                  600: '#936f46',      // Oscuro medio
                  700: '#70532f',      // Oscuro
                  800: '#4f371f',      // Muy oscuro
                  900: '#2e1d12',      // Extremadamente oscuro
              },
            },
        },
    },

    plugins: [forms, require('flowbite/plugin')],
   
};
  