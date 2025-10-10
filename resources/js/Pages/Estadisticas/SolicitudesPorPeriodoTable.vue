<script setup>
import { computed, onMounted } from 'vue';

// Definimos los props que recibirá el componente desde Inertia
const props = defineProps({
    solicitudes: {
        type: Array,
        required: true,
    },
    totalSolicitudes: { // Nuevo prop
        type: Number,
        required: true,
    },
    // Este prop es opcional para determinar si se agrupa por día o mes
    agruparPorDia: {
        type: Boolean,
    },
});

// Propiedad computada para calcular el porcentaje
const solicitudesConPorcentaje = computed(() => {
    return props.solicitudes.map(tramite => ({
        ...solicitud,
        porcentaje: ((solicitud.total / props.totalSolicitudes) * 100).toFixed(2)
    }));
});


// onMounted(() => {
//     console.log('Agrupar por día:', props.agruparPorDia);
// })

// Lógica para formatear el mes
const getMonthName = (monthNumber) => {
    // 1. Crea una nueva fecha para evitar modificar la fecha actual del usuario.
    const date = new Date();
    // 2. Establece el mes. Los meses en JavaScript van de 0 a 11, por eso se resta 1.
    date.setMonth(monthNumber - 1);
    // 3. Obtiene el nombre completo del mes en español.
    const monthName = date.toLocaleString('es-MX', { month: 'long' });
    // 4. Capitaliza la primera letra y concatena el resto del nombre.
    return monthName.charAt(0).toUpperCase() + monthName.slice(1, 3);
};

// Título de la columna, se ajusta automáticamente
const columnaPrincipal = computed(() => {
    return props.agruparPorDia ? 'Día' : 'Mes';
});

// Propiedad computada para calcular porcentaje, formatear datos y ordenar en un solo lugar
const solicitudesFormateadasYOrdenadas = computed(() => {
    // 1. Mapea la colección para agregar el porcentaje y formatear la fecha
    const solicitudesMapeadas = props.solicitudes.map(solicitud => {
        const porcentaje = ((solicitud.total / props.totalSolicitudes) * 100).toFixed(2);
        
        let fechaFormateada;
        if (props.agruparPorDia) {
            fechaFormateada = solicitud.day.toString().padStart(2, '0') + '/' + getMonthName(solicitud.month) + '/' + solicitud.year;
        } else {
            fechaFormateada = getMonthName(solicitud.month) + '/' + solicitud.year;
        }

        return {
            ...solicitud,
            porcentaje: porcentaje,
            fechaFormateada: fechaFormateada,
        };
    });

    // 2. Invierte el array para ordenar
    return solicitudesMapeadas.reverse();
});

const exportarComoXLS = () => {
  const tabla = document.querySelector('table');
  if (!tabla) return;

  const tablaHTML = tabla.outerHTML;
  const BOM = "\uFEFF"; // Para compatibilidad con acentos
  const blob = new Blob([BOM + tablaHTML], {
    type: 'application/vnd.ms-excel;charset=utf-8;',
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'reporte_solicitudes.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};
</script>

<template>
    <div class="flex justify-center w-full mt-5 mb-3">
        <div class="overflow-x-auto w-full max-w-4xl rounded-xl">
            <table v-if="solicitudes.length > 0" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 border-collapse">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-2 cursor-pointer md:text-base text-gray-600 dark:text-gray-300">
                            {{ columnaPrincipal }}
                        </th>
                        <th scope="col" class="px-6 py-2 cursor-pointer md:text-base text-gray-600 dark:text-gray-300">
                            Número de Solicitudes
                        </th>
                        <th scope="col" class="px-6 py-2 md:text-base text-gray-600 dark:text-gray-300">
                            Porcentaje
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(solicitud, index) in solicitudesFormateadasYOrdenadas" :key="index" :class="{'bg-white dark:bg-gray-800': index % 2 === 0, 'bg-gray-50 dark:bg-gray-700': index % 2 !== 0, 'border-b dark:border-gray-700': true, 'hover:bg-gray-100 dark:hover:bg-gray-600': true}">
                        <td class="px-6 py-2 font-medium text-gray-600 dark:text-white whitespace-nowrap">
                            <template v-if="agruparPorDia">
                                {{ solicitud.day.toString().padStart(2, '0') + '/' + getMonthName(solicitud.month) + '/' + solicitud.year }}
                            </template>
                            <template v-else>
                                {{ getMonthName(solicitud.month) + '/' + solicitud.year }}
                            </template>
                        </td>
                        <td class="px-6 py-2 text-gray-600 dark:text-white">
                            {{ solicitud.total }}
                        </td>
                          <td class="px-6 py-2 text-gray-600 dark:text-white">
                            {{ solicitud.porcentaje }}%
                        </td>
                    </tr>
                </tbody>
                
            </table>
            <div class="flex justify-end p-4">
                <button 
                    @click="exportarComoXLS" 
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white font-normal rounded-lg hover:bg-green-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-file-spreadsheet">
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                        <path d="M8 11h8v7h-8z" /><path d="M8 15h8" /><path d="M11 11v7" />
                    </svg>
                    &nbsp; Exportar a Excel
                </button>
            </div>
        </div>
    </div>
</template>

