import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
    const colorsToSafelist = [
      'gray', // Si lo usas como color base en estatus
      'violet',
      'dodgerblue',
      'slateblue',
      'orange',
      'tomato',
      'mediumseagreen',
      // Añade cualquier otro nombre de color base que uses en estatus.color
    ];

    const shadesToSafelist = [ // Tonos que quieres generar
      '50', '100', '200', '300', '400', '500', '600', '700', '800', '900',
    ];

    const prefixesToSafelist = [ // Utilidades que quieres que tengan estos colores
      'text',
      'bg',
      'border',
    ];

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
 	"./index.html",
    	"./src/**/*.{vue,js,ts,jsx,tsx}",
        "./node_modules/flowbite/**/*.js",
        "node_modules/flowbite/**/*.js",
         
    ],

    safelist: [
        // Genera clases como 'text-gray-50', 'text-gray-100', ..., 'text-violet-900', etc.
        ...prefixesToSafelist.flatMap(prefix =>
          colorsToSafelist.flatMap(color =>
            shadesToSafelist.map(shade => `${prefix}-${color}-${shade}`)
          )
        ),
        // Si usas el color DEFAULT sin un tono (ej. bg-dodgerblue),
        // asegúrate de que esa clase también se genere si tu backend lo envía.
        // Esto es solo si tu backend puede enviar 'dodgerblue' sin un tono.
        // Si 'DEFAULT' se mapea a '500' en tu definición de color, y tu backend siempre envía 'color-500',
        // entonces no necesitas estas líneas adicionales.
        // Pero si tu backend puede enviar solo 'dodgerblue' y quieres que aplique DEFAULT, lo necesitarías:
        // ...prefixesToSafelist.flatMap(prefix =>
        //   colorsToSafelist.map(color => `${prefix}-${color}`)
        // ),
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
              dodgerblue: {
                  DEFAULT: '#1e90ff',  // Azul brillante
                  50: '#e6f2ff',       // Muy claro
                  100: '#b3d7ff',      // Claro
                  200: '#80bcff',      // Claro medio
                  300: '#4da1ff',      // Medio
                  400: '#1a86ff',      // Medio oscuro
                  500: '#1e90ff',      // DEFAULT
                  600: '#1767cc',      // Oscuro medio
                  700: '#104d99',      // Oscuro
                  800: '#093366',      // Muy oscuro
                  900: '#021933',      // Extremadamente oscuro
              },
              violet: {
                  DEFAULT: '#ee82ee', // El nuevo color base: Violeta (estándar CSS)
                  50: '#FDF7FD',     // Muy claro, casi blanco
                  100: '#F7EFF7',     // Muy claro
                  200: '#F2E6F2',     // Claro
                  300: '#ECDDEC',     // Claro medio
                  400: '#E6D5E6',     // Medio
                  500: '#ee82ee',     // DEFAULT / Medio oscuro (Manteniendo el DEFAULT en 500)
                  600: '#BF68BF',     // Oscuro medio
                  700: '#904F90',     // Oscuro
                  800: '#603560',     // Muy oscuro
                  900: '#301B30',     // Extremadamente oscuro
              },
              // SlateBlue
              slateblue: {
                DEFAULT: '#6A5ACD', // SlateBlue
                50: '#F0EFFF',     // Muy claro
                100: '#DEDCF6',     // Claro
                200: '#C7C2EE',     // Claro medio
                300: '#AFA9E6',     // Medio
                400: '#9890DD',     // Medio oscuro
                500: '#6A5ACD',     // DEFAULT
                600: '#584CBA',     // Oscuro medio
                700: '#463D9F',     // Oscuro
                800: '#342D73',     // Muy oscuro
                900: '#221D4A',     // Extremadamente oscuro
              },

              // Orange
              orange: {
                DEFAULT: '#FFA500', // Naranja
                50: '#FFF7E6',      // Muy claro
                100: '#FFECC0',     // Claro
                200: '#FFDF99',     // Claro medio
                300: '#FFD173',     // Medio
                400: '#FFC44D',     // Medio oscuro
                500: '#FFA500',     // DEFAULT
                600: '#CC8400',     // Oscuro medio
                700: '#996300',     // Oscuro
                800: '#664200',     // Muy oscuro
                900: '#332100',     // Extremadamente oscuro
              },

              // Tomato
              tomato: {
                DEFAULT: '#FF6347', // Tomate
                50: '#FFF0ED',      // Muy claro
                100: '#FFD9D3',     // Claro
                200: '#FFC0B9',     // Claro medio
                300: '#FFA89F',     // Medio
                400: '#FF9085',     // Medio oscuro
                500: '#FF6347',     // DEFAULT
                600: '#CC4F38',     // Oscuro medio
                700: '#993B2A',     // Oscuro
                800: '#66271C',     // Muy oscuro
                900: '#33130E',     // Extremadamente oscuro
              },

              // MediumSeaGreen
              mediumseagreen: {
                DEFAULT: '#3CB371', // MediumSeaGreen
                50: '#EDFFF4',      // Muy claro
                100: '#D2FDE3',     // Claro
                200: '#B6FBD0',     // Claro medio
                300: '#9AF9BC',     // Medio
                400: '#7EF7A9',     // Medio oscuro
                500: '#3CB371',     // DEFAULT
                600: '#308F5B',     // Oscuro medio
                700: '#246B45',     // Oscuro
                800: '#18472F',     // Muy oscuro
                900: '#0C2318',     // Extremadamente oscuro
              },

            },
        },
    },

    plugins: [forms, require('flowbite/plugin')],
   
};
  