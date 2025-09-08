<script setup>
    import { router, usePage } from '@inertiajs/vue3'
    import { ref, computed, watch, onMounted, onUnmounted, onBeforeUnmount, markRaw, nextTick, onBeforeMount } from 'vue'
    import Pagination from '@/Components/Pagination.vue'
    import Swal from 'sweetalert2'

    const props = defineProps({
        solicitudes: Array,
        estatusSolicitud: Array,
        pagination: Object,
        tiposPropiedades: Array,
        destinosObras: Array,
        sectores: Array,
        tiposTramites: Array,
        tramites: Array,
        localidades: Array,
        nombreQuery: String,
        fechaInicioQuery: String,
        fechaFinQuery: String,
        idRangoFechasQuery: Number,
        tiposTramitesQuery: Array,
        tramitesQuery: Array,
        filtroChkSolicitudes: Number,
        idRangoFechasQuery: Number,
        sortDirection: String,
        sortColumn: String,
        paraNuevaSolicitud: Boolean,
        periodoActual: Object
    })

    const ID_CONSTANCIA_UBICACION = 18;
    
    const intervalo = ref(null)
    const intervaloMs = 30000 // 👈 ajusta aquí la frecuencia del polling

    //Parámetros para filtrar
    const nombreQuery = ref('')
    const fechaInicioQuery = ref('')
    const fechaFinQuery = ref('')
    const rangoFechasQuery = ref([])
    const tiposTramitesQuery = ref([])
    const tramitesQuery = ref([])
    const estatusQuery = ref([])
    const filtroChkSolicitudes = ref(0)
    const rangoFechasManual = ref(false)
    const rangoFechasShortcut = ref(false)
    const idRangoFechasQuery = ref(99)
    const pickerWrapper = ref(null)
    const isDropZoneCroquisFocused = ref(false); // Nueva variable para el estado del foco

    const resetFiltros = () => {
        tramitesQuery.value = []
        estatusQuery.value = []
    }

    const selectedCountText = computed(() => {
        const total = tramitesQuery.value.length + estatusQuery.value.length
        return total > 0 ? `(${total})` : ''
    })

    const esFolioManual = ref(false)
    const folioManual = ''
    const sortColumn = ref(props.sortColumn || 'fecha_ingreso');
    const sortDirection = ref(props.sortDirection || 'asc');
    const dropdownVisible = ref(null)

    const idSolicitudEditar = ref('')
    const nuevoSolicitante = ref(false)
    const nuevoPropietario = ref(false)
    const nuevaPropiedad = ref(false)
    const nuevaPropiedadReciente = ref(false)
    const fecha_ingreso = ref(null)

    const isLoading = ref(false)
    const isLoadingModal = ref(false)
    const isLoadingModal2 = ref(false)
    const isSavingModal = ref(false)
    const isSearching = ref(false)
    const isProcessingModal = ref(false)
    const paraNuevaSolicitud = ref(false)
    const paraEditarSolicitud = ref(false)
    const dialogVisible = ref(false)
    const modalBuscarPersonaVisible = ref(false)
    const modalBuscarPersonaTipo = ref(null)
    const modalBuscarColoniaVisible = ref(false)
    const modalBuscarLocalidadVisible = ref(false)
    const floatingCURPSolicitanteRef = ref(null)
    const floatingNomSolicitanteRef = ref(null)
    const floatingApeSolicitanteRef = ref(null)
    const floatingEmailSolicitanteRef = ref(null)
    const floatingTelefonoSolicitanteRef = ref(null)

    const floatingCURPPropietarioRef = ref(null)
    const floatingNomPropietarioRef = ref(null)
    const floatingApePropietarioRef = ref(null)
    const floatingEmailPropietarioRef = ref(null)
    const floatingTelefonoPropietarioRef = ref(null)
    const floatingNombrePersonaRef = ref(null)

    const floatingNombreColoniaRef = ref(null)
    const floatingNombreLocalidadRef = ref(null)
    const floatingTipoPropiedadRef = ref(null)
    const floatingSuperficiePropiedadRef = ref(null)
    const floatingSuperficieConstruccionPropiedadRef = ref(null)
    const floatingCallePropiedadRef = ref(null)
    const floatingNumeroPropiedadRef = ref(null)

    const floatingDestinoObraRef = ref(null)
    const floatingSectorRef = ref(null)
    const floatingReferenciaRef = ref(null)

    const estatusSolicitudSelect = ref([])

    const abreModalSolicitud = async () => {
        estatusSolicitudSelect.value = props.estatusSolicitud.filter((item) => item.id > 1 && item.id < 6)

        paraNuevaSolicitud.value = true
        paraEditarSolicitud.value = false
        dialogVisible.value = true
        claveCatastralEditable.value = true
        claveCatastralCompleta.value = false
        curpPropietarioEditable.value = true
        curpSolicitanteEditable.value = true
        curpPropietarioInvalida.value = false
        curpSolicitanteInvalida.value = false
        cambiaTabError.value = false

        resetFormData()

        activeTab.value = 'tramite'
        isLoading.value = false
        isLoadingModal2.value = false

        setTimeout(() => {
            claveCatastralRef.value?.focus()
        }, 50)
    }

    const cargaDatosSolicitud = (solicitud) => {
        idSolicitudEditar.value = solicitud.id //FOLIO
        idContactoSolicitud.value = solicitud.id_contacto
        idPropiedadSolicitud.value = solicitud.id_propiedad
        idEstatusSolicitud.value = solicitud.id_estatus
        nombreEstatusSolicitud.value = solicitud.estatus.nombre
        colorEstatusSolicitud.value = solicitud.estatus.color
        fecha_ingreso.value = solicitud.fecha_ingreso
        idDestinoObra.value = solicitud.id_destino_obra
        nombreDestinoObra.value = solicitud.destino_obra?.nombre
        idSector.value = solicitud.id_sector
        nombreSector.value = solicitud.sector_tramite?.nombre

        esSolicitante.value = solicitud.id_solicitante === solicitud.id_propietario ? '1' : '0'
    }

    const cargaDatosPropiedad = (propiedad) => {
        if (propiedad)
        {
            claveCatastralCompleta.value = true
            claveCatastral.value = propiedad.clave_catastral
            tipoPropiedad.value = propiedad.id_tipo
            nombreTipoPropiedad.value = propiedad?.tipo?.nombre
            superficiePropiedad.value = propiedad?.superficie
            superficieConstruccionPropiedad.value = propiedad?.superficie_construccion
            callePropiedad.value = propiedad?.calle
            numeroPropiedad.value = propiedad?.numero
            idColoniaPropiedad.value = propiedad?.id_colonia
            nombreColoniaPropiedad.value = propiedad?.colonia?.nombre
            idLocalidadPropiedad.value = propiedad?.id_localidad
            nombreLocalidadPropiedad.value = propiedad?.localidad?.nombre
            imgCroquisPropiedad.value = propiedad?.img_croquis
            idContactoPropiedad.value = propiedad?.id_contacto
            propiedadEsEditable.value = propiedad?.editable 

            if (imgCroquisPropiedad.value == null) 
            {
                isFileLoaded.value = false
                file.value = null
                fileName.value = ''
                imageSrc.value = ''
            } 
            // else 
            // {
            //     isFileLoaded.value = true
            //     const fileIconTemplate = {
            //         template: `
            //     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
            //         <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
            //     </svg> `
            //     }
            //     fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
            //     fileName.value = imgCroquisPropiedad.value
            //     imageSrc.value = '/storage/croquis/' + fileName.value
            // }
        }
    }

    const cargaDatosPersonaPropietario = (persona) => {
        nomPropietario.value = persona.nombre
        apePropietario.value = persona.apellidos
    }

    const cargaDatosPropietario = (propietario) => {
        if (propietario) 
        {
            idPersonaPropietario.value = propietario.id_persona
            curpPropietario.value = propietario.persona?.curp
            nomPropietario.value = propietario.persona?.nombre
            apePropietario.value = propietario.persona?.apellidos
            telefonoPropietario.value = propietario.telefono
            emailPropietario.value = propietario.email
            curpPropietarioInvalida.value = false
        } 
        else 
        {
            inicializaPropietario()
        }
    }

    const cargaDatosPropietarioIntegridad = (persona) => {
        if (persona) 
        {
            idPersonaPropietario.value = persona.id
            curpPropietario.value = persona.curp
            nomPropietario.value = persona.nombre
            apePropietario.value = persona.apellidos
            telefonoPropietario.value = ''
            emailPropietario.value = ''
        } 
    }

    const cargaDatosSolicitante = (solicitante) => {
        idPersonaSolicitante.value = solicitante.id_persona
        curpSolicitante.value = solicitante.persona.curp
        nomSolicitante.value = solicitante.persona.nombre
        apeSolicitante.value = solicitante.persona.apellidos
        calleSolicitante.value = solicitante.calle
        numeroSolicitante.value = solicitante.num_casa
        idColoniaSolicitante.value = solicitante.id_colonia
        nombreColoniaSolicitante.value = solicitante.colonia?.nombre
        idLocalidadSolicitante.value = solicitante.id_localidad
        nombreLocalidadSolicitante.value = solicitante.localidad?.nombre
        telefonoSolicitante.value = solicitante.telefono
        emailSolicitante.value = solicitante.email
    }

    const cargaDatosReferencia = (ref) => {
        if (ref) 
        {
            referencia.value = ref.contenido
        } 
    }

    const cargaDatosRazonSocial = (razSoc) => {
        if (razSoc) 
        {
            razonSocialSolicitante.value = razSoc.nombre
            solicitaOrganizacion.value = true
        }
        else
        {
            solicitaOrganizacion.value = false
        }
    }

    const refrescaCroquis = () => {
        if (tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
        { 
            if (imgCroquisAux.value) 
            {
                isFileLoaded.value = true
                const fileIconTemplate = {
                    template: `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
                </svg> `
                }
                fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
                fileName.value = imgCroquisAux.value
                imageSrc.value = '/storage/croquis/' + fileName.value
            }
            else
            {
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
            }
        }
        else
        {
            if (imgCroquisPropiedad.value)
            {
                isFileLoaded.value = true
                const fileIconTemplate = {
                    template: `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
                </svg> `
                }
                fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
                fileName.value = imgCroquisPropiedad.value
                imageSrc.value = '/storage/croquis/' + fileName.value
            }
            else
            {
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
            }
        }
    }

    const cargaDatosCroquis = (solicitud) => {
        if (solicitud.croquis_aux) 
        {
            if (solicitud.croquis_aux.img)
            {
                isFileLoaded.value = true
                imgCroquisAux.value = solicitud.croquis_aux.img
            }
            else
            {
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
            }
        }

         if (solicitud.propiedad)
        {
            if (solicitud.propiedad.img_croquis)
            {
                isFileLoaded.value = true
                imgCroquisPropiedad.value = solicitud.propiedad.img_croquis
            }
            else
            {
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
            }
        }

        if (tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
        { 
            const fileIconTemplate = {
                template: `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
                <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
            </svg> `
            }
            fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
            fileName.value = imgCroquisAux.value
            imageSrc.value = '/storage/croquis/' + fileName.value
        }
        else
        {
           
            const fileIconTemplate = {
                template: `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
                <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
            </svg> `
            }
            fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
            fileName.value = imgCroquisPropiedad.value
            imageSrc.value = '/storage/croquis/' + fileName.value
        }
    }

    const editablesSolicitud = (valor) => {
        idDestinoObraEditable.value = valor
        idSectorEditable.value = valor
    }

    const editablesPropiedad = (valor) => {
        tipoPropiedadEditable.value = valor
        superficiePropiedadEditable.value = valor
        superficieConstruccionPropiedadEditable.value = valor
        callePropiedadEditable.value = valor
        numeroPropiedadEditable.value = valor
        idColoniaPropiedadEditable.value = valor
        idLocalidadPropiedadEditable.value = valor
    }

    const editablesPersonaPropietario = (valor) => {
        nomPropietarioEditable.value = valor
        apePropietarioEditable.value = valor
    }

    const editablesPropietario = (valor) => {
        telefonoPropietarioEditable.value = valor
        emailPropietarioEditable.value = valor
    }

    const editablesPersonaSolicitante = (valor) => {
        nomSolicitanteEditable.value = valor
        apeSolicitanteEditable.value = valor
    }

    const editablesSolicitante = (valor) => {
        telefonoSolicitanteEditable.value = valor
        emailSolicitanteEditable.value = valor
    }

    const editablesReferencia = (valor) => {
        referenciaEditable.value = valor
    }

    const bloqueadosSolicitud = (valor) => {
        idDestinoObraBloqueado.value = valor
        idSectorBloqueado.value = valor
    }

    const bloqueadosPersonaPropietario = (valor) => {
        nomPropietarioBloqueado.value = valor
        apePropietarioBloqueado.value = valor
    }

    const bloqueadosPersonaSolicitante = (valor) => {
        nomSolicitanteBloqueado.value = valor
        apeSolicitanteBloqueado.value = valor
    }

    const bloqueadosPropiedad = (valor) => {
        tipoPropiedadBloqueado.value = valor
        superficiePropiedadBloqueado.value = valor
        superficieConstruccionPropiedadBloqueado.value = valor
        callePropiedadBloqueado.value = valor
        numeroPropiedadBloqueado.value = valor
        idColoniaPropiedadBloqueado.value = valor
        idLocalidadPropiedadBloqueado.value = valor
    }

    const bloqueadosPropietario = (valor) => {
        telefonoPropietarioBloqueado.value = valor
        emailPropietarioBloqueado.value = valor
    }

    const bloqueadosSolicitante = (valor) => {
        telefonoSolicitanteBloqueado.value = valor
        emailSolicitanteBloqueado.value = valor
        razonSocialSolicitanteBloqueado.value = valor
    }

    const bloqueadosReferencia = (valor) => {
        referenciaBloqueado.value = valor
    }

    const imprimirSolicitud = async (solicitud) => {
        isLoading.value = true

        const urlParams = new URLSearchParams(window.location.search)
        const currentPage = urlParams.get('page') || 1

        dropdownVisible.value = null

        // Determinar si la solicitud está finalizada
        const esFinalizada = solicitud.id_estatus === 99

        // Ruta POST que prepara la impresión (valida, registra, etc.)
        const rutaPrepare = esFinalizada ? `/solicitudes/print-pdf/${solicitud.id}` : `/solicitudes/print-preview-pdf/${solicitud.id}`

        // Ruta GET que devuelve el PDF para abrir en nueva pestaña
        const rutaPDF = esFinalizada ? `/solicitudes/print-pdf/${solicitud.id}` : `/solicitudes/print-preview-pdf/${solicitud.id}`

        try {
            await router.post(
                rutaPrepare,
                {},
                {
                    onStart: () => {
                        // Aquí podrías activar un spinner o deshabilitar botones
                    },
                    onSuccess: () => {
                        // Abrir el PDF en nueva pestaña
                        window.open(rutaPDF, '_blank')
                        isLoading.value = false

                        // Volver a cargar la vista actual con los filtros (sin recargar todo)
                        router.post(
                            '/solicitudes',
                            {
                                fechaInicioQuery: fechaInicioQuery.value,
                                fechaFinQuery: fechaFinQuery.value,
                                nombreQuery: nombreQuery.value,
                                tiposTramitesQuery: tiposTramitesQuery.value,
                                tramitesQuery: tramitesQuery.value,
                                estatusQuery: estatusQuery.value,
                                filtroChkSolicitudes: filtroChkSolicitudes.value,
                                sortColumn: sortColumn.value,
                                sortDirection: sortDirection.value,
                                page: currentPage
                            },
                            {
                                preserveState: true,
                                preserveScroll: true,
                                replace: true
                            }
                        )
                    },
                    onError: (errors) => {
                        isLoading.value = false
                        // Aquí puedes mostrar un mensaje de error al usuario
                    },
                    onFinish: () => {
                        // Aquí podrías ocultar el spinner o habilitar botones
                    }
                }
            )
        } catch (error) {
            console.error('Error inesperado:', error)
            // Manejo general de errores
        }
    }

    // ASÍ FUNCIONA CUANDO NO QUIERO QUE APAREZCA EL ID EN EL URL
    // const imprimirSolicitud = (solicitud) => {
    //     const form = document.createElement('form');
    //     form.method = 'POST';
    //     form.action = '/solicitudes/print-pdf';
    //     form.target = '_blank'; // Abre en nueva pestaña

    //     // CSRF token
    //     const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    //     const csrfInput = document.createElement('input');
    //     csrfInput.type = 'hidden';
    //     csrfInput.name = '_token';
    //     csrfInput.value = token;
    //     form.appendChild(csrfInput);

    //     // ID de la solicitud
    //     const inputId = document.createElement('input');
    //     inputId.type = 'hidden';
    //     inputId.name = 'id';
    //     inputId.value = solicitud.id;
    //     form.appendChild(inputId);

    //     document.body.appendChild(form);
    //     form.submit();
    //     document.body.removeChild(form);
    // };

    const abreModalEditarSolicitud = async (solicitud) => {
        resetFormData()
        isLoading.value = true
        isLoadingModal.value = true
        isUploading.value = false
        isProcessingFile.value = false

        const response = await fetch(`/solicitudes/get-solicitud/${encodeURIComponent(solicitud.id)}`, {
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
        solicitud = data.solicitud // Aquí se almacenan los datos en la variable puestos

        dropdownVisible.value = null

        paraNuevaSolicitud.value = false
        paraEditarSolicitud.value = true
        nuevaPropiedad.value = false
        esSolicitanteEditable.value = true
        idEstatusSolicitudEditable.value = true
        cambiaTabError.value = false
        solicitudBloqueada.value = false
        claveCatastralEditable.value = true
        curpPropietarioEditable.value = true
        curpSolicitanteEditable.value = true

        estatusSolicitudSelect.value = props.estatusSolicitud.filter((item) => item.id > 1)

        tramitesSeleccionados.value = solicitud.tramites.map((tramite) => tramite.id_tramite)

        bloqueadosPersonaPropietario(!solicitud.propiedad?.contacto.persona.editable)
        bloqueadosPersonaSolicitante(!solicitud.contacto.persona.editable)
        bloqueadosSolicitud(false)
        bloqueadosPropiedad(false)
        bloqueadosPropietario(false)
        bloqueadosSolicitante(false)
        bloqueadosReferencia(false)

        cargaDatosSolicitud(solicitud)
        cargaDatosPropiedad(solicitud.propiedad)
        cargaDatosPropietario(solicitud.propiedad?.contacto)
        idContactoPropiedadAlCargar.value = solicitud.propiedad?.contacto.id
        cargaDatosSolicitante(solicitud.contacto)
        cargaDatosRazonSocial(solicitud.razon_social)
        cargaDatosReferencia(solicitud.referencia)
        cargaDatosCroquis(solicitud)

        esSolicitante.value = '1'
        if (solicitud.contacto.id_persona != solicitud.propiedad?.contacto.id_persona || tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
        {
            esSolicitante.value = '0'
        }

        if (idEstatusSolicitud.value >= 6) {
            solicitudBloqueada.value = true
            curpPropietarioEditable.value = false
            claveCatastralEditable.value = false
            idEstatusSolicitudEditable.value = false
            idDestinoObraEditable.value = false
            idSectorEditable.value = false
            curpSolicitanteEditable.value = false
            esSolicitanteEditable.value = false
            referenciaEditable.value = false
            razonSocialSolicitanteEditable.value = false
            bloqueadosSolicitud(true)
            bloqueadosPropiedad(true)
            bloqueadosPropietario(true)
            bloqueadosSolicitante(true)
            bloqueadosPersonaPropietario(true)
            bloqueadosPersonaSolicitante(true)
            bloqueadosReferencia(true)
            editablesSolicitud(false)
        }
        else
        {
            razonSocialSolicitanteEditable.value = true
        }

        if (curpPropietario.value.length >= LONGITUD_CURP.value) {
            curpPropietarioCompleta.value = true
        }

        if (!curpRegex.test(curpPropietario.value)) {
            curpPropietarioInvalida.value = true
            curpPropietarioCompleta.value = false
        }

        if (curpSolicitante.value.length >= LONGITUD_CURP.value) {
            curpSolicitanteCompleta.value = true
        }

        if (!curpRegex.test(curpSolicitante.value)) {
            curpSolicitanteInvalida.value = true
            curpSolicitanteCompleta.value = false
        }

        if (curpSolicitante.value.length == 0) {
            curpSolicitanteInvalida.value = false
            curpSolicitanteCompleta.value = false
        }

        editablesSolicitud(false)
        editablesPropiedad(false)
        editablesPersonaPropietario(false)
        editablesPropietario(false)
        editablesPersonaSolicitante(false)
        editablesSolicitante(false)
        editablesReferencia(false)

        nuevoPropietario.value = false
        nuevaPropiedad.value = false
        nuevaPropiedadReciente.value = false
        nuevoSolicitante.value = false
        activeTab.value = 'tramite'
        isLoadingModal.value = false
        isLoading.value = false
        dialogVisible.value = true

        setTimeout(() => {
            claveCatastralRef.value?.focus()
        }, 50)

        fetchSolicitudes()
    }

    const dialogWidth = computed(() => {
        return window.innerWidth < 1024 ? '95%' : '55%'
    })

    const dialogWidthBuscar = computed(() => {
        return window.innerWidth < 1024 ? '95%' : '35%'
    })

    const inicializaSolicitante = () => {
        nomSolicitante.value = ''
        apeSolicitante.value = ''
        telefonoSolicitante.value = ''
        emailSolicitante.value = ''
        calleSolicitante.value = ''
        numeroSolicitante.value = ''
        idColoniaSolicitante.value = ''
        nombreColoniaSolicitante.value = ''
        idLocalidadSolicitante.value = ''
        nombreLocalidadSolicitante.value = ''
    }

    const inicializaPropietario = () => {
        idPersonaPropietario.value = null
        nomPropietario.value = ''
        apePropietario.value = ''
        telefonoPropietario.value = ''
        emailPropietario.value = ''
        callePropietario.value = ''
        numeroPropietario.value = ''
        idColoniaPropietario.value = ''
        nombreColoniaPropietario.value = ''
        idLocalidadPropietario.value = ''
        nombreLocalidadPropietario.value = ''
        idContactoPropiedad.value = ''
    }

    const inicializaPropiedad = () => {
        propiedad.value = []
        tipoPropiedad.value = ''
        nombreTipoPropiedad.value = ''
        superficiePropiedad.value = ''
        superficieConstruccionPropiedad.value = ''
        callePropiedad.value = ''
        numeroPropiedad.value = ''
        idColoniaPropiedad.value = ''
        nombreColoniaPropiedad.value = ''
        imgCroquisPropiedad.value = ''
        //idLocalidadPropiedad.value = '';
        //nombreLocalidadPropiedad.value = '';
    }

    const inicializaSolicitud = () => {
        idEstatusSolicitudEditable.value = true
        const today = new Date()
        const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().split('T')[0]
        fecha_ingreso.value = localDate
        tramitesSeleccionados.value = []
        imageSrc.value = ''
        isFileLoaded.value = false
        file.value = null
        fileName.value = ''
        // idDestinoObra.value = ''
        // idSector.value = ''
        solicitudBloqueada.value = false
        esSolicitante.value = '1'
        idDestinoObra.value = 1
        idSector.value = 1
    }

    const resetFormData = () => {
        claveCatastral.value = CVE_MUNICIPIO.value + '  000'
        idEstatusSolicitud.value = 2

        //   id_contacto de Propiedad: <strong>${propiedad.value?.id_contacto}</strong> <br>
        //             idContactoPropiedad: <strong>${idContactoPropiedad.value}</strong>

        inicializaPropietario()
        curpPropietario.value = ''
        curpPropietarioEditable.value = true
        curpPropietarioCompleta.value = false
        nuevoPropietario.value = false
        inicializaPropiedad()
        nuevaPropiedad.value = false
        inicializaSolicitante()
        curpSolicitante.value = ''
        curpSolicitanteEditable.value = true
        curpSolicitanteCompleta.value = false
        nuevoSolicitante.value = false
        razonSocialSolicitante.value = ''
        solicitaOrganizacion.value = false
        inicializaSolicitud()
        referencia.value = ''
        imgCroquisAux.value = ''

        if (paraNuevaSolicitud.value) {
            editablesSolicitud(true)
            editablesPropiedad(true)
            editablesPropietario(true)
            editablesSolicitante(true)
            editablesPersonaPropietario(true)
            editablesPersonaSolicitante(true)
            editablesReferencia(true)

            bloqueadosPropiedad(true)
            bloqueadosPropietario(true)

            esFolioManual.value = false
        }
    }

    const handleClose = () => {
        dialogVisible.value = false
    }

    const isFocusedTipoPropiedad = ref(false)

    const handleFocusTipoPropiedad = (status) => {
        isFocusedTipoPropiedad.value = status // SELECT - Cambia el estado del foco
    }

    const isFocusedDestinoObra = ref(false)

    const handleFocusDestinoObra = (status) => {
        isFocusedDestinoObra.value = status // SELECT - Cambia el estado del foco
    }

    const isFocusedSector = ref(false)

    const handleFocusSector = (status) => {
        isFocusedSector.value = status // SELECT - Cambia el estado del foco
    }

    const abreModalBuscarPersona = async (tipoPersona) => {
        personasFiltradas.value = null
        nombrePersonaBuscar.visible = ''
        modalBuscarPersonaVisible.value = true
        modalBuscarPersonaTipo.value = tipoPersona

        setTimeout(() => {
            floatingNombrePersonaRef.value?.focus()
        }, 50)
    }

    const solicitudBloqueada = ref(false)
    const idEstatusSolicitud = ref('')
    const nombreEstatusSolicitud = ref('')
    const colorEstatusSolicitud = ref('')
    const idContactoSolicitud = ref('')
    const idPropiedadSolicitud = ref('')
    const imgCroquisPropiedad = ref('')
    const imgCroquisAux = ref('')
    const idDestinoObra = ref('')
    const idSector = ref('')
    const idEstatusSolicitudEditable = ref(true)

    const curpSolicitanteCompleta = ref(false)
    const curpSolicitanteInvalida = ref(false)
    const persona = ref([])
    const curpSolicitante = ref('')
    const idPersonaSolicitante = ref('')
    const nomSolicitante = ref('')
    const apeSolicitante = ref('')
    const calleSolicitante = ref('')
    const numeroSolicitante = ref('')
    const idColoniaSolicitante = ref('')
    const nombreColoniaSolicitante = ref('')
    const idLocalidadSolicitante = ref('')
    const nombreLocalidadSolicitante = ref('')
    const telefonoSolicitante = ref('')
    const emailSolicitante = ref('')
    const razonSocialSolicitante = ref('')
    const curpSolicitanteEditable = ref(true)
    const nomSolicitanteEditable = ref(true)
    const apeSolicitanteEditable = ref(true)
    const telefonoSolicitanteEditable = ref(true)
    const emailSolicitanteEditable = ref(true)
    const razonSocialSolicitanteEditable = ref(true)

    const curpSolicitanteBloqueado = ref(true)
    const nomSolicitanteBloqueado = ref(true)
    const apeSolicitanteBloqueado = ref(true)
    const telefonoSolicitanteBloqueado = ref(true)
    const emailSolicitanteBloqueado = ref(true)
    const razonSocialSolicitanteBloqueado = ref(true)
    const solicitaOrganizacion = ref(false)

    const LONGITUD_CURP = ref(18)

    const idPersonaPropietario = ref('')
    const curpPropietarioCompleta = ref(false)
    const curpPropietarioInvalida = ref(false)
    const curpPropietario = ref('')
    const nomPropietario = ref('')
    const apePropietario = ref('')
    const callePropietario = ref('')
    const numeroPropietario = ref('')
    const idColoniaPropietario = ref('')
    const nombreColoniaPropietario = ref('')
    const idLocalidadPropietario = ref('')
    const nombreLocalidadPropietario = ref('')
    const telefonoPropietario = ref('')
    const emailPropietario = ref('')
    const esSolicitante = ref('1')
    const esSolicitanteEditable = ref(true)
    const curpPropietarioEditable = ref(true)
    const nomPropietarioEditable = ref(true)
    const apePropietarioEditable = ref(true)
    const telefonoPropietarioEditable = ref(true)
    const emailPropietarioEditable = ref(true)

    const curpPropietarioBloqueado = ref(true)
    const nomPropietarioBloqueado = ref(true)
    const apePropietarioBloqueado = ref(true)
    const telefonoPropietarioBloqueado = ref(true)
    const emailPropietarioBloqueado = ref(true)

    const propiedad = ref([])
    const tipoPropiedad = ref('')
    const nombreTipoPropiedad = ref('')
    const claveCatastral = ref('')
    const claveCatastralCompleta = ref(false)
    const superficiePropiedad = ref('')
    const superficieConstruccionPropiedad = ref('')
    const callePropiedad = ref('')
    const numeroPropiedad = ref('')
    const idColoniaPropiedad = ref('')
    const nombreColoniaPropiedad = ref('')
    const idLocalidadPropiedad = ref('')
    const nombreLocalidadPropiedad = ref('')
    const idContactoPropiedad = ref('')
    const idContactoPropiedadAlCargar = ref('')
    const tipoPropiedadEditable = ref(true)
    const claveCatastralEditable = ref(true)
    const superficiePropiedadEditable = ref(true)
    const superficieConstruccionPropiedadEditable = ref(true)
    const callePropiedadEditable = ref(true)
    const numeroPropiedadEditable = ref(true)
    const idColoniaPropiedadEditable = ref(true)
    const idLocalidadPropiedadEditable = ref(true)
    const tipoPropiedadBloqueado = ref(true)
    const claveCatastralBloqueado = ref(true)
    const superficiePropiedadBloqueado = ref(true)
    const superficieConstruccionPropiedadBloqueado = ref(true)
    const callePropiedadBloqueado = ref(true)
    const numeroPropiedadBloqueado = ref(true)
    const idColoniaPropiedadBloqueado = ref(true)
    const idLocalidadPropiedadBloqueado = ref(true)
    const propiedadEsEditable = ref(false)

    const croquis = ref('')

    const referencia = ref('') 
    const referenciaBloqueado = ref(true)
    const referenciaEditable = ref(true)

    const handleCloseModalCroquis = () => {
        previewDialogVisible.value = false
    }

    const tramitesSeleccionados = ref([])
    const idDestinoObraEditable = ref(true)
    const nombreDestinoObra = ref('')
    const idDestinoObraBloqueado = ref(true)

    const idSectorEditable = ref(true)
    const nombreSector = ref('')
    const idSectorBloqueado = ref(true)

    const obtenerPropiedadSolicitud = async (idSolicitud) => {
        try {
            if (dialogVisible.value) {
                isLoadingModal.value = true
                isLoading.value = false
            } else {
                isLoading.value = true
                isLoadingModal.value = false
            }

            const response = await fetch(`/solicitudes/get-propiedad-solicitud/${encodeURIComponent(idSolicitud)}`, {
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
            propiedad.value = data.propiedad // Aquí se almacenan los datos en la variable puestos
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoading.value = false
            isLoadingModal.value = false
        }
    }

    const obtenerPropiedad = async (claveCatastral) => {
        try {
            if (dialogVisible.value) {
                isLoadingModal.value = true
                isLoading.value = false
            } else {
                isLoading.value = true
                isLoadingModal.value = false
            }

            const response = await fetch(`/solicitudes/get-propiedad/${encodeURIComponent(claveCatastral)}`, {
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
            propiedad.value = data.propiedad // Aquí se almacenan los datos en la variable puestos
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoading.value = false
            isLoadingModal.value = false
        }
    }

    const obtenerPersona = async (curp, tipo) => {
        try {
            if (dialogVisible.value) {
                isLoadingModal.value = true
                isLoading.value = false
            } else {
                isLoading.value = true
                isLoadingModal.value = false
            }

            const response = await fetch(`/solicitudes/get-persona/${encodeURIComponent(curp)}?tipo=${encodeURIComponent(tipo)}`, {
                // Agrega las comillas
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            if (!response.ok) {
                const errorData = await response.json() // Intenta obtener más detalles del error del servidor
                const errorMessage = errorData?.message || `Error en la respuesta del servidor: ${response.status} ${response.statusText}`
                throw new Error(errorMessage)
            }

            const data = await response.json()
            persona.value = data.persona // Aquí se almacenan los datos en la variable puestos
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoading.value = false
            isLoadingModal.value = false
        }
    }

    watch(esSolicitante, async (newValue) => {
        if (newValue == 0) {
            if (!curpSolicitanteCompleta.value) {
                inicializaSolicitante()
            }

            if (paraEditarSolicitud.value && (idContactoSolicitud.value == idContactoPropiedad.value)) {
                curpSolicitante.value = ''
                curpSolicitanteCompleta.value = false
                inicializaSolicitante()
            }
            // curpSolicitante.value = ''
            // curpSolicitanteCompleta.value = false
        }
    })

    // Expresión regular para validar CURP
    const curpRegex =
        /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/

    const inputCurpSolicitante = async (event) => {
        if (event.isTrusted) {
            //Sólo si se capturó/copió la clave catastral
            if (curpSolicitante.value.length >= LONGITUD_CURP.value) {
                if (curpSolicitante.value.length > LONGITUD_CURP.value) {
                    curpSolicitanteInvalida.value = true
                    curpSolicitanteCompleta.value = false
                    return
                }
                if (!curpRegex.test(curpSolicitante.value)) {
                    curpSolicitanteInvalida.value = true
                    curpSolicitanteCompleta.value = false
                    return
                }

                isLoadingModal.value = true // Muestra el cargador
                await obtenerPersona(curpSolicitante.value, 'solicitante')
                curpSolicitanteCompleta.value = true
                curpSolicitanteInvalida.value = false

                if (persona.value) {
                    idContactoSolicitud.value = persona.value.solicitante?.id || null
                    idPersonaSolicitante.value = persona.value.id
                    nomSolicitante.value = persona.value.nombre
                    apeSolicitante.value = persona.value.apellidos
                    telefonoSolicitante.value = persona.value.solicitante?.telefono || null
                    emailSolicitante.value = persona.value.solicitante?.email || null
                    // calleSolicitante.value = persona.value.solicitante?.calle || null
                    // numeroSolicitante.value = persona.value.solicitante?.num_casa || null
                    // idColoniaSolicitante.value = persona.value.solicitante?.id_colonia || null
                    // idLocalidadSolicitante.value = persona.value.solicitante?.id_localidad || null
                    // nombreColoniaSolicitante.value = persona.value.solicitante?.colonia?.nombre ?? ''
                    // nombreLocalidadSolicitante.value = persona.value.solicitante?.localidad?.nombre ?? ''
                    nuevoSolicitante.value = false

                    if (!solicitudBloqueada.value) {
                        bloqueadosPersonaSolicitante(!persona.value.solicitante?.editable)
                        editablesPersonaSolicitante(false)
                        editablesSolicitante(false)
                        bloqueadosSolicitante(false)
                    }
                } 
                else 
                {
                    nuevoSolicitante.value = true
                    idContactoSolicitud.value = null
                    idPersonaSolicitante.value = ''
                    inicializaSolicitante()
                    editablesPersonaSolicitante(true)
                    bloqueadosPersonaSolicitante(false)
                    editablesSolicitante(true)
                    bloqueadosSolicitante(false)
                    setTimeout(() => {
                        floatingNomSolicitanteRef.value?.focus()
                    }, 50)
                }
                isLoadingModal.value = false
            } 
            else 
            {
                curpSolicitanteInvalida.value = false
                curpSolicitanteCompleta.value = false
            }
        }
    }

    const activeTab = ref('tramite')
    const cambiaTabError = ref(false)

    const nombreColoniaBuscar = ref('')
    const coloniasFiltradas = ref(null)
    const tipoColonia = ref('')
    const tipoLocalidad = ref('')

    const nombrePersonaBuscar = ref('')
    const personasFiltradas = ref(null)

    const focusInputSuperficiePropiedad = () => {
        setTimeout(() => {
            floatingSuperficiePropiedadRef.value?.focus()
        }, 50)
    }

    const focusInputSuperficieConstruccionPropiedad = () => {
        setTimeout(() => {
            floatingSuperficieConstruccionPropiedadRef.value?.focus()
        }, 50)
    }

    const abreModalBuscarColonia = (tipo) => {
        modalBuscarColoniaVisible.value = true

        isLoading.value = false
        isLoadingModal.value = false
        nombreColoniaBuscar.value = ''
        coloniasFiltradas.value = null

        tipoColonia.value = tipo

        setTimeout(() => {
            floatingNombreColoniaRef.value?.focus()
        }, 50)
    }

    function debounce(func, delay) {
        let timeout
        return (...args) => {
            clearTimeout(timeout) // Resetea el temporizador si se vuelve a llamar
            timeout = setTimeout(() => {
                func(...args) // Ejecuta la función tras el retraso
            }, delay)
        }
    }

    const filtrarColonias = async () => {
        isLoadingModal2.value = true // Indicar que está cargando
        try {
            const response = await fetch(`/solicitudes/get-colonias?nombre=${encodeURIComponent(nombreColoniaBuscar.value)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            const data = await response.json()
            coloniasFiltradas.value = data.colonias
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoadingModal2.value = false // Finalizar el estado de carga
        }
    }

    const agregarColonia = async () => {
        isLoadingModal2.value = true
        const formData = new FormData()
        formData.append('nombre', nombreColoniaBuscar.value)

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
                        timerProgressBar: true,
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })
                    modalBuscarColoniaVisible.value = false

                    if (tipoColonia.value == 'solicitante') {
                        idColoniaSolicitante.value = page.props.flash.idColonia
                        nombreColoniaSolicitante.value = nombreColoniaBuscar.value.toUpperCase().trim()
                    } else if (tipoColonia.value == 'propietario') {
                        idColoniaPropietario.value = page.props.flash.idColonia
                        nombreColoniaPropietario.value = nombreColoniaBuscar.value.toUpperCase().trim()
                    } else if (tipoColonia.value == 'propiedad') {
                        idColoniaPropiedad.value = page.props.flash.idColonia
                        nombreColoniaPropiedad.value = nombreColoniaBuscar.value.toUpperCase().trim()
                    }
                    isLoadingModal2.value = false
                },
                onFinish: () => {
                    isLoadingModal2.value = false
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isLoadingModal2.value = false
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
                        width: '600px',
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

    const debouncedFiltrarColonias = debounce(filtrarColonias, 500)

    const seleccionaColonia = (idColonia, nombreColonia) => {
        modalBuscarColoniaVisible.value = false

        if (tipoColonia.value == 'solicitante') 
        {
            idColoniaSolicitante.value = idColonia
            nombreColoniaSolicitante.value = nombreColonia
        } 
        else if (tipoColonia.value == 'propietario') 
        {
            idColoniaPropietario.value = idColonia
            nombreColoniaPropietario.value = nombreColonia
        } 
        else if (tipoColonia.value == 'propiedad') 
        {
            idColoniaPropiedad.value = idColonia
            nombreColoniaPropiedad.value = nombreColonia
        }
    }
    
    const filtrarPersonas = async () => {
        isLoadingModal2.value = true // Indicar que está cargando
        try {
            const response = await fetch(`/solicitudes/get-personas?nombre=${encodeURIComponent(nombrePersonaBuscar.value)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            const data = await response.json()
            personasFiltradas.value = data.personas
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoadingModal2.value = false // Finalizar el estado de carga
        }
    }

    const debouncedFiltrarPersonas = debounce(filtrarPersonas, 500)

    const seleccionaPersona = (persona) => {
        modalBuscarPersonaVisible.value = false

        if (modalBuscarPersonaTipo.value == 'solicitante') {
            idPersonaSolicitante.value = persona.id
            curpSolicitante.value = persona.curp
            curpSolicitanteCompleta.value = true
            nomSolicitante.value = persona.nombre
            apeSolicitante.value = persona.apellidos
            telefonoSolicitante.value = persona.solicitante?.telefono || null
            emailSolicitante.value = persona.solicitante?.email || null                          
            nuevoSolicitante.value = false

            if (!solicitudBloqueada.value) {
                bloqueadosPersonaSolicitante(!persona.solicitante?.editable)
                editablesPersonaSolicitante(false)
                editablesSolicitante(false)
                bloqueadosSolicitante(false)
            }
        } 
        else if (modalBuscarPersonaTipo.value == 'propietario') {
            idPersonaPropietario.value = persona.id
            curpPropietario.value = persona.curp
            curpPropietarioCompleta.value = true
            nomPropietario.value = persona.nombre
            apePropietario.value = persona.apellidos
            telefonoPropietario.value = persona.propietario?.telefono || null
            emailPropietario.value = persona.propietario?.email || null                          
            nuevoPropietario.value = false

            if (!solicitudBloqueada.value) {
                bloqueadosPersonaPropietario(!persona.propietario?.editable)
                editablesPersonaPropietario(false)
                editablesPropietario(false)
                bloqueadosPropietario(false)
            }
        } 
    }

    const nombreLocalidadBuscar = ref('')
    const claveLocalidadBuscar = ref('')
    const localidadesFiltradas = ref(null)

    const abreModalBuscarLocalidad = (tipo) => {
        modalBuscarLocalidadVisible.value = true

        isLoading.value = false
        isLoadingModal.value = false
        nombreLocalidadBuscar.value = ''
        localidadesFiltradas.value = null

        tipoLocalidad.value = tipo

        setTimeout(() => {
            floatingNombreLocalidadRef.value?.focus()
        }, 50)
    }

    const filtrarLocalidades = async () => {
        isLoadingModal2.value = true // Indicar que está cargando
        try {
            const response = await fetch(`/solicitudes/get-localidades?nombre=${encodeURIComponent(nombreLocalidadBuscar.value)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            const data = await response.json()
            localidadesFiltradas.value = data.localidades
        } catch (error) {
            console.error('Error al obtener los datos:', error)
        } finally {
            isLoadingModal2.value = false // Finalizar el estado de carga
        }
    }

    const agregarLocalidad = async () => {
        isLoadingModal2.value = true
        const formData = new FormData()
        formData.append('nombre', nombreLocalidadBuscar.value)
        formData.append('id', claveLocalidadBuscar.value)

        if (!claveLocalidadBuscar.value) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'La CLAVE de la LOCALIDAD es obligatorio',
                confirmButtonText: 'Aceptar',
                width: '600px',
                target: 'body', // Renderizar en el body
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })
            isLoadingModal2.value = false
            return
        }

        if (!/^\d+$/.test(claveLocalidadBuscar.value)) {
            Swal.fire({
                icon: 'error',
                title: 'Valor inválido',
                text: 'La CLAVE de la LOCALIDAD debe contener solo NÚMEROS',
                confirmButtonText: 'Aceptar',
                width: '600px',
                target: 'body', // Renderizar en el body
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })
            isLoadingModal2.value = false
            return
        }

        if (claveLocalidadBuscar.value.length > 3) {
            Swal.fire({
                icon: 'error',
                title: 'Longitud excedida',
                text: 'La CLAVE de la LOCALIDAD no puede tener más de 3 dígitos',
                confirmButtonText: 'Aceptar',
                width: '600px',
                target: 'body', // Renderizar en el body
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })
            isLoadingModal2.value = false
            return
        }

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
                        timerProgressBar: true,
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })
                    modalBuscarLocalidadVisible.value = false
                    if (tipoLocalidad.value == 'solicitante') {
                        idLocalidadSolicitante.value = page.props.flash.idLocalidad
                        nombreLocalidadSolicitante.value = nombreLocalidadBuscar.value.toUpperCase().trim()
                    } else if (tipoLocalidad.value == 'propietario') {
                        idLocalidadPropietario.value = page.props.flash.idLocalidad
                        nombreLocalidadPropietario.value = nombreLocalidadBuscar.value.toUpperCase().trim()
                    } else if (tipoLocalidad.value == 'propiedad') {
                        idLocalidadPropiedad.value = page.props.flash.idLocalidad
                        nombreLocalidadPropiedad.value = nombreLocalidadBuscar.value.toUpperCase().trim()
                    }
                    isLoadingModal2.value = false
                },
                onFinish: () => {
                    isLoadingModal2.value = false
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: (errors) => {
                    isLoadingModal2.value = false
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
                        width: '600px',
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

    const debouncedFiltrarLocalidades = debounce(filtrarLocalidades, 500)

    const seleccionaLocalidad = (idLocalidad, nombreLocalidad) => {
        modalBuscarLocalidadVisible.value = false

        if (tipoLocalidad.value == 'solicitante') {
            idLocalidadSolicitante.value = idLocalidad
            nombreLocalidadSolicitante.value = nombreLocalidad
        } else if (tipoLocalidad.value == 'propietario') {
            idLocalidadPropietario.value = idLocalidad
            nombreLocalidadPropietario.value = nombreLocalidad
        } else if (tipoLocalidad.value == 'propiedad') {
            idLocalidadPropiedad.value = idLocalidad
            nombreLocalidadPropiedad.value = nombreLocalidad
        }

        idLocalidadSolicitante.value = idLocalidad
        nombreLocalidadSolicitante.value = nombreLocalidad
    }

    const inputCurpPropietario = async (event) => {
        if (event.isTrusted) {
            //Sólo si se capturó/copió la clave catastral
            if (curpPropietario.value && curpPropietario.value.length >= LONGITUD_CURP.value) {
                isLoadingModal.value = true
                if (curpPropietario.value.length > LONGITUD_CURP.value) {
                    curpPropietarioInvalida.value = true
                    curpPropietarioCompleta.value = false
                    isLoadingModal.value = false
                    return
                }
                if (!curpRegex.test(curpPropietario.value)) {
                    curpPropietarioInvalida.value = true
                    curpPropietarioCompleta.value = false
                    isLoadingModal.value = false
                    return
                }

                await obtenerPersona(curpPropietario.value, 'propietario')
                curpPropietarioCompleta.value = true
                curpPropietarioInvalida.value = false

                if (persona.value) {
                    nuevoPropietario.value = false
                    idPersonaPropietario.value = persona.value.id
                    idContactoPropiedad.value = persona.value.propietario?.id || null
                    cargaDatosPersonaPropietario(persona.value)
                    if (persona.value)
                    {
                        if (persona.value.propietario == null) 
                        {
                            cargaDatosPropietarioIntegridad(persona.value)

                            if (!solicitudBloqueada.value) {
                                bloqueadosPersonaPropietario(persona.editable)
                                bloqueadosPropietario(false)
                                editablesPersonaPropietario(persona.editable)
                                editablesPropietario(false)
                            }
                        }
                        else 
                        { 
                            cargaDatosPropietario(persona.value.propietario)
                            if (!solicitudBloqueada.value) {
                                bloqueadosPersonaPropietario(!persona.value.propietario?.editable)
                                editablesPersonaPropietario(false)
                                editablesPropietario(false)
                                bloqueadosPropietario(false)
                            }
                        }
                    }
                } 
                else {
                    nuevoPropietario.value = true
                    idContactoPropiedad.value = ''
                    inicializaPropietario()
                    editablesPersonaPropietario(true)
                    editablesPropietario(true)

                    inicializaSolicitante()
                    editablesPersonaSolicitante(true)
                    editablesSolicitante(true)
                    setTimeout(() => {
                        floatingNomPropietarioRef.value?.focus()
                    }, 50)
                }

                isLoadingModal.value = false
            } 
            else 
            {
                curpPropietarioInvalida.value = false
                curpPropietarioCompleta.value = false
            }
        }
    }

    // function formatoFecha(fecha) {
    //     const year = fecha.getFullYear();
    //     const month = String(fecha.getMonth() + 1).padStart(2, "0"); // meses: 0-indexed
    //     const day = String(fecha.getDate()).padStart(2, "0");
    //     return `${year}-${month}-${day}`;
    // }

    // function inicializaFechas() {
    //     const hoy = new Date();
    //     const hace1Mes = new Date();
    //     hace1Mes.setDate(hoy.getDate() - 30);

    //     fechaInicioQuery.value = formatoFecha(hace1Mes);
    //     fechaFinQuery.value = formatoFecha(hoy);

    //     rangoFechasQuery.value = [
    //         formatoFecha(hace1Mes), // formato 'YYYY-MM-DD'
    //         formatoFecha(hoy),
    //     ];
    // }

    const CVE_MUNICIPIO = ref('012')
    const nuevaBtn = ref(null);

    onMounted(() => {
        fechaInicioQuery.value = props.fechaInicioQuery
        fechaFinQuery.value = props.fechaFinQuery
        idRangoFechasQuery.value = props.idRangoFechasQuery

        sortColumn.value = props.sortColumn || 'fecha_ingreso';
        sortDirection.value = props.sortDirection || 'asc';
        paraNuevaSolicitud.value = props.paraNuevaSolicitud || false;

        if (paraNuevaSolicitud.value) {
            // Simula el click después de un pequeño delay (para asegurar que el botón esté en el DOM)
            setTimeout(() => {
            if (nuevaBtn.value) {
                nuevaBtn.value.click(); // Dispara el click programático
            }
            }, 100);
        }

        // sortDirection.value = router.page.props.sortDirection;
        // sortColumn.value = router.page.props.sortColumn;

        rangoFechasQuery.value = [
            fechaInicioQuery.value, // formato 'YYYY-MM-DD'
            fechaFinQuery.value
        ]

        claveCatastral.value = CVE_MUNICIPIO.value + '  000' // Inicializa con CVE_MUNICIPIO seguido de un espacio

        filtroChkSolicitudes.value = 0

        asignarLocalidad(claveCatastral)
        document.addEventListener('click', handleClickOutside)
        window.addEventListener('resize', updateSize)

        nextTick(() => {
            const inputs = pickerWrapper.value?.querySelectorAll('input')
            if (inputs?.length === 2) {
                inputs.forEach((input) => {
                    input.addEventListener('input', (event) => {
                        if (event.isTrusted) {
                            rangoFechasManual.value = true
                            onDateChange(rangoFechasQuery.value)
                        }
                    })
                })
            }
        })

        intervalo.value = setInterval(() => {
            fetchSolicitudes(false, true)
        }, intervaloMs)

    })

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside)
    })

    // Método para dar formato y permitir edición completa
    const formatClaveCatastral = async (event) => {
        let value = event.target.value.replace(/\D/g, '') // Elimina todo lo que no sean dígitos
        value = value.substring(0, 18)

        // Formatear en grupos de tres dígitos separados por espacios
        value = value.replace(/(\d{3})(?=\d)/g, '$1 ')
        claveCatastral.value = value.trim()

        if (event.isTrusted) {
            //Sólo si se capturó/copió la clave catastral
            if (!claveCatastral) {
                // Verifica si es null, undefined o vacío
                claveCatastral.value = '' // O maneja el caso como prefieras
                return
            }

            if (claveCatastral.value.length >= 23) {
                if (claveCatastral.value.length != 23) 
                {
                    claveCatastralCompleta.value = false
                    return
                }

                asignarLocalidad(claveCatastral)

                const claveCatastralSinEspacios = claveCatastral.value.replace(/\s/g, '')

                propiedad.value = null
                await obtenerPropiedad(claveCatastralSinEspacios)
                isLoadingModal.value = true // Muestra el cargador

                claveCatastralCompleta.value = true

                if (propiedad.value) {
                    //Si existe la propiedad carga los datos
                    propiedadEsEditable.value = propiedad.value.editable
                    idPropiedadSolicitud.value = propiedad.value.id
                    tipoPropiedad.value = propiedad.value.id_tipo
                    nombreTipoPropiedad.value = propiedad?.value?.tipo?.nombre || ''
                    superficiePropiedad.value = propiedad.value.superficie
                    superficieConstruccionPropiedad.value = propiedad.value.superficie_construccion
                    callePropiedad.value = propiedad.value.calle
                    numeroPropiedad.value = propiedad.value.numero
                    idColoniaPropiedad.value = propiedad.value.id_colonia
                    nombreColoniaPropiedad.value = propiedad.value?.colonia?.nombre || ''
                    idLocalidadPropiedad.value = propiedad.value.id_localidad
                    nombreLocalidadPropiedad.value = propiedad?.value?.localidad?.nombre || ''
                    croquis.value = propiedad?.value?.img_croquis || null
                    imgCroquisPropiedad.value = propiedad?.value?.img_croquis

                    if (imgCroquisPropiedad.value) 
                    {
                        const ruta = `/storage/croquis/${imgCroquisPropiedad.value}`
                        const response = await fetch(ruta)
                        const blob = await response.blob()

                        const archivoSimulado = new File([blob], imgCroquisPropiedad, { type: blob.type })

                        file.value = archivoSimulado
                    }
                    else 
                    {
                        isFileLoaded.value = false
                        file.value = null
                        fileName.value = ''
                        imageSrc.value = ''
                    }

                    curpPropietario.value = propiedad.value?.contacto?.persona.curp
                    curpPropietarioCompleta.value = true
                    idPersonaPropietario.value = propiedad.value?.contacto.id_persona
                    nomPropietario.value = propiedad.value?.contacto.persona?.nombre
                    apePropietario.value = propiedad.value?.contacto.persona?.apellidos
                    telefonoPropietario.value = propiedad.value?.contacto.telefono
                    emailPropietario.value = propiedad.value?.contacto.email

                    idContactoPropiedad.value = propiedad.value.id_contacto

                    if (paraNuevaSolicitud.value) {
                        estatusSolicitudSelect.value = props.estatusSolicitud.filter((item) => item.id > 1 && item.id !== 6)
                    }

                    bloqueadosPersonaPropietario(!propiedad.value.contacto.persona.editable)
                    editablesPersonaPropietario(false)
                    bloqueadosPropietario(false)
                    editablesPropietario(false)

                    nuevaPropiedad.value = false
                    nuevaPropiedadReciente.value = false
                    if (croquis.value != null) {
                        isFileLoaded.value = true
                        const fileIconTemplate = {
                            template: `
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1f1f1f" class="size-6">
                            <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" />
                        </svg> `
                        }
                        fileIcon.value = markRaw(fileIconTemplate) // Marca el objeto como "raw"
                        fileName.value = croquis.value
                        imageSrc.value = '/storage/croquis/' + fileName.value
                    }

                    bloqueadosPropiedad(false)
                    if (solicitudBloqueada.value) {
                        bloqueadosPropiedad(true)
                    }

                    editablesPropiedad(false)
                } //Si no existe la propiedad
                else 
                {
                    nuevaPropiedad.value = true //Es propiedad NUEVA
                    idPropiedadSolicitud.value = null
                    let datosPropiedadOcupados = 0 //Para contar cuantos campos ya han sido capturados

                    if (!(tipoPropiedad.value == '' || tipoPropiedad.value == null)) datosPropiedadOcupados++
                    if (!(superficiePropiedad.value == '' || superficiePropiedad.value == null)) datosPropiedadOcupados++
                    if (!(callePropiedad.value == '' || callePropiedad.value == null)) datosPropiedadOcupados++
                    if (!(numeroPropiedad.value == '' || numeroPropiedad.value == null)) datosPropiedadOcupados++
                    if (!(callePropiedad.value == '' || callePropiedad.value == null)) datosPropiedadOcupados++
                    
                    if (datosPropiedadOcupados < 3) {
                        //Si hay menos de 3 datos de propiedad capturados
                        // inicializaSolicitud()
                        inicializaPropiedad()
                        editablesPropiedad(true)
                        inicializaPropietario(true)
                        curpPropietario.value = ''
                        curpPropietarioEditable.value = true
                        curpPropietarioCompleta.value = false

                        setTimeout(() => {
                            floatingTipoPropiedadRef.value?.focus()
                        }, 50)
                    } 
                    else 
                    {
                        if (nuevaPropiedadReciente.value) {
                            //Si la propiedad reciente era NUEVA
                            const result = await Swal.fire({
                                icon: 'warning',
                                title: 'Hay datos capturados previamente en la propiedad',
                                html: `<div style="text-align: center; font-size: 12pt">
                                    ¿Deseas eliminar esta información e ingresar todo de nueva cuenta? </div>`,
                                showCancelButton: true,
                                confirmButtonText: 'Aceptar',
                                cancelButtonText: 'Cancelar',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                backdrop: false,
                                didOpen: () => {
                                    const swalContainer = document.querySelector('.swal2-container')
                                    if (swalContainer) {
                                        swalContainer.style.setProperty('z-index', '99999', 'important')
                                    }
                                }
                            })

                            if (result.isConfirmed) {
                                //inicializaSolicitud()
                                inicializaPropiedad()
                                editablesPropiedad(true)
                                inicializaPropietario(true)
                                curpPropietario.value = ''
                                curpPropietarioEditable.value = true
                                curpPropietarioCompleta.value = false
                            }
                        } else {
                            //inicializaSolicitud()
                            inicializaPropiedad()
                            editablesPropiedad(true)
                            inicializaPropietario(true)
                            curpPropietario.value = ''
                            curpPropietarioEditable.value = true
                            curpPropietarioCompleta.value = false
                        }
                        nuevaPropiedadReciente.value = true
                    }
                }
                isLoadingModal.value = false
            } 
            else 
            {
                claveCatastralCompleta.value = false
                nuevaPropiedad.value = false
            }
        }
    }

    function asignarLocalidad(claveCatastral) {
        const localidades = props.localidades

        if (claveCatastral.value.length > 6) {
            const idLocalidad = parseInt(claveCatastral.value.substring(4, 7), 10)
            const localidadPropiedad = localidades.find((l) => l.id === idLocalidad)
            idLocalidadPropiedad.value = localidadPropiedad?.id
            nombreLocalidadPropiedad.value = localidadPropiedad?.nombre
        }
    }

    watch(claveCatastral, (newValue) => {
        const input = claveCatastralRef.value

        if (!newValue) {
            claveCatastral.value = ''
            return
        }

        let value = newValue.replace(/\D/g, '').substring(0, 18)
        value = value.replace(/(\d{3})(?=\d)/g, '$1 ')
        claveCatastral.value = value.trim()

        if (!input) return

        const start = input.selectionStart
        const end = input.selectionEnd
        const wasAtEnd = input.selectionStart === input.value.length

        // Restaurar la posición del cursor
        requestAnimationFrame(() => {
            if (wasAtEnd) {
                input.setSelectionRange(input.value.length, input.value.length)
            } else {
                input.setSelectionRange(start, end)
            }
        })
    })

    const handleFocusClaveCatastral = (event) => {
        if (claveCatastral.value.length === 7) {
            event.target.setSelectionRange(7, 7) // Coloca el cursor después del tercer carácter
        } else {
            // event.target.select();
        }
    }

    const claveCatastralRef = ref(null)

    watch(activeTab, (newValue) => {
        if (newValue === 'propiedad') {
            if (!cambiaTabError.value) {
                setTimeout(() => {
                    claveCatastralRef.value?.focus()
                }, 50)
            }
        }

        if (newValue === 'propietario') {
            if (!cambiaTabError.value) {
                setTimeout(() => {
                    floatingCURPPropietarioRef.value?.focus()
                }, 50)
            }
        }

        if (newValue === 'solicitante') {
            if (!cambiaTabError.value) {
                setTimeout(() => {
                    floatingCURPSolicitanteRef.value?.focus()
                }, 50)
            }
        }

        if (newValue === 'referencia') {
            if (!cambiaTabError.value) {
                setTimeout(() => {
                    floatingReferenciaRef.value?.focus()
                }, 50)
            }
        }

        if (newValue === 'croquis') {
            if (!cambiaTabError.value) {
                setTimeout(() => {
                    dropZoneCroquis.value?.focus()
                }, 50)
            }
        }
    })

    const isFileLoaded = ref(false)
    const archivoInvalido = ref(false)
    const file = ref(null)
    const imageSrc = ref('')
    const previewDialogVisible = ref(false)
    const fileInput = ref(null)
    const fileIcon = ref(null) // Para almacenar el componente del icono
    const fileName = ref('')
    const borraArchivoCroquis = ref(false)

    const triggerFileInput = () => {
        fileInput.value.click()
    }

    const dropZoneCroquis = ref(null)

    const handleDragOver = () => {
        if (dropZoneCroquis.value) {
            dropZoneCroquis.value.classList.add('drop-zone', 'bg-color1-50', 'border-color1-700') // Ejemplo con Tailwind CSS
        }
    }

    const handleDrop = (event) => {
        if (dropZoneCroquis.value) {
            dropZoneCroquis.value.classList.remove('drop-zone', 'bg-color1-50', 'border-color1-700')
        }
        const droppedFile = event.dataTransfer.files[0]
        handleFile(droppedFile)
    }

    // Función para crear un retraso utilizando una promesa
    const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

    const handleFileChange = async (event) => {
        isLoadingModal.value = true

        // Retraso para simular la espera de carga
        await delay(50) // Simulando el retraso antes de procesar el archivo
        const selectedFile = event.target.files[0]
        await handleFile(selectedFile)
    }

    const uploadProgress = ref(0)
    const isUploading = ref(false)
    const isProcessingFile = ref(false)
    const permiteDescartarCroquis = ref(false)

    const uploadFile = async () => {
        const formData = new FormData()
        let claveCatastralSinEspacios = {
            value: claveCatastral.value.replace(/\s/g, '')
        }
        if (claveCatastralSinEspacios.length <= 6) {
            claveCatastralSinEspacios.value = null
        }

        const currentPage = router.page.props.paginaActual || 1

        formData.append('archivo', file.value)
        formData.append('tramitesSeleccionados', tramitesSeleccionados.value)
        formData.append('claveCatastral', claveCatastralSinEspacios.value)
        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaInicioQuery', fechaInicioQuery.value)
        formData.append('fechaFinQuery', fechaFinQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('curpSolicitante', curpSolicitante.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('rangoFechasManual', rangoFechasManual.value)
        formData.append('idPropiedadSolicitud', idPropiedadSolicitud.value)

        formData.append('page', currentPage)

        isUploading.value = true
        uploadProgress.value = 0
        isProcessingFile.value = false

        await router.post('/solicitudes/upload-croquis/' + idSolicitudEditar.value, formData, {
            onProgress: (progress) => {
                if (progress) uploadProgress.value = Math.round(progress.percentage)
                if (uploadProgress.value == 100) {
                    isUploading.value = false
                    isProcessingFile.value = true
                }
            },
            onSuccess: (page) => {
                isUploading.value = false
                isProcessingFile.value = true

                const successMessage = page.props?.flash?.success
                if (successMessage) {
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
                    isProcessingModal.value = false
                    // fetchSolicitudes(false, true)
                }
                uploadProgress.value = 100
                permiteDescartarCroquis.value = false

                if (tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION))
                {
                    imgCroquisAux.value = router.page.props.flash.solicitud?.croquis_aux?.img
                }
                else
                {
                    //idPropiedadSolicitud.value = router.page.props.flash.solicitud?.id_propiedad
                    imgCroquisPropiedad.value = router.page.props.flash.solicitud?.propiedad?.img_croquis
                }
            },
            onFinish: () => {
                isProcessingFile.value = false
            },
            onError: () => {
                isUploading.value = false
                isProcessingFile.value = true
                uploadProgress.value = 0
            },
            forceFormData: true
        })
    }

    const handleFile = async (selectedFile) => {
        if (selectedFile) {
            file.value = selectedFile
            fileName.value = selectedFile.name

            if (selectedFile.type.startsWith('image/')) {
                const reader = new FileReader()
                reader.onloadend = async () => {
                    imageSrc.value = reader.result
                    isLoadingModal.value = false
                    previewDialogVisible.value = true
                    await delay(50)
                }
                reader.readAsDataURL(selectedFile)
                archivoInvalido.value = false
                permiteDescartarCroquis.value = true
            } 
            else 
            {
                isFileLoaded.value = false
                previewDialogVisible.value = true
                file.value = null
                fileName.value = ''
                imageSrc.value = ''
                if (fileInput.value) {
                    fileInput.value.value = ''
                }
                archivoInvalido.value = true
                isLoadingModal.value = false // Apagar el spinner si el archivo no es válido
            }
        } else {
            fileName.value = ''
            isLoadingModal.value = false
        }
    }

    const acceptFile = () => {
        isUploading.value = true
        isProcessingFile.value = false
        previewDialogVisible.value = false
        uploadFile()
        isFileLoaded.value = true
        const fileIconTemplate = {
            template: `
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M220-344q65-8 129.5-12t130.5-4q66 0 130.5 4T740-344L560-560 446-424l-80-96-146 176ZM120-160q-17 0-28.5-11.5T80-200v-560q0-17 11.5-28.5T120-800q8 0 35.5 9.5T229-770q46 11 108.5 20.5T480-740q80 0 142.5-9.5T731-770q46-11 73.5-20.5T840-800q17 0 28.5 11.5T880-760v560q0 17-11.5 28.5T840-160q-8 0-35.5-9.5T731-190q-46-11-108.5-20.5T480-220q-80 0-142.5 9.5T229-190q-46 11-73.5 20.5T120-160Zm40-94q78-23 158.5-34.5T480-300q81 0 161.5 11.5T800-254v-451q-78 23-158.5 34T480-660q-81 0-161.5-11T160-705v451Zm320-226Z"/>
            </svg>
            `
        }
        fileIcon.value = markRaw(fileIconTemplate)
    }

    const rejectFile = () => {
        previewDialogVisible.value = false
        isFileLoaded.value = false
        file.value = null
        fileName.value = ''
        imageSrc.value = ''
        if (fileInput.value) {
            fileInput.value.value = ''
        }
    }

    const showPreviewDialog = async () => {
        isLoadingModal.value = true

        if (file.value) {
            if (file.value.type.startsWith('image/')) {
                const reader = new FileReader()

                reader.onloadend = async () => {
                    await delay(150)
                    imageSrc.value = reader.result
                    previewDialogVisible.value = true
                    isLoadingModal.value = false
                }

                reader.readAsDataURL(file.value)
            } else {
                previewDialogVisible.value = true
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''

                if (fileInput.value) {
                    fileInput.value.value = ''
                }
                isLoadingModal.value = false
            }
        } else {
            if (!nuevaPropiedad.value) {
                await delay(150)
                previewDialogVisible.value = true
                isFileLoaded.value = true
                isLoadingModal.value = false
            } else {
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
                if (fileInput.value) {
                    fileInput.value.value = ''
                }
            }
        }
    } 

    //CHECKPOINT: Se puede presentar el siguiente caso:
    //Una PROPIEDAD puede tener varias solicitudes sin ACEPTAR con un CROQUIS cargado
    //Si se ACEPTA una SOLICITUD entonces la PROPIEDAD (y por tanto el CROQUIS)
    //queda como NO EDITABLE y es la PROPIEDAD ACTIVA.
    //Ahora si después se le cambia el CROQUIS a otra SOLICITUD de esa misma PROPIEDAD
    //en la BD se agrega otra PROPIEDAD con otro CROQUIS
    //entonces ahora la PROPIEDAD (y el CROQUIS) recién insertada
    //es la ACTIVA y está EDITABLE y las otras solicitudes quedan con la PROPIEDAD 
    //que tiene el CROQUIS que ya es el de la PROPIEDAD ACTIVA

    const removeFile = async () => {
        previewDialogVisible.value = false

       if (propiedadEsEditable.value == 1 || tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
       {
            // Si la propiedad es editable, muestra la advertencia
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                html: `<div style="text-align: center; font-size: 13pt">
                    ¿Deseas eliminar el archivo del croquis? <br> Una vez eliminado no podrás recuperar el archivo. </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            }).then(async (result) => {
                if (result.isConfirmed) 
                {
                    // Si el usuario confirma, ejecuta la lógica de borrado
                    await deleteFile();
                } 
                else 
                {
                    // El usuario canceló, no hace nada
                }
            });
        } 
        else 
        {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                html: `<div style="text-align: center; font-size: 13pt">
                   El CROQUIS está a punto de ser actualizado. Si lo actualizas, a partir de hoy la PROPIEDAD tendrá el CROQUIS que selecciones en esta ventana. <br> ¿Deseas continuar? </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            }).then(async (result) => {
                if (result.isConfirmed) 
                {
                    // Si el usuario confirma, ejecuta la lógica de borrado
                    isFileLoaded.value = false;
                    file.value = null;
                    imageSrc.value = '';
                    fileName.value = '';
                    borraArchivoCroquis.value = true;
                    imgCroquisPropiedad.value = null;

                    if (fileInput.value) {
                        fileInput.value.value = '';
                    }

                    const formData2 = new FormData();
                    formData2.append('imgCroquisPropiedad', imgCroquisPropiedad.value);
                    formData2.append('tipoPropiedad', tipoPropiedad.value);
                    
                    let claveCatastralSinEspacios = {
                        value: claveCatastral.value.replace(/\s/g, '')
                    };

                    if (claveCatastralSinEspacios.length <= 6) {
                        //Si no capturaron más del inicio de la clave
                        claveCatastralSinEspacios.value = null;
                    }

                    const currentPage = router.page.props.paginaActual || 1

                    formData2.append('claveCatastral', claveCatastralSinEspacios.value);
                    formData2.append('tramitesSeleccionados', tramitesSeleccionados.value);
                    formData2.append('callePropiedad', callePropiedad.value);
                    formData2.append('numeroPropiedad', numeroPropiedad.value);
                    formData2.append('idColoniaPropiedad', idColoniaPropiedad.value);
                    formData2.append('idLocalidadPropiedad', idLocalidadPropiedad.value);
                    formData2.append('superficiePropiedad', superficiePropiedad.value);
                    formData2.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value);
                    formData2.append('idContactoPropiedad', idContactoPropiedad.value);
                    formData2.append('nombreQuery', nombreQuery.value);
                    formData2.append('fechaInicioQuery', fechaInicioQuery.value);
                    formData2.append('fechaFinQuery', fechaFinQuery.value);
                    formData2.append('tiposTramitesQuery', tiposTramitesQuery.value);
                    formData2.append('tramitesQuery', tramitesQuery.value);
                    formData2.append('estatusQuery', estatusQuery.value);
                    formData2.append('filtroChkSolicitudes', filtroChkSolicitudes.value);
                    formData2.append('rangoFechasManual', rangoFechasManual.value);
                    formData2.append('page', currentPage);

                    // await deleteFile();

                    triggerFileInput();
                } 
                else 
                {
                    // El usuario canceló, no hace nada
                }
            });
        }

        async function deleteFile() {
            isFileLoaded.value = false;
            file.value = null;
            imageSrc.value = '';
            fileName.value = '';
            borraArchivoCroquis.value = true;
            imgCroquisPropiedad.value = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }

            const formData2 = new FormData();
            formData2.append('imgCroquisPropiedad', imgCroquisPropiedad.value);
            formData2.append('tipoPropiedad', tipoPropiedad.value);
            
            let claveCatastralSinEspacios = {
                value: claveCatastral.value.replace(/\s/g, '')
            };

            if (claveCatastralSinEspacios.length <= 6) {
                //Si no capturaron más del inicio de la clave
                claveCatastralSinEspacios.value = null;
            }

            const currentPage = router.page.props.paginaActual || 1

            formData2.append('claveCatastral', claveCatastralSinEspacios.value);
            formData2.append('tramitesSeleccionados', tramitesSeleccionados.value);
            formData2.append('callePropiedad', callePropiedad.value);
            formData2.append('numeroPropiedad', numeroPropiedad.value);
            formData2.append('idColoniaPropiedad', idColoniaPropiedad.value);
            formData2.append('idLocalidadPropiedad', idLocalidadPropiedad.value);
            formData2.append('superficiePropiedad', superficiePropiedad.value);
            formData2.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value);
            formData2.append('idContactoPropiedad', idContactoPropiedad.value);
            formData2.append('nombreQuery', nombreQuery.value);
            formData2.append('fechaInicioQuery', fechaInicioQuery.value);
            formData2.append('fechaFinQuery', fechaFinQuery.value);
            formData2.append('tiposTramitesQuery', tiposTramitesQuery.value);
            formData2.append('tramitesQuery', tramitesQuery.value);
            formData2.append('estatusQuery', estatusQuery.value);
            formData2.append('filtroChkSolicitudes', filtroChkSolicitudes.value);
            formData2.append('rangoFechasManual', rangoFechasManual.value);
            formData2.append('page', currentPage)
            
            isProcessingModal.value = true;

            await router.post('/solicitudes/delete-croquis/' + idSolicitudEditar.value, formData2, {
                onSuccess: (page) => {
                    const successMessage = page.props?.flash?.success;
                    if (successMessage) {
                        Swal.fire({
                            toast: true,
                            icon: 'success',
                            position: 'top-end',
                            showConfirmButton: false,
                            title: 'La imagen ha sido borrada con éxito!',
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: () => {
                                const swalContainer = document.querySelector('.swal2-container');
                                if (swalContainer) {
                                    swalContainer.style.setProperty('z-index', '99999', 'important');
                                }
                            }
                        });
                        isProcessingModal.value = false;
                    }
                    permiteDescartarCroquis.value = true;
                },
                onFinish: () => {
                    isProcessingModal.value = false;
                },
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onError: () => {
                    isProcessingModal.value = false;
                    const errorMessage = 'Hubo un error al borrar el archivo.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: `<div style="text-align: justify; font-size: 12pt">
                            <ul>No existe el archivo de la imagen deseada.</ul>
                        </div>`,
                        confirmButtonText: 'Aceptar',
                        width: '400px',
                        target: 'body',
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important');
                            }
                        }
                    });
                }
            });
        }
    }

    const generarNombreArchivo = (nombreOriginal) => {
        const extension = nombreOriginal.substring(nombreOriginal.lastIndexOf('.'))
        const nombreBase = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
        return `${nombreBase}${extension}`
    }

    const enviarSolicitud = async (formData) => {
        await router.post('/solicitudes/store', formData, {
            onSuccess: (page) => {
                // fetchSolicitudes()

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
                modalBuscarColoniaVisible.value = false
                isSavingModal.value = false
                paraEditarSolicitud.value = true
                nuevaPropiedad.value = false
                nuevoPropietario.value = false
                nuevoSolicitante.value = false
                activeTab.value = router.page.props.flash.activeTab || 'croquis'
                idSolicitudEditar.value = router.page.props.flash.solicitud?.id
                idPersonaPropietario.value = router.page.props.flash.solicitud?.propiedad?.contacto.id_persona
                idContactoPropiedad.value = router.page.props.flash.solicitud?.propiedad?.id_contacto
                idPersonaSolicitante.value = router.page.props.flash.solicitud?.contacto.id_persona
                idContactoSolicitud.value = router.page.props.flash.solicitud?.id_contacto
                idPropiedadSolicitud.value = router.page.props.flash.solicitud?.propiedad?.id
                idContactoPropiedadAlCargar.value = router.page.props.flash.solicitud?.propiedad?.id_contacto
                propiedadEsEditable.value = router.page.props.flash.solicitud?.propiedad?.editable
                estatusSolicitudSelect.value = props.estatusSolicitud.filter((item) => item.id > 1)
                paraEditarSolicitud.value = router.page.props.flash.solicitud ? true : false

                if (idEstatusSolicitud.value == 99) 
                {
                    dialogVisible.value = false
                }

                activeTab.value = router.page.props.flash.activeTab || 'croquis'
            },
            onFinish: () => {
                isSavingModal.value = false
                cambiaTabError.value = true //Cambia el tab por error
            },
            preserveScroll: true,
            preserveState: true,
            replace: true,

            onError: async (errors) => {
                isSavingModal.value = false

                if (errors.duplicado) {
                    const result = await Swal.fire({
                        title: 'Solicitud duplicada',
                        text: errors.duplicado,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })

                    if (result.isConfirmed) {
                        formData.append('forzar', 'true')
                        await enviarSolicitud(formData)
                    }
                } else {
                    const hasValidationErrors = errors && Object.keys(errors).length > 0
                    const errorMessage = hasValidationErrors ? Object.values(errors).join('<br>') : 'Hubo un error al guardar la información.'

                    Swal.fire({
                        icon: hasValidationErrors ? 'warning' : 'error',
                        title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                        html: `<div style="text-align: justify; font-size: 12pt"><ul>${errorMessage}</ul></div>`,
                        confirmButtonText: 'Aceptar',
                        width: '400px',
                        target: 'body', // Renderizar en el body
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
                    })
                }
            }
        })
    }

    const agregarSolicitud = async () => {
        if (idEstatusSolicitud.value == 99) {
            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Deseas ACEPTAR la solicitud?',
                html: `<div style="text-align: center; font-size: 12pt">
                    Si ACEPTAS, a la solicitud ya no se le podrá hacer cambios. </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })

            if (result.isDismissed) {
                return
            }
        }

        isSavingModal.value = true
        const formData = new FormData()

        formData.append('idPersonaPropietario', idPersonaPropietario.value)
        formData.append('curpPropietario', curpPropietario.value)
        formData.append('curpPropietarioInvalida', curpPropietarioInvalida.value)
        formData.append('nomPropietario', nomPropietario.value)
        formData.append('apePropietario', apePropietario.value)
        formData.append('telefonoPropietario', telefonoPropietario.value)
        formData.append('emailPropietario', emailPropietario.value)
        formData.append('esSolicitante', esSolicitante.value)

        if (esSolicitante.value === '0' || tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
        {
            formData.append('idPersonaSolicitante', idPersonaSolicitante.value)
            formData.append('curpSolicitante', curpSolicitante.value)
            formData.append('curpSolicitanteInvalida', curpSolicitanteInvalida.value)
            formData.append('nomSolicitante', nomSolicitante.value)
            formData.append('apeSolicitante', apeSolicitante.value)
            formData.append('telefonoSolicitante', telefonoSolicitante.value)
            formData.append('emailSolicitante', emailSolicitante.value)
            formData.append('solicitaOrganizacion', solicitaOrganizacion.value)
            formData.append('razonSocialSolicitante', razonSocialSolicitante.value)
        }

        formData.append('idPropiedadSolicitud', idPropiedadSolicitud.value)
        formData.append('tipoPropiedad', tipoPropiedad.value)
        let claveCatastralSinEspacios = {
            value: claveCatastral.value.replace(/\s/g, '')
        }
        if (claveCatastralSinEspacios.length <= 6) {
            //Si no capturaron más del inicio de la clave
            claveCatastralSinEspacios.value = null
        }
        formData.append('claveCatastral', claveCatastralSinEspacios.value)
        formData.append('callePropiedad', callePropiedad.value)
        formData.append('numeroPropiedad', numeroPropiedad.value)
        formData.append('idColoniaPropiedad', idColoniaPropiedad.value)
        formData.append('idLocalidadPropiedad', idLocalidadPropiedad.value)
        formData.append('superficiePropiedad', superficiePropiedad.value)
        formData.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value)
        formData.append('idContactoPropiedad', idContactoPropiedad.value)

        formData.append('idDestinoObra', idDestinoObra.value)
        formData.append('idSector', idSector.value)
        formData.append('fecha_ingreso', fecha_ingreso.value)
        formData.append('idEstatusSolicitud', idEstatusSolicitud.value)
        formData.append('imgCroquisPropiedad', imgCroquisPropiedad.value)
        formData.append('idContactoSolicitud', idContactoSolicitud.value)

        formData.append('referencia', referencia.value)

        const currentPage = router.page.props.paginaActual || 1

        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaInicioQuery', fechaInicioQuery.value)
        formData.append('fechaFinQuery', fechaFinQuery.value)
        formData.append('idRangoFechasQuery', idRangoFechasQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('sortColumn', sortColumn.value)
        formData.append('sortDirection', sortDirection.value)
        formData.append('rangoFechasManual', rangoFechasManual.value)
        formData.append('page', currentPage)

        tramitesSeleccionados.value.forEach((tramiteId) => {
            formData.append('tramitesSeleccionados[]', tramiteId)
        })

        formData.append('forzar', false)

        cambiaTabError.value = false //Indica que NO Cambia el tab por error

        if (!nuevaPropiedad.value && propiedad.value.id_contacto != undefined && propiedad.value?.id_contacto != idContactoPropiedad.value) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                html: `<div style="text-align: center; font-size: 12pt">
                    La PROPIEDAD ya tiene asignado un PROPIETARIO. <br> Si ACEPTAS se hará un CAMBIO DE PROPIETARIO.
                    <br><br>
                    Valores actuales: <br>
                    id_contacto de Propiedad: <strong>${propiedad.value?.id_contacto}</strong> <br>
                    idContactoPropiedad: <strong>${idContactoPropiedad.value}</strong>
                </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })

            if (result.isDismissed) {
                isSavingModal.value = false
                return
            }
        }

        const idFormateado = String(Math.abs(Number(idLocalidadPropiedad.value))).padStart(3, '0')
        // Extraer caracteres 5-7 (posiciones 4-6 en base 0)
        const segmentoClave = claveCatastral.value.substring(4, 7)

        if (!tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
        {
            // Comparar
            if (idFormateado != segmentoClave) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    html: `<div style="text-align: center; font-size: 12pt">
                        La CLAVE de la LOCALIDAD de la PROPIEDAD NO COINCIDE con la de la CLAVE CATASTRAL. <br> ¿Deseas guardar de todas formas? </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    backdrop: false,
                    didOpen: () => {
                        const swalContainer = document.querySelector('.swal2-container')
                        if (swalContainer) {
                            swalContainer.style.setProperty('z-index', '99999', 'important')
                        }
                    }
                })

                if (result.isDismissed) {
                    isSavingModal.value = false
                    return
                }
            }
        }

        if (file.value) {
            const nombreArchivoCroquis = generarNombreArchivo(fileName.value)
            formData.append('croquis', file.value, nombreArchivoCroquis)
        }
        try {
            await enviarSolicitud(formData)
        } catch (err) {
            console.error('Error inesperado:', err)
        }
    }

    function buildQueryParams(page = 1) {
        const urlParams = new URLSearchParams(window.location.search)

        if (nombreQuery.value) {
            urlParams.set('nombreQuery', nombreQuery.value)
        } else {
            urlParams.delete('nombreQuery')
        }

        urlParams.set('fechaInicioQuery', fechaInicioQuery.value)
        urlParams.set('fechaFinQuery', fechaFinQuery.value)

        urlParams.set('page', page)

        return Object.fromEntries(urlParams.entries())
    }

    function fetchSolicitudes(onMounted, polling = false) {
        const page = router.page.props.solicitudes?.current_page ? router.page.props.solicitudes.current_page : 1
        const paramsObject = buildQueryParams(page)

        let showLoaderTimeout = null

        if (onMounted) {
            //Si está cargando al montar el componente
            isLoading.value = true //Muestra la pantalla de Cargando...
            sortColumn.value = 'fecha_ingreso'
            sortDirection.value = 'asc'
        } else {
            if (!polling) {
                showLoaderTimeout = setTimeout(() => {
                    isSearching.value = true //Muestra la pantalla de Buscando...
                }, 200) // solo mostrar si tarda más de 200ms
            }
        }

        const filtros = {
            fechaInicioQuery: fechaInicioQuery.value,
            fechaFinQuery: fechaFinQuery.value,
            nombreQuery: nombreQuery.value,
            tiposTramitesQuery: tiposTramitesQuery.value,
            tramitesQuery: tramitesQuery.value,
            estatusQuery: estatusQuery.value,
            idRangoFechasQuery: idRangoFechasQuery.value,
            rangoFechasManual: rangoFechasManual.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            sortColumn: sortColumn.value,
            sortDirection: sortDirection.value
        }

        const formData = new FormData()

        for (const key in filtros) {
            const value = filtros[key]

            // Si es un array, agregar cada elemento con el mismo nombre
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item)
                })
            } else {
                formData.append(key, value ?? '')
            }
        }

        formData.append('page', paramsObject.page)

        // Realiza la solicitud GET con los parámetros de búsqueda
        router.post(
            '/solicitudes',
            formData,
            {
                preserveState: true,
                replace: true,
                onFinish: () => {
                    if (onMounted) {
                        isLoading.value = false
                    } else {
                        clearTimeout(showLoaderTimeout)
                        isSearching.value = false
                    }
                    filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes
                }
            }
        )
    }

    function onDateChange(fechaSeleccionada) {
      function resetToLocalMidnight(dateStringOrDate) {
            const d = typeof dateStringOrDate === 'string'
                ? new Date(dateStringOrDate + 'T00:00:00') // fuerza hora local
                : new Date(dateStringOrDate)
            d.setHours(0, 0, 0, 0)
            return d
        }

        const coincideConShortcut = shortcuts.some(sc => {
            const [start, end] = sc.value()

            const seleccionadoStart = resetToLocalMidnight(fechaSeleccionada[0])
            const seleccionadoEnd = resetToLocalMidnight(fechaSeleccionada[1])

            return resetToLocalMidnight(start).getTime() === seleccionadoStart.getTime() &&
                resetToLocalMidnight(end).getTime() === seleccionadoEnd.getTime()
        })

        // Si no coincide, asumimos que fue manual
        rangoFechasManual.value = !coincideConShortcut
        rangoFechasShortcut.value = coincideConShortcut

        if (rangoFechasManual.value)
        {
            fechaInicioQuery.value = rangoFechasQuery.value[0]
            fechaFinQuery.value = rangoFechasQuery.value[1]
        }
        fetchSolicitudes(false)
    }

    function sortTable(column) {
        if (sortColumn.value === column) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
        } else {
            sortColumn.value = column
        }

        // const paramsObject = buildQueryParams();
        isLoading.value = true

        router.post(
            '/solicitudes',
            {
                fechaInicioQuery: fechaInicioQuery.value,
                fechaFinQuery: fechaFinQuery.value,
                nombreQuery: nombreQuery.value,
                tiposTramitesQuery: tiposTramitesQuery.value,
                tramitesQuery: tramitesQuery.value,
                estatusQuery: estatusQuery.value,
                filtroChkSolicitudes: filtroChkSolicitudes.value,
                sortColumn: sortColumn.value,
                sortDirection: sortDirection.value,
                rangoFechasManual: rangoFechasManual.value,
            },
            {
                preserveState: true,
                replace: true,
                onFinish: () => {
                    isLoading.value = false
                    filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes
                }
            }
        )
    }

    const formatDate = (dateString) => {
        const [year, month, day] = dateString.split('-') // Divide la fecha ISO en partes

        return `${day}/${month}/${year}` // Construye la fecha en formato "dd/mm/yyyy"
    }

    const actualizarTramitesSeleccionados = (tramiteId) => {
        if (tramiteId === ID_CONSTANCIA_UBICACION && tramitesSeleccionados.value.length > 0 && !tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) {
            Swal.fire({
                icon: 'warning', // Puedes usar 'error', 'info', etc.
                title: 'Operación inválida',
                text: 'Para seleccionar el trámite CONSTANCIA DE UBICACIÓN DE PREDIO, primero deselecciona los demás.',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            });
            return;
        }

        // Si el trámite seleccionado es CONSTANCIA DE UBICACIÓN
        if (tramiteId === ID_CONSTANCIA_UBICACION) {
              // Si CONSTANCIA_UBICACION ya está seleccionado, lo deselecciona.
                if (tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
                {
                    tramitesSeleccionados.value = [];
                } 
                else 
                {
                    // Si no está seleccionado, lo selecciona y deselecciona los demás.
                    tramitesSeleccionados.value = [ID_CONSTANCIA_UBICACION];
                }
                esSolicitante.value = '0'
                refrescaCroquis()
        } 
        else 
        {
            // Si el trámite CONSTANCIA UBICACIÓN ya está seleccionado, no permitas seleccionar otros
            if (tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) 
            { 
                Swal.fire({
                    icon: 'warning', // Puedes usar 'error', 'info', etc.
                    title: 'Operación inválida',
                    text: 'No puedes seleccionar otro trámite si el trámite CONSTANCIA DE UBICACIÓN DE PREDIO ya está seleccionado.',
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const swalContainer = document.querySelector('.swal2-container')
                        if (swalContainer) {
                            swalContainer.style.setProperty('z-index', '99999', 'important')
                        }
                    }
                });
                return;
            }

            if (curpPropietario || curpPropietario.value == '')
            {
                curpPropietarioInvalida.value = false
            }

            esSolicitante.value = '1' // Cambia el estado a solicitante si se selecciona otro trámite

            // Si el trámite no es la CONSTANCIA DE UBICACION y no está seleccionado,
            // gestiona la selección normal de otros trámites.
            if (tramitesSeleccionados.value.includes(tramiteId)) 
            { 
                tramitesSeleccionados.value = tramitesSeleccionados.value.filter(id => id !== tramiteId); 
            } 
            else 
            {
                tramitesSeleccionados.value.push(tramiteId); 
            }

            refrescaCroquis()
        }

        if (claveCatastral.value.length != 23)
        {
            claveCatastralCompleta.value = false
        }

    }

    function toggleDropdown(solicitudId, event) {
        if (dropdownVisible.value === solicitudId) {
            dropdownVisible.value = null
            return
        }

        dropdownVisible.value = solicitudId
    }

    function closeDropdown() {
        dropdownVisible.value = null
    }

    function handleClickOutside(event) 
    {
        const dropdown = document.getElementById(`dropdown-${dropdownVisible.value}`)
        const button = document.getElementById(`dropdown-button-${dropdownVisible.value}`)
        if (dropdownVisible.value && dropdown && button && !dropdown.contains(event.target) && !button.contains(event.target)) {
            closeDropdown()
        }
    }

    const getColorById = (id) => {
        const estatus = props.estatusSolicitud?.find((item) => item.id === id)
        return estatus ? estatus.color : '#ffffff'
    }

    const habilitarCaptura = (control) => {
        if (control == 'nomSolicitanteEditable') {
            nomSolicitanteEditable.value = !nomSolicitanteEditable.value
            setTimeout(() => {
                floatingNomSolicitanteRef.value?.focus()
            }, 50)
        } else if (control == 'apeSolicitanteEditable') {
            apeSolicitanteEditable.value = !apeSolicitanteEditable.value
            setTimeout(() => {
                floatingApeSolicitanteRef.value?.focus()
            }, 50)
        } else if (control == 'telefonoSolicitanteEditable') {
            telefonoSolicitanteEditable.value = !telefonoSolicitanteEditable.value
            setTimeout(() => {
                floatingTelefonoSolicitanteRef.value?.focus()
            }, 50)
        } else if (control == 'emailSolicitanteEditable') {
            emailSolicitanteEditable.value = !emailSolicitanteEditable.value
            setTimeout(() => {
                floatingEmailSolicitanteRef.value?.focus()
            }, 50)
        } else if (control == 'nomPropietarioEditable') {
            nomPropietarioEditable.value = !nomPropietarioEditable.value
            setTimeout(() => {
                floatingNomPropietarioRef.value?.focus()
            }, 50)
        } else if (control == 'apePropietarioEditable') {
            apePropietarioEditable.value = !apePropietarioEditable.value
            setTimeout(() => {
                floatingApePropietarioRef.value?.focus()
            }, 50)
        } else if (control == 'telefonoPropietarioEditable') {
            telefonoPropietarioEditable.value = !telefonoPropietarioEditable.value
            setTimeout(() => {
                floatingTelefonoPropietarioRef.value?.focus()
            }, 50)
        } else if (control == 'emailPropietarioEditable') {
            emailPropietarioEditable.value = !emailPropietarioEditable.value
            setTimeout(() => {
                floatingEmailPropietarioRef.value?.focus()
            }, 50)
        } else if (control == 'tipoPropiedadEditable') {
            tipoPropiedadEditable.value = !tipoPropiedadEditable.value
            setTimeout(() => {
                floatingTipoPropiedadRef.value?.focus()
            }, 50)
        } else if (control == 'superficiePropiedadEditable') {
            superficiePropiedadEditable.value = !superficiePropiedadEditable.value
            setTimeout(() => {
                floatingSuperficiePropiedadRef.value?.focus()
            }, 50)
        } else if (control == 'superficieConstruccionPropiedadEditable') {
            superficieConstruccionPropiedadEditable.value = !superficieConstruccionPropiedadEditable.value
            setTimeout(() => {
                floatingSuperficieConstruccionPropiedadRef.value?.focus()
            }, 50)
        } else if (control == 'callePropiedadEditable') {
            callePropiedadEditable.value = !callePropiedadEditable.value
            setTimeout(() => {
                floatingCallePropiedadRef.value?.focus()
            }, 50)
        } else if (control == 'numeroPropiedadEditable') {
            numeroPropiedadEditable.value = !numeroPropiedadEditable.value
            setTimeout(() => {
                floatingNumeroPropiedadRef.value?.focus()
            }, 50)
        } else if (control == 'idColoniaPropiedadEditable') {
            idColoniaPropiedadEditable.value = !idColoniaPropiedadEditable.value
            if (idColoniaPropiedadEditable.value) {
                abreModalBuscarColonia('propiedad')
            }
        } else if (control == 'idLocalidadPropiedadEditable') {
            idLocalidadPropiedadEditable.value = !idLocalidadPropiedadEditable.value
            if (idLocalidadPropiedadEditable.value) {
                abreModalBuscarLocalidad('propiedad')
            }
        } else if (control == 'idDestinoObraEditable') {
            idDestinoObraEditable.value = !idDestinoObraEditable.value
            setTimeout(() => {
                floatingDestinoObraRef.value?.focus()
            }, 50)
        } else if (control == 'idSectorEditable') {
            idSectorEditable.value = !idSectorEditable.value
            setTimeout(() => {
                floatingSectorRef.value?.focus()
            }, 50)
        }  else if (control == 'referenciaEditable') {
            referenciaEditable.value = !referenciaEditable.value
            setTimeout(() => {
                floatingReferenciaRef.value?.focus()
            }, 50)
        }
    }

    const actualizarSolicitud = async () => {
        const debeSubirNuevoCroquis = ref(false)

        let claveCatastralSinEspacios = {
            value: claveCatastral.value.replace(/\s/g, '')
        }
        if (claveCatastralSinEspacios.length <= 6) {
            //Si no capturaron más del inicio de la clave
            claveCatastralSinEspacios.value = null
        }

        if (imgCroquisPropiedad.value && claveCatastralCompleta.value && imgCroquisPropiedad.value.substring(0, 18) !== claveCatastralSinEspacios.value) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                html: `<div style="text-align: center; font-size: 12pt">
                La imagen previamente almacenada correspondía a la clave catastral 
                <b>${imgCroquisPropiedad.value.substring(0, 18)}</b> <br>
                Se eliminará esa imagen y tendrás que subir una nueva.
                <hr>
                <div style="text-align: left; font-size: 10pt; margin-top: 10px;">
                    <b>Depuración:</b><br>
                    claveCatastralCompleta: ${claveCatastralCompleta.value} <br>
                    imgCroquisPropiedad (substring 0-18): ${imgCroquisPropiedad.value.substring(0, 18)} <br>
                    claveCatastralSinEspacios: ${claveCatastralSinEspacios.value}
                </div>
                </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })

            if (result.isConfirmed) 
            {
                //Inicializa el CROQUIS
                borraArchivoCroquis.value = true
                debeSubirNuevoCroquis.value = true
                isFileLoaded.value = false
                file.value = null
                imageSrc.value = ''
                fileName.value = ''
                activeTab.value = 'croquis'
                isProcessingModal.value = true
                const formData2 = new FormData()
                formData2.append('imgCroquisPropiedad', imgCroquisPropiedad.value)
                await router.post('/solicitudes/delete-croquis/' + idSolicitudEditar.value, formData2, {
                    onSuccess: (page) => {
                        const successMessage = page.props?.flash?.success
                        if (successMessage) {
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
                            isProcessingModal.value = false
                            imgCroquisPropiedad.value = null
                            idPropiedadSolicitud.value = router.page.props.flash.solicitud.propiedad.id
                        }
                    },
                    onFinish: () => {
                        isProcessingModal.value = false
                    },
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                    onError: () => {
                        isProcessingModal.value = false
                        const errorMessage = 'Hubo un error al borrar el archivo.'
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: `<div style="text-align: justify; font-size: 12pt"><ul>${errorMessage}</ul></div>`,
                            confirmButtonText: 'Aceptar',
                            width: '400px',
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
                return
            } else {
                borraArchivoCroquis.value = false
                debeSubirNuevoCroquis.value = false
                return
            }
        }

        if (idEstatusSolicitud.value == 99) 
        {
            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Deseas aceptar la solicitud?',
                html: `<div style="text-align: center; font-size: 12pt">
                    Si ACEPTAS, a la solicitud ya no se le podrá hacer cambios. </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })

            if (result.isDismissed) {
                return
            }
        }

        isSavingModal.value = true
        const formData = new FormData()

        if (esSolicitante.value === '0' || tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)) {
            formData.append('curpSolicitante', curpSolicitante.value)
            formData.append('curpSolicitanteInvalida', curpSolicitanteInvalida.value)
            formData.append('idPersonaSolicitante', idPersonaSolicitante.value)
            formData.append('nomSolicitante', nomSolicitante.value)
            formData.append('apeSolicitante', apeSolicitante.value)
            formData.append('telefonoSolicitante', telefonoSolicitante.value)
            formData.append('emailSolicitante', emailSolicitante.value)
            formData.append('nuevoSolicitante', nuevoSolicitante.value)
            formData.append('solicitaOrganizacion', solicitaOrganizacion.value)
            formData.append('razonSocialSolicitante', razonSocialSolicitante.value)
        }

        formData.append('curpPropietario', curpPropietario.value)
        formData.append('curpPropietarioInvalida', curpPropietarioInvalida.value)
        formData.append('idPersonaPropietario', idPersonaPropietario.value)
        formData.append('curpPropietario', curpPropietario.value)
        formData.append('nomPropietario', nomPropietario.value)
        formData.append('apePropietario', apePropietario.value)
        formData.append('telefonoPropietario', telefonoPropietario.value)
        formData.append('emailPropietario', emailPropietario.value)
        formData.append('esSolicitante', esSolicitante.value)

        formData.append('tipoPropiedad', tipoPropiedad.value)

        formData.append('claveCatastral', claveCatastralSinEspacios.value)
        formData.append('tipoPropiedad', tipoPropiedad.value)
        formData.append('callePropiedad', callePropiedad.value)
        formData.append('numeroPropiedad', numeroPropiedad.value)
        formData.append('idColoniaPropiedad', idColoniaPropiedad.value)
        formData.append('idLocalidadPropiedad', idLocalidadPropiedad.value)
        formData.append('superficiePropiedad', superficiePropiedad.value)
        formData.append('superficieConstruccionPropiedad', superficieConstruccionPropiedad.value)
        formData.append('idContactoPropiedad', idContactoPropiedad.value)
        formData.append('imgCroquisPropiedad', imgCroquisPropiedad.value)
        formData.append('imgCroquisAux', imgCroquisAux.value)
        formData.append('borraArchivoCroquis', borraArchivoCroquis.value)
        formData.append('debeSubirNuevoCroquis', debeSubirNuevoCroquis.value)

        formData.append('idDestinoObra', idDestinoObra.value)
        formData.append('idSector', idSector.value)
        formData.append('fecha_ingreso', fecha_ingreso.value)
        formData.append('idEstatusSolicitud', idEstatusSolicitud.value)
        formData.append('idContactoSolicitud', idContactoSolicitud.value)
        formData.append('idPropiedadSolicitud', idPropiedadSolicitud.value)

        tramitesSeleccionados.value.forEach((tramiteId) => {
            formData.append('tramitesSeleccionados[]', tramiteId)
        })

        formData.append('referencia', referencia.value)

        const currentPage = router.page.props.paginaActual || 1

        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaInicioQuery', fechaInicioQuery.value)
        formData.append('fechaFinQuery', fechaFinQuery.value)
        formData.append('idRangoFechasQuery', idRangoFechasQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('sortColumn', sortColumn.value)
        formData.append('sortDirection', sortDirection.value)
        formData.append('rangoFechasManual', rangoFechasManual.value)
        formData.append('page', currentPage)

        cambiaTabError.value = false //Indica que NO cambia el tab por error

        //await obtenerPropiedadSolicitud(idSolicitudEditar.value)
        // if (!nuevaPropiedad.value && propiedad?.value?.id_contacto != undefined && propiedad?.value?.id_contacto != idContactoPropiedad.value) {
        if (!nuevaPropiedad.value && idContactoPropiedadAlCargar.value != idContactoPropiedad.value) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                html: `<div style="text-align: center; font-size: 12pt">
                    La PROPIEDAD ya tiene asignado un PROPIETARIO. <br> Si ACEPTAS se hará un CAMBIO DE PROPIETARIO 
                    <br><br>
                    Valores actuales: <br>
                    idContactoPropiedadAlCargar: <strong>${idContactoPropiedadAlCargar.value}</strong> <br>
                    idContactoPropiedad: <strong>${idContactoPropiedad.value}</strong>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            })

            if (result.isDismissed) {
                isSavingModal.value = false
                return
            }
        }

        if (file.value) {
            const nombreArchivoCroquis = generarNombreArchivo(fileName.value)
            formData.append('croquis', file.value, nombreArchivoCroquis)
        }
        try {
            await router.post('/solicitudes/update/' + idSolicitudEditar.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onSuccess: () => {
                    if (borraArchivoCroquis.value && debeSubirNuevoCroquis.value) {
                        isSavingModal.value = false
                        dialogVisible.value = true
                    } else {
                        Swal.fire({
                            toast: true,
                            icon: 'success',
                            position: 'top-end',
                            showConfirmButton: false,
                            title: '¡Solicitud actualizada con éxito!',
                            timer: 2000,
                            timerProgressBar: true
                        })
                        modalBuscarColoniaVisible.value = false
                        modalBuscarLocalidadVisible.value = false
                        isSavingModal.value = false
                        dialogVisible.value = false
                        resetFormData()
                        // fetchSolicitudes(false, true)
                    }
                },
                onFinish: () => {
                    isSavingModal.value = false
                    activeTab.value = router.page.props.flash.activeTab
                    cambiaTabError.value = true //Cambia el tab por error
                },
  
                onError: (errors) => {
                    isSavingModal.value = false

                    const hasValidationErrors = errors && Object.keys(errors).length > 0
                    const errorMessage = hasValidationErrors ? Object.values(errors).join('<br>') : 'Hubo un error al guardar la información.'

                    Swal.fire({
                        icon: hasValidationErrors ? 'warning' : 'error',
                        title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                        html: `<div style="text-align: justify; font-size: 12pt"><ul>${errorMessage}</ul></div>`,
                        confirmButtonText: 'Aceptar',
                        width: '400px',
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

    const isMobile = ref(window.innerWidth <= 450)

    const updateSize = () => {
        isMobile.value = window.innerWidth <= 450
    }

    onUnmounted(() => {
        window.removeEventListener('resize', updateSize)
        // window.Echo.leaveChannel('solicitudes')
        clearInterval(intervalo.value)
    })

    const handleTabClick = (tabName) => {
        cambiaTabError.value = false
        activeTab.value = tabName // Cambia el tab activo
    }

    const shortcuts = [
        // {
        //     id: 1,
        //     text: 'Hoy',
        //     value: () => {
        //         const today = new Date()
        //         idRangoFechasQuery.value = 1
        //         rangoFechasShortcut.value = true
        //         rangoFechasManual.value = false

        //         return [today, today]
        //     }
        // },
        {
            id: 2,
            text: 'Sem. Actual',
            value: () => {
                const today = new Date()
                const dayOfWeek = today.getDay() // 0 (domingo) - 6 (sábado)
                const mondayOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1 // Si es domingo, retrocede 6 días; si no, retrocede (día - 1)
                const start = new Date(today)
                start.setDate(today.getDate() - mondayOffset)
                idRangoFechasQuery.value = 2
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, today]
            }
        },
        {
            id: 3,
            text: 'Mes Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), today.getMonth(), 1) // Primer día del mes actual
                idRangoFechasQuery.value = 3
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, today]
            }
        },
        {
            id: 4,
            text: 'Año Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), 0, 1) // 0 = enero, 1 = día 1
                idRangoFechasQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, today]
            }
        },
        {
            id: 5,
            text: 'Sem. Pasada',
            value: () => {
                const today = new Date()
                const dayOfWeek = today.getDay() // 0 (domingo) - 6 (sábado)
                const currentMondayOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1

                // Lunes pasado = lunes de esta semana - 7 días
                const start = new Date(today)
                start.setDate(today.getDate() - currentMondayOffset - 7)

                // Domingo pasado = lunes pasado + 6 días
                const end = new Date(start)
                end.setDate(start.getDate() + 6)
                idRangoFechasQuery.value = 5
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 6,
            text: 'Mes Pasado',
            value: () => {
                const today = new Date()

                // Primer día del mes pasado
                const start = new Date(today.getFullYear(), today.getMonth() - 1, 1)

                // Último día del mes pasado
                const end = new Date(today.getFullYear(), today.getMonth(), 0) // Día 0 del mes actual = último del anterior
                idRangoFechasQuery.value = 6
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 7,
            text: 'Año Pasado',
            value: () => {
                const start = new Date(new Date().getFullYear() - 1, 0, 1) // 1 de enero del año anterior
                const end = new Date(new Date().getFullYear() - 1, 11, 31) // 31 de diciembre del año anterior
                idRangoFechasQuery.value = 7
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 8,
            text: 'Últ. Semana',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
                idRangoFechasQuery.value = 8
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 9,
            text: 'Últ. Mes',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setMonth(start.getMonth() - 1)
                idRangoFechasQuery.value = 9
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 10,
            text: 'Últ. Año',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setFullYear(start.getFullYear() - 1)
                idRangoFechasQuery.value = 10
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]
            }
        },
        {
            id: 11,
            text: '1er Año Gob',
            value: () => {
                const today = new Date()
                const end = new Date(today.getFullYear(), 9, 31)
                const start = new Date(today.getFullYear(), 10, 1) // 0 = enero, 1 = día 1
                start.setFullYear(start.getFullYear() - 1)
                idRangoFechasQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]    
            }
        },
        {
            id: 11,
            text: '2do Año Gob',
            value: () => {
                const today = new Date()
                const end = new Date(today.getFullYear(), 9, 31)
                const start = new Date(today.getFullYear(), 10, 1) // 0 = enero, 1 = día 1
                start.setFullYear(start.getFullYear() - 1)
                idRangoFechasQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]    
            }
        },
        {
            id: 11,
            text: '2do Año Gob',
            value: () => {
                const today = new Date()
                const end = new Date(today.getFullYear(), 9, 31)
                const start = new Date(today.getFullYear(), 10, 1) // 0 = enero, 1 = día 1
                start.setFullYear(start.getFullYear() - 1)
                idRangoFechasQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasManual.value = false

                return [start, end]    
            }
        }
    ]

    watch(rangoFechasQuery, (nuevoValor) => {
        if (Array.isArray(nuevoValor) && nuevoValor.length === 2 && nuevoValor[0] && nuevoValor[1]) {
            onDateChange(nuevoValor)
            fechaInicioQuery.value = nuevoValor[0]
            fechaFinQuery.value = nuevoValor[1]
        }
    })

    watch(tramitesQuery, () => {
        fetchSolicitudes(false)
    })

    watch(estatusQuery, () => {
        fetchSolicitudes(false)
    })


    function handlePageChange(page) {
        isLoading.value = true;

        router.post('/solicitudes', {
            fechaInicioQuery: fechaInicioQuery.value,
            fechaFinQuery: fechaFinQuery.value,
            nombreQuery: nombreQuery.value,
            tiposTramitesQuery: tiposTramitesQuery.value,
            tramitesQuery: tramitesQuery.value,
            estatusQuery: estatusQuery.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            sortColumn: sortColumn.value,
            sortDirection: sortDirection.value,
            page: page
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
                filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes;
            }
        });
    }

    // Referencia al elemento DOM del input
    const floatingRazonSocialSolicitanteRef = ref(null);

    // Usa 'watch' para observar los cambios en 'solicitaOrganizacion'
    watch(solicitaOrganizacion, async (newValue) => {
    if (newValue) {
        // Cuando el checkbox se marca (newValue es true)
        // Usamos nextTick para esperar a que el DOM se actualice
        // antes de intentar enfocar el input.
        await nextTick();
        if (floatingRazonSocialSolicitanteRef.value) 
        {
            floatingRazonSocialSolicitanteRef.value.focus();
        }
    }
    });
</script>

<template>
    <el-dialog v-model="dialogVisible" :width="dialogWidth" :before-close="handleClose" :close-on-click-modal="false" :close-on-press-escape="false" top="6vh">
        <template #header>
            <div class="flex items-center justify-between">
                <div v-if="paraEditarSolicitud" class="inline-flex items-center justify-center text-base font-medium whitespace-nowrap">
                    <span v-if="solicitudBloqueada" class="pl-0 pr-2 py-0 flex items-center justify-center">Solicitud</span>
                    <span v-else class="pl-0 pr-2 py-0 flex items-center justify-center">Editar solicitud</span>
                    <span class="bg-color1 text-white px-3 py-0 rounded-r-md flex items-center justify-center">
                        N°
                        {{ (idSolicitudEditar % 10000).toString().padStart(4, '0') }}
                    </span>
                </div>
                <span v-else class="whitespace-nowrap">Nueva solicitud</span>
                
                <div v-if="tramitesSeleccionados.length" class="flex flex-wrap gap-2 w-full md:flex-wrap ml-4">
                    <div
                        v-for="tramiteId in tramitesSeleccionados"
                        :key="tramiteId"
                        class="bg-color3-50 text-color3-800 text-md px-2 py-1 rounded-full flex items-center justify-center gap-1 shadow-sm w-[calc(50%-theme('gap.2')/2)] md:w-auto">
                        <span class="text-[11px] flex-grow text-center">
                            {{ tiposTramites.flatMap((t) => t.tramites).find((t) => t.id === tramiteId)?.nombre }}
                        </span>     
                    </div>
                </div>
            </div>
        </template>
        <div
            v-if="isLoadingModal"
            style="
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 50;
                background-color: rgba(255, 255, 255, 0.7);
                width: 100%;
                height: 100%;">
            <svg class="animate-spin" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4" cx="12" cy="12" r="10"></circle>
                <path style="opacity: 0.75; fill: currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span style="margin-left: 0.5rem; color: gray">Cargando...</span>
        </div>
        <div
            v-if="isSavingModal"
            style="position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 50;
                background-color: rgba(255, 255, 255, 0.75);
                width: 100%;
                height: 100%;">
            <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                <path
                    d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"
                />
            </svg>
            <span style="margin-left: 0.5rem; color: gray">Guardando...</span>
        </div>
        <div
            v-if="isProcessingModal"
            style="
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                background-color: rgba(255, 255, 255, 0.8);
                width: 100%;
                height: 100%;">
            <svg style="width: 3rem; height: 3rem; color: #4b5563" class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span style="margin-top: 0.75rem; font-size: 1.1rem; font-weight: 500; color: #4b5563">Procesando...</span>
        </div>
        <div
            v-if="isUploading || isProcessingFile"
            style="position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                background-color: rgba(255, 255, 255, 0.8);
                width: 100%;
                height: 100%;">
            <svg v-if="isProcessingFile" style="width: 3rem; height: 3rem; color: #4b5563" class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span style="margin-top: 0.75rem; font-size: 1.1rem; font-weight: 500; color: #4b5563">
                {{ isUploading ? 'Subiendo archivo...' : 'Procesando archivo en el servidor...' }}
            </span>
            <div v-if="isUploading" style="margin-top: 1rem; width: 300px">
                <div class="w-full bg-gray-200 rounded h-4 overflow-hidden">
                    <div class="bg-color2-600 h-4 transition-all duration-500 ease-in-out" :style="{ width: uploadProgress + '%', opacity: 1 }"></div>
                </div>
                <div style="margin-top: 0.5rem; text-align: center; font-size: 0.9rem; color: #4b5563">{{ uploadProgress }}%</div>
            </div>
        </div>
        <div class="flex flex-col mb-5 md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-4">
            <div class="relative w-full md:w-auto flex flex-col items-center md:items-start">
                <label class="block text-sm text-gray-500 dark:text-gray-400">Fecha</label>
                <input
                    v-model="fecha_ingreso"
                    type="date"
                    :class="{
                        'block w-full md:w-auto py-2.5 pb-1 px-0 text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer text-right md:text-left':
                            !solicitudBloqueada,
                        'block w-full md:w-auto p-0 m-0 text-sm text-gray-900 bg-color3-50 border-0 appearance-none dark:text-gray-400 focus:outline-none focus:ring-0 peer text-right md:text-cen':
                            solicitudBloqueada 
                    }"
                    :disabled="solicitudBloqueada"
                    required
                />
            </div>
            <div class="flex flex-col w-full md:w-60 lg:w-[18rem] p-0 m-0">
                <div :class="['flex items-center text-sm text-gray-500 dark:text-gray-400', solicitudBloqueada ? 'justify-center p-2 mb-1' : 'justify-start m-0 p-0']" style="margin: 0; padding: 0">
                    <span class="ml-0 pl-1 mb-2 mr-2 leading-none">Estatus</span>
                    <span
                        v-if="idEstatusSolicitudEditable && idEstatusSolicitud"
                        class="w-2 h-2 rounded-sm inline-block mb-2"
                        :style="{
                            backgroundColor: getColorById(idEstatusSolicitud)
                        }"
                    ></span>
                </div>
                <el-select v-if="idEstatusSolicitudEditable" v-model="idEstatusSolicitud" placeholder="Selecciona un ESTATUS" class="custom-select">
                    <el-option class="custom-option" v-for="item in estatusSolicitudSelect" :key="item.id" :label="item.nombre" :value="item.id" :style="{ color: item.color }">
                        <span class="w-3 h-3 rounded-sm inline-block mr-2" :style="{ backgroundColor: item.color }"></span>
                        {{ item.nombre }}
                    </el-option>
                </el-select>

                <div v-else class="py-1 px-1 text-center text-white font-bold rounded-full" :style="{ backgroundColor: colorEstatusSolicitud }">
                    {{ nombreEstatusSolicitud }}
                </div>
            </div>

            <!-- <div class="flex flex-col items-end w-full md:w-auto" v-if="!paraEditarSolicitud">
                    <el-switch
                    v-model="esFolioManual"
                    class="custom-switch w-full md:w-auto"
                    size="large"
                    inactive-text="Folio automático"
                    active-text="Folio manual"/>
                    <div v-if="esFolioManual" class="mt-0 w-90">
                        <input
                        id="folioManual"
                        v-model="folioManual"
                        type="text"
                        class="block w-full py-0.5 pb-1 px-0 text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-color1 focus:outline-none focus:ring-0 focus:border-color1 peer text-right"
                        placeholder="Ingresa el folio"/>
                    </div>
                </div> -->
        </div>
        <div class="border-b-2 mb-4 border-gray-200 dark:border-gray-700">
            <ul class="flex overflow-x-auto whitespace-nowrap md:flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                <li v-if="!(idEstatusSolicitud === 6 && tramitesSeleccionados.length === 0)" class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('tramite')"
                        :class="activeTab === 'tramite' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'tramite' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 -960 960 960">
                            <path
                                d="M760-200H320q-33 0-56.5-23.5T240-280v-560q0-33 23.5-56.5T320-920h280l240 240v400q0 33-23.5 56.5T760-200ZM560-640v-200H320v560h440v-360H560ZM160-40q-33 0-56.5-23.5T80-120v-560h80v560h440v80H160Zm160-800v200-200 560-560Z"
                            />
                        </svg>
                        <span class="hidden md:inline">Trámite(s) </span>
                    </a>
                </li>
                <li v-if="!tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION) && tramitesSeleccionados.length > 0" class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('propiedad')"
                        :class="activeTab === 'propiedad' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'propiedad' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 -960 960 960">
                            <path
                                d="M80-120v-650l200-150 200 150v90h400v560H80Zm80-80h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 0h80v-80h-80v80Zm0 480h480v-400H320v400Zm240-240v-80h160v80H560Zm0 160v-80h160v80H560ZM400-440v-80h80v80h-80Zm0 160v-80h80v80h-80Z"
                            />
                        </svg>
                        <span class="hidden md:inline">Propiedad</span>
                    </a>
                </li>
                <li v-if="!tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION) && tramitesSeleccionados.length > 0" class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('propietario')"
                        :class="activeTab === 'propietario' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'propietario' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 0 16 16">
                            <path
                                d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6m5 2.755C12.146 12.825 10.623 12 8 12s-4.146.826-5 1.755V14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1z"/>
                        </svg>
                        <span class="hidden md:inline">Propietario</span>
                    </a>
                </li>
                <li v-if="(esSolicitante === '0' || tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION)) && tramitesSeleccionados.length > 0" class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('solicitante')"
                        :class="activeTab === 'solicitante' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'solicitante' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                        <span class="hidden md:inline">Solicitante</span>
                    </a>
                </li>
                <li v-if="tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION) && tramitesSeleccionados.length > 0" class="inline-block md:me-2">
                   <a
                        href="#"
                        @click.prevent="handleTabClick('referencia')"
                        :class="activeTab === 'referencia' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'referencia' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-5 h-5 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 -960 960 960">
                            <path d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                        </svg>
                        <span class="hidden md:inline">Referencia</span>
                    </a>
                </li>
                <li
                    v-if="(claveCatastral.length == 23 || 
                    (tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION) && paraEditarSolicitud))
                    && ((!(idEstatusSolicitud === 6 && !isFileLoaded) && paraEditarSolicitud) 
                    || (!paraEditarSolicitud && !nuevaPropiedad)) && (tramitesSeleccionados.length > 0 && paraEditarSolicitud)"
                    class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('croquis')"
                        :class="activeTab === 'croquis' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                        <svg
                            :class="activeTab === 'croquis' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 -960 960 960">
                            <path
                                d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Zm140-360q25 0 42.5-17.5T400-620q0-25-17.5-42.5T340-680q-25 0-42.5 17.5T280-620q0 25 17.5 42.5T340-560Z"
                            />
                        </svg>
                        <span class="hidden md:inline">Croquis</span>
                    </a>
                </li>
            </ul>
        </div>
        <div v-if="activeTab === 'propiedad'" class="space-y-4">
            <h3 class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                <span>Datos de la propiedad</span>
                <span v-if="nuevaPropiedad" style="letter-spacing: 0.5px" class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">Nueva</span>
            </h3>
            <div class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-1/4">
                    <input
                        v-model="claveCatastral"
                        ref="claveCatastralRef"
                        type="text"
                        maxlength="23"
                        @focus="handleFocusClaveCatastral"
                        @input="formatClaveCatastral"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': claveCatastralEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !claveCatastralEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': claveCatastralEditable
                            }
                        ]"
                        :disabled="!claveCatastralEditable"
                        required
                    />
                    <label
                        style="z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                    >
                        <span>Clave catastral</span>
                    </label>
                </div>
                <div v-if="claveCatastralCompleta" class="relative z-0 mb-5 group peer w-full md:w-1/4">
                    <template v-if="tipoPropiedadEditable">
                        <select
                            ref="floatingTipoPropiedadRef"
                            v-model="tipoPropiedad"
                            @focus="handleFocusTipoPropiedad(true)"
                            @blur="handleFocusTipoPropiedad(false)"
                            class="pt-[10px] pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50"
                            required>
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="(tipo, index) in tiposPropiedades" :key="index" :value="tipo.id">
                                {{ tipo.nombre }}
                            </option>
                        </select>
                        <label
                            style="z-index: 10"
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'scale-85 -translate-y-9': tipoPropiedad || isFocusedTipoPropiedad,
                                'scale-90 translate-y-[-11px]': !tipoPropiedad && !isFocusedTipoPropiedad
                            }"
                            :style="{
                                top: tipoPropiedad || isFocusedTipoPropiedad ? '25px' : '24px'
                            }">
                            Tipo
                        </label>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreTipoPropiedad"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': tipoPropiedadEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !tipoPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': tipoPropiedadEditable
                                }
                            ]"
                            :disabled="!tipoPropiedadEditable"
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'translate-y-0 scale-90': !nombreTipoPropiedad
                            }">
                            Tipo
                            <button v-if="!tipoPropiedadEditable && !tipoPropiedadBloqueado" class="bg-transparent text-gray-500 hover:text-color1" @click="habilitarCaptura('tipoPropiedadEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </template>
                </div>
                <div v-if="claveCatastralCompleta" class="relative z-0 mb-5 group peer w-full md:w-1/5">
                    <input
                        v-model="superficiePropiedad"
                        ref="floatingSuperficiePropiedadRef"
                        type="number"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': superficiePropiedadEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !superficiePropiedadEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': superficiePropiedadEditable
                            }
                        ]"
                        required
                        :disabled="!superficiePropiedadEditable"
                        :placeholder="''"/>
                    <label
                        style="z-index: 10"
                        class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                        :class="{
                            'translate-y-0 scale-90': !superficiePropiedad
                        }">
                        <span class="hidden sm:block" @click="focusInputSuperficiePropiedad">
                            Superficie (m²)
                            <button
                                v-if="!superficiePropiedadEditable && !superficiePropiedadBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('superficiePropiedadEditable')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </span>
                        <span class="block sm:hidden">
                            Superficie (m²)
                            <button
                                v-if="!superficiePropiedadEditable && !superficiePropiedadBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('superficiePropiedadEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </span>
                    </label>
                </div>
                <div v-if="claveCatastralCompleta && tipoPropiedad == 2" class="relative z-0 mb-1 md:mb-5 group peer w-full md:w-1/4">
                    <div v-if="tipoPropiedad == 2">
                        <input
                            v-model="superficieConstruccionPropiedad"
                            ref="floatingSuperficieConstruccionPropiedadRef"
                            type="number"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': superficieConstruccionPropiedadEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !superficieConstruccionPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': superficieConstruccionPropiedadEditable
                                }
                            ]"
                            :disabled="!superficieConstruccionPropiedadEditable"
                            required
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{ 'translate-y-0 scale-90': superficieConstruccionPropiedad === null || superficieConstruccionPropiedad === '' }">
                            <span class="hidden sm:block" @click="focusInputSuperficieConstruccionPropiedad">
                                Sup. en construcción (m²)
                                <button
                                    v-if="!superficieConstruccionPropiedadEditable && !superficieConstruccionPropiedadBloqueado"
                                    class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                    @click="habilitarCaptura('superficieConstruccionPropiedadEditable')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                        <path
                                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                    </svg>
                                </button>
                            </span>
                            <span class="block sm:hidden">
                                Superficie en construcción (m²)
                                <button
                                    v-if="!superficieConstruccionPropiedadEditable && !superficieConstruccionPropiedadBloqueado"
                                    class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                    @click="habilitarCaptura('superficieConstruccionPropiedadEditable')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                        <path
                                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                        />
                                    </svg>
                                </button>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div v-if="claveCatastralCompleta" class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-[41%]">
                    <input
                        v-model="callePropiedad"
                        ref="floatingCallePropiedadRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': callePropiedadEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !callePropiedadEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': callePropiedadEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!callePropiedadEditable"
                        required/>
                    <label
                        style="z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        Calle
                        <button
                            v-if="!callePropiedadEditable && !callePropiedadBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('callePropiedadEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
                <div class="relative z-0 mb-5 group peer w-full md:w-[15%]">
                    <input
                        v-model="numeroPropiedad"
                        ref="floatingNumeroPropiedadRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': numeroPropiedadEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !numeroPropiedadEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': numeroPropiedadEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!numeroPropiedadEditable"
                        required/>
                    <label
                        style="z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N°
                        <button
                            v-if="!numeroPropiedadEditable && !numeroPropiedadBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('numeroPropiedadEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
                <div class="relative z-0 mb-1 md:mb-5 group peer w-full md:w-[41%]">
                    <div class="relative">
                        <input
                            v-model="nombreColoniaPropiedad"
                            type="text"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': idColoniaPropiedadEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !idColoniaPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idColoniaPropiedadEditable
                                }
                            ]"
                            placeholder=""
                            disabled
                            required/>
                        <label
                            style="z-index: 10"
                            :class="[
                                'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                idColoniaPropiedadEditable
                                    ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                    : '-translate-y-8 peer-placeholder-shown:scale-90',
                                {
                                    'peer-placeholder-shown:translate-y-0': idColoniaPropiedadEditable
                                },
                                {
                                    'peer-placeholder-shown:translate-y-[-0.5rem]': !idColoniaPropiedadEditable
                                }
                            ]">
                            Colonia
                            <button
                                v-if="!idColoniaPropiedadEditable && !idColoniaPropiedadBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('idColoniaPropiedadEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <!-- Uso de `group-hover` para cambiar el color -->
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </div>
                    <button
                        type="button"
                        v-if="idColoniaPropiedadEditable"
                        @click="abreModalBuscarColonia('propiedad')"
                        title="Buscar colonia..."
                        class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-color1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <div v-if="claveCatastralCompleta" class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-[50%]">
                    <div class="relative">
                        <input
                            v-model="nombreLocalidadPropiedad"
                            type="text"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': idLocalidadPropiedadEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !idLocalidadPropiedadEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idLocalidadPropiedadEditable
                                }
                            ]"
                            placeholder=""
                            disabled
                            required/>
                        <label
                            style="z-index: 10"
                            :class="[
                                'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                                idLocalidadPropiedadEditable
                                    ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                    : '-translate-y-8 peer-placeholder-shown:scale-90',
                                {
                                    'peer-placeholder-shown:translate-y-0': idLocalidadPropiedadEditable
                                },
                                {
                                    'peer-placeholder-shown:translate-y-[-0.5rem]': !idLocalidadPropiedadEditable
                                }
                            ]">
                            Localidad
                            <button
                                v-if="!idLocalidadPropiedadEditable && !idLocalidadPropiedadBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('idLocalidadPropiedadEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <!-- Uso de `group-hover` para cambiar el color -->
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </div>
                    <button
                        type="button"
                        v-if="idLocalidadPropiedadEditable"
                        @click="abreModalBuscarLocalidad('propiedad')"
                        title="Buscar localidad..."
                        class="absolute right-0 top-1/2 transform -translate-y-1/2 p-1 text-gray-500 hover:text-color1-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-color1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 scale-110" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.42-1.42l4.9 4.9a1 1 0 01-1.42 1.42l-4.9-4.9zM8 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div v-if="activeTab === 'solicitante'" class="space-y-4">
            <div class="flex items-start w-[61.5%]" :class="{ 'flex-col': solicitaOrganizacion }">
                <h3 class="flex items-center mr-4 text-lg font-semibold text-gray-800 dark:text-white flex-shrink-0">
                <span>Datos del/a solicitante </span>
                <span
                    v-if="nuevoSolicitante"
                    style="letter-spacing: 0.5px"
                    class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">
                    Nuevo(a)
                </span>
                </h3>

                <div class="flex items-start flex-col w-full" :class="{ 'flex-col': solicitaOrganizacion }">
                    <div class="flex items-center py-1">
                        <input
                        id="solicitaOrganizacion"
                        v-model="solicitaOrganizacion"
                        v-if="idEstatusSolicitud < 6"
                        type="checkbox"
                        class="rounded border-gray-300 mr-2 text-color1-600 shadow-sm focus:ring-color1-500"/>
                         <label for="solicitaOrganizacion" class="block text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            Organización / Razón social
                        </label>
                    </div>

                    <div
                        v-if="solicitaOrganizacion"
                        class="relative z-0 mt-0 group peer w-full">
                        <input
                        v-model="razonSocialSolicitante"
                        ref="floatingRazonSocialSolicitanteRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                            'pb-0 py-0 px-0': razonSocialSolicitanteEditable,
                            'bg-color3-50 p-0 m-0 mb-5': !razonSocialSolicitanteEditable,
                            'border-b-2 border-gray-300 mb-5 focus:border-color1 dark:border-gray-600': razonSocialSolicitanteEditable,
                            }
                        ]"
                        placeholder=""
                        :disabled="!razonSocialSolicitanteEditable"
                        required/>
                    </div>
                </div>
            </div>
            <div class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-[25%]">
                    <input
                        v-model="curpSolicitante"
                        ref="floatingCURPSolicitanteRef"
                        autocomplete="off"
                        type="text"
                        @input="inputCurpSolicitante"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': curpSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !curpSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': curpSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :maxlength="LONGITUD_CURP"
                        :disabled="!curpSolicitanteEditable"
                        @keyup.enter="abreModalBuscarPersona('solicitante')"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span v-if="!curpSolicitanteInvalida" class="flex-shrink-0">C U R P</span>
                        <span class="flex items-center bg-color1-50 text-color1-800 px-1 rounded-md" style="letter-spacing: 0em; z-index: 10" v-else="curpSolicitanteInvalida">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                                />
                            </svg>
                            CURP inválida
                        </span>
                    </label>
                </div>
                <div v-if="curpSolicitanteCompleta" class="relative z-0 mb-5 group peer w-full md:w-[35%]">
                    <input
                        v-model="nomSolicitante"
                        ref="floatingNomSolicitanteRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': nomSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !nomSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': nomSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!nomSolicitanteEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N o m b r e
                        <button
                            v-if="!nomSolicitanteEditable && !nomSolicitanteBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('nomSolicitanteEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
                <div v-if="curpSolicitanteCompleta" class="relative z-0 mb-1 md:mb-5 group peer w-full md:w-[37%]">
                    <input
                        v-model="apeSolicitante"
                        ref="floatingApeSolicitanteRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': apeSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !apeSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': apeSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!apeSolicitanteEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        A p e l l i d o s
                        <button
                            v-if="!apeSolicitanteEditable && !apeSolicitanteBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('apeSolicitanteEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
            </div>
            <div v-if="curpSolicitanteCompleta" class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap" style="margin-bottom: -1rem">
                <div class="relative z-0 mb-5 group peer w-full md:w-[25%]">
                    <input
                        v-model="telefonoSolicitante"
                        ref="floatingTelefonoSolicitanteRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': telefonoSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !telefonoSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': telefonoSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!telefonoSolicitanteEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block">N ° &nbsp; d e &nbsp; t e l é f o n o</span>
                        <span class="block sm:hidden">T e l é f o n o</span>
                        <button
                            v-if="!telefonoSolicitanteEditable && !telefonoSolicitanteBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('telefonoSolicitanteEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
                <div class="relative z-0 mb-5 group peer w-full md:w-[40%]">
                    <input
                        v-model="emailSolicitante"
                        ref="floatingEmailSolicitanteRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': emailSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !emailSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': emailSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!emailSolicitanteEditable"/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="block">C o r r e o &nbsp; e l e c t r ó n i c o</span>
                        <button
                            v-if="!emailSolicitanteEditable && !emailSolicitanteBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('emailSolicitanteEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
            </div>
        </div>
        <div v-if="activeTab === 'propietario'" class="space-y-4">
            <h3 class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                <span>Datos del/a propietario(a)</span>
                <span v-if="nuevoPropietario" style="letter-spacing: 0.5px" class="flex items-center bg-color2-600 text-xs text-white py-1 font-thin px-2 rounded-lg ml-2">Nuevo(a)</span>
            </h3>
            <div class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-[25%]">
                    <input
                        v-model="curpPropietario"
                        @input="inputCurpPropietario"
                        ref="floatingCURPPropietarioRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': curpPropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !curpPropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': curpPropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :maxlength="LONGITUD_CURP"
                        :disabled="!curpPropietarioEditable"
                        @keyup.enter="abreModalBuscarPersona('propietario')"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span v-if="!curpPropietarioInvalida" class="flex-shrink-0">C U R P</span>
                        <span class="flex items-center bg-color1-50 text-color1-800 px-1 rounded-md" style="letter-spacing: 0em; z-index: 10" v-else="curpPropietarioInvalida">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                                />
                            </svg>
                            CURP inválida
                        </span>
                    </label>
                </div>
                <div v-if="curpPropietarioCompleta" class="relative z-0 mb-5 group peer w-full md:w-[35%]">
                    <input
                        v-model="nomPropietario"
                        ref="floatingNomPropietarioRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': nomPropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !nomPropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': nomPropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!nomPropietarioEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        N o m b r e
                        <button
                            v-if="!nomPropietarioEditable && !nomPropietarioBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('nomPropietarioEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                            </svg>
                        </button>
                    </label>
                </div>
                <div v-if="curpPropietarioCompleta" class="relative z-0 mb-1 md:mb-5 group peer w-full md:w-[37%]">
                    <input
                        v-model="apePropietario"
                        ref="floatingApePropietarioRef"
                        autocomplete="off"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': apePropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !apePropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': apePropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!apePropietarioEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        A p e l l i d o s
                        <button
                            v-if="!apePropietarioEditable && !apePropietarioBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('apePropietarioEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                            </svg>
                        </button>
                    </label>
                </div>
            </div>
            <div v-if="curpPropietarioCompleta" class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-[25%]">
                    <input
                        v-model="telefonoPropietario"
                        ref="floatingTelefonoPropietarioRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': telefonoPropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !telefonoPropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': telefonoPropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!telefonoPropietarioEditable"
                        required/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="hidden sm:block">N ° &nbsp; d e &nbsp; t e l é f o n o</span>
                        <span class="block sm:hidden">T e l é f o n o</span>
                        <button
                            v-if="!telefonoPropietarioEditable && !telefonoPropietarioBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('telefonoPropietarioEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                />
                            </svg>
                        </button>
                    </label>
                </div>
                <div class="relative z-0 mb-5 group peer w-full md:w-[40%]">
                    <input
                        v-model="emailPropietario"
                        ref="floatingEmailPropietarioRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': emailPropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !emailPropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': emailPropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!emailPropietarioEditable"/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="block">C o r r e o &nbsp; e l e c t r ó n i c o</span>
                        <button
                            v-if="!emailPropietarioEditable && !emailPropietarioBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('emailPropietarioEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                            </svg>
                        </button>
                    </label>
                </div>
                <div v-if="esSolicitanteEditable" class="relative z-0 mb-5 group peer w-full md:w-[32%]">
                    <select
                        v-model="esSolicitante"
                        class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50 p-2.5"
                        required>
                        <option value="" disabled selected style="display: none"></option>
                        <option :key="'1'" :value="'1'">SÍ</option>
                        <option :key="'0'" :value="'0'">NO</option>
                    </select>
                    <label
                        style="z-index: 10"
                        :class="[
                            'absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform scale-85 top-3 -z-10 origin-[0]',
                            esSolicitanteEditable || esSolicitante == null
                                ? 'peer-focus:-translate-y-8 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1 -translate-y-6 peer-placeholder-shown:scale-90'
                                : '-translate-y-8 peer-placeholder-shown:scale-90',
                            {
                                'peer-placeholder-shown:translate-y-0': esSolicitanteEditable
                            },
                            {
                                'peer-placeholder-shown:translate-y-[-0.5rem]': esSolicitante == null
                            }
                        ]">
                        <span class="hidden sm:block">¿Es el/la solicitante?</span>
                        <span class="block sm:hidden">¿Solicitante(a)?</span>
                    </label>
                </div>
            </div>
        </div>
        <div v-if="activeTab == 'tramite'" class="space-y-4">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-2 md:gap-4 w-full">
                <h3 class="flex items-center text-lg font-semibold text-gray-800 dark:text-white flex-shrink-0">
                    Trámite(s) a realizar 
                </h3>
                <div v-if="tramitesSeleccionados.length && !solicitudBloqueada" class="flex flex-wrap gap-2 w-full md:flex-wrap">
                    <div
                        v-for="tramiteId in tramitesSeleccionados"
                        :key="tramiteId"
                        class="bg-color1-50 text-color1-800 text-xs font-semibold px-2 py-1 rounded-md flex items-center justify-center gap-1 shadow-sm w-[calc(50%-theme('gap.2')/2)] md:w-auto">
                        <span class="text-[10px] flex-grow text-center">
                            {{ tiposTramites.flatMap((t) => t.tramites).find((t) => t.id === tramiteId)?.nombre_abreviado }}
                        </span>
                        <button
                            @click="tramitesSeleccionados = tramitesSeleccionados.filter((id) => id !== tramiteId)"
                            class="text-color1-800 text-[8px] font-bold bg-color1-50 w-3 h-3 flex items-center justify-center rounded-full hover:bg-red-200">
                            X
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="solicitudBloqueada" class="flex items-center overflow-x-auto">
                <div v-if="tramitesSeleccionados.length" class="flex flex-wrap gap-2">
                    <div
                        v-for="tramiteId in tramitesSeleccionados"
                        :key="tramiteId"
                        class="bg-transparent border-2 border-color1-300 text-color1-600 text-sm font-semibold px-2 py-2 rounded-xl flex items-center gap-1 shadow-sm w-full md:w-auto justify-center">
                        <span>
                            {{ tiposTramites.flatMap((t) => t.tramites).find((t) => t.id === tramiteId)?.nombre }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="flex items-center overflow-x-auto">
                <div class="h-[30vh] overflow-y-auto mx-auto">
                    <div v-if="!isMobile">
                        <table class="min-w-fit mx-auto table-fixed border-separate border-spacing-x-1">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th
                                        v-for="tipoTramite in tiposTramites"
                                        :key="tipoTramite.id"
                                        :style="{
                                            width: `${100 / tiposTramites.length}%`
                                        }"
                                        class="bg-gray-100 uppercase px-4 py-2 text-sm font-semibold text-gray-900 dark:text-white text-center">
                                        {{ tipoTramite.nombre }}
                                    </th>
                                </tr>
                                <tr>
                                    <th class="h-2 bg-white" colspan="100"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="index in Math.max(...tiposTramites.map((t) => t.tramites.length))" :key="index">
                                    <td
                                        v-for="tipoTramite in tiposTramites"
                                        :key="tipoTramite.id"
                                        class="px-1 py-1 text-center h-[55px] whitespace-normal"
                                        :style="{
                                            width: 760 / tiposTramites.length + 'px',
                                            minWidth: 760 / tiposTramites.length + 'px'
                                        }">
                                        <div
                                            v-if="tipoTramite.tramites[index - 1]"
                                            @click="actualizarTramitesSeleccionados(tipoTramite.tramites[index - 1].id)"
                                            :class="{
                                                'bg-color1-600 text-white font-bold border-color1-800': tramitesSeleccionados.includes(tipoTramite.tramites[index - 1].id),
                                                'text-gray-700 bg-gray-50 border-gray-400 hover:shadow-md hover:border-2 hover:text-color1-800': !tramitesSeleccionados.includes(
                                                    tipoTramite.tramites[index - 1].id
                                                )
                                            }"
                                            class="flex items-center justify-center h-full w-full rounded-md px-4 py-2 cursor-pointer border transition-colors hover:shadow-lg hover:-translate-y-1">
                                            <span class="text-xs dark:text-gray-300 text-center leading-tight">
                                                {{ tipoTramite.tramites[index - 1]?.nombre }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <tbody v-if="isMobile">
                        <template v-for="tipoTramite in tiposTramites" :key="tipoTramite.id">
                            <tr>
                                <td class="px-4 py-2 text-center font-bold align-middle w-[40px] h-[60px] border border-gray-300" :rowspan="tipoTramite.tramites.length || 1">
                                    <span class="inline-block [writing-mode:vertical-lr] [text-orientation:upright]">
                                        {{ tipoTramite.nombre }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center w-[120px] h-[60px] overflow-hidden">
                                    <div
                                        v-if="tipoTramite.tramites.length"
                                        @click="actualizarTramitesSeleccionados(tipoTramite.tramites[0].id)"
                                        :class="{
                                            'bg-color1-600 text-white font-bold border-color1-800': tramitesSeleccionados.includes(tipoTramite.tramites[0].id),
                                            'text-gray-700 border-gray-400  hover:bg-gray-100 hover:font-semibold hover:border-color1-500 hover:border-2': !tramitesSeleccionados.includes(
                                                tipoTramite.tramites[0].id
                                            )
                                        }"
                                        class="flex items-center justify-center h-full w-full rounded-md px-4 py-2 cursor-pointer border transition-colors hover:shadow-lg hover:-translate-y-1">
                                        <span class="text-xs dark:text-gray-300 text-center leading-tight whitespace-normal">
                                            {{ tipoTramite.tramites[0]?.nombre }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="tramite in tipoTramite.tramites.slice(1)" :key="tramite.id">
                                <td class="px-3 py-2 text-center w-[100px] h-[60px] overflow-hidden">
                                    <div
                                        @click="actualizarTramitesSeleccionados(tramite.id)"
                                        :class="{
                                            'bg-color1-600 text-white font-bold border-color1-800': tramitesSeleccionados.includes(tramite.id),
                                            'text-gray-700 border-gray-400  hover:bg-gray-100 hover:font-semibold hover:border-color1-500 hover:border-2': !tramitesSeleccionados.includes(tramite.id)
                                        }"
                                        class="flex items-center justify-center h-full w-full rounded-md px-4 py-2 cursor-pointer border transition-colors hover:shadow-lg hover:-translate-y-1">
                                        <span class="text-xs dark:text-gray-300 text-center leading-tight whitespace-normal">
                                            {{ tramite.nombre }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </div>
            </div>
            <div class="flex flex-col md:flex-row md:justify-between md:items-start md:space-x-4">
                <div class="relative z-0 mt-3 mb-5 group peer w-full md:w-[40%]">
                    <template v-if="idDestinoObraEditable">
                        <select
                            v-model="idDestinoObra"
                            @focus="handleFocusDestinoObra(true)"
                            @blur="handleFocusDestinoObra(false)"
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50 p-2.5"
                            required>
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="(destino, index) in destinosObras" :key="index" :value="destino.id">
                                {{ destino.nombre }}
                            </option>
                        </select>
                        <label
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'scale-85 -translate-y-9': idDestinoObra || isFocusedDestinoObra,
                                'scale-90 translate-y-[-11px]': !idDestinoObra && !isFocusedDestinoObra
                            }"
                            :style="{
                                top: idDestinoObra || isFocusedDestinoObra ? '25px' : '24px'
                            }">
                            <span class="hidden sm:block">Destino de la obra</span>
                            <span class="block sm:hidden">Dest. obra</span>
                        </label>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreDestinoObra"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': idDestinoObraEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !idDestinoObraEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idDestinoObraEditable
                                }
                            ]"
                            :disabled="!idDestinoObraEditable"
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'translate-y-0 scale-90': !idDestinoObra
                            }">
                            Destino Obra
                            <button
                                v-if="!idDestinoObraEditable && !idDestinoObraBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('idDestinoObraEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <!-- Uso de `group-hover` para cambiar el color -->
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </template>
                </div>

                <div class="relative z-0 mt-3 mb-5 group peer w-full md:w-[40%]">
                    <template v-if="idSectorEditable">
                        <select
                            v-model="idSector"
                            @focus="handleFocusSector(true)"
                            @blur="handleFocusSector(false)"
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-gray-50 p-2.5"
                            required>
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="(sector, index) in sectores" :key="index" :value="sector.id">
                                {{ sector.nombre }}
                            </option>
                        </select>
                        <label
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'scale-85 -translate-y-9': idSector || isFocusedSector,
                                'scale-90 translate-y-[-11px]': !idSector && !isFocusedSector
                            }"
                            :style="{
                                top: idSector || isFocusedSector ? '25px' : '24px'
                            }">
                            <span>Sector</span>
                        </label>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreSector"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': idSectorEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !idSectorEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idSectorEditable
                                }
                            ]"
                            :disabled="!idSectorEditable"
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'translate-y-0 scale-90': !idSector
                            }">
                            Sector
                            <button
                                v-if="!idSectorEditable && !idSectorBloqueado"
                                class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                                @click="habilitarCaptura('idSectorEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <!-- Uso de `group-hover` para cambiar el color -->
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </template>
                </div>
            </div>
        </div>
        <div v-if="activeTab == 'croquis'" class="space-y-4">
            <h3 class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                <span>Croquis de localización </span>
            </h3>
            <div class="relative">
                <div
                    ref="dropZoneCroquis"
                    v-if="!isFileLoaded && !isLoadingModal && !isUploading"
                    class="flex items-center justify-center w-full h-60 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 cursor-pointer transition-all duration-200 focus:outline-none focus:border-color1-500 focus:ring-1 focus:ring-color1-50" 
                    @click="triggerFileInput"
                    @dragover.prevent="handleDragOver"
                    @drop.prevent="handleDrop"
                    tabindex="0"
                    @focus="isDropZoneCroquisFocused = true"
                    @blur="isDropZoneCroquisFocused = false"
                    @keydown.enter="triggerFileInput">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-color1-500 dark:text-color1-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-semibold">Click para subir el archivo</span>
                            o arrastra y suelta
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG o JPEG</p>
                    </div>
                </div>
                <div v-else class="flex items-center justify-center mb-10">
                    <div v-if="!isLoadingModal && !isUploading && !isProcessingFile" class="flex flex-col items-center md:flex-row md:items-center">
                        <div class="relative w-16 h-16 cursor-pointer" @click="showPreviewDialog">
                            <component :is="fileIcon" class="w-full h-full"></component>
                            <button
                                v-if="!isLoadingModal && !solicitudBloqueada && (paraEditarSolicitud || nuevaPropiedad)"
                                class="absolute top-1 right-0 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center focus:outline-none"
                                @click.stop="removeFile"
                                title="Eliminar imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                    <path
                                        fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>          

                        <div class="mt-2 md:mt-0 md:ml-4" v-if="!isLoadingModal && !isUploading && !isProcessingFile">
                            <span class="font-semibold text-center md:text-left" style="font-style: italic">{{ fileName }}</span>
                        </div>
                    </div>
                </div>
                <input type="file" class="hidden" ref="fileInput" @change="handleFileChange" accept="image/png, image/jpeg, image/jpg" />
            </div>
        </div>
        <div v-if="activeTab == 'referencia'" class="space-y-4">
            <h3 class="flex items-center text-lg font-semibold text-gray-800 dark:text-white">
                <span>Información de referencia</span>
                <button
                    v-if="!referenciaEditable && !referenciaBloqueado"
                    class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                    @click="habilitarCaptura('referenciaEditable')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                        <!-- Uso de `group-hover` para cambiar el color -->
                        <path
                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                    </svg>
                </button>
            </h3>
            <div class="relative">
                <textarea
                    v-model="referencia"
                    ref="floatingReferenciaRef"
                    :rows="3" 
                    maxlength="255"
                    resize="none" 
                    placeholder=" " 
                    class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                    :class="[
                        {
                            'pb-1 pt-1 px-0': referenciaEditable,
                            'bg-color3-50 p-0 m-0 mt-2': !referenciaEditable, /* Fondo para disabled */
                            'border-b-2 border-t-2 border-gray-300 focus:border-color1 dark:border-gray-600': referenciaEditable
                        }
                    ]"
                    :disabled="!referenciaEditable">
                </textarea>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center mb-4">
                <button
                    v-if="paraEditarSolicitud && !solicitudBloqueada"
                    type="button"
                    class="mt-0 bg-color2-700 hover:bg-color2-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color2-300 font-medium rounded-xl text-sm px-4 py-2 focus:outline-none dark:focus:ring-color2-700"
                    @click="actualizarSolicitud"
                >
                    Actualizar
                </button>
                <button
                    v-if="!paraEditarSolicitud"
                    type="button"
                    class="mt-0 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-xl text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="agregarSolicitud"
                >
                    Guardar
                </button>
                <button
                    type="button"
                    class="mt-0 ml-4 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-xl text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                    @click="handleClose"
                >
                    Cerrar
                </button>
            </div>
        </template>
    </el-dialog>

    <el-dialog v-model="previewDialogVisible" class="flex flex-col items-center" style="width: 500px; max-width: 90vw" :before-close="handleCloseModalCroquis">
        <template #header>
            <div style="display: flex; align-items: center" class="text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-2">
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                    <path
                        fill-rule="evenodd"
                        d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
                Vista previa del CROQUIS
            </div>
        </template>
        <div v-if="imageSrc" class="flex justify-center mb-2">
            <img :src="imageSrc" alt="Vista previa" class="w-[80vw] h-auto md:max-w-full" style="max-width: 400px; max-height: 300px" />
        </div>
        <div v-else-if="archivoInvalido" class="flex items-center space-x-4">
            <span class="flex-shrink-0 text-color1-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-16">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                    />
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
                    v-if="permiteDescartarCroquis"
                    type="button"
                    class="mt-2 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="acceptFile">
                    Aceptar
                </button>
                <button
                    v-if="permiteDescartarCroquis"
                    type="button"
                    class="mt-2 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                    @click="rejectFile">
                    Descartar
                </button>
                <button
                    v-if="!permiteDescartarCroquis && !solicitudBloqueada && (paraEditarSolicitud || nuevaPropiedad)"
                    type="button"
                    class="mt-2 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="removeFile">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 mr-2">
                        <path
                            fill-rule="evenodd"
                            d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Eliminar Archivo
                </button>
                <div></div>
                <button
                    v-if="solicitudBloqueada || (!paraEditarSolicitud && !nuevaPropiedad)"
                    type="button"
                    class="mt-2 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                    @click="handleCloseModalCroquis">
                    Cerrar
                </button>
            </div>
        </template>
    </el-dialog>

    <el-dialog v-model="modalBuscarPersonaVisible" :title="'Buscar ' + modalBuscarPersonaTipo" top="30vh" :width="dialogWidthBuscar">
        <div
            v-if="isLoadingModal2"
            style="position: fixed; top: 0; left: 0; display: flex; align-items: center; justify-content: center; z-index: 50; background-color: rgba(255, 255, 255, 0.7); width: 100vw; height: 100vh">
            <svg class="animate-spin" style="width: 3rem; height: 3rem; color: #4b5563" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4" cx="12" cy="12" r="10"></circle>
                <path style="opacity: 1; stroke: currentColor; stroke-linecap: round; stroke-width: 4" d="M4 12a8 8 0 018-8"></path>
            </svg>
            <span style="margin-left: 0.5rem; color: #4b5563; font-size: 1rem">Cargando...</span>
        </div>
        <div class="w-full">
        <input
                v-model="nombrePersonaBuscar"
                ref="floatingNombrePersonaRef"
                type="text"
                placeholder="Escribe el NOMBRE/APELLIDOS"
                @input="debouncedFiltrarPersonas"
                class="block w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-color1 focus:border-color1 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:placeholder-gray-400"/>
        </div>
        <div class="w-full mt-2 overflow-x-auto">
            <table class="min-w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <template v-if="personasFiltradas && personasFiltradas.length > 0">
                        <tr>
                            <th colspan="2" class="px-0 py-1 text-left">RESULTADOS DE LA BÚSQUEDA</th>
                        </tr>
                    </template>
                </thead>
                <tbody>
                    <tr
                        v-if="personasFiltradas && personasFiltradas.length > 0"
                        v-for="persona in personasFiltradas"
                        :key="persona.id"
                        class="hover:text-color1-800 hover:font-bold hover:bg-color3-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-300 dark:border-gray-600"
                        @click="seleccionaPersona(persona)">
                        <td class="px-4 py-2 w-1/2">{{ persona.nombre + ' ' + persona.apellidos }}</td>
                    </tr>
                    <tr v-else>
                        <td colspan="2" class="px-4 py-6 text-sm text-gray-400 dark:text-gray-300">
                            <div v-if="personasFiltradas && nombrePersonaBuscar.length >= 3" class="flex items-center space-x-4 justify-center">
                                <span class="flex-shrink-0 text-color4-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                                        />
                                    </svg>
                                </span>
                                <span class="text-left text-color4-600 bg-color4-50 px-4 py-2 rounded-r-lg border-l-4 border-color4-600">
                                    No hay RESULTADOS para mostrar
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </el-dialog>

    <el-dialog
        v-model="modalBuscarColoniaVisible"
        :title="nombreColoniaBuscar.length >= 5 && coloniasFiltradas && coloniasFiltradas.length === 0 ? 'Agregar colonia' : 'Buscar colonia'"
        top="30vh"
        :width="dialogWidthBuscar">
        <div
            v-if="isLoadingModal2"
            style="position: fixed; top: 0; left: 0; display: flex; align-items: center; justify-content: center; z-index: 50; background-color: rgba(255, 255, 255, 0.7); width: 100vw; height: 100vh">
            <svg class="animate-spin" style="width: 3rem; height: 3rem; color: #4b5563" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4" cx="12" cy="12" r="10"></circle>
                <path style="opacity: 1; stroke: currentColor; stroke-linecap: round; stroke-width: 4" d="M4 12a8 8 0 018-8"></path>
            </svg>
            <span style="margin-left: 0.5rem; color: #4b5563; font-size: 1rem">Cargando...</span>
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
                        <tr>
                            <th colspan="2" class="px-0 py-1 text-left">RESULTADOS DE LA BÚSQUEDA</th>
                        </tr>
                    </template>
                </thead>
                <tbody>
                    <tr
                        v-if="coloniasFiltradas && coloniasFiltradas.length > 0"
                        v-for="colonia in coloniasFiltradas"
                        :key="colonia.id"
                        class="hover:text-color1-800 hover:font-bold hover:bg-color3-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-300 dark:border-gray-600"
                        @click="seleccionaColonia(colonia.id, colonia.nombre)"
                    >
                        <td class="px-4 py-2 w-1/2">{{ colonia.nombre }}</td>
                    </tr>
                    <tr v-else>
                        <td v-if="coloniasFiltradas" colspan="2" class="px-4 py-6 text-sm text-gray-400 dark:text-gray-300">
                            <div class="flex items-center space-x-4">
                                <span class="flex-shrink-0 text-color4-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                                        />
                                    </svg>
                                </span>
                                <span class="text-left text-color4-600 bg-color4-50 px-4 py-2 rounded-r-lg border-l-4 border-color4-600">
                                    {{
                                        coloniasFiltradas === null
                                            ? ''
                                            : nombreColoniaBuscar.length >= 5
                                              ? 'No se encontró ninguna colonia con ese nombre. Si quieres agregar ' +
                                                nombreColoniaBuscar.toUpperCase().trim() +
                                                ' al CATÁLOGO DE COLONIAS del sistema debes seleccionar el botón de +'
                                              : 'No hay RESULTADOS para mostrar. Si quieres agregar una COLONIA al sistema debes escribir su NOMBRE COMPLETO y seleccionar el botón de + que eventualmente aparecerá.'
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
        :title="nombreLocalidadBuscar.length >= 5 && localidadesFiltradas && localidadesFiltradas.length === 0 ? 'Agregar localidad' : 'Buscar localidad'"
        top="30vh"
        :width="dialogWidthBuscar"
    >
        <div
            v-if="isLoadingModal2"
            style="position: fixed; top: 0; left: 0; display: flex; align-items: center; justify-content: center; z-index: 50; background-color: rgba(255, 255, 255, 0.7); width: 100vw; height: 100vh"
        >
            <svg class="animate-spin" style="width: 3rem; height: 3rem; color: #4b5563" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25; stroke: currentColor; stroke-width: 4" cx="12" cy="12" r="10"></circle>
                <path style="opacity: 1; stroke: currentColor; stroke-linecap: round; stroke-width: 4" d="M4 12a8 8 0 018-8"></path>
            </svg>
            <span style="margin-left: 0.5rem; color: #4b5563; font-size: 1rem">Cargando...</span>
        </div>
        <div class="relative w-full">
            <input
                v-model="nombreLocalidadBuscar"
                ref="floatingNombreLocalidadRef"
                type="text"
                placeholder="Escribe el NOMBRE de la LOCALIDAD"
                @input="debouncedFiltrarLocalidades"
                class="block w-full p-2 mb-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-color1 focus:border-color1 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:placeholder-gray-400"
            />

            <button
                v-if="nombreLocalidadBuscar.length >= 5 && localidadesFiltradas && localidadesFiltradas.length === 0"
                type="button"
                @click="agregarLocalidad"
                class="absolute inset-y-0 right-0 px-4 text-white bg-color1 rounded-r-lg hover:bg-color1-600 focus:ring-2 focus:ring-color1"
            >
                +
            </button>
        </div>
        <div>
            <div v-if="nombreLocalidadBuscar.length >= 5 && localidadesFiltradas && localidadesFiltradas.length === 0" class="relative w-full">
                <!-- Contenedor padre que recibirá las clases de focus -->
                <div class="flex focus-within:ring-1 focus-within:ring-color1 focus-within:border-color1 rounded-lg border border-gray-300 dark:border-gray-600">
                    <div class="flex items-center justify-center bg-color1-700 text-white text-sm px-3 rounded-l-md border-r border-gray-300 dark:border-gray-600">Clave</div>

                    <input
                        v-model="claveLocalidadBuscar"
                        type="text"
                        class="flex-1 p-2 text-sm text-gray-900 bg-white border-none rounded-r-lg shadow-sm focus:outline-none focus:ring-0 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                        style="border-left: none"
                    />
                </div>
            </div>
        </div>
        <div class="w-full mt-2 overflow-x-auto">
            <table class="min-w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <template v-if="localidadesFiltradas && localidadesFiltradas.length > 0">
                        <tr>
                            <th colspan="2" class="px-0 py-1 text-left">RESULTADOS DE LA BÚSQUEDA</th>
                        </tr>
                    </template>
                </thead>
                <tbody>
                    <tr
                        v-if="localidadesFiltradas && localidadesFiltradas.length > 0"
                        v-for="localidad in localidadesFiltradas"
                        :key="localidad.id"
                        class="hover:text-color1-800 hover:font-bold hover:bg-color3-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-300 dark:border-gray-600"
                        @click="seleccionaLocalidad(localidad.id, localidad.nombre)"
                    >
                        <td class="px-4 py-2 w-1/5">
                            {{ String(localidad.id).padStart(3, '0') }}
                        </td>
                        <td class="px-4 py-2 w-1/2">{{ localidad.nombre }}</td>
                    </tr>
                    <tr v-else>
                        <td v-if="localidadesFiltradas" colspan="2" class="px-4 py-6 text-sm text-gray-400 dark:text-gray-300">
                            <div class="flex items-center space-x-4">
                                <span class="flex-shrink-0 text-color4-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                                        />
                                    </svg>
                                </span>
                                <span class="text-justify text-color4-600 bg-color4-50 px-4 py-2 rounded-r-lg border-l-4 border-color4-600">
                                    {{
                                        localidadesFiltradas === null
                                            ? ''
                                            : nombreLocalidadBuscar.length >= 5
                                              ? 'No se encontró ninguna localidad con ese nombre. Si quieres agregar ' +
                                                nombreLocalidadBuscar.toUpperCase().trim() +
                                                ' al CATÁLOGO DE LOCALIDADES del sistema debes asignarle una CLAVE y seleccionar el botón de +'
                                              : 'No hay RESULTADOS para mostrar. Si quieres agregar una LOCALIDAD al sistema debes escribir su CLAVE y NOMBRE COMPLETO y seleccionar el botón de + que eventualmente aparecerá.'
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
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%]">
            <h1 class="text-2xl font-bold">Solicitudes </h1>
            <br />
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-visible">
                <div class="relative flex flex-wrap items-center justify-between gap-2 md:gap-3 p-2">
                    <!-- Picker de fechas -->
                    <div class="flex items-center w-full md:w-auto" ref="pickerWrapper">
                        <el-date-picker
                            v-model="rangoFechasQuery"
                            type="daterange"
                            unlink-panels
                            range-separator="-"
                            start-placeholder="Fecha Inicial"
                            end-placeholder="Fecha Final"
                            :shortcuts="shortcuts"
                            class="custom-date-picker w-full md:w-[260px]"
                            value-format="YYYY-MM-DD"
                            format="DD/MM/YYYY"
                            @change="onDateChange"/>
                    </div>

                    <!-- Input de búsqueda -->
                    <div class="flex items-center w-full md:w-[25%]">
                        <form class="flex items-center w-full">
                            <div class="relative w-full group">
                                <!-- Añade 'group' aquí -->
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="simple-search"
                                    class="custom-input bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                    placeholder="Propietario / Solicitante"
                                    v-model="nombreQuery"
                                    @input="fetchSolicitudes(false)"/>
                                <button
                                    v-if="nombreQuery"
                                    @click=";((nombreQuery = ''), fetchSolicitudes(false))"
                                    type="button"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 bg-white dark:bg-gray-700 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <svg class="mr-0 ml-4 w-4 h-4 text-current hover:text-color1-600 transition-colors" viewBox="0 -960 960 960" fill="currentColor">
                                        <path
                                            d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Select múltiple de tipos -->
                    <div class="flex items-center w-full md:w-[25%]">
                        <el-select
                            v-model="tiposTramitesQuery"
                            placeholder="Tipos de Trámite"
                            class="custom-select w-full"
                            multiple
                            collapse-tags
                            collapse-tags-tooltip
                            :max-collapse-tags="1"
                            @change="fetchSolicitudes(false)">
                            <!-- SVG icon in the prefix slot -->
                            <template #prefix>
                               <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 mr-1" 
                                viewBox="0 -960 960 960" fill="currentColor">
                                    <path d="M160-480v240-480 240Zm400 360q17 0 28.5-11.5T600-160q0-17-11.5-28.5T560-200q-17 0-28.5 11.5T520-160q0 17 11.5 28.5T560-120Zm240-400q17 0 28.5-11.5T840-560q0-17-11.5-28.5T800-600q-17 0-28.5 11.5T760-560q0 17 11.5 28.5T800-520Zm-560 0h200v-80H240v80Zm0 160h200v-80H240v80Zm-80 200q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720H160v480h200v80H160ZM560-40q-50 0-85-35t-35-85q0-39 22.5-70t57.5-43v-127h240v-47q-35-12-57.5-43T680-560q0-50 35-85t85-35q50 0 85 35t35 85q0 39-22.5 70T840-447v127H600v47q35 12 57.5 43t22.5 70q0 50-35 85t-85 35Z"/>
                                </svg>
                            </template>

                            <el-option class="custom-option" v-for="item in tiposTramites" :key="item.id" :label="item.nombre" :value="item.id">
                                {{ item.nombre }}
                            </el-option>
                        </el-select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-end gap-2 w-full md:w-auto">
                        <!-- Botón Filtros -->
                        <div class="flex items-center w-full md:w-auto">
                            <button
                                :class="{
                                    'disabled-button': solicitudes.length == 0,
                                    'has-selections': parseInt(selectedCountText.replace(/[()]/g, '')) > 0
                                }"
                                :disabled="solicitudes.length == 0"
                                id="filterDropdownButton"
                                data-dropdown-toggle="filterDropdown"
                                class="filter-button group w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-600 focus:outline-none bg-white rounded-lg border border-gray-300 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-2 focus:ring-color1-200 dark:focus:ring-color1-700 dark:bg-color1-800 dark:text-color1-400 dark:border-color1-600 dark:hover:text-white dark:hover:bg-color1-700"
                                :style="solicitudes.length == 0 ? 'background-color: #f3f4f6; border-color: #d1d5db; color: #9ca3af; cursor: not-allowed;' : ''"
                                type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                        clip-rule="evenodd"/>
                                </svg>
                                Filtros {{ selectedCountText }}

                                <svg
                                    v-if="parseInt(selectedCountText.replace(/[()]/g, '')) > 0"
                                    class="reset-icon mr-0 ml-2 w-4 h-4 text-current opacity-0 group-hover:opacity-100 group-focus:opacity-100 transition-opacity hover:text-color1-600"
                                    viewBox="0 -960 960 960"
                                    fill="currentColor"
                                    @click.prevent="resetFiltros">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>

                                <svg class="mr-1 ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        clip-rule="evenodd"
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            </button>

                            <!-- Dropdown Filtros -->
                            <div
                                id="filterDropdown"
                                class="z-10 hidden w-full md:w-[22em] p-3 bg-white rounded-lg shadow dark:bg-gray-700"
                                style="position: absolute; top: 100%; left: 0; z-index: 50; margin-top: -8px">
                                <div class="flex flex-col w-full">
                                    <div class="w-full">
                                        <div
                                            class="flex items-center justify-center w-full"
                                            :class="{
                                                'bg-color1-600': filtroChkSolicitudes == 1
                                            }">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true"
                                                class="h-4 w-4 ml-2 mr-2 text-color1"
                                                :class="{
                                                    'text-white': filtroChkSolicitudes == 1
                                                }"
                                                viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                    clip-rule="evenodd"/>
                                            </svg>
                                            <h6
                                                class="mb-1 mt-1 mr-2 text-sm font-medium text-color1 dark:text-white"
                                                :class="{
                                                    'text-white': filtroChkSolicitudes == 1
                                                }">
                                                <b>Trámite</b>
                                            </h6>
                                        </div>
                                        <hr class="mb-2" />
                                        <ul v-if="tramites && tramites.length > 0" class="text-sm max-h-32 overflow-y-auto pt-2 pl-3 mb-3 w-full" aria-labelledby="filterDropdownButton">
                                            <li v-for="tramite in tramites" :key="tramite.id" class="flex items-center mb-0 w-full">
                                                <input
                                                    :id="`tramite-${tramite.id}`"
                                                    type="checkbox"
                                                    class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-300 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                                    v-model="tramitesQuery"
                                                    :value="tramite.id"
                                                    :disabled="tramite.count === 0"
                                                    :class="{
                                                        'cursor-not-allowed opacity-50 text-gray-400': tramite.count === 0,
                                                        'cursor-pointer': tramite.count > 0,
                                                        'hover:border-color1-600 hover:border-2 cursor-pointer': tramite.count > 0
                                                    }"/>
                                                <label
                                                    :for="`tramite-${tramite.id}`"
                                                    class="mb-1 ml-2 text-xs uppercase text-gray-600 dark:text-gray-100"
                                                    :class="{
                                                        'cursor-not-allowed opacity-50 text-gray-400': tramite.count === 0,
                                                        'cursor-pointer': tramite.count > 0,
                                                        'hover:text-color1-600 cursor-pointer': tramite.count > 0
                                                    }">
                                                    {{ tramite.nombre }} ({{ tramite.count }})
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <hr class="mb-2" />
                                    <div class="w-full">
                                        <div
                                            class="flex items-center justify-center w-full"
                                            :class="{
                                                'bg-color1-600': filtroChkSolicitudes == 2
                                            }">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true"
                                                class="h-4 w-4 mr-2 ml-2 text-color1"
                                                :class="{
                                                    'text-white': filtroChkSolicitudes == 2
                                                }"
                                                viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                    clip-rule="evenodd"/>
                                            </svg>
                                            <h6
                                                class="mb-1 mt-1 mr-2 text-sm font-medium text-color1 dark:text-white"
                                                :class="{
                                                    'text-white': filtroChkSolicitudes == 2
                                                }">
                                                <b>Estatus</b>
                                            </h6>
                                        </div>
                                        <hr class="mb-2" />
                                        <ul v-if="estatusSolicitud && estatusSolicitud.length > 0" class="text-sm max-h-32 overflow-y-auto pt-2 pl-3 w-full" aria-labelledby="filterDropdownButton2">
                                            <li v-for="estatus in estatusSolicitud.filter((e) => e.id !== 1)" :key="estatus.id" class="flex items-center mb-0 w-full">
                                                <input
                                                    :id="`estatus-${estatus.id}`"
                                                    type="checkbox"
                                                    class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-200 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                                    v-model="estatusQuery"
                                                    :value="estatus.id"
                                                    :disabled="estatus.count === 0"
                                                    :class="{
                                                        'cursor-not-allowed opacity-50 text-gray-400': estatus.count === 0,
                                                        'cursor-pointer': estatus.count > 0,
                                                        'hover:border-color1-600 hover:border-2 cursor-pointer': estatus.count > 0
                                                    }"/>
                                                <label
                                                    :for="`estatus-${estatus.id}`"
                                                    class="mb-1 ml-2 text-xs uppercase text-gray-600 dark:text-gray-100"
                                                    :class="{
                                                        'cursor-not-allowed opacity-50 text-gray-400': estatus.count === 0,
                                                        'cursor-pointer': estatus.count > 0,
                                                        'hover:text-color1-600 cursor-pointer': estatus.count > 0
                                                    }">
                                                    {{ estatus.nombre }} ({{ estatus.count }})
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Botón Nueva -->
                        <button
                            ref="nuevaBtn"
                            @click="abreModalSolicitud"
                            type="button"
                            class="bg-color1-800 hover:bg-color1-700 ml-5 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-lg text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-800">
                            + Nueva
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <!-- FOLIO -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base">Folio</span>
                                        <svg v-if="sortColumn === 'id'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                                <!-- FECHA INGRESO -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('fecha_ingreso')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base">Fecha</span>
                                        <svg v-if="sortColumn === 'fecha_ingreso'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                                <!-- Solicitante -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" style="width: 25%" @click="sortTable('id_solicitante')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base hidden md:inline">Propietario / Solicitante </span>
                                        <span class="mr-1 normal-case text-base inline md:hidden">Propietario/Solicitante</span>
                                        <svg v-if="sortColumn === 'id_solicitante'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                                <!-- Clave Catastral -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" style="width: 15%" @click="sortTable('clave_catastral')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base">Clave Catastral</span>
                                        <svg v-if="sortColumn === 'clave_catastral'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                                <!-- Trámite -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_tramite')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base">Trámite(s)</span>
                                        <svg v-if="sortColumn === 'id_tramite'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                                <!-- Estatus -->
                                <th scope="col" class="px-4 py-3 cursor-pointer" @click="sortTable('id_estatus')">
                                    <div class="flex items-center">
                                        <span class="mr-1 normal-case text-base">Estatus</span>
                                        <svg v-if="sortColumn === 'id_estatus'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-color1">
                                            <path
                                                v-if="sortDirection === 'asc'"
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm14.47 3.97a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 1 1-1.06 1.06L18 10.81V21a.75.75 0 0 1-1.5 0V10.81l-2.47 2.47a.75.75 0 1 1-1.06-1.06l3.75-3.75ZM2.25 9A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm0 4.5a.75.75 0 0 1 .75-.75h5.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M2.25 4.5A.75.75 0 0 1 3 3.75h14.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm0 4.5A.75.75 0 0 1 3 8.25h9.75a.75.75 0 0 1 0 1.5H3A.75.75 0 0 1 2.25 9Zm15-.75A.75.75 0 0 1 18 9v10.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 1 1 1.06-1.06l2.47 2.47V9a.75.75 0 0 1 .75-.75Zm-15 5.25a.75.75 0 0 1 .75-.75h9.75a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Z"
                                                clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="solicitud in solicitudes" :key="solicitud.id" class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                                <th scope="row" class="px-4 py-2 font-normal">
                                    {{ (solicitud.id % 10000).toString().padStart(4, '0') }}
                                </th>
                                <td class="px-4 py-2">
                                    {{ formatDate(solicitud.fecha_ingreso) }}´
                                </td>
                                <td v-if="solicitud.id_contacto == solicitud.propiedad?.id_contacto" class="px-4 py-2 w-80">
                                    {{ solicitud.propiedad.contacto.persona.nombre + ' ' + solicitud.propiedad.contacto.persona.apellidos }}
                                </td>
                                <td v-else class="px-4 py-2">
                                <template v-if="solicitud.propiedad">
                                        {{
                                            solicitud.propiedad.contacto.persona.nombre +
                                            ' ' +
                                            solicitud.propiedad.contacto.persona.apellidos +
                                            ' / ' +
                                            (solicitud.razon_social
                                                ? solicitud.razon_social.nombre + ' «' + (solicitud.contacto.persona.nombre || '') + ' ' + (solicitud.contacto.persona.apellidos || '') + '»'
                                                : (solicitud.contacto.persona.nombre || '') + ' ' + (solicitud.contacto.persona.apellidos || ''))
                                        }}
                                    </template>
                                    <template v-else>
                                        <!-- If no property, only show solicitant's name -->
                                        {{
                                            solicitud.contacto.persona.nombre +
                                            ' ' +
                                            solicitud.contacto.persona.apellidos
                                        }}
                                    </template>
                                </td>
                                <td class="px-4 py-2">
                                    <table class="table-fixed border-collapse">
                                        <tbody>
                                        <tr>
                                            <template v-if="solicitud.propiedad" v-for="(char, index) in solicitud.propiedad?.clave_catastral?.toString().split('')" :key="'char-' + index">
                                            <td class="text-center w-[7px] p-0 m-0 leading-none">
                                                {{ char }}
                                            </td>
                                            <td v-if="(index + 1) % 3 === 0 && index !== solicitud.propiedad.clave_catastral.length - 1" class="w-[4px] p-0 m-0"></td>
                                            </template>
                                            <template v-else>
                                            <td class="text-center">N/A</td>
                                            </template>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td class="px-4 py-2">
                                    <ul v-if="solicitud.tramites && solicitud.tramites.length > 0">
                                        <li v-for="sTramites in solicitud.tramites" :key="sTramites.id" class="flex items-start gap-2">
                                            <!-- <template v-if="solicitud.tramites.length > 1"> -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-2 h-2 text-color1-300 mt-1" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M2 2v13.5l6-3.5 6 3.5V2z" />
                                            </svg>
                                            <span>{{ sTramites.tramite.nombre }}</span>
                                        </li>
                                    </ul>
                                    <div v-else class="text-gray-500">SIN TRÁMITES</div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="flex items-center gap-1">
                                        <span :style="{ color: solicitud.estatus.color }">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.25 2.25a3 3 0 0 0-3 3v4.318a3 3 0 0 0 .879 2.121l9.58 9.581c.92.92 2.39 1.186 3.548.428a18.849 18.849 0 0 0 5.441-5.44c.758-1.16.492-2.629-.428-3.548l-9.58-9.581a3 3 0 0 0-2.122-.879H5.25ZM6.375 7.5a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </span>
                                        {{ solicitud.estatus.nombre }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 flex items-center justify-end text-left">
                                    <button
                                        @click="toggleDropdown(solicitud.id, $event)"
                                        :id="`dropdown-button-${solicitud.id}`"
                                        class="inline-flex items-center p-0.5 text-sm font-medium text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                                        type="button">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                        </svg>
                                    </button>
                                    <div
                                        v-show="dropdownVisible === solicitud.id"
                                        :id="'dropdown-' + solicitud.id"
                                        class="border border-gray-100 absolute right-0 mb-3 z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow-2xl dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="text-left py-1 text-sm text-gray-700 dark:text-gray-200" style="text-align: left !important">
                                            <li class="text-left hover:bg-gray-100 hover:font-bold dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-white">
                                                <button
                                                    @click="abreModalEditarSolicitud(solicitud)"
                                                    class="w-full py-2 px-4 text-left hover:bg-transparent dark:hover:bg-transparent hover:text-inherit dark:hover:text-inherit">
                                                    <span v-if="solicitud.id_estatus >= 6">Ver</span>
                                                    <span v-else>Editar</span>
                                                </button>
                                            </li>
                                            <li class="hover:bg-gray-100 hover:font-bold dark:hover:bg-gray-600 hover:text-gray-700 dark:hover:text-white">
                                                <button
                                                    @click="imprimirSolicitud(solicitud)"
                                                    class="w-full py-2 px-4 text-left hover:bg-transparent dark:hover:bg-transparent hover:text-inherit dark:hover:text-inherit">
                                                    <span>Imprimir</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- <Pagination :data="pagination" /> -->
                <Pagination :data="pagination" @page-changed="handlePageChange" />
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
            <div v-if="isSearching" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-60 transition-opacity">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-color1-500 animate-bounce" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="ml-2 text-gray-800">Buscando...</span>
                </div>
            </div>
        </div>
    </section>
</template>

