<template>
  <aside 
    class="fixed top-0 left-0 z-40 w-16 sm:w-56 h-screen bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex flex-col" 
    aria-label="Sidebar" 
    id="drawer-navigation">

    <div class="pt-6">
    <Link :href="route('dashboard')" class="flex flex-col items-center group">
    <!-- Imagen para celular -->
    <img
        src="/img/logo_r.svg"
        alt="Logo"
        class="w-12 mb-2 logo-anim block sm:hidden"
    />
    <!-- Imagen para pantallas medianas en adelante -->
    <img
        src="/img/logo.svg"
        alt="Logo"
        class="w-36 mb-2 logo-anim hidden sm:block"
    />

        <span class="hidden sm:block text-lg font-bold text-center dark:text-white uppercase tracking-wider title-anim">
        SIPRES
        </span>
    </Link>
    </div>

    <div class="overflow-y-auto py-6 h-full">
        <ul :class="['space-y-0', cargandoRuta ? 'pointer-events-none opacity-80' : '']">
            
            <!-- Ítem 1: Solicitudes -->
            <li>
                <a
                    href="#"
                    @click.prevent="cargarSolicitudesConParametros"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group cursor-pointer rounded-lg',
                        isSolicitudesActive 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30 dark:text-color1-300 pointer-events-none'  
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        :class="[
                            'w-5 h-5 transition-transform duration-150 origin-left group-hover:scale-110',
                            isSolicitudesActive ? 'text-color1-700 dark:text-color1-300' : 'text-gray-500 dark:text-gray-400 group-hover:text-color1-600'
                        ]">
                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                        <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                        <path d="M10 9H8"/>
                        <path d="M16 13H8"/>
                        <path d="M16 17H8"/>
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Solicitudes</span>
                </a>
            </li>

            <!-- Ítem 4: Estadísticas -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_estadisticas')">
                <a
                    href="#"
                    @click.prevent="cargarEstadisticasConParametros"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        isEstadisticasActive 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30 pointer-events-none' 
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" 
                        :class="['w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110', isEstadisticasActive ? 'text-color1-700' : 'text-gray-500 group-hover:text-color1']">
                        <path d="M16 20v-7h3v7h-3Zm-6 0V4h3v16h-3Zm-6 0v-11h3v11H4Z"/>
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Estadísticas</span>
                </a>
            </li>

            <!-- Ítem 6: Usuarios -->
            <li v-if="$page.props.auth.user?.permissions.includes('ver_usuarios')">
                <Link
                    :href="route('admin.usuarios')"
                    @click="marcarComoObjetivo('usuarios')"
                    :class="[
                        'flex items-center w-full px-4 py-3.5 transition-all duration-150 group',
                        isUsuariosActive 
                            ? 'text-color1-700 font-bold text-[15px] bg-color1-40 dark:bg-color1-950/30 pointer-events-none' 
                            : 'text-gray-900 font-medium text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" 
                        :class="['w-[1.375rem] h-[1.375rem] transition-transform duration-150 origin-left group-hover:scale-110', isUsuariosActive ? 'text-color1-700' : 'text-gray-500 group-hover:text-color1']">
                        <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-7 3.134-7 7h14c0-3.866-3.134-7-7-7z" />
                    </svg>
                    <span class="ml-2 hidden sm:block transition-transform duration-150 origin-left group-hover:scale-105">Usuarios</span>
                </Link>
            </li>
        </ul>
    </div>

    <div class="p-3 sm:p-4">
    <!-- Contenedor del Perfil de Usuario -->
    <div class="flex items-center gap-3 mb-4 flex-col sm:flex-row text-center sm:text-left">
        <img class="w-10 h-10 rounded-full border border-gray-300 object-cover flex-shrink-0" src="/img/user.jpg" alt="User">
        
        <div class="hidden sm:block overflow-hidden">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $page.props.auth.user.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $page.props.auth.user.email }}</p>
        </div>
    </div>

    <Link 
        :href="route('logout')" 
        method="post" 
        as="button"
        class="group w-full flex items-center justify-center p-2.5 sm:p-2 text-sm text-color1-700 bg-color1-50 rounded-xl sm:rounded-full hover:bg-color1-700 dark:bg-color1-900/20 dark:hover:bg-color1-900/40 transition-colors"
        title="Cerrar Sesión"
    >
        <svg 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke-width="1.5" 
            stroke="currentColor" 
            class="w-5 h-5 sm:mr-2 text-color1-700 transition-colors group-hover:text-white flex-shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
        </svg>
        
        <span class="hidden sm:inline font-medium text-color1-700 transition-colors group-hover:text-white">
            Cerrar Sesión
        </span>
    </Link>
