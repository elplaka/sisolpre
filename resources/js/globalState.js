import { ref } from 'vue';

// Variable para controlar la visibilidad del spinner
export const isGlobalLoading = ref(false);

// Variable para la bandera de exclusión
export const loadingFlag = ref(false);