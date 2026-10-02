<script setup>
    import { ref } from 'vue'; // Importa markRaw
    import { router } from '@inertiajs/vue3'

    const sortDirection = ref('')
    const sortColumn = ref('')
    const isLoading = ref(false);

    function nuevaSolicitud() {
        isLoading.value = true;

        router.get(
            '/solicitudes/nueva',
            {
                onStart: () => { // <--- Inicia el estado de carga
                    isLoading.value = true;
                },
                onFinish: () => { // <--- Termina el estado de carga
                    isLoading.value = false;
                }
            }
        )
    }
</script>

<template>
    <div class="p-0 mt-0">
        <!-- <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div class="p-6 flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-color1-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-50" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-50" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white">Cargando...</p>
            </div>
        </div> -->
        <div class="bg-white rounded-lg shadow-sm min-h-[160px] flex flex-col gap-4">
            <h3 class="text-lg font-semibold">Accesos Directos</h3>
            <button 
                @click="nuevaSolicitud"
                class="w-full md:w-auto flex items-center justify-center gap-2 bg-color1-600 hover:bg-color1-700 text-white py-2 px-4 rounded-full transition-colors shadow-md hover:shadow-lg">
                <span class="text-xl">+</span>
                <span class="text-md font-bold">Nueva Solicitud</span>
            </button>
        </div>
    </div>
</template>