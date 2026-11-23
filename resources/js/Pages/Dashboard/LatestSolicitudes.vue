<script setup>
    import { defineProps } from 'vue';
    import { router } from '@inertiajs/vue3';

    const props = defineProps({
        panelData: Array, // Este prop contendrá los datos específicos para este panel (ej: latestSolicitudes)
    });

    const formatFecha = (dateString) => {
        if (!dateString) return 'N/A'; // Manejar el caso de fecha nula o indefinida

        const parts = dateString.split('-'); // Divide "2025-08-11" en ["2025", "08", "11"]
        
        // El constructor de Date usa el mes como índice base 0, por eso se resta 1
        const date = new Date(parts[0], parts[1] - 1, parts[2]);

        // Verificar si la fecha es válida
        if (isNaN(date.getTime())) {
            return 'Fecha inválida';
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}-${month}-${year}`;
    };

    function formatFechaLarga(fechaStr) {
        if (!fechaStr) return '—';
        const [fecha] = String(fechaStr).trim().split(' '); // "2026-11-22"
        const [anio, mes, dia] = fecha.split('-');
        if (!anio || !mes || !dia) return fechaStr;
        return `${dia.padStart(2, '0')}-${mes.padStart(2, '0')}-${anio}`;
    }

    // Nueva función para formatear el folio a 4 dígitos con ceros a la izquierda
    const formatFolio = (folio) => {
        if (folio === null || folio === undefined) {
            return 'N/A';
        }
        // Convertir a número y luego a cadena para asegurar el relleno
        return String(Number(folio)).padStart(4, '0');
    };



     const truncateText = (text, maxLength = 48) => {
        if (!text) {
            return '';
        }
        // Convertir a cadena y verificar si la longitud supera el límite
        if (text.length > maxLength) {
            return text.substring(0, maxLength) + '...';
        }
        return text;
    };

    const getStatusColor = (colorKey) => {
            const colors = {
                success: '#10B981', // Verde
                warning: '#F59E0B', // Amarillo / Naranja
                danger: '#EF4444',  // Rojo
                info: '#3B82F6',    // Azul
                primary: '#6366F1', // Índigo / Tu color principal
                secondary: '#6B7280' // Gris
            };

            // Si viene un hex o rgb directo, lo respeta; si viene una palabra, la busca; si no hay nada, usa gris por defecto.
            return colors[colorKey] || colorKey || '#9CA3AF';
        };

        function timeAgo(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return `hace ${interval} año${interval === 1 ? '' : 's'}`;

        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return `hace ${interval} mes${interval === 1 ? '' : 'es'}`;

        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return `hace ${interval} día${interval === 1 ? '' : 's'}`;

        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return `hace ${interval} hora${interval === 1 ? '' : 's'}`;

        interval = Math.floor(seconds / 60);
        if (interval >= 1) return `hace ${interval} minuto${interval === 1 ? '' : 's'}`;

        return 'hace unos momentos';
    }

    function verEditarSolicitud(id) {
        router.put('/solicitudes/editar', {
            id_solicitud: id
        });
    }

    function nuevaSolicitud() {

        router.get(
            '/solicitudes/nueva',
            {
                onStart: () => { // <--- Inicia el estado de carga
                },
                onFinish: () => { // <--- Termina el estado de carga
                }
            }
        )
    }
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
<div class="bg-white p-0 m-0 min-h-[180px]">
    <!-- Título y Botón de Nueva Solicitud en la parte superior derecha -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <!-- Título -->
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 transition-colors duration-300 group-hover:text-gray-700 dark:group-hover:text-gray-300">
            Últimas Solicitudes
        </span>
        
        <!-- Botón con tipografía escalada y responsive -->
        <button 
            @click="nuevaSolicitud"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-color1-700 hover:bg-color1-800 active:scale-95 text-white text-xs sm:text-sm font-semibold rounded-xl sm:rounded-full shadow-sm transition-all duration-150 cursor-pointer">
            <!-- Icono adaptado al tamaño de fuente -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nueva Solicitud</span>
        </button>
    </div>

    <div v-if="panelData && panelData.length > 0" class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-[0.65rem] uppercase tracking-wider font-bold text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th class="py-2.5 px-3 text-center">Folio</th>
                    <th class="py-2.5 px-3">Fechas</th>
                    <th class="py-2.5 px-3">Solicitante / Petición</th>
                    <th class="py-2.5 px-3 text-center">Estatus</th>
                    <th class="py-2.5 px-3  text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-[0.7rem]">
                <tr v-for="solicitud in panelData" :key="solicitud.id" 
                    @click="(solicitud.id_estatus !== 4) ? verEditarSolicitud(solicitud.id) : null" 
                    :class="[
                        'transition-colors',
                        (solicitud.id_estatus === 4) 
                            ? 'bg-gray-50/70 dark:bg-gray-800/40 cursor-not-allowed select-none' 
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer'
                    ]">
                    
                    <!-- Folio con pequeño badge integrado -->
                    <td class="py-3 px-1 text-center">
                        <span :class="[
                            'font-bold px-2 py-1 rounded-md text-[0.65rem] inline-flex items-center gap-1',
                            (solicitud.id_estatus === 4)
                                ? 'bg-gray-200/70 text-gray-600 dark:bg-gray-700 dark:text-gray-400' 
                                : 'bg-color1-40 dark:bg-color1-950/40 text-color1-700 dark:text-color1-300'
                        ]">
                            <svg v-if="solicitud.id_estatus === 4" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ formatFolio(solicitud.id) }}
                        </span>
                    </td>

                    <!-- Fechas agrupadas -->
                    <td class="py-3 px-3 text-gray-500 text-[0.65rem] max-w-[130px]">
                        <div class="truncate">Ingreso: {{ formatFecha(solicitud.fecha) }}</div>
                        <div v-if="solicitud.id_estatus === 3" class="text-gray-400 truncate">Cierre: {{ formatFechaLarga(solicitud.fecha_resolucion)  }}</div>
                        <div v-else class="text-gray-400 truncate">Modif. {{ timeAgo(solicitud.updated_at) }}</div>

                    </td>

                    <!-- Solicitante y Petición -->
                    <td class="py-3 px-3 max-w-[210px]">
                        <div class="font-bold text-gray-600 truncate dark:text-gray-200">
                            {{ solicitud.solicitante?.nombre + ' ' + solicitud.solicitante?.apellidos }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 truncate text-[0.65rem]">
                            {{ solicitud.peticion }}
                        </div>
                    </td>           

                    <td class="py-3 px-3 text-center whitespace-nowrap">
                        <span class="inline-block font-semibold text-[0.65rem] px-3 py-1 rounded"
                            :style="{ 
                                color: getStatusColor(solicitud.estatus.color), 
                                backgroundColor: getStatusColor(solicitud.estatus.color) + '15' 
                            }">
                            {{ solicitud.estatus.nombre }}
                        </span>
                    </td>

                    <td class="py-3 px-3 text-right whitespace-nowrap font-bold text-gray-600 dark:text-gray-200">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <span
                                class="inline-flex items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium"
                                :class="solicitud.id_tipo_apoyo === 1 ? 'bg-color2-50/80' : 'bg-color4-50/80'">
                                <svg v-if="solicitud.id_tipo_apoyo === 1"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-3.5 text-color2-800 transition-transform duration-200 group-hover:scale-110"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect width="20" height="12" x="2" y="6" rx="2"/>
                                    <circle cx="12" cy="12" r="2"/>
                                    <path d="M6 12h.01M18 12h.01"/>
                                </svg>
                                <svg v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-3.5 h-3 text-color4-800 transition-transform duration-200 group-hover:scale-110"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                    <path d="m3.3 7 8.7 5 8.7-5"/>
                                    <path d="M12 22V12"/>
                                </svg>
                            </span>
                            <span v-if="solicitud.cantidad_aprobada">$ {{ Number(solicitud.cantidad_aprobada).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} 
                            </span>
                            
                            <span v-else class="text-gray-400 font-normal">—</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div v-else class="text-center py-6 text-gray-400 text-xs">Sin registros recientes.</div>
</div>
</template>