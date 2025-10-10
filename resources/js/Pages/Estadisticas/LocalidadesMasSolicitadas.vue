<template>
  <div class="bg-white p-0 m-0 h-96 flex flex-col md:flex-row items-center justify-center md:space-x-2">
    
    <div v-if="chartData.labels.length > 0" class="flex-grow w-full md:w-2/3 flex items-center justify-center p-0 h-full">
      <Radar :data="chartData" :options="chartOptions" class="block p-0 m-0 h-full w-full" />
    </div>
    
    <div v-else class="text-center text-gray-500 py-8 px-6 pb-6 flex-grow w-full md:w-2/3">
      No hay datos de solicitudes registradas.
    </div>

    <div class="w-full md:w-1/3  py-4">
      <table class="w-full text-xs text-left text-gray-500">
        <tbody>
          <tr v-for="(item, index) in localidadesMasSolicitadas" :key="index" class="bg-white hover:bg-gray-50">
            <td class="py-1 px-2 font-medium text-gray-900 whitespace-nowrap">
              <span class="inline-block w-3 h-3 rounded-full mr-2" :style="{ backgroundColor: pointColors[index] }"></span>
              {{ item.localidad_nombre }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
    import { defineProps, ref, computed, onMounted } from 'vue';
    import { Radar } from 'vue-chartjs';
    import {
    Chart as ChartJS,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend,
    Title
    } from 'chart.js';
    import ChartDataLabels from 'chartjs-plugin-datalabels';

    const currentYear = new Date().getFullYear();

    ChartJS.register(
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend,
    Title,
    ChartDataLabels
    );

    ChartJS.defaults.font.family = 'Figtree, sans-serif';

    const props = defineProps({
    localidadesMasSolicitadas: {
        type: Array,
        required: true,
    },
    });

    const pointColors = ref([
    '#ef4444', 
    '#f59e0b',
    '#10b981', 
    '#3b82f6',
    '#8b5cf6', 
    '#ec4899',
    '#e11d48',  // Rojo intenso
    '#facc15', // Amarillo
    '#0e7490', // Cian
    '#6b21a8', // Púrpura oscuro
    ]);

    const lineColor = ref('#0284c7'); 

    const getTailwindColor = (className) => {
    if (typeof document === 'undefined') {
        return lineColor.value;
    }
    const div = document.createElement('div');
    div.className = `bg-${className} hidden`;
    document.body.appendChild(div);
    const color = getComputedStyle(div).backgroundColor;
    document.body.removeChild(div);
    return color;
    };

    onMounted(() => {
    lineColor.value = getTailwindColor('gray-700');
    });

    const chartData = computed(() => {
    const labels = [];
    const totals = [];

    const sortedData = [...props.localidadesMasSolicitadas].sort((a, b) => {
        if (a.year === b.year) {
        return a.month - b.month;
        }
        return a.year - b.year;
    });

    sortedData.forEach((data) => {
        let truncatedLocalidad = data.localidad_nombre;
        
        // Verifica si la pantalla es de un dispositivo móvil (menor a 768px)
        if (window.innerWidth < 768) {
            if (truncatedLocalidad.length > 8) {
                truncatedLocalidad = truncatedLocalidad.substring(0, 7) + '...';
            }
        } else {
            // Si la pantalla es más grande, aplica el truncamiento de 30 caracteres
            if (truncatedLocalidad.length > 30) {
                truncatedLocalidad = truncatedLocalidad.substring(0, 30) + '...';
            }
        }
        
        labels.push(`${truncatedLocalidad} (${data.total})`);
        totals.push(data.total);
    });

    return {
        labels: labels,
        datasets: [
        {
            label: 'Total de Solicitudes',
            data: totals,
            borderColor: lineColor.value,
            pointBackgroundColor: pointColors.value,
            pointRadius: 6,
            pointHoverRadius: 4,
            backgroundColor: 'rgba(2,132,199,0.2)',
            fill: false,
            tension: 0.05,
            pointBorderColor: 'transparent',
            pointHoverBorderColor: 'transparent'
        },
        ],
    };
    });

    const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: {
        top: 10,
        bottom: 0
        }
    },
    plugins: {
        legend: {
        display: false,
        },
        title: {
        display: false,
        },
        tooltip: {
        enabled: true,
        callbacks: {
            label: function (context) {
            const label = context.dataset.label || '';
            const value = context.raw;
            return `${label}: ${value}`;
            },
        },
        },
        datalabels: {
            display: false
            }
    },
    scales: {
        r: {
        ticks: {
        display: false,
        },
        grid: {
        color: '#e5e7eb',
        },
        angleLines: {
        color: '#e5e7eb',
        },
        pointLabels: {
        font: {
            size: window.innerWidth < 768 ? 8 : 11,
        },
        color: '#000000',
        },
        },
    },
    });
</script>