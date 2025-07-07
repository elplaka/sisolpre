    @font-face {
        font-family: 'FigTree';
        font-weight: normal;
        font-style: normal;
        src: url('{{ storage_path('fonts/Figtree/Figtree-Regular.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'FigTree';
        font-weight: 900;
        font-style: normal;
        src: url('{{ storage_path('fonts/Figtree/Figtree-Black.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'FigTree';
        font-weight: 800;
        font-style: normal;
        src: url('{{ storage_path('fonts/Figtree/Figtree-ExtraBold.ttf') }}') format('truetype');
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

    @font-face {
        font-family: 'FigTree';
        font-weight: normal;
        font-style: italic;
        src: url('{{ storage_path('fonts/Figtree/Figtree-Italic.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'FigTree';
        font-weight: bold;
        font-style: italic;
        src: url('{{ storage_path('fonts/Figtree/Figtree-BoldItalic.ttf') }}') format('truetype');
    }


    @page {
        margin: 8mm 15mm 4mm 15mm; /* top, right, bottom, left */
    }


    body {
        font-family: 'FigTree';
        font-size: 12pt;
        color: #333;
        line-height: 1.5;
        margin: 0;
        padding: 0;
    }

h1, h2, h3 {
    font-family: 'FigTree';
    color: #000;
    text-align: center;
}

h1 {
    font-weight: 800;
    font-size: 14pt;
}

h2 {
    font-weight: 600;
    font-size: 13pt;
}

h3 {
    font-weight: 600;
    font-size: 12pt;
}


table {
    width: 100%;
    border-collapse: collapse;
}


.titlePrevTd {
    letter-spacing: 0.1em;
    font-weight: bold;
    text-align: center; 
    font-weight: bold;

    line-height: 1em;          /* Reduce la altura de línea si es demasiado grande */
    vertical-align: middle;       /* Asegura que el contenido se alinee arriba */
    margin: 0 !important;      /* Asegura que no haya márgenes externos en la celda */
    height: auto;              /* Asegura que la altura de la celda se ajuste al contenido */

    border: 1px solid #ccc;
}

.titleTd {
    letter-spacing: 0.1em;
    font-weight: bold;
    text-align: center; 
    font-weight: bold;

    line-height: 1em;          /* Reduce la altura de línea si es demasiado grande */
    vertical-align: middle;       /* Asegura que el contenido se alinee arriba */
    margin: 0 !important;      /* Asegura que no haya márgenes externos en la celda */
    height: auto;              /* Asegura que la altura de la celda se ajuste al contenido */

    background-color: #dadada;
}

td {
    font-size: 10pt;
}

.tdPrevFieldName {
    font-size: 9pt;
    text-align: right; 
    font-weight: bold;
}

ul {
    font-size: 10pt;
}

th
{
    font-size: 10pt;
}

.cuadro-checkbox {
    width: 14px;
    height: 14px;
    border: 1.5px solid #888;
    border-radius: 4px;
    background-color: #fff;
    font-size: 18pt;
    line-height: 14px;
    text-align: center;
    color: black;
    font-family: DejaVu Sans, sans-serif; /* Para Dompdf */
}

