<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    currentYear: Number
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showError = ref(false);
const errorMessage = ref('');

const submit = () => {
    form.post(route('admin.login.post'), {
        onSuccess: (page) => {
            const error = page.props.flash?.error; // Captura el mensaje de error desde flash
            if (error) {
                errorMessage.value = error; // Asigna el mensaje de error
                showError.value = true;    // Muestra el error en la interfaz
            }
        },
        onFinish: () => {
            form.reset('password');
        },
    });
};

const clearError = () => {
    showError.value = false;
    form.errors = {};
};

</script>

<template>
    <GuestLayout>
        <div class="fixed inset-0 bg-[url('/img/background2.jpg')] bg-cover bg-center w-full h-full">
            <!-- Contenido principal centrado -->
            <div class="absolute inset-0 flex justify-center items-center">
                <div class="relative bg-white p-8 rounded-xl shadow-xl w-[75%] md:w-[25%] max-w-md">
                    <!-- Aquí va tu contenido principal -->
                    <Head title="Iniciar sesión" />
                    <div>
                        <Link href="/">
                            <ApplicationLogo class="w-25 h-20 fill-current text-gray-500" />
                        </Link>
                    </div>
                    <div class="text-gray-700" style="text-align: center; margin-bottom: 20px; margin-top:10px">
                        <p style="font-size: 14pt;"><b> SISTEMA PMU </b></p>
                    </div>
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="email" value="Correo Electrónico" />
                            <input 
                                type="email" 
                                id="email" 
                                class="bg-gray-50 border mt-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-2 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500" 
                                v-model="form.email" 
                                @focus="clearError"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        <div class="mt-4">
                            <InputLabel for="password" value="Contraseña" />
                            <input 
                                type="password" 
                                id="password" 
                                class="bg-gray-50 border mt-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-2 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500" 
                                v-model="form.password" 
                                @focus="clearError"
                                required
                                autofocus
                                autocomplete="current-password"
                            >
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <button class="bg-color1-800 hover:bg-color1-700 text-white focus:ring-4 focus:outline-none focus:ring-color1-300 font-medium rounded-lg text-sm w-full sm:w-auto px-4 py-2 text-center dark:bg-color1-600 dark:hover:bg-color1-700 dark:focus:ring-color1-800" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                                Iniciar Sesión
                            </button>
                        </div>
                        <div v-if="showError" class="flex mt-4 text-sm text-red-600 border-l-4 border-red-600 bg-red-100 rounded" style="padding: 0; height: auto; align-items: stretch;">
                            <div class="flex-shrink-0 bg-red-600 p-2" style="width: 15%; display: flex; justify-content: center; align-items: center;">
                                <div class="bg-white rounded-full p-2" style="display: flex; justify-content: center; align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 text-justify" style="flex-grow: 1; padding: 1rem; display: flex; align-items: center;">
                                <p>Los datos proporcionados no corresponden a los registrados en el sistema. Inténtelo nuevamente.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
    <div class="relative bottom-[120px] left-0 right-0 text-black text-sm text-center text-shadow-md">
        Copyright © {{ currentYear }}. Gobierno del Municipio de Concordia
    </div>
</template>



