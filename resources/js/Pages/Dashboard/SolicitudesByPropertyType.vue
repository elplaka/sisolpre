<template>
  <div class="bg-white p-0 m-0 min-h-[200px] sm:min-h-[200px] md:min-h-[200px]">
    <h3 class="text-lg font-semibold text-center md:text-left">Solicitudes por Tipo de Propiedad en {{ currentYear }}</h3>

    <div v-if="panelData.length" class="p-2 flex-grow flex items-center justify-center overflow-hidden h-[160px]">
      <canvas ref="pieCanvas" class="w-full max-w-[450px] h-full"></canvas>
    </div>
    <div v-else class="text-center text-gray-500 py-6">No hay datos disponibles.</div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import {
  Chart,
  ArcElement,
  Tooltip,
  Legend,
  Title,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

const currentYear = new Date().getFullYear();

Chart.register(ArcElement, Tooltip, Legend, Title, ChartDataLabels);
Chart.defaults.font.family = 'Figtree, sans-serif';

const props = defineProps({
  panelData: { type: Array, required: true }
});

const pieCanvas = ref(null);
let pieChartInstance = null;
let resizeObserver = null;

const renderPieChart = () => {
  const canvas = pieCanvas.value;
  if (!canvas || !props.panelData?.length) return;

  const container = canvas.offsetParent;
  canvas.height = container?.clientHeight || 320;
  canvas.width = container?.clientWidth || 450;

  if (pieChartInstance) pieChartInstance.destroy();

  const labels = props.panelData.map(item =>
    item.tipo_propiedad_nombre?.length > 18
      ? item.tipo_propiedad_nombre.slice(0, 18) + '...'
      : item.tipo_propiedad_nombre || 'N/A'
  );

  const totals = props.panelData.map(item => item.total);

  const getTailwindColor = (name) => {
    const el = document.createElement('div');
    el.className = `bg-${name}`;
    el.style.display = 'none';
    document.body.appendChild(el);
    const color = getComputedStyle(el).backgroundColor;
    document.body.removeChild(el);
    return color;
  };

  const colors = [
    getTailwindColor('color1-600'),
    getTailwindColor('color2-600')
  ].slice(0, totals.length);

  pieChartInstance = new Chart(canvas, {
    type: 'doughnut', // Cambiado a doughnut
    data: {
      labels,
      datasets: [{
        label: 'Solicitudes',
        data: totals,
        backgroundColor: colors,
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%', // Tamaño del agujero central
      plugins: {
        legend: {
          position: 'right',
          labels: { 
            boxWidth: 20, 
            font: { size: 11 },
            padding: 12,
            usePointStyle: true,
          },
        },
        tooltip: {
          callbacks: {
            label(context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: ${value.toLocaleString('es-MX')} (${percentage}%)`;
            },
          },
        },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold', size: 11 },
          textStrokeColor: 'rgba(0, 0, 0, 0.8)',
          textStrokeWidth: 1.5,
          formatter(value, context) {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${percentage}%`;
          },
        },
      },
    },
  });
};

onMounted(() => {
  renderPieChart();

  const container = pieCanvas.value?.offsetParent;
  if (container) {
    resizeObserver = new ResizeObserver(() => {
      renderPieChart();
    });
    resizeObserver.observe(container);
  }
});

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect();
  if (pieChartInstance) pieChartInstance.destroy();
});

watch(() => props.panelData, renderPieChart, { deep: true });
</script>