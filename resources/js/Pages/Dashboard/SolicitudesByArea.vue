<template>
  <div class="bg-white dark:bg-gray-800 p-0 m-0 min-h-[200px] flex flex-col justify-between h-full">
    <!-- Título -->
    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate mb-1">
      Solicitudes por Área
    </span>

    <!-- Contenedor Principal: Anillo + Leyenda Ancha -->
    <div v-if="panelData && panelData.length" class="flex-grow flex items-center justify-between gap-2 overflow-hidden h-[160px] w-full px-1">
      
      <!-- Lado Izquierdo: Gráfico de Anillo (45% del ancho) -->
      <div
        ref="chartContainer"
        class="w-[48%] h-full min-w-0 min-h-0 flex items-center justify-center relative">
        <canvas ref="pieCanvas" class="w-full h-full"></canvas>
      </div>

      <!-- Lado Derecho: Leyenda de Categorías Más Ancha (55% del ancho) -->
      <div class="w-[50%] flex flex-col justify-center gap-1.5 pl-2">
        <div 
          v-for="(item, index) in chartItems" 
          :key="index"
          class="flex items-center justify-between text-[11px] leading-tight group hover:bg-gray-50 dark:hover:bg-gray-700/40 p-1 rounded transition-colors">
          <!-- Indicador de color + Nombre de Área -->
          <div class="flex items-center gap-1.5 min-w-0 pr-1">
            <span 
              class="w-2.5 h-2.5 rounded-full flex-shrink-0" 
              :style="{ backgroundColor: item.color }">
            </span>
            <span class="font-medium text-gray-700 dark:text-gray-200 truncate" :title="item.area">
              {{ item.area }}
            </span>
          </div>

          <!-- Total de solicitudes / Porcentaje -->
          <span class="font-normal text-gray-900 dark:text-gray-100 tabular-nums flex-shrink-0">
            {{ item.total.toLocaleString('es-MX') }}
          </span>
        </div>
      </div>
    </div>
    <!-- Estado Vacío -->
    <div v-else class="flex items-center justify-center flex-grow text-center text-gray-400 py-6 text-xs italic">
      No hay datos disponibles.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import {
  Chart,
  ArcElement,
  Tooltip,
  Title,
  DoughnutController,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(ArcElement, Tooltip, Title, ChartDataLabels, DoughnutController); 
Chart.defaults.font.family = 'Figtree, sans-serif';

const props = defineProps({
  panelData: { type: Array, required: true, default: () => [] }
});

const pieCanvas = ref(null);
let pieChartInstance = null;
let resizeObserver = null;

const getTailwindColor = (name) => {
  if (typeof document === 'undefined') return '#000000';
  const el = document.createElement('div');
  el.className = `bg-${name} hidden`;
  document.body.appendChild(el);
  const color = getComputedStyle(el).backgroundColor;
  document.body.removeChild(el);
  return color;
};

// Colores asignados a cada posición
const palette = [
  'color1-800',
  'color1-700',
  'color1-600',
  'color1-500',
  'color1-400',
  'color1-300',
];

// Computed que prepara los datos combinados para la gráfica y la leyenda externa
const chartItems = computed(() => {
  if (!props.panelData?.length) return [];
  
  return props.panelData.map((item, index) => ({
    area: item.area || 'N/A',
    total: item.total || 0,
    color: getTailwindColor(palette[index % palette.length])
  }));
});

const renderPieChart = () => {
  const canvas = pieCanvas.value;
  if (!canvas || !chartItems.value.length) return;

  const container = canvas.offsetParent;
  canvas.height = container?.clientHeight || 160; 
  canvas.width = container?.clientWidth || 200;

  if (pieChartInstance) pieChartInstance.destroy();

  const labels = chartItems.value.map(item => item.area);
  const totals = chartItems.value.map(item => item.total);
  const colors = chartItems.value.map(item => item.color);

  pieChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        label: 'Solicitudes',
        data: totals,
        backgroundColor: colors,
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 4,
        radius: '75%',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '55%',
      plugins: {
        // Desactivamos la leyenda nativa de Chart.js para usar la nuestra personalizada en HTML
        legend: { display: false },
        tooltip: {
          callbacks: {
            label(context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
              return ` ${label}: ${value.toLocaleString('es-MX')} (${percentage}%)`;
            },
          },
        },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold', size: 9 },
          textStrokeColor: 'rgba(0, 0, 0, 0.8)',
          textStrokeWidth: 1.5,
          formatter(value, context) {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
            return percentage > 5 ? `${percentage}%` : '';
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

watch(
    () => props.panelData,
    () => {
        renderPieChart();
    },
    {
        deep: true,
        flush: 'post'
    }
);

onBeforeUnmount(() => {
    resizeObserver?.disconnect();

    pieChartInstance?.destroy();
    pieChartInstance = null;
});

</script>