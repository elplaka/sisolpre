<!-- <template>
    <aside class="fixed top-[4rem] left-0 z-40 w-16 sm:w-48 h-[calc(100%-4rem)] bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar" id="drawer-navigation">
        <div class="overflow-y-auto py-6 px-4 h-full">
            <ul class="space-y-2">
                <li>
                    <Link
                        href="#"
                        @click.prevent="cargarSolicitudesConParametros"
                        class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1">
                            <path
                                fill-rule="evenodd"
                                d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                                clip-rule="evenodd"
                            />
                            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                        </svg>
                        <span class="ml-2 hidden sm:block text-sm">Solicitudes</span>
                    </Link>
                </li>
                <li v-if="$page.props.auth.user?.permissions.includes('ver_estadisticas')">
                    <Link
                        :href="route('estadisticas')"
                        class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1">
                            <path d="M16 20v-7h3v7h-3Zm-6 0V4h3v16h-3Zm-6 0v-11h3v11H4Z"/>
                        </svg>
                        <span class="ml-2 hidden sm:block text-sm">Estadísticas</span>
                    </Link>
                </li>
                <li v-if="$page.props.auth.user?.permissions.includes('ver_usuarios')">
                    <Link
                        :href="route('admin.usuarios')"
                        class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-7 3.134-7 7h14c0-3.866-3.134-7-7-7z" />
                        </svg>
                        <span class="ml-2 hidden sm:block text-sm">Usuarios</span>
                    </Link>
                </li>
                <li v-if="$page.props.auth.user?.permissions.includes('ver_requisitos')">
                    <Link
                        :href="route('requisitos')"
                        class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group">
                    <svg
                        class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        stroke-width="1"
                        stroke="currentColor"
                        fill="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                        <path d="M5 21h14" />
                        <path d="M5 18h14" />
                        <path d="M5 15h14" />
                    </svg>
                        <span class="ml-2 hidden sm:block text-sm">Requisitos</span>
                    </Link>
                </li>
            </ul>
        </div>
    </aside>
</template> -->

<template>
  <aside 
    class="fixed top-0 left-0 z-40 w-16 sm:w-56 h-screen bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex flex-col" 
    aria-label="Sidebar" 
    id="drawer-navigation">

    <div class="p-4">
    <Link :href="route('dashboard')" class="flex flex-col items-center group">
        <img
        src="/img/logo.svg"
        alt="Logo"
        class="w-36 mb-2 logo-anim"
        />

        <span class="hidden sm:block text-xs font-bold text-center dark:text-white uppercase tracking-wider title-anim">
        Planeación Urbana
        </span>
    </Link>
    </div>

    <div class="overflow-y-auto py-6 h-full">
        <ul class="space-y-0">
            <!-- Ítem 1: Solicitudes -->
            <li>
                <Link
                    href="#"
                    @click.prevent="cargarSolicitudesConParametros"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        $page.url.startsWith('/solicitudes') 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30'  
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        :class="[
                            'w-5 h-5 transition-transform duration-150 origin-left group-hover:scale-110',
                            $page.url.startsWith('/solicitudes') 
                                ? 'text-color1-700' 
                                : 'text-gray-500 dark:text-gray-400 group-hover:text-color1'
                        ]">
                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                        <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                        <path d="M10 9H8"/>
                        <path d="M16 13H8"/>
                        <path d="M16 17H8"/>
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Solicitudes</span>
                </Link>
            </li>

            <li>
                <Link
                    href="#"
                    @click.prevent="cargarTramitesConParametros"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        ($page.url.startsWith('/tramites') || $page.url.startsWith('/constancias-numero-oficial'))
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30'  
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 24 24" 
                        fill="none"
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        :class="[
                            'w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110',
                            ($page.url.startsWith('/tramites') || $page.url.startsWith('/constancias-numero-oficial')) ? 'text-color1-700' : 'text-gray-500 dark:text-gray-400 group-hover:text-color1'
                        ]">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M19 8.268a2 2 0 0 1 1 1.732v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h3" />
                        <path d="M5 15.734a2 2 0 0 1 -1 -1.734v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-3" />
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Trámites</span>
                </Link>
            </li>

            <!-- Ítem 3: Propiedades -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_propiedades')">
                <Link
                    href="#"
                    @click.prevent="cargarPropiedadesConParametros"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        $page.url.startsWith('/propiedades') 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30'  
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        :class="[
                            'w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110',
                            $page.url.startsWith('/propiedades') 
                                ? 'text-color1-700' 
                                : 'text-gray-500 dark:text-gray-400 group-hover:text-color1'
                        ]">
                        <path d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z"/>
                        <path d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2"/>
                        <path d="M18 22v-3"/>
                        <circle cx="10" cy="10" r="3"/>
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Propiedades</span>
                </Link>
            </li>

            <!-- Ítem 4: Estadísticas -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_estadisticas')">
                <Link
                    :href="route('estadisticas')"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        route().current('estadisticas') 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30' 
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" 
                        :class="['w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110', route().current('estadisticas') ? 'text-color1-700' : 'text-gray-500 group-hover:text-color1']">
                        <path d="M16 20v-7h3v7h-3Zm-6 0V4h3v16h-3Zm-6 0v-11h3v11H4Z"/>
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Estadísticas</span>
                </Link>
            </li>

            <!-- Ítem 5: Requisitos -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_requisitos')">
                <Link
                    :href="route('requisitos')"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        route().current('requisitos') 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30' 
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg
                        :class="[
                            'w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110', 
                            route().current('requisitos') ? 'text-color1-700' : 'text-gray-500 dark:text-gray-400 group-hover:text-color1'
                        ]"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                        <path d="M5 21h14" />
                        <path d="M5 18h14" />
                        <path d="M5 15h14" />
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Requisitos</span>
                </Link>
            </li>

            <!-- Ítem 6: Usuarios -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_usuarios')">
                <Link
                    :href="route('admin.usuarios')"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        route().current('admin.usuarios') 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30' 
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" 
                        :class="['w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110', route().current('admin.usuarios') ? 'text-color1-700' : 'text-gray-500 group-hover:text-color1']">
                        <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-7 3.134-7 7h14c0-3.866-3.134-7-7-7z" />
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Usuarios</span>
                </Link>
            </li>
        </ul>
    </div>

    <div class="p-4">
      <div class="flex items-center mb-4 sm:flex-row flex-col">
        <img class="w-10 h-10 rounded-full border border-gray-300" src="/img/user.jpg" alt="User">
        <div class="ml-3 hidden sm:block overflow-hidden">
          <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $page.props.auth.user.name }}</p>
          <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.email }}</p>
        </div>
      </div>
      <Link 
        :href="route('logout')" 
        method="post" 
        as="button"
        class="group w-full flex items-center justify-center p-2 text-sm text-color1-700 bg-color1-50 rounded-full hover:bg-color1-700 dark:bg-color1-900/20 dark:hover:bg-color1-900/40 transition-colors">
        <svg 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke-width="1.5" 
            stroke="currentColor" 
            class="w-5 h-5 mr-2 text-color1-700 transition-colors group-hover:text-white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
        </svg>
        
        <span class="text-color1-700 sm:inline font-medium transition-colors group-hover:text-white">
            Cerrar Sesión
        </span>
        </Link>
    </div>
  </aside>
