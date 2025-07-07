<script setup>
    import { Head } from '@inertiajs/vue3' // Componente Head para Inertia.js
    import { computed } from 'vue'

    const props = defineProps({
        solicitud: Object,
        croquis: String
    })

    // Formatear el folio con ceros a la izquierda
    const folioFormateado = computed(() => props.solicitud.id.toString().padStart(4, '0'))

    const croquis = props.croquis
    const norte = '/img/norte.png'

    // Formatear la fecha de ingreso
    const fechaFormateada = computed(() => {
        const fecha = new Date(props.solicitud.fecha_ingreso)
        const opciones = { day: '2-digit', month: 'long', year: 'numeric' }
        return fecha.toLocaleDateString('es-MX', opciones)
    })

    // Saber si el solicitante es el mismo que el propietario
    const esSolicitante = computed(() => props.solicitud.contacto?.id === props.solicitud.propiedad?.contacto?.id)

    // Formato de superficie en m²
    function formatMeters(value) {
        if (value == null) return ''
        const formatted = Number(value).toLocaleString('es-MX', {
            minimumFractionDigits: value % 1 !== 0 ? 2 : 0,
            maximumFractionDigits: 2
        })
        return `${formatted} m²`
    }

    // Mostrar "COL." si no empieza con FRACC o INFONA
    function showColPrefix(nombre) {
        return !nombre.startsWith('FRACC') && !nombre.startsWith('INFONA')
    }

    // Agrupar trámites por tipo
    const tramitesAgrupados = computed(() => {
        const agrupados = {}

        for (const tramite of props.solicitud.tramites || []) {
            const tipo = tramite?.tramite?.tipo_tramite?.nombre ?? 'Sin tipo definido'

            if (!agrupados[tipo]) agrupados[tipo] = []

            agrupados[tipo].push({
                tramite_nombre: tramite?.tramite?.nombre ?? 'Trámite sin nombre'
            })
        }

        return agrupados
    })
