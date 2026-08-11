<script setup>
    import { router, usePage } from '@inertiajs/vue3';
    import { ref, watch, onMounted, computed, nextTick, toRaw } from 'vue';
    import Swal from 'sweetalert2'; 
    import Pagination from '@/Components/Pagination.vue'
    import { debounce } from 'lodash'; // Necesitas instalar lodash o importarlo si está globalmente disponible

    // 1. Props e Inicialización
    const props = defineProps({
        requisitos: Object,
        pagination: Object,
        requisitosSelect: Array,
        tramitesSelect: Array,
        nomRequisitosQuery: { type: [Array, String], default: () => [] }, 
        nomTramitesQuery: { type: [Array, String], default: () => [] }, 
        obligatorioQuery: { type: Boolean, default: false },
        activoQuery: { type: Boolean, default: false },
        agruparTramites: { type: Boolean, default: true },
        tramites: Array,
        errors: Object, 
        flash: Object,
    });

    const showModalAsignacionTramite = ref(false);
    const showModalRequisitoTramite = ref(false);
    const isEditing = ref(false);
    const isEditingRequisito = ref(false);
    const isEditingRequisitoTramite = ref(false); 
    const isAssigningRequisitoTramite = ref(false); 
    const isLoading = ref(false);
    const isSavingModal = ref(false);
    const isLoadingModal = ref(false);
    const showModalRequisito = ref(false)
    const showModalAsignaciones = ref(false);
    const showModalAsignacionesRequisitosTramite = ref(false);
    const requisitoAsignaciones = ref([])
    const requisitosAsignaciones = ref([])
    const cantRequisitosSeleccionados = ref('')
    const cantTramitesAsignados = ref('')

    const requisitosActivos = computed(() => {
        // Verificamos que exista y sea un array para evitar errores de ejecución
        if (!props.requisitosSelect || !Array.isArray(props.requisitosSelect)) {
            return [];
        }

        // Filtramos comparando con 1 (o true dependiendo de cómo venga de MySQL)
        return props.requisitosSelect.filter(item => item.activo == 1);
    })

    // Inicialización de filtros reactivos con los valores de las props
    const nomRequisitosQuery = ref(props.nomRequisitosQuery);
    const nomTramitesQuery = ref(props.nomTramitesQuery);
    const obligatorioQuery = ref(
        props.obligatorioQuery || ''
    );
    const activoQuery = ref(
        props.activoQuery || ''
    );
    const agruparTramites = ref(props.agruparTramites ?? false);
    const tramites = ref(props.tramites);

    const selectNomRequisitosRef = ref(null);
    const selectNomTramitesRef = ref(null);

    function buildQueryParams(page = 1) {
        const urlParams = new URLSearchParams(window.location.search)

        urlParams.set('page', page)
        return Object.fromEntries(urlParams.entries())
    }

    function fetchRequisitos(preserveStateOption = true) 
    {
        const page = router.page.props.requisitos?.current_page ?? 1

        const paramsObject = buildQueryParams(page)

        const data = {
            nomRequisitosQuery: nomRequisitosQuery.value, // Array se serializa correctamente
            nomTramitesQuery: nomTramitesQuery.value,
            obligatorioQuery: obligatorioQuery.value,
            activoQuery: activoQuery.value,
            agruparTramites: agruparTramites.value,       // Boolean se serializa correctamente
            page: paramsObject.page                                   // Página
        };

        router.post('/requisitos', data, {
            preserveScroll: true,
            preserveState: preserveStateOption,
            replace: true,
            onStart: () => {
                isLoading.value = true;
                },
            onFinish: () => {
                const currentPage = router.page.props.requisitos.current_page;
                const lastPage = router.page.props.requisitos.last_page;

                // Si la página actual excede al número de páginas obtenidas
                if (currentPage > lastPage && lastPage > 0) 
                {
                    const currentFilters = router.page.props.filters ?? {}; 
                    
                    const newFilters = { 
                        ...currentFilters, // Usamos los filtros que fueron devueltos por la última carga
                        page: 1,
                        nomRequisitosQuery: nomRequisitosQuery.value,
                        nomTramitesQuery: nomTramitesQuery.value,
                        obligatorioQuery: obligatorioQuery.value,
                        activoQuery: activoQuery.value,
                        agruparTramites: agruparTramites.value,
                    };
                    
                    // Vuelve a cargar la página con la página #1, usando GET para enviar los filtros en la URL
                    router.post(
                        router.page.url.split('?')[0],
                        newFilters, // Envía todos los filtros y page: 1 como Query Parameters
                        { 
                            preserveState: true,
                            // Añadimos onStart/onFinish para esta recarga también
                            onStart: () => { isLoading.value = true; },
                            onFinish: () => { isLoading.value = false; }
                        }
                    );
                } else {
                    // Solo si no hubo redirección de corrección, quitamos la carga
                    isLoading.value = false;
                }
            }
        });
    }

    function switchRequisitos() 
    {
        const data = {
            nomRequisitosQuery: '',
            nomTramitesQuery: '',
            obligatorioQuery: '',
            activoQuery: '',
            agruparTramites: agruparTramites.value,
            page: 1 
        };

        router.cancel()

        router.post('/requisitos', data, {
            preserveScroll: true,
            preserveState: false,
            replace: true,
            onStart: () => {
                isLoading.value = true;
            },
            // Opcional: Si el componente de paginación no se actualiza,
            // puedes forzar la limpieza de errores o estados específicos aquí
            onSuccess: () => {
                // Lógica extra si es necesario
            }
        });
    }

    function resetearFiltros() 
    {
        nomRequisitosQuery.value = [];
        nomTramitesQuery.value = [];
        obligatorioQuery.value = '';
        activoQuery.value = '';   
    }

    function changeSwitchAgruparTramites() 
    {
        switchRequisitos(false) 
    }

    // Usamos debounce para nomRequisitosQuery para no disparar peticiones con cada tecla.
    const debouncedFetchRequisitos = debounce(() => {
        // Cuando los filtros cambian, reiniciamos a la página 1 y reemplazamos el historial
        fetchRequisitos(); 
    }, 300); 

    // Observar nomRequisitosQuery (select múltiple)
    watch(nomRequisitosQuery, () => {
        if (Array.isArray(nomRequisitosQuery.value)) {
            debouncedFetchRequisitos();
        }
    }, { deep: true });

    // Función para manejar el cambio en el selector (si es necesario)
    const handleSelectNomRequisitosChange = () => {      
        if (selectNomRequisitosRef.value) {
            selectNomRequisitosRef.value.toggleMenu();
        }
    };

    watch(nomTramitesQuery, () => {
        if (Array.isArray(nomTramitesQuery.value)) {
            debouncedFetchRequisitos();
        }
    }, { deep: true });

    const handleSelectNomTramitesChange = () => {      
        if (selectNomTramitesRef.value) {
            // En este punto, el watcher ya ha disparado la búsqueda, solo se cierra el menú.
            selectNomTramitesRef.value.toggleMenu();
        }
    };

    function handlePageChange(page) {
        router.post('/requisitos', {
                nomRequisitosQuery: nomRequisitosQuery.value,
                nomTramitesQuery: nomTramitesQuery.value,
                obligatorioQuery: obligatorioQuery.value,
                activoQuery: activoQuery.value,
                agruparTramites: agruparTramites.value,
                page: page
            }, {
                preserveState: true,
                replace: true,
                onFinish: () => {
                    isLoading.value = false;
                }
            });
    }

    onMounted(() => {
        if (typeof nomRequisitosQuery.value === 'string' && nomRequisitosQuery.value.trim() === '') {
            nomRequisitosQuery.value = [];
        }

        if (typeof nomTramitesQuery.value === 'string' && nomTramitesQuery.value.trim() === '') {
            nomTramitesQuery.value = [];
        }
    });

    const tramitesSeleccionadosParaDisplay = computed(() => {
        if (!nomTramitesQuery.value || nomTramitesQuery.value.length === 0) {
            return [];
        }
        
        return props.tramitesSelect.filter(tramite => 
            nomTramitesQuery.value.includes(tramite.id)
        );
    });

      function toggleObligatorioQuery(value) {
        if (value === obligatorioQuery.value) 
        {
          obligatorioQuery.value = '';
        }
        else 
        {
          obligatorioQuery.value = value;
        }

        debouncedFetchRequisitos();
      } 

        function toggleActivoQuery(value) {
            if (value === activoQuery.value) 
            {
            activoQuery.value = '';
            }
            else 
            {
            activoQuery.value = value;
            }

            debouncedFetchRequisitos();
        }

        const paginaActualAlAbrirModal = ref(1);

        const nuevoRequisito = async () => {
            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            isEditingRequisito.value = false;  
            showModalRequisito.value = true;
            nombreRequisito.value = ''
            nombreRequisitoEditable.value = true
            nombreRequisitoCorto.value = ''
            nombreRequisitoCortoEditable.value = true
            requisitoActivo.value = true

            nombreRequisitoOriginal.value = ''
            nombreRequisitoCortoOriginal.value = ''

            await nextTick();

            if (floatingNombreRequisitoRef.value && nombreRequisitoEditable.value) {
                floatingNombreRequisitoRef.value.focus();
            }
        }

        const editRequisito = async (requisito) => {
            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            isEditingRequisito.value = true;  
            showModalRequisito.value = true;
            idRequisitoEditar.value = requisito.id
            nombreRequisito.value = requisito.nombre
            nombreRequisitoEditable.value = !!requisito.editable
            nombreRequisitoCorto.value = requisito.nombre_corto
            nombreRequisitoCortoEditable.value = !!requisito.editable
            requisitoActivo.value = !!requisito.activo
            cantTramitesAsignados.value = requisito.tramites.length

            nombreRequisitoOriginal.value = requisito.nombre
            nombreRequisitoCortoOriginal.value = requisito.nombre_corto

            await nextTick();

            if (floatingNombreRequisitoRef.value && nombreRequisitoEditable.value) {
                floatingNombreRequisitoRef.value.focus();
            }
        }

        const nuevaAsignacion = async () => {
            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            isEditing.value = false;  
            showModalAsignacionTramite.value = true;
            idTramite.value = ''
            idTramiteOriginal.value = ''
            idRequisitoOriginal.value = ''
            idTramiteEditable.value = true
            requisitoObligatorio.value = true
            requisitoActivo.value = true

            idRequisito.value = ''
            requisitosNoAsignados.value = []

            await nextTick();

            if (floatingIdTramiteRef.value) {
                floatingIdTramiteRef.value.focus();
            }
        }


        const asignarRequisitoTramite = async (requisito) => {
            isLoading.value = true

            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            idTramite.value = ''

            // PENDIENTE
            // idRequisitoEditable.value = false
            // idTramiteEditable.value = false

            isAssigningRequisitoTramite.value = true;
            isEditingRequisitoTramite.value = false
            showModalRequisitoTramite.value = true;
            idRequisitoEditar.value = requisito.id
            idRequisitoOriginal.value = requisito.id
            requisitoActivo.value = true 
            requisitoObligatorio.value = true 

            isLoading.value = false

            await nextTick();

            if (floatingIdTramiteRef.value && floatingIdTramiteRef.value) {
                floatingIdTramiteRef.value.focus();
            }
        };

        const editRequisitoTramite = async (requisito) => {
            isLoadingModal.value = true

            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            isEditingRequisitoTramite.value = true;
            isAssigningRequisitoTramite.value = false; 
            showModalRequisitoTramite.value = true;
            idRequisitoEditar.value = requisito.id
            idRequisitoOriginal.value = requisito.id
            idRequisitoEditable.value = requisito.requisito_tramite_editable
            idTramiteEditable.value = requisito.requisito_tramite_editable
            idTramite.value = requisito.id_tramite
            idTramiteOriginal.value = requisito.id_tramite
            requisitoActivo.value = !!requisito.requisito_activo;   // !!1 -> true; !!0 -> false
            requisitoObligatorio.value = !!requisito.obligatorio; // !!1 -> true; !!0 -> false
            requisitoDocumentacionActivo.value = !!requisito.activo

            isLoadingModal.value = false

            await nextTick();

            if (floatingIdTramiteRef.value && floatingIdTramiteRef.value) {
                floatingIdTramiteRef.value.focus();
            }
        };

        const viewAsignaciones = async (requisito) => {
            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            showModalAsignaciones.value = true;
            idRequisitoEditar.value = requisito.id
            nombreRequisito.value = requisito.nombre
            requisitoAsignaciones.value = JSON.parse(JSON.stringify(requisito));  
            
            // if (requisitoAsignaciones.value.tramites) {
                
            //     requisitoAsignaciones.value.tramites = requisitoAsignaciones.value.tramites.filter(tramite => {
            //         return tramite.pivot?.activo === 1;
            //     });
            // }
        };

        
        const viewAsignacionesRequisitosTramite = async (requisito) => 
        {
            const currentPageNumber = page.props.requisitos?.current_page || 1; 
            paginaActualAlAbrirModal.value = currentPageNumber;

            requisitosAsignaciones.value = props.requisitos

            console.log('requisitosAsignaciones', requisitosAsignaciones.value)

            showModalAsignacionesRequisitosTramite.value = true;
            idTramite.value = requisito.id_tramite
            nombreTramite.value = requisito.tramite_nombre
            colorTramite.value = requisito.color

            const coincidencias = requisitosAsignaciones.value.filter(r => 
                r && r.id_tramite === idTramite.value
            );

            cantRequisitosSeleccionados.value = coincidencias.length; 
        };

        const idRequisitoEditar = ref(null)
        const idRequisitoEditable = ref(true)
        const idRequisitoOriginal = ref('')
        const idRequisito = ref('')
        const isFocusedIdRequisito = ref(false)
        const nombreRequisitoEditable = ref(true)
        const nombreRequisito = ref('')
        const nombreRequisitoOriginal = ref('')
        const nombreRequisitoBloqueado = ref(true)
        const floatingNombreRequisitoRef = ref(null)
        const nombreRequisitoCortoEditable = ref(true)
        const nombreRequisitoCorto = ref('')
        const nombreRequisitoCortoOriginal = ref('')
        // const nombreRequisitoCortoBloqueado = ref(true)
        const floatingNombreRequisitoCortoRef = ref(null)
        const idTramiteEditable = ref(true)
        const idTramite = ref('')
        const idTramiteOriginal = ref('')
        const nombreTramite = ref('')
        const colorTramite = ref('')
        const nombreTramiteEditable = ref(true)
        // const idTramiteBloqueado = ref(true)
        const floatingIdTramiteRef = ref(null) 
        const isFocusedIdTramite = ref(false)
        const requisitoDocumentacionActivo = ref(true)
        const requisitoObligatorio = ref(true)
        const requisitoActivo = ref(true)
        const requisitosNoAsignados = ref([])

        // const habilitarCaptura = (control) => {
        //     if (control == 'nombreRequisitoEditable') {
        //         nombreRequisitoEditable.value = !nombreRequisitoEditable.value
        //         setTimeout(() => {
        //             floatingNombreRequisitoRef.value?.focus()
        //         }, 50)
        //     }
        //     else if (control == 'nombreRequisitoCortoEditable') {
        //         nombreRequisitoCortoEditable.value = !nombreRequisitoCortoEditable.value
        //         setTimeout(() => {
        //             floatingNombreRequisitoCortoRef.value?.focus()
        //         }, 50)
        //     } 
        //     else if (control == 'nombreTramiteEditable') {
        //         nombreTramiteEditable.value = !nombreTramiteEditable.value
        //         setTimeout(() => {
        //             floatingIdTramiteRef.value?.focus()
        //         }, 50)
        //     } 
        // }

        const handleFocusIdTramite = (status) => {
            isFocusedIdTramite.value = status 
        }

        const handleFocusIdRequisito = (status) => {
            isFocusedIdRequisito.value = status 
        }

        const selectedTramite = computed(() => {
                return props.tramites.find(t => t.id === idTramite.value) || null
            })

        const confirmarActualizacionRequisito = () => {
            if (cantTramitesAsignados.value > 1)
            {
                Swal.fire({
                    title: 'Advertencia de Actualización',
                    html: 'Estás a punto de modificar un requisito que afectará a todos los trámites que lo utilizan. <br><br> ¿Deseas continuar con la actualización?',
                    icon: 'warning',
                    showCancelButton: true,
                    // confirmButtonColor: '#3085d6', // Puedes cambiar el color
                    // cancelButtonColor: '#d33', // Color rojo
                    confirmButtonText: 'Sí, ¡Actualizar!',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true, // Pone el botón principal a la izquierda
                    timer: undefined, // <--- Fuerza a que NO haya temporizador
                    timerProgressBar: false // Desactiva la barra si se quedó activada
                }).then((result) => {
                    // Si el usuario hace clic en el botón 'Sí, ¡Actualizar!'
                    if (result.isConfirmed) {
                        //CHECKPOINT: Hago el filtro por CONSTANCIA DE ZONIFICACIÓN y
                        //edito CÉDULA CATASTRAL y le cambio el estatus a INACTIVO
                        //Corregir porque HAY DOS TRÁMITES EN EL REQUISITO y SÓLO PONE INACTIVO UNO
                        // Llama a la función de actualización si el usuario confirma
                        actualizarRequisito()
                    } 
                })
            }
            else
            {
                actualizarRequisito()
            }
        }

    const page = usePage();

    const crearAsignacionRequisitoTramite = async () => {
        const formData = new FormData()

        formData.append('idTramite', idTramite.value)
        formData.append('idTramiteOriginal', idTramiteOriginal.value)
        formData.append('idRequisitoEditar', idRequisitoEditar.value)
        formData.append('idRequisitoOriginal', idRequisitoOriginal.value)
        formData.append('requisitoActivo', requisitoActivo.value ? '1' : '0')
        formData.append('requisitoObligatorio', requisitoObligatorio.value ? '1' : '0')
        formData.append('page', paginaActualAlAbrirModal.value)
        const lastPage = router.page.props.requisitos.last_page;
        formData.append('lastPage', lastPage)

        appendDataToFormData(formData, filterData.value);

        try {
            await router.post('/requisitos/store-assignment-requisito/' + idRequisitoEditar.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: (page) => {
                    if (page.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar el requisito',
                            html: page.props.flash.error, // Usa 'html' para mostrar saltos de línea (<br>)
                            confirmButtonText: 'Entendido'
                        });
                        isSavingModal.value = false;
                        return; 
                    }
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    showModalRequisitoTramite.value = false
                    isSavingModal.value = false
                },
                onStart: () => {
                    isSavingModal.value = true;
                },
                onFinish: () => {
                    isSavingModal.value = false;
                },
                onError: (errors) => {
                    isSavingModal.value = false
                    
                    // 1. Aplanar todos los mensajes de error usando reduce (la versión más compatible)
                    const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                    // 2. Construir la lista HTML con estilos inline
                    // Nota: Agregamos 'style="text-align: left; list-style-position: inside;"' al UL
                    let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                    flattenedErrors.forEach(message => {
                        // Estilo inline opcional en el LI (generalmente no necesario si el UL está bien estilado)
                        if (typeof message === 'string') {
                            errorListHtml += `<li>${message}</li>`;
                        }
                    });
                    errorListHtml += '</ul>'; 
                    
                    // 3. Mostrar el SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Validación!',
                        // Usamos la lista HTML construida con estilos
                        html: errorListHtml, 
                        confirmButtonText: 'Entendido'
                    });
                },
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }        
    } 
    
    //CHECKPOINT: Me quedé en el function para actualizar un requisito
    const iniciarActualizacionRequisito = () => {
        // Comprobación de si alguno de los campos clave ha sido modificado.
        const nombreCambio = nombreRequisito.value !== nombreRequisitoOriginal.value;
        const nombreCortoCambio = nombreRequisitoCorto.value !== nombreRequisitoCortoOriginal.value;

        // Si SOLO se modificaron campos NO CRÍTICOS (u otros), o NINGÚN campo CRÍTICO cambió:
        if (!nombreCambio && !nombreCortoCambio) {
            // Nota: Esto asume que no hay otros campos no críticos que requieran confirmación.
            confirmarActualizacionRequisito()

            return; // Detiene la ejecución aquí
        }
        // actualizarRequisito();

        // Si AL MENOS uno de los campos CRÍTICOS ha cambiado, lanzamos la confirmación.
        confirmarActualizacionRequisito()
    }

    const realizarAsignacionTramiteRequisitos = async () => {
        const formData = new FormData()

        formData.append('idTramite', idTramite.value)
        const rawRequisitos = requisitosAsignaciones.value.filter(r => 
                r && r.id_tramite === idTramite.value
            );
        const requisitosIds = rawRequisitos.map(requisito => requisito.id);
        const requisitosIdsJsonString = JSON.stringify(requisitosIds);

        formData.append('agruparTramites', agruparTramites.value)
        formData.append('requisitosAsignacionesTramite', requisitosIdsJsonString)
        formData.append('page', paginaActualAlAbrirModal.value)
        
        appendDataToFormData(formData, filterData.value);
        
        const lastPage = router.page.props.requisitos.last_page;
        formData.append('lastPage', lastPage)
        isSavingModal.value = true

        try {
            await router.post('/requisitos/update-assignment-requisitos-tramite/' + idTramite.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: (page) => {
                    if (page.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar el requisito',
                            html: page.props.flash.error, // Usa 'html' para mostrar saltos de línea (<br>)
                            confirmButtonText: 'Entendido'
                        });
                        isSavingModal.value = false;
                        return; 
                    }
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    showModalAsignacionesRequisitosTramite.value = false
                    isSavingModal.value = false
                },
                onError: (errors) => {
                    isSavingModal.value = false
                    
                    // 1. Aplanar todos los mensajes de error usando reduce (la versión más compatible)
                    const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                    // 2. Construir la lista HTML con estilos inline
                    // Nota: Agregamos 'style="text-align: left; list-style-position: inside;"' al UL
                    let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                    flattenedErrors.forEach(message => {
                        // Estilo inline opcional en el LI (generalmente no necesario si el UL está bien estilado)
                        if (typeof message === 'string') {
                            errorListHtml += `<li>${message}</li>`;
                        }
                    });
                    errorListHtml += '</ul>'; 
                    
                    // 3. Mostrar el SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Validación!',
                        html: errorListHtml, 
                        confirmButtonText: 'Entendido'
                    });
                },
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }        
    }

 
    const actualizarAsignacion = async () => {
        const formData = new FormData()

        isSavingModal.value = true
        formData.append('idRequisito', idRequisitoEditar.value)
        const rawTramites = toRaw(requisitoAsignaciones.value.tramites); 
        const tramitesIds = rawTramites.map(tramite => tramite.id);
        const tramitesIdsJsonString = JSON.stringify(tramitesIds);

        formData.append('requisitoAsignacionesTramites', tramitesIdsJsonString)
        formData.append('page', paginaActualAlAbrirModal.value)
        
        appendDataToFormData(formData, filterData.value);
        
        const lastPage = router.page.props.requisitos.last_page;
        formData.append('lastPage', lastPage)

        try {
            await router.post('/requisitos/update-assignment/' + idRequisitoEditar.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: (page) => {
                    if (page.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar el requisito',
                            html: page.props.flash.error, // Usa 'html' para mostrar saltos de línea (<br>)
                            confirmButtonText: 'Entendido'
                        });
                        isSavingModal.value = false;
                        return; 
                    }
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    showModalAsignaciones.value = false
                    isSavingModal.value = false
                },
                onError: (errors) => {
                    isSavingModal.value = false
                    
                    // 1. Aplanar todos los mensajes de error usando reduce (la versión más compatible)
                    const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                    // 2. Construir la lista HTML con estilos inline
                    // Nota: Agregamos 'style="text-align: left; list-style-position: inside;"' al UL
                    let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                    flattenedErrors.forEach(message => {
                        // Estilo inline opcional en el LI (generalmente no necesario si el UL está bien estilado)
                        if (typeof message === 'string') {
                            errorListHtml += `<li>${message}</li>`;
                        }
                    });
                    errorListHtml += '</ul>'; 
                    
                    // 3. Mostrar el SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Validación!',
                        html: errorListHtml, 
                        confirmButtonText: 'Entendido'
                    });
                },
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }        
    }

    function appendDataToFormData(formData, data) {
        if (!formData || !data) {
            return;
        }

        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                const value = data[key];
                
                const finalValue = (value === null || value === undefined) ? '' : value;
                
                if (Array.isArray(finalValue)) {
                    finalValue.forEach(item => formData.append(`${key}[]`, item));
                } else {
                    formData.append(key, finalValue);
                }
            }
        }
    }

    const filterData = computed(() => {
        return {
            nomRequisitosQuery: nomRequisitosQuery.value,
            nomTramitesQuery: nomTramitesQuery.value,
            obligatorioQuery: obligatorioQuery.value,
            activoQuery: activoQuery.value,
            agruparTramites: agruparTramites.value
        };
    });

    const crearRequisito = async () => {
        const formData = new FormData()

        isSavingModal.value = true       
        formData.append('nombreRequisito', nombreRequisito.value)
        formData.append('nombreRequisitoCorto', nombreRequisitoCorto.value)
        formData.append('requisitoActivo', requisitoActivo.value ? '1' : '0')
        formData.append('agruparTramites', agruparTramites.value)
        formData.append('page', paginaActualAlAbrirModal.value)
        
        appendDataToFormData(formData, filterData.value);

        const lastPage = router.page.props.requisitos.last_page;
        formData.append('lastPage', lastPage)

        try {
            await router.post('/requisitos/store', formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: (page) => {
                    if (page.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error inesperado al crear el requisito',
                            html: page.props.flash.error, // Usa 'html' para mostrar saltos de línea (<br>)
                            confirmButtonText: 'Entendido'
                        });
                        isSavingModal.value = false;
                        return; 
                    }
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    showModalRequisito.value = false
                    isSavingModal.value = false
                },
                onError: (errors) => {
                    isSavingModal.value = false
                    
                    // 1. Aplanar todos los mensajes de error usando reduce (la versión más compatible)
                    const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                    // 2. Construir la lista HTML con estilos inline
                    // Nota: Agregamos 'style="text-align: left; list-style-position: inside;"' al UL
                    let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                    flattenedErrors.forEach(message => {
                        // Estilo inline opcional en el LI (generalmente no necesario si el UL está bien estilado)
                        if (typeof message === 'string') {
                            errorListHtml += `<li>${message}</li>`;
                        }
                    });
                    errorListHtml += '</ul>'; 
                    
                    // 3. Mostrar el SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Validación!',
                        html: errorListHtml, 
                        confirmButtonText: 'Entendido'
                    });
                },
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }        
    }

    const actualizarRequisito = async () => {
        const formData = new FormData()

        isSavingModal.value = true
        formData.append('nombreRequisito', nombreRequisito.value)
        formData.append('nombreRequisitoCorto', nombreRequisitoCorto.value)
        formData.append('requisitoActivo', requisitoActivo.value ? '1' : '0')
        formData.append('agruparTramites', agruparTramites.value)
        formData.append('page', paginaActualAlAbrirModal.value)
        
        appendDataToFormData(formData, filterData.value);

        const lastPage = router.page.props.requisitos.last_page;
        formData.append('lastPage', lastPage)

        try {
            await router.post('/requisitos/update/' + idRequisitoEditar.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: (page) => {
                    if (page.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar el requisito',
                            html: page.props.flash.error, // Usa 'html' para mostrar saltos de línea (<br>)
                            confirmButtonText: 'Entendido'
                        });
                        isSavingModal.value = false;
                        return; 
                    }
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    showModalRequisito.value = false
                    isSavingModal.value = false
                },
                onFinish: () => {
                    isSavingModal.value = false
                },
                onError: (errors) => {
                    isSavingModal.value = false
                    
                    // 1. Aplanar todos los mensajes de error usando reduce (la versión más compatible)
                    const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                    // 2. Construir la lista HTML con estilos inline
                    // Nota: Agregamos 'style="text-align: left; list-style-position: inside;"' al UL
                    let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                    flattenedErrors.forEach(message => {
                        // Estilo inline opcional en el LI (generalmente no necesario si el UL está bien estilado)
                        if (typeof message === 'string') {
                            errorListHtml += `<li>${message}</li>`;
                        }
                    });
                    errorListHtml += '</ul>'; 
                    
                    // 3. Mostrar el SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Validación!',
                        html: errorListHtml, 
                        confirmButtonText: 'Entendido'
                    });
                },
            })
        } catch (err) {
            console.error('Error inesperado:', err)
        }        
    }

    const iniciarAsignacionRequisitoTramite = () => {
        crearAsignacionRequisitoTramite()
    }

   function toggleTramite(tramite) {
        if (!tramite || !tramite.id) {
            console.warn("Objeto trámite inválido recibido.");
            return;
        }

        const req = tramite.requisitos.find(r => r.id == idRequisitoEditar.value);

        const currentTramites = (requisitoAsignaciones.value.tramites || [])
        .filter(t => t.pivot?.activo === 1); // Filtra los trámites donde pivot.activo es 1
        const targetId = String(tramite.id); 
        const index = currentTramites.findIndex(t => t && String(t.id) === targetId);

        let newTramites;

        if (index > -1) {
            newTramites = currentTramites.filter(t => String(t.id) !== targetId);
        } 
        else 
        {
           const obligatorioPivot = req?.pivot || {
                obligatorio: 1, 
                editable: 1 
            };

            // Creamos un nuevo objeto que simula el resultado de Eloquent
            const newAssignment = {
                ...tramite, // Copiamos todas las propiedades del trámite del catálogo
                
                // Definimos o sobrescribimos la propiedad 'pivot' con el estado activo
                pivot: {
                    ...obligatorioPivot,
                    // Aquí podrías agregar otros campos de pivote (editable, obligatorio, etc.)
                    activo: 1, // 💡 Establecer activo en 1 (true) para la nueva asignación
                }
            };
            
            newTramites = [...currentTramites, newAssignment]; 
        }
        
        requisitoAsignaciones.value.tramites = newTramites;
    }

        //     const asignacion = props.requisitos.find(r => {
        //     // Verificamos que 'r' también sea válido dentro del find
        //     return r && 
        //         requisito.id && 
        //         r.id === requisito.id && 
        //         r.id_tramite === idTramite.value;
        // });

    function toggleRequisito(requisito) {
        const currentRequisitos = (requisitosAsignaciones.value || [])
        .filter(r => r?.requisito_activo === 1); // Filtra los requisitos donde pivot.activo es 1
        const targetId = String(requisito.id); 
        const index = currentRequisitos.findIndex(r => 
            r && 
            String(r.id) === targetId && 
            r.id_tramite === idTramite.value
        );

        let newRequisitos;

        if (index > -1) {
            newRequisitos = currentRequisitos.filter(r => String(r.id) !== targetId);
        } 
        else 
        {
            // Creamos un nuevo objeto que simula el resultado de Eloquent
            const newAssignment = {
                ...requisito, // Copiamos todas las propiedades del requisito del catálogo
                id_tramite: idTramite.value, // 💡 Establecer activo en 1 (true) para la nueva asignación
                requisito_activo: 1,
                obligatorio: 1,
            };            
            newRequisitos = [...currentRequisitos, newAssignment]; 
        }        
       requisitosAsignaciones.value = newRequisitos;

       const coincidencias = requisitosAsignaciones.value.filter(r => 
            r && r.id_tramite === idTramite.value
        );

        cantRequisitosSeleccionados.value = coincidencias.length;  
    }

    function hexToRgb(hex) {
        if (!hex) return '0, 0, 0'; 

        // Eliminar el # si existe
        const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
        hex = hex.replace(shorthandRegex, function(m, r, g, b) {
            return r + r + g + g + b + b;
        });

        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

        // Retorna el string en formato "R, G, B"
        return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '0, 0, 0';
    }

    // const isTramiteDisabled = (tramite) => {
    //         const asignacion = tramite.requisitos.find(r => r.id === idRequisitoEditar.value); 

    //         const isNotEditable = asignacion?.pivot?.editable === 0;

    //         // Retornamos true si el campo 'editable' es 0 (deshabilitado).
    //         return isNotEditable;
    // };

    const getTramites = (requisito) => {
        if (requisito.tramites) {
            return requisito.tramites;
        }
        return [];
    };

    const isTramiteAssignedAndActive = (tramite) => {
        const asignacion = requisitoAsignaciones.value.tramites?.find(t => t.id === tramite.id);
        
        return asignacion && asignacion.pivot;
    };

   const isRequisitoAssignedAndActive = (requisito) => {
        // 1. Verificación de seguridad: si requisito no existe, salimos de inmediato
        if (!requisito || typeof requisito !== 'object') {
            return false;
        }

        // 2. Verificación de props.requisitos
        if (!requisitosAsignaciones.value) return false;

        // 3. Buscamos la asignación con seguridad adicional
        const asignacion = requisitosAsignaciones.value.find(r => {
            // Verificamos que 'r' también sea válido dentro del find
            return r && 
                requisito.id && 
                r.id === requisito.id && 
                r.id_tramite === idTramite.value;
        });

        // Retornamos true si se encontró y cumple condiciones adicionales si las tienes
        return !!asignacion; 
    };

    const isTramiteRequisitoObligatorio  = (tramite) => {
        const asignacion = requisitoAsignaciones.value.tramites?.find(t => t.id === tramite.id);
        
        return asignacion && asignacion.pivot && asignacion.pivot.obligatorio === 1;
    };

    const isRequisitoObligatorio  = (requisito) => {
        const asignacion = requisitosAsignaciones.value.find(r => {
            return r && 
                requisito.id && 
                r.id === requisito.id && 
                r.id_tramite === idTramite.value;
        });

        return asignacion && asignacion.obligatorio === 1;
    };

    const isTramiteRequisitoNotEditable  = (tramite) => {
        const asignacion = requisitoAsignaciones.value.tramites?.find(t => t.id === tramite.id);
        
        return asignacion && asignacion.pivot && asignacion.pivot.editable === 0;
    };

    const isRequisitoNotEditable  = (requisito) => {
        const asignacion = requisitosAsignaciones.value.find(r => {
            return r && 
                requisito.id && 
                r.id === requisito.id && 
                r.id_tramite === idTramite.value;
        });
        
        return asignacion && asignacion.requisito_editable === 0;
    };

    const cargarRequisitosTramite = async (idTramite) => {
        try {
            isLoadingModal.value = false

            const response = await fetch(`/requisitos/get-requisitos-no-asignados-tramite/${encodeURIComponent(idTramite)}`, {
                // Agrega las comillas
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor')
            }

            const data = await response.json()
            requisitosNoAsignados.value = data.requisitosNoAsignados // Aquí se almacenan los datos en la variable puestos
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoadingModal.value = false
            // isLoadingModal.value = false
        }
    }  
    
    const iniciarCrearAsignacion = () => {
         const formData = new FormData()
        
        formData.append('idTramite', idTramite.value)
        formData.append('idRequisito', idRequisito.value)
        formData.append('requisitoObligatorio', requisitoObligatorio.value)
        formData.append('requisitoActivo', requisitoActivo.value)
        formData.append('agruparTramites', agruparTramites.value)
        formData.append('page', paginaActualAlAbrirModal.value)

        crearAsignacion(formData)
    }

    const crearAsignacion = async (formData) => {
        await router.post('/requisitos/store-assignment-tramite', formData, {
            onSuccess: (page) => {
                if (page.props.flash.error) {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error de Sistema!',
                        // Usamos la lista HTML construida con estilos
                        text: page.props.flash.error, 
                        confirmButtonText: 'Entendido'
                    });
                }
                else
                {
                    showModalAsignacionTramite.value = false
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })
                }
            },
            preserveScroll: true,
            preserveState: true,
            replace: true,

            onError: async (errors) => {
                const flattenedErrors = Object.values(errors).reduce((acc, val) => {
                        return acc.concat(val);
                    }, []); 

                let errorListHtml = '<ul style="text-align: left; margin: 0; padding-left: 1.5em; list-style-type: disc;">'; 
                    
                flattenedErrors.forEach(message => {
                    if (typeof message === 'string') {
                        errorListHtml += `<li>${message}</li>`;
                    }
                });
                errorListHtml += '</ul>'; 
                
                // 3. Mostrar el SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: '¡Error de Validación!',
                    // Usamos la lista HTML construida con estilos
                    html: errorListHtml, 
                    confirmButtonText: 'Entendido'
                });
            }
        })
    }
