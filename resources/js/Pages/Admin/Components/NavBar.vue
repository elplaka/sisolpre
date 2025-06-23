<template>
    <nav class="bg-white border-b w-full border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed right-0 top-0 z-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                    aria-controls="drawer-navigation"
                    class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <svg class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Toggle sidebar</span>
                </button>
                <Link :href="route('admin.dashboard')" class="flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                        <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                    </svg>
                    &nbsp;&nbsp;
                    <span class="hidden md:inline self-center text-xl font-semibold whitespace-nowrap dark:text-white">Sistema de Planeación Municipal Urbana</span> 
                    <span class="inline md:hidden self-center text-2xl font-semibold whitespace-nowrap dark:text-white">PMU</span>
                </Link>
            </div>
            <div class="ml-auto flex items-center justify-center">
                <img src="/img/logo.svg" alt="Logo" class="w-36 m-0 p-0">
            </div>
            <div class="flex items-center mr-5">
                <button type="button"
                    class="flex items-center mx-3 pl-1 text-sm rounded-full md:mr-0 focus:ring-1 focus:ring-gray-300 dark:focus:ring-gray-600"
                    id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                    <span class="mr-2 text-xs"> <strong> {{ userAuth.name }} </strong></span>
                    <img class="w-8 h-8 rounded-full"
                        src="/img/user.jpg"
                        />
                </button>

                <!-- Dropdown menu -->
                <div class="hidden z-50 my-4 w-56 text-base list-none bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 "
                    id="dropdown">
                    <div class="py-3 px-4">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white"> {{ userAuth.name}} </span>
                        <span class="block text-sm text-gray-900 truncate dark:text-white">{{ userAuth.email}} </span>
                    </div>
                   
                    <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
                        <li class="hover:bg-gray-100 dark:hover:bg-gray-600">
                            <Link :href="route('logout')" method="post" as="button"
                                class="block cursor-pointer py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                Cerrar Sesión
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</template>


<script setup>
import { Link } from '@inertiajs/vue3';
import { defineProps } from 'vue'; 

const props = defineProps({ userAuth: { type: Object, required: true } });

</script>