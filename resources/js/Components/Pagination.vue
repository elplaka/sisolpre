<script setup>
    import { router } from '@inertiajs/vue3';
    import { computed } from 'vue';

    const props = defineProps({
        data: {
            type: Object,
            required: true
        }
    });

    // Define los eventos que este componente puede emitir
    const emit = defineEmits(['page-changed']);

    const getNewUrl = (url) => {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('page');
        const newParams = urlParams.toString();
        
        let newUrl = url;
        if (newParams) {
            // Asegúrate de usar '?' para el primer parámetro o '&' si ya hay otros
            newUrl += url.includes('?') ? `&${newParams}` : `?${newParams}`;
        }
        return newUrl;
    };

    const updatePageNumber = (link) => {
        if (link.url) {
            // Extrae el número de página de la URL si existe, o asume 1 si es el primer enlace
            const pageMatch = link.url.match(/page=(\d+)/);
            const pageNumber = pageMatch ? parseInt(pageMatch[1]) : 1; 
            
            // Emite el evento 'page-changed' con el número de página
            emit('page-changed', pageNumber);
        }
    };

    const fetchPage = (url) => {
        if (url) {
            const pageMatch = url.match(/page=(\d+)/);
            const pageNumber = pageMatch ? parseInt(pageMatch[1]) : 1;
            emit('page-changed', pageNumber);
        }
    };

    const filteredLinks = computed(() => { 
        return props.data.links.slice(1, -1); 
    });
</script>

<template>
    <div>
         <nav v-if="data.total > 0" id="nav_pagination" class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                <span v-if="data.from" class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    Mostrando
                    <span class="font-semibold text-gray-900 dark:text-white">{{ data.from }}-{{ data.to }}</span>
                    de
                    <span class="font-semibold text-gray-900 dark:text-white">{{ data.total }}</span>
                </span> 
                <ul class="inline-flex items-stretch -space-x-px">
                    <li>
                    <a href="#" :disabled="!data.prev_page_url"
                    :style="{ opacity: !data.prev_page_url ? 0.75 : 1, 
                              pointerEvents: !data.prev_page_url ? 'none' : 'auto' }"
                    @click.prevent="fetchPage(data.prev_page_url)" class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <span class="sr-only">Previous</span>
                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    </li>
                    <li v-for="(link, index) in filteredLinks" :key="index">
                        <a 
                            href="#" 
                            @click.prevent="updatePageNumber(link)" 
                            :disabled="link.active || !link.url"
                            :class="[
                                'flex items-center justify-center text-sm py-2 px-3 leading-tight border',
                                link.active 
                                    ? 'text-color1 font-bold  border-gray-300 dark:border-color1-700 hover:bg-color1-bg-hover hover:text-color1-hover'
                                    : 'text-gray-500 bg-white border-gray-300 dark:border-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
                            ]">
                            {{ link.label }}
                        </a>
                    </li>

                    <li>
                    <a href="#" :disabled="!data.next_page_url"
                     :style="{ opacity: !data.next_page_url ? 0.75 : 1, 
                              pointerEvents: !data.next_page_url ? 'none' : 'auto' }"
                    @click.prevent="fetchPage(data.next_page_url)" class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <span class="sr-only">Next</span>
                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    </li> 
                </ul>  
            </nav> 
    </div>
</template>

<!-- <script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true
    }
});

// Define los eventos que este componente puede emitir
const emit = defineEmits(['page-changed']);

const getNewUrl = (url) => {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('page');
    const newParams = urlParams.toString();
    
    let newUrl = url;
    if (newParams) {
        // Asegúrate de usar '?' para el primer parámetro o '&' si ya hay otros
        newUrl += url.includes('?') ? `&${newParams}` : `?${newParams}`;
    }
    return newUrl;
};

const updatePageNumber = (link) => {
    if (link.url) {
        // Extrae el número de página de la URL si existe, o asume 1 si es el primer enlace
        const pageMatch = link.url.match(/page=(\d+)/);
        const pageNumber = pageMatch ? parseInt(pageMatch[1]) : 1; 
        
        // Emite el evento 'page-changed' con el número de página
        emit('page-changed', pageNumber);
    }
};

const fetchPage = (url) => {
    if (url) {
        const pageMatch = url.match(/page=(\d+)/);
        const pageNumber = pageMatch ? parseInt(pageMatch[1]) : 1;
        emit('page-changed', pageNumber);
    }
};

const filteredLinks = computed(() => { 
    return props.data.links.slice(1, -1); 
});
</script>

<template>
    <div>
        <nav v-if="data.total > 0" id="nav_pagination" class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                Mostrando
                <span class="font-semibold text-gray-900 dark:text-white">{{ data.from }}-{{ data.to }}</span>
                de
                <span class="font-semibold text-gray-900 dark:text-white">{{ data.total }}</span>
            </span> 
            <ul class="inline-flex items-stretch -space-x-px">
                <li>
                    <a href="#" :disabled="!data.prev_page_url"
                    :style="{ opacity: !data.prev_page_url ? 0.75 : 1, 
                              pointerEvents: !data.prev_page_url ? 'none' : 'auto' }"
                    @click.prevent="fetchPage(data.prev_page_url)" class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <span class="sr-only">Previous</span>
                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
                <li v-for="(link, index) in filteredLinks" :key="index">
                    <a 
                        href="#" 
                        @click.prevent="updatePageNumber(link)" 
                        :disabled="link.active || !link.url"
                        :class="[
                            'flex items-center justify-center text-sm py-2 px-3 leading-tight border',
                            link.active 
                                ? 'text-color1 font-bold border-red-800 dark:border-red-600 hover:bg-red-50 hover:text-red-900 bg-red-100' // Clases para el borde guinda (rojo oscuro)
                                : 'text-gray-500 bg-white border-gray-300 dark:border-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
                        ]">
                        {{ link.label }}
                    </a>
                </li>

                <li>
                    <a href="#" :disabled="!data.next_page_url"
                    :style="{ opacity: !data.next_page_url ? 0.75 : 1, 
                              pointerEvents: !data.next_page_url ? 'none' : 'auto' }"
                    @click.prevent="fetchPage(data.next_page_url)" class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <span class="sr-only">Next</span>
                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li> 
            </ul>  
        </nav> 
    </div>
</template> -->