</script>
<template>
    <Head title="Vista de Solicitud" />
    <div class="space-y-6 px-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row items-center sm:items-start px-6 gap-4 mt-5">
            <!-- Imagen (se alterna según el tamaño de pantalla) -->
            <div class="flex-shrink-0">
                <!-- Imagen para pantallas grandes -->
                <img src="/img/Marca_Gestion.jpg" class="hidden sm:block h-20 w-auto" />
                <!-- Imagen para pantallas pequeñas -->
                <img src="/img/Logo_Y_Escudo.jpg" class="block sm:hidden h-16 w-auto" />
            </div>

            <!-- Texto -->
            <div class="text-center md:text-left">
                <h2 class="text-2xl font-bold text-gray-800">Formato Único de Solicitud</h2>
                <p class="text-xl text-gray-500">Dirección de Planeación Municipal Urbana</p>
            </div>
        </div>

        <!-- Tarjeta de datos generales -->
        <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
            <div class="grid grid-cols-[1fr_2fr] gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Folio</p>
                    <p class="text-lg font-semibold text-gray-900">{{ folioFormateado }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-500">Fecha de Ingreso</p>
                    <p class="text-lg font-semibold text-gray-900 truncate">{{ fechaFormateada }}</p>
                </div>
            </div>
        </div>

        <!-- Sección de solicitante -->
        <div class="bg-white border border-gray-200 rounded-lg shadow">
            <!-- Encabezado tipo fila con fondo rojo -->
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">
                    {{ esSolicitante ? 'Solicitante / Propietario' : 'Solicitante' }}
                </h3>
            </div>

            <!-- Contenido del bloque -->
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <!-- CURP -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">CURP</p>
                        <p class="text-base text-gray-900">{{ solicitud.contacto.persona.curp }}</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre</p>
                        <p class="text-base text-gray-900">{{ solicitud.contacto.persona.nombre }} {{ solicitud.contacto.persona.apellidos }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="text-base text-gray-900">
                            <span v-if="solicitud.contacto.telefono?.trim()">
                                {{ solicitud.contacto.telefono }}
                            </span>
                            <span v-else class="text-red-600">«SIN CAPTURAR»</span>
                        </p>
                    </div>

                    <!-- Email -->
                    <div v-if="solicitud.contacto.email?.trim()">
                        <p class="text-sm font-medium text-gray-500">E-mail</p>
                        <p class="text-base text-gray-900">{{ solicitud.contacto.email }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sección de propietario -->
        <div v-if="!esSolicitante" class="bg-white border border-gray-200 rounded-lg shadow">
            <!-- Encabezado tipo fila con fondo rojo -->
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">Propietario</h3>
            </div>

            <!-- Contenido del bloque -->
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <!-- CURP -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">CURP</p>
                        <p class="text-base text-gray-900">
                            {{ solicitud.propiedad.contacto.persona.curp }}
                        </p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre</p>
                        <p class="text-base text-gray-900">
                            {{ solicitud.propiedad.contacto.persona.nombre }}
                            {{ solicitud.propiedad.contacto.persona.apellidos }}
                        </p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="text-base text-gray-900">
                            <span v-if="solicitud.propiedad.contacto.telefono?.trim()">
                                {{ solicitud.propiedad.contacto.telefono }}
                            </span>
                            <span v-else class="text-red-600">«SIN CAPTURAR»</span>
                        </p>
                    </div>

                    <!-- Email -->
                    <div v-if="solicitud.propiedad.contacto.email?.trim()">
                        <p class="text-sm font-medium text-gray-500">E-mail</p>
                        <p class="text-base text-gray-900">
                            {{ solicitud.propiedad.contacto.email }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg shadow">
            <!-- Encabezado tipo fila -->
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">Propiedad</h3>
            </div>

            <!-- Contenido -->
            <div class="p-6 space-y-4 text-sm text-gray-800">
                <!-- Clave catastral y tipo -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-gray-500 font-medium">Cve. Catastral:</p>
                        <p class="text-base text-gray-900">{{ solicitud.propiedad.clave_catastral }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Tipo:</p>
                        <p>
                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.tipo">
                                {{ solicitud.propiedad.tipo.nombre }}
                            </span>
                            <span v-else class="text-red-600">«SIN SELECCIONAR»</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Superficie:</p>
                        <p>
                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.superficie && solicitud.propiedad.superficie > 0">
                                {{ formatMeters(solicitud.propiedad.superficie) }}
                            </span>
                            <span v-else class="text-red-600">«SIN CAPTURAR»</span>
                        </p>
                    </div>

                    <div v-if="solicitud.propiedad.id_tipo === 2">
                        <p class="text-gray-500 font-medium">Sup. en Construcción:</p>
                        <p>
                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.superficie_construccion >= 0">
                                {{ formatMeters(solicitud.propiedad.superficie_construccion) }}
                            </span>
                            <span v-else class="text-red-600">«SIN CAPTURAR»</span>
                        </p>
                    </div>
                </div>

                <!-- Domicilio -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-gray-500 font-medium">Domicilio:</p>
                        <p>
                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.calle">{{ solicitud.propiedad.calle }},&nbsp;</span>
                            <span v-else class="text-red-600">«CALLE SIN CAPTURAR»,</span>

                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.numero">N° {{ solicitud.propiedad.numero }},&nbsp;</span>
                            <span v-else class="text-red-600">«NÚMERO SIN CAPTURAR»,</span>

                            <template v-if="solicitud.propiedad.colonia">
                                <span class="text-base text-gray-900" v-if="showColPrefix(solicitud.propiedad.colonia.nombre)">COL. &nbsp;</span>
                                <span class="text-base text-gray-900">{{ solicitud.propiedad.colonia.nombre }}</span>
                            </template>
                        </p>
                    </div>

                    <!-- Localidad -->
                    <div>
                        <p class="text-gray-500 font-medium">Localidad:</p>
                        <p>
                            <span class="text-base text-gray-900" v-if="solicitud.propiedad.localidad">
                                {{ solicitud.propiedad.localidad.nombre }}
                            </span>
                            <span v-else class="text-red-600">«SIN SELECCIONAR»</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg shadow">
            <!-- Encabezado -->
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">Trámite{{ solicitud.tramites.length > 1 ? 's' : '' }} a realizar</h3>
            </div>

            <!-- Contenido -->
            <div class="p-6 overflow-x-auto">
                <!-- Grid de 2 filas: encabezados y listas -->
                <div class="hidden sm:grid gap-4" :style="`grid-template-columns: repeat(${Object.keys(tramitesAgrupados).length}, minmax(0, 1fr))`">
                    <!-- Fila 1: Encabezados -->
                    <div v-for="(tramites, tipo) in tramitesAgrupados" :key="tipo" class="font-semibold text-gray-700 text-base border-b pb-1 text-left">
                        {{ tipo }}
                    </div>

                    <!-- Fila 2: Listas -->
                    <div v-for="(tramites, tipo) in tramitesAgrupados" :key="tipo + '-lista'" class="text-base text-gray-800 text-left">
                        <ul class="list-disc list-inside space-y-1">
                            <li v-for="tramite in tramites" :key="tramite.tramite_nombre">
                                {{ tramite.tramite_nombre }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Vista móvil: apilado vertical -->
                <div class="flex flex-col gap-6 sm:hidden">
                    <div v-for="(tramites, tipo) in tramitesAgrupados" :key="tipo + '-mobile'" class="space-y-2">
                        <div class="font-semibold text-gray-700 text-sm border-b pb-1">
                            {{ tipo }}
                        </div>
                        <ul class="list-disc list-inside text-sm text-gray-800 space-y-1">
                            <li v-for="tramite in tramites" :key="tramite.tramite_nombre">
                                {{ tramite.tramite_nombre }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg shadow">
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">Destino de la Obra</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-x-4 gap-y-2 text-base">
                    <div class="sm:col-span-3 text-gray-800">
                        <span v-if="solicitud.destino_obra">
                            {{ solicitud.destino_obra.nombre }}
                        </span>
                        <span v-else class="text-red-600">«SIN SELECCIONAR»</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg shadow">
            <div class="bg-color3-50 text-gray-900 px-6 py-2 rounded-t-lg">
                <h3 class="text-lg font-bold">Croquis de Localización</h3>
            </div>

            <div class="p-6 text-center">
                <div v-if="croquis" class="relative inline-block border border-gray-300 p-2 overflow-hidden">
                    <img :src="croquis" alt="Croquis de Localización" class="croquis-img mx-auto h-auto" />

                    <img :src="norte" alt="Norte" class="img-norte absolute top-3 right-3 h-[2cm] w-[1.75cm]" />
                </div>
                <div v-else class="text-red-600 p-2">«SIN IMAGEN DE CROQUIS»</div>
            </div>
        </div>
        <br />
    </div>
</template>

<style scoped>
    .croquis-img {
        width: 70vw;
    }

    .img-norte {
        width: 7vw;
        height: auto;
    }

    @media (orientation: portrait) {
        .croquis-img {
            width: 90vw;
        }

        .img-norte {
            width: 8vw;
        }
    }
</style>
