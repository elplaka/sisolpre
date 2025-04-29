<script setup>
    import { router, usePage } from '@inertiajs/vue3';
    import { ref, computed, watch, onMounted, onBeforeUnmount, markRaw  } from 'vue';
    import Pagination from '@/Components/Pagination.vue'

    const props = defineProps({
        solicitudes: Array,
        estatusSolicitud: Array,
        pagination: Object,
        tiposPropiedades: Array,
        destinosObras: Array,
        tiposTramites: Array,
        // searchQuery: String,
        // selectedTypes: Array,
        // isActive: Boolean,
        // isInactive: Boolean,
        // activos: Number,
        // inactivos: Number,
        // sortColumn: String, 
        // sortDirection: String
    });

    const value = ref(false)
    const sortColumn = ref('');
    const sortDirection = ref('');
    const dropdownVisible = ref(null);
    
    const idSolicitudEditar = ref('');
    const nuevoSolicitante = ref(false);
    const nuevoPropietario = ref(false);
    const nuevaPropiedad = ref(false);
    const fecha_ingreso = ref(null);

    const isLoading = ref(false);
    const isLoadingModal = ref(false);
    const isLoadingModal2 = ref(false);
    const isSavingModal = ref(false);
    const paraNuevaSolicitud = ref(false);
    const paraEditarSolicitud = ref(false);
    const dialogVisible = ref(false);
    const modalBuscarSolicitanteVisible = ref(false);
    const modalBuscarColoniaVisible = ref(false);
    const modalBuscarLocalidadVisible = ref(false);
    const floatingCURPRef = ref(null);  
    const floatingNombreRef = ref(null); 
    const floatingNombrePropietarioRef = ref(null); 
    const floatingNombreColoniaRef = ref(null);  
    const floatingNombreLocalidadRef = ref(null); 
    const floatingTipoPropiedadRef = ref(null); 
    const floatingSuperficiePropiedadRef = ref(null);
    const floatingSuperficieConstruccionPropiedadRef = ref(null);

    const abreModalSolicitud = async () => {
        resetFormData();

        activeTab.value = 'solicitante';

        paraNuevaSolicitud.value = true;
        paraEditarSolicitud.value = false;
        dialogVisible.value = true; 

        idColoniaSolicitante.value = '';
        nombreColoniaSolicitante.value = '';
        idLocalidadSolicitante.value = '';
        nombreLocalidadSolicitante.value = '';

        // claveCatastral.value = '01598765432109876';

        //curp.value = 'CAGA910511MPLVRP07';
        // nomSolicitante.value = 'ANGEL';
        // apeSolicitante.value = 'SANCHEZ DIAZ';
        // telefonoSolicitante.value = '6941088943';
        // emailSolicitante.value = 'elplaka@hotmail.com';
        // calleSolicitante.value = 'PROL. BENITO JUAREZ';
        // numeroSolicitante.value = '25';
        // idColoniaSolicitante.value = 1;
        // nombreColoniaSolicitante.value = 'CENTRO';
        // idLocalidadSolicitante.value = '1';
        // nombreLocalidadSolicitante.value = 'CONCORDIA';

        // destinoObra.value = 1;

        isLoading.value = false;   
        isLoadingModal2.value = false; 

        setTimeout(() => {
            floatingCURPRef.value?.focus();
        }, 50);
    }

    const abreModalEditarSolicitud = async (solicitud) => {
        paraNuevaSolicitud.value = false;
        paraEditarSolicitud.value = true;
        dialogVisible.value = true; 

        idSolicitudEditar.value = solicitud.id;

        folio.value = solicitud.id;
        idEstatusSolicitud.value = solicitud.id_estatus;
        fecha_ingreso.value = solicitud.fecha_ingreso;
        curp.value = solicitud.solicitante.persona.curp;
        nomSolicitante.value = solicitud.solicitante.persona.nombre;
        apeSolicitante.value = solicitud.solicitante.persona.apellidos;
        telefonoSolicitante.value = solicitud.solicitante.telefono;
        emailSolicitante.value = solicitud.solicitante.email;
        calleSolicitante.value = solicitud.solicitante.calle;
        numeroSolicitante.value = solicitud.solicitante.num_casa;
        idColoniaSolicitante.value = solicitud.solicitante.id_colonia;
        nombreColoniaSolicitante.value = solicitud.solicitante.colonia?.nombre;
        idLocalidadSolicitante.value = solicitud.solicitante.id_localidad;
        nombreLocalidadSolicitante.value = solicitud.solicitante.localidad?.nombre;

        claveCatastral.value = solicitud.propiedad.clave_catastral;
        tipoPropiedad.value = solicitud.propiedad.id_tipo;
        nombreTipoPropiedad.value = solicitud.propiedad?.tipo?.nombre;
        superficiePropiedad.value = solicitud.propiedad?.superficie;
        superficieConstruccionPropiedad.value = solicitud.propiedad?.superficie_construccion;
        callePropiedad.value = solicitud.propiedad?.calle;
        numeroPropiedad.value = solicitud.propiedad?.numero;
        idColoniaPropiedad.value = solicitud.propiedad?.id_colonia;
        nombreColoniaPropiedad.value = solicitud.propiedad?.colonia?.nombre;
        idLocalidadPropiedad.value = solicitud.propiedad?.id_localidad;
        nombreLocalidadPropiedad.value = solicitud.propiedad?.localidad?.nombre;

        tramitesSeleccionados.value = solicitud.tramites.map(tramite => tramite.id_tramite);
        activeTab.value = 'solicitante';
    } 

    const dialogWidth = computed(() =>  {
            return window.innerWidth < 1024 ? '95%' : '55%';
    });

    const dialogWidthBuscar = computed(() =>  {
            return window.innerWidth < 1024 ? '95%' : '35%';
    });

    const resetFormData = () => {
        curp.value = '';
        tipoPropiedad.value = '';
        nombreTipoPropiedad.value = '';
        superficiePropiedad.value = '';
        superficieConstruccionPropiedad.value = '';
        callePropiedad.value = '';
        numeroPropiedad.value = '';
        idColoniaPropiedad.value = '';
        nombreColoniaPropiedad.value = '';
        idLocalidadPropiedad.value = '';
        nombreLocalidadPropiedad.value = '';
        tipoPropiedadEditable.value = true;
        claveCatastralEditable.value = true;
        superficiePropiedadEditable.value = true;
        superficieConstruccionPropiedadEditable.value = true;
        callePropiedadEditable.value = true;
        numeroPropiedadEditable.value = true;
        idColoniaPropiedadEditable.value = true;
        idLocalidadPropiedadEditable.value = true;
        idEstatusSolicitud.value = '';
        const today = new Date();
        const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().split('T')[0];
        fecha_ingreso.value = localDate;
        imageSrc.value = '';
        isFileLoaded.value = false;
    };

    const handleClose = () => {
        dialogVisible.value = false;
    };

    const isFocusedTipoPropiedad = ref(false);

    const handleFocusTipoPropiedad = (status) => {
        isFocusedTipoPropiedad.value = status; // SELECT - Cambia el estado del foco
    };

    const isFocusedDestinoObra = ref(false);

    const handleFocusDestinoObra = (status) => {
        isFocusedDestinoObra.value = status; // SELECT - Cambia el estado del foco
    };

    const abreModalBuscarSolicitante = async () => {

        modalBuscarSolicitanteVisible.value = true; 
    }

    const folio = ref('');
    const idEstatusSolicitud = ref('');

    const curpCompleta = ref(false);
    const curpInvalida = ref(false);   
    const persona = ref([]);
    const curp = ref('');
    const nomSolicitante = ref('');
    const apeSolicitante = ref('');
    const calleSolicitante = ref('');
    const numeroSolicitante = ref('');
    const idColoniaSolicitante = ref('');
    const nombreColoniaSolicitante = ref('');
    const idLocalidadSolicitante = ref('');
    const nombreLocalidadSolicitante = ref('');
    const telefonoSolicitante = ref('');
    const emailSolicitante = ref('');
    const nomSolicitanteEditable = ref(true);
    const apeSolicitanteEditable = ref(true);
    const calleSolicitanteEditable = ref(true);
    const numeroSolicitanteEditable = ref(true);
    const idColoniaSolicitanteEditable = ref(true);
    const idLocalidadSolicitanteEditable = ref(true);
    const telefonoSolicitanteEditable = ref(true);
    const emailSolicitanteEditable = ref(true);

    const esPropietario = ref(1);
    const longitud_curp = ref(18);

    const curpCompletaPropietario = ref(false);
    const curpInvalidaPropietario = ref(false);   
    const curpPropietario = ref('');
    const nomPropietario = ref('');
    const apePropietario = ref('');
    const callePropietario = ref('');
    const numeroPropietario = ref('');
    const idColoniaPropietario = ref('');
    const nombreColoniaPropietario = ref('');
    const idLocalidadPropietario = ref('');
    const nombreLocalidadPropietario = ref('');
    const telefonoPropietario = ref('');
    const emailPropietario = ref('');

    const propiedad = ref([]);
    const tipoPropiedad = ref('');
    const nombreTipoPropiedad = ref('');
    const claveCatastral = ref('');
    const claveCatastralCompleta = ref(false);
    const superficiePropiedad = ref('');
    const superficieConstruccionPropiedad = ref('');
    const callePropiedad = ref('');
    const numeroPropiedad = ref('');
    const idColoniaPropiedad = ref('');
    const nombreColoniaPropiedad = ref('');
    const idLocalidadPropiedad = ref('');
    const nombreLocalidadPropiedad = ref('');
    const tipoPropiedadEditable = ref(true);
    const claveCatastralEditable = ref(true);
    const superficiePropiedadEditable = ref(true);
    const superficieConstruccionPropiedadEditable = ref(true);
    const callePropiedadEditable = ref(true);
    const numeroPropiedadEditable = ref(true);
    const idColoniaPropiedadEditable = ref(true);
    const idLocalidadPropiedadEditable = ref(true);
    const croquis = ref('');

    const tramitesSeleccionados = ref([]); 
    const destinoObra = ref('');

    const obtenerPropiedad = async (claveCatastral) => {
        try {
            if (dialogVisible.value)
            {
                isLoadingModal.value = true;
                isLoading.value = false;
            }
            else
            { 
                isLoading.value = true;
                isLoadingModal.value = false;
            }

            const response = await fetch(`/solicitudes/get-propiedad/${encodeURIComponent(claveCatastral)}`, { // Agrega las comillas
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }

            const data = await response.json();
            propiedad.value = data.propiedad; // Aquí se almacenan los datos en la variable puestos
            croquis.value = data.croquis;

        } catch (error) {
            console.error('Error al obtener los datos:', error);
        }
        finally
        {
            isLoading.value = false;
            isLoadingModal.value = false;
        }
    };


    const obtenerPersona = async (curp) => {
        try {
            if (dialogVisible.value)
            {
                isLoadingModal.value = true;
                isLoading.value = false;
            }
            else
            { 
                isLoading.value = true;
                isLoadingModal.value = false;
            }

            const response = await fetch(`/solicitudes/get-persona/${encodeURIComponent(curp)}`, { // Agrega las comillas
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }

            const data = await response.json();
            persona.value = data.persona; // Aquí se almacenan los datos en la variable puestos
        } catch (error) {
            console.error('Error al obtener los datos:', error);
        }
        finally
        {
            isLoading.value = false;
            isLoadingModal.value = false;
        }
    };

   // Expresión regular para validar CURP
    const curpRegex = /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/;

    watch(curp, async (newValue) => {
        if (newValue.length >= longitud_curp.value)
        {
            if (newValue.length > longitud_curp.value)
            {
                curpInvalida.value = true;
                curpCompleta.value = false;
                return;
            }
            if (!curpRegex.test(newValue)) 
            {
                curpInvalida.value = true;
                curpCompleta.value = false;
                return;
            }            

            isLoadingModal.value = true; // Muestra el cargador
            if (paraNuevaSolicitud)
            {
                await obtenerPersona(newValue);
                curpCompleta.value = true;
                curpInvalida.value = false;

                if (persona.value && persona.value.length > 0) {
                    nomSolicitante.value = persona.value[0].nombre;
                    apeSolicitante.value = persona.value[0].apellidos;
                    telefonoSolicitante.value = persona.value[0].solicitante.telefono;
                    emailSolicitante.value = persona.value[0].solicitante.email;
                    calleSolicitante.value = persona.value[0].solicitante.calle;
                    numeroSolicitante.value = persona.value[0].solicitante.num_casa;
                    idColoniaSolicitante.value = persona.value[0].solicitante.id_colonia;
                    idLocalidadSolicitante.value = persona.value[0].solicitante.id_localidad;
                    nombreColoniaSolicitante.value = persona.value[0].solicitante.colonia?.nombre ?? '';
                    nombreLocalidadSolicitante.value = persona.value[0].solicitante.localidad?.nombre ?? '';
                    nuevoSolicitante.value = false;
                    nomSolicitanteEditable.value = false;
                    apeSolicitanteEditable.value = false;
                    telefonoSolicitanteEditable.value = false;
                    emailSolicitanteEditable.value = false;
                    calleSolicitanteEditable.value = false;
                    numeroSolicitanteEditable.value = false;
                    idColoniaSolicitanteEditable.value = false;
                    idLocalidadSolicitanteEditable.value = false;
                } 
                else
                {
                    nuevoSolicitante.value = true;
                    nomSolicitante.value = '';
                    apeSolicitante.value = '';
                    telefonoSolicitante.value = '';
                    emailSolicitante.value = '';
                    calleSolicitante.value = '';
                    numeroSolicitante.value = '';
                    idColoniaSolicitante.value = '';
                    nombreColoniaSolicitante.value = '';
                    idLocalidadSolicitante.value = '';
                    nombreLocalidadSolicitante.value = '';
                    nomSolicitanteEditable.value = true;
                    apeSolicitanteEditable.value = true;
                    telefonoSolicitanteEditable.value = true;
                    emailSolicitanteEditable.value = true;
                    calleSolicitanteEditable.value = true;
                    numeroSolicitanteEditable.value = true;
                    idColoniaSolicitanteEditable.value = true;
                    idLocalidadSolicitanteEditable.value = true;
                    setTimeout(() => {
                        floatingNombreRef.value?.focus();
                    }, 50);
                }
            }
            isLoadingModal.value = false; 
        } 
        else 
        {
            curpInvalida.value = false;
            curpCompleta.value = false;
        }
    });

    const activeTab = ref('solicitante');
    
    const nombreColoniaBuscar = ref('');
    const coloniasFiltradas = ref(null);
    const tipoColonia = ref('');

    const focusInputSuperficiePropiedad = () =>
    {
        setTimeout(() => {
                floatingSuperficiePropiedadRef.value?.focus();
        }, 50);
    }

    const focusInputSuperficieConstruccionPropiedad = () =>
    {
        setTimeout(() => {
                floatingSuperficieConstruccionPropiedadRef.value?.focus();
        }, 50);
    }

    const abreModalBuscarColonia = (tipo) =>
    {
        modalBuscarColoniaVisible.value = true;

        isLoading.value = false;   
        isLoadingModal.value = false;
        nombreColoniaBuscar.value = '';
        coloniasFiltradas.value = null;

        tipoColonia.value = tipo;

        setTimeout(() => {
            floatingNombreColoniaRef.value?.focus();
        }, 50);
    }

    function debounce(func, delay) 
    {
        let timeout;
        return (...args) => {
            clearTimeout(timeout); // Resetea el temporizador si se vuelve a llamar
            timeout = setTimeout(() => {
                func(...args); // Ejecuta la función tras el retraso
            }, delay);
        };
    }

    const filtrarColonias = async () => {
        isLoadingModal2.value = true; // Indicar que está cargando
        try {
            const response = await fetch(`/solicitudes/get-colonias?nombre=${encodeURIComponent(nombreColoniaBuscar.value)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            coloniasFiltradas.value = data.colonias;
        } catch (error) {
            console.error('Error al obtener los datos:', error);
        }
        finally 
        {
            isLoadingModal2.value = false; // Finalizar el estado de carga
        }
    };

    const agregarColonia = async() => {
        isLoadingModal2.value = true;
        const formData = new FormData();
        formData.append('nombre', nombreColoniaBuscar.value);

        try {
            await router.post('/colonias/store', formData, {
                onSuccess: (page) => {
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    modalBuscarColoniaVisible.value = false;
                    idColoniaSolicitante.value = page.props.flash.idColonia;
                    nombreColoniaSolicitante.value = nombreColoniaBuscar.value.toUpperCase().trim();
                    // resetFormData();
                    isLoadingModal2.value = false;
                },
                onFinish: () => {
                    isLoadingModal2.value = false;
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isLoadingModal2.value = false;
                    // Mostrar SweetAlert2 con los errores de validación
                    let errorMessage = 'Hubo un error al guardar la información.';
                    
                    // Si hay errores de validación, construir un mensaje con ellos
                    if (errors && Object.keys(errors).length > 0) {
                        errorMessage = Object.values(errors).join('<br>'); // Unir los errores en un solo mensaje
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage,
                        confirmButtonText: 'Aceptar',
                        width: '600px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important');
                            }
                        }
                    });
                },
            });
        } catch (err) {
            console.error('Error inesperado:', err);
        }
    };
    
    const debouncedFiltrarColonias = debounce(filtrarColonias, 500);

    const seleccionaColonia = (idColonia, nombreColonia) =>
    {
        modalBuscarColoniaVisible.value = false;

        if (tipoColonia.value == 'solicitante')
        {
            idColoniaSolicitante.value = idColonia;
            nombreColoniaSolicitante.value = nombreColonia;
        }
        else if (tipoColonia.value == 'propietario')
        {
            idColoniaPropietario.value = idColonia;
            nombreColoniaPropietario.value = nombreColonia;
        }
        else if (tipoColonia.value == 'propiedad')
        {
            idColoniaPropiedad.value = idColonia;
            nombreColoniaPropiedad.value = nombreColonia;
        }
    }

    const nombreLocalidadBuscar = ref('');
    const localidadesFiltradas = ref(null);

    const abreModalBuscarLocalidad = () =>
    {
        modalBuscarLocalidadVisible.value = true;

        isLoading.value = false;   
        isLoadingModal.value = false;
        nombreLocalidadBuscar.value = '';
        localidadesFiltradas.value = null;

        setTimeout(() => {
            floatingNombreLocalidadRef.value?.focus();
        }, 50);
    }

    const filtrarLocalidades = async () => {
        isLoadingModal2.value = true; // Indicar que está cargando
        try {
            const response = await fetch(`/solicitudes/get-localidades?nombre=${encodeURIComponent(nombreLocalidadBuscar.value)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            localidadesFiltradas.value = data.localidades;
        } catch (error) {
            console.error('Error al obtener los datos:', error);
        }
        finally 
        {
            isLoadingModal2.value = false; // Finalizar el estado de carga
        }
    };

    const agregarLocalidad = async() => {
        isLoadingModal2.value = true;
        const formData = new FormData();
        formData.append('nombre', nombreLocalidadBuscar.value);

        try {
            await router.post('/localidades/store', formData, {
                onSuccess: (page) => {
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    modalBuscarLocalidadVisible.value = false;
                    idLocalidadSolicitante.value = page.props.flash.idLocalidad;
                    nombreLocalidadSolicitante.value = nombreLocalidadBuscar.value.toUpperCase().trim();
                    // resetFormData();
                    isLoadingModal2.value = false;
                },
                onFinish: () => {
                    isLoadingModal2.value = false;
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isLoadingModal2.value = false;
                    // Mostrar SweetAlert2 con los errores de validación
                    let errorMessage = 'Hubo un error al guardar la información.';
                    
                    // Si hay errores de validación, construir un mensaje con ellos
                    if (errors && Object.keys(errors).length > 0) {
                        errorMessage = Object.values(errors).join('<br>'); // Unir los errores en un solo mensaje
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage,
                        confirmButtonText: 'Aceptar',
                        width: '600px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important');
                            }
                        }
                    });
                },
            });
        } catch (err) {
            console.error('Error inesperado:', err);
        }
    };
    
    const debouncedFiltrarLocalidades = debounce(filtrarLocalidades, 500);

    const seleccionaLocalidad = (idLocalidad, nombreLocalidad) =>
    {
        modalBuscarLocalidadVisible.value = false;

        idLocalidadSolicitante.value = idLocalidad;
        nombreLocalidadSolicitante.value = nombreLocalidad;
    }

    watch(claveCatastral, async (newValue) => {
        if (!newValue) { // Verifica si es null, undefined o vacío
            claveCatastral.value = ''; // O maneja el caso como prefieras
            return;
        }
        
        if (newValue.length >= 23)
        {
            if (newValue.length != 23)
            {
                claveCatastralCompleta.value = false;
                return;
            }

            const claveCatastralSinEspacios = newValue.replace(/\s/g, '');

            isLoadingModal.value = true; // Muestra el cargador
            propiedad.value = null;
            await obtenerPropiedad(claveCatastralSinEspacios);

            claveCatastralCompleta.value = true;

            if (propiedad.value) 
            {
                tipoPropiedad.value = propiedad.value.id_tipo;
                nombreTipoPropiedad.value = propiedad?.value?.tipo?.nombre || '';
                superficiePropiedad.value = propiedad.value.superficie;
                superficieConstruccionPropiedad.value = propiedad.value.superficie_construccion;
                callePropiedad.value = propiedad.value.calle;
                numeroPropiedad.value = propiedad.value.numero;
                idColoniaPropiedad.value = propiedad.value.id_colonia;
                nombreColoniaPropiedad.value = propiedad?.value?.colonia?.nombre || '';
                idLocalidadPropiedad.value = propiedad.value.id_localidad;
                nombreLocalidadPropiedad.value = propiedad?.value?.localidad?.nombre || '';               
                nuevaPropiedad.value = false;              
                acceptFile();
                imageSrc.value = '/storage/croquis/' + croquis.value;
            } 
            else
            {
                resetFormData();
                nuevaPropiedad.value = true;
                setTimeout(() => {
                    floatingTipoPropiedadRef.value?.focus();
                }, 50);
            }

            tipoPropiedadEditable.value = nuevaPropiedad;
            claveCatastralEditable.value = nuevaPropiedad;
            superficiePropiedadEditable.value = nuevaPropiedad;
            superficieConstruccionPropiedadEditable.value = nuevaPropiedad;
            callePropiedadEditable.value = nuevaPropiedad;
            numeroPropiedadEditable.value = nuevaPropiedad;
            idColoniaPropiedadEditable.value = nuevaPropiedad;
            idLocalidadPropiedadEditable.value = nuevaPropiedad;
        } 
        else 
        {
            claveCatastralCompleta.value = false;
        }
    });

    watch(curpPropietario, async (newValue) => {
        if (newValue.length >= longitud_curp.value)
        {
            if (newValue.length > longitud_curp.value)
            {
                curpInvalidaPropietario.value = true;
                curpCompletaPropietario.value = false;
                return;
            }
            if (!curpRegex.test(newValue)) 
            {
                curpInvalidaPropietario.value = true;
                curpCompletaPropietario.value = false;
                return;
            }
            
            isLoadingModal.value = true; // Muestra el cargador
            await obtenerPersona(newValue);
            curpCompletaPropietario.value = true;
            curpInvalidaPropietario.value = false;

            if (persona.value && persona.value.length > 0) {
                // nomPropietario.value = persona.value[0].nombre;
                // apePropietario.value = persona.value[0].apellidos;
                // callePropietario.value = persona.value[0].solicitante.calle;
                // nuevoPropietario.value = false;
            } else
            {
                nuevoPropietario.value = true;
                setTimeout(() => {
                    floatingNombrePropietarioRef.value?.focus();
                }, 50);
            }
        } 
        else 
        {
            curpInvalidaPropietario.value = false;
            curpCompletaPropietario.value = false;
        }
    });

    const cveMunicipio = ref('012');

    onMounted(() => {
        claveCatastral.value = cveMunicipio.value + '  000'; // Inicializa con cveMunicipio seguido de un espacio
        document.addEventListener('click', handleClickOutside);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    // Método para dar formato y permitir edición completa
    const formatClaveCatastral = (event) => {
        let value = event.target.value.replace(/\D/g, ''); // Elimina todo lo que no sean dígitos
        value = value.substring(0, 18);

        // Formatear en grupos de tres dígitos separados por espacios
        value = value.replace(/(\d{3})(?=\d)/g, '$1 ');
        claveCatastral.value = value.trim();
    };

    watch(claveCatastral, (newValue) => {
        if (!newValue) { // Verifica si es null, undefined o vacío
            claveCatastral.value = ''; // O maneja el caso como prefieras
            return;
        }

        let value = newValue.replace(/\D/g, '').substring(0, 18);
        value = value.replace(/(\d{3})(?=\d)/g, '$1 ');
        claveCatastral.value = value.trim();
    });

    const handleFocusClaveCatastral = (event) => {
        if (claveCatastral.value.length === 7) {
            event.target.setSelectionRange(7, 7); // Coloca el cursor después del tercer carácter
        } else {
            // event.target.select();
        }
    };

    const claveCatastralRef = ref(null);

    watch(activeTab, (newValue) => {
        if (newValue === 'propiedad') {
            setTimeout(() => {
                claveCatastralRef.value?.focus();
            }, 50);
        }
    });

    const isFileLoaded = ref(false);
    const archivoInvalido = ref(false);
    const file = ref(null);
    const imageSrc = ref('');
    const previewDialogVisible = ref(false);
    const fileInput = ref(null);
    const fileIcon = ref(null); // Para almacenar el componente del icono
    const fileName = ref('');

    const triggerFileInput = () => {
        fileInput.value.click();
    };

    const dropZoneCroquis = ref(null);

    const handleDragOver = () => {
        if (dropZoneCroquis.value) 
        {
            dropZoneCroquis.value.classList.add('drop-zone', 'bg-color1-50', 'border-color1-700'); // Ejemplo con Tailwind CSS
        }
    };

    const handleDrop = (event) => {
        if (dropZoneCroquis.value) 
        {
            dropZoneCroquis.value.classList.remove('drop-zone','bg-color1-50', 'border-color1-700');
        }
        const droppedFile = event.dataTransfer.files[0];
        handleFile(droppedFile);
    };

    const handleFileChange = (event) => {
        const selectedFile = event.target.files[0];
        handleFile(selectedFile);
    };

    const handleFile = (selectedFile) => {
        if (selectedFile) {
            file.value = selectedFile;
            fileName.value = selectedFile.name;
            if (selectedFile.type.startsWith('image/')) 
            {
                const reader = new FileReader();
                reader.onloadend = () => 
                {
                    imageSrc.value = reader.result;
                    previewDialogVisible.value = true;
                };
                reader.readAsDataURL(selectedFile);
                archivoInvalido.value = false;
            } 
            else 
            {
                isFileLoaded.value = false;
                previewDialogVisible.value = true;
                file.value = null;
                fileName.value = '';
                imageSrc.value = '';
                if (fileInput.value) 
                {
                    fileInput.value.value = '';
                }
                archivoInvalido.value = true;
            }
        }
        else 
        {
            fileName.value = ''; // Limpiar el nombre si no hay archivo seleccionado
        }
    };

    const acceptFile = () => {
        previewDialogVisible.value = false;
        isFileLoaded.value = true;
        const fileIconTemplate = {
            template: `
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M220-344q65-8 129.5-12t130.5-4q66 0 130.5 4T740-344L560-560 446-424l-80-96-146 176ZM120-160q-17 0-28.5-11.5T80-200v-560q0-17 11.5-28.5T120-800q8 0 35.5 9.5T229-770q46 11 108.5 20.5T480-740q80 0 142.5-9.5T731-770q46-11 73.5-20.5T840-800q17 0 28.5 11.5T880-760v560q0 17-11.5 28.5T840-160q-8 0-35.5-9.5T731-190q-46-11-108.5-20.5T480-220q-80 0-142.5 9.5T229-190q-46 11-73.5 20.5T120-160Zm40-94q78-23 158.5-34.5T480-300q81 0 161.5 11.5T800-254v-451q-78 23-158.5 34T480-660q-81 0-161.5-11T160-705v451Zm320-226Z"/>
            </svg>
            `
        };
        fileIcon.value = markRaw(fileIconTemplate); // Marca el objeto como "raw"
    };

        const rejectFile = () => 
        {
            previewDialogVisible.value = false;
            isFileLoaded.value = false;
            file.value = null;
            fileName.value = '';
            imageSrc.value = '';
            if (fileInput.value) 
            {
                fileInput.value.value = '';
            }
        };

    const showPreviewDialog = () => {
        if (file.value) {
            if (file.value.type.startsWith('image/')) 
            {
                const reader = new FileReader();
                reader.onloadend = () => {
                    imageSrc.value = reader.result;
                    previewDialogVisible.value = true;
                };
                reader.readAsDataURL(file.value);
            } 
            else 
            {
                previewDialogVisible.value = true;
                isFileLoaded.value = false;
                file.value = null;
                imageSrc.value = '';
                fileName.value = '';
                if (fileInput.value) 
                {
                    fileInput.value.value = '';
                }
            }
        }
        else 
        {
            if (!nuevaPropiedad.value)
            {
                previewDialogVisible.value = true;
                isFileLoaded.value = true;
            }
            else
            {    
                file.value = null;
                imageSrc.value = '';
                fileName.value = '';
                if (fileInput.value) 
                {
                    fileInput.value.value = '';
                }
            }
        }
    };

    const removeFile = () => {
        isFileLoaded.value = false;
        file.value = null;
        imageSrc.value = '';
        fileName.value = '';
        if (fileInput.value) 
        {
            fileInput.value.value = '';
        }
    };

    const generarNombreArchivo = (nombreOriginal) => 
    {
        const extension = nombreOriginal.substring(nombreOriginal.lastIndexOf('.'));
        const nombreBase = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        return `${nombreBase}${extension}`;
    };

    const agregarSolicitud = async() => {
        isSavingModal.value = true;
        const formData = new FormData();
        formData.append('curp', curp.value);
        formData.append('curpInvalida', curpInvalida.value);
        formData.append('nomSolicitante', nomSolicitante.value);
        formData.append('apeSolicitante', apeSolicitante.value);
        formData.append('calleSolicitante', calleSolicitante.value);
        formData.append('idColoniaSolicitante', idColoniaSolicitante.value);
        formData.append('numeroSolicitante', numeroSolicitante.value);
        formData.append('idLocalidadSolicitante', idLocalidadSolicitante.value);
        formData.append('telefonoSolicitante', telefonoSolicitante.value);
        formData.append('emailSolicitante', emailSolicitante.value);
        formData.append('esPropietario', esPropietario.value);
        if (!esPropietario.value)
        {
            formData.append('curpPropietario', curpPropietario.value);
            formData.append('nomPropietario', nomPropietario.value);
            formData.append('apePropietario', apePropietario.value);
            formData.append('callePropietario', callePropietario.value);
            formData.append('idColoniaPropietario', idColoniaPropietario.value);
            formData.append('numeroPropietario', numeroPropietario.value);
            formData.append('idLocalidadPropietario', idLocalidadPropietario.value);
            formData.append('telefonoPropietario', telefonoPropietario.value);
            formData.append('emailPropietario', emailPropietario.value);
        }
        formData.append('tipoPropiedad', tipoPropiedad.value);
        let claveCatastralSinEspacios = { value: claveCatastral.value.replace(/\s/g, '') };
        if (claveCatastralSinEspacios.length <= 6)  //Si no capturaron más del inicio de la clave
        {
            claveCatastralSinEspacios.value = null;
        }
        formData.append('claveCatastral', claveCatastralSinEspacios.value);      
        formData.append('callePropiedad', callePropiedad.value);
        formData.append('numeroPropiedad', numeroPropiedad.value);
        formData.append('idColoniaPropiedad', idColoniaPropiedad.value);
        formData.append('idLocalidadPropiedad', idLocalidadPropiedad.value);
        formData.append('superficiePropiedad', superficiePropiedad.value);
        formData.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value);

        formData.append('destinoObra', destinoObra.value);
        formData.append('fechaIngreso', fecha_ingreso.value);
        formData.append('idEstatusSolicitud', idEstatusSolicitud.value);

        tramitesSeleccionados.value.forEach(tramiteId => {
            formData.append('tramitesSeleccionados[]', tramiteId);
        });

        if (file.value)
        {
            const nombreArchivoCroquis = generarNombreArchivo(fileName.value);
            formData.append('croquis', file.value, nombreArchivoCroquis);
        }
        try {
            await router.post('/solicitudes/store', formData, {
                onSuccess: (page) => {
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    modalBuscarColoniaVisible.value = false;
                    resetFormData();
                    isSavingModal.value = false;
                    dialogVisible.value = false;
                },
                onFinish: () => {
                    isSavingModal.value = false;
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isSavingModal.value = false;

                    const hasValidationErrors = errors && Object.keys(errors).length > 0;
                    const errorMessage = hasValidationErrors
                        ? Object.values(errors).join('<br>')
                        : 'Hubo un error al guardar la información.';
                    
                    Swal.fire({
                        icon: hasValidationErrors ? 'warning' : 'error',
                        title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                        html: `<div style="text-align: justify; font-size: 12pt"><ul>${errorMessage}</ul></div>`,
                        confirmButtonText: 'Aceptar',
                        width: '400px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important');
                            }
                        }
                    });
                },
            });
        } catch (err) {
            console.error('Error inesperado:', err);
        }
    };

    function sortTable(column) 
    {
        if (sortColumn.value === column) {
            // Cambiar la dirección de ordenación si se hace clic en la misma columna
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            // Establecer la nueva columna y la dirección de ordenación a ascendente
            sortColumn.value = column;
            if (sortColumn.value == 'es_activo')
            {
                sortDirection.value = 'desc';
            }
            else
            { 
                sortDirection.value = 'asc';
            }
        }

        // Obtener los parámetros actuales
        const urlParams = new URLSearchParams(window.location.search);
        const selectedTypesSet = new Set(selectedTypes.value);
        
        // Añadir los nuevos valores de selectedTypes[] sin duplicados
        selectedTypesSet.forEach((type) => {
            urlParams.append('selectedTypes[]', type);
        });

        if (isActive.value) {
            urlParams.set('isActive', true);
        } else if (isInactive.value) {
            urlParams.set('isActive', false);
        } else {
            urlParams.delete('isActive');
        }

        if (isInactive.value) {
            urlParams.set('isInactive', true);
        } else if (isInactive.value) {
            urlParams.set('isInactive', false);
        } else {
            urlParams.delete('isInactive');
        }

        // Construir el objeto de parámetros final
        const paramsObject = {};
        urlParams.forEach((value, key) => {
            if (key === 'selectedTypes[]') {
                // Almacenar los valores como un array en paramsObject
                if (!paramsObject[key]) {
                    paramsObject[key] = [];
                }
                paramsObject[key].push(value);
            } else {
                paramsObject[key] = value;
            }
        });

        // Añadir los nuevos parámetros sortColumn y sortDirection al objeto 
        paramsObject.sortColumn = sortColumn.value; 
        paramsObject.sortDirection = sortDirection.value;

        // Realizar la solicitud con los parámetros combinados
        router.get('/admin/usuarios', paramsObject, {
            preserveState: true,
            replace: true
        });

    }

    const formatDate = (dateString) => {
            const [year, month, day] = dateString.split('-'); // Divide la fecha ISO en partes
            return `${day}/${month}/${year}`; // Construye la fecha en formato "dd/mm/yyyy"
        };

    const actualizarTramitesSeleccionados = (tramiteId, isChecked) => {
        if (isChecked) {
            if (!tramitesSeleccionados.value.includes(tramiteId)) {
            tramitesSeleccionados.value.push(tramiteId);
            }
        } else {
            const index = tramitesSeleccionados.value.indexOf(tramiteId);
            if (index > -1) {
            tramitesSeleccionados.value.splice(index, 1);
            }
        }
    };

    function toggleDropdown(solicitudId, event) {
        if (dropdownVisible.value === solicitudId) {
            dropdownVisible.value = null;
            return;
        }

        dropdownVisible.value = solicitudId;
    }

    function closeDropdown() {
        dropdownVisible.value = null;
    }

    function handleClickOutside(event) {
        const dropdown = document.getElementById(`dropdown-${dropdownVisible.value}`);
        const button = document.getElementById(`dropdown-button-${dropdownVisible.value}`);
        if (
            dropdownVisible.value &&
            dropdown &&
            button &&
            !dropdown.contains(event.target) &&
            !button.contains(event.target)
        ) {
            closeDropdown();
        }
    }

    const getColorById = (id) => {
        const estatus = props.estatusSolicitud?.find((item) => item.id === id);
        return estatus ? estatus.color : "#766e76";
    };

    const habilitarCaptura = (control) => {
        if (control == 'nomSolicitanteEditable')
        {
             nomSolicitanteEditable.value = !nomSolicitanteEditable.value;
        }
        else if (control == 'apeSolicitanteEditable')
        {
             apeSolicitanteEditable.value = !apeSolicitanteEditable.value;
        }
        else if (control == 'telefonoSolicitanteEditable')
        {
             telefonoSolicitanteEditable.value = !telefonoSolicitanteEditable.value;
        }
        else if (control == 'emailSolicitanteEditable')
        {
             emailSolicitanteEditable.value = !emailSolicitanteEditable.value;
        }
        else if (control == 'calleSolicitanteEditable')
        {
             calleSolicitanteEditable.value = !calleSolicitanteEditable.value;
        }
        else if (control == 'numeroSolicitanteEditable')
        {
             numeroSolicitanteEditable.value = !numeroSolicitanteEditable.value;
        }
        else if (control == 'idColoniaSolicitanteEditable')
        {
             idColoniaSolicitanteEditable.value = !idColoniaSolicitanteEditable.value;
        }
        else if (control == 'idLocalidadSolicitanteEditable')
        {
             idLocalidadSolicitanteEditable.value = !idLocalidadSolicitanteEditable.value;
        }
    }

    const actualizarSolicitud = async() => {
        isSavingModal.value = true;
        const formData = new FormData();
        formData.append('curp', curp.value);
        formData.append('curpInvalida', curpInvalida.value);
        formData.append('nomSolicitante', nomSolicitante.value);
        formData.append('apeSolicitante', apeSolicitante.value);
        formData.append('calleSolicitante', calleSolicitante.value);
        formData.append('idColoniaSolicitante', idColoniaSolicitante.value);
        formData.append('numeroSolicitante', numeroSolicitante.value);
        formData.append('idLocalidadSolicitante', idLocalidadSolicitante.value);
        formData.append('telefonoSolicitante', telefonoSolicitante.value);
        formData.append('emailSolicitante', emailSolicitante.value);
        formData.append('esPropietario', esPropietario.value);
        if (!esPropietario.value)
        {
            formData.append('curpPropietario', curpPropietario.value);
            formData.append('nomPropietario', nomPropietario.value);
            formData.append('apePropietario', apePropietario.value);
            formData.append('callePropietario', callePropietario.value);
            formData.append('idColoniaPropietario', idColoniaPropietario.value);
            formData.append('numeroPropietario', numeroPropietario.value);
            formData.append('idLocalidadPropietario', idLocalidadPropietario.value);
            formData.append('telefonoPropietario', telefonoPropietario.value);
            formData.append('emailPropietario', emailPropietario.value);
        }
        formData.append('tipoPropiedad', tipoPropiedad.value);
        let claveCatastralSinEspacios = { value: claveCatastral.value.replace(/\s/g, '') };
        if (claveCatastralSinEspacios.length <= 6)  //Si no capturaron más del inicio de la clave
        {
            claveCatastralSinEspacios.value = null;
        }
        formData.append('claveCatastral', claveCatastralSinEspacios.value);      
        formData.append('callePropiedad', callePropiedad.value);
        formData.append('numeroPropiedad', numeroPropiedad.value);
        formData.append('idColoniaPropiedad', idColoniaPropiedad.value);
        formData.append('idLocalidadPropiedad', idLocalidadPropiedad.value);
        formData.append('superficiePropiedad', superficiePropiedad.value);
        formData.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value);

        formData.append('destinoObra', destinoObra.value);
        formData.append('fechaIngreso', fecha_ingreso.value);
        formData.append('idEstatusSolicitud', idEstatusSolicitud.value);

        tramitesSeleccionados.value.forEach(tramiteId => {
            formData.append('tramitesSeleccionados[]', tramiteId);
        });

        if (file.value)
        {
            const nombreArchivoCroquis = generarNombreArchivo(fileName.value);
            formData.append('croquis', file.value, nombreArchivoCroquis);
        }
        try {
            await router.post('/solicitudes/update/' + folio.value, formData, {
                onSuccess: (page) => {
                    Swal.fire({
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    modalBuscarColoniaVisible.value = false;
                    resetFormData();
                    isSavingModal.value = false;
                    dialogVisible.value = false;
                },
                onFinish: () => {
                    isSavingModal.value = false;
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isSavingModal.value = false;

                    const hasValidationErrors = errors && Object.keys(errors).length > 0;
                    const errorMessage = hasValidationErrors
                        ? Object.values(errors).join('<br>')
                        : 'Hubo un error al guardar la información.';
                    
                    Swal.fire({
                        icon: hasValidationErrors ? 'warning' : 'error',
                        title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                        html: `<div style="text-align: justify; font-size: 12pt"><ul>${errorMessage}</ul></div>`,
                        confirmButtonText: 'Aceptar',
                        width: '400px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important');
                            }
                        }
                    });
                },
            });
        } catch (err) {
            console.error('Error inesperado:', err);
        }
    };
