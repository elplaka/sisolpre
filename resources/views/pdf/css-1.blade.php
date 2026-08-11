@page {
/* Margen: superior | derecho | inferior | izquierdo */
margin: 3cm 2cm 2cm 2cm;
}

/* --- LIGHT (300) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Light.ttf") }}') format('truetype');
font-weight: 300;
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-LightItalic.ttf") }}') format('truetype');
font-weight: 300;
font-style: italic;
}

/* --- REGULAR (400) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Regular.ttf") }}') format('truetype');
font-weight: normal; /* o 400 */
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Italic.ttf") }}') format('truetype');
font-weight: normal;
font-style: italic;
}

/* --- MEDIUM (500) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Medium.ttf") }}') format('truetype');
font-weight: 500;
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-MediumItalic.ttf") }}') format('truetype');
font-weight: 500;
font-style: italic;
}

/* --- SEMIBOLD (600) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-SemiBold.ttf") }}') format('truetype');
font-weight: 600;
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-SemiBoldItalic.ttf") }}') format('truetype');
font-weight: 600;
font-style: italic;
}

/* --- BOLD (700) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Bold.ttf") }}') format('truetype');
font-weight: bold; /* o 700 */
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-BoldItalic.ttf") }}') format('truetype');
font-weight: bold;
font-style: italic;
}

/* --- EXTRA BOLD (800) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-ExtraBold.ttf") }}') format('truetype');
font-weight: 800;
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-ExtraBoldItalic.ttf") }}') format('truetype');
font-weight: 800;
font-style: italic;
}

/* --- BLACK (900) --- */
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-Black.ttf") }}') format('truetype');
font-weight: 900;
font-style: normal;
}
@font-face {
font-family: 'FigTree';
src: url('{{ storage_path("fonts/Figtree/Figtree-BlackItalic.ttf") }}') format('truetype');
font-weight: 900;
font-style: italic;
}

body {
font-family: 'FigTree', sans-serif;
font-size: 10pt;
line-height: 1.5;
color: #333333;
margin: 0;
}

table {
color: inherit;
font-size: 10pt;
}

h2, h3 {
color: #444444;
}