</template>


<script setup>
    import { Link, router } from '@inertiajs/vue3'
    import { ref } from 'vue'

    const nombreQuery = ref('')
    const fechaInicioQuery = ref('')
    const fechaFinQuery = ref('')
    const filtroChkSolicitudes = ref(0) // Filtro de solicitudes

    function formatoFecha(fecha) {
        const year = fecha.getFullYear()
        const month = String(fecha.getMonth() + 1).padStart(2, '0') // meses: 0-indexed
        const day = String(fecha.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
    }

    function inicializaFechas() {
        const hoy = new Date()
        const hace1Mes = new Date()
        hace1Mes.setDate(hoy.getDate() - 30)

        fechaInicioQuery.value = formatoFecha(hace1Mes)
        fechaFinQuery.value = formatoFecha(hoy)

        filtroChkSolicitudes.value = 0
    }

    function cargarSolicitudesConParametros() {
        inicializaFechas() // o lo que se requiera para precargar los valores

        nombreQuery.value = ''

        router.get(
            '/solicitudes',
            {
                fechaInicioQuery: fechaInicioQuery.value,
                fechaFinQuery: fechaFinQuery.value,
                nombreQuery: nombreQuery.value,
            },
            {
                // preserveState: true,
                replace: true
            }
        )
    }

    function inicializaFechas2() {
        const hoy = new Date()
        const hace1Mes = new Date()
        hace1Mes.setDate(hoy.getDate() - 30)

        fechaInicioQuery.value = formatoFecha(hace1Mes)
        fechaFinQuery.value = formatoFecha(hoy)
    }

    function cargarTramitesConParametros() {
        inicializaFechas2() // o lo que se requiera para precargar los valores

        router.post(
            '/tramites',
            {
                fechaInicioQuery: fechaInicioQuery.value,
                fechaFinQuery: fechaFinQuery.value,
                estatusQuery: []
            },
            {
                preserveState: false,
                replace: true
            }
        )
    }

    function cargarPropiedadesConParametros() {
        router.post(
            '/propiedades',
            {
                // estatusQuery: []
            },
            {
                preserveState: false,
                replace: true
            }
        )
    }
</script>

<style>
    /* Definición de clases para los fondos y colores de texto */
    .bg-light-gray {
        background-color: #f3f4f6;
    }
    .bg-burgundy {
        background-color: #800020;
    }
    .text-black {
        color: #000000;
    }
    .text-white {
        color: #ffffff;
    }

    .group:hover .logo-anim {
    animation: wobble 700ms cubic-bezier(.2,.6,.2,1) forwards;
    filter: drop-shadow(0 6px 10px rgba(255, 255, 255, 0.25));
    }

    .group:hover .title-anim {
    animation: glow 600ms ease-out forwards;
    }

    @keyframes wobble {
    0%   { transform: rotate(0deg) scale(1); }
    20%  { transform: rotate(2deg)  scale(1.05); }
    40%  { transform: rotate(-2deg) scale(1.06); }
    60%  { transform: rotate(1.5deg) scale(1.05); }
    80%  { transform: rotate(-1deg) scale(1.03); }
    100% { transform: rotate(0deg) scale(1.04); }
    }


</style>
