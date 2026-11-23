<template>
<div class="w-full flex flex-col justify-between h-full">
    
    <!-- Fila superior: Título e Icono SVG (Emerald) -->
    <div class="flex items-center justify-between gap-2 w-full">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 transition-colors duration-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
            Apoyos entregados
        </span>

        <!-- SVG minimalista con animación al hover -->
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-600 to-emerald-700 dark:from-emerald-500 dark:to-emerald-600 text-white shadow-xs flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:shadow-emerald-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <path d="M11 12h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 14"></path>
                <path d="m7 18 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"></path>
                <path d="m2 13 6 6"></path>
            </svg>
        </div>
    </div>

    <!-- Contenido central: Barra vertical + Número grande (Homologado en tamaño y espaciados) -->
    <div class="flex items-center gap-3 my-2">
        <div class="w-1 h-8 rounded-full bg-emerald-600 dark:bg-emerald-500 flex-shrink-0 transition-all duration-300 group-hover:h-9"></div>
        <div class="flex flex-col">
            <span class="text-3xl sm:text-[2.1rem] font-extrabold text-gray-900 dark:text-white tracking-tight tabular-nums">
                {{ Number(panelData?.count ?? 0).toLocaleString() }}
            </span>
        </div>
    </div>

    <!-- Fila inferior: Porcentaje + Indicador de Acción -->
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 min-h-[28px] border-t border-gray-100 dark:border-gray-800 transition-colors duration-300 group-hover:border-emerald-100 dark:group-hover:border-emerald-900/40">
        <div class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10M12 20V4M6 20v-6"></path>
            </svg>
            <span><strong class="text-gray-700 dark:text-gray-300 tabular-nums">{{ panelData?.percentage || 0 }}%</strong> del total</span>
        </div>

        <!-- Flecha sutil que se desplaza al hacer hover -->
        <div class="flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 opacity-80 group-hover:opacity-100 transition-opacity">
            <span class="hidden sm:inline uppercase tracking-wider text-[10px]">Ver</span>
            <svg class="w-3.5 h-3.5 transform transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>

</div>
</template>

<script setup>
    defineProps({ 
        panelData: {
            type: [Object, Number],
            default: () => ({ count: 0, percentage: 0 })
        }
    });
</script>