</script>

   <template>
        <el-dialog
            v-model="dialogVisible"
            :width="dialogWidth"
            :before-close="handleClose"
            top="6vh">
            <template #header>
                <span v-if="paraEditarSolicitud">
                    Editar solicitud 
                    <span class="bg-color1 text-white text-sm font-semibold px-2 py-1 ml-2 rounded-r-md rounded-l-none">
                        N° {{ (idSolicitudEditar % 10000).toString().padStart(4, '0') }}
                    </span>
                </span>
                <span v-else>
                    Nueva solicitud
                </span>
            </template>
            <div v-if="isLoadingModal" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); display: flex; align-items: center; justify-content: center; z-index: 50; background-color: rgba(255, 255, 255, 0.7); width: 100%; height: 100%;">
                <svg class="animate-spin" style="width: 2rem; height: 2rem; color: gray;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4;" cx="12" cy="12" r="10"></circle>
                    <path style="opacity: 0.75; fill: currentColor;" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span style="margin-left: 0.5rem; color: gray;">Cargando...</span>
            </div>
            <div v-if="isSavingModal" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); display: flex; align-items: center; justify-content: center; z-index: 50; background-color: rgba(255, 255, 255, 0.7); width: 100%; height: 100%;">
                <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray;" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"/>
                </svg>
                <span style="margin-left: 0.5rem; color: gray;">Guardando...</span>
            </div>
            <div class="flex flex-col mb-5 md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-4">
                <div class="relative w-full md:w-auto">
                    <label
                        class="block text-sm text-gray-500 dark:text-gray-400">
                        Fecha
                    </label>
                    <input
                    v-model="fecha_ingreso"
                    type="date"
                    name="floating_fecha"
                    id="floating_fecha"
                    class="block w-full md:w-auto py-2.5 pb-1 px-0 text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer"
                    required/>
                </div>
                <div class="flex flex-col w-full md:w-90 lg:w-[18rem]">
                    <label
                        for="estatus-solicitud"
                        class="block text-sm text-gray-500 dark:text-gray-400 mb-1">
                        Estatus
                        <span
                            class="w-2 h-2 rounded-sm inline-block mr-2"
                            :style="{ backgroundColor: getColorById(idEstatusSolicitud) }"></span>
                    </label>
                    <el-select
                        v-model="idEstatusSolicitud"
                        placeholder="Selecciona un ESTATUS"
                        class="custom-select w-full md:w-60 lg:w-[18rem]">
                        <el-option
                        class="custom-option"
                        v-for="item in estatusSolicitud"
                        :key="item.id"
                        :label="item.nombre"
                        :value="item.id"
                        :style="{ color: item.color }">
                        <span
                            class="w-3 h-3 rounded-sm inline-block mr-2"
                            :style="{ backgroundColor: item.color }"
                        ></span>
                        {{ item.nombre }}
                        </el-option>
                    </el-select>
                </div>
                <el-switch
                    v-if="!paraEditarSolicitud"
                    v-model="value"
                    class="custom-switch self-end w-auto"
                    size="large"
                    active-text="Folio manual"
                    inactive-text="Folio automático"
                />
            </div>
            <div class="border-b-2 mb-4 border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    <li class="me-2">
                        <a
                            href="#"
                            @click.prevent="activeTab = 'solicitante'"
                            :class="activeTab === 'solicitante' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                            class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg group">
                            <svg
                                :class="activeTab === 'solicitante' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                                class="w-4 h-4 me-2"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 20 20">
                                <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z" />
                            </svg>
                            Solicitante
                        </a>
                    </li>
                    <li v-if="esPropietario == 0" class="me-2">
                        <a
                            href="#"
                            @click.prevent="activeTab = 'propietario'"
                            :class="activeTab === 'propietario' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                            class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg group">
                            <svg
                                :class="activeTab === 'propietario' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                                class="w-4 h-4 me-2"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 16 16">
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6m5 2.755C12.146 12.825 10.623 12 8 12s-4.146.826-5 1.755V14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1z"/>
                            </svg>
                            Propietario
                        </a>
                    </li>
                    <li class="me-2">
                        <a
                            href="#"
                            @click.prevent="activeTab = 'propiedad'"
                            :class="activeTab === 'propiedad' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                            class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg group">
                            <svg
                                :class="activeTab === 'propiedad' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                                class="w-4 h-4 me-2"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 -960 960 960">
                                <path d="M80-120v-650l200-150 200 150v90h400v560H80Zm80-80h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 0h80v-80h-80v80Zm0 480h480v-400H320v400Zm240-240v-80h160v80H560Zm0 160v-80h160v80H560ZM400-440v-80h80v80h-80Zm0 160v-80h80v80h-80Z" />
                            </svg>                            
                            Propiedad
                        </a>
                    </li>
                    <li class="me-2">
                        <a
                            href="#"
                            @click.prevent="activeTab = 'tramite'"
                            :class="activeTab === 'tramite' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                            class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg group">
                            <svg
                                :class="activeTab === 'tramite' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                                class="w-4 h-4 me-2"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 -960 960 960"
                                >
                                <path d="M760-200H320q-33 0-56.5-23.5T240-280v-560q0-33 23.5-56.5T320-920h280l240 240v400q0 33-23.5 56.5T760-200ZM560-640v-200H320v560h440v-360H560ZM160-40q-33 0-56.5-23.5T80-120v-560h80v560h440v80H160Zm160-800v200-200 560-560Z" />
                            </svg>
                            Trámite(s)
                        </a>
                    </li>
                    <li class="me-2">
                        <a
                            href="#"
                            @click.prevent="activeTab = 'croquis'"
                            :class="activeTab === 'croquis' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                            class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg group">
                            <svg
                            :class="activeTab === 'croquis' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 -960 960 960">
                            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Zm140-360q25 0 42.5-17.5T400-620q0-25-17.5-42.5T340-680q-25 0-42.5 17.5T280-620q0 25 17.5 42.5T340-560Z"/>
                        </svg>
                            Croquis
                        </a>
                    </li>
                </ul>
            </div>
            <div v-if="activeTab === 'solicitante'" class="space-y-4">
                <h3 
                    class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                    <span>Datos del/a solicitante</span>
                    <span v-if="nuevoSolicitante"
                        style="letter-spacing:0.5px" class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">
                        Nuevo(a)
                    </span>
                </h3>
                <div class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 25%;">
                        <input
                        v-model="curp"
                        ref="floatingCURPRef"
                        autocomplete="off"
                        type="text"
                        name="floating_CURP"
                        id="floating_CURP"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        :maxlength="longitud_curp" 
                        required/>
                        <label 
                            for="floating_CURP" 
                            style="letter-spacing: -0.12em;" 
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                            <span v-if="!curpInvalida" class="flex-shrink-0">C U R P</span>
                            <span 
                                class="flex items-center bg-color1-50 text-color1-800 px-1 rounded-md"
                                style="letter-spacing: 0em;" 
                                v-else="curpInvalida">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" 
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                CURP inválida
                            </span>
                        </label>
                    </div>
                    <div v-if="curpCompleta" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 35%;">
                        <input
                        v-model="nomSolicitante"
                        ref="floatingNombreRef"
                        autocomplete="off"
                        type="text"          
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': nomSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !nomSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': nomSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!nomSolicitanteEditable"
                        required/>
                        <label for="floating_nombre"
                            style="letter-spacing: -0.12em; z-index: 10;"
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                            N o m b r e
                            <button
                                v-if="!nomSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('nomSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>                        
                    </div>
                    <div v-if="curpCompleta" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="apeSolicitante"
                        ref="floatingApellidosRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': apeSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !apeSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': apeSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!apeSolicitanteEditable"
                        required/>
                        <label
                        style="letter-spacing: -0.12em; z-index: 10;"
                        for="floating_apellidos"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        A p e l l i d o s
                            <button
                                v-if="!apeSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('apeSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>
                    </div>
                </div>
                <div v-if="curpCompleta" class="flex items-center space-x-4" style="margin-bottom: -1rem;">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 25%;">
                        <input
                        v-model="telefonoSolicitante"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': telefonoSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !telefonoSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': telefonoSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!telefonoSolicitanteEditable"
                        required/>
                        <label
                        style="letter-spacing: -0.12em; z-index: 10;"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block">N ° &nbsp; d e &nbsp; t e l é f o n o</span>
                        <span class="block sm:hidden">T e l é f o n o</span>
                            <button
                                v-if="!telefonoSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('telefonoSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="emailSolicitante"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': emailSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !emailSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': emailSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!emailSolicitanteEditable"/>
                        <label
                        style="letter-spacing: -0.12em; z-index: 10;"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block">C o r r e o &nbsp; e l e c t r ó n i c o</span>
                        <span class="block sm:hidden">E - m a i l</span>
                            <button
                                v-if="!emailSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('emailSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 35%;">
                        <select
                            id="select_propietario"
                            v-model="esPropietario"
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer
                            bg-gray-50 p-2.5"
                            required>
                            <option value="" disabled selected style="display: none;"></option>
                            <option :key="'1'" :value="'1'">SÍ</option>
                            <option :key="'0'" :value="'0'">NO</option>
                        </select>
                        <label
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block"> ¿Es el/la propietario(a)?</span>
                        <span class="block sm:hidden">¿Propietario(a)?</span>
                        </label>
                    </div>
                </div>
                <h2 v-if="curpCompleta" 
                    class="flex items-center text-sm font-semibold text-gray-800 dark:text-white">
                    <span>Domicilio</span>
                </h2>
                <div v-if="curpCompleta" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="calleSolicitante"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': calleSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !calleSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': calleSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!calleSolicitanteEditable"/>
                        <label style="z-index: 10;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        Calle
                            <button
                                v-if="!calleSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('calleSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 10%;">
                        <input
                        v-model="numeroSolicitante"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': numeroSolicitanteEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !numeroSolicitanteEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': numeroSolicitanteEditable
                        }]"
                        placeholder=""
                        :disabled="!numeroSolicitanteEditable"/>
                        <label style="z-index: 10;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N°
                            <button
                                v-if="!numeroSolicitanteEditable"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('numeroSolicitanteEditable')" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                            </button>
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreColoniaSolicitante"
                                type="text"
                                class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                                :class="[{
                                    'pb-1 py-2.5 px-0': idColoniaSolicitanteEditable,
                                    'bg-color3-100 p-0 m-0 mt-2': !idColoniaSolicitanteEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idColoniaSolicitanteEditable
                                }]"
                                placeholder=""
                                disabled
                                required />
                                <label
                                    style="z-index: 10;"
                                    :class="[
                                        'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                        idColoniaSolicitanteEditable || idColoniaSolicitante == null
                                            ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                            : '-translate-y-8 peer-placeholder-shown:scale-90' ,
                                        { 'peer-placeholder-shown:translate-y-0': idColoniaSolicitanteEditable },
                                        { 'peer-placeholder-shown:translate-y-[-0.5rem]': idColoniaSolicitante == null }
                                    ]">
                                    Colonia
                                    <button
                                        v-if="!idColoniaSolicitanteEditable"
                                        class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                        @click="habilitarCaptura('idColoniaSolicitanteEditable')" >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                        </svg>
                                    </button>
                                </label>
                        </div>
                        <button
                            type="button"
                            @click="abreModalBuscarColonia('solicitante')"
                            title="Buscar colonia..."
                            v-if="idColoniaSolicitanteEditable"
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div v-if="curpCompleta" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreLocalidadSolicitante"
                                type="text"
                                class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                                :class="[{
                                    'pb-1 py-2.5 px-0': idLocalidadSolicitanteEditable,
                                    'bg-color3-100 p-0 m-0 mt-2': !idLocalidadSolicitanteEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idLocalidadSolicitanteEditable
                                }]"
                                placeholder=""
                                disabled
                                required />
                                <label
                                    style="z-index: 10;"
                                    :class="[
                                        'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                        idLocalidadSolicitanteEditable
                                            ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                            : '-translate-y-8 peer-placeholder-shown:scale-90' ,
                                        { 'peer-placeholder-shown:translate-y-0': idLocalidadSolicitanteEditable },
                                        { 'peer-placeholder-shown:translate-y-[-0.5rem]': !idLocalidadSolicitanteEditable }
                                    ]"
                                >
                                    Localidad
                                    <button
                                        v-if="!idLocalidadSolicitanteEditable"
                                        class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                        @click="habilitarCaptura('idLocalidadSolicitanteEditable')" >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1"> <!-- Uso de `group-hover` para cambiar el color -->
                                            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                        </svg>
                                    </button>
                                </label>
                        </div>
                        <button
                            type="button"
                            @click="abreModalBuscarLocalidad"
                            v-if="idLocalidadSolicitanteEditable"
                            title="Buscar localidad..."
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="activeTab === 'propietario'" class="space-y-4">
                <h3 
                    class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                    <span>Datos del/a propietario(a)</span>
                    <span 
                        style="letter-spacing:0.5px" class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">
                        Nuevo(a)
                    </span>
                </h3>
                <div class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 25%;">
                        <input
                        v-model="curpPropietario"
                        ref="floatingCURPPropietarioRef"
                        autocomplete="off"
                        type="text"
                        name="floating_CURPPropietario"
                        id="floating_CURPPropietario"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        :maxlength="longitud_curp" 
                        required/>
                        <label 
                            for="floating_CURPPropietario" 
                            style="letter-spacing: -0.12em;" 
                            class="flex items-center peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                            <span v-if="!curpInvalidaPropietario" class="flex-shrink-0">C U R P</span>
                            <span 
                                class="flex items-center bg-color1-50 text-color1-800 px-1 rounded-md"
                                style="letter-spacing: 0em;" 
                                v-else="curpInvalidaPropietario">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" 
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                CURP inválida
                            </span>
                        </label>
                    </div>
                    <div v-if="curpCompletaPropietario" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 35%;">
                        <input
                        v-model="nomPropietario"
                        ref="floatingNombrePropietarioRef"
                        autocomplete="off"
                        type="text"          
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        for="floating_nombrePropietario"
                        style="letter-spacing: -0.12em;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N o m b r e
                        </label>
                    </div>
                    <div v-if="curpCompletaPropietario" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="apePropietario"
                        autocomplete="off"
                        type="text"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        style="letter-spacing: -0.12em;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        A p e l l i d o s
                        </label>
                    </div>
                </div>
                <div v-if="curpCompletaPropietario" class="flex items-center space-x-4" style="margin-bottom: -1rem;">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 25%;">
                        <input
                        v-model="telefonoPropietario"
                        type="text"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        style="letter-spacing: -0.12em;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        
                        <span class="hidden sm:block">N ° &nbsp; d e &nbsp; t e l é f o n o</span>
                        <span class="block sm:hidden">T e l é f o n o</span>
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 45%;">
                        <input
                        v-model="emailPropietario"
                        type="text"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        style="letter-spacing: -0.12em;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block">C o r r e o &nbsp; e l e c t r ó n i c o</span>
                        <span class="block sm:hidden">E - m a i l</span>
                        </label>
                    </div>
                </div>
                <h2 v-if="curpCompletaPropietario" 
                    class="flex items-center text-sm font-semibold text-gray-800 dark:text-white">
                    <span>Domicilio</span>
                </h2>
                <div v-if="curpCompletaPropietario" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="callePropietario"
                        type="text"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        Calle
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 10%;">
                        <input
                        v-model="numeroPropietario"
                        type="text"
                        class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                        placeholder=""
                        required/>
                        <label
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N°
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreColoniaPropietario"
                                type="text"
                                class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                                placeholder=""
                                disabled
                                required />
                            <label
                                class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                                Colonia
                            </label>
                        </div>
                        <button
                            type="button"
                            @click="abreModalBuscarColonia('propietario')"
                            title="Buscar colonia..."
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div v-if="curpCompletaPropietario" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreLocalidadPropietario"
                                type="text"
                                class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                                placeholder=""
                                disabled
                                required />
                            <label
                                class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                                Localidad
                            </label>
                        </div>
                        <button
                            type="button"
                            @click="abreModalBuscarLocalidad"
                            title="Buscar localidad..."
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="activeTab === 'propiedad'" class="space-y-4">
                <h3 
                    class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                    <span>Datos de la propiedad</span>
                    <span v-if="nuevaPropiedad"
                        style="letter-spacing:0.5px" class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">
                        Nueva
                    </span>
                </h3>
                <div class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 22%;">
                        <input
                            v-model="claveCatastral"
                            ref="claveCatastralRef" 
                            type="text"
                            class="block pb-1 py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 focus:outline-none focus:ring-0 focus:border-color1 peer"
                            maxlength="23"
                            @focus="handleFocusClaveCatastral"
                            @input="formatClaveCatastral"
                            required
                            />
                        <label
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block"> Clave catastral</span>
                        <span class="block sm:hidden">Cve. catastral</span>
                        </label>
                    </div>
                    <div v-if="claveCatastralCompleta" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 24%;">
                        <template v-if="tipoPropiedadEditable">
                            <select
                                id="select_tipo_propiedad"
                                ref="floatingTipoPropiedadRef"
                                v-model="tipoPropiedad"
                                @focus="handleFocusTipoPropiedad(true)"
                                @blur="handleFocusTipoPropiedad(false)"
                                class="pt-[10px] pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50 p-2.5"
                                required>
                                <option value="" disabled selected style="display: none;"></option>
                                <option v-for="(tipo, index) in tiposPropiedades" :key="index" :value="tipo.id">
                                    {{ tipo.nombre }}
                                </option>
                            </select>
                            <label style="z-index: 10;"
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                            for="select_tipo_propiedad"
                            :class="{
                                'scale-85 -translate-y-9': tipoPropiedad || isFocusedTipoPropiedad,
                                'scale-90 translate-y-[-11px]': !tipoPropiedad && !isFocusedTipoPropiedad
                            }"
                            :style="{
                                top: tipoPropiedad || isFocusedTipoPropiedad ? '25px' : '24px',
                            }"
                            >
                            Tipo
                            </label>
                        </template>
                        <template v-else>
                            <input
                            v-model="nombreTipoPropiedad"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[{
                                'pb-1 py-2.5 px-0': tipoPropiedadEditable,
                                'bg-color3-100 p-0 m-0 mt-2': !tipoPropiedadEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': tipoPropiedadEditable
                            }]"
                            :disabled="!tipoPropiedadEditable"
                            :placeholder="''" />
                            <label style="z-index: 10;"
                                class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] 
                                        peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                                :class="{ 'translate-y-0 scale-90': !nombreTipoPropiedad }">
                                Tipo
                            </label>
                        </template>
                    </div>
                    <div v-if="claveCatastralCompleta" class="relative z-0 mb-5 group peer w-full" style="flex-basis: 27%;">
                        <input
                            v-model="superficiePropiedad"
                            ref="floatingSuperficiePropiedadRef"
                            type="number"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[{
                                'pb-1 py-2.5 px-0': superficiePropiedadEditable,
                                'bg-color3-100 p-0 m-0 mt-2': !superficiePropiedadEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': superficiePropiedadEditable
                            }]"
                            required
                            :disabled="!superficiePropiedadEditable"
                            :placeholder="''" />
                        <label style="z-index: 10;"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] 
                                    peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{ 'translate-y-0 scale-90': !superficiePropiedad }"
                        >
                            <span class="hidden sm:block" @click="focusInputSuperficiePropiedad"> Superficie (m²)</span>
                            <span class="block sm:hidden"> Sup.</span>
                        </label>
                    </div>
                    <div v-if="claveCatastralCompleta" style="flex-basis: 27%;">
                        <div v-if="tipoPropiedad == 2" class="relative z-0 mb-5 group peer w-full">
                            <input
                                v-model="superficieConstruccionPropiedad"
                                ref="floatingSuperficieConstruccionPropiedadRef"
                                type="number"
                                class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                                :class="[{
                                    'pb-1 py-2.5 px-0': superficieConstruccionPropiedadEditable,
                                    'bg-color3-100 p-0 m-0 mt-2': !superficieConstruccionPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': superficieConstruccionPropiedadEditable
                                }]"
                                :disabled="!superficieConstruccionPropiedadEditable"
                                required
                                :placeholder="''" />
                                <label style="z-index: 10;"
                                class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]
                                    peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                                :class="[
                                    superficieConstruccionPropiedadEditable ? '-translate-y-6' : '-translate-y-8',
                                    { 'translate-y-0 scale-90': !superficieConstruccionPropiedad }
                                ]">
                                <span class="hidden sm:block" @click="focusInputSuperficieConstruccionPropiedad"> Superficie en construcción (m²)</span>
                                <span class="block sm:hidden"> Sup. const.</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div v-if="claveCatastralCompleta" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 40%;">
                        <input
                        v-model="callePropiedad"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': callePropiedadEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !callePropiedadEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': callePropiedadEditable
                        }]"
                        placeholder=""
                        :disabled="!callePropiedadEditable"
                        required/>
                        <label style="z-index: 10;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        Calle
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 10%;">
                        <input
                        v-model="numeroPropiedad"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[{
                            'pb-1 py-2.5 px-0': numeroPropiedadEditable,
                            'bg-color3-100 p-0 m-0 mt-2': !numeroPropiedadEditable,
                            'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': numeroPropiedadEditable
                        }]"
                        placeholder=""
                        :disabled="!numeroPropiedadEditable"
                        required/>
                        <label style="z-index: 10;"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N°
                        </label>
                    </div>
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreColoniaPropiedad"
                                type="text"
                                class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                                :class="[{
                                    'pb-1 py-2.5 px-0': idColoniaPropiedadEditable,
                                    'bg-color3-100 p-0 m-0 mt-2': !idColoniaPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idColoniaPropiedadEditable
                                }]"
                                placeholder=""
                                disabled
                                required />
                                <label
                                    style="z-index: 10;"
                                    :class="[
                                        'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                        idColoniaPropiedadEditable
                                            ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                            : '-translate-y-8 peer-placeholder-shown:scale-90' ,
                                        { 'peer-placeholder-shown:translate-y-0': idColoniaPropiedadEditable },
                                        { 'peer-placeholder-shown:translate-y-[-0.5rem]': !idColoniaPropiedadEditable }
                                    ]"
                                >
                                    Colonia
                                </label>
                        </div>
                        <button
                            type="button"
                            v-if="idColoniaPropiedadEditable"
                            @click="abreModalBuscarColonia('propiedad')"
                            title="Buscar colonia..."
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div v-if="claveCatastralCompleta" class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 50%;">
                        <div class="relative">
                            <input
                                v-model="nombreLocalidadPropiedad"
                                type="text"
                                class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                                :class="[{
                                    'pb-1 py-2.5 px-0': idLocalidadPropiedadEditable,
                                    'bg-color3-100 p-0 m-0 mt-2': !idLocalidadPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idLocalidadPropiedadEditable
                                }]"
                                placeholder=""
                                disabled
                                required />
                                <label
                                    style="z-index: 10;"
                                    :class="[
                                        'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                        idLocalidadPropiedadEditable
                                            ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                            : '-translate-y-8 peer-placeholder-shown:scale-90' ,
                                        { 'peer-placeholder-shown:translate-y-0': idLocalidadPropiedadEditable },
                                        { 'peer-placeholder-shown:translate-y-[-0.5rem]': !idLocalidadPropiedadEditable }
                                    ]"
                                >
                                    Localidad
                                </label>
                        </div>
                        <button
                            type="button"
                            v-if="idLocalidadPropiedadEditable"
                            @click="abreModalBuscarLocalidad"
                            title="Buscar localidad..."
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="activeTab == 'tramite'" class="space-y-4">
                <h3 
                    class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                    <span>Trámite(s) a realizar</span>
                </h3>
                <div class="flex items-center overflow-x-auto">
                    <table class="min-w-full border-collapse shadow-md rounded-lg overflow-hidden mb-5">
                        <thead>
                            <tr>
                                <th
                                    v-for="tipoTramite in tiposTramites"
                                    :key="tipoTramite.id"
                                    class="border-0 uppercase border-b-2 border-gray-300 px-4 py-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ tipoTramite.nombre }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td v-for="(tipoTramite, index) in tiposTramites"
                                    :key="tipoTramite.id"
                                    :class="{
                                        'bg-gray-100': index % 2 === 0, // Aplica fondo gray-200 para columnas pares (0, 2, 4...)
                                        'bg-white': index % 2 !== 0    // Aplica fondo blanco para columnas impares (1, 3, 5...)
                                    }"
                                    class="border-0 border-gray-300 px-4 py-2 align-top">
                                    <div v-for="tramite in tipoTramite.tramites" :key="tramite.id" class="mb-1">
                                        <input
                                            type="checkbox"
                                            :id="`tramite-${tramite.id}`"
                                            :value="tramite.id"
                                            class="w-4 h-4 text-color1 focus:ring-color1 border-gray-300 rounded"
                                            :checked="tramitesSeleccionados.includes(tramite.id)"
                                            @change="actualizarTramitesSeleccionados(tramite.id, $event.target.checked)"
                                            />
                                        <label
                                            :for="`tramite-${tramite.id}`"
                                            class="ml-2 text-xs text-gray-700 dark:text-gray-300">
                                            {{ tramite.nombre }}
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative z-0 mb-5 group peer w-full" style="flex-basis: 30%;">
                        <select
                            id="select_destino_obra"
                            v-model="destinoObra"
                            @focus="handleFocusDestinoObra(true)"
                            @blur="handleFocusDestinoObra(false)"
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50 p-2.5"
                            required>
                            <option value="" disabled selected style="display: none;"></option>
                            <option v-for="(destino, index) in destinosObras" :key="index" :value="destino.id">
                                {{ destino.nombre }}
                            </option>
                        </select>
                        <label
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                        for="select_destino_obra"
                        :class="{
                            'scale-85 -translate-y-9': destinoObra || isFocusedDestinoObra,
                            'scale-90 translate-y-[-11px]': !destinoObra && !isFocusedDestinoObra
                        }"
                        :style="{
                            top: destinoObra || isFocusedDestinoObra ? '25px' : '24px',
                        }"
                        >
                        <span class="hidden sm:block"> Destino de la obra</span>
                        <span class="block sm:hidden">Dest. obra</span>
                        </label>
                    </div>
                </div>
            </div>
            <div v-if="activeTab == 'croquis'" class="space-y-4">
                <h3 
                    class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                    <span>Croquis de localización</span>
                </h3>
                <div class="relative">
                    <div
                    ref="dropZoneCroquis"
                    v-if="!isFileLoaded"
                    class="flex items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 cursor-pointer"
                    @click="triggerFileInput"
                    @dragover.prevent="handleDragOver"
                    @drop.prevent="handleDrop">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg
                            class="w-8 h-8 mb-4 text-color1-500 dark:text-color1-400"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 16">
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                            </svg>
                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold">Click para subir</span> o arrastra y suelta
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                            PNG, JPG o JPEG
                            </p>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center mb-10">
                        <div class="relative w-16 h-16 cursor-pointer" @click="showPreviewDialog">
                            <component :is="fileIcon" class="w-full h-full"></component>
                            <button
                            class="absolute top-0 right-0 w-4 h-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center focus:outline-none"
                            @click.stop="removeFile"
                            title="Eliminar imagen"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            </button>
                        </div>
                        <div class="ml-4">
                            <span class="font-semibold" style="font-style: italic;">{{ fileName }}</span>
                        </div>
                    </div>
                    <input type="file" class="hidden" ref="fileInput" @change="handleFileChange" accept="image/png, image/jpeg, image/jpg" />
                </div>   
            </div>
            <template #footer>
                <div class="flex justify-center mb-4">                    
                    <button v-if="paraEditarSolicitud"
                        type="button" class="mt-0 bg-color2-700 hover:bg-color2-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color2-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color2-700"
                        @click="actualizarSolicitud">
                        Actualizar
                    </button>
                    <button v-else
                        type="button" class="mt-0 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                        @click="agregarSolicitud">
                        Guardar
                    </button>
                    <button
                        type="button" class="mt-0 ml-4 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                        @click="handleClose">
                        Cerrar
                    </button>
                </div>
            </template>
        </el-dialog>

        <el-dialog
            v-model="previewDialogVisible"
            title="Vista previa del CROQUIS"
            class="flex flex-col items-center"
            style="width: 500px; max-width: 90vw;">
            <div v-if="imageSrc" class="flex justify-center mb-4">
                <img
                :src="imageSrc"
                alt="Vista previa"
                class="max-w-full h-auto"
                style="max-width: 400px; max-height: 300px;"
                />
            </div>
            <div v-else-if="archivoInvalido" class="flex items-center space-x-4">
                <span 
                    class="flex-shrink-0 text-color1-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </span>
                <span class="text-left text-color1-600 bg-color1-50 px-4 py-2 rounded-r-lg border-l-8 border-color1-600 inline-block">
                    <span class="text-xl block font-bold">Tipo de archivo inválido</span>
                    <span class="block">No se puede mostrar la vista previa para este tipo de archivo.</span>
                </span>
            </div>
            <template v-if="imageSrc" #footer>
                <div class="dialog-footer flex justify-center gap-4">
                    <button
                        type="button" class="mt-2 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                        @click="acceptFile">
                        Aceptar
                    </button>
                    <button
                        type="button" class="mt-2 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                        @click="rejectFile">
                        Descartar
                    </button>
                </div>
            </template>
        </el-dialog>

    <el-dialog v-model="modalBuscarSolicitanteVisible">

    </el-dialog>

    <el-dialog 
    v-model="modalBuscarColoniaVisible"
    title="Buscar colonia" 
    top="30vh"
    :width="dialogWidthBuscar">
        <div v-if="isLoadingModal2"
            style="position: fixed; 
                top: 0; 
                left: 0; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                z-index: 50; 
                background-color: rgba(255, 255, 255, 0.7); 
                width: 100vw; 
                height: 100vh;">
                <svg class="animate-spin" 
                    style="width: 3rem; height: 3rem; color: #4B5563;" 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24">
                    <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4;" cx="12" cy="12" r="10"></circle>
                    <path style="opacity: 1; stroke: currentColor; stroke-linecap: round; stroke-width: 4;" 
                        d="M4 12a8 8 0 018-8"></path>
                </svg>
            <span style="margin-left: 0.5rem; color: #4B5563; font-size: 1rem;">Cargando...</span>
        </div>
        <div class="relative w-full">
            <input
            v-model="nombreColoniaBuscar"
            ref="floatingNombreColoniaRef"
            type="text" 
            placeholder="Escribe el NOMBRE de la COLONIA"
            @input="debouncedFiltrarColonias"
            class="block w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-color1 focus:border-color1 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:placeholder-gray-400"/>

            <button
                v-if="nombreColoniaBuscar.length >= 5 && coloniasFiltradas && coloniasFiltradas.length === 0"
                type="button"
                @click="agregarColonia"
                class="absolute inset-y-0 right-0 px-4 text-white bg-color1 rounded-r-lg hover:bg-color1-600 focus:ring-2 focus:ring-color1">
                +
            </button>
        </div>
        <div class="w-full mt-2 overflow-x-auto">
            <table class="min-w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <template v-if="coloniasFiltradas && coloniasFiltradas.length > 0">
                    RESULTADOS DE LA BÚSQUEDA
                    </template>
                </thead>
                <tbody>
                    <tr v-if="coloniasFiltradas && coloniasFiltradas.length > 0" 
                    v-for="colonia in coloniasFiltradas" :key="colonia.id" 
                    class="hover:text-color1-800 hover:font-bold hover:bg-color1-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-300 dark:border-gray-600" 
                    @click="seleccionaColonia(colonia.id, colonia.nombre)">
                        <td class="px-4 py-2 w-1/2">{{ colonia.nombre }}</td>
                    </tr>
                    <tr v-else>
                        <td v-if="coloniasFiltradas" colspan="2" class="px-4 py-6 text-sm text-gray-400 dark:text-gray-300">
                            <div class="flex items-center space-x-4">
                                <span 
                                    class="flex-shrink-0 text-color4-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                </span>
                                <span 
                                    class="text-left text-color4-600 bg-color4-50 px-4 py-2 rounded-r-lg border-l-4 border-color4-600">
                                    {{ coloniasFiltradas === null 
                                        ? '' 
                                        : (nombreColoniaBuscar.length >= 5 
                                            ? 'No se encontró ninguna colonia con ese nombre. Si quieres agregar ' + nombreColoniaBuscar.toUpperCase().trim() + ' al CATÁLOGO DE COLONIAS del sistema debes seleccionar el botón de +' 
                                            : 'No hay RESULTADOS para mostrar. Si quieres agregar una COLONIA al sistema debes escribir su NOMBRE COMPLETO y seleccionar el botón de + que eventualmente aparecerá.') 
                                    }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </el-dialog>

    <el-dialog 
    v-model="modalBuscarLocalidadVisible"
    title="Buscar localidad" 
    top="30vh"
    :width="dialogWidthBuscar">
        <div v-if="isLoadingModal2"
            style="position: fixed; 
                top: 0; 
                left: 0; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                z-index: 50; 
                background-color: rgba(255, 255, 255, 0.7); 
                width: 100vw; 
                height: 100vh;">
                <svg class="animate-spin" 
                    style="width: 3rem; height: 3rem; color: #4B5563;" 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24">
                    <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4;" cx="12" cy="12" r="10"></circle>
                    <path style="opacity: 1; stroke: currentColor; stroke-linecap: round; stroke-width: 4;" 
                        d="M4 12a8 8 0 018-8"></path>
                </svg>
            <span style="margin-left: 0.5rem; color: #4B5563; font-size: 1rem;">Cargando...</span>
        </div>
        <div class="relative w-full">
            <input
            v-model="nombreLocalidadBuscar"
            ref="floatingNombreLocalidadRef"
            type="text" 
            placeholder="Escribe el NOMBRE de la LOCALIDAD"
            @input="debouncedFiltrarLocalidades"
            class="block w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-color1 focus:border-color1 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:placeholder-gray-400"/>

            <button
                v-if="nombreLocalidadBuscar.length >= 5 && localidadesFiltradas && localidadesFiltradas.length === 0"
                type="button"
                @click="agregarLocalidad"
                class="absolute inset-y-0 right-0 px-4 text-white bg-color1 rounded-r-lg hover:bg-color1-600 focus:ring-2 focus:ring-color1">
                +
            </button>
        </div>
        <div class="w-full mt-2 overflow-x-auto">
            <table class="min-w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <template v-if="localidadesFiltradas && localidadesFiltradas.length > 0">
                    RESULTADOS DE LA BÚSQUEDA
                    </template>
                </thead>
                <tbody>
                    <tr v-if="localidadesFiltradas && localidadesFiltradas.length > 0" 
                    v-for="localidad in localidadesFiltradas" :key="localidad.id" 
                    class="hover:text-color1-800 hover:font-bold hover:bg-color1-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-300 dark:border-gray-600" 
                    @click="seleccionaLocalidad(localidad.id, localidad.nombre)">
                        <td class="px-4 py-2 w-1/2">{{ localidad.nombre }}</td>
                    </tr>
                    <tr v-else>
                        <td v-if="localidadesFiltradas" colspan="2" class="px-4 py-6 text-sm text-gray-400 dark:text-gray-300">
                            <div class="flex items-center space-x-4">
                                <span 
                                    class="flex-shrink-0 text-color4-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                </span>
                                <span 
                                    class="text-left text-color4-600 bg-color4-50 px-4 py-2 rounded-r-lg border-l-4 border-color4-600">
                                    {{ localidadesFiltradas === null 
                                        ? '' 
                                        : (nombreLocalidadBuscar.length >= 5 
                                            ? 'No se encontró ninguna localidad con ese nombre. Si quieres agregar ' + nombreLocalidadBuscar.toUpperCase().trim() + ' al CATÁLOGO DE LOCALIDADES del sistema debes seleccionar el botón de +' 
                                            : 'No hay RESULTADOS para mostrar. Si quieres agregar una LOCALIDAD al sistema debes escribir su NOMBRE COMPLETO y seleccionar el botón de + que eventualmente aparecerá.') 
                                    }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </el-dialog>

    <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5">
     <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[95%]">
        <h1 class="text-2xl font-bold">Solicitudes</h1>
        <br>
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
            <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                    <button @click="abreModalSolicitud" type="button" class="mt-2 bg-color1-800 hover:bg-color1-700 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-800">
                        + Nueva
                    </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <!-- ID -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id')">
                                <div class="flex items-center">
                                    <span class="mr-1">FOLIO</span>
                                    <svg v-if="sortColumn === 'id'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <!-- ID -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('fecha_ingreso')">
                                <div class="flex items-center">
                                    <span class="mr-1">FECHA</span>
                                    <svg v-if="sortColumn === 'fecha_ingreso'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <!-- Solicitante -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_solicitante')">
                                <div class="flex items-center">
                                    <span class="mr-1">Solicitante</span>
                                    <svg v-if="sortColumn === 'id_solicitante'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <!-- Propiedad -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_propiedad')">
                                <div class="flex items-center">
                                    <span class="mr-1">Propiedad</span>
                                    <svg v-if="sortColumn === 'id_propiedad'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <!-- Trámite -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_tramite')">
                                <div class="flex items-center">
                                    <span class="mr-1">Trámite(s)</span>
                                    <svg v-if="sortColumn === 'id_tramite'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <!-- Estatus -->
                            <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_estatus')">
                                <div class="flex items-center">
                                    <span class="mr-1">Estatus</span>
                                    <svg v-if="sortColumn === 'id_estatus'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                    <path v-if="sortDirection === 'asc'" fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="solicitud in solicitudes" :key="solicitud.id" class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                            <th scope="row" class="px-4 py-2 font-normal w-5"> {{ (solicitud.id % 10000).toString().padStart(4, '0') }}
                            </th>
                            <td class="px-4 py-2 w-5">{{ formatDate(solicitud.fecha_ingreso) }}</td>
                            <td class="px-4 py-2 w-60">{{ solicitud.solicitante.persona.nombre + ' ' + solicitud.solicitante.persona.apellidos }}</td>
                            <td class="px-4 py-2 w-50">
                                {{ solicitud.propiedad.calle || 'SIN DOMICILIO' }}
                                <template v-if="solicitud.propiedad.calle && solicitud.propiedad.numero">
                                {{ solicitud.propiedad.numero }} ::
                                </template>
                                <template v-else-if="solicitud.propiedad.numero">
                                {{ solicitud.propiedad.numero }}
                                </template>

                                <template v-if="solicitud.propiedad.colonia && solicitud.propiedad.colonia.nombre">
                                - {{ solicitud.propiedad.colonia.nombre }}
                                </template>

                                <template v-if="solicitud.propiedad.localidad && solicitud.propiedad.localidad.nombre">
                                {{ (solicitud.propiedad.colonia && solicitud.propiedad.colonia.nombre) ? ' - ' : '' }} {{ solicitud.propiedad.localidad.nombre }}
                                </template>
                            </td>
                            <td class="px-4 py-2">
                                <ul v-if="solicitud.tramites && solicitud.tramites.length > 0">
                                    <li v-for="sTramites in solicitud.tramites" :key="sTramites.id"
                                        class="before:content-['▪'] before:mr-2 before:text-gray-600">
                                        {{ sTramites.tramite.nombre_abreviado }}
                                    </li>
                                </ul>
                                <div v-else class="text-gray-500">SIN TRÁMITES</div>
                            </td>
                            <td class="px-4 py-2 w-60">
                                <span>
                                    <span :style="{ color: solicitud.estatus.color }">●</span>
                                    {{ solicitud.estatus.nombre }}
                                </span>
                            </td>
                            <td class="px-4 py-2 flex items-center justify-end relative">
                                <button
                                    @click="toggleDropdown(solicitud.id, $event)"
                                    :id="`dropdown-button-${solicitud.id}`"
                                    class="inline-flex items-center p-0.5 text-sm font-medium text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                    type="button">
                                    <svg
                                        class="w-5 h-5"
                                        aria-hidden="true"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    </svg>
                                </button>
                                <div
                                    v-show="dropdownVisible === solicitud.id"
                                    :id="'dropdown-' + solicitud.id"
                                    class="absolute right-0 mb-3 z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow-xl dark:bg-gray-700 dark:divide-gray-600">
                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                    <li class="hover:bg-gray-100 dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white">
                                        <button
                                        @click="abreModalEditarSolicitud(solicitud)"
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
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
            <div class="flex items-center">
                <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-300">Cargando...</span>
            </div>
        </div>
    </div>
    </section>
</template>
