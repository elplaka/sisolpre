<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title, Tooltip, Legend, BarController, LineController } from 'chart.js';
import { Chart } from 'vue-chartjs';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import annotationPlugin from 'chartjs-plugin-annotation';
import { ElLoading } from 'element-plus';
import { router } from '@inertiajs/vue3'; // O import axios from 'axios';

ChartJS.register(
    CategoryScale, 
    LinearScale, 
    BarElement, 
    PointElement, 
    LineElement, 
    Title, 
    Tooltip, 
    Legend,
    annotationPlugin,
    BarController, 
    LineController,
    ChartDataLabels
);

ChartJS.defaults.font.family = 'Figtree, sans-serif';

const colorGuinda = getComputedStyle(document.documentElement)
.getPropertyValue('--color-1-800')
.trim();

const colorGuindaClaro = getComputedStyle(document.documentElement)
.getPropertyValue('--color-1-500')
.trim();

const colorGuindaOscuro = getComputedStyle(document.documentElement)
.getPropertyValue('--color-1-900')
.trim();

const STATUS_COLORS = {
    'En trámite': '#FBBF24',       // Amber 400
    'Aprobadas': '#818CF8',        // Indigo 400
    'Apoyo entregado': '#34D399',  // Emerald 400
    'Canceladas': '#F87171'        // Red 400
};

const STATUS_ORDER = {
    'En trámite': 1,
    'Aprobadas': 2,
    'Apoyo entregado': 3,
    'Canceladas': 4
};

const props = defineProps({
    rawDataFromBackend: {
        type: Array,
        default: () => []
    },
    rangoFechasQuery: {
        type: Object,
        default: () => ({})
    },
    periodoActual: Object,
    rawDataLocalidades: {
        type: Array,
        default: () => []
    },
    rawDataAreas: {
        type: Array,
        default: () => []
    },
    rawDataEstatus: {
        type: Array,
        default: () => []
    },
    promedioDineroPersona: [String, Number],
    idTipoApoyo: [String, Number],
    kpisRecursosPublicos: {
        type: Object,
        default: () => ({})
    },
    cargando: {
        type: Boolean,
        default: true
    }
});

    // Controladores reactivos para los checkboxes (Capas)
    const capaMontoEntregado = ref(true);
    const capaMontoPendiente = ref(true);
    const capaPromedioEntregado = ref(true);
    const capaTablaDesglose = ref(false);
    const capaMontoTotal = ref(false);
    const isMounted = ref(false);

    onMounted(() => {
        capaPromedioEntregado.value = false
        incluirLocalidadesSinMonto.value = props.kpisRecursosPublicos.incluir_localidades_sin_monto
        setTimeout(() => {
            isMounted.value = true;
        }, 50);
    });

    const hasData = computed(() => {
        return props.rawDataFromBackend && props.rawDataFromBackend.length > 0;
    });

    // Cálculo del Promedio General Global para la línea de referencia
    const promedioEntregadoGlobal = computed(() => {
        if (!hasData.value) return 0;
        const totalSuma = props.rawDataFromBackend.reduce((acc, item) => acc + (Number(item.monto_entregado) || 0), 0);
        return totalSuma / props.rawDataFromBackend.length;
    });

    const formatearFecha = (fecha) => {
        const [anio, mes, dia] = fecha.split('-');
        return `${dia}/${mes}/${anio}`;
    };

    const subtituloParametros = computed(() => {
        if (props.rangoFechasQuery && props.rangoFechasQuery.length === 2) {

            const [inicio, fin] = props.rangoFechasQuery;
            const anioInicio = new Date(inicio + 'T00:00:00').getFullYear();
            const anioFin = new Date(fin + 'T00:00:00').getFullYear();
            
            const mesInicioObj = new Date(inicio + 'T00:00:00');
            const mesFinObj = new Date(fin + 'T00:00:00');
            
            const mesInicio = mesInicioObj.toLocaleString('es-MX', { month: 'long' });
            const mesFin = mesFinObj.toLocaleString('es-MX', { month: 'long' });

            const nombreMesInicio = mesInicio.charAt(0).toUpperCase() + mesInicio.slice(1);
            const nombreMesFin = mesFin.charAt(0).toUpperCase() + mesFin.slice(1);

            const anioActual = new Date().getFullYear();

            // 1. Hoy
            const hoyStr = new Date().toISOString().split('T')[0];
            if (inicio === hoyStr && fin === hoyStr) {
                return `Mostrando Solicitudes de Hoy (${inicio})`;
            }

            // 2. Año Calendario Actual
            if (inicio === `${anioActual}-01-01` && fin === `${anioActual}-12-31`) {
                return `Mostrando Año Calendario ${anioActual} (Enero - Diciembre)`;
            }

            // 3. Año Calendario Anterior
            const anioAnterior = anioActual - 1;
            if (inicio === `${anioAnterior}-01-01` && fin === `${anioAnterior}-12-31`) {
                return `Mostrando Año Calendario ${anioAnterior} (Enero - Diciembre)`;
            }

            // 4. Año de Gobierno Actual / Anterior (Noviembre a Octubre)
            // Detectar si es Año de Gobierno Actual o Anterior (Noviembre a Octubre)
            if (mesInicioObj.getMonth() === 10 && mesFinObj.getMonth() === 9 && (anioFin - anioInicio === 1)) {
                    // Calculamos cuál es el año de gobierno actual usando la misma lógica de fechas
                    const today = new Date();
                    const [baseYear, baseMonth, baseDay] = props.periodoActual.inicio.split('-').map(Number);
                    let currentGovYear = today.getFullYear();
                    let currentMonth = today.getMonth() + 1;
                    let currentDay = today.getDate();

                    if (currentMonth < baseMonth || (currentMonth === baseMonth && currentDay < baseDay)) {
                        currentGovYear -= 1;
                    }

                    const startYY = String(anioInicio).slice(-2);
                    const endYY = String(anioFin).slice(-2);

                    // Si el año de inicio del rango coincide con el año de gobierno actual
                    if (anioInicio === currentGovYear) {
                        return `Mostrando Año de Gobierno Actual (Nov. '${startYY} - Oct. '${endYY})`;
                    } else {
                        return `Mostrando Año de Gobierno Anterior (Nov. '${startYY} - Oct. '${endYY})`;
                    }
                }

            // 7. Semana Actual / Anterior (Detectando rangos de 7 días aproximados o coincidencia)
            const ultimoDiaDelMes = new Date(anioInicio, mesInicioObj.getMonth() + 1, 0).getDate();
            const esMesCompleto = (mesInicioObj.getDate() === 1 && mesFinObj.getDate() === ultimoDiaDelMes);

            if (mesInicioObj.getMonth() === mesFinObj.getMonth() && anioInicio === anioFin && esMesCompleto) {
                const mesActualIdx = new Date().getMonth();
                const mesSeleccionadoIdx = mesInicioObj.getMonth();
                
                if (mesSeleccionadoIdx === mesActualIdx && anioInicio === anioActual) {
                    return `Mostrando Mes Actual (${nombreMesInicio} ${anioInicio})`;
                }
                
                // 6. Mes Anterior u otro mes específico del año
                const mesAnteriorIdx = (mesActualIdx - 1 + 12) % 12;
                if (mesSeleccionadoIdx === mesAnteriorIdx) {
                    return `Mostrando Mes Anterior (${nombreMesInicio} ${anioInicio})`;
                }

                return `Mostrando Mes de ${nombreMesInicio} de ${anioInicio}`;
                }

            const diffTime = Math.abs(new Date(fin + 'T00:00:00') - new Date(inicio + 'T00:00:00'));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays >= 6 && diffDays <= 7) {
                // Verificamos si incluye la fecha de hoy para saber si es la semana actual
                const hoyDate = new Date();
                const inicioDate = new Date(inicio + 'T00:00:00');
                const finDate = new Date(fin + 'T00:00:00');
                
                if (hoyDate >= inicioDate && hoyDate <= finDate) {
                    return `Mostrando Semana Actual (${inicio} al ${fin})`;
                } else {
                    return `Mostrando Semana Anterior (${inicio} al ${fin})`;
                }
            }


            // 8. Trimestres exactos
            if (mesInicioObj.getMonth() === 0 && mesFinObj.getMonth() === 2 && anioInicio === anioFin) {
                return `Mostrando Primer Trimestre de ${anioInicio} (Enero - Marzo)`;
            }
            if (mesInicioObj.getMonth() === 3 && mesFinObj.getMonth() === 5 && anioInicio === anioFin) {
                return `Mostrando Segundo Trimestre de ${anioInicio} (Abril - Junio)`;
            }
            if (mesInicioObj.getMonth() === 6 && mesFinObj.getMonth() === 8 && anioInicio === anioFin) {
                return `Mostrando Tercer Trimestre de ${anioInicio} (Julio - Septiembre)`;
            }
            if (mesInicioObj.getMonth() === 9 && mesFinObj.getMonth() === 11 && anioInicio === anioFin) {
                return `Mostrando Cuarto Trimestre de ${anioInicio} (Octubre - Diciembre)`;
            }

            // 9. Rango Custom o general por defecto
            return `Mostrando Rango Personalizado (${formatearFecha(inicio)} al ${formatearFecha(fin)})`;
        }

    });

    const emptyChartPlugin = {
        id: 'emptyChartMessage',
        afterDraw(chart) {
            const hasNonZeroValue = chart.data.datasets.some(dataset =>
                dataset.data.some(val => Number(val || 0) > 0)
            );
            if (hasNonZeroValue) return;

            const { ctx, chartArea } = chart;
            if (!chartArea) return;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = "300 13px 'Figtree', sans-serif";
            ctx.fillStyle = '#9ca3af'; // gris neutro
            const centerX = (chartArea.left + chartArea.right) / 2;
            const centerY = (chartArea.top + chartArea.bottom) / 2;
            ctx.fillText('Sin montos registrados en el periodo seleccionado', centerX, centerY);
            ctx.restore();
        }
    };

    const chartPlugins = [emptyChartPlugin]