</div>
  </aside>
</template>


<script setup>
    import { Link, router, usePage } from '@inertiajs/vue3'
    import { ref, computed } from 'vue'

    const page = usePage();
    const enlaceActivo = ref(false);
    
    // Estados nuevos para controlar la interactividad global del menú durante la transición
    const cargandoRuta = ref(false);
    const rutaObjetivo = ref(null);

    // Solicitudes Activo
    const isSolicitudesActive = computed(() => {
        // Si el usuario ya dio clic para ir a otra parte, se apaga de inmediato
        if (rutaObjetivo.value && rutaObjetivo.value !== 'solicitudes') return false;
        return enlaceActivo.value || page.url.startsWith('/solicitudes');
    });

    // Estadísticas Activo
    const isEstadisticasActive = computed(() => {
        if (rutaObjetivo.value && rutaObjetivo.value !== 'estadisticas') return false;
        return route().current('estadisticas') || rutaObjetivo.value === 'estadisticas';
    });

    // Usuarios Activo
    const isUsuariosActive = computed(() => {
        if (rutaObjetivo.value && rutaObjetivo.value !== 'usuarios') return false;
        return route().current('admin.usuarios') || rutaObjetivo.value === 'usuarios';
    });

    // Función para marcar el objetivo al hacer clic en enlaces nativos de Inertia (<Link>)
    const marcarComoObjetivo = (destino) => {
        cargandoRuta.value = true;
        rutaObjetivo.value = destino;
    };

    async function cargarSolicitudesConParametros() {
        enlaceActivo.value = true;
        cargandoRuta.value = true;
        rutaObjetivo.value = 'solicitudes';

        try {
           router.get(
                '/solicitudes',
                {
                    rangoFechasIngresoManual: false,
                    desdeSideBar: true,
                    sortCriteriaQuery: 'fecha_asc',
                    page: 1
                },
                {
                    preserveState: true,
                    replace: true,
                    onFinish: () => {
                        enlaceActivo.value = false;
                        cargandoRuta.value = false;
                        rutaObjetivo.value = null;
                    }
                }
            )
        } catch (error) {
            enlaceActivo.value = false;
            cargandoRuta.value = false;
            rutaObjetivo.value = null;
            console.error('Error al obtener solicitudes:', error)
        }
    }

    async function cargarEstadisticasConParametros() {
        cargandoRuta.value = true;
        rutaObjetivo.value = 'estadisticas';

        try {
           router.get(
                '/estadisticas',
                {
                    desdeSideBar: true,
                    estadisticaSeleccionada: ''
                },
                {
                    preserveState: true,
                    replace: true,
                    onFinish: () => {
                        cargandoRuta.value = false;
                        rutaObjetivo.value = null;
                    }
                }
            )
        } catch (error) {
            cargandoRuta.value = false;
            rutaObjetivo.value = null;
            console.error('Error al obtener estadísticas:', error)
        }
    }
</script>

<style>
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

    .active-click:active a {
        transform: scale(0.97);
        background-color: var(--color1-40, rgba(128, 0, 32, 0.15));
        transition: transform 50ms ease, background-color 50ms ease;
    }
</style>