</script>

<template>
    <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%]">
            <div class="flex flex-col md:flex-row items-start md:items-center md:mb-5 md:mr-2 justify-between">
                <h1 class="text-2xl font-bold ml-2">Requisitos </h1>
                <button
                    v-if="agruparTramites"
                    ref="nuevaBtn"
                    @click="nuevoRequisito()"
                    type="button"
                    class="bg-color1-800 hover:bg-color1-700 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-10 py-2 focus:outline-none dark:focus:ring-color1-800 
                        mt-4 md:mt-0 
                        w-full md:w-auto"> + Nuevo Requisito
                </button>
                <button
                    v-else
                    ref="nuevaBtn"
                    @click="nuevaAsignacion()"
                    type="button"
                    class="bg-color1-800 hover:bg-color1-700 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-10 py-2 focus:outline-none dark:focus:ring-color1-800 
                        mt-4 md:mt-0 
                        w-full md:w-auto"> + Nueva Asignación
                </button>
            </div>
            <div v-if="!isLoading" class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 mb-2">
                <template v-if="!agruparTramites">
                    <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                        <el-select
                            ref="selectNomTramitesRef"
                            v-model="nomTramitesQuery"
                            class="custom-select-gray w-full"
                            collapse-tags
                            collapse-tags-tooltip
                            :max-collapse-tags="1"
                            filterable
                            multiple
                            placeholder="Trámite(s)"
                            value-key="value"
                            @change="handleSelectNomTramitesChange">
                            <template #prefix>
                                <div class="flex items-center ml-1">
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="w-5 h-5 mr-2 text-gray-800"
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round"> 
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M19 8.268a2 2 0 0 1 1 1.732v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h3" />
                                        <path d="M5 15.734a2 2 0 0 1 -1 -1.734v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-3" />
                                    </svg>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <span 
                                        v-for="tramite in tramitesSeleccionadosParaDisplay" 
                                        :key="tramite.id"
                                        :style="{ backgroundColor: tramite.color }" 
                                        class="w-2.5 h-2.5 rounded-full inline-block shrink-0">
                                    </span>
                                </div>
                            </template>
                            <el-option 
                                v-for="item in tramitesSelect" 
                                :key="item.id" 
                                :label="item.nombre"
                                :value="item.id"> 
                                <div class="flex items-center">
                                    <span 
                                    :style="{ backgroundColor: item.color }" 
                                    class="w-2.5 h-2.5 ml-1 mr-2 rounded-full inline-block shrink-0"> 
                                    </span>
                                    <span class="text-xs">{{ item.nombre }}</span>
                                </div> 
                            </el-option>
                        </el-select>
                    </div>
                    <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                        <el-select
                            ref="selectNomRequisitosRef"
                            v-model="nomRequisitosQuery"
                            class="custom-select-gray w-full"
                            collapse-tags
                            collapse-tags-tooltip
                            :max-collapse-tags="1"
                            filterable
                            multiple
                            placeholder="Requisito(s)"
                            value-key="value"
                            @change="handleSelectNomRequisitosChange">                        
                            <template #prefix>
                                <div class="flex items-center ml-1">
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="w-5 h-5 mr-2 text-gray-800"  
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round">                                    
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M11 19h-6a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v2.5" />
                                        <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                        <path d="M20.2 20.2l1.8 1.8" />
                                    </svg>
                                </div>
                            </template>
                            <el-option 
                                v-for="item in requisitosSelect" 
                                :key="item.id" 
                                :label="item.nombre"
                                :value="item.id">                            
                                <div class="flex items-center">
                                    <span class="w-4 h-5 mr-2">
                                    </span>
                                    <span class="text-xs">{{ item.nombre }}</span>
                                </div>                            
                            </el-option>
                        </el-select>
                    </div>
                </template>
                <template v-else>
                    <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                        <el-select
                            ref="selectNomRequisitosRef"
                            v-model="nomRequisitosQuery"
                            class="custom-select-gray w-full"
                            collapse-tags
                            collapse-tags-tooltip
                            :max-collapse-tags="1"
                            filterable
                            multiple
                            placeholder="Requisito(s)"
                            value-key="value"
                            @change="handleSelectNomRequisitosChange">                        
                            <template #prefix>
                                <div class="flex items-center ml-1">
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="w-5 h-5 mr-2 text-gray-800"  
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round">                                    
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M11 19h-6a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v2.5" />
                                        <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                        <path d="M20.2 20.2l1.8 1.8" />
                                    </svg>
                                </div>
                            </template>
                            <el-option 
                                v-for="item in requisitosSelect" 
                                :key="item.id" 
                                :label="item.nombre"
                                :value="item.id">                            
                                <div class="flex items-center">
                                    <span class="w-4 h-5 mr-2">
                                    </span>
                                    <span class="text-xs">{{ item.nombre }}</span>
                                </div>                            
                            </el-option>
                        </el-select>
                    </div>
                    <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                        <el-select
                            ref="selectNomTramitesRef"
                            v-model="nomTramitesQuery"
                            class="custom-select-gray w-full"
                            collapse-tags
                            collapse-tags-tooltip
                            :max-collapse-tags="1"
                            filterable
                            multiple
                            placeholder="Trámite(s)"
                            value-key="value"
                            @change="handleSelectNomTramitesChange">
                            <template #prefix>
                                <div class="flex items-center ml-1">
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="w-5 h-5 mr-2 text-gray-800"
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round"> 
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M19 8.268a2 2 0 0 1 1 1.732v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h3" />
                                        <path d="M5 15.734a2 2 0 0 1 -1 -1.734v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-3" />
                                    </svg>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <span 
                                        v-for="tramite in tramitesSeleccionadosParaDisplay" 
                                        :key="tramite.id"
                                        :style="{ backgroundColor: tramite.color }" 
                                        class="w-2.5 h-2.5 rounded-full inline-block shrink-0">
                                    </span>
                                </div>
                            </template>
                            <el-option 
                                v-for="item in tramitesSelect" 
                                :key="item.id" 
                                :label="item.nombre"
                                :value="item.id"> 
                                <div class="flex items-center">
                                    <span 
                                    :style="{ backgroundColor: item.color }" 
                                    class="w-2.5 h-2.5 ml-1 mr-2 rounded-full inline-block shrink-0"> 
                                    </span>
                                    <span class="text-xs">{{ item.nombre }}</span>
                                </div> 
                            </el-option>
                        </el-select>
                    </div>
                </template>
                <div class="flex items-center">
                    <label 
                        for="agruparTramites" 
                        class="ml-2 mr-3 block text-sm font-medium text-gray-900">
                        Agrupar Trámites
                    </label>
                    <el-switch
                    v-model="agruparTramites"
                    class="custom-switch"
                    size="large"
                    @change="changeSwitchAgruparTramites()"/>
                </div>
                <div v-if="!agruparTramites" class="flex items-center mb-3">
                    <div class="inline-flex w-full md:w-72">
                        <button
                        @click="toggleObligatorioQuery(false)"
                        :class="{
                            'bg-color1-800 text-white hover:bg-color1-600': obligatorioQuery === false,
                            'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': obligatorioQuery !== false
                        }"
                        class="w-1/2 px-3 py-2 text-[13px] transition duration-150 ease-in-out rounded-l-full whitespace-nowrap flex items-center justify-center space-x-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l14 0" />
                            </svg>
                            
                            <span>Opcionales</span>
                        </button>
                        <button
                            @click="toggleObligatorioQuery(true)"
                            :class="{
                                'bg-color1-800 text-white hover:bg-color1-600': obligatorioQuery === true,
                                'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': obligatorioQuery !== true,
                                'border-r-0': obligatorioQuery !== false
                            }"
                            class="w-1/2 px-3 py-2 text-[13px] transition duration-150 ease-in-out rounded-r-full whitespace-nowrap 
                                flex items-center justify-center space-x-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" 
                                class="w-4 h-4" 
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l5 5l10 -10" />
                            </svg>                            
                            <span>Obligatorios</span>
                        </button>
                    </div>                    
                </div>
                <div class="inline-flex w-full md:w-60" :class="{ 'ml-4': !agruparTramites, 'mb-3': agruparTramites }">                      
                    <button
                    @click="toggleActivoQuery(false)"
                    :class="{
                        'bg-color1-800 text-white hover:bg-color1-600': activoQuery === false,
                        'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': activoQuery !== false
                    }"
                    class="w-1/2 px-3 py-2 text-[13px] transition duration-150 ease-in-out rounded-l-full whitespace-nowrap flex items-center justify-center space-x-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                        class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v5"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m15 17 5 5"/><path d="m20 17-5 5"/>
                        </svg>                            
                        <span>Inactivos</span>
                    </button>
                    <button
                        @click="toggleActivoQuery(true)"
                        :class="{
                            'bg-color1-800 text-white hover:bg-color1-600': activoQuery === true,
                            'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': activoQuery !== true,
                            'border-r-0': activoQuery !== false
                        }"
                        class="w-1/2 px-3 py-2 text-[13px] transition duration-150 ease-in-out rounded-r-full whitespace-nowrap 
                            flex items-center justify-center space-x-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="w-4 h-4" 
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.5 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m14 20 2 2 4-4"/>
                        </svg>                            
                        <span>Activos</span>
                    </button>
                </div>
            </div>
            <div v-if="isLoading" class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 mb-2">
                <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                    <el-skeleton :throttle="0">
                        <template #template>
                            <el-skeleton-item variant="rect" class="w-full !h-10 !rounded-full bg-gray-200" />
                        </template>
                    </el-skeleton>
                </div>
                <div class="flex items-center w-full xs:w-1/2 md:w-[38%]">
                    <el-skeleton :throttle="0">
                        <template #template>
                            <el-skeleton-item variant="rect" class="w-full !h-10 !rounded-full bg-gray-200" />
                        </template>
                    </el-skeleton>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center ml-2 mr-3">
                        <el-skeleton :throttle="0">
                            <template #template>
                                <div class="flex items-center">
                                    <el-skeleton-item variant="text" class="!w-28 !mr-3" />
                                    <el-skeleton-item variant="rect" class="!w-12 !h-6 !rounded-full bg-gray-200" />
                                </div>
                            </template>
                        </el-skeleton>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                    <div class="inline-flex w-full md:w-72">
                        <el-skeleton :throttle="0" class="w-full flex">
                            <template #template>
                                <el-skeleton-item variant="rect" class="w-1/2 !h-10 !rounded-l-full bg-gray-200" />
                                <el-skeleton-item variant="rect" class="w-1/2 !h-10 !rounded-r-full ml-px bg-gray-200" />
                            </template>
                        </el-skeleton>
                    </div>

                    <div class="inline-flex w-full md:w-60">
                        <el-skeleton :throttle="0" class="w-full flex">
                            <template #template>
                                <el-skeleton-item variant="rect" class="w-1/2 !h-10 !rounded-l-full bg-gray-200" />
                                <el-skeleton-item variant="rect" class="w-1/2 !h-10 !rounded-r-full ml-px bg-gray-200" />
                            </template>
                        </el-skeleton>
                    </div>
                </div>
            </div>
            <div v-else class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 mb-2">
            </div>
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg"> 
                <div>                    
                    <table v-if="!isLoading" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 table-fixed mb-6">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th v-if="agruparTramites" scope="col" class="px-3 py-3 w-[5%]">
                                    <div class="flex items-center justify-center">
                                        <span class="mr-1 normal-case text-base">ID</span>
                                    </div>
                                </th>
                                <template v-if="agruparTramites">
                                    <th scope="col" class="px-4 py-3 w-[35%]">
                                        <div class="flex items-center">
                                            <span class="mr-1 normal-case text-base">Requisito</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3 w-[15%]">
                                        <div class="flex items-center">
                                            <span class="mr-1 normal-case text-base">Nombre Corto</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3 w-[20%]">
                                        <div class="flex items-end">
                                            <span v-if="agruparTramites" class="normal-case text-base mr-1 p-0 m-0 leading-none whitespace-nowrap inline-flex items-center">
                                                Trámite(s)
                                            </span>
                                            <span v-else class="normal-case text-base mr-1 p-0 m-0 leading-none whitespace-nowrap inline-flex items-center">
                                                Trámite
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3 w-[6%]">
                                        <div class="flex items-center">
                                            <span class="mr-1 normal-case text-base">Estado</span>
                                        </div>
                                    </th>
                                </template>
                                <template v-else>
                                    <th scope="col" class="px-4 py-3 w-[20%]">
                                        <div class="flex items-end">
                                            <span v-if="agruparTramites" class="normal-case text-base mr-1 p-0 m-0 leading-none whitespace-nowrap inline-flex items-center">
                                                Trámite(s)
                                            </span>
                                            <span v-else class="normal-case text-base mr-1 p-0 m-0 leading-none whitespace-nowrap inline-flex items-center">
                                                Trámite
                                            </span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3 w-[40%]">
                                        <div class="flex items-center">
                                            <span class="mr-1 normal-case text-base">Requisito</span>
                                        </div>
                                    </th>
                                </template>
                                <th v-if="!agruparTramites" scope="col" class="px-4 py-3 w-[15%] text-center">
                                    <div class="flex items-center justify-center">
                                        <span class="mr-1 normal-case text-base">Obligatorio</span>
                                        <span class="mr-1 normal-case text-base">/ Estado</span>
                                    </div>
                                </th>
                                <th scope="col" class="px-4 py-3 w-[10%] text-center">
                                </th>
                            </tr>
                        </thead>                        
                        <tbody>
                            <tr v-if="agruparTramites" v-for="req in requisitos" :key="req.id" class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">                                    
                                <td class="px-3 py-1 font-normal text-gray-700 text-center">
                                    {{ req.id }}  
                                </td>
                                <td class="px-4 py-1 text-gray-700 dark:text-white">
                                    {{ req.nombre }} 
                                </td>
                                <td class="px-4 py-1 text-gray-700 dark:text-white">
                                    {{ req.nombre_corto }} 
                                </td>                                  
                                <td class="px-4 py-1 font-normal text-gray-700 dark:text-white">   
                                    <div v-if="getTramites(req).length > 0" class="flex flex-wrap gap-1">
                                        <div 
                                            v-for="tramite in getTramites(req)"
                                            :key="tramite.id"
                                            class="relative inline-block group">
                                            <div
                                            :style="{ backgroundColor: tramite.color }" 
                                            class="w-3 h-3 rounded-full shadow-xl transition z-10 duration-150 ease-in-out 
                                                scale-100 hover:scale-125"> 
                                            </div>
                                            <div
                                                class="absolute 
                                                    whitespace-nowrap 
                                                    z-30 
                                                    top-full mt-2
                                                    left-1/2  
                                                    opacity-0 
                                                    invisible -translate-y-1 
                                                    group-hover:opacity-95 
                                                    group-hover:translate-y-0 
                                                    group-hover:visible transition 
                                                    duration-300 
                                                    transform -translate-x-1/2
                                                    bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                {{ tramite.nombre }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-color1-500">
                                        Sin trámites asignados
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-white flex justify-center items-center">
                                    <div class="relative inline-block group">
                                        <span v-if="req.activo" class="text-color2-700 hover:text-color2-600 cursor-help" aria-label="Requisito Activo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M10.5 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m14 20 2 2 4-4"/>
                                            </svg>
                                        </span>
                                        <span v-else class="text-color1-500 hover:text-color1-700 cursor-help" aria-label="Requisito Inactivo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v5"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m15 17 5 5"/><path d="m20 17-5 5"/>
                                            </svg>
                                        </span>
                                        <div class="absolute whitespace-nowrap z-30 top-full mt-2 left-1/2 opacity-0 invisible -translate-y-1 group-hover:opacity-95 group-hover:translate-y-0 group-hover:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                            {{ req.activo ? 'Requisito ACTIVO' : 'Requisito INACTIVO' }}
                                        </div>
                                    </div>
                                </td> 
                                <td class="px-4 py-1 w-[10%] text-center"> 
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="relative inline-block group/edit">
                                            <button
                                                @click="editRequisito(req)"
                                                class="text-gray-500 hover:text-gray-800 focus:outline-none transition duration-150 ease-in-out"
                                                aria-label="Editar Requisito">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" />
                                                </svg>
                                            </button>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Editar Requisito
                                            </div>
                                        </div>                                  
                                        <div class="relative inline-block group/edit">
                                            <button
                                                @click="viewAsignaciones(req)"
                                                class="text-gray-500 hover:text-gray-800 focus:outline-none transition duration-150 ease-in-out"
                                                aria-label="Asignaciones">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 11V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h7"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m10 18 3-3-3-3"/>
                                                </svg>
                                            </button>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Asignación de Trámites <br> a Requisito
                                            </div>
                                        </div>                                  
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!agruparTramites" v-for="item in requisitos" :key="`${item.id}-${item.id_tramite}`" class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                                <td v-if="item.tramite_nombre" class="px-4 py-1 text-gray-700 dark:text-white">
                                    <div class="flex items-center space-x-2"> 
                                        <div 
                                            :style="{ backgroundColor: item.color }" 
                                            class="w-3 h-3 rounded-full shadow-xl transition z-10 duration-150 ease-in-out hover:scale-110 flex-shrink-0">
                                        </div>
                                        <span class="text-xs">
                                            {{ item.tramite_nombre }}
                                        </span>
                                    </div>
                                </td>
                                <td v-else class="px-4 py-1 text-xs text-color1-500">
                                    Sin trámite asignado
                                </td>
                                <td class="px-4 py-1 text-gray-700 dark:text-white">
                                    {{ item.requisito_nombre }}
                                </td>
                                <td class="px-4 py-1 text-center">
                                    <div v-if="item.tramite_nombre" class="flex items-center justify-center gap-3">
                                        <div class="relative inline-block group" v-if="!agruparTramites">
                                            <span v-if="item.obligatorio" class="text-color2-400 hover:text-color2-600 cursor-help" aria-label="Requisito Obligatorio">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M5 12l5 5l10 -10" />
                                                </svg>
                                            </span>
                                            <span v-else class="text-gray-400 hover:text-gray-600 cursor-help" aria-label="Requisito No Obligatorio">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M5 12l14 0" />
                                                </svg>
                                            </span>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-2 left-1/2 opacity-0 invisible -translate-y-1 group-hover:opacity-95 group-hover:translate-y-0 group-hover:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                {{ item.obligatorio ? 'Requisito OBLIGATORIO' : 'Requisito NO OBLIGATORIO' }}
                                            </div>
                                        </div>

                                        <div class="relative inline-block group" v-if="!agruparTramites">
                                            <span v-if="item.requisito_activo" class="text-color2-700 hover:text-color2-600 cursor-help" aria-label="Requisito Activo">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
                                                   <path d="M10.5 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v6"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m14 20 2 2 4-4"/>
                                                </svg>
                                            </span>
                                            <span v-else class="text-color1-500 hover:text-color1-700 cursor-help" aria-label="Requisito Inactivo">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v5"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m15 17 5 5"/><path d="m20 17-5 5"/>
                                                </svg>
                                            </span>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-2 left-1/2 opacity-0 invisible -translate-y-1 group-hover:opacity-95 group-hover:translate-y-0 group-hover:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                {{ item.requisito_activo ? 'Requisito ACTIVO' : 'Requisito INACTIVO' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else  class="px-4 py-1 flex items-center justify-center text-color1-500">
                                       <b>  N/A </b>
                                    </div>
                                </td>
                                <td class="px-4 py-1 w-[10%] text-center">
                                    <div v-if="item.tramite_nombre" class="flex items-center justify-center gap-2">
                                        <div class="relative inline-block group/edit">
                                            <button
                                                @click="editRequisitoTramite(item)"
                                                class="text-gray-500 hover:text-gray-800 focus:outline-none transition duration-150 ease-in-out"
                                                aria-label="Editar Requisito">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" />
                                                </svg>
                                            </button>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Editar Asignación <br> de Requisito
                                            </div>
                                        </div>
                                        <div v-if="item.activo" class="relative inline-block group/edit">
                                            <button
                                                @click="viewAsignacionesRequisitosTramite(item)"
                                                class="text-gray-500 hover:text-gray-800 focus:outline-none transition duration-150 ease-in-out"
                                                aria-label="Asignaciones">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 11V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h7"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m10 18 3-3-3-3"/>
                                                </svg>
                                            </button>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Asignación de Requisitos <br> a Trámite
                                            </div>
                                        </div>                                      
                                    </div>
                                    <div v-else="item.tramite_nombre" class="flex items-center justify-center gap-2">
                                        <div class="relative inline-block group/edit">
                                            <button
                                                @click="asignarRequisitoTramite(item)"
                                                class="text-gray-500 hover:text-gray-800 focus:outline-none transition duration-150 ease-in-out"
                                                aria-label="Editar Requisito">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M9 15h6"/><path d="M12 18v-6"/>
                                                </svg>
                                            </button>
                                            <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Asignar Requisito a Trámite
                                            </div>
                                        </div>
                                        <!-- <div v-else  class="px-4 py-1 flex items-center justify-center text-color1-500 relative inline-block group/edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ban-icon lucide-ban"><path d="M4.929 4.929 19.07 19.071"/><circle cx="12" cy="12" r="10"/></svg>
                                             <div class="absolute whitespace-nowrap z-30 top-full mt-1 left-1/2 opacity-0 invisible -translate-y-0 group-hover/edit:opacity-95 group-hover/edit:translate-y-0 group-hover/edit:visible transition duration-300 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">
                                                Requisito NO Activo
                                            </div>
                                        </div> -->
                                    </div>                                    
                                </td>
                            </tr> 
                        </tbody>
                    </table>
                </div>
                <div v-if="requisitos.length === 0 && !isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No hay requisitos registrados
                </div>
                <Pagination v-if="!isLoading" :data="pagination" @page-changed="handlePageChange" />
            </div>
            <div v-if="isLoading" class="bg-white mt-8 dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="w-full h-[550px] bg-gray-200 dark:bg-gray-700 opacity-40">
                    <div class="w-full h-10 bg-gray-300 dark:bg-gray-600 border-b dark:border-gray-500"></div>
                </div>
                
                <div class="p-4 border-t dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-between items-center">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                    <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                </div>
            </div>      
        </div>
    </section>
    <div v-if="showModalAsignaciones" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl relative">
            <button 
                @click="showModalAsignaciones = false" 
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-900 transition duration-300 transform hover:rotate-180 focus:outline-none"
                aria-label="Cerrar modal">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <h3 class="text-xl font-bold mb-4">Asignación de Trámites a Requisito</h3>
            <div class="relative z-0 mb-4 w-full md:w-[100%]">
                <label
                    class="text-lg text-gray-800 block mb-0">
                    Requisito
                </label>
                <label
                    class="inline-flex items-center pl-2 pr-3 py-1 text-sm font-medium text-white bg-color1-700 rounded-r-full dark:text-white">
                    {{ nombreRequisito }}
                </label>
            </div>
            <div class="text-lg text-gray-800 -z-10">
                Selecciona los Trámites Asignados
            </div>
            <div class="text-xs text-gray-500 italic mb-2 -z-10">
                Marca los trámites de esta lista que deben estar asignados para cumplir con este requisito.
            </div>
            <div v-if="tramites && tramites.length > 0" class="grid grid-cols-4 gap-4 pt-2 pb-4 w-full max-h-[60vh] overflow-y-auto"> 
                <div 
                    v-for="tramite in tramites" 
                    :key="tramite.id"
                    @click="toggleTramite(tramite)"
                    class="hover:bg-[var(--hover-bg)] active:scale-[0.98] active:ring-2 active:ring-offset-1 active:ring-[var(--hover-border)] relative inline-block cursor-pointer group h-14 flex items-center pl-4 rounded-md shadow-sm border transition duration-150"
                    :class="{ 
                        // 🟢 ESTADO ASIGNADO
                        'bg-white border-[var(--hover-border)] ring-[var(--hover-border)]': isTramiteAssignedAndActive(tramite),
                        // 🟡 ESTADO NO ASIGNADO
                        'bg-white border-gray-300': !isTramiteAssignedAndActive(tramite),
                        
                        // 🟡 LÓGICA DE HOVER DINÁMICO
                        'hover:border-[var(--hover-border)] hover:bg-[var(--hover-bg)]': !isTramiteAssignedAndActive(tramite),
                   
                        'border-l-4 border-l-[var(--hover-border)]': isTramiteRequisitoObligatorio(tramite),

                        // Esto define el ancho y color.
                        'border-r-2 border-r-[var(--hover-border)]': isTramiteRequisitoNotEditable(tramite),

                        // Usaremos la clase de corchetes de Tailwind JIT o estilo in-line como fallback.
                        '[border-right-style:dotted]': isTramiteRequisitoNotEditable(tramite),
                        
                        // 3. Compensación del padding (si es necesario)
                        'pr-[calc(1rem + 2px)]': isTramiteRequisitoNotEditable(tramite)
                   }"
                    :style="1==1 ? { 
                        '--tramite-color': tramite.color,
                        '--hover-bg': `rgba(${hexToRgb(tramite.color)}, 0.1)`,
                        '--hover-border': `rgba(${hexToRgb(tramite.color)}, 0.99)`,
                     } : {}">
                            
                    <button
                    type="button" 
                    @click.stop="toggleTramite(tramite)"                
                    :style="isTramiteAssignedAndActive(tramite)
                        ? { 
                            // Estilos para el estado ASIGNADO (se mantienen igual)
                            'background-color': tramite.color + ' !important',
                            '--tw-ring-color': tramite.color + ' !important', 
                            '--tw-ring-offset-color': tramite.color + ' !important',
                            'border-color': tramite.color + ' !important' 
                        } 
                        : {}" 
                    
                    :class="[
                        // 1. CLASES ESTÁTICAS BASE (simpre aplicadas)
                        'absolute top-1 right-1 h-4 w-4 rounded border',
                        'flex items-center justify-center transition-colors',
                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                        'cursor-pointer',
                        
                        // 2. CLASES CONDICIONALES EN FORMA DE OBJETO
                        {
                            // ESTADO ASIGNADO
                            'bg-opacity-100 border-transparent': isTramiteAssignedAndActive(tramite),
                            // ESTADO NO ASIGNADO
                            'border-gray-400 bg-white': !isTramiteAssignedAndActive(tramite)
                        }
                    ]">
                         <svg v-if="isTramiteAssignedAndActive(tramite)"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" 
                            stroke-width="3.5"  
                            stroke="white"     
                            fill="none"        
                            class="w-3 h-3 relative z-10">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </button>
                    <label 
                        :for="'tramite-' + tramite.id" 
                        class="block pr-8 flex-grow cursor-pointer" >
                        <h4 :class="{ 'font-semibold': isTramiteAssignedAndActive(tramite),
                         }"
                        class="cursor-pointer text-gray-900 text-xs leading-tight line-clamp-2"> {{ tramite.nombre }}
                        </h4> 
                    </label>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="showModalAsignaciones = false;" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="actualizarAsignacion"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Actualizar Asignación
                </button>
                <!-- <button v-else
                    type="button"
                    @click="iniciarGuardarRequisito"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Guardar Requisito
                </button> -->
            </div>
            <div
                v-if="isSavingModal"
                class="absolute inset-0 flex items-center justify-center rounded-lg"
                style="background-color: rgba(255, 255, 255, 0.85); z-index: 50;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path
                        d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                    />
                </svg>
                <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
            </div>
        </div>
    </div> 
    <div v-if="showModalRequisitoTramite" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg relative">
            <h3 v-if="isAssigningRequisitoTramite" class="text-xl font-bold mb-8">Asignar Requisito a Trámite</h3>
            <h3 v-if="isEditingRequisitoTramite" class="text-xl font-bold mb-8">Editar Asignación de Requisito</h3>
            <div class="relative w-full mb-8">
                <el-select
                    ref="floatingIdTramiteRef"
                    v-model="idTramite"
                    @focus="handleFocusIdTramite(true)"
                    @blur="handleFocusIdTramite(false)"
                    placeholder=""
                    class="w-full el-select-dinamyc"
                    :disabled="!idTramiteEditable">                            
                        <template #prefix>
                        <span 
                            v-if="selectedTramite"
                            :style="{ backgroundColor: selectedTramite.color }" 
                            class="w-2.5 h-2.5 rounded-full inline-block shrink-0 mr-2">
                        </span>
                    </template>                            
                    <el-option
                        v-for="(tramite, index) in tramites"
                        :key="index"
                        :label="tramite.nombre"
                        :value="tramite.id"
                        :item-data="tramite"> 
                        <div class="flex items-center">
                            <span 
                                :style="{ backgroundColor: tramite.color }" 
                                class="w-2.5 h-2.5 rounded-full inline-block shrink-0 mr-2">
                            </span>
                            <span>{{ tramite.nombre }}</span>
                        </div>
                    </el-option>                            
                </el-select>
                <label
                    style="z-index: 10"
                    class="absolute text-sm duration-300 transform origin-[0] left-0 right-0 pointer-events-none"
                    :class="{
                        // POSICIÓN ARRIBA: Cuando tiene valor seleccionado O tiene foco.
                        '-translate-y-6': idTramite || isFocusedIdTramite,
                        // POSICIÓN ABAJO (INICIAL): En el estado de Placeholder.
                        'translate-y-0': !idTramite && !isFocusedIdTramite, 
                        // 1. Grande (scale-100): Solo cuando tienes valor seleccionado Y pierdes el foco. (TU REQUERIMIENTO ESPECÍFICO)
                        'scale-100': idTramite && !isFocusedIdTramite,
                        // 2. Pequeño (scale-90): En el estado inicial, con foco, o sin valor.
                        'scale-90': !idTramite || isFocusedIdTramite, // El opuesto de la regla 1                                
                        // COLOR DE FOCO: Solo si está ENFOCADO.
                        'text-color1 dark:text-color1': isFocusedIdTramite,
                        // COLOR POR DEFECTO: Si NO está enfocado.
                        'text-gray-500 dark:text-gray-400': !isFocusedIdTramite
                    }"
                    :style="{
                        top: '10px' 
                    }">
                    Trámite
                </label>
            </div>
            <div class="relative w-full">
                <el-select
                    ref="floatingIdRequisitoRef"
                    v-model="idRequisitoEditar"
                    @focus="handleFocusIdRequisito(true)"
                    @blur="handleFocusIdRequisito(false)"
                    placeholder=""
                    class="w-full el-select-dinamyc"
                    :disabled="!idRequisitoEditable">                        
                    <el-option
                        v-for="(requisito, index) in requisitosSelect"
                        :key="index"
                        :label="requisito.nombre"
                        :value="requisito.id"
                        :item-data="requisito"> 
                        <div class="flex items-center">
                            <span>{{ requisito.nombre }}</span>
                        </div>
                    </el-option>                            
                </el-select>
                <label
                    style="z-index: 10"
                    class="absolute text-sm duration-300 transform origin-[0] left-0 right-0 pointer-events-none"
                    :class="{
                            // 🌟 CORRECCIÓN 1: Usar idRequisitoEditar
                            '-translate-y-6': idRequisitoEditar || isFocusedIdRequisito,
                            // 🌟 CORRECCIÓN 2: Usar idRequisitoEditar
                            'translate-y-0': !idRequisitoEditar && !isFocusedIdRequisito, 
                            // 🌟 CORRECCIÓN 3 (Escala 100): Usar idRequisitoEditar
                            'scale-100': idRequisitoEditar && !isFocusedIdRequisito,
                            // 🌟 CORRECCIÓN 4 (Escala 90): Usar idRequisitoEditar
                            'scale-90': !idRequisitoEditar || isFocusedIdRequisito, 
                            // ... (Clases de color que ya estaban correctas)
                            'text-color1 dark:text-color1': isFocusedIdRequisito,
                            'text-gray-500 dark:text-gray-400': !isFocusedIdRequisito
                        }"
                    :style="{
                        top: '10px' 
                    }">
                    Requisito
                </label>
            </div>
            <div class="flex flex-wrap items-center mb-0 gap-8">
                <div class="relative mb-8 pt-4 w-full md:w-[53%]">
                    <el-switch
                        v-model="requisitoObligatorio"
                        active-text="Obligatorio"
                        inactive-text="Opcional"
                        size="large"/>
                </div>
                <div v-if="requisitoDocumentacionActivo" class="relative mb-4 w-full md:w-[40%]">
                    <el-switch
                        v-model="requisitoActivo"
                        active-text="Activo"
                        inactive-text="Inactivo"
                        size="large"/>
                </div>
                <!-- <div v-else class="text-xs relative mb-4 w-full md:w-[40%]">
                    * El requisito está INACTIVO.
                </div>           -->
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="showModalRequisitoTramite = false;" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">
                    Cancelar
                </button>
                <button v-if="isEditingRequisitoTramite"
                    type="button"
                    @click="iniciarAsignacionRequisitoTramite"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Actualizar Asignación
                </button>
                <button v-if="isAssigningRequisitoTramite"
                    type="button"
                    @click="iniciarAsignacionRequisitoTramite"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Guardar Asignación
                </button>
            </div>
            <div
                v-if="isSavingModal"
                class="absolute inset-0 flex items-center justify-center rounded-lg"
                style="background-color: rgba(255, 255, 255, 0.85); z-index: 50;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path
                        d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                    />
                </svg>
                <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
            </div>
            <div v-if="isLoadingModal"
                class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-lg backdrop-blur-[2px] bg-white/70 transition-all duration-300">
                <div class="relative flex items-center justify-center">
                    <div class="h-12 w-12 rounded-full border-8 border-color1-100"></div>
                    <div class="absolute h-12 w-12 animate-spin rounded-full border-8 border-color1-300 border-t-transparent"></div>
                </div>                
                <span class="mt-4 text-sm font-medium text-gray-600 animate-pulse">
                    Cargando...
                </span>
            </div>
        </div>
    </div>
    <div v-if="showModalAsignacionTramite" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg relative">
            <h3 class="text-xl font-bold mb-8">{{ isEditing ? 'Editar Asignación Trámite - Requisito' : 'Nueva Asignación de Requisito' }}</h3>
            <div class="relative z-0 mb-8 group peer w-full md:w-[100%]">
                <template v-if="idTramiteEditable">
                    <div class="relative w-full">
                        <el-select
                            ref="floatingIdTramiteRef"
                            v-model="idTramite"
                            @focus="handleFocusIdTramite(true)"
                            @blur="handleFocusIdTramite(false)"
                            placeholder=""
                            @change="cargarRequisitosTramite"
                            class="w-full el-select-dinamyc">                            
                              <template #prefix>
                                <span 
                                    v-if="selectedTramite"
                                    :style="{ backgroundColor: selectedTramite.color }" 
                                    class="w-2.5 h-2.5 rounded-full inline-block shrink-0 mr-2">
                                </span>
                            </template>                            
                            <el-option
                                v-for="(tramite, index) in tramites"
                                :key="index"
                                :label="tramite.nombre"
                                :value="tramite.id"
                                :item-data="tramite"> 
                                <div class="flex items-center">
                                    <span 
                                        :style="{ backgroundColor: tramite.color }" 
                                        class="w-2.5 h-2.5 rounded-full inline-block shrink-0 mr-2">
                                    </span>
                                    <span>{{ tramite.nombre }}</span>
                                </div>
                            </el-option>                            
                        </el-select>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm duration-300 transform origin-[0] left-0 right-0 pointer-events-none"
                            :class="{
                                // POSICIÓN ARRIBA: Cuando tiene valor seleccionado O tiene foco.
                                '-translate-y-6': idTramite || isFocusedIdTramite,
                                // POSICIÓN ABAJO (INICIAL): En el estado de Placeholder.
                                'translate-y-0': !idTramite && !isFocusedIdTramite, 
                                // 1. Grande (scale-100): Solo cuando tienes valor seleccionado Y pierdes el foco. (TU REQUERIMIENTO ESPECÍFICO)
                                'scale-100': idTramite && !isFocusedIdTramite,
                                // 2. Pequeño (scale-90): En el estado inicial, con foco, o sin valor.
                                'scale-90': !idTramite || isFocusedIdTramite, // El opuesto de la regla 1                                
                                // COLOR DE FOCO: Solo si está ENFOCADO.
                                'text-color1 dark:text-color1': isFocusedIdTramite,
                                // COLOR POR DEFECTO: Si NO está enfocado.
                                'text-gray-500 dark:text-gray-400': !isFocusedIdTramite
                            }"
                            :style="{
                                top: '10px' 
                            }">
                            Trámite
                        </label>
                    </div>
                </template>
                <!-- <template v-else>
                    <div class="flex items-baseline">
                        <span
                            v-if="selectedTramite"
                            :style="{ backgroundColor: selectedTramite.color }"
                            class="w-2.5 h-2.5 rounded-full inline-block shrink-0 mr-2"
                            :class="[
                                // Clase para ajustar el círculo un poco más abajo si el input tiene la clase 'mt-8'
                                {'relative top-0': !idTramiteEditable}
                            ]" 
                            title="Color del Trámite">
                        </span>

                        <input
                            v-model="nombreTramite"
                            type="text"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer flex-grow"
                            :class="[
                                {
                                    // Clases específicas cuando es editable
                                    'pb-1 py-2.5 px-0': idTramiteEditable,
                                    // Clases específicas cuando NO es editable (mantener el pl-0 y ml-0 para que el texto esté pegado al círculo)
                                    'bg-transparent pl-0 ml-0 mt-0': !idTramiteEditable, 
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idTramiteEditable
                                }
                            ]"
                            placeholder=""
                            :disabled="!idTramiteEditable"
                            required/>
                    </div>
                    <label
                        style="z-index: 10"
                        class="absolute text-sm duration-300 transform origin-[0] left-0 right-0 pointer-events-none"                        
                        :class="{
                            // POSICIÓN ARRIBA: Cuando tiene valor seleccionado O tiene foco.
                            '-translate-y-6': idTramite || isFocusedIdTramite,
                            // POSICIÓN ABAJO (INICIAL): En el estado de Placeholder.
                            'translate-y-0': !idTramite && !isFocusedIdTramite, 
                            // 1. Grande (scale-100): Solo cuando tienes valor seleccionado Y pierdes el foco. (TU REQUERIMIENTO ESPECÍFICO)
                            'scale-100': idTramite && !isFocusedIdTramite,
                            // 2. Pequeño (scale-90): En el estado inicial, con foco, o sin valor.
                            'scale-90': !idTramite || isFocusedIdTramite, // El opuesto de la regla 1                                
                            // COLOR DE FOCO: Solo si está ENFOCADO.
                            'text-color1 dark:text-color1': isFocusedIdTramite,
                            // COLOR POR DEFECTO: Si NO está enfocado.
                            'text-gray-500 dark:text-gray-400': !isFocusedIdTramite
                        }"
                        :style="{
                            top: '10px' 
                        }">
                        Trámite
                    </label>
                </template> -->
            </div>
            <div class="relative z-0 mb-8 group peer w-full md:w-[100%]">
                <div class="relative w-full">
                    <el-select
                        ref="floatingIdRequisitoRef"
                        v-model="idRequisito"
                        @focus="handleFocusIdRequisito(true)"
                        @blur="handleFocusIdRequisito(false)"
                        placeholder=""
                        class="w-full el-select-dinamyc">                        
                        <el-option
                            v-for="(requisito, index) in requisitosNoAsignados"
                            :key="index"
                            :label="requisito.nombre"
                            :value="requisito.id"
                            :item-data="requisito"> 
                            <div class="flex items-center">
                                <span>{{ requisito.nombre }}</span>
                            </div>
                        </el-option>                            
                    </el-select>
                    <label
                        style="z-index: 10"
                        class="absolute text-sm duration-300 transform origin-[0] left-0 right-0 pointer-events-none"
                        :class="{
                            // POSICIÓN ARRIBA: Cuando tiene valor seleccionado O tiene foco.
                            '-translate-y-6': idRequisito || isFocusedIdRequisito,
                            // POSICIÓN ABAJO (INICIAL): En el estado de Placeholder.
                            'translate-y-0': !idRequisito && !isFocusedIdRequisito, 
                            // 1. Grande (scale-100): Solo cuando tienes valor seleccionado Y pierdes el foco. (TU REQUERIMIENTO ESPECÍFICO)
                            'scale-100': idRequisito && !isFocusedIdRequisito,
                            // 2. Pequeño (scale-90): En el estado inicial, con foco, o sin valor.
                            'scale-90': !idRequisito || isFocusedIdRequisito, // El opuesto de la regla 1                                
                            // COLOR DE FOCO: Solo si está ENFOCADO.
                            'text-color1 dark:text-color1': isFocusedIdRequisito,
                            // COLOR POR DEFECTO: Si NO está enfocado.
                            'text-gray-500 dark:text-gray-400': !isFocusedIdRequisito
                        }"
                        :style="{
                            top: '10px' 
                        }">
                        Requisito
                    </label>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center mb-4 gap-8">
                <div class="relative mb-8 pt-4 w-full md:w-[53%]">
                    <el-switch
                        v-model="requisitoObligatorio"
                        active-text="Obligatorio"
                        inactive-text="Opcional"
                        size="large"/>
                </div>
                <div class="relative mb-4 w-full md:w-[40%]">
                    <el-switch
                        v-model="requisitoActivo"
                        active-text="Activo"
                        inactive-text="Inactivo"
                        size="large"/>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="showModalAsignacionTramite = false;" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">
                    Cancelar
                </button>
                <!-- <button v-if="isEditing"
                    type="button"
                    @click="iniciarActualizacionAsignacion"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Actualizar Asignación
                </button> -->
                <button
                    type="button"
                    @click="iniciarCrearAsignacion"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Crear Asignación
                </button>
            </div>
            <div
                v-if="isSavingModal"
                class="absolute inset-0 flex items-center justify-center rounded-lg"
                style="background-color: rgba(255, 255, 255, 0.85); z-index: 50;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path
                        d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                    />
                </svg>
                <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
            </div>
            <div v-if="isLoadingModal"
                class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-lg backdrop-blur-[2px] bg-white/70 transition-all duration-300">
                <div class="relative flex items-center justify-center">
                    <div class="h-12 w-12 rounded-full border-8 border-color1-100"></div>
                    <div class="absolute h-12 w-12 animate-spin rounded-full border-8 border-color1-300 border-t-transparent"></div>
                </div>                
                <span class="mt-4 text-sm font-medium text-gray-600 animate-pulse">
                    Cargando...
                </span>
            </div>
        </div>
    </div>
    <div v-if="showModalAsignacionesRequisitosTramite" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-5xl relative">
            <button 
                @click="showModalAsignacionesRequisitosTramite = false" 
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-900 transition duration-300 transform hover:rotate-180 focus:outline-none"
                aria-label="Cerrar modal">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <h3 class="text-xl font-bold mb-4">Asignación de Requisitos a Trámite </h3>
            <div class="relative z-0 mb-4 w-full md:w-[100%]">
                <label
                    class="text-lg text-gray-800 block mb-0">
                    Trámite
                </label>
                <label
                    class="inline-flex items-center pl-2 pr-3 py-1 text-sm font-medium text-white rounded-r-full"
                    :style="{ backgroundColor: colorTramite }">
                    {{ nombreTramite }}
                </label>
            </div>
            <div class="flex items-center gap-2 text-lg text-gray-800">
                <span>Selecciona los Requisitos Asignados</span>
                <span class="bg-color1-50 text-color1-800 px-2 py-0.5 ml-2 rounded-r-full text-sm font-semibold border border-color1-400 transition-all duration-300">
                    <template v-if="cantRequisitosSeleccionados > 0">
                        {{ cantRequisitosSeleccionados }} 
                        {{ cantRequisitosSeleccionados === 1 ? 'seleccionado' : 'seleccionados' }}
                    </template>                    
                    <template v-else>
                        Ninguno seleccionado
                    </template>
                </span>
            </div>
            <div class="text-xs text-gray-500 italic mb-2 -z-10">
                Marca los requisitos de esta lista que deben estar asignados para cumplir con este trámite.
            </div>
            <div v-if="requisitosActivos && requisitosActivos.length > 0" class="grid grid-cols-4 gap-4 pt-2 pb-4 pr-4 w-full max-h-[51vh] mb-8 overflow-y-auto"> 
                 <div 
                    v-for="requisito in requisitosActivos" 
                    :key="requisito.id"
                    @click="toggleRequisito(requisito)"
                    :title="requisito.nombre"
                    class="active:scale-[0.98] active:ring-2 active:ring-offset-1 relative inline-block cursor-pointer group h-14 flex items-center pl-4 rounded-md shadow-sm border transition duration-150"
                    :class="{ 
                        // 🟢 ESTADO ASIGNADO
                        'bg-white border-color1-500 ring-color1-400': isRequisitoAssignedAndActive(requisito),
                        // 🟡 ESTADO NO ASIGNADO
                        'bg-white border-gray-300': !isRequisitoAssignedAndActive(requisito),
                        
                        // 🟡 LÓGICA DE HOVER DINÁMICO (Solo si NO está asignado)
                        'hover:border-color1-100 hover:ring-color1-900 hover:shadow-md hover:-translate-y-0.5': !isRequisitoAssignedAndActive(requisito),
                        'hover:border-color1-600 hover:ring-color1-900 hover:shadow-md hover:-translate-y-0.5': isRequisitoAssignedAndActive(requisito),
                        'border-l-4': isRequisitoObligatorio(requisito),
                        // Esto define el ancho y color.
                        'border-r-2': isRequisitoNotEditable(requisito),
                        // Usaremos la clase de corchetes de Tailwind JIT o estilo in-line como fallback.
                        '[border-right-style:dotted]': isRequisitoNotEditable(requisito),
                        // 3. Compensación del padding (si es necesario)
                        'pr-[calc(1rem + 2px)]': isRequisitoNotEditable(requisito)
                   }">
                            
                   <button
                    type="button" 
                    @click.stop="toggleRequisito(requisito)"                
                   
                    :class="[
                        // 1. CLASES ESTÁTICAS BASE (simpre aplicadas)
                        'absolute top-1 right-1 h-4 w-4 rounded border',
                        'flex items-center justify-center transition-colors',
                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                        'cursor-pointer',
                        
                        // 2. CLASES CONDICIONALES EN FORMA DE OBJETO
                        {
                            // ESTADO ASIGNADO: Aplicamos el color de fondo y quitamos el borde gris
                            'bg-color1-700 border-color1-700 bg-opacity-100': isRequisitoAssignedAndActive(requisito),
                            
                            // ESTADO NO ASIGNADO: Fondo blanco y borde gris
                            'border-gray-400 bg-white': !isRequisitoAssignedAndActive(requisito)
                        }
                    ]">
                         <svg v-if="isRequisitoAssignedAndActive(requisito)"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" 
                            stroke-width="3.5"  
                            stroke="white"     
                            fill="none"        
                            class="w-3 h-3 relative z-10">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </button> 
                    <label 
                        :for="'requisito-' + requisito.id" 
                        class="block pr-8 flex-grow cursor-pointer" >
                        <h4 :class="{ 'font-semibold': isRequisitoAssignedAndActive(requisito),
                         }"
                        class="cursor-pointer text-gray-900 text-xs leading-tight line-clamp-2"> {{ requisito.nombre }}
                        </h4> 
                    </label> 
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="showModalAsignacionesRequisitosTramite = false;" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="realizarAsignacionTramiteRequisitos"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Realizar Asignación
                </button>
            </div> 
            <div
                v-if="isSavingModal"
                class="absolute inset-0 flex items-center justify-center rounded-lg"
                style="background-color: rgba(255, 255, 255, 0.85); z-index: 50;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path
                        d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                    />
                </svg>
                <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
            </div>
        </div>
    </div>
    <div v-if="showModalRequisito" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg relative">
            <h3 v-if="isEditingRequisito" class="text-xl font-bold mb-8">Editar Requisito</h3>
            <h3 v-if="!isEditingRequisito" class="text-xl font-bold mb-8">Nuevo Requisito</h3>
            <div class="relative z-0 mb-8 group peer w-full md:w-[100%]">
                <input
                    v-model="nombreRequisito"
                    ref="floatingNombreRequisitoRef"
                    type="text"
                    class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                    :class="[
                        {
                            'pb-1 py-2.5 px-0': nombreRequisitoEditable,
                            'bg-transparent pl-0 ml-0 mt-8': !nombreRequisitoEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': nombreRequisitoEditable
                        }
                    ]"
                    placeholder=""
                    :disabled="!nombreRequisitoEditable"
                    required/>
                <label
                    style="z-index: 10"
                    class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                    Nombre
                    <button
                        v-if="!nombreRequisitoEditable && !nombreRequisitoBloqueado"
                        class="ml-2 bg-transparent text-gray-500 hover:text-color1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                            <path
                                d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                            />
                        </svg>
                    </button>
                </label>
            </div>
            <div class="relative z-0 mb-8 group peer w-full md:w-[100%]">
                <input
                    v-model="nombreRequisitoCorto"
                    ref="floatingNombreRequisitoCortoRef"
                    type="text"                    
                    placeholder="Use solo mayúsculas, guiones bajos (_), y sin espacios ni acentos"                    
                    class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer hide-placeholder-on-blur"
                    :class="[
                        {
                            'pb-1 py-2.5 px-0': nombreRequisitoCortoEditable,
                            'bg-transparent pl-0 ml-0 mt-8': !nombreRequisitoCortoEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': nombreRequisitoCortoEditable
                        }
                    ]"
                    :disabled="!nombreRequisitoCortoEditable"
                    required/>
                <label
                    style="z-index: 10"
                    class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                    Nombre Corto
                    </label>
            </div>
            <div class="flex flex-wrap items-center mb-4 gap-8">
                <div class="relative mb-4 w-full md:w-[40%]">
                    <el-switch
                        v-model="requisitoActivo"
                        active-text="Activo"
                        inactive-text="Inactivo"
                        size="large"/>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="showModalRequisito = false;" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">
                    Cancelar
                </button>
                <button v-if="isEditingRequisito"
                    type="button"
                    @click="iniciarActualizacionRequisito"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Actualizar Requisito
                </button>
                <button v-if="!isEditingRequisito"
                    type="button"
                    @click="crearRequisito()"
                    class="px-4 py-2 text-sm font-medium text-white bg-color1-700 rounded-full hover:bg-color1-800">
                    Guardar Requisito
                </button>
            </div>
            <div
                v-if="isSavingModal"
                class="absolute inset-0 flex items-center justify-center rounded-lg"
                style="background-color: rgba(255, 255, 255, 0.85); z-index: 50;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path
                        d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                    />
                </svg>
                <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
            </div>
            <div v-if="isLoadingModal"
                class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-lg backdrop-blur-[2px] bg-white/70 transition-all duration-300">
                <div class="relative flex items-center justify-center">
                    <div class="h-12 w-12 rounded-full border-8 border-color1-100"></div>
                    <div class="absolute h-12 w-12 animate-spin rounded-full border-8 border-color1-300 border-t-transparent"></div>
                </div>                
                <span class="mt-4 text-sm font-medium text-gray-600 animate-pulse">
                    Cargando...
                </span>
            </div>
        </div>
    </div>
</template>