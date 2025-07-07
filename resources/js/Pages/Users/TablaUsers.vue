<script setup>
    import { router, usePage } from '@inertiajs/vue3'
    import { ref, computed, watch, onMounted } from 'vue'
    import Pagination from '@/Components/Pagination.vue'

    const props = defineProps({
        users: Array,
        types: Array,
        pagination: Object,
        searchQuery: String,
        selectedTypes: Array,
        isActive: Boolean,
        isInactive: Boolean,
        activos: Number,
        inactivos: Number,
        sortColumn: String,
        sortDirection: String
    })

    // Estado para almacenar la columna actual y la dirección de ordenación
    const sortColumn = ref('')
    const sortDirection = ref('asc')
    const isSubmitting = ref(false)

    // Función para ordenar la tabla
    function sortTable(column) {
        if (sortColumn.value === column) {
            // Cambiar la dirección de ordenación si se hace clic en la misma columna
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
        } else {
            // Establecer la nueva columna y la dirección de ordenación a ascendente
            sortColumn.value = column
            if (sortColumn.value == 'es_activo') {
                sortDirection.value = 'desc'
            } else {
                sortDirection.value = 'asc'
            }
        }

        // Obtener los parámetros actuales
        const urlParams = new URLSearchParams(window.location.search)
        const selectedTypesSet = new Set(selectedTypes.value)

        // Añadir los nuevos valores de selectedTypes[] sin duplicados
        selectedTypesSet.forEach((type) => {
            urlParams.append('selectedTypes[]', type)
        })

        if (isActive.value) {
            urlParams.set('isActive', true)
        } else if (isInactive.value) {
            urlParams.set('isActive', false)
        } else {
            urlParams.delete('isActive')
        }

        if (isInactive.value) {
            urlParams.set('isInactive', true)
        } else if (isInactive.value) {
            urlParams.set('isInactive', false)
        } else {
            urlParams.delete('isInactive')
        }

        // Construir el objeto de parámetros final
        const paramsObject = {}
        urlParams.forEach((value, key) => {
            if (key === 'selectedTypes[]') {
                // Almacenar los valores como un array en paramsObject
                if (!paramsObject[key]) {
                    paramsObject[key] = []
                }
                paramsObject[key].push(value)
            } else {
                paramsObject[key] = value
            }
        })

        // Añadir los nuevos parámetros sortColumn y sortDirection al objeto
        paramsObject.sortColumn = sortColumn.value
        paramsObject.sortDirection = sortDirection.value

        // Realizar la solicitud con los parámetros combinados
        router.get('/admin/usuarios', paramsObject, {
            preserveState: true,
            replace: true
        })
    }

    const searchQuery = ref(props.searchQuery || '')

    watch(searchQuery, () => {
        fetchUsers()
    })

    function fetchUsers() {
        // Obtener los parámetros de consulta actuales
        const urlParams = new URLSearchParams(window.location.search)

        // Añadir el nuevo parámetro 'query'
        if (searchQuery.value) {
            urlParams.set('query', searchQuery.value)
        } else {
            urlParams.delete('query') // Eliminar 'query' si está vacío
        }

        // Eliminar los parámetros previos de 'selectedTypes[]'
        ;[...urlParams.keys()].forEach((key) => {
            if (key === 'selectedTypes[]' || key === 'selectedTypes[][]') {
                urlParams.delete(key)
            }
        })

        // Establecer la página a 1
        urlParams.set('page', '1')

        // Crear un set para evitar duplicados
        const selectedTypesSet = new Set(selectedTypes.value)

        if (isActive.value) {
            urlParams.set('isActive', true)
        } else if (isInactive.value) {
            urlParams.set('isActive', false)
        } else {
            urlParams.delete('isActive')
        }

        if (isInactive.value) {
            urlParams.set('isInactive', true)
        } else if (isInactive.value) {
            urlParams.set('isInactive', false)
        } else {
            urlParams.delete('isInactive')
        }

        // Añadir los nuevos valores de selectedTypes[] sin duplicados
        selectedTypesSet.forEach((type) => {
            urlParams.append('selectedTypes[]', type)
        })

        // Construir el objeto de parámetros final
        const paramsObject = {}
        urlParams.forEach((value, key) => {
            if (key === 'selectedTypes[]') {
                // Almacenar los valores como un array en paramsObject
                if (!paramsObject[key]) {
                    paramsObject[key] = []
                }
                paramsObject[key].push(value)
            } else {
                paramsObject[key] = value
            }
        })

        // Realizar la solicitud con los parámetros de consulta combinados
        router.get('/admin/usuarios/search', paramsObject, {
            preserveState: true,
            replace: true
        })
    }

    // Mantener el estado de searchQuery después de la actualización de la página
    onMounted(() => {
        const page = usePage()
        const query = page.props.searchQuery || ''
        if (query) {
            searchQuery.value = query
        }
        const types = page.props.selectedTypes || []
        selectedTypes.value = types.flat()

        isActive.value = page.props.isActive
        isInactive.value = page.props.isInactive
    })

    const isFocused = ref(false) // SELECT - Controla si el select tiene el foco
    const isFocusedEstatus = ref(false) // SELECT - Controla si el select tiene el foco
    const isFocusedGenero = ref(false)

    const id = ref('')
    const name = ref('')
    const last_name = ref('')
    const nickname = ref('')
    const email = ref('')
    const celular = ref('')
    const genero = ref('')
    const fecha_entrada = ref(null)
    const verificado = ref(true)
    const type_id = ref('')
    const es_activo = ref('')
    const es_activo_select = ref('')
    const password = ref('')
    const password_confirmation = ref('')

    const paraNuevoUsuario = ref(false)
    const paraEditarUsuario = ref(false)
    const dialogVisible = ref(false)
    const floatingNameRef = ref(null) // Ref para el input

    const abreModalUsuario = async () => {
        resetFormData()

        paraNuevoUsuario.value = true
        paraEditarUsuario.value = false
        dialogVisible.value = true

        setTimeout(() => {
            floatingNameRef.value?.focus()
        }, 50)
    }

    const abreModalEditarUsuario = (user) => {
        paraNuevoUsuario.value = false
        paraEditarUsuario.value = true
        dialogVisible.value = true
        password.value = ''

        id.value = user.id
        name.value = user.name
        last_name.value = user.last_name
        nickname.value = user.nickname
        email.value = user.email
        celular.value = user.celular
        genero.value = user.genero
        es_activo.value = user.es_activo
        type_id.value = user.user_type_id
        verificado.value = user.verificado === 1 ? true : false
    }

    const cierraModalEditarUsuario = () => {
        dialogVisible.value = false
    }

    const handleClose = () => {
        dialogVisible.value = false
    }

    const AddUser = async () => {
        isSubmitting.value = true // Habilitar el botón

        const formData = new FormData()
        formData.append('name', name.value)
        formData.append('last_name', last_name.value)
        formData.append('nickname', nickname.value)
        formData.append('email', email.value)
        formData.append('celular', celular.value)
        formData.append('type_id', type_id.value)
        formData.append('genero', genero.value)
        formData.append('type_id', type_id.value)
        formData.append('verificado', verificado.value)
        formData.append('password', password.value)
        formData.append('password_confirmation', password_confirmation.value)

        try {
            await router.post('/admin/usuarios/store', formData, {
                onSuccess: (page) => {
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    dialogVisible.value = false
                    resetFormData()
                },
                onFinish: () => {
                    isSubmitting.value = false // Habilitar el botón
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    // Mostrar SweetAlert2 con los errores de validación
                    let errorMessage = 'Hubo un error al guardar la información.'

                    // Si hay errores de validación, construir un mensaje con ellos
                    if (errors && Object.keys(errors).length > 0) {
                        errorMessage = Object.values(errors).join('<br>') // Unir los errores en un solo mensaje
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage,
                        confirmButtonText: 'Aceptar',
                        width: '200px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })
                }
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }
    }

    const UpdateUser = async () => {
        isSubmitting.value = true // Habilitar el botón

        const formData = new FormData()
        formData.append('name', name.value)
        formData.append('last_name', last_name.value)
        formData.append('nickname', nickname.value)
        formData.append('email', email.value)
        formData.append('celular', celular.value)
        formData.append('genero', genero.value)
        formData.append('verificado', verificado.value)
        formData.append('es_activo', es_activo.value)
        formData.append('type_id', type_id.value)
        if (password.value.length > 0) {
            formData.append('password', password.value)
            formData.append('password_confirmation', password_confirmation.value)
        }
        formData.append('_method', 'POST')

        try {
            await router.post('/admin/usuarios/update/' + id.value, formData, {
                onSuccess: (page) => {
                    dialogVisible.value = false
                    resetFormData()
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                },
                onFinish: () => {
                    isSubmitting.value = false // Habilitar el botón
                },
                preserveScroll: true,
                preserveState: true,
                replace: true
            })
        } catch (err) {
            console.log(err)
        }
    }

    const resetFormData = () => {
        id.value = ''
        name.value = ''
        last_name.value = ''
        nickname.value = ''
        email.value = ''
        celular.value = ''
        password.value = ''
        password_confirmation.value = ''
        type_id.value = ''
        genero.value = ''
        const today = new Date()
        const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().split('T')[0]
        fecha_entrada.value = localDate
        verificado.value = false
        es_activo.value = ''
    }

    const dialogWidth = computed(() => {
        return window.innerWidth < 1024 ? '70%' : '50%'
    })

    const shouldShowPasswordNote = computed(() => {
        if (paraNuevoUsuario.value) {
            return true
        } else if (paraEditarUsuario.value && password.value.length > 0) {
            return true
        } else return false
    })

    const handleFocus = (status) => {
        isFocused.value = status // SELECT - Cambia el estado del foco
    }

    const handleFocusEstatus = (status) => {
        isFocusedEstatus.value = status // SELECT - Cambia el estado del foco
    }

    const handleFocusGenero = (status) => {
        isFocusedGenero.value = status // SELECT - Cambia el estado del foco
    }

    // Observa los cambios en es_activo y realiza acciones en consecuencia
    watch(es_activo, (newVal) => {
        if (newVal === 0 || newVal === 1) {
            es_activo_select.value = true
        } else {
            es_activo_select.value = false
        }
    })

    const isActive = ref(false)
    const isInactive = ref(false)
    const selectedTypes = ref([])
    const selectedCount = computed(() => {
        return selectedTypes.value.length + isActive.value + isInactive.value
    })

    const selectedCountText = computed(() => {
        return selectedCount.value > 0 ? `(${selectedCount.value})` : ''
    })

    watch(selectedTypes, () => {
        updateFilteredUsers()
    })

    watch(isActive, () => {
        updateFilteredUsers()
    })

    watch(isInactive, () => {
        updateFilteredUsers()
    })

    function updateFilteredUsers() {
        // Obtener los parámetros de consulta actuales
        const urlParams = new URLSearchParams(window.location.search)

        // Eliminar los parámetros previos de 'selectedTypes[]'
        ;[...urlParams.keys()].forEach((key) => {
            if (key === 'selectedTypes[]' || key === 'selectedTypes[][]') {
                urlParams.delete(key)
            }
        })

        // Establecer la página a 1
        urlParams.set('page', '1')

        // Crear un set para evitar duplicados
        const selectedTypesSet = new Set(selectedTypes.value)

        // Añadir los nuevos valores de selectedTypes[] sin duplicados
        selectedTypesSet.forEach((type) => {
            urlParams.append('selectedTypes[]', type)
        })

        if (isActive.value) {
            urlParams.set('isActive', true)
        } else if (isInactive.value) {
            urlParams.set('isActive', false)
        } else {
            urlParams.delete('isActive')
        }

        if (isInactive.value) {
            urlParams.set('isInactive', true)
        } else if (isInactive.value) {
            urlParams.set('isInactive', false)
        } else {
            urlParams.delete('isInactive')
        }

        // Construir el objeto de parámetros final
        const paramsObject = {}
        urlParams.forEach((value, key) => {
            if (key === 'selectedTypes[]') {
                // Almacenar los valores como un array en paramsObject
                if (!paramsObject[key]) {
                    paramsObject[key] = []
                }
                paramsObject[key].push(value)
            } else {
                paramsObject[key] = value
            }
        })

        let url = ''

        if (window.location.pathname.includes('/search')) {
            url = '/admin/usuarios/search'
        } else {
            url = '/admin/usuarios'
        }

        // Realizar la solicitud con los parámetros de consulta combinados
        router.get(url, paramsObject, {
            preserveState: true,
            replace: true
        })
    }
</script>
<template>
    <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
        <el-dialog v-model="dialogVisible" :title="paraEditarUsuario ? 'Editar usuario' : 'Nuevo usuario'" :width="dialogWidth" :before-close="handleClose" top="10vh">
            <form @submit.prevent="paraEditarUsuario ? UpdateUser() : AddUser()" class="max-w-4xl mx-auto mt-1">
                <!-- Contenedor de grid con dos columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="name"
                            ref="floatingNameRef"
                            autocomplete="off"
                            type="text"
                            name="floating_name"
                            id="floating_name"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            required
                        />
                        <label
                            style="letter-spacing: -0.12em"
                            for="floating_name"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            N o m b r e
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="last_name"
                            autocomplete="off"
                            type="text"
                            name="floating_last_name"
                            id="floating_last_name"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            required
                        />
                        <label
                            for="floating_last_name"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            Apellido(s)
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="nickname"
                            autocomplete="off"
                            type="nickname"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            required
                        />
                        <label
                            style="letter-spacing: -0.12em"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            N i c k n a m e
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="email"
                            autocomplete="off"
                            type="email"
                            name="no-cuenta"
                            id="no-cuenta"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            required
                        />
                        <label
                            style="letter-spacing: -0.12em"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            C o r r e o &nbsp; E l e c t r ó n i c o
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="celular"
                            autocomplete="off"
                            type="text"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            required
                            minlength="10"
                            maxlength="10"
                            pattern="\d{10}"
                            title="El celular debe tener exactamente 10 dígitos."
                        />
                        <label
                            style="letter-spacing: -0.12em"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            C e l u l a r
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="password"
                            type="password"
                            name="floating_password"
                            id="floating_password"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                            :required="paraNuevoUsuario"
                        />
                        <label
                            for="floating_password"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            Contraseña {{ paraEditarUsuario ? '(Dejar en blanco para mantener la actual)' : '' }}
                        </label>
                    </div>

                    <div v-if="shouldShowPasswordNote" class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="password_confirmation"
                            type="password"
                            name="floating_password_confirmation"
                            id="floating_password_confirmation"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=" "
                            :required="paraNuevoUsuario"
                        />
                        <label
                            for="floating_password_confirmation"
                            class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-placeholder-shown:translate-y-0 peer-focus:scale-90 peer-focus:-translate-y-6 peer-not-empty:scale-90"
                        >
                            Confirmar Contraseña
                        </label>
                    </div>

                    <div class="relative z-0 w-full mb-5 group">
                        <select
                            id="select_tipo"
                            v-model="type_id"
                            @focus="handleFocus(true)"
                            @blur="handleFocus(false)"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            required
                        >
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                        <label
                            for="select_tipo"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform top-3 -z-10 origin-[0] peer-focus:font-medium peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-focus:scale-90 peer-not-empty:scale-90"
                            :class="{
                                'scale-85 -translate-y-6': type_id || isFocused,
                                'scale-90 translate-y-0': !type_id && !isFocused
                            }"
                            :style="{
                                top: type_id || isFocused ? '12px' : '10px'
                            }"
                        >
                            Tipo
                        </label>
                    </div>
                    <div class="relative z-0 w-full mb-5 group">
                        <select
                            id="select_genero"
                            v-model="genero"
                            @focus="handleFocusGenero(true)"
                            @blur="handleFocusGenero(false)"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            required
                        >
                            <option value="" disabled selected style="display: none"></option>
                            <option :key="'H'" :value="'H'">HOMBRE</option>
                            <option :key="'M'" :value="'M'">MUJER</option>
                        </select>
                        <label
                            for="select_genero"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform top-3 -z-10 origin-[0] peer-focus:font-medium peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-focus:scale-90 peer-not-empty:scale-90"
                            :class="{
                                'scale-85 -translate-y-6': genero || isFocusedGenero,
                                'scale-90 translate-y-0': !genero && !isFocusedGenero
                            }"
                            :style="{
                                top: genero || isFocusedGenero ? '12px' : '10px'
                            }"
                        >
                            Género
                        </label>
                    </div>
                    <div v-if="paraEditarUsuario" class="relative z-0 w-full mb-5 group">
                        <select
                            id="select_estatus"
                            v-model="es_activo"
                            @focus="handleFocusEstatus(true)"
                            @blur="handleFocusEstatus(false)"
                            class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            required
                        >
                            <option value="" style="display: none" disabled selected></option>
                            <option :key="1" :value="1">ACTIVO</option>
                            <option :key="0" :value="0">INACTIVO</option>
                        </select>

                        <label
                            for="select_estatus"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform top-3 -z-10 origin-[0] peer-focus:font-medium peer-focus:text-color1 peer-focus:dark:text-color1 peer-placeholder-shown:scale-90 peer-focus:scale-90 peer-not-empty:scale-90"
                            :class="{
                                'scale-85 -translate-y-6': es_activo_select || isFocusedEstatus,
                                'scale-90 translate-y-0': !es_activo_select && !isFocusedEstatus
                            }"
                            :style="{
                                top: es_activo_select || isFocusedEstatus ? '12px' : '10px' // Ajusta la posición inicial
                            }"
                        >
                            Estatus
                        </label>
                    </div>
                    <div class="relative z-0 w-full mb-5 group">
                        <input
                            v-model="verificado"
                            type="checkbox"
                            id="verificado"
                            class="mr-2 w-5 h-5 text-color1 bg-transparent border-2 border-gray-300 appearance-none checked:bg-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            placeholder=""
                        />
                        <label
                            for="verificado"
                            :class="{
                                'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-100 peer-focus:text-color1 peer-focus:dark:text-color1': verificado,
                                'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-90': !verificado
                            }"
                        >
                            Verificado
                        </label>
                    </div>
                </div>
                <div class="relative z-0 w-full mb-5 group flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                    <button
                        :disabled="isSubmitting"
                        type="submit"
                        class="bg-color1-800 hover:bg-color1-700 text-white focus:ring-4 focus:outline-none focus:ring-color1-300 font-medium rounded-lg text-base md:text-sm w-full md:w-1/6 px-4 py-2 text-center dark:bg-color1-600 dark:hover:bg-color1-700 dark:focus:ring-color1-800"
                    >
                        Aceptar
                    </button>
                    <button
                        :disabled="isSubmitting"
                        @click="cierraModalEditarUsuario"
                        type="button"
                        class="bg-color3-600 hover:bg-color3-500 text-white focus:ring-4 focus:outline-none focus:ring-color3-300 font-medium rounded-lg text-base md:text-sm w-full md:w-1/6 px-4 py-2 text-center dark:bg-color3-600 dark:hover:bg-color3-700 dark:focus:ring-color3-800"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </el-dialog>

        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[95%]">
            <h1 class="text-2xl font-bold">Usuarios</h1>
            <br />
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                <div class="relative flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                    <div class="w-full md:w-1/2">
                        <form class="flex items-center">
                            <label for="simple-search" class="sr-only">Buscar...</label>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="simple-search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                    placeholder="Buscar por NOMBRE-APELLIDO"
                                    v-model="searchQuery"
                                    @input="fetchUsers"
                                />
                            </div>
                        </form>
                    </div>
                    <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                        <button
                            @click="abreModalUsuario"
                            type="button"
                            class="bg-color1-800 hover:bg-color1-700 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-800"
                        >
                            + Nuevo
                        </button>
                        <div class="flex items-center space-x-3 w-full md:w-auto">
                            <button
                                :class="{ 'disabled-button': users.length == 0 }"
                                :disabled="users.length == 0"
                                id="filterDropdownButton"
                                data-dropdown-toggle="filterDropdown"
                                class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-color1-200 dark:focus:ring-color1-700 dark:bg-color1-800 dark:text-color1-400 dark:border-color1-600 dark:hover:text-white dark:hover:bg-color1-700"
                                :style="users.length == 0 ? 'background-color: #f3f4f6; border-color: #d1d5db; color: #9ca3af; cursor: not-allowed;' : ''"
                                type="button"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewbox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Filtros {{ selectedCountText }}
                                <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path
                                        clip-rule="evenodd"
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    />
                                </svg>
                            </button>
                            <div
                                id="filterDropdown"
                                class="z-10 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700"
                                style="position: absolute; top: 100%; left: 0; z-index: 50; width: auto; min-width: 12rem"
                            >
                                <div class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-color1" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <h6 class="mb-1 text-sm font-medium text-color1 dark:text-white"><b>TIPO DE USUARIO</b></h6>
                                </div>
                                <hr class="mb-2" />
                                <ul v-if="types && types.length > 0" class="text-sm" aria-labelledby="filterDropdownButton">
                                    <li v-for="type in types" :key="type.id" class="flex items-center mb-0">
                                        <input
                                            :id="`type-${type.id}`"
                                            type="checkbox"
                                            class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-500 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                            v-model="selectedTypes"
                                            :value="type.id"
                                            :disabled="type.users_count == 0"
                                            :style="type.users_count == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''"
                                        />
                                        <label
                                            :for="`type-${type.id}`"
                                            :style="type.users_count == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''"
                                            class="mb-1 ml-2 text-sm uppercase text-gray-900 dark:text-gray-100"
                                        >
                                            {{ type.name }} ({{ type.users_count }})
                                        </label>
                                    </li>
                                </ul>
                                <div class="flex items-center justify-center mt-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-color1" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <h6 class="mb-1 text-sm font-medium text-color1 dark:text-white"><b>ESTATUS</b></h6>
                                </div>
                                <hr class="mb-2" />
                                <ul class="text-sm" aria-labelledby="filterDropdownButton">
                                    <li class="flex items-center mb-0">
                                        <input
                                            type="checkbox"
                                            v-model="isActive"
                                            class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-500 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                            :disabled="activos == 0"
                                            :style="activos == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''"
                                        />
                                        <label :style="activos == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''" class="mb-1 ml-2 text-sm uppercase text-gray-900 dark:text-gray-100">
                                            ACTIVO ({{ activos }})
                                        </label>
                                    </li>
                                </ul>
                                <ul class="text-sm" aria-labelledby="filterDropdownButton">
                                    <li class="flex items-center mb-0">
                                        <input
                                            type="checkbox"
                                            v-model="isInactive"
                                            class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-500 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                            :disabled="inactivos == 0"
                                            :style="inactivos == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''"
                                        />
                                        <label :style="inactivos == 0 ? 'color: #9ca3af; opacity: 0.5; cursor: not-allowed;' : ''" class="mb-1 ml-2 text-sm uppercase text-gray-900 dark:text-gray-100">
                                            INACTIVO ({{ inactivos }})
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Id</span>
                                        <svg v-if="sortColumn === 'id'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('name')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Nombre</span>
                                        <svg v-if="sortColumn === 'name'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('nickname')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Nickname</span>
                                        <svg v-if="sortColumn === 'name'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('email')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Correo Electrónico</span>
                                        <svg v-if="sortColumn === 'email'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('celular')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Celular</span>
                                        <svg v-if="sortColumn === 'celular'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('user_types.name')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Tipo de Usuario</span>
                                        <svg v-if="sortColumn === 'user_types.name'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('es_activo')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Estatus</span>
                                        <svg v-if="sortColumn === 'es_activo'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'desc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('verificado')">
                                    <div class="flex items-center">
                                        <span class="mr-1">Verificado</span>
                                        <svg v-if="sortColumn === 'es_activo'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'desc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                                <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ user.id }}</th>
                                <td class="px-4 py-2">{{ user.name + ' ' + user.last_name }}</td>
                                <td class="px-4 py-2">{{ user.nickname }}</td>
                                <td class="px-4 py-2">{{ user.email }}</td>
                                <td class="px-4 py-2">{{ user.celular }}</td>
                                <td class="px-4 py-2">{{ user.user_type ? user.user_type.name : '-' }}</td>
                                <td class="px-4 py-2">
                                    <span>
                                        <span :style="{ color: user.es_activo ? 'green' : 'red' }">●</span>
                                        {{ user.es_activo ? 'ACTIVO' : 'INACTIVO' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span v-if="user.verificado" class="text-green-500 inline-flex items-center justify-center font-bold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 16.17l-4.17-4.17-1.41 1.41L9 19 21 7l-1.41-1.41z" stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </span>

                                    <span v-else class="text-red-500 inline-flex items-center justify-center font-bold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <path d="M18 6L6 18M6 6l12 12" />
                                        </svg>
                                    </span>
                                </td>
                                <td class="px-4 py-2 flex items-center justify-end relative">
                                    <button
                                        @click="toggleDropdown(user.id, $event)"
                                        :id="`dropdown-button-${user.id}`"
                                        class="inline-flex items-center p-0.5 text-sm font-medium text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                        type="button"
                                    >
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                        </svg>
                                    </button>
                                    <div
                                        v-show="dropdownVisible === user.id"
                                        :id="'dropdown-' + user.id"
                                        :class="{
                                            'absolute right-0 top-full mt-[-40%]': dropdownDirection === 'down',
                                            'absolute right-0 bottom-full mb-[-40%]': dropdownDirection === 'up'
                                        }"
                                        class="fixed z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow-xl dark:bg-gray-700 dark:divide-gray-600"
                                    >
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li class="hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white">
                                                <button
                                                    @click="abreModalEditarUsuario(user)"
                                                    class="block w-full py-2 px-4 text-left hover:bg-transparent dark:hover:bg-transparent hover:text-inherit dark:hover:text-inherit"
                                                >
                                                    Editar
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :data="pagination" />
            </div>
        </div>
    </section>
</template>

<script>
    export default {
        data() {
            return {
                dropdownVisible: null, // Tracks the active dropdown by user.id
                dropdownDirection: 'down' // Tracks dropdown direction ('up' or 'down')
            }
        },
        methods: {
            toggleDropdown(userId, event) {
                // Close any open dropdowns
                if (this.dropdownVisible === userId) {
                    this.dropdownVisible = null
                    return
                }

                // Determine space available
                const button = event.target.closest('button')
                const rect = button.getBoundingClientRect()
                const navPagination = document.getElementById('nav_pagination')
                const navRect = navPagination.getBoundingClientRect()

                const spaceBelow = navRect.top - rect.bottom // Espacio entre el final del botón y la parte superior de la paginación
                const dropdownHeight = 100 // Approximate height of the dropdown

                // Set direction based on available space
                this.dropdownDirection = spaceBelow < dropdownHeight ? 'up' : 'down'
                this.dropdownVisible = userId
            },
            closeDropdown() {
                this.dropdownVisible = null
            },
            handleClickOutside(event) {
                const dropdown = document.getElementById(`dropdown-${this.dropdownVisible}`)
                const button = document.getElementById(`dropdown-button-${this.dropdownVisible}`)
                if (this.dropdownVisible && dropdown && button && !dropdown.contains(event.target) && !button.contains(event.target)) {
                    this.closeDropdown()
                }
            }
        },
        mounted() {
            document.addEventListener('click', this.handleClickOutside)
        },
        beforeDestroy() {
            document.removeEventListener('click', this.handleClickOutside)
        }
    }
</script>
