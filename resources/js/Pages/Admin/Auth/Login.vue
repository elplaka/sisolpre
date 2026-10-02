<script setup>
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import InputLabel from '@/Components/InputLabel.vue'
    import { Head, useForm, Link, usePage } from '@inertiajs/vue3'
    import { ref, onMounted } from 'vue'
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'

    // defineProps({
    //     canResetPassword: {
    //         type: Boolean,
    //     },
    //     currentYear: Number
    // });

    const { props } = usePage()
    const currentYear = props.currentYear

    const form = useForm({
        nickname: '',
        email: '',
        password: '',
        remember: false
    })

    const showError = ref(false)
    const errorMessage = ref('')
    const showSpinner = ref(true)
    const spinnerMessage = ref('Inicializando el sistema...')
    const backgroundImageLoaded = ref(false)

    const submit = () => {
        spinnerMessage.value = 'Verificando credenciales...'
        showSpinner.value = true
        form.post(route('admin.login.post'), {
            onSuccess: (page) => {
                showSpinner.value = false
                const error = page.props.flash?.error
                if (error) {
                    errorMessage.value = error
                    showError.value = true
                }
            },
            onError: () => {
                showSpinner.value = false
            },
            onFinish: () => {
                form.reset('password')
            }
        })
    }

    const clearError = () => {
        showError.value = false
        form.errors = {}
    }

    onMounted(() => {
        const bgImage = new Image()
        spinnerMessage.value = 'Inicializando el sistema...'
        bgImage.onload = () => {
            backgroundImageLoaded.value = true
            showSpinner.value = false
        }
        bgImage.src = '/img/imagen_wallpaper.jpg'
    })
</script>

<template>
    <GuestLayout class="h-screen overflow-hidden">
        <div v-if="backgroundImageLoaded" class="fixed inset-0 bg-[url('/img/imagen_wallpaper.jpg')] bg-cover bg-center w-full h-screen overflow-hidden">
            <div class="absolute inset-0 flex justify-center items-center min-h-screen">
                <div v-if="showSpinner" class="absolute inset-0 bg-white opacity-75 flex flex-col justify-center items-center rounded-xl z-10">
                    <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0c-4.42 0-8 3.58-8 8z"></path>
                    </svg>
                    <p class="mt-2 text-gray-900">{{ spinnerMessage }}</p>
                </div>
                <div class="relative bg-white px-8 py-10 rounded-xl shadow-xl w-[75%] md:w-[25%] max-w-md">
                    <Head title="Iniciar sesión" />
                    <div>
                        <Link href="/">
                            <ApplicationLogo class="w-25 h-20 fill-current text-gray-500" />
                        </Link>
                    </div>
                  <div class="text-center my-5 md:my-6">
                        <p class="text-[11px] sm:text-sm md:text-base font-bold tracking-wider text-gray-700">
                            SIPRES
                        </p>
                    </div>
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="nickname" value="Usuario" />
                            <input
                                type="text"
                                id="nickname"
                                class="bg-gray-50 border mt-2 border-gray-300 text-gray-900 text-sm rounded-2xl focus:ring-color1-500 focus:border-color1-500 block w-full pl-2 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                v-model="form.nickname"
                                @focus="clearError"
                                required
                                autofocus
                                autocomplete="nickname"
                            />
                        </div>
                        <div class="mt-4">
                            <InputLabel for="password" value="Contraseña" />
                            <input
                                type="password"
                                id="password"
                                class="bg-gray-50 border mt-2 border-gray-300 text-gray-900 text-sm rounded-2xl focus:ring-color1-500 focus:border-color1-500 block w-full pl-2 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                v-model="form.password"
                                @focus="clearError"
                                required
                                autofocus
                                autocomplete="current-password"
                            />
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <button
                                class="bg-color1-800 hover:bg-color1-700 text-white focus:ring-4 focus:outline-none focus:ring-color1-300 font-medium rounded-full text-sm w-full sm:w-auto px-8 py-2 text-center dark:bg-color1-600 dark:hover:bg-color1-700 dark:focus:ring-color1-800"
                                :class="{ 'opacity-50': form.processing }"
                                :disabled="form.processing || showSpinner"
                            >
                                Iniciar Sesión
                            </button>
                        </div>
                        <div v-if="showError" class="flex mt-4 text-sm text-red-600 border-l-4 border-red-600 bg-red-100 rounded" style="padding: 0; height: auto; align-items: stretch">
                            <div class="flex-shrink-0 bg-red-600 p-2" style="width: 15%; display: flex; justify-content: center; align-items: center">
                                <div class="bg-red-400 rounded-full p-2" style="display: flex; justify-content: center; align-items: center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 text-justify" style="flex-grow: 1; padding: 1rem; display: flex; align-items: center">
                                <p>{{ errorMessage || 'Los datos proporcionados no corresponden a los registrados en el sistema. Inténtelo nuevamente.' }}</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-else class="fixed inset-0 bg-gray-900 flex justify-center items-center">
            <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0c-4.42 0-8 3.58-8 8z"></path>
            </svg>
            <p class="mt-2 text-white">{{ spinnerMessage }}</p>
        </div>
    </GuestLayout>
    <div class="fixed bottom-0 left-0 right-0 text-white text-sm text-center text-shadow-md p-4">Copyright © {{ currentYear }}. Gobierno del Municipio de Concordia</div>
</template>
