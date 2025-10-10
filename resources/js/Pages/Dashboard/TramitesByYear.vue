<template>
  <div class="bg-white p-0 m-0 min-h-[180px] sm:min-h-[180px] md:min-h-[180px]">
    <h3 class="text-lg font-semibold text-center md:text-left">Trámites más Solicitados en {{ currentYear }}</h3>

    <div v-if="chartData.labels.length > 0" class="h-full min-h-[120px] max-h-[155px] flex items-center">
      <Bar :data="chartData" :options="chartOptions" class="w-full h-full" />
    </div>
    <div v-else class="text-center text-gray-500 py-8 px-6 pb-6 flex-grow">
      No hay datos de trámites registrados en el año actual.
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
import { defineProps, ref, computed, onMounted } from 'vue';

const currentYear = new Date().getFullYear();

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
    },
});

const barColors = ref([]);

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

onMounted(() => {
    barColors.value = [
        getTailwindColor('color1-500'),
        getTailwindColor('color1-600'),
        getTailwindColor('color1-700')
    ];
});

const chartData = computed(() => {
    const labels = [];
    const totals = [];
    const backgroundColors = [];

    const sortedData = [...props.panelData].sort((a, b) => b.total - a.total);

    sortedData.forEach((data, index) => {
        labels.push(data.nombre_abreviado);
        totals.push(data.total);
        backgroundColors.push(barColors.value[index % barColors.value.length]);
    });

    return {
        labels: labels,
        datasets: [{
            label: 'Trámites totales',
            data: totals,
            backgroundColor: backgroundColors,
            borderRadius: 5
        }]
    };
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 25, bottom: 0 }
    },
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            enabled: true,
            callbacks: {
                label: function (context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('es-MX').format(context.parsed.y);
                    }
                    return label;
                }
            }
        },
        datalabels: {
            color: '#000000',
            font: { weight: 'bold', size: 11 },
            formatter: value => new Intl.NumberFormat('es-MX').format(value),
            anchor: 'end',
            align: 'top',
            offset: 4
        }
    },
    scales: {
        x: {
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                display: true,
                color: '#000000',
                font: { size: 11 },
                padding: 0,
                autoSkip: false
            },
            border: { display: false }
        },
        y: {
            beginAtZero: true,
            ticks: {
                display: false,
                color: '#000000',
                font: { size: 11 }
            },
            grid: {
                display: false,
                drawBorder: false
            },
            border: { display: false }
        }
    }
});
</script>