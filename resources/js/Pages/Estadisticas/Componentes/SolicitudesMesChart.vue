<script setup>

    import { computed, ref } from 'vue';
    import { ElLoading } from 'element-plus';
    import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    LineElement, PointElement, BarElement,
    CategoryScale, LinearScale, 
    Filler
    } from 'chart.js';
    import annotationPlugin from 'chartjs-plugin-annotation';
    import { Line, Bar } from 'vue-chartjs';

    const props = defineProps({
        kpisSolicitudes: {
            type: Object,
            default: () => ({})
        },
        rawDataFromBackend: {
            type: Array,
            required: true,
            default: () => []
        },
        rangoFechasQuery: {
            type: Array,
            default: () => []
        },
        periodoActual: Object,
        cargando: {
            type: Boolean,
            default: true
        }
        });

        // Ejemplo de cómo puedes usarlos dentro de tus funciones o propiedades computadas:
        const totalRegistros = computed(() => {
        return props.rawDataFromBackend.length;
        });

    // const rangoFechasQuery = ref(props.rangoFechasQuery);

    const isMobile = window.innerWidth < 640;
    // Banderas de Capas Analíticas (para Solicitudes por Mes)
    const capaDesgloseEstatus = ref(false);
    const capaPromedio = ref(true);
    const capaTablaDesglose = ref(false);

    ChartJS.register(
        Title, Tooltip, Legend, LineElement, PointElement, BarElement,
        CategoryScale, LinearScale, annotationPlugin, Filler
        );

    ChartJS.defaults.font.family = 'Figtree, sans-serif';

    const tituloEstadisticaActual = "Evolución Mensual de Solicitudes Ciudadanas";

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
            // 5. Mes Actual (Mismo mes y año en curso, cubriendo del día 1 al último)
                if (mesInicioObj.getMonth() === mesFinObj.getMonth() && anioInicio === anioFin) {
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

            // 7. Semana Actual / Anterior (Detectando rangos de 7 días aproximados o coincidencia)
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

    // CONFIGURACIÓN REACTIVA DE CHART.JSabreviaturasmes
    const chartData = computed(() => {
        const dataList = props.rawDataFromBackend;
        if (!Array.isArray(dataList) || dataList.length === 0) {
            return { labels: [], datasets: [] };
        }

    const labels = props.rawDataFromBackend.map(item => {
    if (!item.mes) return '';

    // Separar el mes y el año (ej. "octubre 2026" -> ["octubre", "2026"])
    const partes = item.mes.toLowerCase().trim().split(' ');
    const mesTexto = partes[0];
    const anioTexto = partes[1] || '';

    if (isMobile) {
        // Diccionario de abreviaturas para dispositivos móviles
        const abreviaturas = {
        'enero': 'Ene', 'febrero': 'Feb', 'marzo': 'Mar', 'abril': 'Abr',
        'mayo': 'May', 'junio': 'Jun', 'julio': 'Jul', 'agosto': 'Ago',
        'septiembre': 'Sep', 'octubre': 'Oct', 'noviembre': 'Nov', 'diciembre': 'Dic'
        };
        
        const mesCorto = abreviaturas[mesTexto] || mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1, 3);
        return `${mesCorto} ${anioTexto}`;
    } 

    // Formato completo y capitalizado para computadora ("Octubre 2026")
    const mesCapitalizado = mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);
    return `${mesCapitalizado} ${anioTexto}`;
    });
 
    const datasets = [];

    const esUltimaBarraVisible = (context) => {
        const datasets = context.chart.data.datasets;
        const indiceActual = context.datasetIndex;
        const indiceMes = context.dataIndex;

        for (let i = datasets.length - 1; i > indiceActual; i--) {
            const dataset = datasets[i];

            if (
                dataset.type === 'bar' &&
                dataset.stack === 'stack-estatus' &&
                Number(dataset.data[indiceMes]) > 0
            ) {
                return false;
            }
        }

        return true;
    };

   if (capaDesgloseEstatus.value) {
        // Si activa "Desglose por Estatus" -> Se muestran barras apiladas
        datasets.push(
            {
                type: 'bar',
                label: 'En Trámite',
                data: props.rawDataFromBackend.map(i => i.enTramite),
                backgroundColor: 'rgba(217, 119, 6, 0.8)', // Ámbar
                borderColor: 'rgba(217, 119, 6, 0.8)',
                borderWidth: 1,
                stack: 'stack-estatus', // Agrupa las barras para que se apilen juntas
                borderRadius: (context) => {
                    if (!esUltimaBarraVisible(context)) {
                        return 0;
                    }

                    return {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
                },
            },
            {
                type: 'bar',
                label: 'Aprobadas',
                data: props.rawDataFromBackend.map(i => i.aprobadas),
                backgroundColor: '#6366f1', // Índigo
                borderColor: '#6366f1',
                borderWidth: 1,
                stack: 'stack-estatus',
                borderRadius: (context) => {
                    if (!esUltimaBarraVisible(context)) {
                        return 0;
                    }

                    return {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
                },
            },
            {
                type: 'bar',
                label: 'Canceladas',
                data: props.rawDataFromBackend.map(i => i.canceladas),
                backgroundColor: '#ef4444', // Rojo
                borderColor: '#ef4444',
                borderWidth: 1,
                stack: 'stack-estatus',
                borderRadius: (context) => {
                    if (!esUltimaBarraVisible(context)) {
                        return 0;
                    }

                    return {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
                },
            },
            {
                type: 'bar',
                label: 'Apoyo Entregado',
                data: props.rawDataFromBackend.map(i => i.apoyoEntregado),
                backgroundColor: '#10b981', // Esmeralda / Verde
                borderColor: '#10b981',
                borderWidth: 1,
                stack: 'stack-estatus',
                borderRadius: (context) => {
                    if (!esUltimaBarraVisible(context)) {
                        return 0;
                    }

                    return {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
                },
            },
        );
    } else {
        // Línea Principal de Solicitudes Totales
        datasets.push({
        label: 'Total de Solicitudes',
        data: props.rawDataFromBackend.map(i => i.total),
        borderColor: '#800020', // Guinda
        // backgroundColor: 'rgba(128, 0, 32, 0.08)',
        backgroundColor: (context) => {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return 'rgba(156,163,175,0.05)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(123,0,58,0.16)');
                    gradient.addColorStop(0.85, 'rgba(123,0,58,0.06)');
                    gradient.addColorStop(1, 'rgba(123,0,58,0)');
                    return gradient;
                },
        pointBackgroundColor: '#800020',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
        yAxisID: 'y'
        });
    }

    return { labels, datasets };
    });

    const chartOptions = computed(() => {
    // Cálculo dinámico del promedio para la línea de anotación
    const totalSum = props.rawDataFromBackend.reduce((acc, curr) => acc + curr.total, 0);
    const promedio = props.rawDataFromBackend.length > 0 ? (totalSum / props.rawDataFromBackend.length).toFixed(1) : 0;

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: 'bottom',
                align: 'middle',
                display: capaDesgloseEstatus.value,
                onClick: () => {},
                labels: {
                    boxWidth: 16,  // Ancho del indicador de color
                    boxHeight: 12, // Alto del indicador de color
                    padding: 12,
                    pointStyle: 'rectRounded',
                    usePointStyle: true,
                    font: {
                        size: 11,
                        weight: '500'
                    },
                }
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    title: (items) => {
                        if (!items.length) return '';

                        const indice = items[0].dataIndex;
                        const item = props.rawDataFromBackend[indice];

                        if (!item?.periodo_key) return item?.mes ?? '';

                        const [anio, mes] = item.periodo_key.split('-');

                        const fecha = new Date(
                            Number(anio),
                            Number(mes) - 1,
                            1
                        );

                        return fecha.toLocaleDateString('es-MX', {
                            month: 'short',
                            year: 'numeric'
                        })
                        .replace('.', '')
                        .replace(/^\p{L}/u, letra => letra.toLocaleUpperCase('es-MX'));
                    }
                }
            },
            datalabels: { display: false },
            annotation: { annotations: {} }
        },
        scales: {
        y: {
            beginAtZero: true,
            stacked: true,
            title: { display: true, text: 'Solicitudes', font: { size: 12 } },
            grid: { color: 'rgba(156, 163, 175, 0.1)' },
            ticks: {
            // Fuerza a que los ticks del eje Y sean estrictamente números enteros
            precision: 0,
            stepSize: 1,
            callback: function(value) {
                // Muestra solo valores enteros (evita 0.5, 1.5, etc.)
                if (Math.floor(value) === value) {
                return value;
                }
            }
            }
        },
        x: {
            grid: { display: false },
            stacked: true,
            offset: true,
            ticks: { 
                    font: { 
                        size: 11,        // <--- Cambia aquí el tamaño del texto (ej. 10, 11, 12, etc.)
                        weight: '500',   // Opcional: peso de la fuente ('normal', 'bold', '500', etc.)
                        family: 'Figtree, sans-serif' // Opcional: familia tipográfica
                    },
                    color: '#6b7280'     // Color del texto
                }
            }
        }
    };

    // Agregar línea de promedio si el switch está activo
    if (capaPromedio.value) {
        options.plugins.annotation.annotations.lineaPromedio = {
        type: 'line',
        yMin: promedio,
        yMax: promedio,
        borderColor: '#ef4444', // Línea roja punteada
        borderDash: [5, 5],
        borderWidth: 1.5,
        label: {
            content: `Media: ${promedio}`,
            display: true,
            position: 'end',
            // Fondo translúcido oscuro/sólido para proteger el texto del fondo de la gráfica
            backgroundColor: 'rgba(17, 24, 39, 0.85)', // Fondo gris oscuro elegante (Tailwind gray-900 con opacidad)
            color: '#ffffff', // Texto blanco brillante para un contraste máximo
            font: { size: 10, weight: 'bold' },
            padding: 6, // Espaciado interno para que no se vea apretado
            borderRadius: 4 // Bordes ligeramente redondeados
        }
        };
    }

    return options;
    });

    const exportarReporte = () => {
        if (!props.rawDataFromBackend || props.rawDataFromBackend.length === 0) {
            return;
        }

        // Activar indicador visual (Loading de Element Plus)
        const loadingInstance = ElLoading.service({
            lock: true,
            text: 'Generando reporte y preparando descarga...',
            background: 'rgba(15, 23, 42, 0.45)', // Fondo oscuro suave y translúcido (slate con 45% de opacidad)
            // O si prefieres un fondo claro translúcido, puedes usar: 'rgba(255, 255, 255, 0.8)'
        });

        const loadingText = document.querySelector('.el-loading-text');
        if (loadingText) {
            loadingText.style.color = '#ffffff';
            loadingText.style.fontWeight = '400';
        }

        // Simulamos un breve retraso estético de 600ms para que el usuario aprecie el layout de carga
        setTimeout(() => {
            try {
            // Encabezados para la primera fila
            const headers = ['Mes / Periodo', 'Solicitudes Totales', 'En Tramite', 'Aprobadas', 'Apoyos Entregados', 'Canceladas'];
            
            // Mapear datos a filas CSV
            const rows = props.rawDataFromBackend.map(row => [
                `"${row.mes ? row.mes.charAt(0).toUpperCase() + row.mes.slice(1) : ''}"`,
                row.total ?? 0,
                row.enTramite ?? 0,
                row.aprobadas ?? 0,
                row.apoyoEntregado ?? 0,
                row.canceladas ?? 0
            ]);

            // Construir cadena CSV con delimitador por comas
            const csvContent = [
                headers.join(','),
                ...rows.map(e => e.join(','))
            ].join('\n');

            // BOM (\uFEFF) para forzar la codificación UTF-8 en Microsoft Excel
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `desglose_solicitudes_mes_${new Date().toISOString().slice(0, 10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            } catch (error) {
            console.error('Error al exportar el reporte:', error);
            } finally {
            // Asegurar de cerrar el loading pase lo que pase
            loadingInstance.close();
            }
        }, 600);
        };

        const chartDataDiasSemana = computed(() => {
        const datos = props.kpisSolicitudes?.distribucion_dias_semana || [];
        

        return {
            labels: datos.map(item => {
                if (!item.dia_semana) return '';
                const dia = item.dia_semana.trim();
               
                // Recorta cualquier día a sus primeras 3 letras (ej. Lun, Mar, Mié, Jue, Vie...)
                return dia.charAt(0).toUpperCase() + dia.slice(1, 3).toLowerCase();
    
            }),
            datasets: [
                {
                    label: 'Total de Solicitudes',
                    data: datos.map(item => item.total || 0),
                    backgroundColor: '#800020', // Tono guinda institucional
                    borderRadius: 6,
                }
            ]
        };
    });

const chartOptionsBar = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
          datalabels: {
                display: false,
                onClick: () => {},
            },
        // Si usas el plugin datalabels y quieres asegurarte de ocultar los números en las barras:
        // datalabels: { display: false },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleFont: { size: 11, weight: 'bold' },
            bodyFont: { size: 11 },
            padding: 10,
            cornerRadius: 8,
        }
    },
    scales: {
        x: { 
            grid: { display: false }, 
            ticks: { font: { size: 10 } } 
        },
        y: { 
            grid: { color: 'rgba(243, 244, 246, 0.6)' }, 
            ticks: { font: { size: 10 }, precision: 0 },
            // Configuración para el título del Eje Y
            title: {
                display: true,
                text: 'Solicitudes',
                font: { size: 10 },
                color: '#6b7280' // Color gris sutil acorde al diseño (Tailwind gray-500)
            }
        }
    }
};

    // Validador genérico basado en días (ideal para semanas, quincenas o periodos cortos)
    const validarRangoDias = (minimoDiasRequeridos) => {
        if (!props.rangoFechasQuery || !props.rangoFechasQuery[0] || !props.rangoFechasQuery[1]) return false;

        const fechaInicio = new Date(props.rangoFechasQuery[0]);
        const fechaFin = new Date(props.rangoFechasQuery[1]);

        // Calculamos la diferencia total en días (+1 para incluir el día de inicio)
        const diferenciaMs = fechaFin - fechaInicio;
        const totalDias = (diferenciaMs / (1000 * 60 * 60 * 24)) + 1;

        return totalDias >= minimoDiasRequeridos;
    };

    // Cada una mantiene su propia propiedad computada y su requerimiento específico
    const esRangoValidoEvolucionSolicitudes = computed(() => validarRangoDias(90));
    const esRangoValidoDemandaDia = computed(() => validarRangoDias(7));
    const esRangoValidoTabla = computed(() => validarRangoDias(29));

     const restablecerCapas = () => {
        capaDesgloseEstatus.value = false
        capaPromedio.value = true
        capaTablaDesglose.value = false
    };

    const mostrarModalDiasPico = ref(false);
</script>

<template>
    <el-dialog
    v-model="mostrarModalDiasPico"
    title="Fechas con mayor demanda"
    width="420px"
    destroy-on-close
    style="border-radius: 20px; overflow: hidden;">
        <div class="space-y-3">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">
                        Máximo diario
                    </p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white tabular-nums">
                        {{ kpisSolicitudes.dia_mayor_demanda.total_solicitudes }}
                        <span class="text-sm font-bold text-gray-500">
                            solicitudes
                        </span>
                    </p>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300 text-xs font-bold">
                    {{ kpisSolicitudes.dia_mayor_demanda.total_dias_pico }}
                    días
                </div>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="(dia, index) in kpisSolicitudes.dia_mayor_demanda.dias_pico"
                    :key="dia.fecha_demanda"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-6 h-6 rounded-full bg-color1-100 dark:bg-color1-900/50 text-color1-700 dark:text-color1-300 flex items-center justify-center text-[10px] font-bold shrink-0">
                            {{ index + 1 }}
                        </span>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">
                            {{ dia.fecha_demanda }}
                        </span>
                    </div>
                    <span class="text-xs font-bold text-color1-600 dark:text-color1-400 shrink-0">
                        {{ dia.total_solicitudes }}
                    </span>
                </div>
            </div>
        </div>
        <template #footer>
            <button
                type="button"
                @click="mostrarModalDiasPico = false"
                class="px-5 py-2 text-sm font-semibold text-white dark:text-gray-300
                    bg-color1-700 dark:bg-gray-800
                    border border-gray-200
                    hover:bg-color1-500
                    rounded-full
                    active:scale-[0.98]
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-color1-500 focus-visible:ring-offset-2
                    dark:focus-visible:ring-offset-gray-900
                    transition-all duration-200">
                Cerrar
            </button>
        </template>
    </el-dialog>
    <div v-if="props.cargando" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5">
        <div
            v-for="i in 4"
            :key="i"
            class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex-1 space-y-3">
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                </div>

                <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
            </div>
        </div>
    </div>  
    <!-- Contenedor principal con Grid adaptable -->
    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5">
        <!-- 1. Total de Solicitudes -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
            <div class="flex flex-col justify-between h-full">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                    Total de Solicitudes
                </span>
                <div class="text-3xl font-black text-gray-900 dark:text-white my-1 tabular-nums">
                    {{ props.rawDataFromBackend?.reduce((acc, curr) => acc + (curr.total || 0), 0) ?? 0 }}
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ (props.rawDataFromBackend?.length ?? 0) > 0 ? 'Acumulado en el periodo' : 'Sin registros para este periodo' }}
                </span>
            </div>
            <div class="p-2.5 bg-color1-50 dark:bg-color1-900/50 text-color1-600 dark:text-color1-400 rounded-lg self-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>

        <!-- 2. Promedio Diario -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
            <div class="flex flex-col justify-between h-full">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                    Promedio Diario
                </span>
                <div class="text-3xl font-black text-gray-900 dark:text-white my-1 tabular-nums">
                    {{ Number(kpisSolicitudes?.promedio_diario || 0).toFixed(1) ?? '0.00' }}
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ (kpisSolicitudes?.promedio_diario ?? 0) > 0 ? 'Solicitudes / día' : 'Sin actividad en el periodo' }}
                </span>
            </div>
            <div class="p-2.5 bg-color1-50 dark:bg-color1-900/50 text-color1-600 dark:text-color1-400 rounded-lg self-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>

        <!-- 3. Pico Máximo de Atención -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
            <div class="flex flex-col justify-between h-full w-full min-w-0">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-200">
                    Pico Máximo de Atención
                </span>
                <template v-if="kpisSolicitudes?.dia_mayor_demanda">
                    <!-- Sin pico: máximo de 1 solicitud por día -->
                    <template v-if="kpisSolicitudes.dia_mayor_demanda.total_dias_pico === 0">
                        <div class="text-lg font-black text-gray-900 dark:text-white my-1">
                            Actividad distribuida
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                            1 solicitud como máximo por día
                        </span>
                    </template>
                    <!-- Existe un pico -->
                    <template v-else>
                        <div class="text-xl font-black text-gray-900 dark:text-white my-1 tabular-nums">
                            {{ kpisSolicitudes.dia_mayor_demanda.total_solicitudes }}
                            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">
                                solicitudes
                            </span>
                        </div>
                        <!-- 1 fecha -->
                        <template v-if="kpisSolicitudes.dia_mayor_demanda.total_dias_pico === 1">
                            <span class="text-xs text-color1-600 dark:text-color1-400 font-semibold truncate">
                                {{ kpisSolicitudes.dia_mayor_demanda.dias_pico[0].fecha_demanda }}
                            </span>
                        </template>
                        <!-- 2 fechas -->
                        <template v-else-if="kpisSolicitudes.dia_mayor_demanda.total_dias_pico === 2">
                            <span class="text-[11px] text-color1-600 dark:text-color1-400 font-semibold">
                                {{ kpisSolicitudes.dia_mayor_demanda.dias_pico[0].fecha_demanda }}
                                ·
                                {{ kpisSolicitudes.dia_mayor_demanda.dias_pico[1].fecha_demanda }}
                            </span>
                        </template>

                        <!-- Más de 2 fechas -->
                        <template v-else>
                            <button
                                type="button"
                                @click="mostrarModalDiasPico = true"
                                class="inline-flex items-center gap-1.5 w-fit mt-1
                                    px-2.5 py-1
                                    rounded-full
                                    bg-color1-40 dark:bg-color1-950/60
                                    text-color1-700 dark:text-color1-300
                                    text-[11px] font-bold
                                    dark:border-color1-900
                                    hover:bg-color1-50 dark:hover:bg-color1-900
                                    hover:border-color1-200 dark:hover:border-color1-800
                                    active:scale-[0.98]
                                    transition-all duration-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/>
                                </svg>

                                {{ kpisSolicitudes.dia_mayor_demanda.total_dias_pico }} días
                                <span class="opacity-70">·</span>
                                Ver fechas
                            </button>
                        </template>
                    </template>
                </template>
                <template v-else>
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-500 my-1">
                        Sin registros
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                        Sin flujo en este periodo
                    </span>
                </template>
            </div>
        <div class="p-2.5 bg-color1-50 dark:bg-color1-950/50 text-color1-600 dark:text-color1-400 rounded-lg self-center shrink-0 ml-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                />
            </svg>
        </div>
        </div>

        <!-- 4. Gráfica: Demanda por Día de la Semana (Alineada de forma idéntica) -->
        <div class="bg-white dark:bg-gray-800 px-4 pt-4 pb-0 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col justify-between transition-all">
            <div class="flex flex-col h-full justify-between">
                <!-- Nivel 1: Título -->
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-200">
                    Demanda por Día
                </span>
                
                <!-- Nivel 2: Espacio central (Gráfica compacta) -->
                <div class="h-[85px] relative w-full flex items-center justify-center my-1">
                    <div v-if="!kpisSolicitudes?.dia_mayor_demanda || kpisSolicitudes.dia_mayor_demanda.length === 0" class="text-xs text-gray-400 italic">
                        Sin registros
                    </div>
                    <div
                        v-else-if="!esRangoValidoDemandaDia"
                        class="flex flex-col items-center justify-center text-center px-3">
                        <!-- Badge -->
                        <span
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full
                                bg-gray-100 text-gray-600
                                dark:bg-gray-800 dark:text-gray-300
                                text-[10px] font-semibold uppercase tracking-wide
                                border border-gray-200 dark:border-gray-700 mb-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3"
                                viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 00-1.5 0v4.25c0 .414.336.75.75.75h2.25a.75.75 0 000-1.5h-1.5V6.5z"
                                    clip-rule="evenodd"/>
                            </svg>
                            Periodo insuficiente
                        </span>
                        <!-- Ayuda -->
                        <span
                            class="text-[11px] leading-tight text-gray-400
                                dark:text-gray-500 mt-0.5">
                            Selecciona al menos 1 semana para visualizar la demanda por día
                        </span>
                    </div>
                    <Bar v-else :data="chartDataDiasSemana" :options="chartOptionsBar" />
                </div>
            </div>
        </div>
    </div>

    <div class="py-2 text-center space-y-3">
        <!-- SKELETON: EVOLUCIÓN MENSUAL -->
        <section
            v-if="props.cargando"
            class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden animate-pulse">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,3fr)_minmax(190px,1fr)]">
                <!-- COLUMNA IZQUIERDA: ENCABEZADO Y GRÁFICA -->
                <div class="min-w-0">
                    <!-- Encabezado -->
                    <div class="px-5 pt-5 space-y-2">
                        <div class="h-3 w-48 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-2.5 w-64 max-w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                    <!-- Área de gráfica -->
                    <div class="px-5 py-4">
                        <div class="pb-3">
                            <div class="h-2.5 w-40 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                        <div class="h-[280px] sm:h-[320px] w-full relative px-4 pb-8 border-b border-gray-100 dark:border-gray-700">
                            <svg
                                viewBox="0 0 600 240"
                                preserveAspectRatio="none"
                                class="w-full h-full overflow-visible">
                            <!-- Líneas horizontales de referencia -->
                            <line
                                v-for="i in 4"
                                :key="i"
                                :x1="0"
                                :x2="600"
                                :y1="i * 48"
                                :y2="i * 48"
                                stroke="currentColor"
                                class="text-gray-100 dark:text-gray-700"
                                stroke-width="1"
                                stroke-dasharray="4 5"/>

                            <!-- Línea simulada con curvas suaves -->
                                <path
                                    d="M 0,165
                                    C 18,165 37,125 55,125
                                    S 92,145 110,145
                                    S 147,75 165,75
                                    S 202,105 220,105
                                    S 257,45 275,45
                                    S 312,125 330,125
                                    S 367,85 385,85
                                    S 422,155 440,155
                                    S 477,55 495,55
                                    S 532,95 550,95
                                    S 582,30 600,30"
                                    fill="none"
                                    stroke="currentColor"
                                    class="text-gray-200 dark:text-gray-600"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"/>

                                <!-- Puntos de la línea -->
                                <circle
                                    v-for="(point, i) in [
                                        [0,165], [55,125], [110,145], [165,75],
                                        [220,105], [275,45], [330,125], [385,85],
                                        [440,155], [495,55], [550,95], [600,30]
                                    ]"
                                    :key="i"
                                    :cx="point[0]"
                                    :cy="point[1]"
                                    r="3.5"
                                    fill="currentColor"
                                    class="text-gray-300 dark:text-gray-500"
                                />
                            </svg>
                        </div>
                        <!-- Eje inferior -->
                        <div class="flex justify-between gap-2 px-2 mt-3">
                            <div
                                v-for="i in 6"
                                :key="i"
                                class="h-2 w-8 rounded bg-gray-100 dark:bg-gray-700/70"
                            ></div>
                        </div>
                    </div>
                </div>
                <!-- COLUMNA DERECHA: CAPAS -->
                <div
                    class="flex flex-col gap-4 border-l border-gray-200/80 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    <!-- Encabezado de capas -->
                    <div class="flex items-center gap-2">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-gray-200 dark:bg-gray-700"></div>

                        <div class="flex-1 space-y-2">
                            <div class="h-2.5 w-32 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-2 w-40 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                    </div>
                    <!-- Botón restablecer -->
                    <div class="h-9 w-full rounded-full bg-gray-100 dark:bg-gray-800"></div>
                    <!-- Opciones de capas -->
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50/70 px-3 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="h-4 w-4 shrink-0 rounded bg-gray-200 dark:bg-gray-700"></div>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="h-2.5 w-24 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-2 w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section v-else class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="grid grid-cols-1 gap-0 lg:grid-cols-[minmax(0,3fr)_minmax(190px,1fr)]">
                <!-- COLUMNA IZQUIERDA: ENCABEZADO Y GRÁFICA -->
                <div class="min-w-0">
                    <!-- Encabezado -->
                    <div class="px-5 pt-5 text-left">
                        <h3 class="text-xs font-bold uppercase mb-0 tracking-wider text-gray-800 dark:text-gray-200">
                            {{ tituloEstadisticaActual }}
                        </h3>
                        <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            Composición mensual de las solicitudes de apoyo.
                        </p>                  
                    </div>
                    <div
                        v-if="!esRangoValidoEvolucionSolicitudes"
                        class="flex flex-col items-center justify-center px-3 py-8 text-center">
                        <span
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full
                                bg-gray-100 text-gray-600
                                dark:bg-gray-800 dark:text-gray-300
                                text-[10px] font-semibold uppercase tracking-wide
                                border border-gray-200 dark:border-gray-700 mb-2">
                                <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3"
                                viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 00-1.5 0v4.25c0 .414.336.75.75.75h2.25a.75.75 0 000-1.5h-1.5V6.5z"
                                    clip-rule="evenodd"/>
                            </svg>
                            Periodo insuficiente
                        </span>

                        <!-- Texto -->
                        <p class="text-[11px] leading-tight text-gray-400 dark:text-gray-500 max-w-sm mt-1.5">
                            Selecciona un periodo de 3 o más meses para visualizar la evolución de las solicitudes.
                        </p>
                    </div>
                    <div
                        v-else-if="!props.rawDataFromBackend || props.rawDataFromBackend.length === 0 ||
                                    props.rawDataFromBackend.every(row => Number(row.total) === 0)"
                        class="my-4 mx-5 flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-12 dark:border-gray-700 dark:bg-gray-900/30">
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 dark:text-gray-500 mb-3 shadow-xs">
                            <!-- Icono de documento vacío / carpeta -->
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
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-sm mt-1">
                            No se encontraron solicitudes registradas para las fechas seleccionadas. Intenta cambiando el periodo en el filtro superior.
                        </p>
                    </div>
                    <div v-else class="px-5 py-4">
                        <div class="pb-2">
                            <span class="text-[11px] text-gray-400 italic block text-left">
                                {{ subtituloParametros }}
                            </span>
                        </div>
                        <div class="relative h-[280px] w-full min-w-0 sm:h-[320px]">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>
                <!-- Cuando existen datos -->
                <div
                    class="flex flex-col gap-4 border-l border-gray-200/80
                        bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    <template
                    v-if="
                        esRangoValidoEvolucionSolicitudes &&
                        props.rawDataFromBackend?.some(row => Number(row.total) > 0)">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-color1-40 text-color1-600 dark:bg-color1-900/30 dark:text-color1-300">
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
                                <p class="text-[11px] font-bold text-left uppercase tracking-wider text-gray-700 dark:text-gray-200">
                                    Capas de visualización
                                </p>
                                <p class="text-[10px] text-left text-gray-400 dark:text-gray-500">
                                    Personaliza la información de la gráfica
                                </p>                    
                            </div>
                        </div>
                        <div class="flex flex-col gap-2.5">
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
                            <!-- Capa: Estatus -->
                            <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg
                                border border-indigo-200 bg-indigo-50 px-3 py-2
                                transition-colors duration-200
                                hover:bg-indigo-100"
                            :class="{ 'opacity-80': !capaDesgloseEstatus }">
                                <input
                                    type="checkbox"
                                    v-model="capaDesgloseEstatus"
                                    class="h-4 w-4 shrink-0 rounded border-amber-400
                                        text-amber-600 focus:ring-amber-600
                                        dark:border-amber-700">

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-amber-900 dark:text-color1-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-column preview-icon h-3.5 w-3.5"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                                        Estatus
                                    </span>
                                    <span class="mt-1 block text-left text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Desglose de solicitudes según su estatus.
                                    </span>
                                </span>
                            </label>
                            <!-- Capa: Promedio -->
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg
                                    border border-red-200 bg-red-50/60 px-3 py-2
                                    transition hover:bg-red-100
                                    dark:border-color1-900/60 dark:bg-color1-900/15
                                    dark:hover:bg-color1-900/25"
                                :class="{ 'opacity-80': !capaPromedio }">
                                <input
                                    type="checkbox"
                                    v-model="capaPromedio"
                                    class="h-4 w-4 shrink-0 rounded border-red-300
                                        text-red-700 focus:ring-red-600
                                        dark:border-red-700">
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-red-800 dark:text-red-300">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="12"
                                            viewBox="0 0 24 12"
                                            fill="none"
                                            class="h-3.5 w-3.5">
                                            <line
                                                x1="1"
                                                y1="6"
                                                x2="23"
                                                y2="6"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-dasharray="1 4"
                                            />
                                        </svg>
                                        Media
                                    </span>
                                    <span class="mt-1 block text-left text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Línea del promedio de solicitudes.
                                    </span>
                                </span>
                            </label>
                            <!-- Capa: Tabla -->
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg
                                    border border-emerald-200 bg-emerald-50/60 px-3 py-2
                                    transition hover:bg-emerald-100/70
                                    dark:border-color1-900/60 dark:bg-color1-900/15
                                    dark:hover:bg-color1-900/25"
                                :class="{ 'opacity-80': !capaTablaDesglose }">
                                <input
                                    type="checkbox"
                                    v-model="capaTablaDesglose"
                                    class="h-4 w-4 shrink-0 rounded border-emerald-300
                                        text-emerald-700 focus:ring-emerald-600
                                        dark:border-color1-700">

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-emerald-800 dark:text-color1-300">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 shrink-0 text-emerald-700"
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
                                    <span class="mt-1 block text-left text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Información detallada por periodo.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </template>
                    <div v-else class="flex flex-col gap-4 h-full">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center
                                        rounded-lg bg-gray-100 text-gray-500
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
            </div>
        </section>
        <section v-if="capaTablaDesglose" class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="pt-8 pb-1 px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <!-- Distintivo Visual del Título -->
                    <div class="flex items-center gap-3">
                        <!-- Icono con contenedor cuadrado moderno -->
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 shrink-0">
                            <!-- SVG Ícono de Hoja de Cálculo / Tabla -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 shrink-0 text-emerald-700"
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
                        </div>

                        <!-- Títulos y Metadatos alineados limpiamente a la izquierda -->
                        <div class="flex flex-col text-left">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">
                                    Desglose Mensual Detallado
                                </h3>
                                <span v-if=" props.rawDataFromBackend?.some(row => Number(row.total) > 0) && esRangoValidoTabla" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50">
                                    {{ props.rawDataFromBackend.length }} meses
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 text-left mt-0.5">
                                Resumen consolidado por estatus de solicitud
                            </p>
                        </div>
                    </div>

                    <!-- Botón Exportar (Solo se muestra si hay datos para exportar) -->
                    <button v-if="props.rawDataFromBackend &&  props.rawDataFromBackend?.some(row => Number(row.total) > 0) && esRangoValidoTabla"
                        @click="exportarReporte" 
                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold text-emerald-50 bg-emerald-700 hover:bg-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition-all shadow-2xs">
                        <!-- SVG Microsoft Excel -->
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14.2 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.8L14.2 2zM13 3.5L18.5 9H13V3.5zM8.8 17.5l1.7-3.1 1.7 3.1h1.8l-2.6-4.5 2.5-4.3h-1.8l-1.6 2.9-1.6-2.9H8.1l2.5 4.3-2.6 4.5h1.8z" />
                        </svg>
                        <span>Exportar a Excel (CSV)</span>
                    </button>
                </div>

                <div v-if="!esRangoValidoTabla" class="flex flex-col items-center justify-center text-center px-3">
                    <span
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full
                            bg-gray-100 text-gray-600
                            dark:bg-gray-800 dark:text-gray-300
                            text-[10px] font-semibold uppercase tracking-wide
                            border border-gray-200 dark:border-gray-700 mb-2">
                            <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-3 h-3"
                            viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 00-1.5 0v4.25c0 .414.336.75.75.75h2.25a.75.75 0 000-1.5h-1.5V6.5z"
                                clip-rule="evenodd"/>
                        </svg>
                        Periodo insuficiente
                    </span>
                    <p class="text-[11px] leading-tight text-gray-400 dark:text-gray-500 max-w-sm mt-1.5">
                        Selecciona un periodo de 1 o más meses para ver la tabla de las solicitudes.
                    </p>
                </div>
                <!-- 📭 ESTADO VACÍO DE LA TABLA (Se muestra si el arreglo está vacío) -->
                <div v-else-if=" !props.rawDataFromBackend ||
                    props.rawDataFromBackend.length === 0 ||
                    props.rawDataFromBackend.every(row => Number(row.total) === 0)" class="py-10 px-4 flex flex-col items-center justify-center bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
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

                <!-- Tabla de Desglose Mensual (Solo si hay datos) -->
                <div v-else class="overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300 min-w-[550px]">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-[11px] font-semibold uppercase text-gray-500 dark:text-gray-400">
                        <tr>
                        <th class="py-2.5 px-3">Mes</th>
                        <th class="py-2.5 px-3 text-right">Totales</th>
                        <th class="py-2.5 px-3 text-right">En Trámite</th>
                        <th class="py-2.5 px-3 text-right">Aprobadas</th>
                        <th class="py-2.5 px-3 text-right">Apoyos Entregados</th>
                        <th class="py-2.5 px-3 text-right">Canceladas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <tr v-for="(row, idx) in props.rawDataFromBackend" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="py-2 px-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            {{ row.mes ? row.mes.charAt(0).toUpperCase() + row.mes.slice(1) : '' }}
                        </td>
                        <td class="py-2 px-3 text-right">
                            <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-lg text-xs font-bold bg-color1-50/50 text-color1-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ row.total }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                            {{ row.enTramite }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                            {{ row.aprobadas }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                            {{ row.apoyoEntregado }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300">
                            {{ row.canceladas }}
                            </span>
                        </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50/90 dark:bg-gray-800/80 font-bold text-gray-900 dark:text-gray-100 border-t-2 border-gray-100 dark:border-gray-700">
                        <tr>
                            <td class="py-3 px-3 uppercase tracking-wider text-left">Total General</td>
                            <td class="whitespace-nowrap py-3 px-3 text-right">
                                <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-xl text-base font-bold bg-color1-50/50 text-color1-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ props.rawDataFromBackend.reduce((acc, row) => acc + (Number(row.total) || 0), 0) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap py-3 px-3 text-right text-amber-700 dark:text-amber-300">
                                <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-xl text-base font-bold bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                {{ props.rawDataFromBackend.reduce((acc, row) => acc + (Number(row.enTramite) || 0), 0) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap py-3 px-3 text-right text-indigo-700 dark:text-indigo-300">
                                <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-xl text-base font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">
                                {{ props.rawDataFromBackend.reduce((acc, row) => acc + (Number(row.aprobadas) || 0), 0) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap py-3 px-3 text-right text-emerald-700 dark:text-emerald-300">
                                <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-xl text-base font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">
                                {{ props.rawDataFromBackend.reduce((acc, row) => acc + (Number(row.apoyoEntregado) || 0), 0) }}
                                </span>
                                    
                            </td>
                            <td class="whitespace-nowrap py-3 px-3 text-right text-red-700 dark:text-red-300">
                                <span class="inline-flex min-w-10 justify-center px-2.5 py-1 rounded-xl text-base font-bold bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200">
                                {{ props.rawDataFromBackend.reduce((acc, row) => acc + (Number(row.canceladas) || 0), 0) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
