<script setup>
    import { defineProps } from 'vue';

    const props = defineProps({
        panelData: Array, // Este prop contendrá los datos específicos para este panel (ej: latestSolicitudes)
    });

    // Función para formatear la fecha
    const formatFecha = (dateString) => {
        if (!dateString) return 'N/A'; // Manejar el caso de fecha nula o indefinida

        const date = new Date(dateString);

        // Verificar si la fecha es válida
        if (isNaN(date.getTime())) {
            return 'Fecha inválida';
        }

        const day = String(date.getDate()).padStart(2, '0'); // Añade un 0 al principio si es necesario
        const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() es base 0, por eso +1
        const year = date.getFullYear();

        return `${day}-${month}-${year}`;
    };

    // Nueva función para formatear el folio a 4 dígitos con ceros a la izquierda
    const formatFolio = (folio) => {
        if (folio === null || folio === undefined) {
            return 'N/A';
        }
        // Convertir a número y luego a cadena para asegurar el relleno
        return String(Number(folio)).padStart(4, '0');
    };

    // Función para obtener la clase de color del SVG basado en el estatus
    // const getStatusSvgColorClass = (estatus) => {
    //     if (estatus && estatus.color) { // <-- ¡Aquí asumimos 'color_name' es la columna!
    //         return `text-${estatus.color}-500`; // Construimos la clase de Tailwind, ej: 'text-violet-500'
    //     }
    //     // Color por defecto si no hay estatus o color definido
    //     return 'text-gray-500';
    // };

    // Función para obtener el color del texto del estatus
    const getStatusTextColorClass = (estatus) => {
        if (estatus && estatus.color) { // <-- ¡Aquí asumimos 'color_name' es la columna!
            return `text-${estatus.color}-500`; // Construimos la clase de Tailwind, ej: 'text-violet-500'
        }
        return 'text-gray-600'; // Color por defecto para el texto del estatus
    };

    const formatClaveCatastral = (clave) => {
        if (typeof clave !== 'string' || clave.length !== 18 || !/^\d+$/.test(clave)) {
            // Si no es una cadena, no tiene 18 dígitos o no son solo números, retorna la original o un mensaje de error
            return clave; // O 'Formato inválido' si prefieres
        }

        // Divide la cadena en grupos de 3 dígitos
        let formattedClave = '';
        for (let i = 0; i < clave.length; i += 3) {
            formattedClave += clave.substring(i, i + 3) + ' ';
        }

        // Elimina el espacio extra al final y retorna
        return formattedClave.trim();
    };

    const getDireccion = (propiedad) => {
    // Si la propiedad es nula o no tiene datos de dirección en sus campos principales
    if (!propiedad || (!propiedad.calle && !propiedad.numero && !propiedad.colonia?.nombre && !propiedad.localidad?.nombre)) {
        return 'SIN DOMICILIO';
    }

    let direccion = '';

    // Agregar calle si existe
    if (propiedad.calle) {
        direccion += propiedad.calle;
    }

    // Agregar número si existe
    if (propiedad.numero) {
        // Agregar ' N° ' solo si la calle no está vacía
        direccion += (propiedad.calle ? ' N° ' : 'N° ') + propiedad.numero;
    }

    // Agregar colonia si existe la relación y tiene un nombre
    if (propiedad.colonia?.nombre) {
        let nombreColonia = propiedad.colonia.nombre.toUpperCase(); // Convertir a mayúsculas para la comparación
        let prefijo = '';

        // Comprobar si el nombre de la colonia no empieza con 'FRAC' ni 'INFONAV'
        if (!nombreColonia.startsWith('FRAC') && !nombreColonia.startsWith('INFONAV')) {
            prefijo = 'COL. ';
        }

        direccion += (direccion ? ', ' : '') + prefijo + propiedad.colonia.nombre;
    }

    // Agregar localidad si existe la relación y tiene un nombre
    if (propiedad.localidad?.nombre) {
        direccion += (direccion ? ', ' : '') + propiedad.localidad.nombre;
    }

    return direccion;
};
</script>

<style>
    .custom-small-tooltip {
        font-size: 0.6rem !important; /* Ajusta este valor. 0.75rem = 12px (text-xs de Tailwind) */
        padding: 0.25rem 0.5rem !important; /* También puedes reducir el padding para un tooltip más compacto */
        min-width: unset !important; /* Asegura que el tooltip no tenga un ancho mínimo si lo tenía */
        max-width: 120px !important; /* Limita el ancho para que se envuelva el texto si es largo */
        white-space: normal !important; /* Permite que el texto se envuelva */
    }

    /* NUEVA CLASE para el tooltip rectangular sin flecha */
    .custom-rectangle-tooltip .el-popper__arrow {
        display: none !important; /* Oculta la flecha */
    }

    /* Opcional: Ajusta el borde redondeado del tooltip si es necesario */
    /* Element UI ya usa bordes redondeados por defecto, pero puedes personalizarlos */
    .custom-rectangle-tooltip {
        border-radius: 0.25rem !important; /* Ejemplo: 4px de radio de borde (rounded de Tailwind) */
        border: 1px solid #e5e7eb !important; 
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1) !important;
    }
