<template>
  <div class="bg-white p-0 m-0 min-h-[200px] sm:min-h-[200px] md:min-h-[200px]">
    <h3 class="text-lg font-semibold text-center md:text-left">Solicitudes por Localidad en  {{ currentYear }}</h3>

    <div v-if="chartData.labels.length > 0" class="m-0 px-0 h-[130px] sm:h-[160px] flex items-stretch">
      <Radar :data="chartData" :options="chartOptions" class="block p-0 m-0" />
    </div>
    <div v-else class="text-center text-gray-500 py-8 px-6 pb-6 flex-grow">
      No hay datos disponibles.
    </div>
  </div>  
</template>


<script setup>
    import { defineProps, ref, computed, onMounted } from 'vue';
    import { Radar } from 'vue-chartjs'; // 👈 ESTE ES EL COMPONENTE CORRECTO

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
        panelData: {
            type: Array,
            required: true,
        },
    });

    // Definimos lineColor como un ref
    const lineColor = ref('#0284c7'); // Color por defecto o de fallback

    // --- FUNCIÓN PARA OBTENER COLORES DE TAILWIND ---
    const getTailwindColor = (className) => {
        if (typeof document === 'undefined') {
            return lineColor.value; // Retorna el color por defecto si no estamos en un entorno de navegador
        }
        const div = document.createElement('div');
        div.className = `bg-${className} hidden`; // Crea un div oculto con la clase de Tailwind
        document.body.appendChild(div);
        const color = getComputedStyle(div).backgroundColor; // Obtiene el color de fondo computado
        document.body.removeChild(div); // Elimina el div
        return color; // Retorna el color en formato rgb(...)
    };
    // -------------------------------------------------

    // Ejecutamos la función cuando el componente está montado para asegurar que Tailwind CSS esté cargado
    onMounted(() => {
        lineColor.value = getTailwindColor('gray-800'); // Un solo color para la línea
    });

    const chartData = computed(() => {
        const labels = [];
        const totals = [];

        const sortedData = [...props.panelData].sort((a, b) => {
            if (a.year === b.year) {
                return a.month - b.month;
            }
            return a.year - b.year;
        });

        sortedData.forEach((data) => {
            // Truncar localidad_nombre si es necesario
            let truncatedLocalidad = data.localidad_nombre;
            if (truncatedLocalidad.length > 12) {
                truncatedLocalidad = truncatedLocalidad.substring(0, 12) + '...';
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
                    borderColor: lineColor.value, // Usamos el color de la línea
                    pointBackgroundColor: [ // Array con color por punto
                        '#ef4444', // rojo
                        '#f59e0b', // amarillo
                        '#10b981', // verde
                        '#3b82f6', // azul
                        '#8b5cf6', // morado
                        '#ec4899'  // rosa
                    ],
                    pointRadius: 4,
                    pointHoverRadius: 4,
                    backgroundColor: 'rgba(2,132,199,0.2)', // Fondo para el área bajo la línea
                    fill: false, // <--- Aquí quitas el relleno
                    tension: 0, // Suaviza la línea
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
                    display: false  // <--- Esto oculta los valores encima de cada punto
                }
        },
        scales: {
            r: {
            ticks: {
                display: false, // <-- Oculta los valores numéricos en los anillos
            },
            grid: {
                color: '#e5e7eb', // puedes cambiar el color si quieres mantener las líneas de la cuadrícula
            },
            angleLines: {
                color: '#e5e7eb', // color de las líneas radiales
            },
            pointLabels: {
                font: {
                size: 11,              
                },
                color: '#000000',
            },
            },
        },      
    });
</script>