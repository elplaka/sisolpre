<template>
    <div class="bg-white dark:bg-gray-800 px-1 pb-0 m-0 flex flex-col justify-between h-full">
        
        <!-- Cabecera -->
        <div class="flex items-center justify-between mb-1 px-1">
            <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate">
                Top 5 Localidades
            </span>
            
            <div class="p-1.5 rounded-lg bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>

        <!-- Contenedor del Gráfico Chart.js -->
        <div class="relative w-full flex-grow min-h-[160px] sm:min-h-[140px]">
            <template v-if="panelData && panelData.length > 0">
                <Bar :data="chartData" :options="chartOptions" />
            </template>

            <!-- Estado Vacío -->
            <div v-else class="flex items-center justify-center h-full text-center text-gray-400 py-4 text-xs italic">
                Sin datos suficientes para mostrar.
            </div>
        </div>

    </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    BarElement,
    CategoryScale, LinearScale,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { defineProps, ref, computed, onMounted, onUnmounted } from 'vue';

ChartJS.register(
    Title, Tooltip, Legend,
    BarElement,
    CategoryScale, LinearScale,
    ChartDataLabels
);

ChartJS.defaults.font.family = 'Figtree, sans-serif';

const props = defineProps({
    panelData: {
        type: Array,
        required: true,
        default: () => []
    },
});

const barColors = ref([]);
const isMobile = ref(false);

const checkScreenSize = () => {
    isMobile.value = window.innerWidth < 640;
};

onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);

    barColors.value = [
        getTailwindColor('color1-900'),
        getTailwindColor('color1-800'),
        getTailwindColor('color1-700'),
        getTailwindColor('color1-600'),
        getTailwindColor('color1-500'),

    ];
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize);
});

const getTailwindColor = (className) => {
    if (typeof document === 'undefined') {
        return '#000000';
    }
    const div = document.createElement('div');
    div.className = `bg-${className} hidden`;
    document.body.appendChild(div);
    const color = getComputedStyle(div).backgroundColor;
    document.body.removeChild(div);
    return color;
};

const chartData = computed(() => {
    const labels = [];
    const totals = [];
    const backgroundColors = [];

    const sortedData = [...props.panelData].sort((a, b) => b.total - a.total);

    sortedData.forEach((data, index) => {
        let nombre = data.localidad_nombre || data.nombre_abreviado || 'N/D';
        
        // 👈 Truncamiento adaptativo:
        // En celular (barras horizontales), el espacio lateral es más reducido, cortamos a 12 caracteres.
        // En computadora (barras verticales), hay un poco más de margen horizontal, permitimos hasta 15 caracteres.
        const maxLength = isMobile.value ? 8 : 15;
        if (nombre.length > maxLength) {
            nombre = nombre.substring(0, maxLength - 2) + '...';
        }

        labels.push(nombre);
        totals.push(data.total);
        backgroundColors.push(barColors.value[index % barColors.value.length]);
    });

    return {
        labels: labels,
        datasets: [{
            label: 'Solicitudes',
            data: totals,
            backgroundColor: backgroundColors,
            borderRadius: 4
        }]
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: isMobile.value ? 'y' : 'x',
    layout: {
        padding: { 
            top: isMobile.value ? 8 : 20, 
            right: isMobile.value ? 35 : 50, // Margen derecho extra en móvil para que el número al final de la barra no se corte
            bottom: 0 
        }
    },
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            enabled: true,
            callbacks: {
                // 💡 Nota útil: Si el usuario pasa el cursor/tap sobre la barra truncada, 
                // el tooltip muestra el nombre completo original gracias a los datos de 'panelData'.
                title: function(context) {
                    const index = context[0].dataIndex;
                    const originalItem = [...props.panelData].sort((a, b) => b.total - a.total)[index];
                    return originalItem.localidad_nombre || originalItem.nombre_abreviado || 'N/D';
                },
                label: function (context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    const value = isMobile.value ? context.parsed.x : context.parsed.y;
                    if (value !== null) {
                        label += new Intl.NumberFormat('es-MX').format(value);
                    }
                    return label;
                }
            }
        },
        datalabels: {
            color: '#6b7280',
            font: { weight: 'bold', size: 9 },
            formatter: value => new Intl.NumberFormat('es-MX').format(value),
            anchor: 'end',
            align: isMobile.value ? 'right' : 'top',
            offset: 2
        }
    },
    scales: {
        x: {
            grid: { display: false, drawBorder: false },
            ticks: {
                display: !isMobile.value,
                color: '#6b7280',
                font: { size: 9 },
                padding: 2,
                autoSkip: false,
                maxRotation: 0,
            },
            beginAtZero: true,
            border: { display: false }
        },
       y: {
            grid: { display: false, drawBorder: false },
            ticks: {
                display: false,
                color: '#6b7280',
                font: { size: 9 },
                autoSkip: false,
                precision: 0,
                stepSize: 1,
                callback: function(value) {
                    return Number.isInteger(Number(value)) ? value : '';
                }
            },
            beginAtZero: true,
            border: { display: false }
        }
    }
}));
</script>