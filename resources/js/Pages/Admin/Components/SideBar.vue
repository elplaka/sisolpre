<template>
  <aside
    class="fixed top-[4rem] left-0 z-40 w-16 sm:w-48 h-[calc(100%-4rem)] bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700"
    aria-label="Sidebar"
    id="drawer-navigation"
  >
    <div class="overflow-y-auto py-6 px-4 h-full"> <ul class="space-y-2"> <li>
          <Link
            :href="route('solicitudes')"
            class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group" >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor"
              class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1" >
              <path
                fill-rule="evenodd"
                d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                clip-rule="evenodd"
              />
              <path
                d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z"
              />
            </svg>
            <span class="ml-2 hidden sm:block text-sm">Solicitudes</span> </Link>
        </li>
        <li>
          <Link
            :href="route('admin.usuarios')"
            class="flex items-center p-1 text-sm font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-color1 group" >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 24 24"
              class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-color1" >
              <path
                d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-7 3.134-7 7h14c0-3.866-3.134-7-7-7z"
              />
            </svg>
            <span class="ml-2 hidden sm:block text-sm">Usuarios</span> </Link>
        </li>
      </ul>
    </div>
  </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style>
/* Definición de clases para los fondos y colores de texto */
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
</style>
