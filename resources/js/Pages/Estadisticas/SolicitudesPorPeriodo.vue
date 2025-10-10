<script setup>
import { computed, onMounted, ref } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler  } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';  

// Registramos los componentes necesarios de Chart.js
ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, ChartDataLabels, Filler);
ChartJS.defaults.font.family = 'Figtree, sans-serif';

// Definimos las propiedades que recibirá el componente
const props = defineProps({
  solicitudes: {
    type: Array,
    required: true,
  },
});

// 1. Variable reactiva para almacenar el color de la línea
const lineColor = ref('');

// 2. Función para obtener el color de la variable CSS de Tailwind
const getTailwindColor = (className) => {
    // Si no estamos en un entorno de navegador, retorna un color por defecto
    if (typeof document === 'undefined') {
        return '#e53e3e'; // Color de fallback, equivalente a 'color1-600'
    }
    const div = document.createElement('div');
    // Crea un div oculto con la clase de Tailwind
    div.className = `bg-${className} hidden`; 
    document.body.appendChild(div);
    // Obtiene el color de fondo computado
    const color = getComputedStyle(div).backgroundColor; 
    // Elimina el div para limpiar el DOM
    document.body.removeChild(div); 
    // Retorna el color en formato rgb(...)
    return color; 
};

// 3. Lógica para obtener el color cuando el componente esté montado
onMounted(() => {
  // Obtiene el valor de 'color1-600' y lo asigna a la variable reactiva
  lineColor.value = getTailwindColor('color1-600');
});

// Lógica para determinar el tipo de agrupación
const isGroupedByDay = computed(() => {
  return props.solicitudes.length > 0 && 'day' in props.solicitudes[0];
});

// Obtenemos las etiquetas y los datos del gráfico
const chartData = computed(() => {
  const labels = [];
  const totals = [];

  if (isGroupedByDay.value) {
    for (const solicitud of props.solicitudes) {
      const date = new Date(solicitud.year, solicitud.month - 1, solicitud.day);
      const formattedDate = format(date, 'd-MMM', { locale: es });
      // Divide la cadena en día y mes
      const parts = formattedDate.split('-');
      // Capitaliza la primera letra del mes y únelo de nuevo
      const capitalizedLabel = parts[0] + '-' + parts[1].charAt(0).toUpperCase() + parts[1].slice(1);
      labels.push(capitalizedLabel);
      totals.push(solicitud.total);
    }
  } 
  else 
  {
    for (const solicitud of props.solicitudes) {
      const date = new Date(solicitud.year, solicitud.month - 1);
      const formattedDate = format(date, 'MMM-yyyy', { locale: es });
      // Capitalizamos la primera letra del mes
      const capitalizedLabel = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
      labels.push(capitalizedLabel);
      totals.push(solicitud.total);
    }
  }

  return {
    labels: labels.reverse(),
    datasets: [{
      label: 'Número de Solicitudes',
      // Usa la variable reactiva que contiene el valor del color
      backgroundColor: 'rgba(220,220,220,0.3)',
      pointBackgroundColor: lineColor.value,
      borderColor: lineColor.value,
      data: totals.reverse(),
      tension: 0.1,
      pointRadius: 6,
      pointHoverRadius: 8,
      fill: true 
    }],
  };
});

// Opciones del gráfico
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  layout: {
      padding: {
          top: 25,
          bottom: 0
      }
  },
  plugins: {
    legend: {
      display: false, // Oculta la leyenda del gráfico
    },
    // Configuración para el plugin de data labels
        datalabels: {
            // Define el color del texto
            color: '#34495e',
            // Define la posición del texto. 'end' lo pone arriba del punto.
            anchor: 'end',
            // Define la alineación del texto
            align: 'top',
            // Callback para formatear el texto. 'value' es el valor del punto de datos.
            formatter: (value) => value,
            // Puedes ajustar el offset para mover el texto
            offset: -1,
            // Define la fuente
            font: {
                weight: 'bold',
                size: 14,
            },
        },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
    },
    y: {
      beginAtZero: true, // Inicia en 0
      display: false,
      ticks: {
        stepSize: 1, // Asegura que los pasos sean de 1 en 1
        precision: 0, // Muestra solo números enteros
        crossAlign: 'near',
        textAlign: 'right',
      },
      grid: {
        display: false,
      },
    },
  },
};
</script>

  <template>
    <div class="bg-white p-0 m-0 h-96 flex flex-col">
      <div v-if="chartData.labels.length > 0" class="flex-grow h-0">
        <Line
            id="my-chart-id"
            :options="chartOptions"
            :data="chartData"
          />
      </div>
      <div v-else class="text-center text-gray-500 py-8 px-6 pb-6 flex-grow">
        No hay datos de solicitudes registradas.
      </div>
    </div>
</template>
 
