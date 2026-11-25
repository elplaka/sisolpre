<template>
    <div class="py-4 space-y-6 max-w-7xl mx-auto">
        
        <!-- 1. Tarjetas KPI Ejecutivas -->
        <div
            v-if="cargando"
            class="grid grid-cols-1 sm:grid-cols-3 gap-4 animate-pulse">
            <div
                v-for="i in 3"
                :key="i"
                class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between">
                <!-- Información -->
                <div class="min-w-0 flex-1 pr-3">
                    <!-- Título -->
                    <div
                        class="h-3 rounded bg-gray-200 dark:bg-gray-700 mb-3"
                        :class="i === 2 ? 'w-40' : 'w-36'"
                    ></div>
                    <!-- Valor principal -->
                    <div class="h-8 w-24 rounded bg-gray-200 dark:bg-gray-700 mt-1 mb-3"></div>
                    <!-- Descripción -->
                    <div class="space-y-1.5">
                        <div class="h-2.5 w-full max-w-[190px] rounded bg-gray-100 dark:bg-gray-700/70"></div>
                        <div
                            v-if="i === 2 || i === 3"
                            class="h-2.5 w-3/4 max-w-[150px] rounded bg-gray-100 dark:bg-gray-700/70"
                        ></div>
                    </div>
                </div>
                <!-- Icono -->
                <div class="p-2.5 rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0">
                    <div class="w-5 h-5 rounded-md bg-gray-200 dark:bg-gray-600"></div>
                </div>
            </div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- KPI: Total Personas Atendidas -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                        Ciudadanos Atendidos
                    </span>
                    <div class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                        {{ kpisAtencionCiudadana?.total_personas ?? 0 }}
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ (kpisAtencionCiudadana?.total_personas ?? 0) > 0 ? 'Personas únicas registradas' : 'Sin actividad en el periodo' }}
                    </span>
                </div>
                <div class="p-2.5 bg-color1-50 dark:bg-color1-950/50 text-color1-600 dark:text-color1-400 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-200">
                        Promedio de Solicitudes por Ciudadano
                    </span>
                    <div class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                        {{ kpisAtencionCiudadana?.promedio_solicitudes_persona ?? '0.00' }}
                    </div>
                    <!-- UX Mejorada: Explica el comportamiento o muestra la base de cálculo -->
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ (kpisAtencionCiudadana?.promedio_solicitudes_persona ?? 0) > 0 
                            ? 'Frecuencia de solicitudes por persona atendida' 
                            : 'Sin registros válidos en este periodo' }}
                    </span>
                </div>
                
                <!-- Icono de grupo / ciudadanos -->
                <div class="p-2.5 bg-color1-50 dark:bg-color1-950/50 text-color1-600 dark:text-color1-400 rounded-lg self-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-user preview-icon w-5 h-5"><path d="M10.254 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2V9.255"/><path d="M22 22a4 4 0 0 0-8 0"/><circle cx="18" cy="15" r="3"/></svg>
                </div>
            </div>
            <div
                class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex items-center justify-between transition-all">
                <div>
                    <span
                        class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-200">
                        Ciudadanos Recurrentes
                    </span>

                    <div class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                        {{
                            Number(kpisAtencionCiudadana?.total_ciudadanos_recurrentes ?? 0)
                                .toLocaleString('es-MX')
                        }}
                    </div>

                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{
                            Number(kpisAtencionCiudadana?.total_ciudadanos_recurrentes ?? 0) > 0
                                ? `${Number(kpisAtencionCiudadana?.porcentaje_ciudadanos_recurrentes ?? 0).toLocaleString('es-MX', { maximumFractionDigits: 1 })}% del total de ciudadanos atendidos`
                                : 'Sin ciudadanos recurrentes en este periodo'
                        }}
                    </span>
                </div>
                <div
                    class="p-2.5 bg-color1-50 dark:bg-color1-950/50 text-color1-600 dark:text-color1-400 rounded-lg self-center shrink-0 ml-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-star preview-icon w-5 h-5"><path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.867l-1.156-1.152a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M2 21a8 8 0 0 1 6.778-7.905"/><path d="M8.927 12.885a5 5 0 1 1 6.061-4.535"/></svg>
                </div>
            </div>
        </div>
        
        <!-- PERIODO DE 1 O 2 MESES: CHART Y TABLAS EN EL MISMO ROW -->
        <template v-if="mostrarTablasJuntoAlChart">
            <div
                class="grid grid-cols-1 lg:grid-cols-[1fr_3fr] gap-4 mt-4">
                <!-- CHART -->
                <section v-if="cargando" class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                    <!-- Encabezado -->
                    <div class="px-4 py-4 sm:px-5 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col gap-3">

                            <div class="space-y-2">
                                <div class="h-3 w-52 rounded bg-gray-200 dark:bg-gray-700"></div>
                                <div class="h-2.5 w-full rounded bg-gray-100 dark:bg-gray-700/70"></div>
                                <div class="h-2.5 w-4/5 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                            </div>

                            <!-- Indicador de solicitudes -->
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 dark:bg-gray-800/70 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-2.5 w-36 rounded bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                                <div class="h-6 w-12 rounded bg-gray-200 dark:bg-gray-700"></div>
                            </div>

                        </div>
                    </div>

                    <!-- Gráfica simulada -->
                    <div class="p-4 sm:p-5">
                        <div class="h-[280px] sm:h-[320px] w-full relative px-2 pb-6">
                            <svg
                                viewBox="0 0 600 240"
                                preserveAspectRatio="none"
                                class="w-full h-full overflow-visible">

                                <!-- Cuadrícula -->
                                <line
                                    v-for="i in 5"
                                    :key="i"
                                    x1="0"
                                    x2="600"
                                    :y1="i * 40"
                                    :y2="i * 40"
                                    stroke="currentColor"
                                    class="text-gray-100 dark:text-gray-700"
                                    stroke-width="1"
                                    stroke-dasharray="4 5"
                                />

                                <!-- Barras -->
                                <g fill="currentColor" class="text-gray-300 dark:text-gray-600">
                                    <rect x="55" y="105" width="75" height="95" rx="9" />
                                    <rect x="195" y="55" width="75" height="145" rx="9" />
                                    <rect x="335" y="85" width="75" height="115" rx="9" />
                                    <rect x="475" y="30" width="75" height="170" rx="9" />
                                </g>

                                <!-- Base -->
                                <line
                                    x1="0"
                                    y1="200"
                                    x2="600"
                                    y2="200"
                                    stroke="currentColor"
                                    class="text-gray-200 dark:text-gray-700"
                                    stroke-width="1.5"
                                />
                            </svg>
                        </div>
                    </div>
                </section>
                <section v-else class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-4 py-4 sm:px-5 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col gap-3">
                            <!-- Título y descripción -->
                            <div class="min-w-0">
                                <h3 class="text-xs font-semibold leading-5 text-gray-800 dark:text-white uppercase">
                                    Evolución de la Atención Ciudadana
                                </h3>

                                <p class="mt-1.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                    Composición mensual de los ciudadanos atendidos por primera vez y de aquellos con atenciones recurrentes.
                                </p>
                            </div>                        
                            <!-- Indicador de solicitudes -->
                            <div class="flex items-center justify-between gap-3 rounded-lg
                                        bg-gray-50 px-3 py-2
                                        dark:bg-gray-800/70">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center
                                                rounded-lg bg-color1-50 text-color1-700
                                                dark:bg-color1-900/40 dark:text-color1-300">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-3 w-3"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        TOTAL DE SOLICITUDES
                                    </span>
                                </div>
                                <span class="shrink-0 text-xl font-bold leading-none tabular-nums text-gray-900 dark:text-white">
                                    {{ kpisAtencionCiudadana.total_solicitudes }}
                                </span>
                            </div>
                        </div>
                    </div>
            
                    <div class="p-4 sm:p-5">
                        <div
                            v-if="Number(kpisAtencionCiudadana.total_solicitudes) === 0"
                            class="h-[280px] sm:h-[320px] flex flex-col items-center justify-center text-center px-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 mb-3"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                            </svg>

                            <p class="text-[12px] font-semibold text-gray-700 dark:text-gray-200">
                                Sin solicitudes registradas
                            </p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                No hay información disponible para generar la gráfica en el periodo seleccionado.
                            </p>
                        </div>
                        <!-- Gráfica -->
                        <div v-else class="h-[280px] sm:h-[320px]">
                            <Bar
                                :data="chartAtencionCiudadanaData"
                                :options="chartAtencionCiudadanaOptions"/>
                        </div>
                    </div>
                </section>

                <!-- SKELETON: TABLAS DE ATENCIÓN CIUDADANA -->
                <div v-if="props.cargando" class="grid grid-cols-1 lg:grid-cols-2 gap-4 animate-pulse">

                    <!-- SKELETON: CIUDADANOS RECURRENTES -->
                    <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">

                        <!-- Encabezado -->
                        <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="h-3 w-40 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                                    <div class="mt-2 h-2.5 w-64 max-w-full bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                                </div>

                                <div class="shrink-0 flex flex-col items-end">
                                    <div class="h-5 w-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    <div class="mt-2 h-2 w-12 bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Filas -->
                        <div class="p-3 flex-1">
                            <div class="rounded-lg border border-gray-100 dark:border-gray-700/70 overflow-hidden">

                                <!-- Encabezado de tabla -->
                                <div class="flex items-center justify-between px-3 py-3 bg-gray-50 dark:bg-gray-700">
                                    <div class="h-2 w-32 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                </div>

                                <!-- Registros simulados -->
                                <div v-for="i in 5" :key="i"
                                    class="flex items-center justify-between gap-3 px-3 py-3 border-t border-gray-100 dark:border-gray-700/70">

                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="w-6 h-6 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full"
                                            :class="i % 2 === 0 ? 'w-36' : 'w-48'">
                                        </div>
                                    </div>

                                    <div class="w-10 h-6 shrink-0 rounded-md bg-gray-100 dark:bg-gray-700"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- SKELETON: DISTRIBUCIÓN DE SOLICITUDES -->
                    <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">

                        <!-- Encabezado -->
                        <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                            <div class="h-3 w-44 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            <div class="mt-2 h-2.5 w-64 max-w-full bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                        </div>

                        <!-- Filas -->
                        <div class="p-3 flex-1">
                            <div class="rounded-lg border border-gray-100 dark:border-gray-700/70 overflow-hidden">

                                <!-- Encabezado de tabla -->
                                <div class="flex items-center justify-between px-3 py-3 bg-gray-50 dark:bg-gray-700">
                                    <div class="h-2 w-36 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                    <div class="h-2 w-16 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                </div>

                                <!-- Registros simulados -->
                                <div v-for="i in 5" :key="i"
                                    class="flex items-center justify-between gap-3 px-3 py-3 border-t border-gray-100 dark:border-gray-700/70">

                                    <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full"
                                        :class="i % 2 === 0 ? 'w-28' : 'w-36'">
                                    </div>

                                    <div class="w-11 h-6 shrink-0 rounded-md bg-gray-100 dark:bg-gray-700"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <!-- TABLAS -->
                <div v-else class="grid grid-cols-1 gap-4 min-w-0">
                    <!-- 2. Grid de Gráficas y Tablas -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <!-- TABLA 1: CIUDADANOS RECURRENTES -->
                        <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                            <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">Ciudadanos recurrentes</h3>
                                        <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">Personas que registraron más de una solicitud durante el periodo.</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-lg font-bold leading-5 text-gray-900 dark:text-white tabular-nums">{{ rawDataFromBackend?.length ?? 0 }}</div>
                                        <div class="mt-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ (rawDataFromBackend?.length ?? 0) === 1 ? 'Persona' : 'Personas' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 flex-1 min-h-0">
                                <div v-if="rawDataFromBackend?.length" class="overflow-auto max-h-[320px] rounded-lg border border-gray-100 dark:border-gray-700/70">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700">
                                            <tr class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                                <th scope="col" class="px-3 py-2.5 font-bold">Persona solicitante</th>
                                                <th scope="col" class="px-3 py-2.5 text-right font-bold whitespace-nowrap">Solicitudes</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                            <tr v-for="(item, index) in rawDataFromBackend" :key="item.id_solicitante" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                <td class="px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 text-[10px] font-bold shrink-0 tabular-nums">{{ index + 1 }}</span>
                                                        <span class="min-w-0 break-words">{{ item.nombre_completo }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-md bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300 text-xs font-bold tabular-nums">{{ item.total_solicitudes }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="min-h-[180px] rounded-lg border border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center px-4 py-6 text-center">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Sin registros de recurrencia</p>
                                    <p class="mt-1 max-w-xs text-xs leading-4 text-gray-500 dark:text-gray-400">No hay personas con más de una solicitud en el periodo.</p>
                                </div>
                            </div>
                        </section>

                        <!-- TABLA 2: DISTRIBUCIÓN DE SOLICITUDES -->
                        <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                            <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">Distribución de solicitudes</h3>
                                <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">Cantidad de personas según el número de solicitudes del periodo.</p>
                            </div>
                            <div class="p-3 flex-1 min-h-0">
                                <div v-if="kpisAtencionCiudadana.distribucion_solicitudes_ciudadano?.length" class="overflow-auto max-h-[320px] rounded-lg border border-gray-100 dark:border-gray-700/70">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700">
                                            <tr class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                                <th scope="col" class="px-3 py-2.5 font-bold">Solicitudes por persona</th>
                                                <th scope="col" class="px-3 py-2.5 text-right font-bold">Ciudadanos</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                            <tr v-for="item in kpisAtencionCiudadana.distribucion_solicitudes_ciudadano" :key="item.total_solicitudes" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                <td class="px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                                                    {{ item.total_solicitudes }} {{ Number(item.total_solicitudes) === 1 ? 'solicitud' : 'solicitudes' }}
                                                </td>
                                                <td class="px-3 py-2.5 text-right">
                                                    <span class="inline-flex items-center justify-center min-w-9 px-2.5 py-1 rounded-md bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300 text-xs font-bold tabular-nums">{{ Number(item.total_ciudadanos).toLocaleString('es-MX') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="min-h-[180px] rounded-lg border border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center px-4 py-6 text-center">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Sin solicitudes registradas</p>
                                    <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">No hay información para el periodo seleccionado.</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </template>

        <!-- PERIODO DE 3 MESES O MÁS: CHART EN UNA FILA Y TABLAS EN OTRA -->
        <template v-else-if="!mostrarTablasJuntoAlChart">
            <!-- CHART A TODO EL ANCHO -->
            <section class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <!-- SKELETON: Evolución de la Atención Ciudadana -->
                <div v-if="cargando" class="grid grid-cols-1 gap-0 lg:grid-cols-[3fr_1fr] animate-pulse">
                    <!-- Columna Izquierda: Título, KPI, Subtítulo y Gráfica -->
                    <div class="px-5 border-b border-gray-100 dark:border-gray-700 pb-5">
                        <!-- Encabezado y KPI -->
                        <div class="px-1 pt-4 pb-0 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1 space-y-2">
                                <!-- Título -->
                                <div class="h-3.5 w-52 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                <!-- Descripción -->
                                <div class="space-y-1.5 pt-0.5">
                                    <div class="h-3 w-full max-w-md rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-3 w-3/4 max-w-xs rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </div>

                            <!-- Contador Total -->
                            <div class="shrink-0 flex flex-col sm:items-end gap-1.5">
                                <div class="h-6 w-14 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                                <div class="h-2.5 w-16 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                        </div>

                        <!-- Subtítulo de parámetros -->
                        <div class="pt-3 mb-3">
                            <div class="h-3 w-64 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        </div>

                        <!-- Área de la gráfica -->
                        <div class="h-[280px] sm:h-[340px] lg:h-[360px] w-full rounded-2xl bg-gray-100 dark:bg-gray-800/80 border border-gray-200/60 dark:border-gray-700/60 p-4 flex flex-col justify-end gap-3">
                            <!-- Simulación sutil de barras de gráfica -->
                            <div class="flex items-end justify-around h-4/5 gap-3 px-4">
                                <div class="w-full max-w-[32px] h-2/5 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                <div class="w-full max-w-[32px] h-3/4 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                <div class="w-full max-w-[32px] h-1/2 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                <div class="w-full max-w-[32px] h-5/6 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                <div class="w-full max-w-[32px] h-3/5 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                <div class="w-full max-w-[32px] h-2/3 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <!-- Eje X simulado -->
                            <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Panel de Capas de Visualización -->
                    <div class="bg-white dark:bg-gray-800/40 py-4 px-5 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-700">
                        <!-- Encabezado del panel -->
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="h-3 w-32 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-2.5 w-40 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </div>

                            <!-- Botón Restablecer capas -->
                            <div class="h-8 w-full rounded-full bg-gray-200 dark:bg-gray-700 mb-2"></div>
                        </div>

                        <!-- Lista de 5 capas simuladas -->
                        <div class="mt-4 grid grid-cols-1 gap-2">
                            <div v-for="n in 5" :key="n"
                                class="flex items-center gap-3 rounded-lg border border-gray-200/80 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-800/60 px-3 py-2.5">
                                <!-- Checkbox -->
                                <div class="h-4 w-4 shrink-0 rounded bg-gray-200 dark:bg-gray-700"></div>
                                <!-- Textos de la capa -->
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-3.5 shrink-0 rounded-sm bg-gray-300 dark:bg-gray-600"></div>
                                        <div class="h-3 w-28 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    </div>
                                    <div class="h-2.5 w-4/5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="grid grid-cols-1 gap-0 lg:grid-cols-[3fr_1fr]">
                    <div class="px-5 border-b border-gray-100 dark:border-gray-700">
                        <div class="px-1 pt-4 pb-0 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase">
                                    Evolución de la Atención Ciudadana
                                </h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Composición mensual de los ciudadanos atendidos por primera vez y de aquellos con atenciones recurrentes.
                                </p>
                            </div>

                            <div class="shrink-0 text-left sm:text-right">
                                <div class="text-2xl font-bold leading-none text-gray-900 dark:text-white">
                                    {{ kpisAtencionCiudadana.total_solicitudes }}
                                </div>
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Solicitudes
                                </div>
                            </div>
                        </div>

                        <div v-if="kpisAtencionCiudadana.total_solicitudes != 0" class="pt-3 mb-3">
                            <span class="text-[11px] text-gray-400 italic block">
                                {{ subtituloParametros }}
                            </span>
                        </div>
                        <div v-if="kpisAtencionCiudadana.total_solicitudes === 0"
                            class="my-4 mx-0 flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-12 dark:border-gray-700 dark:bg-gray-900/30">
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
                            <p class="text-xs text-center text-gray-400 dark:text-gray-500 max-w-sm mt-1">
                                No se encontraron solicitudes registradas para las fechas seleccionadas. Intenta cambiando el periodo en el filtro superior.
                            </p>
                        </div>
                        <div v-else class="h-[280px] sm:h-[340px] lg:h-[360px]">
                            <Bar
                                :data="chartAtencionCiudadanaData"
                                :options="chartAtencionCiudadanaOptions"/>
                        </div>
                    </div>
                    <div v-if="kpisAtencionCiudadana.total_solicitudes != 0" class="bg-white py-4 px-5 border-l
                        border-gray-200">
                        <!-- Encabezado -->
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

                            <!-- Botón debajo del encabezado -->
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

                        <!-- Capas -->
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-1">
                            <!-- Ciudadanos de primera vez -->
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg
                                        border border-color1-100 bg-color1-50/60 px-3 py-2
                                        transition hover:bg-color1-50/90
                                        dark:border-color1-900/60 dark:bg-color1-900/15
                                        dark:hover:bg-color1-900/25"
                                        :class="{ 'opacity-80': !capasActivas.ciudadanosNuevos }">
                                <input
                                    type="checkbox"
                                    :checked="capasActivas.ciudadanosNuevos"
                                    :disabled="
                                        capasActivas.ciudadanosNuevos &&
                                        capasGrafica.filter(nombre => capasActivas[nombre]).length === 1"
                                    @change="cambiarCapa('ciudadanosNuevos')"
                                    class="h-4 w-4 rounded border-color1-300 text-color1-700 focus:ring-color1-600 disabled:cursor-not-allowed"/>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-color1-900 dark:text-color1-300">
                                        <span class="h-2 w-3.5 shrink-0 rounded-sm
                                                    bg-color1-700"></span>
                                        Ciudadanos 1.ª vez
                                    </span>
                                    <span class="mt-1 block text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Personas atendidas por primera vez.
                                    </span>
                                </span>
                            </label>

                            <!-- Ciudadanos recurrentes -->
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg
                                        border border-color4-200/60 bg-color4-50/60 px-3 py-2
                                        transition hover:bg-color4-100/40
                                        dark:border-amber-900/60 dark:bg-amber-900/15
                                        dark:hover:bg-amber-900/25"
                                        :class="{ 'opacity-80': !capasActivas.ciudadanosRecurrentes }">
                                <input
                                    type="checkbox"
                                    :checked="capasActivas.ciudadanosRecurrentes"
                                    :disabled="
                                        capasActivas.ciudadanosRecurrentes &&
                                        capasGrafica.filter(nombre => capasActivas[nombre]).length === 1"
                                    @change="cambiarCapa('ciudadanosRecurrentes')"
                                    class="h-4 w-4 rounded border-color4-400 text-color4-500 focus:ring-color4-500 disabled:cursor-not-allowed"/>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-color4-700 dark:text-amber-300">
                                        <span class="h-2 w-3.5 shrink-0 rounded-sm
                                                    bg-color4-500"></span>
                                        Ciudadanos recurrentes
                                    </span>
                                    <span class="mt-1 block text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Personas que vuelven a recibir atención.
                                    </span>
                                </span>
                            </label>

                            <!-- Total de ciudadanos -->
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg
                                        border border-color1-900/20 bg-color1-900/5 px-3 py-2
                                        transition hover:bg-color1-900/10
                                        dark:border-gray-700 dark:bg-gray-800/40
                                        dark:hover:bg-gray-800/70"
                                        :class="{ 'opacity-80': !capasActivas.totalSolicitudes }">
                                <input
                                    type="checkbox"
                                    :checked="capasActivas.totalSolicitudes"
                                    :disabled="
                                    capasActivas.totalSolicitudes &&
                                    capasGrafica.filter(nombre => capasActivas[nombre]).length === 1"
                                    @change="cambiarCapa('totalSolicitudes')"
                                    class="h-4 w-4 rounded border-color1-900 text-color1-900 focus:ring-color1-900 disabled:cursor-not-allowed"/>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-color1-900 dark:text-indigo-200">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 shrink-0 text-color1-900"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 17l6-6 4 4 8-9"/>
                                        </svg>
                                        Total de solicitudes
                                    </span>
                                    <span class="mt-1 block text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Línea de referencia del total mensual.
                                    </span>
                                </span>
                            </label>

                            <!-- Porcentaje de recurrencia -->
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg
                                        border border-color2-200 bg-color2-50/70 px-3 py-2
                                        transition hover:bg-color2-100/50
                                        dark:border-gray-700 dark:bg-gray-800/40
                                        dark:hover:bg-gray-800/70"
                                        :class="{ 'opacity-80': !capasActivas.tasaRecurrencia }">
                                <input
                                    type="checkbox"
                                    :checked="capasActivas.tasaRecurrencia"
                                    :disabled="
                                    capasActivas.tasaRecurrencia &&
                                    capasGrafica.filter(nombre => capasActivas[nombre]).length === 1"
                                    @change="cambiarCapa('tasaRecurrencia')"
                                    class="h-4 w-4 rounded border-color2-400 text-color2-600 focus:ring-color2-500 disabled:cursor-not-allowed"/>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-xs font-semibold
                                                text-color2-700 dark:text-emerald-200">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 shrink-0 text-color2-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 17l6-6 4 4 8-9"/>
                                        </svg>
                                        Tasa de recurrencia
                                    </span>
                                    <span class="mt-1 block text-[10px] leading-relaxed
                                                text-gray-500 dark:text-gray-400">
                                        Línea porcentual en un eje secundario.
                                    </span>
                                </span>
                            </label>
                            
                            <!-- Capa 5: Tabla de desglose -->
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg
                                    border border-gray-300 bg-gray-100 px-3 py-2
                                    transition hover:bg-gray-200/80
                                    dark:border-gray-700 dark:bg-gray-800/40
                                    dark:hover:bg-gray-800/70"
                                :class="{ 'opacity-80': !capasActivas.tablaDesglose }">
                                <input
                                    type="checkbox"
                                    :checked="capasActivas.tablaDesglose"
                                    :disabled="
                                        capasActivas.tablaDesglose &&
                                        cantidadCapasActivas === 1
                                    "
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

            <!-- Tabla de detalle mensual de Atención Ciudadana -->
            <div v-if="capasActivas.tablaDesglose"  class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
                        dark:border-gray-700 dark:bg-gray-900">
                <!-- Encabezado -->
                <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-4
                            sm:flex-row sm:items-center sm:justify-between
                            dark:border-gray-700">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center
                                    rounded-lg bg-gray-100 text-gray-600
                                    dark:bg-color1-900/30 dark:text-color1-300">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path stroke-linecap="round" d="M3 9h18M9 9v11m6-11v11"/>
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h4 class="text-xs font-bold uppercase tracking-wide
                                    text-gray-800 dark:text-gray-100">
                                Detalle mensual de atención ciudadana
                            </h4>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Desglose de ciudadanos por periodo
                            </p>
                        </div>
                         <span class="inline-flex w-fit items-center gap-1.5 rounded-full
                                    bg-gray-100 px-2.5 py-1 text-[10px] font-semibold
                                    text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-color1-700"></span>
                            {{ evolucionAtencionCiudadana.length }}
                            {{ evolucionAtencionCiudadana.length === 1 ? 'mes' : 'meses' }}
                        </span>
                    </div>

                    <button
                        @click="exportarEvolucionAtencionCiudadana" 
                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-full text-xs font-semibold text-emerald-50 bg-emerald-700 hover:bg-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition-all shadow-2xs">
                        <!-- SVG Microsoft Excel -->
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14.2 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.8L14.2 2zM13 3.5L18.5 9H13V3.5zM8.8 17.5l1.7-3.1 1.7 3.1h1.8l-2.6-4.5 2.5-4.3h-1.8l-1.6 2.9-1.6-2.9H8.1l2.5 4.3-2.6 4.5h1.8z" />
                        </svg>
                        <span>Exportar a Excel (CSV)</span>
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/70">
                            <tr>
                                <th class="px-4 py-3 font-semibold uppercase tracking-wide
                                        text-[11px] text-gray-500 dark:text-gray-400">
                                    Mes
                                </th>

                                <th class="px-4 py-3 text-right font-semibold uppercase
                                        tracking-wide text-[11px] text-gray-500
                                        dark:text-gray-400">
                                    <span class="inline-flex items-center justify-end gap-1.5">
                                        <span class="h-2 w-2 rounded-sm bg-color1-700"></span>
                                        Ciudadanos 1.ª vez
                                    </span>
                                </th>

                                <th class="px-4 py-3 text-right font-semibold uppercase
                                        tracking-wide text-[11px] text-gray-500
                                        dark:text-gray-400">
                                    <span class="inline-flex items-center justify-end gap-1.5">
                                        <span class="h-2 w-2 rounded-sm bg-amber-600"></span>
                                        Ciudadanos recurrentes
                                    </span>
                                </th>

                                <th class="px-4 py-3 text-right font-semibold uppercase
                                        tracking-wide text-[11px] text-gray-500
                                        dark:text-gray-400">
                                    Total de Solicitudes
                                </th>
                                <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide
                                        text-[11px] text-gray-500 dark:text-gray-400">
                                    Recurrencia
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="item in evolucionAtencionCiudadana"
                                :key="item.periodo"
                                class="transition-colors hover:bg-gray-50/80
                                    dark:hover:bg-gray-800/40">
                                <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-700 dark:text-gray-200">
                                    {{ 
                                        item.periodo
                                            ? new Date(`${item.periodo}-01T12:00:00`).toLocaleDateString('es-MX', {
                                                month: 'long',
                                                year: 'numeric',
                                                timeZone: 'UTC'
                                            }).replace(/^\p{L}/u, letra => letra.toLocaleUpperCase('es-MX'))
                                            : item.mes
                                    }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-md
                                                bg-color1-40 px-2.5 py-1 font-bold tabular-nums
                                                text-color1-800 dark:bg-gray-800 dark:text-gray-100">
                                        {{ Number(item.ciudadanos_nuevos ?? 0).toLocaleString('es-MX') }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-md
                                                bg-amber-50 px-2.5 py-1 font-bold tabular-nums
                                                text-amber-800 dark:bg-gray-800 dark:text-gray-100">
                                        {{ Number(item.solicitudes_recurrentes ?? 0).toLocaleString('es-MX') }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-2 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-md
                                                bg-indigo-50 px-2.5 py-1 font-bold tabular-nums
                                                text-indigo-800 dark:bg-gray-800 dark:text-gray-100">
                                        {{
                                            (
                                                Number(item.ciudadanos_nuevos ?? 0) +
                                                Number(item.solicitudes_recurrentes ?? 0)
                                            ).toLocaleString('es-MX')
                                        }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-right">
                                    <span
                                        class="inline-flex min-w-[58px] justify-center rounded-md
                                            bg-emerald-50 px-2.5 py-1 font-semibold tabular-nums
                                            text-emerald-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{
                                            (() => {
                                                const primeras = Number(item.ciudadanos_nuevos ?? 0);
                                                const recurrentes = Number(item.solicitudes_recurrentes ?? 0);
                                                const total = primeras + recurrentes;

                                                return total > 0
                                                    ? `${((recurrentes / total) * 100).toLocaleString('es-MX', {
                                                        maximumFractionDigits: 1
                                                    })}%`
                                                    : '—';
                                            })()
                                        }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Sin información -->
                            <tr v-if="!evolucionAtencionCiudadana.length">
                                <td colspan="4" class="px-4 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-8 w-8 text-gray-300 dark:text-gray-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <path stroke-linecap="round" d="M3 9h18M8 13h3m-3 3h8"/>
                                        </svg>

                                        <p class="text-xs font-medium text-gray-500
                                                dark:text-gray-400">
                                            No hay información para el periodo seleccionado.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Totales -->
                        <tfoot v-if="evolucionAtencionCiudadana.length"
                            class="border-t-2 border-gray-200 bg-gray-50
                                    dark:border-gray-700 dark:bg-gray-800/80">
                            <tr>
                                <td class="px-4 py-3 font-bold uppercase tracking-wide
                                        text-gray-700 dark:text-gray-200">
                                    Total General
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-lg
                                                bg-color1-50 px-2.5 py-1 font-bold text-base
                                                tabular-nums text-color1-700
                                                dark:text-amber-400">
                                        {{
                                            evolucionAtencionCiudadana
                                                .reduce((total, item) =>
                                                    total + Number(item.ciudadanos_nuevos ?? 0), 0)
                                                .toLocaleString('es-MX')
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-lg
                                                bg-amber-100 px-2.5 py-1 font-bold text-base
                                                tabular-nums text-amber-700
                                                dark:text-amber-400">
                                        {{
                                            evolucionAtencionCiudadana
                                                .reduce((total, item) =>
                                                    total + Number(item.solicitudes_recurrentes ?? 0), 0)
                                                .toLocaleString('es-MX')
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex min-w-10 justify-center rounded-lg
                                                bg-indigo-100 px-2.5 py-1 font-bold text-base
                                                tabular-nums text-indigo-900
                                                dark:bg-color1-900/30 dark:text-color1-300">
                                        {{
                                            evolucionAtencionCiudadana
                                                .reduce((total, item) =>
                                                    total +
                                                    Number(item.ciudadanos_nuevos ?? 0) +
                                                    Number(item.solicitudes_recurrentes ?? 0), 0)
                                                .toLocaleString('es-MX')
                                        }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <span
                                        class="inline-flex min-w-[58px] justify-center rounded-lg
                                            bg-emerald-100 px-2.5 py-1 font-bold text-base tabular-nums
                                            text-emerald-900 dark:bg-amber-900/40 dark:text-amber-300">
                                        {{
                                            (() => {
                                                const primeras = evolucionAtencionCiudadana.reduce(
                                                    (total, item) =>
                                                        total + Number(item.ciudadanos_nuevos ?? 0), 0
                                                );

                                                const recurrentes = evolucionAtencionCiudadana.reduce(
                                                    (total, item) =>
                                                        total + Number(item.solicitudes_recurrentes ?? 0), 0
                                                );

                                                const total = primeras + recurrentes;

                                                return total > 0
                                                    ? `${((recurrentes / total) * 100).toLocaleString('es-MX', {
                                                        maximumFractionDigits: 1
                                                    })}%`
                                                    : '—';
                                            })()
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pie informativo -->
                <div class="border-t border-gray-100 px-4 py-2.5 dark:border-gray-700">
                    <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                        * Los totales mensuales de solicitudes corresponden a la suma de ambas categorías.
                        Un ciudadano recurrente puede aparecer en más de un mes.
                    </p>
                </div>
            </div>
            <!-- TABLAS EN LA SIGUIENTE FILA -->
            <div v-if="props.cargando" class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4 animate-pulse">
                <!-- SKELETON: CIUDADANOS RECURRENTES -->
                <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <!-- Encabezado -->
                    <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="h-3 w-40 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                                <div class="mt-2 h-2.5 w-64 max-w-full bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                            </div>

                            <div class="shrink-0 flex flex-col items-end">
                                <div class="h-5 w-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                <div class="mt-1.5 h-2 w-12 bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla simulada -->
                    <div class="p-3 flex-1">
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700/70 overflow-hidden">
                            <!-- Encabezados -->
                            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 dark:bg-gray-700">
                                <div class="h-2 w-36 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                <div class="h-2 w-16 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                            </div>
                            <!-- Filas -->
                            <div v-for="i in 5" :key="i"
                                class="flex items-center justify-between gap-3 px-3 py-3 border-t border-gray-100 dark:border-gray-700/70">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div class="w-6 h-6 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full"
                                        :class="i % 2 === 0 ? 'w-36' : 'w-48'">
                                    </div>
                                </div>

                                <div class="w-10 h-6 shrink-0 rounded-md bg-gray-100 dark:bg-gray-700"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SKELETON: DISTRIBUCIÓN DE SOLICITUDES -->
                <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <!-- Encabezado -->
                    <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                        <div class="h-3 w-44 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        <div class="mt-2 h-2.5 w-64 max-w-full bg-gray-100 dark:bg-gray-700/70 rounded-full"></div>
                    </div>
                    <!-- Tabla simulada -->
                    <div class="p-3 flex-1">
                        <div class="rounded-lg border border-gray-100 dark:border-gray-700/70 overflow-hidden">
                            <!-- Encabezados -->
                            <div class="flex items-center justify-between px-3 py-3 bg-gray-50 dark:bg-gray-700">
                                <div class="h-2 w-36 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                                <div class="h-2 w-16 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                            </div>
                            <!-- Filas -->
                            <div v-for="i in 5" :key="i"
                                class="flex items-center justify-between gap-3 px-3 py-3 border-t border-gray-100 dark:border-gray-700/70">
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full"
                                    :class="i % 2 === 0 ? 'w-28' : 'w-36'">
                                </div>
                                <div class="w-11 h-6 shrink-0 rounded-md bg-gray-100 dark:bg-gray-700"></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                <!-- 2. Grid de Gráficas y Tablas -->
                <!-- TABLA 1: CIUDADANOS RECURRENTES -->
                <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">Ciudadanos recurrentes</h3>
                                <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">Personas que registraron más de una solicitud durante el periodo.</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-lg font-bold leading-5 text-gray-900 dark:text-white tabular-nums">{{ rawDataFromBackend?.length ?? 0 }}</div>
                                <div class="mt-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ (rawDataFromBackend?.length ?? 0) === 1 ? 'Persona' : 'Personas' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 flex-1 min-h-0">
                        <div v-if="rawDataFromBackend?.length" class="overflow-auto max-h-[540px] rounded-lg border border-gray-100 dark:border-gray-700/70">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700">
                                    <tr class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        <th scope="col" class="px-3 py-2.5 font-bold">Persona solicitante</th>
                                        <th scope="col" class="px-3 py-2.5 text-right font-bold whitespace-nowrap">Solicitudes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                    <tr v-for="(item, index) in rawDataFromBackend" :key="item.id_solicitante" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 text-[10px] font-bold shrink-0 tabular-nums">{{ index + 1 }}</span>
                                                <span class="min-w-0 break-words">{{ item.nombre_completo }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                            <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-md bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300 text-xs font-bold tabular-nums">{{ item.total_solicitudes }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="min-h-[180px] rounded-lg border border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center px-4 py-6 text-center">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Sin registros de recurrencia</p>
                            <p class="mt-1 max-w-xs text-xs leading-4 text-gray-500 dark:text-gray-400">No hay personas con más de una solicitud en el periodo.</p>
                        </div>
                    </div>
                </section>

                <!-- TABLA 2: DISTRIBUCIÓN DE SOLICITUDES -->
                <section class="min-w-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700/70">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-100">Distribución de solicitudes</h3>
                        <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">Cantidad de personas según el número de solicitudes del periodo.</p>
                    </div>
                    <div class="p-3 flex-1 min-h-0">
                        <div v-if="kpisAtencionCiudadana.distribucion_solicitudes_ciudadano?.length" class="overflow-auto max-h-[320px] rounded-lg border border-gray-100 dark:border-gray-700/70">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700">
                                    <tr class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        <th scope="col" class="px-3 py-2.5 font-bold">Solicitudes por persona</th>
                                        <th scope="col" class="px-3 py-2.5 text-right font-bold">Ciudadanos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                    <tr v-for="item in kpisAtencionCiudadana.distribucion_solicitudes_ciudadano" :key="item.total_solicitudes" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-3 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {{ item.total_solicitudes }} {{ Number(item.total_solicitudes) === 1 ? 'solicitud' : 'solicitudes' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right">
                                            <span class="inline-flex items-center justify-center min-w-9 px-2.5 py-1 rounded-md bg-color1-50 dark:bg-color1-950/50 text-color1-700 dark:text-color1-300 text-xs font-bold tabular-nums">{{ Number(item.total_ciudadanos).toLocaleString('es-MX') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="min-h-[180px] rounded-lg border border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center px-4 py-6 text-center">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Sin solicitudes registradas</p>
                            <p class="mt-1 text-xs leading-4 text-gray-500 dark:text-gray-400">No hay información para el periodo seleccionado.</p>
                        </div>
                    </div>
                </section>
            </div>                
        </template>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import { ElLoading } from 'element-plus';

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Legend,
);

ChartJS.defaults.font.family = 'Figtree, sans-serif';

const mostrarTablasJuntoAlChart = computed(() => {
    // 1. Si ya cargaron los datos, usamos la longitud real del arreglo
    if (!props.cargando && evolucionAtencionCiudadana.value.length > 0) {
        return evolucionAtencionCiudadana.value.length <= 4;
    }

    // 2. Mientras carga (o si está vacío), calculamos con base en las fechas del arreglo
    if (props.rangoFechasQuery && props.rangoFechasQuery.length === 2) {
        const [fechaInicio, fechaFin] = props.rangoFechasQuery;
        
        if (fechaInicio && fechaFin) {
            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);
            
            // Cálculo exacto de la diferencia en meses
            const mesesDiferencia = 
                (fin.getFullYear() - inicio.getFullYear()) * 12 + 
                (fin.getMonth() - inicio.getMonth()) + 1;
                
            return mesesDiferencia <= 4;
        }
    }

    // Por defecto si no hay fechas definidas aún
    return false;
});

const props = defineProps({
    kpisAtencionCiudadana: {
        type: Object,
        default: () => ({})
    },
    distribucionDiasSemana: {
        type: Array,
        default: () => []
    },
    rawDataFromBackend: {
        type: [Array, Object],
        default: () => []
    },
    rangoFechasQuery: {
        type: Array,
        default: () => []
    },
    cargando: {
        type: Boolean,
        default: true
    }
});

const mesesCortos = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
];

const evolucionAtencionCiudadana = computed(() => {
    const datos = props.kpisAtencionCiudadana?.evolucion_atencion_ciudadana ?? [];

    return datos.map(item => {
        const mes = Number(item.periodo.split('-')[1]);

        const solicitudesNuevas = Number(item.ciudadanos_nuevos ?? 0);
        const solicitudesRecurrentes = Math.max(
            Number(item.total_solicitudes ?? 0) - solicitudesNuevas,
            0
        );

        return {
            periodo: item.periodo,
            mes: mesesCortos[mes - 1],
            total_solicitudes: Number(item.total_solicitudes ?? 0),
            ciudadanos_nuevos: Number(item.ciudadanos_nuevos ?? 0),
            solicitudes_recurrentes: solicitudesRecurrentes,
            porcentaje_recurrentes: Number(item.total_solicitudes ?? 0) > 0
                ? (solicitudesRecurrentes / Number(item.total_solicitudes)) * 100
                : 0
        };
    });
});

const colorVerdeOscuro = getComputedStyle(document.documentElement)
.getPropertyValue('--color-2-600')
.trim();


const chartAtencionCiudadanaData = computed(() => {
    const evolucion = evolucionAtencionCiudadana.value;

    const colorGuindaRojo = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-1-900')
        .trim();

    const datasets = [
        {
            capa: 'ciudadanosNuevos',
            label: 'Ciudadanos 1ᵃ vez',
            data: evolucion.map(item => item.ciudadanos_nuevos),

            backgroundColor: (context) => {
                const chart = context.chart;
                const { ctx, chartArea } = chart;

                if (!chartArea) {
                    return '#8f0030';
                }

                const gradient = ctx.createLinearGradient(
                    0,
                    chartArea.top,
                    0,
                    chartArea.bottom
                );

                gradient.addColorStop(0, '#b3003b');
                gradient.addColorStop(1, '#8f0030');

                return gradient;
            },

            borderColor: '#8f0030',
            borderWidth: 0,

            borderRadius: (context) => {
                const index = context.dataIndex;
                const recurrentes = Number(
                    evolucion[index]?.solicitudes_recurrentes ?? 0
                );

                 if (!capasActivas.value.ciudadanosRecurrentes) {
                    return {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
                }

                return recurrentes > 0
                    ? {
                        topLeft: 0,
                        topRight: 0,
                        bottomLeft: 0,
                        bottomRight: 0
                    }
                    : {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    };
            },

            borderSkipped: false,
            stack: 'ciudadanos',
            order: 3,
        },
        {
            capa: 'ciudadanosRecurrentes',
            label: 'Ciudadanos recurrentes',
            data: evolucion.map(item => item.solicitudes_recurrentes),

            backgroundColor: '#b4905e',
            borderColor: '#b4905e',
            borderWidth: 0,

            borderRadius: (context) => {
                const index = context.dataIndex;
                const primeras = Number(
                    evolucion[index]?.ciudadanos_nuevos ?? 0
                );

                return primeras > 0
                    ? {
                        topLeft: 6,
                        topRight: 6,
                        bottomLeft: 0,
                        bottomRight: 0
                    }
                    : 6;
            },

            borderSkipped: false,
            stack: 'ciudadanos',
            order: 4
        },
        // Capa 3: Total de ciudadanos
        {
            capa: 'totalSolicitudes',
            type: 'line',
            label: 'Total de solicitudes',
            data: evolucion.map(item =>
                Number(item.ciudadanos_nuevos ?? 0) +
                Number(item.solicitudes_recurrentes ?? 0)
            ),
            yAxisID: 'y',
            borderColor: colorGuindaRojo,
            backgroundColor: colorGuindaRojo,
            borderWidth: 2.5,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: colorGuindaRojo,
            pointBorderColor: colorGuindaRojo,
            pointBorderWidth: 3,
            tension: 0.3,
            fill: false,
            order: 2,
        },

        // Capa 4: Tasa de recurrencia
        {
            capa: 'tasaRecurrencia',
            type: 'line',
            label: 'Tasa de recurrencia',
            data: evolucion.map(item =>
                Number(item.porcentaje_recurrentes ?? 0)
            ),
            yAxisID: 'y1',
            borderColor: colorVerdeOscuro,
            backgroundColor: colorVerdeOscuro,
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: colorVerdeOscuro,
            pointBorderColor: colorVerdeOscuro,
            pointBorderWidth: 2,
            tension: 0.3,
            fill: false,
            order: 1,
        },
        
    ];

     return {
        labels: evolucion.map(item => item.mes),
        datasets: datasets.filter(dataset =>
            capasActivas.value[dataset.capa]
        ),
    };
});


const chartAtencionCiudadanaOptions = {

    responsive: true,
    maintainAspectRatio: false,

    interaction: {
        mode: 'index',
        intersect: false
    },

    plugins: {
        datalabels: {
            display: false
        },
        legend: {
            position: 'bottom',
            align: 'middle',
            onClick: (e, legendItem, legend) => {
                // Al no poner lógica aquí, evitamos que altere la visibilidad del gráfico
                e.native.stopPropagation(); // Opcional: evita propagación de eventos si lo requieres
            },
            labels: {
                usePointStyle: true,
                pointStyle: 'rectRounded',
                boxWidth: 9,
                boxHeight: 9,
                padding: 16,
                font: {
                    size: 11,
                    weight: '500'
                },
                sort: (a, b) => a.datasetIndex - b.datasetIndex
            }
        },

       tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.96)',
            padding: 11,
            cornerRadius: 8,

            itemSort: (a, b) => {
                const orden = {
                    'Ciudadanos 1ᵃ vez': 1,
                    'Ciudadanos recurrentes': 2,
                    'Total de solicitudes': 3,
                    'Tasa de recurrencia': 4
                };

                return (orden[a.dataset.label] ?? 99) -
                    (orden[b.dataset.label] ?? 99);
            },

            callbacks: {
                title: (items) => {
                    const index = items[0]?.dataIndex;
                    const periodo = evolucionAtencionCiudadana.value[index]?.periodo;

                    if (!periodo) return '';

                    // Si periodo viene como "2026-01"
                    const [anio, mes] = periodo.split('-');

                    const fecha = new Date(
                        Number(anio),
                        Number(mes) - 1,
                        1
                    );

                    return fecha.toLocaleDateString('es-MX', {
                        month: 'long',
                        year: 'numeric'
                    }).replace(/^./, letra => letra.toUpperCase());
                },

                label: (context) => {
                    const valor = context.parsed.y;

                    if (context.dataset.label === 'Tasa de recurrencia') {
                        return `${context.dataset.label}: ${Number(valor).toFixed(1)}%`;
                    }

                    return `${context.dataset.label}: ${valor}`;
                },
            }
        }
    },

    scales: {
        x: {
            stacked: true,
            grid: {
                display: false
            },
            ticks: {
                color: '#64748b',
                font: {
                    size: 11
                }
            }
        },

        y: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                precision: 0,
                color: '#64748b',
                font: {
                    size: 11
                }
            },
            grid: {
                color: 'rgba(148, 163, 184, 0.15)'
            }
        },
        y1: {
            position: 'right',
            display: true,
            beginAtZero: true,
            min: 0,
            max: 100,
            grid: {
                drawOnChartArea: false,
            },
            title: {
                display: true,
                text: 'Recurrencia (%)',
                color: '#64748b',
                font: {
                    size: 10,
                    weight: '500',
                },
            },
            ticks: {
                stepSize: 20,
                color: colorVerdeOscuro,
                font: {
                    size: 10,
                },
                callback: (value) => `${value}%`,
            },
        },
    }
};

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

    const capasActivas = ref({
        ciudadanosNuevos: true,
        ciudadanosRecurrentes: true,
        totalSolicitudes: false,
        tasaRecurrencia: false,
        tablaDesglose: false,
    });

    const cantidadCapasActivas = computed(() =>
        Object.values(capasActivas.value).filter(Boolean).length
    );

    const capasGrafica = [
    'ciudadanosNuevos',
    'ciudadanosRecurrentes',
    'totalSolicitudes',
    'tasaRecurrencia',
];

    const cambiarCapa = (capa) => {
        // La tabla es independiente de las capas de la gráfica.
        if (capa === 'tablaDesglose') {
            capasActivas.value.tablaDesglose =
                !capasActivas.value.tablaDesglose;
            return;
        }

        // Contar únicamente las capas de la gráfica que están activas.
        const activas = capasGrafica.filter(
            nombre => capasActivas.value[nombre]
        ).length;

        // No permitir desactivar la última capa activa.
        if (capasActivas.value[capa] && activas === 1) {
            return;
        }

        capasActivas.value[capa] = !capasActivas.value[capa];
    };

    const restablecerCapas = () => {
        capasActivas.value = {
            ciudadanosNuevos: true,
            ciudadanosRecurrentes: true,
            totalSolicitudes: false,
            tasaRecurrencia: false,
            tablaDesglose: false,
        };
    };

    const exportarEvolucionAtencionCiudadana = () => {
    if (
        !evolucionAtencionCiudadana.value ||
        evolucionAtencionCiudadana.value.length === 0
    ) {
        return;
    }

    // Activar indicador visual
    const loadingInstance = ElLoading.service({
        lock: true,
        text: 'Generando reporte y preparando descarga...',
        background: 'rgba(15, 23, 42, 0.45)',
    });

    const loadingText = document.querySelector('.el-loading-text');
    if (loadingText) {
        loadingText.style.color = '#ffffff';
        loadingText.style.fontWeight = '400';
    }

    setTimeout(() => {
        try {
            // Encabezados base
            const headers = ['Mes / Periodo'];

            // Agregar columnas según las capas activas
            if (capasActivas.value.ciudadanosNuevos) {
                headers.push('Ciudadanos 1.ª vez');
            }

            if (capasActivas.value.ciudadanosRecurrentes) {
                headers.push('Ciudadanos recurrentes');
            }

            if (capasActivas.value.tasaRecurrencia) {
                headers.push('Tasa de recurrencia');
            }

            if (capasActivas.value.solicitudes) {
                headers.push('Solicitudes totales');
            }

            // Mapear datos a filas CSV
            const rows = evolucionAtencionCiudadana.value.map(row => {
                const fila = [
                    `"${row.periodo ?? ''}"`
                ];

                if (capasActivas.value.ciudadanosNuevos) {
                    fila.push(row.ciudadanos_nuevos ?? 0);
                }

                if (capasActivas.value.ciudadanosRecurrentes) {
                    fila.push(row.ciudadanos_recurrentes ?? 0);
                }

                if (capasActivas.value.tasaRecurrencia) {
                    fila.push(
                        row.porcentaje_recurrentes != null
                            ? row.porcentaje_recurrentes.toFixed(1)
                            : 0
                    );
                }

                if (capasActivas.value.solicitudes) {
                    fila.push(row.total_solicitudes ?? 0);
                }

                return fila;
            });

            // Construir CSV
            const csvContent = [
                headers.join(','),
                ...rows.map(row => row.join(','))
            ].join('\n');

            // BOM para compatibilidad con Excel
            const blob = new Blob(
                ['\uFEFF' + csvContent],
                { type: 'text/csv;charset=utf-8;' }
            );

            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute(
                'download',
                `evolucion_atencion_ciudadana_${new Date()
                    .toISOString()
                    .slice(0, 10)}.csv`
            );

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);

        } catch (error) {
            console.error(
                'Error al exportar la evolución de Atención Ciudadana:',
                error
            );
        } finally {
            loadingInstance.close();
        }
    }, 600);
};
</script>