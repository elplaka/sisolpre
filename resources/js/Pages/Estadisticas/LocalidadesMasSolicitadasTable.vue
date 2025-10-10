<script setup>
import { computed } from 'vue';

// Definimos los props que recibirá el componente desde Inertia
const props = defineProps({
    localidadesMasSolicitadas: {
        type: Array,
        required: true,
    },
    totalSolicitudes: {
        type: Number,
        required: true,
    },
});

// Propiedad computada para agregar el porcentaje a cada localidad
const localidadesConPorcentaje = computed(() => {
    // Si no hay solicitudes, devuelve un array vacío para evitar errores de división por cero
    if (props.totalSolicitudes === 0) {
        return [];
    }
    
    // Mapea la colección de localidades para agregar el porcentaje
    return props.localidadesMasSolicitadas.map(localidad => ({
        ...localidad,
        porcentaje: ((localidad.total / props.totalSolicitudes) * 100).toFixed(2)
    }));
});

// Lógica de exportación de la tabla a Excel
const exportarComoXLS = () => {
    const tabla = document.getElementById('localidades-ranking-table');
    if (!tabla) return;

    const tablaHTML = tabla.outerHTML;
    const BOM = "\uFEFF"; // Para compatibilidad con acentos
    const blob = new Blob([BOM + tablaHTML], {
        type: 'application/vnd.ms-excel;charset=utf-8;',
    });

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ranking_localidades.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>

<template>
    <div class="flex justify-center w-full mt-5 mb-3">
        <div class="overflow-x-auto w-full max-w-4xl rounded-xl">
            <table v-if="localidadesMasSolicitadas.length > 0" id="localidades-ranking-table" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-2 md:text-base text-gray-600 dark:text-gray-300">
                            Localidad
                        </th>
                        <th scope="col" class="px-6 py-2 md:text-base text-gray-600 dark:text-gray-300">
                            Número de Solicitudes
                        </th>
                        <th scope="col" class="px-6 py-2 md:text-base text-gray-600 dark:text-gray-300">
                            Porcentaje
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(localidad, index) in localidadesConPorcentaje" :key="index" :class="{'bg-white dark:bg-gray-800': index % 2 === 0, 'bg-gray-50 dark:bg-gray-700': index % 2 !== 0, 'border-b dark:border-gray-700': true, 'hover:bg-gray-100 dark:hover:bg-gray-600': true}">
                        <td class="px-6 py-2 font-medium text-gray-600 dark:text-white whitespace-nowrap">
                            {{ localidad.localidad_nombre }}
                        </td>
                        <td class="px-6 py-2 text-gray-600 dark:text-white">
                            {{ localidad.total }}
                        </td>
                        <td class="px-6 py-2 text-gray-600 dark:text-white">
                            {{ localidad.porcentaje }}%
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div v-if="localidadesMasSolicitadas.length == 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                No hay solicitudes de localidades en el rango de fechas seleccionado.
            </div>

            <div class="flex flex-col md:flex-row md:justify-end w-full p-4">
                <button
                    @click="exportarComoXLS"
                    class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-500 text-white font-normal rounded-lg hover:bg-green-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-spreadsheet">
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                        <path d="M8 11h8v7h-8z" /><path d="M8 15h8" /><path d="M11 11v7" />
                    </svg>
                    &nbsp; Exportar a Excel
                </button>
            </div>
        </div>
    </div>
</template>