// Opciones de la gráfica convertidas a computed para manejar dinámicamente las anotaciones
const chartOptions = computed(() => {
    let options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            axis: 'x',
            intersect: false
        },
        plugins: {
            datalabels: {
                display: false
            },
            legend: {
                display: true,
                position: 'bottom',
                onClick: (e, legendItem, legend) => {
                    e.native.stopPropagation(); // Opcional: evita propagación de eventos si lo requieres
                },
                labels: {
                    font: { size: 11 },
                    usePointStyle: true,
                    pointStyle: 'rectRounded',
                    boxWidth: 9,
                    boxHeight: 9,
                    padding: 16,
                    sort: (a, b) => {
                        const orden = {
                            'Monto Entregado': 1,
                            'Monto Pendiente': 2,
                            'Monto Total': 3,
                        };

                        return (orden[a.text] ?? 99) - (orden[b.text] ?? 99);
                    },
                },
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                boxBorderWidth: 0,
                boxBorderColor: 'transparent',
                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                titleFont: { size: 11, weight: 'bold' },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    title: function(context) {
                        if (!context.length) return '';
                        const index = context[0].dataIndex;
                        return props.rawDataFromBackend?.[index]?.mes_corto ?? '';
                    },
                    
                    labelColor: function(context) {
                        const color = context.dataset.label === 'Monto Entregado'
                            ? colorGuinda
                            : colorGuindaClaro;

                        return {
                            borderColor: color,
                            backgroundColor: color,
                            borderWidth: 2,
                            borderRadius: 2
                        };
                    },

                    label: function(context) {
                        const value = Number(context.raw) || 0;

                        return ` ${context.dataset.label}: $${value.toLocaleString('es-MX', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}`;
                    },

                    afterBody: function(context) {
                        if (!context.length) return '';

                        const index = context[0].dataIndex;
                        const registro = props.rawDataFromBackend?.[index] ?? {};

                        const entregadas = Number(registro.total_solicitudes_entregado) || 0;
                        const pendientes = Number(registro.total_solicitudes_pendiente) || 0;
                        const totalSolicitudes = Number(
                            registro.total_solicitudes ?? (entregadas + pendientes)
                        ) || 0;

                        return [
                            '',
                            `Entregadas: ${entregadas.toLocaleString('es-MX')} solicitud${entregadas === 1 ? '' : 'es'}`,
                            `Pendientes: ${pendientes.toLocaleString('es-MX')} solicitud${pendientes === 1 ? '' : 'es'}`,
                            `Total mensual: ${totalSolicitudes.toLocaleString('es-MX')} solicitud${totalSolicitudes === 1 ? '' : 'es'}`
                        ];
                    }
                }
            },
            annotation: {
                annotations: {}
            }
        },
        scales: {
            x: {
                grid: { display: false },
                stacked: true,
                ticks: {
                    font: { size: 10, weight: '500' },
                    color: '#6b7280'
                }
            },
            y: {
                grid: { color: 'rgba(243, 244, 246, 0.6)' },
                stacked: true,
                beginAtZero: true,
                ticks: {
                    font: { size: 10 },
                    callback: function(value) {
                        return '$' + Number(value).toLocaleString('es-MX');
                    }
                },
                title: {
                    display: true,
                    text: 'Monto ($)',
                    font: { size: 11, weight: 'bold' },
                    color: '#6b7280'
                }
            }
        }
    };

    if (capaPromedioEntregado.value && hasData.value) {
        const promedio = promedioEntregadoGlobal.value;
        options.plugins.annotation.annotations.lineaPromedio = {
            type: 'line',
            yMin: promedio,
            yMax: promedio,
            borderColor: '#ef4444',
            borderDash: [5, 5],
            borderWidth: 1.5,
            label: {
                content: `Prom. Mensual Entregado: $${Number(promedio).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                display: true,
                position: 'end',
                backgroundColor: 'rgba(17, 24, 39, 0.85)',
                color: '#ffffff',
                font: { size: 10, weight: 'bold' },
                padding: 6,
                borderRadius: 4
            }
        };
    }

    return options;
});

// Procesamiento dinámico de datasets según los checkboxes activos
const chartData = computed(() => {
    if (!hasData.value) return { labels: [], datasets: [] };

    const labels = props.rawDataFromBackend.map(item => {
        // Si item.mes_corto no existe, usamos una cadena vacía para evitar que falle substring
        const mes = item.mes_corto ? String(item.mes_corto) : ''
        return mes.substring(0, 3)
    })

    const dataTotalMonto = props.rawDataFromBackend.map(item => item.total_monto_aprobado ?? 0);
    const dataEntregado = props.rawDataFromBackend.map(item => item.monto_entregado ?? 0);
    const dataPendiente = props.rawDataFromBackend.map(item => item.monto_pendiente_entrega ?? 0);

     let datasets = [];

     if (capaMontoTotal.value) {
        datasets.push({
            type: 'line', // <--- Cambiado de 'bar' a 'line'
            label: 'Monto Total',
            data: dataTotalMonto,
            backgroundColor: colorGuindaOscuro, // Color guinda sólido para puntos/leyenda
            borderColor: colorGuindaOscuro,       // Línea color guinda
            borderWidth: 2,
            pointBackgroundColor: colorGuindaOscuro,
            pointBorderColor: colorGuindaOscuro,
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.4, // Suaviza ligeramente la línea (0 para que sea recta)
            order: 1,     // order 1 para que la línea se pinte por encima de las barras
        });
    }

    // Capa 1: Monto Entregado
    if (capaMontoEntregado.value) {
        datasets.push({
            type: 'bar',
            label: 'Monto Entregado',
            data: dataEntregado,
            backgroundColor: colorGuinda,
            hoverBackgroundColor: colorGuinda,
            borderRadius: (context) => {
                const index = context.dataIndex;
                const montoPendiente = Number(dataPendiente[index] ?? 0);

                const pendienteVisible = capaMontoPendiente.value && montoPendiente > 0;

                return pendienteVisible
                    ? 0
                    : {
                        topLeft: 8,
                        topRight: 8,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
            },
            order: 2,
        });
    }  

    // Capa 2: Pendientes de Entrega (Color Amber / Naranja-Ámbar)
    if (capaMontoPendiente.value) {
        datasets.push({
            type: 'bar',
            label: 'Monto Pendiente',
            data: dataPendiente,
            backgroundColor: colorGuindaClaro,
            hoverBackgroundColor: colorGuindaClaro,
           borderRadius: (context) => {
                const index = context.dataIndex;

                return Number(dataPendiente[index] ?? 0) > 0
                    ? {
                        topLeft: 8,
                        topRight: 8,
                        bottomLeft: 0,
                        bottomRight: 0
                    }
                    : 0;
            },
            order: 3,
        });
    }

    return { labels, datasets };
});

// Función auxiliar para formatear moneda de forma limpia
const formatMoney = (value) => {
    return '$' + Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const totalMontoEntregado = computed(() => {
    return props.rawDataFromBackend.reduce((acc, item) => acc + (Number(item.monto_entregado) || 0), 0);
});

const totalMontoPendiente = computed(() => {
    return props.rawDataFromBackend.reduce((acc, item) => acc + (Number(item.monto_pendiente_entrega) || 0), 0);
});


// Función nativa para generar y descargar el archivo Excel (.csv con BOM para acentos y caracteres de México)
const exportarAExcel = () => {
    if (!props.rawDataFromBackend || props.rawDataFromBackend.length === 0) {
        return;
    }

    // Activar indicador visual (Loading de Element Plus)
    const loadingInstance = ElLoading.service({
        lock: true,
        text: 'Generando reporte financiero y preparando descarga...',
        background: 'rgba(15, 23, 42, 0.45)', // Fondo oscuro suave y translúcido
    });

    const loadingText = document.querySelector('.el-loading-text');
    if (loadingText) {
        loadingText.style.color = '#ffffff';
        loadingText.style.fontWeight = '400';
    }

    // Simulamos un breve retraso estético de 600ms para que el usuario aprecie la transición
    setTimeout(() => {
        try {
            // 1. Encabezados de las columnas
            const headers = ['Mes', 'Monto Entregado', 'Monto Pendiente de Entrega', 'Monto Total'];

            // 2. Filas de datos
            const rows = props.rawDataFromBackend.map(item => {
                const entregado = Number(item.monto_entregado) || 0;
                const pendiente = Number(item.monto_pendiente_entrega) || 0;
                const total = entregado + pendiente;

                return [
                    `"${item.mes}"`,
                    entregado.toFixed(2),
                    pendiente.toFixed(2),
                    total.toFixed(2)
                ];
            });

            // 3. Fila de totales generales
            const totalEntregadoVal = totalMontoEntregado.value.toFixed(2);
            const totalPendienteVal = totalMontoPendiente.value.toFixed(2);
            const totalGeneralVal = (totalMontoEntregado.value + totalMontoPendiente.value).toFixed(2);

            rows.push(['"TOTAL GENERAL"', totalEntregadoVal, totalPendienteVal, totalGeneralVal]);

            // 4. Construir contenido CSV
            const csvContent = [
                headers.join(','),
                ...rows.map(row => row.join(','))
            ].join('\n');

            // 5. Agregar el BOM UTF-8 (\uFEFF) para que Excel reconozca e interprete los acentos y símbolos
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            // 6. Descargar archivo dinámicamente
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `Reporte_Financiero_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error al exportar el reporte financiero:', error);
        } finally {
            // Asegurar de cerrar el loading pase lo que pase
            loadingInstance.close();
        }
    }, 600);
};

const hasDataLocalidades = computed(() => props.rawDataLocalidades && props.rawDataLocalidades.length > 0);
const hasDataAreas = computed(() => props.rawDataAreas && props.rawDataAreas.length > 0);
const hasDataEstatus = computed(() => props.rawDataEstatus && props.rawDataEstatus.length > 0);

const radio = props.rawDataLocalidades.length <= 8 ? 16 : 8;

// Configuración de datos para Localidades (Barras horizontales delgadas)
const chartDataLocalidades = computed(() => ({
    tipoChart: 'localidades',
    labels: props.rawDataLocalidades.map(i => i.localidad_nombre),
    datasets: [
        {
            type: 'bar',
            label: 'Monto Pendiente',
            data: props.rawDataLocalidades.map(i =>
                Number(i.monto_pendiente_entrega) || 0
            ),
             backgroundColor: (context) =>
                Number(context.raw) > 0 ? colorGuindaClaro : 'transparent',
            stack: 'stack-loc',

            borderRadius: (context) => {
                const entregado = Number(
                    props.rawDataLocalidades[context.dataIndex]?.monto_entregado
                ) || 0;

                return {
                    topLeft: entregado > 0 ? 0 : radio,
                    bottomLeft: entregado > 0 ? 0 : radio,
                    topRight: radio,
                    bottomRight: radio
                };
            },

            borderSkipped: false,
            borderWidth: 0,
            order: 2
        },
            {
            type: 'bar',
            label: 'Monto Entregado',
            data: props.rawDataLocalidades.map(i =>
                Number(i.monto_entregado) || 0
            ),
            backgroundColor: (context) =>
                Number(context.raw) > 0 ? colorGuinda : 'transparent',

            stack: 'stack-loc',

            borderRadius: (context) => {
                const pendiente = Number(
                    props.rawDataLocalidades[context.dataIndex]?.monto_pendiente_entrega
                ) || 0;

                return {
                    topLeft: radio,
                    bottomLeft: radio,
                    topRight: pendiente > 0 ? 0 : radio,
                    bottomRight: pendiente > 0 ? 0 : radio
                };
            },

            borderSkipped: false,
            borderWidth: 0,
            order: 1
        }
    ]
}));

const chartDataAreas = computed(() => ({
    tipoChart: 'areas',
    labels: props.rawDataAreas.map(i => i.area_nombre),
    datasets: [
        {
            type: 'bar',
            label: 'Monto Pendiente',
            data: props.rawDataAreas.map(i => Number(i.monto_pendiente_entrega) || 0),
             backgroundColor: (context) =>
                Number(context.raw) > 0 ? colorGuindaClaro : 'transparent',
            stack: 'stack-area',
            borderRadius: (context) => {
                const entregado = Number(
                    props.rawDataAreas[context.dataIndex]?.monto_entregado
                ) || 0;

                return {
                    topLeft: entregado > 0 ? 0 : 8,
                    bottomLeft: entregado > 0 ? 0 : 8,
                    topRight: 8,
                    bottomRight: 8
                };
            },
            borderSkipped: false,
            order: 2
        },
        {
            type: 'bar',
            label: 'Monto Entregado',
            data: props.rawDataAreas.map(i => Number(i.monto_entregado) || 0),
            backgroundColor: (context) =>
                Number(context.raw) > 0 ? colorGuinda : 'transparent',
            stack: 'stack-area',
            borderRadius: (context) => {
                const pendiente = Number(
                    props.rawDataAreas[context.dataIndex]?.monto_pendiente_entrega
                ) || 0;

                return {
                    topLeft: 8,
                    bottomLeft: 8,
                    topRight: pendiente > 0 ? 0 : 8,
                    bottomRight: pendiente > 0 ? 0 : 8
                };
            },
            borderSkipped: false,
            order: 1
        },
    ]
}));

const chartDataEstatus = computed(() => ({
    tipoChart: 'estatus',

    labels: props.rawDataEstatus.map(i => {
    // Asigna el texto exacto que deseas mostrar en el eje izquierdo
    const mapNombres = {
            'EN TRÁMITE': 'EN TRÁMITE',
            'APROBADA': 'APROBADAS',
            'APOYO ENTREGADO': 'APOYOS ENTREGADOS',
            'CANCELADA': 'CANCELADAS'
        };
        return mapNombres[i.estatus_nombre] || i.estatus_nombre;
    }),

    datasets: [
        {
            type: 'bar',
            label: 'En trámite',
            data: props.rawDataEstatus.map(i => Number(i.monto_en_tramite) || 0),
            // backgroundColor: (context) => Number(context.raw) > 0 ? STATUS_COLORS['En trámite'] : 'transparent',
            backgroundColor: crearPatron(STATUS_COLORS['En trámite']),
            stack: 'stack-estatus',
            borderSkipped: false,
            borderWidth: 0,
            order: 4
        },
        {
            type: 'bar',
            label: 'Aprobadas',
            data: props.rawDataEstatus.map(i => Number(i.monto_aprobadas) || 0),
            // backgroundColor: (context) => Number(context.raw) > 0 ? STATUS_COLORS['Aprobadas'] : 'transparent',
            backgroundColor: crearPatron(STATUS_COLORS['Aprobadas']),
            stack: 'stack-estatus',
            borderSkipped: false,
            borderWidth: 0,
            order: 3
        },
        {
            type: 'bar',
            label: 'Apoyo entregado',
            data: props.rawDataEstatus.map(i => Number(i.monto_apoyo_entregado) || 0),
            // backgroundColor: (context) => Number(context.raw) > 0 ? STATUS_COLORS['Apoyo entregado'] : 'transparent',
            backgroundColor: crearPatron(STATUS_COLORS['Apoyo entregado']),
            stack: 'stack-estatus',
            borderSkipped: false,
            borderWidth: 0,
            order: 2
        },
        {
            type: 'bar',
            label: 'Canceladas',
            data: props.rawDataEstatus.map(i => Number(i.monto_canceladas) || 0),
            // backgroundColor: (context) => Number(context.raw) > 0 ? STATUS_COLORS['Canceladas'] : 'transparent',
            backgroundColor: crearPatron(STATUS_COLORS['Canceladas']),
            stack: 'stack-estatus',
            borderSkipped: false,
            borderWidth: 0,
            order: 1
        }
    ]
}));

