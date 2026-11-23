<template>
    <div class="bg-white dark:bg-gray-800 px-3 pt-2 pb-1 m-0 flex flex-col justify-between">
        <!-- ================================= -->
        <!-- CABECERA -->
        <!-- ================================= -->
        <div class="flex items-center justify-between mb-0 px-1">
            <!-- Título -->
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate transition-colors duration-300 group-hover:text-color1-700 dark:group-hover:text-color1-300">
                    Solicitudes
                </span>
            </div>

            <!-- Indicador -->
            <div v-if="chartData.labels.length > 0" class="p-1.5 flex items-center gap-1.5 flex-shrink-0 text-[10px] sm:text-[11px] font-medium text-gray-400 dark:text-gray-500">
                <span class="w-1.5 h-1.5 rounded-full bg-color1-500"></span>
                <span>Evolución de los últimos 6 meses</span>
            </div>
        </div>

        <!-- ================================= -->
        <!-- GRÁFICO -->
        <!-- ================================= -->
        <div v-if="chartData.labels.length > 0" :class="chartContainerClass">
            <div class="relative w-full h-full min-w-0">
                <Line :data="chartData" :options="chartOptions" class="w-full h-full" />
            </div>
        </div>

        <!-- ================================= -->
        <!-- SIN DATOS -->
        <!-- ================================= -->
        <div v-else class="min-h-[125px] flex flex-col items-center justify-center text-center px-4">
            <div class="w-9 h-9 mb-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l4-5 3 3 4-6" />
                </svg>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500">
                Sin datos disponibles para este periodo.
            </span>
        </div>
        <div class="mt-2.5 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
            <span class="text-[11px] text-gray-400 dark:text-gray-500 italic">
                Reporte de solicitudes 
            </span>
            
            <!-- Tu snippet exacto de UI -->
            <div class="flex items-center gap-1 text-[11px] font-semibold text-color1-700 dark:text-color1-400 opacity-80 group-hover:opacity-100 transition-opacity">
                <span class="hidden sm:inline uppercase tracking-wider text-[10px]">Ver</span>
                <svg class="w-3.5 h-3.5 transform transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, ref, computed, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    ChartDataLabels
);

ChartJS.defaults.font.family = 'Figtree, sans-serif';

const props = defineProps({
    kpisAtencionCiudadana: {
        type: Object,
        default: () => ({})
    },
    panelData: {
        type: Array,
        required: true
    },
    userAuth: {
        type: Object,
        default: null
    }
});

const chartContainerClass = computed(() => {
    const isAuxiliar = props.userAuth?.roles?.some(
        role => role.name === 'AUXILIAR'
    );

    return [
        'w-full',
        'min-w-0',
        'relative',
        'mt-2',
        isAuxiliar
            ? 'h-[200px] sm:h-[220px] lg:h-[240px]'
            : 'h-[100px] sm:h-[110px] lg:h-[120px]'
    ];
});

const monthNames = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

const lineColor = ref('#0284c7');

const getTailwindColor = (className) => {
    if (typeof document === 'undefined') return lineColor.value;
    const div = document.createElement('div');
    div.className = `bg-${className} hidden`;
    document.body.appendChild(div);
    const color = getComputedStyle(div).backgroundColor;
    document.body.removeChild(div);
    return color;
};

onMounted(() => {
    lineColor.value = getTailwindColor('color1-600');
});

const chartData = computed(() => {
    const labels = [];
    const totals = [];
    const sortedData = [...props.panelData].sort((a, b) => {
        if (a.year === b.year) return a.month - b.month;
        return a.year - b.year;
    });

    sortedData.forEach((data) => {
        labels.push(`${monthNames[data.month - 1]} ${data.year}`);
        totals.push(Number(data.total ?? 0));
    });

    return {
        labels,
        datasets: [
            {
                label: 'Solicitudes',
                data: totals,
                borderColor: lineColor.value,
                borderWidth: 2.5,
                borderCapStyle: 'butt',
                borderJoinStyle: 'miter',
                pointBackgroundColor: lineColor.value,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 5,
                pointHoverBorderWidth: 2,
                tension: 0.35,
                fill: true,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return 'rgba(156,163,175,0.05)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(123,0,58,0.16)');
                    gradient.addColorStop(0.55, 'rgba(123,0,58,0.06)');
                    gradient.addColorStop(1, 'rgba(123,0,58,0)');
                    return gradient;
                }
            }
        ]
    };
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 500, easing: 'easeOutQuart' },
    layout: { padding: { top: 18, right: 8, bottom: 0, left: 8 } },
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            enabled: true,
            backgroundColor: 'rgba(17, 24, 39, 0.96)',
            titleColor: '#ffffff',
            bodyColor: '#e5e7eb',
            titleFont: { size: 11, weight: '600' },
            bodyFont: { size: 12, weight: '500' },
            padding: { top: 9, right: 11, bottom: 9, left: 11 },
            cornerRadius: 8,
            displayColors: false,
            caretSize: 5,
            callbacks: {
                title: (items) => items[0]?.label ?? '',

                label: (context) => {
                    const value = context.parsed.y ?? 0;
                    const formattedValue = new Intl.NumberFormat('es-MX').format(value);
                    const labelText = value === 1 ? 'solicitud' : 'solicitudes';
                    
                    return ` ${formattedValue} ${labelText}`;
                }
            }
        },
        datalabels: {
            color: '#6b7280',
            font: { weight: '600', size: 10 },
            formatter: (value) => (value > 0 ? new Intl.NumberFormat('es-MX').format(value) : ''),
            anchor: 'end',
            align: 'top',
            offset: 3,
            clamp: true
        }
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                display: true,
                color: '#9ca3af',
                font: { size: 9, weight: '500' },
                padding: 2,
                autoSkip: true,
                maxTicksLimit: 8,
                maxRotation: 0,
                minRotation: 0
            }
        },
        y: {
            beginAtZero: true,
            grid: { display: false },
            border: { display: false },
            ticks: { display: false }
        }
    }
});
</script>