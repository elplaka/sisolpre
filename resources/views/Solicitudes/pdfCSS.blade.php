@font-face {
    font-family: 'FigTree';
    font-weight: normal;
    font-style: normal;
    src: url('{{ storage_path('fonts/Figtree/Figtree-Regular.ttf') }}') format('truetype');
}

@font-face {
    font-family: 'FigTree';
    font-weight: bold;
    font-style: normal;
    src: url('{{ storage_path('fonts/Figtree/Figtree-Bold.ttf') }}') format('truetype');
}

@font-face {
    font-family: 'FigTree';
    font-weight: 600;
    font-style: normal;
    src: url('{{ storage_path('fonts/Figtree/Figtree-SemiBold.ttf') }}') format('truetype');
}

body {
    font-family: 'FigTree';
    font-size: 12pt;
    color: #333;
    line-height: 1.5;
}

h1, h2, h3 {
    font-family: 'FigTree';
    font-weight: 600;
    color: #000;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
}


.titlePrevTd {
    background-color: #f0f0f0; /* gris claro */
    letter-spacing: 0.1em;
    font-weight: bold;
    text-align: center; 
    font-weight: bold;

       /* ***** CÓDIGO CLAVE PARA ELIMINAR EL ESPACIO SUPERIOR ***** */
    line-height: 1em;          /* Reduce la altura de línea si es demasiado grande */
    vertical-align: middle;       /* Asegura que el contenido se alinee arriba */
    margin: 0 !important;      /* Asegura que no haya márgenes externos en la celda */
    height: auto;              /* Asegura que la altura de la celda se ajuste al contenido */
    /* ********************************************************** */
}

td {
    font-size: 11pt;
}