const obtenerChartOptions = (tipoChart) => {
    const esLocalidades = tipoChart === 'localidades';

    const obtenerRegistro = (dataIndex) => {
        return esLocalidades
            ? props.rawDataLocalidades?.[dataIndex]
            : props.rawDataAreas?.[dataIndex];
    };

    return {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            axis: 'y',
            intersect: false
        },
        layout: {
            padding: {
                right: 30
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                onClick: (e, legendItem, legend) => {
                    e.native.stopPropagation(); // Opcional: evita propagación de eventos si lo requieres
                },
                labels: {
                    boxWidth: 9,
                    boxHeight: 9,
                    font: { size: 11 },
                    usePointStyle: true,
                    pointStyle: 'rectRounded',
                    padding: 16,
                    sort: (a, b) => {
                        const orden = {
                            'Monto Entregado': 1,
                            'Monto Pendiente': 2
                        };

                        return (orden[a.text] ?? 99) - (orden[b.text] ?? 99);
                    },
                    generateLabels: (chart) => {
                        return chart.data.datasets.map((dataset, index) => {
                            const colorAsignado = dataset.label === 'Monto Entregado'
                                ? colorGuinda
                                : colorGuindaClaro;

                            return {
                                text: dataset.label,
                                fillStyle: colorAsignado,
                                strokeStyle: colorAsignado,
                                fontColor: '#6b7280',
                                hidden: !chart.isDatasetVisible(index),
                                datasetIndex: index,
                                pointStyle: 'rectRounded',
                                boxWidth: 9,
                                boxHeight: 9
                            };
                        });
                    }
                }
            },

            datalabels: {
                display: true,
                anchor: 'end',
                align: 'right',
                clip: false,
                offset: 6,

                formatter: (value, context) => {
                    if (context.datasetIndex !== 0) return '';

                    const registro = obtenerRegistro(context.dataIndex);

                    const totalSolicitudes = Number(registro?.total_solicitudes) || 0;
                    const totalMontoAprobado = Number(registro?.total_monto_aprobado) || 0;

                    if (totalSolicitudes === 0) return '';

                    const montoFormateado = '$' + totalMontoAprobado.toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    return `${montoFormateado}  (${totalSolicitudes})`;
                },

                font: {
                    size: 9
                },
                color: '#374151'
            },

            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                titleFont: { size: 11, weight: 'bold' },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 8,

                callbacks: {
                    labelColor: function(context) {
                        const color = context.dataset.label === 'Monto Entregado'
                            ? colorGuinda
                            : colorGuindaClaro;

                        return {
                            borderColor: color,
                            backgroundColor: color,
                            borderWidth: 2,
                            borderRadius: 2
                        };
                    },

                    label: function(context) {
                        const value = Number(context.raw) || 0;

                        return ` ${context.dataset.label}: $${value.toLocaleString('es-MX', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}`;
                    },

                    afterBody: function(items) {
                        if (!items.length) return '';

                        const dataIndex = items[0].dataIndex;
                        const registro = obtenerRegistro(dataIndex);

                        const entregadas = Number(registro?.total_solicitudes_entregadas) || 0;
                        const pendientes = Number(registro?.total_solicitudes_pendientes) || 0;
                        const total = Number(registro?.total_solicitudes) || 0;

                        return [
                            '',
                            `Entregadas: ${entregadas} ${entregadas === 1 ? 'solicitud' : 'solicitudes'}`,
                            `Pendientes: ${pendientes} ${pendientes === 1 ? 'solicitud' : 'solicitudes'}`,
                            `Total: ${total} ${total === 1 ? 'solicitud' : 'solicitudes'}`
                        ];
                    }
                }
            }
        },

        scales: {
            x: {
                stacked: true,
                display: false,
                grid: { color: 'rgba(243, 244, 246, 0.6)' },
                ticks: {
                    font: { size: 10 },
                    color: '#6b7280',
                    callback: (val) => '$' + Number(val).toLocaleString()
                },
                grace: '20%'
            },

            y: {
                border: {
                    display: false
                },
                stacked: true,
                grid: { display: false },
                ticks: {
                    font: { size: 10 },
                    color: '#6b7280',
                    callback: function(value) {
                        const etiqueta = this.getLabelForValue(value);

                        return etiqueta.length > 17
                            ? etiqueta.substring(0, 17) + '...'
                            : etiqueta;
                    }
                }
            }
        }
    };
};
const chartOptionsEstatus = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false, // <-- Lo dejamos en false para que sea fluido
    responsiveAnimationDuration: 0,
         layout: {
            padding: {
                right: 40
            }
        },
    
    interaction: {
        mode: 'index',
        intersect: false
    },

    plugins: {
        legend: {
            display: false,
            position: 'bottom',
            labels: {
                boxWidth: 9,
                boxHeight: 9,
                font: { size: 11 },
                usePointStyle: true,
                pointStyle: 'rectRounded',
                padding: 16,
                color: '#6b7280',
                // Usando la constante declarada arriba
                sort: (a, b) => {
                    return (STATUS_ORDER[a.text] ?? 99) - (STATUS_ORDER[b.text] ?? 99);
                },

                // Usando la constante declarada arriba para los colores
                generateLabels: (chart) => {
                    // Recorremos directamente los datasets de la gráfica actual
                    return chart.data.datasets.map((dataset, index) => ({
                        text: dataset.label,
                        fillStyle: STATUS_COLORS[dataset.label] || '#6366F1',
                        strokeStyle: STATUS_COLORS[dataset.label] || '#6366F1',
                        fontColor: '#6b7280',
                        hidden: !chart.isDatasetVisible(index),
                        datasetIndex: index,
                        pointStyle: 'rectRounded',
                        boxWidth: 9,
                        boxHeight: 9
                    }));
                }
            }
        },

        tooltip: {
            display: false,
            enabled: false,
            mode: 'index',
            intersect: false,
            backgroundColor: '#1f2937',
            titleFont: {
                size: 11,
                weight: 'bold'
            },
            bodyFont: {
                size: 10
            },
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (context) => {
                    const valor = Number(context.raw) || 0;

                    return ` ${context.dataset.label}: $${valor.toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;
                },
                afterBody: (items) => {
                    if (!items.length) return '';
                    const registro = props.rawDataEstatus?.[items[0].dataIndex];
                    const total = Number(registro?.total_solicitudes) || 0;
                    return [`Solicitudes: ${total}`];
                }
            }
        },

     datalabels: {
    display: (context) => {
        const dataIndex = context.dataIndex;
        const registro = props.rawDataEstatus?.[dataIndex];
        const totalSolicitudes = Number(registro?.total_solicitudes) || 0;
        if (totalSolicitudes === 0) return false;

        // Si es CANCELADAS (valor 0 en todos los datasets), solo lo mostramos en el último dataset (ej. datasetIndex === 0)
        // para que no se repita/encime en cada dataset
        const valorActual = Number(context.dataset.data[dataIndex]) || 0;
        const esCancelada = String(registro?.estatus_id) === '4' ||
            String(registro?.estatus_nombre || '').toUpperCase().includes('CANCELAD');

        if (esCancelada) {
            return context.datasetIndex === 0;
        }

        // En estatus con monto, solo mostramos el label en el dataset que tiene el monto (> 0)
        return valorActual > 0;
    },
    color: '#6b7280',
    anchor: 'end',
    align: 'end',
    clamp: false,
    clip: false,
    offset: 6,
    font: {
        size: 9,
    },
    formatter: (value, context) => {
        const dataIndex = context.dataIndex;
        const registro = props.rawDataEstatus?.[dataIndex];
        const totalSolicitudes = Number(registro?.total_solicitudes) || 0;

        // Usamos el monto total del registro para que siempre salga el monto real (ej. $8,000.00 o $0.00)
        const montoMostrar = Number(registro?.total_monto_aprobado ?? value) || 0;

        return '$' + montoMostrar.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ` (${totalSolicitudes})`;
    }
}
        },

    scales: {
        x: {
            display: false,
            grid: {
                color: 'rgba(243, 244, 246, 0.6)'
            },
            ticks: {
                font: {
                    size: 10
                },
                color: '#6b7280',
                callback: (value) => '$' + Number(value).toLocaleString('es-MX')
            },
            grace: '25%'
        },

        y: {
            border: {
                display: false
            },
            grid: {
                display: false
            },
            ticks: {
                font: {
                    size: 10
                },
                color: '#6b7280'
            },
            min: 0,
            gridLines: { offsetGridLines: true },
            grace: '20%'
        }
    }
}));

const cambiarCapa = (capa) => {
    switch (capa) {
        case 'tablaDesglose':
            capaTablaDesglose.value = !capaTablaDesglose.value;
            break;

        case 'montoEntregado':
        case 'montoPendiente':
        case 'montoTotal': {
            const capasActivas = [
                capaMontoEntregado.value,
                capaMontoPendiente.value,
                capaMontoTotal.value
            ].filter(Boolean).length;

            const capaActiva = {
                montoEntregado: capaMontoEntregado,
                montoPendiente: capaMontoPendiente,
                montoTotal: capaMontoTotal
            }[capa];

            // Evitar desactivar la última capa habilitada.
            if (capaActiva.value && capasActivas === 1) {
                return;
            }

            capaActiva.value = !capaActiva.value;
            break;
        }

        default:
            console.warn(`Capa no reconocida: ${capa}`);
    }
};

const mostrarSolicitudesMontoMaximo = ref(false);
const mostrarSolicitudesMontoMinimo = ref(false);

  const restablecerCapas = () => {
        capaMontoEntregado.value = true
        capaMontoPendiente.value = true
        capaMontoTotal.value = false
        capaTablaDesglose.value = false
    };


    const porcentajeConcentracionMaximo = computed(() => {
        const montoTotal = Number(props.kpisRecursosPublicos?.monto_total ?? 0);
        const cantidadMax = Number(props.kpisRecursosPublicos?.monto_maximo?.[0]?.cantidad_aprobada ?? 0);
        const totalSolicitudesMax = props.kpisRecursosPublicos?.monto_maximo?.length ?? 0;

        if (totalSolicitudesMax === 0) {
            return '0.0';
        }

        // Todas las solicitudes tienen cantidad_aprobada en NULL
        if (props.kpisRecursosPublicos?.monto_maximo?.every(
            solicitud => solicitud.cantidad_aprobada == null
        )) {
            return '100.0';
        }

        if (montoTotal === 0 || cantidadMax === 0) {
            return '0.0';
        }

        const subtotalGrupoMax = cantidadMax * totalSolicitudesMax;
        return ((subtotalGrupoMax / montoTotal) * 100).toFixed(1);
    });

    const porcentajeSolicitudesMinimo = computed(() => {
        const totalSolicitudes = Number(props.kpisRecursosPublicos.total_solicitudes ?? 0)
        const totalSolicitudesMin = props.kpisRecursosPublicos?.monto_minimo?.length ?? 0

        if (totalSolicitudes === 0 || totalSolicitudesMin === 0) {
            return '0.0'
        }

        const porcentaje = (totalSolicitudesMin / totalSolicitudes) * 100

        // Retornamos formateado a 1 decimal
        return porcentaje.toFixed(1)
    })

    const criterioOrdenLocalidades = ref('total')
    const criterioOrdenAreas = ref('total')

    const cambiarCriterioOrdenLocalidades = (valor) => {
     // Recuperar los parámetros actuales de la URL
        const parametrosActuales = Object.fromEntries(
            new URLSearchParams(window.location.search)
        );

        // Agregar o actualizar el criterio de ordenamiento
        parametrosActuales.criterioOrdenLocalidades = valor;

        // Volver a ejecutar la consulta
        router.get(route('estadisticas'), parametrosActuales, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const cambiarCriterioOrdenAreas = (valor) => {
     // Recuperar los parámetros actuales de la URL
        const parametrosActuales = Object.fromEntries(
            new URLSearchParams(window.location.search)
        );

        // Agregar o actualizar el criterio de ordenamiento
        parametrosActuales.criterioOrdenAreas = valor;

        // Volver a ejecutar la consulta
        router.get(route('estadisticas'), parametrosActuales, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const crearPatron = (color) => {
        const canvas = document.createElement('canvas');
        canvas.width = 7;
        canvas.height = 6;

        const ctx = canvas.getContext('2d');

        ctx.strokeStyle = color;
        ctx.lineWidth = 5;

        // Líneas verticales
        ctx.beginPath();
        ctx.moveTo(1, 0);
        ctx.lineTo(1, 6);
        ctx.stroke();

        return ctx.createPattern(canvas, 'repeat');
    };

    // 1. Estado reactivo para controlar la apertura y cierre del aside
    const asideAbierto = ref(false);

    // 2. Funciones para abrir y cerrar el panel
    const abrirAsideAnalritica = () => {
        asideAbierto.value = true;
    };

    const cerrarAside = () => {
        asideAbierto.value = false;
    };


    watch(asideAbierto, (nuevoEstado) => {
        if (nuevoEstado) {
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    });

    onUnmounted(() => {
        document.body.classList.remove('overflow-hidden');
    });

    const limiteTop = ref(5);

    const topSolicitudes = computed(() => {
        return (props.kpisRecursosPublicos?.top_solicitudes || [])
            .slice(0, limiteTop.value);
    });

    const distribucionMontos = computed(() => {
    const datos = props.kpisRecursosPublicos?.distribucion_montos || {};

    const rangos = [
        { label: 'Menos de $1,000', key: 'menos_1000' },
        { label: '$1,000 - $3,000', key: 'entre_1000_3000' },
        { label: '$3,000 - $5,000', key: 'entre_3000_5000' },
        { label: 'Más de $5,000', key: 'mas_5000' },
    ];

    const total = rangos.reduce(
        (suma, rango) => suma + Number(datos[rango.key] || 0),
        0
    );

    return rangos.map(rango => {
        const cantidad = Number(datos[rango.key] || 0);

        return {
            ...rango,
            cantidad,
            porcentaje: total > 0
                ? (cantidad / total) * 100
                : 0,
        };
    });
});

    function verEditarSolicitud(id) {
        asideAbierto.value = false;
        router.put('/solicitudes/editar', {
            id_solicitud: id
        });
    }

    const incluirLocalidadesSinMonto = ref(false)

    const cambiarSwitchLocalidades = () => {
     // Recuperar los parámetros actuales de la URL
        const parametrosActuales = Object.fromEntries(
            new URLSearchParams(window.location.search)
        );

        // Agregar o actualizar el criterio de ordenamiento
        parametrosActuales.incluirLocalidadesSinMonto = incluirLocalidadesSinMonto.value;

        // Volver a ejecutar la consulta
        router.get(route('estadisticas'), parametrosActuales, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const incluirAreasSinMonto = ref(false)

    const cambiarSwitchAreas = () => {
     // Recuperar los parámetros actuales de la URL
        const parametrosActuales = Object.fromEntries(
            new URLSearchParams(window.location.search)
        );

        // Agregar o actualizar el criterio de ordenamiento
        parametrosActuales.incluirAreasSinMonto = incluirAreasSinMonto.value;

        // Volver a ejecutar la consulta
        router.get(route('estadisticas'), parametrosActuales, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

</script>

<template>
    <!-- SKELETON: RECURSOS PÚBLICOS -->
    <div v-if="props.cargando" class="py-3 bg-gray-50 dark:bg-gray-900 animate-pulse">
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 items-stretch">
            <!-- KPI 1: MONTO GLOBAL SOLICITADO -->
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col h-full min-h-[180px]">
                <div class="w-full mb-4">
                    <div class="h-3 w-40 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="min-w-0 flex-1">
                        <!-- Monto principal -->
                        <div class="h-9 w-52 max-w-full bg-gray-200 dark:bg-gray-700 rounded-lg mt-2"></div>
                        <div class="h-2 w-44 max-w-full bg-gray-100 dark:bg-gray-700/70 rounded-full mt-2 mb-5"></div>

                        <!-- Entregado / Pendiente -->
                        <div class="grid grid-cols-2 gap-2 min-h-[80px]">

                            <!-- Entregado -->
                            <div class="bg-gray-100 dark:bg-emerald-950/30 rounded-lg p-2.5">
                                <div class="flex items-center gap-1.5 mb-3">
                                    <div class="w-3.5 h-3.5 rounded-full bg-gray-200 dark:bg-emerald-900/60"></div>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-emerald-900/60 rounded-full"></div>
                                </div>
                                <div class="h-4 w-full bg-gray-200 dark:bg-emerald-900/50 rounded"></div>
                                <div class="h-2 w-14 bg-gray-200 dark:bg-emerald-900/40 rounded-full mt-2"></div>
                            </div>

                            <!-- Pendiente -->
                            <div class="bg-gray-100 dark:bg-amber-950/30 rounded-lg p-2.5">
                                <div class="flex items-center gap-1.5 mb-3">
                                    <div class="w-3.5 h-3.5 rounded-full bg-gray-200 dark:bg-amber-900/60"></div>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-amber-900/60 rounded-full"></div>
                                </div>
                                <div class="h-4 w-full bg-gray-200 dark:bg-amber-900/50 rounded"></div>
                                <div class="h-2 w-14 bg-gray-200 dark:bg-amber-900/40 rounded-full mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- KPI 2: MONTO PROMEDIO POR SOLICITUD -->
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col h-full min-h-[180px]">
                <div class="w-full mb-2">
                    <div class="h-3 w-48 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>
                <div class="flex items-center justify-between gap-3 min-w-0 flex-1">
                    <div class="min-w-0 flex-1">
                        <!-- Monto principal -->
                        <div class="h-9 w-48 max-w-full bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <!-- Barra de distribución -->
                        <div class="flex h-1.5 overflow-hidden mt-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                            <div class="w-[60%] bg-gray-300 dark:bg-gray-600 rounded-l-full"></div>
                            <div class="w-[40%] bg-gray-200 dark:bg-gray-700 rounded-r-full"></div>
                        </div>
                        <!-- Promedios secundarios -->
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div>
                                <div class="flex items-center gap-1.5 mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                                </div>
                                <div class="h-3 w-24 max-w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5 mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                                </div>
                                <div class="h-3 w-24 max-w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Solicitudes -->
                <div class="flex items-center gap-2 mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 shrink-0"></div>
                    <div class="h-2.5 w-48 max-w-full bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>
            </div>
            <!-- KPI 3: RANGO DE MONTOS SOLICITADOS -->
            <div class="bg-white xl:col-span-2 dark:bg-gray-800 px-4 pt-4 pb-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs h-full min-h-[180px]">
                <!-- Encabezado -->
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="h-3 w-48 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    <!-- Botón análisis -->
                    <div class="h-7 w-32 shrink-0 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>

                <!-- Máximo y mínimo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
                    <!-- MONTO MÁXIMO -->
                    <div class="rounded-lg bg-gray-100 dark:bg-emerald-950/20 py-3 px-5 min-w-0 min-h-[176px] flex flex-col">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-7 h-7 rounded-md bg-gray-200 dark:bg-emerald-900/50"></div>
                            <div class="h-2 w-24 bg-gray-200 dark:bg-emerald-900/50 rounded-full"></div>
                        </div>
                        <!-- Importe -->
                        <div class="h-7 w-40 max-w-full bg-gray-200 dark:bg-emerald-900/40 rounded-lg"></div>
                        <!-- Botón folios -->
                        <div class="h-8 w-full bg-gray-200/70 dark:bg-gray-800/70 rounded-2xl mt-3"></div>

                        <!-- Descripción / concentración -->
                        <div class="flex items-center gap-2 mt-4">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-emerald-900 shrink-0"></div>
                            <div class="h-2 w-4/5 bg-gray-200 dark:bg-emerald-900/40 rounded-full"></div>
                        </div>
                    </div>
                    <!-- MONTO MÍNIMO -->
                    <div class="rounded-lg bg-gray-100 dark:bg-amber-950/20 py-3 px-5 min-w-0 min-h-[176px] flex flex-col">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-7 h-7 rounded-md bg-gray-200 dark:bg-amber-900/50"></div>
                            <div class="h-2 w-24 bg-gray-200 dark:bg-amber-900/50 rounded-full"></div>
                        </div>
                        <!-- Importe -->
                        <div class="h-7 w-40 max-w-full bg-gray-200 dark:bg-amber-900/40 rounded-lg"></div>
                        <!-- Botón folios -->
                        <div class="h-8 w-full bg-gray-200/70 dark:bg-gray-800/70 rounded-2xl mt-3"></div>
                        <!-- Descripción / concentración -->
                        <div class="flex items-center gap-2 mt-4">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-amber-900 shrink-0"></div>
                            <div class="h-2 w-4/5 bg-gray-200 dark:bg-amber-900/40 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="py-3 bg-gray-50 dark:bg-gray-900 grid grid-cols-1 xl:grid-cols-4 gap-6 items-stretch">
        <!-- Grid de Tarjetas de Indicadores (KPIs) -->
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col transition-all h-full min-h-[180px]">
            <!-- Encabezado -->
            <div class="w-full mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400 block">
                    Monto Global Solicitado
                </span>
            </div>
            <!-- Contenido principal -->
            <div class="flex items-center justify-between gap-3 min-w-0 flex-1">
                <div class="min-w-0 flex-1">
                    <!-- Monto promedio principal -->
                    <!-- <div class="flex items-center gap-2"> -->
                        <div class="text-3xl font-black text-gray-900 mr-1 mt-2 dark:text-white">
                            {{ formatMoney(kpisRecursosPublicos.monto_total ?? 0) }}
                        </div>
                    <!-- </div> -->
                    <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 mb-5">
                        Cantidad total de recursos solicitados
                    </div>
                    <!-- Bloques de distribución -->
                    <div class="grid grid-cols-2 gap-2 mt-1 min-h-[80px]">
                        <!-- ENTREGADO -->
                        <div class="bg-color1-40 dark:bg-emerald-950/40 rounded-lg p-2.5 min-w-0">
                            <div class="flex items-center gap-1.5 mb-2">
                                <svg class="w-3.5 h-3.5 text-color1-600 dark:text-emerald-400 shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75m6.023 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>

                                <span class="text-[10px] font-bold uppercase tracking-wider text-color1-700 dark:text-emerald-400">
                                    Entregado
                                </span>
                            </div>

                            <div class="text-sm font-extrabold tracking-tight text-color1-900 dark:text-emerald-200 tabular-nums truncate">
                                {{ formatMoney(kpisRecursosPublicos.monto_total_entregado ?? 0) }}
                            </div>

                            <div
                                class="text-[10px] font-semibold mt-1"
                                :class="Number(kpisRecursosPublicos.monto_total ?? 0) > 0
                                    ? 'text-color1-600 dark:text-emerald-500'
                                    : 'text-gray-500 dark:text-gray-500'">
                                <template v-if="Number(kpisRecursosPublicos.monto_total ?? 0) > 0">
                                    {{ ((Number(kpisRecursosPublicos.monto_total_entregado ?? 0) /
                                        Number(kpisRecursosPublicos.monto_total)) * 100).toFixed(1) }}% del total
                                </template>
                                <template v-else>
                                    Sin monto solicitado
                                </template>
                            </div>
                        </div>

                        <!-- PENDIENTE -->
                        <div class="bg-gray-100 dark:bg-amber-950/40 rounded-lg p-2.5 min-w-0">

                            <div class="flex items-center gap-1.5 mb-2">
                                <svg class="w-3.5 h-3.5 text-color1-600 dark:text-amber-400 shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4l2.5 2.5m6.5-2.5a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>

                                <span class="text-[10px] font-bold uppercase tracking-wider text-color1-700 dark:text-amber-400">
                                    Pendiente
                                </span>
                            </div>

                            <div class="text-sm font-extrabold tracking-tight text-color1-900 dark:text-amber-200 tabular-nums truncate">
                                {{ formatMoney(kpisRecursosPublicos.monto_total_pendiente ?? 0) }}
                            </div>
                            <div
                                class="text-[10px] font-semibold mt-1"
                                :class="Number(kpisRecursosPublicos.monto_total ?? 0) > 0
                                    ? 'text-color1-600 dark:text-amber-500'
                                    : 'text-gray-500 dark:text-gray-500'">
                                <template v-if="Number(kpisRecursosPublicos.monto_total ?? 0) > 0">
                                    {{ ((Number(kpisRecursosPublicos.monto_total_pendiente ?? 0) /
                                        Number(kpisRecursosPublicos.monto_total)) * 100).toFixed(1) }}% del total
                                </template>
                                <template v-else>
                                    Sin monto solicitado
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col transition-all h-full min-h-[180px]">
            <!-- 1. ENCABEZADO QUE OCUPA TODO EL ANCHO DEL CARD -->
            <div class="w-full mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400 block">
                    Monto Promedio por Solicitud
                </span>
            </div>

            <!-- 2. CUERPO PRINCIPAL CON ICONO CENTRADO VERTICALMENTE -->
            <div class="flex items-center justify-between gap-3 min-w-0 flex-1">
                <!-- Contenido numérico y gráficos -->
                <div class="min-w-0 flex-1">
                    <!-- Monto principal -->
                    <div class="text-3xl font-black text-gray-900 dark:text-white">
                        {{ formatMoney(kpisRecursosPublicos.solicitudes_avg?.monto_promedio_aprobado ?? 0) }}
                    </div>
                    <!-- Barra de distribución -->
                    <div class="flex h-1.5 overflow-hidden mt-3 bg-gray-100 dark:bg-gray-700">
                        <div
                            class="bg-color1-800 rounded-l-full transition-all duration-500"
                            :style="{
                                width: `${(kpisRecursosPublicos.solicitudes_avg?.monto_promedio_aprobado ?? 0) > 0
                                    ? (kpisRecursosPublicos.solicitudes_avg.monto_promedio_entregado /
                                    kpisRecursosPublicos.solicitudes_avg.monto_promedio_aprobado) * 100
                                    : 0}%`
                            }">
                        </div>
                        <div
                            class="bg-color1-400 rounded-r-full transition-all duration-500"
                            :style="{
                                width: `${(kpisRecursosPublicos.solicitudes_avg?.monto_promedio_aprobado ?? 0) > 0
                                    ? (kpisRecursosPublicos.solicitudes_avg.monto_promedio_pendiente /
                                    kpisRecursosPublicos.solicitudes_avg.monto_promedio_aprobado) * 100
                                    : 0}%`
                            }">
                        </div>
                    </div>
                    <!-- Promedios secundarios -->
                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-color1-800 shrink-0"></span>
                                <span class="text-[10px] font-medium uppercase tracking-wider leading-none text-gray-500 dark:text-gray-400">
                                    Entregado
                                </span>
                            </div>
                            <div class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate">
                                {{ formatMoney(kpisRecursosPublicos.solicitudes_avg?.monto_promedio_entregado ?? 0) }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-color1-400 shrink-0"></span>
                                <span class="text-[10px] uppercase tracking-wider leading-none font-medium text-gray-500 dark:text-gray-400">
                                    Pendiente
                                </span>
                            </div>
                            <div class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate">
                                {{ formatMoney(kpisRecursosPublicos.solicitudes_avg?.monto_promedio_pendiente ?? 0) }}
                            </div>                            
                        </div>
                    </div>
                </div>
            </div>
            <!-- Solicitudes -->
            <div class="flex items-center pb-0 gap-1.5 mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <div class="w-6 h-6 rounded-full bg-color1-50 dark:bg-color1-950/40 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5 text-color1-700 dark:text-color1-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-[11px] text-gray-600 dark:text-gray-500 truncate">
                    <b>  {{ (kpisRecursosPublicos.total_solicitudes ?? 0).toLocaleString('es-MX') }} </b> solicitudes activas en el periodo
                </span>
            </div>
        </div>
        <!-- RANGO DE MONTOS SOLICITADOS -->
        <div class="bg-white xl:col-span-2 dark:bg-gray-800 px-4 pt-4 pb-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs h-full min-h-[180px]">
            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">
                        Rango de Montos Solicitados
                    </h3>
                </div>
                <button @click="abrirAsideAnalritica" 
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium text-white bg-color1 hover:bg-color1-900 hover:text-color1-50 dark:bg-color1-950/50 dark:text-color1-300 dark:hover:bg-color1-900/50 transition-all duration-200 shadow-2xs cursor-pointer group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Análisis detallado</span>
                     <svg class="w-3 h-3 transform transition-transform duration-300 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Máximo y mínimo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">

                <!-- MONTO MÁXIMO -->
                <div class="rounded-lg bg-color1-40/90 py-3 px-5 min-w-0 min-h-[176px] flex flex-col">

                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-7 h-7 rounded-md bg-color1-50/90 dark:bg-emerald-900/50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-color1-600 dark:text-emerald-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M7 17l9-9m0 0H8m8 0v8"/>
                            </svg>
                        </span>

                        <span class="text-[10px] font-bold uppercase tracking-wider text-color1-700 dark:text-emerald-400">
                            Monto máximo
                        </span>
                    </div>

                    <div class="text-2xl font-extrabold text-color1-800 dark:text-emerald-400 tabular-nums">
                        {{ kpisRecursosPublicos.monto_maximo?.[0]?.cantidad_aprobada != null
                            ? formatMoney(kpisRecursosPublicos.monto_maximo[0].cantidad_aprobada)
                            : '—' }}
                    </div>

                    <button
                        v-if="kpisRecursosPublicos.total_solicitudes > 0"
                        type="button"
                        @click="mostrarSolicitudesMontoMaximo = !mostrarSolicitudesMontoMaximo"
                        class="w-full text-[11px] text-gray-500 dark:text-gray-400 mt-2 px-2.5 py-1.5 rounded-2xl
                            flex items-center justify-between gap-2
                            bg-color1-200/25 hover:bg-color1-200/40 dark:bg-gray-800/40 dark:hover:bg-gray-800/80
                            border border-gray-100 dark:border-gray-700/50
                            transition-all duration-200 cursor-pointer group"
                        :aria-label="mostrarSolicitudesMontoMaximo ? 'Ocultar solicitudes' : 'Mostrar solicitudes'">
                        
                        <!-- Texto de cantidad -->
                        <span class="font-medium pl-1 text-color1-700 dark:text-gray-300 group-hover:text-color1-700 dark:group-hover:text-emerald-400 transition-colors">
                            {{ kpisRecursosPublicos.monto_maximo?.length }}
                            {{ kpisRecursosPublicos.monto_maximo?.length === 1 ? 'solicitud' : 'solicitudes' }}
                        </span>

                        <!-- Contenedor de la flecha con indicador visual -->
                        <span class="flex items-center gap-1 text-xs font-semibold text-color1-600 dark:text-emerald-400">
                            <span class="text-[10px] hidden sm:inline">
                                {{ mostrarSolicitudesMontoMaximo ? 'Ocultar' : 'Ver' }}
                                {{ kpisRecursosPublicos.monto_maximo?.length === 1 ? 'folio' : 'folios' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 transition-transform duration-300"
                                :class="{ 'rotate-180': mostrarSolicitudesMontoMaximo }"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>
                    <!-- Desglose de solicitudes -->
                    <Transition name="cambio-info" mode="out-in">
                        <!-- 1. Mostrar solicitudes -->
                        <div v-if="mostrarSolicitudesMontoMaximo"
                            key="solicitudes"
                            class="border-t border-color1-50/70 dark:border-emerald-900/50 mt-2.5 pt-2.5">
                            <div v-if="kpisRecursosPublicos.monto_maximo?.length > 0" class="flex flex-wrap pl-1 pb-1 items-center gap-1.5 max-h-20 overflow-y-auto">
                                <el-tooltip 
                                    v-for="solicitud in kpisRecursosPublicos.monto_maximo" 
                                    :key="solicitud.id"
                                    placement="top"
                                    :show-after="50"
                                    :hide-after="0"
                                    :enterable="false"
                                    :raw-content="true"
                                    popper-class="custom-tooltip"
                                    :content="`
                                        <span style='display:inline-flex;align-items:center;gap:6px;'>
                                            <span style='width:8px;height:8px;border-radius:50%;background-color:${
                                                solicitud.id_estatus == 1 ? '#f59e0b' :
                                                solicitud.id_estatus == 2 ? '#6366f1' :
                                                solicitud.id_estatus == 3 ? '#10b981' : '#9ca3af'
                                            };display:inline-block;flex-shrink:0;'></span>
                                            Folio #${String(solicitud.id).padStart(5, '0')} - ${solicitud.solicitante?.nombre ?? ''} ${solicitud.solicitante?.apellidos ?? ''}
                                        </span>
                                    `">
                                    <span
                                        class="inline-flex items-center text-[9px] font-extrabold
                                            pl-1.5 pr-2 py-0.5 rounded-r-md
                                            border-l-[2.5px] rounded-md border-l border border-color1-100/50
                                            bg-white/30 dark:bg-gray-800
                                            text-gray-600 dark:text-gray-300
                                            cursor-help transition-all duration-200
                                            hover:shadow-sm hover:scale-[1.05]"
                                        :class="{
                                            'border-l-amber-500 hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-800 dark:text-amber-200': solicitud.id_estatus == 1,
                                            'border-l-indigo-500 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-indigo-800 dark:text-indigo-200': solicitud.id_estatus == 2,
                                            'border-l-emerald-500 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200': solicitud.id_estatus == 3,
                                            'border-l-red-500 hover:border-red-500 hover:bg-red-50 dark:hover:bg-rose-950/40 text-red-800 dark:text-rose-200': solicitud.id_estatus == 4
                                        }">
                                        <span v-if="idTipoApoyo === 1 || idTipoApoyo === 2" class="opacity-40 select-none mr-0.5">#</span>
                                        <span v-else-if="solicitud.id_tipo_apoyo === 1"                                            
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <path d="M6 12h.01M18 12h.01"/>
                                            </svg>
                                        </span>
                                        <span
                                            v-else-if="solicitud.id_tipo_apoyo === 2"
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                                <path d="M12 22V12"/>
                                            </svg>
                                        </span>
                                        {{ String(solicitud.id).padStart(5, '0') }}
                                    </span>
                                </el-tooltip>
                            </div>
                        </div>
                        <!-- 2. Mostrar concentración -->
                        <div
                            v-else-if="porcentajeConcentracionMaximo >= 10"
                            key="concentracion"
                            class="inline-flex items-center gap-2 px-2.5 py-0.5 backdrop-blur-md dark:border-gray-700/40 shadow-2xs w-full mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[11px] font-medium text-gray-500 dark:text-gray-300 tracking-tight">
                                {{ kpisRecursosPublicos.monto_maximo?.length === 1 ? 'Este único apoyo representa el' : 'Estos apoyos representan el' }}

                                <strong class="font-bold text-color1-700 dark:text-emerald-400">
                                    {{ porcentajeConcentracionMaximo }}%
                                </strong>
                                del presupuesto total solicitado.
                            </span>
                        </div>
                        <!-- 3. Descripción predeterminada -->
                        <div
                            v-else-if = "kpisRecursosPublicos.total_solicitudes > 0"
                            key="descripcion"
                            class="inline-flex items-center gap-2 px-2.5 py-1.5 backdrop-blur-md dark:border-gray-700/40 shadow-2xs w-full mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[10px] font-medium text-gray-600 dark:text-gray-300 tracking-tight">
                                Mayor importe aprobado en una solicitud 
                            </span>
                        </div>
                        <div
                            v-else
                            key="sinSolicitudes"
                            class="inline-flex items-center gap-2 px-2.5 py-1.5 backdrop-blur-md dark:border-gray-700/40 shadow-2xs w-full mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[10px] font-medium text-gray-600 dark:text-gray-300 tracking-tight">
                                No se registraron solicitudes en este periodo
                            </span>
                        </div>
                    </Transition>
                </div>
                <!-- MONTO MÍNIMO -->
                <div class="rounded-lg bg-gray-100 dark:bg-amber-950/20 py-3 px-5 min-w-0 min-h-[176px] flex flex-col">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-7 h-7 rounded-md bg-gray-200 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-color1-600 dark:text-amber-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M17 7l-9 9m0 0h8m-8 0V8"/>
                            </svg>
                        </span>

                        <span class="text-[10px] font-bold uppercase tracking-wider text-color1-700 dark:text-amber-400">
                            Monto mínimo
                        </span>
                    </div>
                    <div class="text-2xl font-extrabold text-color1-800 dark:text-emerald-400 tabular-nums">
                        {{ kpisRecursosPublicos.monto_minimo?.[0]?.cantidad_aprobada != null
                            ? formatMoney(kpisRecursosPublicos.monto_minimo[0].cantidad_aprobada)
                            : '—' }}
                    </div>
                    <button
                        v-if="kpisRecursosPublicos.total_solicitudes > 0"
                        type="button"
                        @click="mostrarSolicitudesMontoMinimo = !mostrarSolicitudesMontoMinimo"
                        class="w-full text-[11px] text-gray-500 dark:text-gray-400 mt-2 px-2.5 py-1.5 rounded-2xl
                            flex items-center justify-between gap-2
                            bg-gray-300/60 hover:bg-gray-300 dark:bg-gray-800/40 dark:hover:bg-gray-800/80
                            border border-gray-100 dark:border-gray-700/50
                            transition-all duration-200 cursor-pointer group"
                        :aria-label="mostrarSolicitudesMontoMinimo ? 'Ocultar solicitudes' : 'Mostrar solicitudes'">
                        <!-- Texto de cantidad -->
                        <span class="font-medium pl-1 text-color1-700 dark:text-gray-300 group-hover:text-color1-700 dark:group-hover:text-emerald-400 transition-colors">
                            {{ kpisRecursosPublicos.monto_minimo?.length }}
                            {{ kpisRecursosPublicos.monto_minimo?.length === 1 ? 'solicitud' : 'solicitudes' }}
                        </span>
                        <!-- Contenedor de la flecha con indicador visual -->
                        <span class="flex items-center gap-1 text-xs font-semibold text-color1-600 dark:text-emerald-400">
                            <span class="text-[10px] hidden sm:inline">
                                {{ mostrarSolicitudesMontoMinimo ? 'Ocultar' : 'Ver' }}
                                {{ kpisRecursosPublicos.monto_minimo?.length === 1 ? 'folio' : 'folios' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 transition-transform duration-300"
                                :class="{ 'rotate-180': mostrarSolicitudesMontoMinimo }"
                                fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>
                    <Transition name="cambio-info" mode="out-in">
                        <div v-if="mostrarSolicitudesMontoMinimo" class="border-t border-gray-200/70 dark:border-emerald-900/50 mt-2.5 pt-2.5">
                            <!-- Si hay una o varias solicitudes -->
                            <div v-if="kpisRecursosPublicos.monto_minimo?.length > 0" class="flex flex-wrap pl-0.5 pb-0.5 items-center gap-1.5 max-h-20 overflow-y-auto">
                                <el-tooltip 
                                    v-for="solicitud in kpisRecursosPublicos.monto_minimo" 
                                    :key="solicitud.id"
                                    placement="top"
                                    :show-after="50"
                                    :hide-after="0"
                                    :enterable="false"
                                    :raw-content="true"
                                    popper-class="custom-tooltip"
                                    :content="`
                                        <span style='display:inline-flex;align-items:center;gap:6px;'>
                                            <span style='width:8px;height:8px;border-radius:50%;background-color:${
                                                solicitud.id_estatus == 1 ? '#f59e0b' :
                                                solicitud.id_estatus == 2 ? '#6366f1' :
                                                solicitud.id_estatus == 3 ? '#10b981' : '#9ca3af'
                                            };display:inline-block;flex-shrink:0;'></span>
                                            Folio #${String(solicitud.id).padStart(5, '0')} - ${solicitud.solicitante?.nombre ?? ''} ${solicitud.solicitante?.apellidos ?? ''}
                                        </span>
                                    `">
                                    <span
                                        class="inline-flex border border-gray-300/80 items-center text-[9px]
                                            font-extrabold pl-1 pr-2 py-0.5 rounded-md border-l-[3px]
                                            bg-gray-50 dark:bg-slate-800/80 text-slate-600
                                            dark:text-slate-300 backdrop-blur-xs cursor-help
                                            transition-all duration-200
                                            hover:scale-[1.05] hover:shadow-md"
                                        :class="{
                                            'border-l-amber-500 hover:!border-amber-500 hover:!bg-amber-50 dark:hover:!bg-amber-950/50 shadow-amber-500/10 text-amber-900 dark:text-amber-200': solicitud.id_estatus == 1,
                                            'border-l-indigo-500 hover:!border-indigo-500 hover:!bg-indigo-50 dark:hover:!bg-indigo-950/50 shadow-indigo-500/10 text-indigo-900 dark:text-indigo-200': solicitud.id_estatus == 2,
                                            'border-l-emerald-500 hover:!border-emerald-500 hover:!bg-emerald-50 dark:hover:!bg-emerald-950/50 shadow-emerald-500/10 text-emerald-900 dark:text-emerald-200': solicitud.id_estatus == 3,
                                            'border-l-red-500 hover:!border-red-500 hover:!bg-red-50 dark:hover:!bg-rose-950/50 shadow-red-500/10 text-red-900 dark:text-rose-200': solicitud.id_estatus == 4
                                        }">
                                                   <span v-if="idTipoApoyo === 1 || idTipoApoyo === 2" class="opacity-40 select-none mr-0.5">#</span>
                                        <span v-else-if="solicitud.id_tipo_apoyo === 1"                                            
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <path d="M6 12h.01M18 12h.01"/>
                                            </svg>
                                        </span>
                                        <span
                                            v-else-if="solicitud.id_tipo_apoyo === 2"
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                                <path d="M12 22V12"/>
                                            </svg>
                                        </span>
                                        {{ String(solicitud.id).padStart(5, '0') }}
                                    </span>
                                </el-tooltip>
                            </div>
                            <!-- Si no hay solicitudes -->
                            <div v-else class="text-[10px] text-gray-400 py-1">
                                Sin solicitudes en el periodo
                            </div>
                        </div>
                        <div v-else-if="porcentajeSolicitudesMinimo >= 10" class="inline-flex items-center gap-2 px-2.5 py-0.5 backdrop-blur-md dark:border-gray-700/40 shadow-2xs w-full mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[11px] font-medium text-gray-500 dark:text-gray-300 tracking-tight">
                                {{ kpisRecursosPublicos.monto_minimo?.length === 1 ? 'Este único apoyo representa el' : 'Estos apoyos representan el' }} 
                                <strong class="font-bold text-color1-700 dark:text-emerald-400">
                                    {{ porcentajeSolicitudesMinimo }}%
                                </strong> 
                                del total de solicitudes en el periodo.
                            </span>
                        </div>
                        <div v-else-if = "kpisRecursosPublicos.total_solicitudes > 0" class="inline-flex items-center gap-2 px-2.5 py-1.5 dark:bg-gray-800/40 backdrop-blur-md dark:border-gray-700/40 w-full shadow-2xs mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[10px] font-medium text-gray-600 dark:text-gray-300 tracking-tight">
                                Menor importe aprobado en una solicitud
                            </span>
                        </div>
                        <div
                            v-else
                            key="sinSolicitudes"
                            class="inline-flex items-center gap-2 px-2.5 py-1.5 backdrop-blur-md dark:border-gray-700/40 shadow-2xs w-full mt-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-color1-600 flex-shrink-0"></span>
                            <span style="line-height: 1.25em;" class="text-[10px] font-medium text-gray-600 dark:text-gray-300 tracking-tight">
                                No se registraron solicitudes en este periodo
                            </span>
                        </div>
                    </Transition>
                </div> 
            </div>
        </div>
    </div>

    <section v-if="props.cargando" class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden animate-pulse">
        <div class="grid grid-cols-1 gap-0 lg:grid-cols-[3fr_1fr]">
            <!-- Columna principal -->
            <div class="px-5 py-2 border-b border-gray-100 dark:border-gray-700">
                <!-- Encabezado -->
                <div class="px-1 pt-4 pb-0 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="h-3 w-72 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="mt-2 h-2.5 w-56 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                        <!-- Total solicitudes -->
                        <div class="shrink-0 text-right">
                            <div class="h-7 w-12 ml-auto rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="mt-2 h-2 w-16 ml-auto rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                    </div>
                    <!-- Badge -->
                    <div class="shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700/60">
                        <div class="w-3.5 h-3.5 rounded bg-gray-300 dark:bg-gray-600"></div>
                        <div class="h-2 w-12 rounded bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                </div>
                <!-- Subtítulo de parámetros -->
                <div class="pt-3 mb-4">
                    <div class="h-2.5 w-48 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                </div>
                <!-- Skeleton de gráfica -->
                <div class="h-[280px] sm:h-[300px] lg:h-[320px] flex flex-col justify-end px-3 pb-4">
                    <!-- Líneas horizontales -->
                    <div class="flex-1 flex flex-col justify-between pb-2">
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                        <div class="w-full border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                        <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                    </div>
                    <!-- Barras simuladas -->
                    <div class="h-[75%] flex items-end justify-around gap-3 px-4 -mt-[230px]">
                        <div class="w-8 sm:w-10 h-[35%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[62%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[45%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[80%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[55%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[92%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[68%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                        <div class="w-8 sm:w-10 h-[42%] rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                    <!-- Etiquetas inferiores -->
                    <div class="flex justify-around gap-3 px-3 mt-3">
                        <div v-for="i in 8" :key="i" class="h-2 w-7 rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                </div>
            </div>
            <!-- Columna lateral -->
            <div class="bg-white dark:bg-gray-800 py-4 px-5 border-l border-gray-200 dark:border-gray-700">
                <!-- Encabezado de capas -->
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                        <div class="h-5 w-5 rounded bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="h-3 w-36 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-2 h-2 w-44 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                </div>
                <!-- Botón restablecer -->
                <div class="w-full h-9 mt-3 mb-2 rounded-full bg-gray-100 dark:bg-gray-700/60"></div>
                <!-- Opciones de capas -->
                <div class="mt-4 grid grid-cols-1 gap-2">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="flex items-center gap-3 rounded-lg border border-gray-100 dark:border-gray-700 px-3 py-3">
                        <!-- Checkbox -->
                        <div class="h-4 w-4 shrink-0 rounded bg-gray-200 dark:bg-gray-600"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <div class="h-2.5 w-3.5 rounded-sm bg-gray-300 dark:bg-gray-600"></div>
                                <div
                                    class="h-2.5 rounded bg-gray-200 dark:bg-gray-700"
                                    :class="i === 4 ? 'w-32' : 'w-28'"
                                ></div>
                            </div>
                            <div class="mt-2 h-2 w-full max-w-[190px] rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section v-else class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="grid grid-cols-1 gap-0 lg:grid-cols-[3fr_1fr]">
            <div class="px-5 py-2 border-b border-gray-100 dark:border-gray-700">
                <div class="px-1 pt-4 pb-0 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1 flex items-center justify-between gap-3">

                        <!-- Título y descripción -->
                        <div class="min-w-0">
                            <h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase">
                                Análisis de Recursos Públicos y Montos Aprobados
                            </h3>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Evolución mensual, promedios y control presupuestal.
                            </p>
                        </div>

                         <div class="shrink-0 text-left sm:text-right">
                        <div class="text-2xl font-bold leading-none text-gray-900 dark:text-white">
                            {{ kpisRecursosPublicos.total_solicitudes }}
                        </div>
                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Solicitudes
                        </div>
                    </div>
               
                    </div>
                        <span
                            v-if="idTipoApoyo === 1"
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <path d="M6 12h.01M18 12h.01"/>
                            </svg>
                            Efectivo
                        </span>
                        <span
                            v-else-if="idTipoApoyo === 2"
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                            En especie
                        </span>
                        <span
                            v-else
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <path d="M6 12h.01M18 12h.01"/>
                            </svg> /
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                        </span>
                    </div>

                <div v-if="kpisRecursosPublicos.total_solicitudes != 0" class="pt-3 mb-4">
                    <span class="text-[11px] text-gray-400 italic block">
                        {{ subtituloParametros }}
                    </span>
                </div>
                
                <div v-if="kpisRecursosPublicos.total_solicitudes === 0"
                    class="my-4 mx-0 flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-12 dark:border-gray-700 dark:bg-gray-900/30">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500 mb-3 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                            <line x1="9" y1="13" x2="15" y2="13" />
                            <line x1="9" y1="17" x2="13" y2="17" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Sin registros en este periodo
                    </h3>
                    <p class="text-xs text-center text-gray-400 dark:text-gray-500 max-w-sm mt-1">
                        No se encontraron solicitudes registradas para las fechas seleccionadas. Intenta cambiando el periodo en el filtro superior.
                    </p>
                </div>
                <div v-else class="h-[280px] sm:h-[300px] lg:h-[320px]">
                    <Chart type="bar" :data="chartData" :options="chartOptions" :plugins="chartPlugins" />
                </div>
            </div>
            <div v-if="kpisRecursosPublicos.total_solicitudes != 0" class="bg-white py-4 px-5 border-l
                border-gray-200">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg
                                bg-color1-40 text-color1-600
                                dark:bg-gray-800 dark:text-gray-300">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M12 3 3 8l9 5 9-5-9-5Z" />
                                <path d="m3 12 9 5 9-5" />
                                <path d="m3 16 9 5 9-5" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h4
                                class="text-xs font-bold uppercase leading-snug tracking-wide
                                    text-gray-800 dark:text-gray-100">
                                Capas de visualización
                            </h4>

                            <p class="mt-0 text-[10px] leading-relaxed text-gray-500 dark:text-gray-400">
                                Personaliza la información de la gráfica
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="restablecerCapas"
                        class="w-full rounded-full border border-gray-200 px-3 py-2 mb-2
                            text-xs font-medium text-gray-500
                            transition-all duration-200 ease-out
                            hover:border-color1-200 hover:bg-color1-500 hover:text-white
                            active:scale-95 active:bg-color1-700
                            dark:border-gray-700 dark:text-gray-400
                            dark:hover:border-color1-800 dark:hover:bg-color1-900/20
                            dark:hover:text-color1-300
                            dark:active:bg-color1-900/40">
                        Restablecer capas
                    </button>
                </div>

                 <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-1">
                    <label class="flex items-center gap-3 rounded-lg
                                border border-color1-800/15 bg-color1-800/5 px-3 py-2
                                transition hover:bg-color1-800/10
                                dark:border-color1-900/60 dark:bg-color1-900/15
                                dark:hover:bg-color1-900/25"
                                :class="{
                                'opacity-80': !capaMontoEntregado,
                                'cursor-not-allowed': capaMontoEntregado && !capaMontoPendiente && !capaMontoTotal,
                                'cursor-pointer': capaMontoPendiente || capaMontoTotal
                        }">
                        <input
                            type="checkbox"
                            :checked="capaMontoEntregado"
                            @change="cambiarCapa('montoEntregado')"
                            :disabled="capaMontoEntregado && !capaMontoPendiente && !capaMontoTotal"
                            class="h-4 w-4 rounded border-color1-300 text-color1-700 focus:ring-color1-700 disabled:cursor-not-allowed"/>

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2 text-xs font-semibold
                                        text-color1-900 dark:text-color1-300">
                                <span class="h-2 w-3.5 shrink-0 rounded-sm
                                            bg-color1-700"></span>
                                Monto entregado
                            </span>
                            <span class="mt-1 block text-[10px] leading-relaxed
                                        text-gray-500 dark:text-gray-400">
                                Importe de los apoyos ya porporcionados.
                            </span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 rounded-lg
                                border border-color1-500/10 bg-color1-500/5 px-3 py-2
                                transition hover:bg-color1-100/30
                                dark:border-color1-900/60 dark:bg-color1-900/15
                                dark:hover:bg-color1-900/25"
                                :class="{
                                'opacity-80': !capaMontoPendiente,
                                'cursor-not-allowed': !capaMontoEntregado && capaMontoPendiente && !capaMontoTotal,
                                'cursor-pointer': capaMontoEntregado || capaMontoTotal
                        }">
                        <input
                            type="checkbox"
                            :checked="capaMontoPendiente"
                            @change="cambiarCapa('montoPendiente')"
                            :disabled="!capaMontoEntregado && capaMontoPendiente && !capaMontoTotal"
                            class="h-4 w-4 rounded border-color1-500/80 text-color1-500 focus:ring-color1-500 disabled:cursor-not-allowed"/>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2 text-xs font-semibold
                                        text-color1-500 dark:text-color1-300">
                                <span class="h-2 w-3.5 shrink-0 rounded-sm
                                            bg-color1-500"></span>
                                Monto pendiente
                            </span>
                            <span class="mt-1 block text-[10px] leading-relaxed
                                        text-gray-500 dark:text-gray-400">
                                Recursos autorizados sin entregar.
                            </span>
                        </span>
                    </label>
                      <label
                        class="flex items-center gap-3 rounded-lg
                            border border-color1-900/15 bg-color1-900/5 px-3 py-2
                            transition hover:bg-color1-900/10"
                         :class="{
                            'opacity-80': !capaMontoTotal,
                            'cursor-not-allowed': !capaMontoEntregado && !capaMontoPendiente && capaMontoTotal,
                            'cursor-pointer': capaMontoEntregado || capaMontoPendiente
                        }">
                        <input
                            type="checkbox"
                            :checked="capaMontoTotal"
                            @change="cambiarCapa('montoTotal')"
                            :disabled="!capaMontoEntregado && !capaMontoPendiente && capaMontoTotal"
                            class="h-4 w-4 rounded border-color1-900 text-color1-900
                                focus:ring-color1-900"/>

                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-center gap-2 text-xs font-semibold
                                    text-color1-900 dark:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 shrink-0 text-color1-900"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 17l6-6 4 4 8-9"/>
                                </svg>
                                Monto total
                            </span>

                            <span
                                class="mt-1 block text-[10px] leading-relaxed
                                    text-gray-600 dark:text-gray-400">
                                Importe global de los recursos aprobados.
                            </span>
                        </span>
                    </label> 
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg
                            border border-gray-300 bg-gray-100 px-3 py-2
                            transition hover:bg-gray-200/80
                            dark:border-gray-700 dark:bg-gray-800/40
                            dark:hover:bg-gray-800/70"
                        :class="{ 'opacity-80': !capaTablaDesglose }">
                        <input
                            type="checkbox"
                            :checked="capaTablaDesglose"
                            @change="cambiarCapa('tablaDesglose')"
                            class="h-4 w-4 rounded border-gray-300 text-gray-600
                                focus:ring-gray-500"/>

                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-center gap-2 text-xs font-semibold
                                    text-gray-800 dark:text-gray-200">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 shrink-0 text-gray-500"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 10h18M9 10v10M15 10v10"
                                    />
                                </svg>

                                Tabla de desglose
                            </span>

                            <span
                                class="mt-1 block text-[10px] leading-relaxed
                                    text-gray-600 dark:text-gray-400">
                                Detalle mensual, indicadores y totales.
                            </span>
                        </span>
                    </label> 
                </div> 
            </div>
            <div v-else class="flex flex-col gap-4 py-4 px-5 h-full">
                <div class="flex items-center gap-2 ">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center
                                rounded-lg bg-gray-100 text-gray-500 mb-2
                                dark:bg-gray-800 dark:text-gray-400">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12 3 3 8l9 5 9-5-9-5Z" />
                            <path d="m3 12 9 5 9-5" />
                            <path d="m3 16 9 5 9-5" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider
                                text-gray-700 dark:text-gray-200">
                            Capas de visualización
                        </p>
                        <p class="text-[10px] text-gray-400 text-left dark:text-gray-500">
                            Configuración de visualización
                        </p>
                    </div>
                </div>

                <div class="flex flex-1 flex-col rounded-xl border border-dashed border-gray-200
                            bg-gray-50/80 p-4 text-center justify-center
                            dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center
                                justify-center rounded-full bg-gray-100
                                text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m0 3h.008M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Capas no disponibles
                    </p>

                    <p class="mt-1.5 text-[11px] leading-relaxed
                            text-gray-500 dark:text-gray-400">
                        Se habilitarán cuando existan registros de solicitudes en el periodo seleccionado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabla de datos y botón de exportación a Excel -->
    <section v-if="capaTablaDesglose" class="mt-4 py-5 px-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <!-- Cabecera de la tabla con título y botón de exportar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider dark:text-gray-100">
                        Desglose de Datos Financieros
                    </h4>
                    <span v-if="props.rawDataFromBackend?.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-color1-50 text-color1-800 dark:bg-color1-900/60 dark:text-color1-300">
                        {{ props.rawDataFromBackend.length }} meses
                    </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Resumen mensual correspondiente a los indicadores mostrados en el gráfico.
                </p>
            </div>

            <!-- Botón Exportar a Excel -->
            <button
                v-if="props.rawDataFromBackend && props.rawDataFromBackend.length > 0"
                @click="exportarAExcel" 
                type="button"
                class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold text-emerald-50 bg-emerald-700 hover:bg-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition-all shadow-2xs">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14.2 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.8L14.2 2zM13 3.5L18.5 9H13V3.5zM8.8 17.5l1.7-3.1 1.7 3.1h1.8l-2.6-4.5 2.5-4.3h-1.8l-1.6 2.9-1.6-2.9H8.1l2.5 4.3-2.6 4.5h1.8z" />
                    </svg>
                    <span>Exportar a Excel (CSV)</span>
            </button>
        </div>   
        <div v-if="!props.rawDataFromBackend || props.rawDataFromBackend.length === 0" class="py-10 px-4 flex flex-col items-center justify-center bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500 mb-2 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <rect x="4" y="4" width="16" height="16" rx="2" />
                        <line x1="4" y1="10" x2="20" y2="10" />
                        <line x1="10" y1="4" x2="10" y2="20" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    No hay información tabular disponible para este periodo.
                </p>
        </div>

        <!-- Contenedor responsive de la tabla -->
        <div v-else class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <table class="w-full text-left text-xs">
                <!-- Encabezados -->
                <thead
                    class="bg-gray-50 dark:bg-gray-900/70
                        text-gray-600 dark:text-gray-400
                        font-bold border-b border-gray-200
                        dark:border-gray-800 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">
                            Mes / Período
                        </th>
                        <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">
                            Monto Entregado
                        </th>
                        <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">
                            Monto Pendiente
                        </th>
                        <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">
                            Monto Total
                        </th>
                    </tr>
                </thead>

                <!-- Cuerpo -->
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    <tr
                        v-for="(item, index) in props.rawDataFromBackend"
                        :key="index"
                        class="hover:bg-gray-50/80 dark:hover:bg-gray-900/40 transition-colors">
                        <!-- Mes -->
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200 whitespace-nowrap">
                            {{ item.mes }}
                        </td>

                        <!-- Monto Entregado: Emerald -->
                        <td class="px-4 py-3 text-right tabular-nums">
                            <span
                                v-if="Number(item.monto_entregado) > 0"
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-800/10 px-2 py-1
                                    text-xs font-semibold text-color1-800
                                    dark:bg-emerald-950/50 dark:text-emerald-300">
                                ${{
                                    Number(item.monto_entregado).toLocaleString('es-MX', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })
                                }}
                            </span>
                            <span v-else class="text-gray-400 dark:text-gray-600">
                                —
                            </span>
                        </td>

                        <!-- Monto Pendiente: Amber -->
                        <td class="px-4 py-3 text-right tabular-nums">
                            <span
                                v-if="Number(item.monto_pendiente_entrega) > 0"
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-500/10 px-2 py-1
                                    text-xs font-semibold text-color1-500
                                    dark:bg-amber-950/50 dark:text-amber-300">
                                ${{
                                    Number(item.monto_pendiente_entrega).toLocaleString('es-MX', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })
                                }}
                            </span>
                            <span v-else class="text-gray-400 dark:text-gray-600">
                                —
                            </span>
                        </td>

                        <!-- Monto Total: color1 -->
                        <td class="px-4 py-3 text-right tabular-nums">
                            <span v-if="((Number(item.monto_entregado) || 0) +
                                        (Number(item.monto_pendiente_entrega) || 0)) > 0"
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-900/10 px-2 py-1
                                    text-xs font-bold text-color1-900
                                    dark:bg-color1-950/40 dark:text-color1-200">
                                ${{
                                    (
                                        (Number(item.monto_entregado) || 0) +
                                        (Number(item.monto_pendiente_entrega) || 0)
                                    ).toLocaleString('es-MX', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })
                                }}
                            </span>
                             <span v-else class="text-gray-400 dark:text-gray-600">
                                —
                            </span>
                        </td>
                    </tr>
                </tbody>

                <!-- Totales generales -->
                <tfoot
                    class="bg-gray-50/90 dark:bg-gray-900/90
                        font-bold text-gray-900 dark:text-gray-100
                        border-t-2 border-gray-200 dark:border-gray-800">
                    <tr>
                        <td class="px-4 py-3 uppercase tracking-wider whitespace-nowrap">
                            Total General
                        </td>

                        <!-- Total entregado -->
                        <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400">
                               <span
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-800/10 px-2 py-1
                                    text-[16px] font-bold text-color1-800
                                    dark:bg-emerald-950/40 dark:text-emerald-200">
                            ${{
                                totalMontoEntregado.toLocaleString('es-MX', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })
                            }}
                            </span>
                        </td>

                        <!-- Total pendiente -->
                        <td class="px-4 py-3 text-right text-amber-700 dark:text-amber-400">
                                        <span
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-500/10 px-2 py-1
                                    text-[16px] font-bold text-color1-500
                                    dark:bg-amber-950/40 dark:text-amber-200">
                            ${{
                                totalMontoPendiente.toLocaleString('es-MX', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })
                            }}
                            </span>
                        </td>

                        <!-- Total general: color1 -->
                        <td class="px-4 py-3 text-right text-color1-700 dark:text-color1-200">
                            <span v-if="totalMontoEntregado + totalMontoPendiente > 0"
                                class="inline-flex items-center justify-end
                                    rounded-md bg-color1-900/10 px-2 py-1
                                    text-[16px] font-bold text-color1-900
                                    dark:bg-color1-950/40 dark:text-color1-200">
                            ${{
                                (totalMontoEntregado + totalMontoPendiente).toLocaleString('es-MX', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })
                            }}
                            </span>
                             <span v-else class="text-gray-400 dark:text-gray-600">
                                —
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>   
    </section>

    <div v-if="props.cargando" class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-6 items-start xl:items-stretch animate-pulse">
        <!-- CARD 1: LOCALIDADES -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden px-5 pt-5 pb-2 flex flex-col gap-4"
            :class="props.rawDataLocalidades?.length > 30
                ? 'h-[800px]'
                : props.rawDataLocalidades?.length > 20
                    ? 'h-[700px]'
                    : 'h-[600px]'">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
                <div class="flex-1 min-w-0">
                    <div class="h-3 w-64 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="mt-2 h-2.5 w-52 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Registros -->
                    <div class="h-5 w-16 rounded-md bg-gray-200 dark:bg-gray-700"></div>

                    <!-- Efectivo -->
                    <div class="h-7 w-20 rounded-lg bg-gray-100 dark:bg-gray-700/70"></div>
                </div>
            </div>

            <!-- Parámetros y selector -->
            <div class="-mt-1 -mb-2 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:items-end w-full min-w-0">
                <div class="min-w-0">
                    <div class="h-2.5 w-48 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                </div>

                <div class="space-y-2 w-full min-w-0 sm:w-[220px] sm:shrink-0">
                    <div class="h-2 w-16 ml-1 rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-9 w-full rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700/50"></div>
                </div>
            </div>

            <!-- Gráfica de localidades -->
            <div class="w-full h-full flex flex-col min-h-0">
                <div class="relative w-full h-full min-h-0 flex flex-col justify-between py-3 px-2">

                    <!-- Líneas horizontales -->
                    <div class="absolute inset-0 flex flex-col justify-between py-4">
                        <div v-for="i in 7" :key="i"
                            class="w-full border-t border-dashed border-gray-200 dark:border-gray-700">
                        </div>
                    </div>

                    <!-- Barras horizontales -->
                    <div class="relative flex-1 flex flex-col justify-around gap-3 py-2 px-2">
                        <div
                            v-for="(width, i) in [88, 72, 95, 55, 80, 63, 91, 45, 68, 77]"
                            :key="i"
                            class="h-5 w-full flex items-center gap-3">
                            <div class="w-20 sm:w-28 shrink-0 h-2 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex-1 h-4 rounded-r-md bg-gray-200 dark:bg-gray-700"
                                :style="{ width: `${width}%`, maxWidth: `${width}%` }">
                            </div>
                        </div>
                    </div>

                    <!-- Etiquetas del eje X -->
                    <div class="relative flex justify-around gap-2 px-2 mt-3">
                        <div v-for="i in 10" :key="i"
                            class="h-2 w-6 rounded bg-gray-200 dark:bg-gray-700">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- COLUMNA DERECHA -->
        <div class="h-full flex flex-col gap-3 min-h-0">
            <!-- CARD 2: ÁREAS -->
            <div class="h-full flex-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 pt-5 pb-2 flex flex-col justify-between overflow-hidden min-h-0">
                <!-- Encabezado -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
                    <div class="flex-1 min-w-0">
                        <div class="h-3 w-40 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-2 h-2.5 w-56 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>

                    <div class="h-7 w-20 shrink-0 rounded-lg bg-gray-100 dark:bg-gray-700/70"></div>
                </div>
                <!-- Parámetros y selector -->
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:items-end w-full min-w-0 pb-2 mb-2 md:pb-0">
                    <div class="min-w-0">
                        <div class="h-2.5 w-48 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                    <div class="space-y-2 w-full min-w-0 sm:w-[220px] sm:shrink-0">
                        <div class="h-2 w-16 ml-1 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-9 w-full rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700/50"></div>
                    </div>
                </div>
                <!-- Gráfica de áreas -->
                <div class="w-full flex-1 flex flex-col justify-center min-h-0 py-3">
                    <div class="relative flex-1 flex flex-col justify-between px-2">
                        <div class="absolute inset-0 flex flex-col justify-between py-2">
                            <div v-for="i in 3" :key="i"
                                class="w-full border-t border-dashed border-gray-200 dark:border-gray-700">
                            </div>
                        </div>
                        <!-- Barras horizontales -->
                        <div
                            v-for="(width, i) in [75, 95, 48, 82]"
                            :key="i"
                            class="h-5 w-full flex items-center gap-3">
                            <div class="w-20 sm:w-28 shrink-0 h-2 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div
                                class="h-4 rounded-r-md bg-gray-200 dark:bg-gray-700"
                                :style="{ width: `${width}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CARD 3: ESTATUS -->
            <div class="h-[500px] xl:h-full xl:flex-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-4 flex flex-col justify-between overflow-hidden min-h-0">
                <!-- Encabezado -->
                <div class="flex flex-row items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-700/60 pb-4 shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="h-3 w-40 max-w-full rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-2 h-2.5 w-56 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                    <div class="h-7 w-20 shrink-0 rounded-lg bg-gray-100 dark:bg-gray-700/70"></div>
                </div>
                <!-- Parámetros -->
                <div class="mt-3 grid grid-cols-1 gap-3 pb-2 mb-2">
                    <div class="h-2.5 w-48 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                </div>
                <!-- Gráfica de estatus -->
                <div class="w-full flex-1 flex flex-col justify-center min-h-0 py-3">
                    <div class="relative flex-1 flex flex-col justify-between px-2">
                        <div class="absolute inset-0 flex flex-col justify-between py-0">
                            <div v-for="i in 6" :key="i"
                                class="w-full">
                            </div>
                        </div>
                        <!-- Barras horizontales -->
                        <div class="relative flex-1 flex flex-col justify-around gap-1 py-0 px-2">
                            <div
                                v-for="(width, i) in [90, 65, 82, 48]"
                                :key="i"
                                class="h-7 w-full flex items-center gap-3">
                                <div class="w-20 sm:w-28 shrink-0 h-2.5 rounded bg-gray-200 dark:bg-gray-700"></div>
                                <div class="h-5 rounded-r-md bg-gray-200 dark:bg-gray-700"
                                    :style="{ width: `${width}%` }">
                                </div>
                            </div>
                        </div>
                        <div class="relative flex justify-around gap-3 mt-3">
                            <div v-for="i in 5" :key="i"
                                class="h-2 w-10 rounded bg-gray-200 dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nota informativa inferior -->
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-start gap-2 shrink-0">
                    <div class="w-3.5 h-3.5 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-2 w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        <div class="h-2 w-4/5 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-6 items-start xl:items-stretch">
    <!-- ================= CARD 1: LOCALIDADES (Cambia aquí tu altura, ej: h-[500px]) ================= -->
    <div :class="[
    'bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden px-5 pt-5 pb-2 flex flex-col gap-4',
    props.rawDataLocalidades?.length <= 10
        ? 'h-[650px]'
        : props.rawDataLocalidades?.length <= 20
                ? 'h-[700px]'
                : props.rawDataLocalidades?.length <= 30
                    ? 'h-[750px]'
                    : 'h-[800px]']">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                    Análisis por Localidades Apoyadas
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Distribución de recursos públicos por localidad.
                </p>
            </div>
            <div class="flex items-center flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-color1-50 text-color1-800 dark:bg-color1-900/60 dark:text-color1-300">
                    {{ props.rawDataLocalidades?.length || 0 }}
                    {{ props.rawDataLocalidades?.length === 1 ? 'registro' : 'registros' }}
                </span>
                <span
                    v-if="idTipoApoyo === 1"
                    class="inline-flex shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg>
                    Efectivo
                </span>
                <span
                    v-else-if="idTipoApoyo === 2"
                    class="inline-flex shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                    En especie
                </span>
                <span
                    v-else
                    class="inline-flex shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg> /
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                </span>
            </div>
        </div>

        <div class="-mt-1 -mb-2 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:items-end w-full min-w-0">
            <div class="min-w-0 flex flex-col gap-1.5">
                <span class="text-[11px] text-gray-400 italic">
                    {{ subtituloParametros }}
                </span>

                <!-- Switch para incluir localidades sin monto -->
                <div class="flex items-center gap-2 pt-0.5">
                    <el-switch
                        v-model="incluirLocalidadesSinMonto"
                        @click="cambiarSwitchLocalidades"
                        size="small"/>
                    <span
                        class="text-[11px] font-medium text-gray-500 dark:text-gray-300 select-none">
                        Incluir sin monto ($0)
                    </span>
                </div>
            </div>

            <!-- Selector de ordenamiento -->
            <div v-if="props.rawDataLocalidades?.length" class="space-y-1 w-full min-w-0 sm:w-[220px] sm:shrink-0">
                <label class="block pl-1 text-[9px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Ordenar por
                </label>
                <el-select 
                    v-model="criterioOrdenLocalidades"
                    class="custom-select-gray custom-select-gray-orden !w-full w-full"
                    popper-class="custom-select-orden-popper"
                    placeholder="Seleccionar orden"
                    size="small"
                    @change="cambiarCriterioOrdenLocalidades">
                    <el-option label="Mayor Monto Total" value="total">
                        <div class="flex items-center">
                            <span class="w-2 h-5 mr-2"></span>
                            <span class="text-xs">Monto Total</span>
                        </div>
                    </el-option>
                    <el-option label="Mayor Monto Entregado" value="entregado">
                        <div class="flex items-center">
                            <span class="w-2 h-5 mr-2"></span>
                            <span class="text-xs">Mayor Monto Entregado</span>
                        </div>
                    </el-option>
                    <el-option label="Mayor Monto Pendiente" value="pendiente">
                        <div class="flex items-center">
                            <span class="w-2 h-5 mr-2"></span>
                            <span class="text-xs">Mayor Monto Pendiente</span>
                        </div>
                    </el-option>
                    <el-option label="Mayor Volumen de Solicitudes" value="volumen">
                        <div class="flex items-center">
                            <span class="w-2 h-5 mr-2"></span>
                            <span class="text-xs">Mayor Volumen de Solicitudes</span>
                        </div>
                    </el-option>
                </el-select>
            </div>
        </div>

        <div class="w-full h-full flex flex-col min-h-0">
            <div
                v-if="hasDataLocalidades"
                class="relative w-full min-h-0"
                :class="props.rawDataLocalidades?.length <= 10 ? 'h-[200px]' : 'h-full'">
                <Chart
                    type="bar"
                    :data="chartDataLocalidades"
                    :options="obtenerChartOptions('localidades')"
                    class="w-full h-full"/>
            </div>
            <div v-else class="h-full border border-dashed border-gray-200 dark:border-gray-700 rounded-xl flex flex-col items-center justify-center p-6 text-center w-full">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Sin registros de localidades en este periodo.
                </span>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: Añadimos min-h-0 para permitir que se reduzca -->
    <div class="h-full flex flex-col gap-3 min-h-0">
        <!-- ================= CARD 2: ÁREAS ================= -->
        <div class="h-full flex-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 pt-5 pb-2 flex flex-col justify-between overflow-hidden min-h-0">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                        Análisis por Áreas
                    </h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Distribución de recursos públicos por departamento o área.
                    </p>
                </div>
                <span
                    v-if="idTipoApoyo === 1"
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg>
                    Efectivo
                </span>
                <span
                    v-else-if="idTipoApoyo === 2"
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                    En especie
                </span>
                <span
                    v-else
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg> /
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:items-end w-full min-w-0 pb-2 mb-2 md:pb-0">
                 <div class="min-w-0 flex flex-col gap-1.5">
                    <span class="text-[11px] text-gray-400 italic">
                        {{ subtituloParametros }}
                    </span>

                    <!-- Switch para incluir localidades sin monto -->
                    <div class="flex items-center gap-2 pt-0.5">
                        <el-switch
                            v-model="incluirAreasSinMonto"
                            @click="cambiarSwitchAreas"
                            size="small"/>
                        <span
                            class="text-[11px] font-medium text-gray-500 dark:text-gray-300 select-none">
                            Incluir sin monto ($0)
                        </span>
                    </div>
                </div>

                <div v-if="props.rawDataAreas?.length" class="space-y-1 w-full min-w-0 sm:w-[220px] sm:shrink-0">
                    <label class="block pl-1 text-[9px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Ordenar por
                    </label>
                    <el-select 
                        v-model="criterioOrdenAreas"
                        class="custom-select-gray custom-select-gray-orden !w-full w-full"
                        popper-class="custom-select-orden-popper"
                        placeholder="Seleccionar orden"
                        size="small"
                        @change="cambiarCriterioOrdenAreas">
                        <el-option label="Mayor Monto Total" value="total">
                            <div class="flex items-center">
                                <span class="w-2 h-5 mr-2"></span>
                                <span class="text-xs">Monto Total</span>
                            </div>
                        </el-option>
                        <el-option label="Mayor Monto Entregado" value="entregado">
                            <div class="flex items-center">
                                <span class="w-2 h-5 mr-2"></span>
                                <span class="text-xs">Mayor Monto Entregado</span>
                            </div>
                        </el-option>
                        <el-option label="Mayor Monto Pendiente" value="pendiente">
                            <div class="flex items-center">
                                <span class="w-2 h-5 mr-2"></span>
                                <span class="text-xs">Mayor Monto Pendiente</span>
                            </div>
                        </el-option>
                        <el-option label="Mayor Volumen de Solicitudes" value="volumen">
                            <div class="flex items-center">
                                <span class="w-2 h-5 mr-2"></span>
                                <span class="text-xs">Mayor Volumen de Solicitudes</span>
                            </div>
                        </el-option>
                    </el-select>
                </div>
            </div>
            <div class="w-full h-full flex flex-col justify-center min-h-0">
                <div v-if="hasDataAreas" class="relative w-full flex-1 min-h-0">
                    <Chart type="bar" :data="chartDataAreas" :options="obtenerChartOptions('areas')" class="w-full h-full" />
                </div>
                <div v-else class="h-full border border-dashed border-gray-200 dark:border-gray-700 rounded-xl flex flex-col items-center justify-center p-6 text-center w-full">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        Sin registros de áreas en este periodo.
                    </span>
                </div>
            </div>
        </div>

        <!-- ================= CARD 3: POR ESTATUS ================= -->
        <div class="h-[500px] xl:h-full xl:flex-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col justify-between overflow-hidden min-h-0">
            <div class="flex flex-row items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-700/60 pb-4 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 text-left">
                        Análisis por Estatus
                    </h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 text-left">
                        Distribución de recursos públicos por estatus de las solicitudes.
                    </p>
                </div>

                <span
                    v-if="idTipoApoyo === 1"
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg>
                    Efectivo
                </span>
                <span
                    v-else-if="idTipoApoyo === 2"
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                    En especie
                </span>
                <span
                    v-else
                    class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg> /
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 sm:items-end w-full min-w-0 pb-2 mb-2 md:pb-0">
                <div class="min-w-0">
                    <span class="text-[11px] text-gray-400 italic">
                        {{ subtituloParametros }}
                    </span>
                </div>
            </div>
            <div class="w-full flex-1 flex flex-col justify-center min-h-0">                    
                <div v-if="isMounted && hasDataEstatus" class="relative w-full h-full min-h-0">
                    <div class="absolute inset-0">
                        <Chart type="bar" :data="chartDataEstatus" :options="chartOptionsEstatus" class="w-full h-full" />
                    </div>
                </div>

                <div v-else-if="!isMounted" class="h-full flex items-center justify-center">
                </div>

                <div v-else class="h-full border border-dashed border-gray-200 dark:border-gray-700 rounded-xl flex flex-col items-center justify-center p-6 text-center w-full">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        Sin registros de estatus en este periodo.
                    </span>
                </div>
            </div>
            <div v-if="isMounted && hasDataEstatus" class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Las solicitudes <b>canceladas</b> se muestran exclusivamente para consulta de estatus y no se contabilizan en los indicadores de esta página.</span>
            </div>
        </div>
    </div>