</style>

<template>
    <div class="bg-white p-0 m-0 min-h-[180px] sm:min-h-[180px] md:min-h-[180px]">
        <h3 class="text-lg font-semibold mb-2 text-center md:text-left">Últimas Solicitudes</h3>
        <div v-if="panelData && panelData.length > 0">
            <ul class="space-y-5">
                <li v-for="solicitud in panelData" :key="solicitud.id" class="border-b border-gray-200 last:border-b-0 last:mb-0 pb-0">
                           <div class="flex flex-col md:flex-row md:justify-between md:items-center text-[0.75rem] gap-y-1 md:gap-y-0">

                        <span class="flex items-center gap-x-4 font-medium flex-wrap md:flex-nowrap md:flex-initial">
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                   <path fill-rule="evenodd" d="M11.097 1.515a.75.75 0 0 1 .589.882L10.666 7.5h4.47l1.079-5.397a.75.75 0 1 1 1.47.294L16.665 7.5h3.585a.75.75 0 0 1 0 1.5h-3.885l-1.2 6h3.585a.75.75 0 0 1 0 1.5h-3.885l-1.08 5.397a.75.75 0 1 1-1.47-.294l1.02-5.103h-4.47l-1.08 5.397a.75.75 0 1 1-1.47-.294l1.02-5.103H3.75a.75.75 0 0 1 0-1.5h3.885l1.2-6H5.25a.75.75 0 0 1 0-1.5h3.885l1.08-5.397a.75.75 0 0 1 .882-.588ZM10.365 9l-1.2 6h4.47l1.2-6h-4.47Z" clip-rule="evenodd" />
                                </svg>
                                <span>
                                    {{ formatFolio(solicitud.id) }}
                                </span>
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                     <path d="M12.75 12.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM7.5 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM8.25 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM9.75 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM10.5 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM12.75 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM14.25 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM15 17.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM16.5 15.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5ZM15 12.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM16.5 13.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
                                 <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clip-rule="evenodd" />
                                </svg>
                                <span>
                                    {{ formatFecha(solicitud.fecha_ingreso) }}
                                </span>
                            </span>
                        </span>

                        <span class="flex items-center gap-1 md:text-right md:flex-initial md:ml-auto min-w-0" :data-tooltip-target="'tooltip-propiedad-' + solicitud.id">
                            <svg
                                class="w-3 h-3 text-gray-500 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 -960 960 960">
                                <path d="M80-120v-650l200-150 200 150v90h400v560H80Zm80-80h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 0h80v-80h-80v80Zm0 480h480v-400H320v400Zm240-240v-80h160v80H560Zm0 160v-80h160v80H560ZM400-440v-80h80v80h-80Zm0 160v-80h80v80h-80Z"/>
                            </svg>
                            <span class="truncate"> {{ solicitud.propiedad.clave_catastral ? formatClaveCatastral(solicitud.propiedad.clave_catastral) : 'N/A' }}
                            </span>
                        </span>
                    </div>

                    <p class="text-[0.75rem] flex items-center gap-1 font-bold" :class="getStatusTextColorClass(solicitud.estatus)">
                        <svg :class="getStatusTextColorClass(solicitud.estatus)" class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                        {{
                        (solicitud.contacto?.persona?.nombre + ' ' + solicitud.contacto?.persona?.apellidos || 'N/A') +
                        (
                            solicitud.propiedad?.contacto?.persona && solicitud.id_contacto !== solicitud.propiedad.id_contacto
                            ? ' / ' + solicitud.propiedad.contacto.persona.nombre + ' ' + solicitud.propiedad.contacto.persona.apellidos
                            : ''
                        )
                        }}
                    </p>
                    <div :id="'tooltip-propiedad-' + solicitud.id" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-[0.625rem] font-medium text-white transition-opacity duration-300 bg-color1-500 rounded-lg shadow-xs opacity-90 tooltip dark:bg-gray-700">
                        {{ getDireccion(solicitud.propiedad) }}
                        <div class="tooltip-arrow opacity-90" data-popper-arrow></div>
                    </div>
                </li>
            </ul>
        </div>
        <div v-else>
            <p class="text-gray-500">No hay solicitudes recientes.</p>
        </div>
    </div>
</template>