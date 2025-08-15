<template>
  <div class="bg-white p-0 m-0 min-h-[180px] sm:min-h-[180px] md:min-h-[180px]">
    <h3 class="text-lg font-semibold text-center md:text-left">Solicitudes por Mes</h3>

    <div v-if="chartData.labels.length > 0" :class="chartContainerClass">
        <Line :data="chartData" :options="chartOptions" class="block p-0 m-0" />
    </div>
    <div v-else class="text-center text-gray-500 py-8 px-6 pb-6 flex-grow">
      No hay datos de solicitudes mensuales disponibles.
    </div>
  </div>  
</template>

<script setup>
    import { defineProps, ref, computed, onMounted } from 'vue';
    import { Line } from 'vue-chartjs'; // Cambiado de Bar a Line
    import {
        Chart as ChartJS,
        Title,
        Tooltip,
        Legend,
        LineElement, // Agregado LineElement
        PointElement, // Agregado PointElement
        CategoryScale,
        LinearScale,
        Filler // Agregado Filler para el área bajo la línea
    } from 'chart.js';
    import ChartDataLabels from 'chartjs-plugin-datalabels';

    ChartJS.register(
        Title,
        Tooltip,
        Legend,
        LineElement, // Registrado
        PointElement, // Registrado
        CategoryScale,
        LinearScale,
        Filler, // Registrado
        ChartDataLabels
    );

    ChartJS.defaults.font.family = 'Figtree, sans-serif';

    const props = defineProps({
        panelData: {
            type: Array,
            required: true,
        },
        userAuth: Object
    });

    const chartContainerClass = computed(() => {
        // Verifica si el rol del usuario es 'AUXILIAR'
        const isAuxiliar = props.userAuth && props.userAuth.roles && props.userAuth.roles.some(role => role.name === 'AUXILIAR');
        
        // Devuelve la clase de altura correspondiente
        return [
            'm-0', 
            'p-0', 
            'flex', 
            'items-stretch', 
            isAuxiliar ? 'h-[430px]' : 'h-[150px]'
        ];
    });

    const monthNames = [
        'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN',
        'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'
    ];

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
        lineColor.value = getTailwindColor('color1-600'); // Un solo color para la línea
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
            labels.push(`${monthNames[data.month - 1]} - ${data.year}`);
            totals.push(data.total);
        });

        return {
            labels: labels,
            datasets: [
                {
                    label: 'Total de Solicitudes',
                    data: totals,
                    borderColor: lineColor.value, // Usamos el color de la línea
                    pointBackgroundColor: lineColor.value,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    backgroundColor: 'rgba(2,132,199,0.2)', // Fondo para el área bajo la línea
                    tension: 0.1, // Curva de la línea
                    fill: false // Rellenar el área bajo la línea
                },
            ],
        };
    });

    const chartOptions = ref({
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
                display: false,
            },
            title: {
                display: false,
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-MX').format(context.parsed.y);
                        }
                        return label;
                    }
                }
            },
            datalabels: {
                color: '#000000',
                font: {
                    weight: 'bold',
                    size: 11,
                },
                formatter: function(value, context) {
                    return new Intl.NumberFormat('es-MX').format(value);
                },
                anchor: 'end',
                align: 'top',
                offset: 4,
                textShadowColor: 'rgba(255,255,255,0.6)',
                textShadowBlur: 4
            }
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    display: true,
                    color: '#000000',
                    font: {
                        size: 11,
                    },
                    padding: 0,
                    autoSkip: false
                },
                border: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    display: false,
                },
                ticks: {
                    display: false,
                },
                border: {
                    display: false
                }
            }
        }
    });
</script>