</div>
    <!-- Backdrop / Fondo oscuro animado -->
    <div v-if="asideAbierto" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 transition-opacity" @click="cerrarAside"></div>

    <!-- Aside deslizante desde la derecha -->
    <aside v-if="asideAbierto" 
        class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-800 flex flex-col transform transition-transform duration-300 ease-in-out">
        
        <!-- Cabecera del Aside -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-lg bg-color1-50 text-color1-700 dark:bg-color1-950 dark:text-color1-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">
                        Desglose de Montos Solicitados
                    </h2>
                    <p class="text-[11px] text-gray-400">Análisis estadístico y rangos de recursos</p>
                </div>
            </div>

            <button @click="cerrarAside" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Contenido scrolleable del Aside -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- SECCIÓN 1: Top 5 de Solicitudes con Más Dinero Pedido -->
            <div class="space-y-3">
                <div class="mb-3 space-y-2.5">
                    <!-- Primera fila: título e indicador -->
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider
                            text-gray-700 dark:text-gray-300">
                            Mayores Montos Solicitados
                        </h3>
                        <!-- Modalidad de apoyo -->
                        <span
                            v-if="idTipoApoyo === 1"
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <path d="M6 12h.01M18 12h.01"/>
                            </svg>
                            Efectivo
                        </span>
                        <span
                            v-else-if="idTipoApoyo === 2"
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                            En especie
                        </span>
                        <span
                            v-else
                            class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                <circle cx="12" cy="12" r="2"/>
                                <path d="M6 12h.01M18 12h.01"/>
                            </svg> /
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                        </span>
                    </div>

                    <!-- Segunda fila: descripción y selector -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-semibold uppercase tracking-wider font-medium text-gray-400 dark:text-gray-500">
                            Cantidad a mostrar
                        </span>
                        <!-- Selector Top 5 / Top 10 -->
                        <div class="inline-flex p-0.5 rounded-md
                            bg-gray-100 dark:bg-gray-800
                            text-[11px] font-semibold">
                            <button
                                type="button"
                                @click="limiteTop = 5"
                                :class="limiteTop === 5
                                    ? 'bg-white dark:bg-gray-700 text-color1-900 dark:text-white shadow-sm'
                                    : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'"
                                class="min-w-[58px] px-3.5 py-1 rounded transition-all duration-200">
                                Top 5
                            </button>
                            <button
                                type="button"
                                @click="limiteTop = 10"
                                :class="limiteTop === 10
                                    ? 'bg-white dark:bg-gray-700 text-color1-900 dark:text-white shadow-sm'
                                    : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'"
                                class="min-w-[58px] px-3.5 py-1 rounded transition-all duration-200">
                                Top 10
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ranking -->
                <div class="space-y-2">
                    <div v-for="(item, index) in topSolicitudes" 
                        :key="item.id"
                        class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800/60 overflow-hidden transition-all duration-200 hover:border-gray-200 dark:hover:border-gray-700 shadow-2xs">
                        <!-- Fila principal -->
                        <div class="flex items-center justify-between p-3 hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <!-- Número consecutivo con tono institucional (color1) -->
                                <span class="w-5 h-5 rounded-full bg-color1 text-white dark:bg-color1-950/60 dark:text-color1-300 text-[10px] font-bold flex items-center justify-center shrink-0 ring-1 ring-color1/90">
                                    {{ index + 1 }}
                                </span>
                                
                                <div class="min-w-0 pr-5">
                                    <div class="flex items-center gap-2">
                                        <!-- Nombre del solicitante -->
                                        <p class="text-xs  font-bold text-gray-800 dark:text-gray-200 truncate">
                                            {{ [item.solicitante?.nombre, item.solicitante?.apellidos].filter(Boolean).join(' ') || 'Ciudadano' }}
                                        </p>
                                    </div>

                                    <!-- Folio + Indicador visual de estatus -->
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span v-if="item.id_tipo_apoyo === 1 && !idTipoApoyo"                                            
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <rect width="20" height="12" x="2" y="6" rx="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <path d="M6 12h.01M18 12h.01"/>
                                            </svg>
                                        </span>
                                        <span
                                            v-else-if="item.id_tipo_apoyo === 2 && !idTipoApoyo" 
                                            class="inline-flex mr-0.5 shrink-0 items-center gap-1.5 px-0.5 py-0.5 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-2 h-2 shrink-0 transition-transform duration-200 group-hover:scale-110"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                                <path d="M12 22V12"/>
                                            </svg>
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-[10px] text-gray-400">
                                            <!-- Mini punto indicador de estatus -->
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                :class="{
                                                    'bg-amber-400': item.id_estatus == 1,
                                                    'bg-indigo-400': item.id_estatus == 2,
                                                    'bg-emerald-400': item.id_estatus == 3
                                                }"></span>
                                            <span>#{{ String(item.id).padStart(5, '0') }}</span>
                                        </span>
                                        <span class="w-px h-3 bg-gray-300 dark:bg-gray-700 shrink-0"></span>
                                        <span class="inline-flex items-center gap-1 text-[10px] text-gray-400">
                                            {{ item.localidad_beneficiada?.nombre }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="text-xs font-bold text-color1-800 dark:text-gray-100">
                                    ${{ Number(item.cantidad_aprobada).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </span>
                                
                                <!-- Icono de expansión sutil -->
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    @click.stop="item.expandido = !item.expandido"
                                    class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 cursor-pointer hover:text-color1-700 dark:hover:text-color1-300"
                                    :class="{ 'rotate-180': item.expandido }"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                                <span class="w-px h-4 bg-gray-200 dark:bg-gray-700"></span>
                                <svg @click="(item.id_estatus !== 4) ? verEditarSolicitud(item.id) : null"  xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                class="w-3.5 h-3.5 text-gray-400 lucide lucide-external-link preview-icon transition-transform duration-200 cursor-pointer hover:text-color1-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 3h6v6"/>
                                    <path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                </svg>
                            </div>                            
                        </div>

                        <!-- Panel expandible con detalle completo -->
                        <div v-if="item.expandido" class="px-3 pb-3 pt-2 bg-gray-50/60 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-800/60 text-[11px] text-gray-600 dark:text-gray-300">
                            <span class="font-semibold text-gray-400 block text-[9px] uppercase tracking-wider mb-0.5">Descripción de la petición:</span>
                            <p class="leading-relaxed">{{ item.peticion || 'Sin descripción detallada disponible.' }}</p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!topSolicitudes.length" 
                        class="text-center py-8 px-4 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">No hay solicitudes registradas en este periodo.</p>
                    </div>
                </div>
            </div>

            <hr class="border-gray-100 dark:border-gray-800">

            <!-- SECCIÓN 2: Distribución de Montos por Rangos -->
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-2">

                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Distribución por Rangos de Monto
                    </h3>
                    <span
                        v-if="idTipoApoyo === 1"
                        class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color2-50/60 text-color2-700 dark:bg-color1-950/40 dark:text-color1-300 dark:border-color1-800/50">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="20" height="12" x="2" y="6" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <path d="M6 12h.01M18 12h.01"/>
                        </svg>
                        Efectivo
                    </span>
                    <span
                        v-else-if="idTipoApoyo === 2"
                        class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color4-50/70 text-color4-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                        En especie
                    </span>
                    <span
                        v-else
                        class="inline-flex ml-2 shrink-0 items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium bg-color1-50/70 text-color1-700 dark:bg-color4-950/40 dark:text-color4-300 dark:border-color4-800/50">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="20" height="12" x="2" y="6" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <path d="M6 12h.01M18 12h.01"/>
                        </svg> /
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-3.5 h-3 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </span>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="rango in distribucionMontos"
                        :key="rango.key"
                        class="space-y-1">

                        <div class="flex justify-between items-center gap-2 text-xs">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">
                                {{ rango.label }}
                            </span>

                            <span class="font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ rango.cantidad }} solicitudes
                                <span class="text-gray-400 font-normal ml-2 mr-2">
                                    ({{ rango.porcentaje.toFixed(1) }}%)
                                </span>
                            </span>
                        </div>

                        <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-color1-600 to-color1-700
                                    h-full rounded-full transition-all duration-500"
                                :style="{ width: `${rango.porcentaje}%` }">
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="!distribucionMontos.some(rango => rango.cantidad > 0)"
                        class="text-center py-4 text-xs text-gray-400 dark:text-gray-500">
                        No hay solicitudes para distribuir.
                    </div>
                </div>
            </div>
        </div>
        <!-- Pie del Aside -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 text-center">
            <button @click="cerrarAside" class="w-full py-2 px-4 rounded-xl text-[13px] font-medium bg-gray-200/80 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors">
                Cerrar panel
            </button>
        </div>
    </aside>
</template>

<style>
/* Tooltip */
.el-popper.custom-tooltip {
    background-color: #000000 !important;
    color: #ffffff !important;
    font-size: 10px !important;
    border: 1px solid #333333 !important;
}

.el-popper.custom-tooltip .el-popper__arrow::before {
    background: #000000 !important;
    border-color: #333333 !important;
    box-shadow: none !important;
}

.el-popper.custom-tooltip[data-popper-placement^="top"] .el-popper__arrow::before {
    border: none !important;
}

/* Tooltip */
.el-popper.custom-tooltip {
    background: #000000 !important;
    border: 1px solid #333333 !important;
    overflow: visible !important;
}

/* Ocultar el rombo original */
.el-popper.custom-tooltip .el-popper__arrow {
    display: none !important;
}

/* Crear una flecha propia */
.el-popper.custom-tooltip[data-popper-placement^="top"]::after {
    content: "";
    position: absolute;
    width: 9px;
    height: 9px;
    background: #000000;
    border-right: 1px solid #333333;
    border-bottom: 1px solid #333333;
    transform: rotate(45deg);
    bottom: -5px;
    left: 50%;
    margin-left: -5px;
}

.cambio-info-enter-active,
.cambio-info-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}

.cambio-info-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.cambio-info-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}


</style>

<style scoped>
:deep(.custom-select-gray-orden .el-select__wrapper) {
    min-height: 30px !important;
    height: 30px !important;
    font-size: 10px !important;
}

:deep(.custom-select-gray-orden .el-select__selected-item),
:deep(.custom-select-gray-orden .el-select__placeholder) {
    font-size: 11px !important;
}

/* Altura del selector */
:deep(.custom-select-gray-orden .el-select__wrapper) {
    min-height: 30px !important;
    height: 30px !important;
}

/* Texto del elemento seleccionado y placeholder */
:deep(.custom-select-gray-orden .el-select__selected-item),
:deep(.custom-select-gray-orden .el-select__placeholder) {
    font-size: 12px !important;
}

/* Texto de las opciones desplegadas */
:global(.custom-select-orden-popper .el-select-dropdown__item) {
    font-size: 12px !important;
}

:global(.custom-select-orden-popper .el-select-dropdown__item) {
    font-size: 12px !important;
}

:global(.custom-select-orden-popper .el-select-dropdown__item span) {
    font-size: 12px !important;
}

/* Texto del elemento seleccionado */
:deep(.custom-select-gray-orden .el-select__selected-item),
:deep(.custom-select-gray-orden .el-select__selected-item span),
:deep(.custom-select-gray-orden .el-select__placeholder),
:deep(.custom-select-gray-orden .el-select__placeholder span) {
    font-size: 12px !important;
    color:rgb(110, 110, 110) !important;
    font-weight: 200 !important;
}
</style>
