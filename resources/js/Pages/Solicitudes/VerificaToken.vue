<template>
    <Head title="Token de Solicitud" />
    <div class="max-w-md mx-auto mt-10 p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <img src="/img/Logo_Y_Escudo.jpg" class="block h-20 w-auto mx-auto mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 text-center">Verificación de Token</h2>

        <p class="text-gray-600 dark:text-gray-400 mb-6 text-center">Ingresa el token de acceso que se te envió al correo electrónico que registraste en la solicitud.</p>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="relative">
                <label for="token-input" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white sr-only">Token de Acceso</label>
                <input
                    :type="tokenInputType"
                    id="token-input"
                    v-model="token"
                    autocomplete="off"
                    maxlength="6"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full p-2.5 pr-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 text-center tracking-widest"
                    placeholder="______"
                />
                <button
                    type="button"
                    @click="toggleTokenVisibility"
                    class="absolute inset-y-0 right-0 flex items-center pr-1 focus:outline-none"
                    :aria-label="tokenInputType === 'password' ? 'Mostrar token' : 'Ocultar token'"
                >
                    <svg
                        v-if="tokenInputType === 'password'"
                        xmlns="http://www.w3.org/2000/svg"
                        height="24px"
                        viewBox="0 -960 960 960"
                        width="24px"
                        fill="currentColor"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mr-6"
                    >
                        <path
                            d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"
                        />
                    </svg>

                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        height="24px"
                        viewBox="0 -960 960 960"
                        width="24px"
                        fill="currentColor"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mr-6"
                    >
                        <path
                            d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z"
                        />
                    </svg>
                </button>
            </div>

            <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full text-white bg-color1-700 hover:bg-color1-800 focus:ring-4 focus:outline-none focus:ring-color1-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-color1-600 dark:hover:bg-color1-700 dark:focus:ring-color1-800"
            >
                <span v-if="isSubmitting">Validando...</span>
                <span v-else>Validar</span>
            </button>
        </form>

        <div v-if="error" class="mt-4 flex items-center justify-center gap-2 text-center bg-red-100 border border-red-400 text-color1-500 px-4 py-2 rounded-md dark:bg-red-200 dark:text-red-800">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                    fill-rule="evenodd"
                    d="M18 10c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 
         8-8 8 3.582 8 8zm-9-4a1 1 0 012 0v4a1 1 0 11-2 0V6zm1 8a1.5 
         1.5 0 100-3 1.5 1.5 0 000 3z"
                    clip-rule="evenodd"
                />
            </svg>
            <span>{{ error }}</span>
        </div>
    </div>
</template>

<script setup>
    import { ref } from 'vue'
    import { Head } from '@inertiajs/vue3' // Componente Head para Inertia.js

    // Define props passed from your Laravel Blade view
    const props = defineProps({
        folio_digital: {
            type: String,
            required: true
        },
        initialError: {
            // Renamed from 'error' to avoid conflict with local ref
            type: String,
            default: ''
        }
    })

    // Reactive state for the input value
    const token = ref('')
    // Reactive state to control the input type (password or text)
    const tokenInputType = ref('password')
    // Reactive state for displaying error messages
    const error = ref(props.initialError)
    // Reactive state for loading/submission status
    const isSubmitting = ref(false)

    // --- OBUSFUCATION MAPS ---
    // These maps MUST be identical on both frontend (Vue) and backend (Laravel)
    const OB_MAP_ENCODE = {
        a: 'b',
        b: 'c',
        c: 'd',
        d: 'e',
        e: 'f',
        f: 'g',
        g: 'h',
        h: 'i',
        i: 'j',
        j: 'k',
        k: 'l',
        l: 'm',
        m: 'n',
        n: 'o',
        o: 'p',
        p: 'q',
        q: 'r',
        r: 's',
        s: 't',
        t: 'u',
        u: 'v',
        v: 'w',
        w: 'x',
        x: 'y',
        y: 'z',
        z: 'a',

        A: 'B',
        B: 'C',
        C: 'D',
        D: 'E',
        E: 'F',
        F: 'G',
        G: 'H',
        H: 'I',
        I: 'J',
        J: 'K',
        K: 'L',
        L: 'M',
        M: 'N',
        N: 'O',
        O: 'P',
        P: 'Q',
        Q: 'R',
        R: 'S',
        S: 'T',
        T: 'U',
        U: 'V',
        V: 'W',
        W: 'X',
        X: 'Y',
        Y: 'Z',
        Z: 'A',

        0: '1',
        1: '2',
        2: '3',
        3: '4',
        4: '5',
        5: '6',
        6: '7',
        7: '8',
        8: '9',
        9: '0'
    }

    /**
     * Encodes the token using the defined mapping.
     * @param {string} originalToken The token to encode.
     * @returns {string} The obfuscated token.
     */
    const encodeToken = (originalToken) => {
        return originalToken
            .split('')
            .map((char) => {
                return OB_MAP_ENCODE[char] || char // Use map, or return original if no mapping exists
            })
            .join('')
    }
    // --- END OBUSFUCATION MAPS ---

    /**
     * Toggles the visibility of the token input field.
     */
    const toggleTokenVisibility = () => {
        tokenInputType.value = tokenInputType.value === 'password' ? 'text' : 'password'
    }

    /**
     * Handles the form submission, obfuscates the token, and redirects.
     */
    const submitForm = () => {
        error.value = '' // Clear previous errors
        isSubmitting.value = true // Set loading state

        // Basic client-side validation for token length
        if (token.value.length !== 6) {
            error.value = 'El token debe ser de 6 caracteres.'
            isSubmitting.value = false
            return
        }

        // Encode the token before putting it in the URL
        const encodedToken = encodeToken(token.value)

        console.log('encodedToken', encodedToken)

        // Construct the URL with the obfuscated token as a query parameter 't'
        // Example: /solicitudes/view/YOUR_FOLIO_DIGITAL?t=obfuscatedtoken
        const url = `/solicitudes/view/${props.folio_digital}?token=${encodedToken}`

        // Perform the page redirection
        window.location.href = url

        // Note: Since this is a full page redirect, the 'isSubmitting' state
        // might not be visibly reset, but it's good practice for clarity.
        // The 'finally' block won't run after redirect, so no need for an explicit reset.
    }

    // This `route` helper is no longer used for the form's action directly
    // as we're handling the URL construction and redirection manually in `submitForm`.
    // It's removed from the template's form action.
</script>

<style scoped>
    /* You can add specific component styles here if needed, or rely solely on Tailwind CSS */
    /* The `tracking-widest` class is used to add spacing between characters for better visibility when masked */
</style>
