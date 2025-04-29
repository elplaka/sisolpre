<template>
    <AdminLayout :userAuth="userAuth">
        <!-- <div class="ml-auto mt-10 flex items-center justify-center mr-4">
            <img src="/img/logo-bco.svg" alt="Logo" style="width: 95vw;" class="m-0 p-0">
        </div> --> 

    </AdminLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { initFlowbite } from 'flowbite'
import AdminLayout from './Components/AdminLayout.vue'
import { defineProps } from 'vue'; 
// initialize components based on data attribute selectors
onMounted(() => {
    initFlowbite();
})

const props = defineProps({ userAuth: { type: Object, required: true } });

</script>

