<template>
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-4 pb-0">
        
        <!-- Título y subtítulo -->
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                Panel General
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Resumen ejecutivo y métricas en tiempo real.
            </p>
        </div>

        <!-- Contenedor derecho: Añadimos w-full sm:w-auto para ordenar el flex -->
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="w-full sm:w-[650px]">
                <div class="flex flex-wrap items-end gap-2">
                    <button
                        v-for="option in options"
                        :key="option.value"
                        type="button"
                        @click="emit('update:modelValue', option.value); emitChange(option.value)"
                        class="shrink-0 min-w-[125px] px-5 py-1 rounded-3xl border text-center
                            transition-all duration-300 ease-out
                            transform-gpu active:scale-95"
                        :class="selectedPeriod === option.value
                            ? 'bg-gray-900 text-white border-gray-900 shadow-md scale-[1.03]'
                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 hover:border-gray-300 hover:-translate-y-0.5 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700'">
                        <div class="flex flex-col gap-0">
                            <span class="text-xs font-bold leading-tight">
                                {{ option.title }}
                            </span>

                            <span
                                class="text-[10px] font-medium leading-tight transition-colors duration-300"
                                :class="selectedPeriod === option.value
                                    ? 'text-gray-300'
                                    : 'text-gray-500 dark:text-gray-400'">
                                {{ option.subtitle }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: 'anio_actual'
    },
    options: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue', 'change', 'periodoChange']);

const selectedPeriod = ref(props.modelValue);

// Sincronizar si el valor cambia externamente desde la URL o el backend
watch(() => props.modelValue, (newVal) => {
    selectedPeriod.value = newVal;
});

const emitChange = (val) => {
    emit('update:modelValue', val);
    emit('change', val);
    emit('periodoChange', val);
};
</script>

<style scoped>
:deep(.custom-select-gray .el-select__selected-item),
:deep(.custom-select-gray .el-select__selected-item span) {
    color: #4f535a !important;
    font-weight: 100 !important;
}
</style>