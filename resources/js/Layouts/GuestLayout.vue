<script setup>
import { usePage } from '@inertiajs/vue3';

const { props: { currentYear } } = usePage();
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg"
        >
            <slot :currentYear="currentYear" />
        </div>
    </div>
</template>
