<template>
    <div class="bg-white dark:bg-gray-800 px-3 pt-3 m-0 flex flex-col justify-between">
        
        <!-- Cabecera -->
        <div class="flex items-center justify-between mb-5">
            <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate transition-colors duration-300 group-hover:text-color1-700 dark:group-hover:text-color1-300">
                Principales motivos de petición
            </span>
            
            <div 
                class="p-1.5 rounded-lg transition-colors duration-300 bg-gray-200/70">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-text text-gray-500">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    <path d="M13 8H7"/>
                    <path d="M17 12H7"/>
                </svg>
            </div>
        </div>

        <!-- Contenedor de palabras con altura controlada para evitar estiramientos -->
        <div class="flex flex-col my-auto">
            <div v-if="panelData?.frecuencia_peticiones && panelData.frecuencia_peticiones.length > 0" class="flex flex-wrap justify-center gap-1 max-h-[85px] overflow-y-auto px-1 py-0.5 custom-scrollbar">
                <span 
                    v-for="(item, index) in panelData.frecuencia_peticiones" 
                    :key="index"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-gray-50 text-gray-500 border border-gray-200/80 dark:bg-gray-700/40 dark:text-gray-200 dark:border-gray-600/60 shadow-2xs hover:border-gray-400 transition-colors">
                    <span>{{ item.palabra }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[0.55rem] font-bold bg-color1-50 text-color1-700 dark:bg-color1-900/50 dark:text-color1-300">
                        {{ item.frecuencia }}
                    </span>
                </span>
            </div>

            <div v-else class="text-center text-gray-400 py-2 text-xs italic">
                Sin datos suficientes para analizar.
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
  panelData: {
    type: Object,
    default: () => ({})
  }
});
</script>