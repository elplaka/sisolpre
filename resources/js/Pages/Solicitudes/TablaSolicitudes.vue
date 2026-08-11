<script setup>
    import { router } from '@inertiajs/vue3'
    import { ref, computed, watch, onMounted, onUnmounted, onBeforeUnmount, markRaw, nextTick } from 'vue'
    import Pagination from '@/Components/Pagination.vue'
    import Swal from 'sweetalert2'
    import CalendarUp from '@/Components/UI/Icons/CalendarUp.vue';
    import CalendarDown from '@/Components/UI/Icons/CalendarDown.vue';
    

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
        localidadesQuery: Array,
        numQuery: String,
        folioQuery: String,
        nombreQuery: String,
        fechaIngresoInicioQuery: String,
        fechaIngresoFinQuery: String,
        fechaAceptacionInicioQuery: String,
        fechaAceptacionFinQuery: String,
        claveCatastralQuery: String,
        idRangoFechasIngresoQuery: Number,
        idRangoFechasAceptacionQuery: Number,
        tiposTramitesQuery: Array,
        tramitesQuery: Array,
        filtroChkSolicitudes: [Number, String],
        sortDirection: String,
        sortColumn: String,
        paraNuevaSolicitud: {
            type: [Boolean, String], // Acepta Boolean O String
            validator: (value) => {
                // El validador se encarga de convertir y validar.
                if (typeof value === 'string') {
                    return value === '1' || value === 'true' || value === '0' || value === 'false';
                }
                return typeof value === 'boolean';
            }
        },
        periodoActual: Object
    })

    const solicitudSelected = ref(null)

    // Paleta de colores neutros (Guinda a Gris Oscuro)
    const colorPalette = ref([
        'DarkRed', // Guinda Profundo
        'blue', // Rojo Cereza (más claro)
        'OliveDrab', // Borgoña Clásico
        'OrangeRed', // Rosa Viejo/Malva (contraste suave)
        ]);

    const getItemStyle = (index) => {
        // 1. Obtén el array de colores (o un array vacío si es null/undefined)
        const colors = colorPalette.value || [];

        // 2. Si el array de colores no tiene elementos, devuelve el color de respaldo
        if (colors.length === 0) {
            return {
                'border-left': '4px solid #CCCCCC', // Gris de respaldo
                'padding-left': '12px',
                'margin-bottom': '4px !important'
            };
        }

        // 3. Calcula el color rotativo y devuelve el estilo completo
        const color = colors[index % colors.length];

        return {
            'border-left': `4px solid ${color}`,
            'padding-left': '12px',
            'padding-bottom': '12px',
            'margin-bottom': '4px !important'
        };
    };

    const getBorderStyle = (tramiteId) => {
        const colors = colorPalette.value || [];

        // Si la paleta está vacía o el ID no es válido, usa un color de respaldo
        if (colors.length === 0 || typeof tramiteId !== 'number') {
            return {
                'border-left': '4px solid #CCCCCC', // Gris de respaldo
                'padding-left': '8px', // Un padding-left más compacto para un span
                'border-top-left-radius': '0',
                'border-bottom-left-radius': '0',
            };
        }

        // Calcula el color usando el ID del trámite
        const color = colors[tramiteId % colors.length];

        return {
            'border-left': `4px solid ${color}`,
            'padding-left': '8px', // Dale un padding-left para separar el texto del borde
            'border-top-left-radius': '0',
            'border-bottom-left-radius': '0',
        };
    };

    const ID_CONSTANCIA_UBICACION = 18;
    const ID_CONSTANCIA_NUMERO_OFICIAL = 4;
    
    const intervalo = ref(null)
    const intervaloMs = 30000 // 👈 ajusta aquí la frecuencia del polling

    //Parámetros para filtrar
    const numQuery = ref('')
    const folioQuery = ref('')
    const nombreQuery = ref('')
    const fechaIngresoInicioQuery = ref('')
    const fechaIngresoFinQuery = ref('')
    const fechaAceptacionInicioQuery = ref('')
    const fechaAceptacionFinQuery = ref('')
    const claveCatastralQuery = ref('')
    const rangoFechasIngresoQuery = ref([])
    const rangoFechasAceptacionQuery = ref([])
    const tiposTramitesQuery = ref([])
    const tramitesQuery = ref([])
    const localidadesQueryFiltradas = ref([])
    const localidadesSelectQuery = ref([])
    const estatusQuery = ref([])
    const filtroChkSolicitudes = ref(0)
    const rangoFechasIngresoManual = ref(false)
    const rangoFechasAceptacionManual = ref(false)
    const rangoFechasShortcut = ref(false)
    const rangoFechasAceptacionShortcut = ref(false)
    const idRangoFechasIngresoQuery = ref(99)
    const idRangoFechasAceptacionQuery = ref(99)
    const pickerWrapper = ref(null)
    const isDropZoneCroquisFocused = ref(false); // Nueva variable para el estado del foco
    const periodoActual = ref(props.periodoActual || {})

    const resetFiltroDocObligatorias = () => {
        tramitesQuery.value = []
        estatusQuery.value = []
    }

    const resetFiltroDocObligatoriasLocalidades = () => {
        localidadesSelectQuery.value = []
    }

    const selectedCountText = computed(() => {
        const total = tramitesQuery.value.length + estatusQuery.value.length
        return total > 0 ? `(${total})` : ''
    })

    const selectedCountTextLocalidades = computed(() => {
        const total = localidadesQueryFiltradas.value.length
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
    const fechaAceptacionSolicitud = ref(null)

    const isLoading = ref(false)
    const isLoadingModal = ref(false)
    const isLoadingModal2 = ref(false)
    const isSavingModal = ref(false)
    const isSearching = ref(false)
    const isProcessingModal = ref(false)
    const paraNuevaSolicitud = ref(props.paraNuevaSolicitud || false)
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
    const floatingDomicilioNotificacionSolicitanteRef = ref(null)
    const floatingTelefonoSolicitanteRef = ref(null)

    const floatingCURPPropietarioRef = ref(null)
    const floatingNomPropietarioRef = ref(null)
    const floatingApePropietarioRef = ref(null)
    const floatingEmailPropietarioRef = ref(null)
    const floatingDomicilioNotificacionPropietarioRef = ref(null)
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
        fechaAceptacionSolicitud.value = solicitud.fecha_aceptacion
        fechaAceptacionSolicitud.value = limpiarFecha(fechaAceptacionSolicitud.value);
        idDestinoObra.value = solicitud.id_destino_obra
        nombreDestinoObra.value = solicitud.destino_obra?.nombre
        idSector.value = solicitud.id_sector
        nombreSector.value = solicitud.sector_tramite?.nombre
        folioSolicitud.value = solicitud.folio

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

    // Al inicializar el componente:
    const limpiarFecha = (fecha) => {
        if (!fecha) return null;
        const index = fecha.indexOf(' ');
        return index !== -1 ? fecha.substring(0, index) : fecha;
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
            idDomicilioNotificacionPropietario.value = propietario.domicilio_notificacion?.id
            domicilioNotificacionPropietario.value = propietario.domicilio_notificacion?.direccion
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
            idDomicilioNotificacionPropietario.value = ''
            domicilioNotificacionPropietario.value = ''       
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
        idDomicilioNotificacionSolicitante.value = solicitante.domicilio_notificacion?.id
        domicilioNotificacionSolicitante.value = solicitante.domicilio_notificacion?.direccion
    }

    const cargaDatosReferencia = (ref) => {
        if (ref) 
        {
            referencia.value = ref.contenido
            tipoPropiedadReferencia.value = ref.id_tipo_propiedad
            nombreTipoPropiedadReferencia.value = ref.tipo_propiedad?.nombre
            idLocalidadReferencia.value = ref.id_localidad
            nombreLocalidadReferencia.value = ref.localidad?.nombre
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
        domicilioNotificacionPropietarioEditable.value = valor
    }

    const editablesPersonaSolicitante = (valor) => {
        nomSolicitanteEditable.value = valor
        apeSolicitanteEditable.value = valor
    }

    const editablesSolicitante = (valor) => {
        telefonoSolicitanteEditable.value = valor
        emailSolicitanteEditable.value = valor
        domicilioNotificacionSolicitanteEditable.value = valor
    }

    const editablesReferencia = (valor) => {
        referenciaEditable.value = valor
        tipoPropiedadReferenciaEditable.value = valor
        idLocalidadReferenciaEditable.value = valor
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
        domicilioNotificacionPropietarioBloqueado.value = valor        
    }

    const bloqueadosSolicitante = (valor) => {
        telefonoSolicitanteBloqueado.value = valor
        emailSolicitanteBloqueado.value = valor
        razonSocialSolicitanteBloqueado.value = valor
        domicilioNotificacionSolicitanteBloqueado.value = valor
    }

    const bloqueadosReferencia = (valor) => {
        referenciaBloqueado.value = valor
        tipoPropiedadReferenciaBloqueado.value = valor
        idLocalidadReferenciaBloqueado.value = valor  
    }

    const abrirRutaDinamica = (idTramite, idSolicitud) => {
        let nombreRuta = '';

        switch (idTramite) {
            case 4:
                nombreRuta = 'constancias-numero-oficial.get-tramite';
                break;
            
            // Aquí podrás agregar futuros casos fácilmente
            case 5:
                nombreRuta = 'otro-tramite.nombre-de-ruta';
                break;

            default:
                console.warn(`No hay una ruta configurada para el trámite con ID: ${idTramite}`);
                return; // Salimos si no hay ruta definida
        }

        // Ejecutamos la navegación con la ruta seleccionada
        router.post(route(nombreRuta), { 
            id_solicitud: idSolicitud,
        });
    };

    // Configuración centralizada
    const CONFIG_TRAMITES = {
        ACTIVOS: [4], // Agrega aquí los nuevos IDs conforme los habilites
        DESCRIPCION_DESHABILITADO: 'Trámite en desarrollo'
    };

    const abrirTramite = async (solicitud, esAceptacion = false) => {
        let idTramiteSeleccionado = null;
        const folioFormateado = solicitud.folio.toString().slice(-5).padStart(5, '0');
        const idUnico = solicitud.tramites[0].tramite ? solicitud.tramites[0].tramite.id : solicitud.tramites[0].id_tramite;
        const esActivo = CONFIG_TRAMITES.ACTIVOS.includes(idUnico);

        function obtenerEstadoTramites(tramitesDisponibles, tramitesAsignados = [], configActivos = []) {
            return tramitesDisponibles.map(t => {
                const id = t.tramite.id;
                const nombre = t.tramite.nombre;
                const asignado = tramitesAsignados.find(ta => ta.id_tramite === id);
                
                // Verificamos primero si está permitido por configuración
                const esActivo = configActivos.includes(id);
                
                if (asignado) {
                    // Si está asignado, pero NO está en los activos permitidos por config, lo bloqueamos
                    if (!esActivo) {
                        return {
                            id,
                            nombre,
                            accionTexto: 'No disponible',
                            estadoBadge: 'Bloqueado',
                            claseCss: 'btn-bloqueado',
                            interactivo: false
                        };
                    }
                    
                    // Si está asignado y sí es activo, evaluamos si está concluido o en proceso
                    const esConcluido = asignado.id_estatus === 99 || ['concluido', 'finalizado'].includes(asignado.estado);
                    return {
                        id,
                        nombre,
                        accionTexto: esConcluido ? 'Ver detalles' : 'Continuar',
                        estadoBadge: esConcluido ? 'Concluido' : 'En proceso',
                        claseCss: esConcluido ? 'btn-concluido' : 'btn-en-proceso',
                        interactivo: true
                    };
                }
                
                // Si no está asignado, evaluamos si se puede iniciar o queda no disponible
                return {
                    id,
                    nombre,
                    accionTexto: esActivo ? 'Iniciar' : 'No disponible',
                    estadoBadge: esActivo ? 'Por Iniciar' : 'Bloqueado',
                    claseCss: esActivo ? 'btn-por-iniciar' : 'btn-bloqueado',
                    interactivo: esActivo
                };
            });
        }

        if (solicitud.tramites.length > 1) {
            const tramites = obtenerEstadoTramites(
                solicitud.tramites, 
                solicitud.tramites_asignados, 
                CONFIG_TRAMITES.ACTIVOS
            );

            await Swal.fire({
                title: '<span class="inline-flex items-center gap-2 text-slate-800 font-bold"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7b003a" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> Selección de Trámite</span>',
                html: `
                    <div class="text-left mt-1">
                        <!-- Header de Folio limpio -->
                        <div class="flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-3 mb-4 shadow-2xs">
                            <span class="text-slate-800 text-sm font-bold uppercase tracking-wider">Folio de Solicitud</span>
                            <div class="flex items-center gap-3">
                                <div class="h-5 w-1" style="background-color: var(--color1, #7b003a);"></div>
                                <span class="text-slate-900 text-base font-black tracking-tight">${folioFormateado}</span>
                            </div>
                        </div>

                        <p class="text-slate-600 text-sm mb-3.5 font-medium">
                            Elige el trámite que deseas iniciar para esta solicitud:
                        </p>

                        <div class="flex flex-col gap-2">
                            ${tramites.map((t, index) => `
                                <div class="flex items-stretch bg-white border rounded-xl overflow-hidden transition-all duration-200 ${
                                    t.interactivo 
                                        ? 'border-slate-300 shadow-sm opacity-100' 
                                        : 'border-slate-200 bg-slate-50/60 opacity-60'
                                }">
                                    
                                    <!-- Bloque Izquierdo: Ícono SVG -->
                                    <div class="flex flex-col items-center justify-center w-12 flex-shrink-0 border-r ${
                                        t.interactivo 
                                            ? 'bg-slate-50 border-slate-200' 
                                            : 'bg-slate-100 border-slate-200'
                                    }">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${t.interactivo ? '#7b003a' : '#94a3b8'}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                    </div>

                                    <!-- Contenido Principal -->
                                    <div class="flex items-center justify-between p-3 w-full gap-2.5">
                                        <div class="flex flex-col text-left overflow-hidden">
                                            <span class="text-sm font-bold truncate ${t.interactivo ? 'text-slate-900' : 'text-slate-400'}">
                                                ${t.nombre}
                                            </span>
                                            <span class="text-xs font-semibold mt-0.5 flex items-center gap-1.5 ${t.interactivo ? 'text-slate-600' : 'text-slate-400'}">
                                                <!-- Pequeño SVG indicador con color1 directo para asegurar su visualización -->
                                                <svg width="6" height="6" viewBox="0 0 24 24" fill="${t.interactivo ? '#7b003a' : '#cbd5e1'}" stroke="none">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                </svg>
                                                ${t.interactivo ? 'Disponible para selección' : 'No disponible actualmente'}
                                            </span>
                                        </div>

                                        <!-- Botón conectado a la variable de Tailwind -->
                                        <button type="button" 
                                                class="${t.claseCss} py-2 px-4 text-sm rounded-full font-bold flex-shrink-0 transition-colors ${
                                                    t.interactivo 
                                                        ? 'text-white hover:bg-color1-600 cursor-pointer' 
                                                        : 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200'
                                                }" 
                                                style="${t.interactivo ? 'background-color: var(--color1, #7b003a);' : ''}"
                                                ${!t.interactivo ? 'disabled' : ''}>
                                            ${t.accionTexto}
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonColor: '#64748b',
                cancelButtonText: 'Cerrar',
                didOpen: () => {
                    const container = Swal.getHtmlContainer();
                    container.querySelectorAll('button').forEach((btn, index) => {
                        if (tramites[index].interactivo) {
                            btn.onclick = () => {
                                idTramiteSeleccionado = tramites[index].id;
                                Swal.close();
                            };
                        }
                    });
                }
            });

            if (!idTramiteSeleccionado) return;
            if (idTramiteSeleccionado) {
                abrirRutaDinamica(idTramiteSeleccionado, solicitud.id);
                return;
            }

        }
        else if (solicitud.tramites.length == 1 && esActivo)
        {
            if (solicitud.tramites_asignados && solicitud.tramites_asignados[0]?.id_estatus == 99)
            {
               const result = await Swal.fire({
                    title: 'Registro de Trámite Concluido',
                    html: `
                        <div style="text-align: left; margin-top: 0.25rem;">         
                            <!-- Ficha de datos estilo tabla/oficio -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.85rem;">
                                <tr>
                                    <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; width: 35%;">Folio Solicitud</td>
                                    <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 700;">${folioFormateado}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Trámite</td>
                                    <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 600;">${solicitud.tramites[0].tramite.nombre ?? 'Constancia de Número Oficial'}</td>
                                </tr>
                            </table>

                            <div style="display: inline-flex; align-items: center; gap: 6px; background-color: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-bottom: 0.85rem; border: 1px solid #d1fae5;">
                                <span style="width: 6px; height: 6px; background-color: #059669; border-radius: 50%;"></span>
                                ENTREGADO A LA PERSONA SOLICITANTE
                            </div>

                            <!-- Nota al pie de solo consulta -->
                            <p style="color: #475569; font-size: 0.9rem; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 0.75rem; margin: 0; text-align: justify">
                                El expediente se encuentra archivado y el proceso ha finalizado formalmente. Se abrirá la vista en <strong>modo de consulta</strong> para la revisión de los detalles del trámite.
                            </p>
                        </div>
                    `,
                    icon: 'success', 
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: 'Ver expediente',
                    cancelButtonText: 'Cerrar'
                });

                if (!result.isConfirmed) {
                    return;
                }

                abrirRutaDinamica(solicitud.tramites_asignados[0].id_tramite, solicitud.id);

                return;
            }

            if (solicitud.tramites_asignados && solicitud.tramites_asignados.length > 0 && (solicitud.tramites_asignados[0]?.documento_generado && solicitud.tramites_asignados[0]?.id_estatus != 99))
            {
                const result = await Swal.fire({
                    title: 'Seguimiento de Trámite',
                    html: `
                        <div style="text-align: left; margin-top: 0.25rem;">           

                            <!-- Ficha de datos estilo tabla/oficio -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.85rem;">
                                <tr>
                                    <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; width: 35%;">Folio de Solicitud</td>
                                    <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 700;">${folioFormateado}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Trámite</td>
                                    <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 600;">${solicitud.tramites[0].tramite.nombre ?? 'Constancia de Número Oficial'}</td>
                                </tr>
                            </table>

                           <div style="display: inline-flex; align-items: center; gap: 6px; background-color: ${solicitud.tramites_asignados[0]?.id_estatus === 2 ? '#f0fdf4' : '#eff6ff'}; color: ${solicitud.tramites_asignados[0]?.id_estatus === 2 ? '#15803d' : '#1d4ed8'}; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-bottom: 0.85rem; border: 1px solid ${solicitud.tramites_asignados[0]?.id_estatus === 2 ? '#bbf7d0' : '#bfdbfe'};">
                                <span style="width: 6px; height: 6px; background-color: ${solicitud.tramites_asignados[0]?.id_estatus === 2 ? '#22c55e' : '#2563eb'}; border-radius: 50%;"></span>
                                ${solicitud.tramites_asignados[0]?.id_estatus === 2 ? 'TRÁMITE APROBADO' : 'TRÁMITE EN PROCESO DE GESTIÓN'}
                            </div>

                            <!-- Nota de seguimiento justificada -->
                            <p style="color: #475569; font-size: 0.9rem; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 0.75rem; margin: 0; text-align: justify;">
                                ${solicitud.tramites_asignados[0]?.id_estatus === 2 
                                    ? 'El presente expediente cuenta con un trámite que ha sido aprobado exitosamente. Al continuar, podrá consultar los detalles finales, registros y documentos correspondientes en el panel de gestión y control.' 
                                    : 'El presente expediente cuenta con un trámite activo que se encuentra actualmente en las fases de integración y revisión técnica. Al continuar, accederá al panel de gestión y control para dar seguimiento a los datos, registros y documentos correspondientes.'}
                            </p>
                        </div>
                    `,
                    icon: 'warning', // Sin icono para mantener la consistencia visual de cédula institucional
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: solicitud.tramites_asignados[0]?.id_estatus === 2 ? 'Ver detalles finales' : 'Continuar trámite',
                    cancelButtonText: 'Cerrar'
                });

                if (!result.isConfirmed) {
                    return;
                }

                abrirRutaDinamica(solicitud.tramites_asignados[0].id_tramite, solicitud.id);

                return;
            }

               const result = await Swal.fire({
                title: '¿Iniciar el trámite?',
                html: `
                    <div style="text-align: left; margin-top: 0.25rem;">
                        ${esAceptacion ? `
                            <div style="background-color: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.75rem; text-align: center;">
                                ✓ Solicitud Aceptada
                            </div>
                        ` : ''}

                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.85rem;">
                            <tr>
                                <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; width: 35%;">Folio de Solicitud</td>
                                <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 700;">${folioFormateado}</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Trámite</td>
                                <td style="padding: 6px 8px; background-color: #ffffff; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 600;">${solicitud.tramites[0]?.tramite?.nombre ?? 'Constancia de Número Oficial'}</td>
                            </tr>
                        </table>

                        <p style="color: #475569; font-size: 0.9rem; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 0.75rem; margin: 0; text-align: justify;">
                            Se procederá a dar seguimiento con la solicitud de acuerdo a la normativa establecida. ¿Deseas dar inicio al trámite correspondiente?
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Sí, dar inicio',
                cancelButtonText: 'Más adelante'
            });

            if (!result.isConfirmed) {
                return;
            }

            abrirRutaDinamica(4, solicitud.id);
        }
    }

    const imprimirSolicitud = async (solicitud) => {
        // isLoading.value = true

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
                        detenerIntervalo();
                    },
                    onSuccess: () => {
                        // Abrir el PDF en nueva pestaña
                        window.open(rutaPDF, '_blank')
                        isLoading.value = false

                        // Volver a cargar la vista actual con los filtros (sin recargar todo)
                        router.post(
                            '/solicitudes',
                            {
                                fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
                                fechaIngresoFinQuery: fechaIngresoFinQuery.value,
                                fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
                                fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
                                numQuery: numQuery.value,
                                folioQuery: folioQuery.value,
                                nombreQuery: nombreQuery.value,
                                claveCatastralQuery: claveCatastralQuery.value,
                                tiposTramitesQuery: tiposTramitesQuery.value,
                                tramitesQuery: tramitesQuery.value,
                                estatusQuery: estatusQuery.value,
                                filtroChkSolicitudes: filtroChkSolicitudes.value,
                                rangoFechasIngresoManual: rangoFechasIngresoManual.value,
                                rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
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

                        iniciarIntervalo()
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

    const cargaDatosDocumentacion = (solicitud) => 
    {
        if (!solicitud || !solicitud.requisitos_docs) {
            requisitosEntregados.value = []; // Inicializar como array vacío
            return; 
        }

        const docs = solicitud.requisitos_docs;
        const idsEntregados = []; // Nuevo array para guardar solo los IDs

        if (docs && docs.length > 0) 
        {
            docs.forEach(doc => {
                idsEntregados.push(doc.id);
            });
        }

        // Actualiza la ref reactiva con el array de IDs entregados
        requisitosEntregados.value = idsEntregados;

        if (!todosObligatoriosCompletados)
        {
            estatusSolicitudSelect.value = props.estatusSolicitud.filter((item) => item.id > 1 && item.id !== 6 && item.id !== 99)
        }
    }

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
        solicitudSelected.value = solicitud
        requisitos.value = data.requisitos

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
        setFiltroDocObligatoria(true)
        cargaDatosDocumentacion(solicitud)

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
            tipoPropiedadReferenciaEditable.value = false
            idLocalidadReferenciaEditable.value = false
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

        fetchSolicitudes(false, true)
    }

    const dialogWidth = computed(() => {
        return window.innerWidth < 1024 ? '95%' : '65%'
    })

    const dialogWidthBuscar = computed(() => {
        return window.innerWidth < 1024 ? '95%' : '35%'
    })

    const inicializaSolicitante = () => {
        nomSolicitante.value = ''
        apeSolicitante.value = ''
        telefonoSolicitante.value = ''
        emailSolicitante.value = ''
        idDomicilioNotificacionSolicitante.value = ''
        domicilioNotificacionSolicitante.value = ''
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
        idDomicilioNotificacionPropietario.value = ''
        domicilioNotificacionPropietario.value = ''
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
        tipoPropiedadReferencia.value = ''
        idLocalidadReferencia.value = ''
        nombreLocalidadReferencia.value = ''
        nombreTipoPropiedadReferencia.value = ''
        imgCroquisAux.value = ''
        filtroObligatorio.value = true;

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
        nombrePersonaBuscar.value = ''

        setTimeout(() => {
            floatingNombrePersonaRef.value?.focus()
        }, 50)
    }

    const solicitudBloqueada = ref(false)
    const folioSolicitud = ref('')
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
    const idDomicilioNotificacionSolicitante = ref('')
    const domicilioNotificacionSolicitante = ref('')
    const razonSocialSolicitante = ref('')
    const curpSolicitanteEditable = ref(true)
    const nomSolicitanteEditable = ref(true)
    const apeSolicitanteEditable = ref(true)
    const telefonoSolicitanteEditable = ref(true)
    const emailSolicitanteEditable = ref(true)   
    const razonSocialSolicitanteEditable = ref(true)
    const domicilioNotificacionSolicitanteEditable = ref(true)

    const curpSolicitanteBloqueado = ref(true)
    const nomSolicitanteBloqueado = ref(true)
    const apeSolicitanteBloqueado = ref(true)
    const telefonoSolicitanteBloqueado = ref(true)
    const emailSolicitanteBloqueado = ref(true)
    const razonSocialSolicitanteBloqueado = ref(true)
    const domicilioNotificacionSolicitanteBloqueado = ref(true)
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
    const idDomicilioNotificacionPropietario = ref('')
    const domicilioNotificacionPropietario = ref('')
    const esSolicitante = ref('1')
    const esSolicitanteEditable = ref(true)
    const curpPropietarioEditable = ref(true)
    const nomPropietarioEditable = ref(true)
    const apePropietarioEditable = ref(true)
    const telefonoPropietarioEditable = ref(true)
    const emailPropietarioEditable = ref(true)
    const domicilioNotificacionPropietarioEditable = ref(true)

    const curpPropietarioBloqueado = ref(true)
    const nomPropietarioBloqueado = ref(true)
    const apePropietarioBloqueado = ref(true)
    const telefonoPropietarioBloqueado = ref(true)
    const emailPropietarioBloqueado = ref(true)
    const domicilioNotificacionPropietarioBloqueado = ref(true)

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
    const idLocalidadReferencia = ref('')
    const idLocalidadReferenciaEditable = ref(true)
    const idLocalidadReferenciaBloqueado = ref(true)
    const nombreLocalidadReferencia = ref('')
    const floatingIdLocalidadReferenciaRef = ref(null)
    const tipoPropiedadReferencia = ref('')
    const tipoPropiedadReferenciaEditable = ref(true)
    const tipoPropiedadReferenciaBloqueado = ref(true)
    const floatingTipoPropiedadReferenciaRef = ref(null)
    const isFocusedTipoPropiedadReferencia = ref(false); // Variable para controlar el enfoque
    const nombreTipoPropiedadReferencia = ref('')
    const isFocusedLocalidadReferencia = ref(false); // Variable para controlar el enfoque

    const handleFocusTipoPropiedadReferencia = (status) => {
        isFocusedTipoPropiedadReferencia.value = status // SELECT - Cambia el estado del foco
    }

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
                    domicilioNotificacionSolicitante.value = persona.value.solicitante?.domicilio_notificacion?.direccion || null

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
                onStart:() => {
                    detenerIntervalo();
                },
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

                    iniciarIntervalo()
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
            domicilioNotificacionSolicitante.value = persona.solicitante?.domicilio_notificacion.direccion || null                        
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
            idContactoPropiedad.value = persona.propietario?.id 
            curpPropietario.value = persona.curp
            curpPropietarioCompleta.value = true
            nomPropietario.value = persona.nombre
            apePropietario.value = persona.apellidos
            telefonoPropietario.value = persona.propietario?.telefono || null
            emailPropietario.value = persona.propietario?.email || null
            domicilioNotificacionPropietario.value = persona.propietario?.contacto?.domicilio_notificacion?.direccion || null                        
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

    const CVE_MUNICIPIO = ref('012')
    const nuevaBtn = ref(null);

    // Función para iniciar el temporizador.
    const iniciarIntervalo = () => {
        if (!intervalo.value) { // Solo si no está ya iniciado
            intervalo.value = setInterval(() => {
            fetchSolicitudes(false, true);
            }, intervaloMs);
        }
    };

    // Función para detener el temporizador.
    const detenerIntervalo = () => {
        if (intervalo.value) {
            clearInterval(intervalo.value);
            intervalo.value = null; // Resetea el valor
        }
    };


    onMounted(() => {
        fechaIngresoInicioQuery.value = props.fechaIngresoInicioQuery
        fechaIngresoFinQuery.value = props.fechaIngresoFinQuery
        idRangoFechasIngresoQuery.value = props.idRangoFechasIngresoQuery

        fechaAceptacionInicioQuery.value = null
        fechaAceptacionFinQuery.value = null
        idRangoFechasAceptacionQuery.value = 2

        sortColumn.value = props.sortColumn || 'fecha_ingreso';
        sortDirection.value = props.sortDirection || 'asc';
        paraNuevaSolicitud.value = props.paraNuevaSolicitud || false;

        busquedaIndexada.value = false;

        if (paraNuevaSolicitud.value) {
            // Simula el click después de un pequeño delay (para asegurar que el botón esté en el DOM)
            setTimeout(() => {
            if (nuevaBtn.value) {
                nuevaBtn.value.click(); // Dispara el click programático
            }
            }, 100);
        }

        rangoFechasIngresoQuery.value = [
            fechaIngresoInicioQuery.value, // formato 'YYYY-MM-DD'
            fechaIngresoFinQuery.value
        ]

        rangoFechasAceptacionQuery.value = [
            fechaAceptacionInicioQuery.value, // formato 'YYYY-MM-DD'
            fechaAceptacionFinQuery.value
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
                            rangoFechasIngresoManual.value = true
                            onDateChange(rangoFechasIngresoQuery.value)
                        }
                    })
                })
            }
        })

        // intervalo.value = setInterval(() => {
        //     fetchSolicitudes(false, true)
        // }, intervaloMs)

        iniciarIntervalo();
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
                    domicilioNotificacionPropietario.value = propiedad.value?.contacto?.domicilio_notificacion?.direccion

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
        formData.append('numQuery', numQuery.value)
        formData.append('folioQuery', folioQuery.value)
        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        formData.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        formData.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        formData.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        formData.append('claveCatastralQuery', claveCatastralQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('curpSolicitante', curpSolicitante.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value)
        formData.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)
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
         const LIMITE_MB = 15; 
        const LIMITE_BYTES = LIMITE_MB * 1024 * 1024; // Convertimos a Bytes

        // Consultamos el tamaño real del objeto 'file.value'
        if (file.value.size > LIMITE_BYTES) {
            const pesoActualMB = (file.value.size / (1024 * 1024)).toFixed(2); // Calculamos peso para mostrarlo
            isUploading.value = false
            Swal.fire({
                icon: 'error',
                title: '¡Archivo muy pesado!',
                html: `
                    El archivo pesa <b>${pesoActualMB} MB</b>.<br>
                    El límite permitido es de <b>${LIMITE_MB} MB</b>.<br>
                    <br>
                    <small>Por favor comprímelo o sube uno más ligero.</small>
                `,
                confirmButtonText: 'Entendido',
                 didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container')
                            if (swalContainer) {
                                swalContainer.style.setProperty('z-index', '99999', 'important')
                            }
                        }
            });

            // IMPORTANTE: Detenemos la función aquí. No se sube nada.
            return; 
        }
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

    //CHECKPOINT: Imprimir en la solicitud (y preview) la documentación entregada/no entregada  OK
    //CHECKPOINT: Agregar campo editable a los requisitos (checar cuales tablas) OK
    //porque se va a hacer un CRUD de requisitos  OK

    //CHECKPOINT: Habilitar la CANCELACIÓN de SOLICITUDES ACEPTADAS (Borrar de solicitudes_aceptadas)  OK
    //CHECKPOINT: Agregar DIRECCIÓN DE NOTIFICACION a CONTACTOS  OK

    //CHECKPOINT: Al filtrar por NÚMERO DE SOLICITUD se traba  OK
    //CHECKPOINT: En la localidad de referencia se encima el titulo con el select
    //CHECKPOINT: Si hay filtro de localidad y se ACTUALIZA una solicitud se borran los filtros OK
    //Ando batallando con las localidades N/A porque tienen valor NULL e ignora este filtro despues de actualizar la solicitud
  
    //CHECKPOINT: Validar que el número de la propiedad no exceda del límite de caracteres de la BD  OK
    //CHECKPOINT: Al imprimir la solicitud preliminar y la oficial en ocasiones no se abre la ventana modal
    //Creo que no se abre cuando coincide con el polling de la tabla de solicitudes  OK
    //Hay que deshabilitar el polling cuando se IMPRIMA la solicitud  OK
    //CHECKPOINT: Falta validar que no se repitan los trámites en diferente solicitudes (Ver caso de SANDRA LUZ GONZALEZ HERNANDEZ)

    //CHECKPOINT: Al ordenar por PROPIETARIO/SOLICITANTE en la tabla de solicitudes no aparece el ícono de
    //ORDENAR a un lado del header y no ordena los solicitantes de CONSTANCIA DE UBICACIÓN  OK

    //CHECKPOINT: Agregar LOCALIDAD y TIPO PROPIEDAD a la CONSTANCIA DE UBICACIÓN --- OK        
    //CHECKPOINT: Modificar el query para corregir las estadísticas de LOCALIDAD y TIPO PROPIEDAD y
    //considere la tabla SOLICITUD_REFERENCIAS --- OK
    //CHECKPOINT: En el DASHBOARD corregir lo de VER MÁS para que se enlace con la estadística correcta --OK
    //CHECKPOINT: Cuando la colonia empiece con la palabra COLONIA o COL. no imprimir COL. en el PDF -- OK
    //CHECKPOINT: De igual manera si en el número dice S/N o S / N o S/ N, o algo así no imprimir N° en el PDF -- OK
    
    
    //CHECKPOINT: Probar la impresión de PREVIEW y SOLICITUD con DOMICILIO DE NOTIFICACIÓN
    //Y SOLICITANTE Y PROPIETARIO incluido para ver si cabe junto con DOCUMENTACIÓN ENTREGADA

    //CHECKPOINT: Checar cuándo se guarda en DOMICILIOS_NOTIFICACIONES, solo debe 
    //guardarse cuando no esté en blanco el input
    
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
                    formData2.append('numQuery', numQuery.value);
                    formData2.append('folioQuery', folioQuery.value);
                    formData2.append('nombreQuery', nombreQuery.value);
                    formData2.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value);
                    formData2.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value);
                    formData2.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value);
                    formData2.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value);
                    formData2.append('claveCatastralQuery', claveCatastralQuery.value)
                    formData2.append('tiposTramitesQuery', tiposTramitesQuery.value);
                    formData2.append('tramitesQuery', tramitesQuery.value);
                    formData2.append('estatusQuery', estatusQuery.value);
                    formData2.append('filtroChkSolicitudes', filtroChkSolicitudes.value);
                    formData2.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value);
                    formData2.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value);
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
            formData2.append('numQuery', numQuery.value);
            formData2.append('folioQuery', folioQuery.value);
            formData2.append('nombreQuery', nombreQuery.value);
            formData2.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value);
            formData2.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value);
            formData2.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value);
            formData2.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value);
            formData2.append('claveCatastralQuery', claveCatastralQuery.value)
            formData2.append('tiposTramitesQuery', tiposTramitesQuery.value);
            formData2.append('tramitesQuery', tramitesQuery.value);
            formData2.append('estatusQuery', estatusQuery.value);
            formData2.append('filtroChkSolicitudes', filtroChkSolicitudes.value);
            formData2.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value);
            formData2.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value);
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
                solicitudSelected.value = router.page.props.flash.solicitud

                if (idEstatusSolicitud.value == 99) 
                {
                    dialogVisible.value = false
                }

                activeTab.value = router.page.props.flash.activeTab || 'croquis'
            },
            onFinish: () => {
                cambiaTabError.value = true //Cambia el tab por error
            },
            preserveScroll: true,
            preserveState: true,
            replace: true,

            onError: async (errors) => {
                if (errors.duplicado) 
                {
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
                        isSavingModal.value = true
                        await nextTick()

                        setTimeout(async () => {
                            await enviarSolicitud(formData)
                        }, 100) // ⏱️ 100ms para asegurar render
                    }
                    else
                    {
                        isSavingModal.value = false
                    }
                } 
                else 
                {
                    const hasValidationErrors = errors && Object.keys(errors).length > 0
                    const errorMessage = hasValidationErrors ? Object.values(errors).join('<br>') : 'Hubo un error al guardar la información.'

                    isSavingModal.value = false

                    activeTab.value = router.page.props.flash.activeTab || 'croquis'

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
        formData.append('domicilioNotificacionPropietario', domicilioNotificacionPropietario.value)
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
            formData.append('domicilioNotificacionSolicitante', domicilioNotificacionSolicitante.value)
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
        formData.append('tipoPropiedadReferencia', tipoPropiedadReferencia.value)
        formData.append('idLocalidadReferencia', idLocalidadReferencia.value)

        const currentPage = router.page.props.paginaActual || 1

        formData.append('numQuery', numQuery.value)
        formData.append('folioQuery', folioQuery.value)
        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        formData.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        formData.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        formData.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        formData.append('claveCatastralQuery', claveCatastralQuery.value)
        formData.append('idRangoFechasIngresoQuery', idRangoFechasIngresoQuery.value)
        formData.append('idRangoFechasAceptacionQuery', idRangoFechasAceptacionQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('sortColumn', sortColumn.value)
        formData.append('sortDirection', sortDirection.value)
        formData.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value)
        formData.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)
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

        if (numQuery.value) {
            urlParams.set('numQuery', numQuery.value)
        } else {
            urlParams.delete('numQuery')
        }

        if (folioQuery.value) {
            urlParams.set('folioQuery', folioQuery.value)
        } else {
            urlParams.delete('folioQuery')
        }

        if (nombreQuery.value) {
            urlParams.set('nombreQuery', nombreQuery.value)
        } else {
            urlParams.delete('nombreQuery')
        }

        if (claveCatastralQuery.value) {
            urlParams.set('claveCatastralQuery', claveCatastralQuery.value)
        } else {
            urlParams.delete('claveCatastralQuery')
        }

        urlParams.set('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        urlParams.set('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        urlParams.set('rangoFechasIngresoManual', rangoFechasIngresoManual.value)

        urlParams.set('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        urlParams.set('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        urlParams.set('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)

        urlParams.set('page', page)

        return Object.fromEntries(urlParams.entries())
    }

    function fetchSolicitudes(onMounted, polling = false) {
        const page = router.page.props.solicitudes?.current_page ?? 1
        const paramsObject = buildQueryParams(page)

        let showLoaderTimeout = null

        if (onMounted) 
        {
            sortColumn.value = 'fecha_ingreso'
            sortDirection.value = 'asc'
        } 
        else 
        {
            if (!polling) {
                showLoaderTimeout = setTimeout(() => {
                    isSearching.value = true
                }, 200)
            }
        }

        const filtros = {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: fechaIngresoFinQuery.value,
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            numQuery: numQuery.value,
            folioQuery: folioQuery.value,
            nombreQuery: nombreQuery.value,
            claveCatastralQuery: claveCatastralQuery.value,
            tiposTramitesQuery: tiposTramitesQuery.value,
            tramitesQuery: tramitesQuery.value,
            localidadesQueryFiltradas: localidadesQueryFiltradas.value,
            estatusQuery: estatusQuery.value,
            idRangoFechasIngresoQuery: idRangoFechasIngresoQuery.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            idRangoFechasAceptacionQuery: idRangoFechasAceptacionQuery.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            sortColumn: sortColumn.value,
            sortDirection: sortDirection.value
        }

        // 1. Prepara el objeto de datos de la forma de manera segura
        const formData = new FormData()
        for (const key in filtros) 
        {
            const value = filtros[key]
            if (Array.isArray(value)) 
            {
                value.forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item)
                })
            } 
            else 
            {
                formData.append(key, value ?? '')
            }
        }
        formData.append('page', paramsObject.page)

        // 2. Define las opciones base de la solicitud
        const requestOptions = {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            onStart: () => {
                // Lógica común de inicio
            },
            onFinish: () => {
                const currentPage = router.page.props.solicitudes.current_page;
                const lastPage = router.page.props.solicitudes.last_page;
                
                if (currentPage > lastPage)   //Si la página actual excede al número de páginas obtenidas
                {
                    const newFilters = { 
                        ...router.page.props.filters, 
                        page: 1 
                    };
                    
                    //Vuelve a cargar la página con la página #1
                    router.get(
                        router.page.url.split('?')[0],
                        newFilters,
                        { preserveState: true }
                    );
                }

                if (onMounted) {
                    isLoading.value = false
                } else {
                    clearTimeout(showLoaderTimeout)
                    isSearching.value = false
                }
                filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes
            }
        }

        // 3. Añade la propiedad 'only' solo si es polling
        if (polling) {
            requestOptions.only = ['solicitudes']
        }

        router.post('/solicitudes', formData, requestOptions)
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
        rangoFechasIngresoManual.value = !coincideConShortcut
        rangoFechasShortcut.value = coincideConShortcut

        if (rangoFechasIngresoManual.value)
        {
            fechaIngresoInicioQuery.value = rangoFechasIngresoQuery.value[0]
            fechaIngresoFinQuery.value = rangoFechasIngresoQuery.value[1]
        }

        fetchSolicitudes(false)
    }

    function onDateChangeAceptacion(fechaSeleccionada) {
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
        rangoFechasAceptacionManual.value = !coincideConShortcut
        rangoFechasAceptacionShortcut.value = coincideConShortcut

        if (rangoFechasAceptacionManual.value)
        {
            fechaAceptacionInicioQuery.value = rangoFechasAceptacionQuery.value[0]
            fechaAceptacionFinQuery.value = rangoFechasAceptacionQuery.value[1]
        }

        fetchSolicitudes(false)
    }

    function sortTable(column) {
        if (column == 'asc' || column == 'desc')
        {
            sortDirection.value = column  
        }
        else
        {
            sortColumn.value = column
        }

        router.post(
            '/solicitudes',
            {
                fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
                fechaIngresoFinQuery: fechaIngresoFinQuery.value,
                fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
                fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
                numQuery: numQuery.value,
                folioQuery: folioQuery.value,
                nombreQuery: nombreQuery.value,
                claveCatastralQuery: claveCatastralQuery.value,
                tiposTramitesQuery: tiposTramitesQuery.value,
                tramitesQuery: tramitesQuery.value,
                estatusQuery: estatusQuery.value,
                filtroChkSolicitudes: filtroChkSolicitudes.value,
                sortColumn: sortColumn.value,
                sortDirection: sortDirection.value,
                rangoFechasIngresoManual: rangoFechasIngresoManual.value,
                rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
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
        // 1. Validación: Retorna 'S/F' si la fecha no existe.
        if (!dateString) {
            return 'N/A' 
        }

        // 2. Manejo de fecha y hora
        // Extraemos solo la parte de la fecha (todo antes del primer espacio, si existe)
        // Ejemplo: '2025-09-26 13:58:37' -> '2025-09-26'
        const dateOnly = dateString.split(' ')[0]

        // Verificamos si la extracción resultó en algo vacío (por si dateString era solo espacios)
        if (!dateOnly) {
            return 'N/A'
        }

        // 3. Proceso de formateo de la fecha limpia
        // Usamos la fecha limpia 'YYYY-MM-DD'
        const [year, month, day] = dateOnly.split('-') // Divide la fecha ISO en partes

        // 4. Retorna la fecha en formato "dd/mm/yyyy"
        return `${day}/${month}/${year}`
    }

    const actualizarTramitesSeleccionados = async (tramiteId) => {  
        if (tramiteId == ID_CONSTANCIA_NUMERO_OFICIAL)
        {
            numeroPropiedad.value = ''
        }
        
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

            // Ejecuta esto en la consola del navegador:

            refrescaCroquis()
        }

        if (claveCatastral.value.length != 23)
        {
            claveCatastralCompleta.value = false
        }

        const tramites = tramitesSeleccionados.value;

        if (!tramites || tramites.length === 0) 
        {
            requisitos.value = [];
        }
        else
        {
            const url = `/solicitudes/get-requisitos?tramites=${encodeURIComponent(tramites)}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json()
            const todosRequisitos = data.requisitos 

            requisitos.value = todosRequisitos.filter(item => Number(item.activo) === 1);

            const idsValidos = new Set(
                requisitos.value.map(r => r.id)
            );

            requisitosEntregados.value = requisitosEntregados.value.filter(idEntregado => {
                return idsValidos.has(parseInt(idEntregado, 10));
            });


            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor')
            }
        }
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
        } else if (control == 'domicilioNotificacionSolicitanteEditable') {
            domicilioNotificacionSolicitanteEditable.value = !domicilioNotificacionSolicitanteEditable.value
            setTimeout(() => {
                floatingDomicilioNotificacionSolicitanteRef.value?.focus()
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
        } else if (control == 'domicilioNotificacionPropietarioEditable') {
            domicilioNotificacionPropietarioEditable.value = !domicilioNotificacionPropietarioEditable.value
            setTimeout(() => {
                floatingDomicilioNotificacionPropietarioRef.value?.focus()
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
        }  else if (control == 'tipoPropiedadReferenciaEditable') {
            tipoPropiedadReferenciaEditable.value = !tipoPropiedadReferenciaEditable.value
            setTimeout(() => {
                floatingTipoPropiedadReferenciaRef.value?.focus()
            }, 50)
        }  else if (control == 'idLocalidadReferenciaEditable') {
            idLocalidadReferenciaEditable.value = !idLocalidadReferenciaEditable.value
            setTimeout(() => {
                floatingIdLocalidadReferenciaRef.value?.focus()
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
           // 1. Verificar si alguno de los trámites de la solicitud tiene un ID activo en la configuración
            const tieneTramiteActivo = solicitudSelected.value?.tramites?.some(t => {
                const id = t.tramite?.id ?? t.id;
                return CONFIG_TRAMITES.ACTIVOS.includes(id);
            });

            // 2. Calcular la cantidad de trámites y el texto correspondiente
            const cantidadTramites = solicitudSelected.value?.tramites?.length || 0;
  
            // 3. Construir el HTML condicionalmente (solo si hay un trámite activo según CONFIG_TRAMITES)
            const htmlCondicional1 = tieneTramiteActivo ? `
                <div style="display: flex; align-items: center; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #dc2626; padding: 10px 12px; border-radius: 6px;">
                   <svg 
                        style="width: 20px; height: 20px; color: #dc2626; flex-shrink: 0; margin-right: 10px;" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        viewBox="0 0 24 24">
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0118 8.414V19a2 2 0 01-2 2z" />
                    </svg>
                    <span style="color: #1e293b; font-size: 0.9rem;">
                        La información capturada <strong>generará los registros correspondientes</strong> para dar continuidad a la solicitud.
                    </span>
                </div>
            ` : '';
            const htmlCondicional2 = tieneTramiteActivo ? `
                <div style="display: flex; align-items: center; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #dc2626; padding: 10px 12px; border-radius: 6px;">
                    <svg 
                        style="width: 20px; height: 20px; color: #dc2626; flex-shrink: 0; margin-right: 10px;" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        viewBox="0 0 24 24">
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            d="M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <span style="color: #1e293b; font-size: 0.9rem;">
                        Algunos procesos pueden requerir <strong>seguimiento desde módulos específicos</strong> del sistema.
                    </span>
                </div>
            ` : '';

            // 4. Lanzar el SweetAlert
            const result = await Swal.fire({
                title: '<h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">¿Aceptar esta solicitud?</h3>',
                html: `
                    <div style="text-align: left; font-size: 0.95rem; line-height: 1.5; color: #475569; margin-top: 0.75rem;">
                        <p style="margin-bottom: 0.85rem; color: #334155; font-weight: 500;">
                            Al confirmar esta acción ocurrirá lo siguiente:
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                            ${htmlCondicional1}
                            ${htmlCondicional2}
                            <div style="display: flex; align-items: center; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #dc2626; padding: 10px 12px; border-radius: 6px;">
                                <svg style="width: 20px; height: 20px; color: #dc2626; flex-shrink: 0; margin-right: 10px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span style="color: #1e293b; font-size: 0.9rem;"><strong>Se bloqueará la edición de la solicitud</strong>; ya no podrás modificar datos ni documentos.</span>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Sí, aceptar',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'No por ahora',
                cancelButtonColor: '#64748b',
                width: '32rem',
                padding: '1.5rem',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
            });

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
            formData.append('idDomicilioNotificacionSolicitante', idDomicilioNotificacionSolicitante.value)
            if (domicilioNotificacionSolicitante.value !== null && domicilioNotificacionSolicitante.value !== undefined) 
            {
                formData.append('domicilioNotificacionSolicitante', domicilioNotificacionSolicitante.value.trim())
            }
            else
            {
                formData.append('domicilioNotificacionSolicitante', ''); 
            }
        }
        formData.append('curpPropietario', curpPropietario.value)
        formData.append('curpPropietarioInvalida', curpPropietarioInvalida.value)
        formData.append('idPersonaPropietario', idPersonaPropietario.value)
        formData.append('curpPropietario', curpPropietario.value)
        formData.append('nomPropietario', nomPropietario.value)
        formData.append('apePropietario', apePropietario.value)
        formData.append('telefonoPropietario', telefonoPropietario.value)
        formData.append('emailPropietario', emailPropietario.value)
        formData.append('idDomicilioNotificacionPropietario', idDomicilioNotificacionPropietario.value)
        if (domicilioNotificacionPropietario.value !== null && domicilioNotificacionPropietario.value !== undefined) 
        {
            formData.append('domicilioNotificacionPropietario', domicilioNotificacionPropietario.value.trim())
        }
        else
        {
            formData.append('domicilioNotificacionPropietario', ''); 
        }
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
        formData.append('tipoPropiedadReferencia', tipoPropiedadReferencia.value)
        formData.append('idLocalidadReferencia', idLocalidadReferencia.value)

        formData.append('requisitosEntregados', requisitosEntregados.value)

        const currentPage = router.page.props.paginaActual || 1

        formData.append('numQuery', numQuery.value)
        formData.append('folioQuery', folioQuery.value)
        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        formData.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        formData.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        formData.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        formData.append('claveCatastralQuery', claveCatastralQuery.value)
        formData.append('idRangoFechasIngresoQuery', idRangoFechasIngresoQuery.value)
        formData.append('idRangoFechasAceptacionQuery', idRangoFechasAceptacionQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('localidadesQueryFiltradas', localidadesQueryFiltradas.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('sortColumn', sortColumn.value)
        formData.append('sortDirection', sortDirection.value)
        formData.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value)
        formData.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)
        formData.append('page', currentPage)

        cambiaTabError.value = false //Indica que NO cambia el tab por error

        if (!nuevaPropiedad.value && 
            idContactoPropiedadAlCargar.value != idContactoPropiedad.value && 
            !tramitesSeleccionados.value.includes(ID_CONSTANCIA_UBICACION)  &&
            claveCatastralCompleta.value && 
            idContactoPropiedadAlCargar.value != undefined) 
        {
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
            const idEstatus = formData.get('idEstatusSolicitud');
            // console.log('idEstatus', idEstatus);
            // return
            await router.post('/solicitudes/update/' + idSolicitudEditar.value, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onStart:() => {
                    detenerIntervalo();
                },
                onSuccess: () => {
                    if (borraArchivoCroquis.value && debeSubirNuevoCroquis.value) {
                        isSavingModal.value = false
                        dialogVisible.value = true
                    } 
                    else 
                    {
                        if (idEstatus != 99)
                        {
                            Swal.fire({ 
                                toast: true,
                                icon: 'success',
                                position: 'top-end',
                                showConfirmButton: false,
                                title: router.page.props.flash.success,
                                timer: 2000,
                                timerProgressBar: true
                            })
                        }
                        modalBuscarColoniaVisible.value = false
                        modalBuscarLocalidadVisible.value = false
                        isSavingModal.value = false
                        dialogVisible.value = false
                        resetFormData()
                        iniciarIntervalo()
                        if (idEstatus == 99) {
                            abrirTramite(solicitudSelected.value, true) 
                        }
                    }
                },
                onFinish: () => {
                    borraArchivoCroquis.value = false
                    debeSubirNuevoCroquis.value = false

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
        //         rangoFechasIngresoManual.value = false

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
                idRangoFechasIngresoQuery.value = 2
                idRangoFechasAceptacionQuery.value = 2
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, today]
            }
        },
        {
            id: 3,
            text: 'Mes Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), today.getMonth(), 1) // Primer día del mes actual
                idRangoFechasIngresoQuery.value = 3
                idRangoFechasAceptacionQuery.value = 3
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, today]
            }
        },
        {
            id: 4,
            text: 'Año Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), 0, 1) // 0 = enero, 1 = día 1
                idRangoFechasIngresoQuery.value = 4
                idRangoFechasAceptacionQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

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
                idRangoFechasIngresoQuery.value = 5
                idRangoFechasAceptacionQuery.value = 5
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

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
                idRangoFechasIngresoQuery.value = 6
                idRangoFechasAceptacionQuery.value = 6
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 7,
            text: 'Año Pasado',
            value: () => {
                const start = new Date(new Date().getFullYear() - 1, 0, 1) // 1 de enero del año anterior
                const end = new Date(new Date().getFullYear() - 1, 11, 31) // 31 de diciembre del año anterior
                idRangoFechasIngresoQuery.value = 7
                idRangoFechasAceptacionQuery.value = 7
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

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
                idRangoFechasIngresoQuery.value = 8
                idRangoFechasAceptacionQuery.value = 8
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

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
                idRangoFechasIngresoQuery.value = 9
                idRangoFechasAceptacionQuery.value = 9
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

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
                idRangoFechasIngresoQuery.value = 10
                idRangoFechasAceptacionQuery.value = 10
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        // En tu objeto de shorcuts
        {
            id: 11,
            text: '1er Año Gob',
            value: () => {
                // Obtenemos el año de la fecha de inicio del periodo actual.
                const startYear = new Date(periodoActual.value.inicio).getFullYear();
                
                // Se establece en el 1 de noviembre del año de inicio del periodo.
                const start = periodoActual.value.inicio; 

                // Creamos la fecha de fin para el 1er año de gobierno.
                const end = new Date(startYear + 1, 9, 31); // El mes 9 es octubre (0-indexado)

                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false

                return [start, end];
            },
        },
        {
            id: 12, 
            text: '2do Año Gob',
            value: () => {
                // Agregamos 'T00:00:00' para que JavaScript interprete la fecha en la zona horaria local.
                const inicioStringLocal = periodoActual.value.inicio + 'T00:00:00';

                // Creamos la fecha base a partir de la cadena de texto local.
                const baseDate = new Date(inicioStringLocal);
                
                // Ahora, ajustamos el año según lo que necesites para el 2do año de gobierno
                const startYear = baseDate.getFullYear() + 1; // 2024 + 1 = 2025
                
                // Creamos la fecha de inicio del segundo año
                const start = new Date(startYear, baseDate.getMonth(), baseDate.getDate());
                
                // Creamos la fecha de fin (1 año después de la fecha de inicio menos un día)
                const end = new Date(start);
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1); 

                // Actualiza tus refs
                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false
                
                return [start, end];
            }
        },
        {
            id: 13, 
            text: '3er Año Gob',
            value: () => {
                // Agregamos 'T00:00:00' para que JavaScript interprete la fecha en la zona horaria local.
                const inicioStringLocal = periodoActual.value.inicio + 'T00:00:00';

                // Creamos la fecha base a partir de la cadena de texto local.
                const baseDate = new Date(inicioStringLocal);
                
                // Ahora, ajustamos el año según lo que necesites para el 2do año de gobierno
                const startYear = baseDate.getFullYear() + 2; // 2024 + 1 = 2025
                
                // Creamos la fecha de inicio del segundo año
                const start = new Date(startYear, baseDate.getMonth(), baseDate.getDate());
                
                // Creamos la fecha de fin (1 año después de la fecha de inicio menos un día)
                const end = new Date(start);
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1); 

                // Actualiza tus refs
                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false
                
                return [start, end];
            }
        },
    ]

    watch(rangoFechasIngresoQuery, (nuevoValor) => {
        if (Array.isArray(nuevoValor) && nuevoValor.length === 2 && nuevoValor[0] && nuevoValor[1]) {
            fechaIngresoInicioQuery.value = nuevoValor[0]
            fechaIngresoFinQuery.value = nuevoValor[1]
            rangoFechasAceptacionQuery.value[0] = null
            rangoFechasAceptacionQuery.value[1] = null
            fechaAceptacionInicioQuery.value = null
            fechaAceptacionFinQuery.value = null
            onDateChange(nuevoValor)
        }
    })

    watch(rangoFechasAceptacionQuery, (nuevoValor) => {
        if (Array.isArray(nuevoValor) && nuevoValor.length === 2 && nuevoValor[0] && nuevoValor[1]) {
            fechaAceptacionInicioQuery.value = nuevoValor[0]
            fechaAceptacionFinQuery.value = nuevoValor[1]
            rangoFechasIngresoQuery.value[0] = null
            rangoFechasIngresoQuery.value[1] = null
            fechaIngresoInicioQuery.value = null
            fechaIngresoFinQuery.value = null
            onDateChangeAceptacion(nuevoValor)
        }
    })

    watch(tramitesQuery, () => {
        fetchSolicitudes(false)
    })

    watch(estatusQuery, () => {
        fetchSolicitudes(false)
    })


    watch(localidadesSelectQuery, () => {
        localidadesQueryFiltradas.value = localidadesSelectQuery.value
        ? localidadesSelectQuery.value.map(item => item === null ? -99 : item)
        : [];

        // localidadesQueryFiltradas.value = cleanedLocalidades;
        fetchSolicitudes(false)
    })


    function handlePageChange(page) {
        router.post('/solicitudes', {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: fechaIngresoFinQuery.value,
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            numQuery: numQuery.value,
            folioQuery: folioQuery.value,
            nombreQuery: nombreQuery.value,
            claveCatastralQuery: claveCatastralQuery.value,
            tiposTramitesQuery: tiposTramitesQuery.value,
            tramitesQuery: tramitesQuery.value,
            localidadesQueryFiltradas: localidadesQueryFiltradas.value,
            estatusQuery: estatusQuery.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
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

    const CustomCalendarUp = CalendarUp; 
    const CustomCalendarDown = CalendarDown; 

    const busquedaIndexada = ref(false); // Inicialmente en false

    watch(numQuery, (newValue) => {
        // Si el valor cambia, activamos la bandera.
        busquedaIndexada.value = true;
        
        if (newValue === '') {
            busquedaIndexada.value = false;
        }
    });

    watch(folioQuery, (newValue) => {
        // Si el valor cambia, activamos la bandera.
        busquedaIndexada.value = true;
        
        if (newValue === '') {
            busquedaIndexada.value = false;
        }

        if (folioQuery.value == 'n' || folioQuery.value == 'N')  //Muestra las solicitudes sin folio
        {
            busquedaIndexada.value = false
        }
    });

    const orderByItems = ref([
        { 
            nombre: 'Número de Solicitud', 
            value: 'id',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 9l14 0" /><path d="M5 15l14 0" /><path d="M11 4l-4 16" /><path d="M17 4l-4 16" /></svg>'
        },
        { 
            nombre: 'Fecha de Ingreso', 
            value: 'fecha_ingreso',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M19 22v-6" /><path d="M22 19l-3 -3l-3 3" /></svg>'
        },
        { 
            nombre: 'Propietario/Solicitante', 
            value: 'id_propietario',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>'
        },
        { 
            nombre: 'Clave Catastral', 
            value: 'clave_catastral',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M11.85 21.48a1.992 1.992 0 0 1 -1.263 -.58l-4.244 -4.243a8 8 0 1 1 13.385 -3.585" /><path d="M20 21l2 -2l-2 -2" /><path d="M17 17l-2 2l2 2" /></svg>'
        },
        { 
            nombre: 'Trámite', 
            value: 'id_tramite',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" /><path d="M5 21h14" /><path d="M5 18h14" /><path d="M5 15h14" /></svg>'
        },
        { 
            nombre: 'Folio', 
            value: 'folio',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M10 8v8" /><path d="M14 8v8" /><path d="M8 10h8" /><path d="M8 14h8" /></svg>'
        },
        { 
            nombre: 'Fecha de Aceptación', 
            value: 'fecha_aceptacion',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /></svg>'
        },
        { 
            nombre: 'Localidad', 
            value: 'id_localidad',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" fill="none"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" fill="none" /></svg>'
        },
    ]);

    const getSelectedItem = (value) => {
        return orderByItems.value.find(item => item.value === value);
    };

    // Asegúrate de definir esto en tu componente Vue, cerca de tus datos (data/setup)
    const sortDirectionIcons = {
        // ICONO ASCENDENTE (Flecha hacia arriba)
       asc: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 9l3 -3l3 3" /><path d="M5 5m0 .5a.5 .5 0 0 1 .5 -.5h4a.5 .5 0 0 1 .5 .5v4a.5 .5 0 0 1 -.5 .5h-4a.5 .5 0 0 1 -.5 -.5z" /><path d="M5 14m0 .5a.5 .5 0 0 1 .5 -.5h4a.5 .5 0 0 1 .5 .5v4a.5 .5 0 0 1 -.5 .5h-4a.5 .5 0 0 1 -.5 -.5z" /><path d="M17 6v12" /></svg>',
        
        // ICONO DESCENDENTE (Flecha hacia abajo)
       desc: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 5m0 .5a.5 .5 0 0 1 .5 -.5h4a.5 .5 0 0 1 .5 .5v4a.5 .5 0 0 1 -.5 .5h-4a.5 .5 0 0 1 -.5 -.5z" /><path d="M5 14m0 .5a.5 .5 0 0 1 .5 -.5h4a.5 .5 0 0 1 .5 .5v4a.5 .5 0 0 1 -.5 .5h-4a.5 .5 0 0 1 -.5 -.5z" /><path d="M14 15l3 3l3 -3" /><path d="M17 18v-12" /></svg>'
    };

    // Función para obtener el icono
    const getSortDirectionIcon = (direction) => {
        return sortDirectionIcons[direction] || '';
    };

    const resetFilters = () => {
        nombreQuery.value = '';
        claveCatastralQuery.value = '';
        tiposTramitesQuery.value = [];
        tramitesQuery.value = [];
        localidadesSelectQuery.value = [];
        estatusQuery.value = [];     
    };

    const handleNumInput = () => {
        // 1. Limpieza del filtro opuesto (folio)
        folioQuery.value = ''; 
        
        // 2. Limpieza de los filtros adicionales
        resetFilters()

        // Llama a la función de búsqueda
        fetchSolicitudes(false);
    };

    const handleFolioInput = () => {
        // 1. Limpieza del filtro opuesto (num)
        numQuery.value = ''; 
        
        // 2. Limpieza de los filtros adicionales
        resetFilters()

        // Llama a la función de búsqueda
        fetchSolicitudes(false);
    };

    const preguntaAceptarCancelarSolicitud = async (idSolicitud, idEstatus) => {
        const texto = ref('')
        const operacionValida = ref(true)
        let titulo = 'Cambio en el estatus de la solicitud';

        if (idEstatus == 99)
        {
            texto.value = `<div style="text-align: center; font-size: 12pt">
                ¿Deseas CANCELAR la solicitud? </div>`;
            operacionValida.value = true;
            titulo = 'Cancelar Solicitud';
        }
        else if (idEstatus == 6)
        {
            texto.value = `<div style="text-align: center; font-size: 12pt">
                ¿Deseas ACTIVAR la solicitud? </div>`;
            operacionValida.value = true;
            titulo = 'Activar Solicitud';
        }
        else 
        {
            texto.value = `<div style="text-align: center; font-size: 12pt">
                Operación INVÁLIDA para el estatus ${idEstatus}. </div>`;
            operacionValida.value = false; // El botón Cancelar se ocultará
            titulo = 'Error de Estatus';            
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: titulo,
            html: texto.value,
            showCancelButton: operacionValida.value, 
            confirmButtonText: operacionValida.value ? 'Aceptar' : 'Cerrar', // Adaptamos el texto
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

        if (result.isConfirmed) 
        {
            if (idEstatus == 99)
                cambiarEstatusSolicitud(idSolicitud, 6)
            else if (idEstatus == 6)
                cambiarEstatusSolicitud(idSolicitud, 99)
        }
    };

    const cambiarEstatusSolicitud = async (idSolicitud, idEstatus) => {
        const currentPage = router.page.props.paginaActual || 1
        const formData = new FormData()

        formData.append('numQuery', numQuery.value)
        formData.append('folioQuery', folioQuery.value)
        formData.append('nombreQuery', nombreQuery.value)
        formData.append('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        formData.append('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        formData.append('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        formData.append('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        formData.append('claveCatastralQuery', claveCatastralQuery.value)
        formData.append('idRangoFechasIngresoQuery', idRangoFechasIngresoQuery.value)
        formData.append('idRangoFechasAceptacionQuery', idRangoFechasAceptacionQuery.value)
        formData.append('tiposTramitesQuery', tiposTramitesQuery.value)
        formData.append('localidadesQueryFiltradas', localidadesQueryFiltradas.value)
        formData.append('tramitesQuery', tramitesQuery.value)
        formData.append('estatusQuery', estatusQuery.value)
        formData.append('filtroChkSolicitudes', filtroChkSolicitudes.value)
        formData.append('sortColumn', sortColumn.value)
        formData.append('sortDirection', sortDirection.value)
        formData.append('rangoFechasIngresoManual', rangoFechasIngresoManual.value)
        formData.append('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)
        formData.append('page', currentPage)

        cambiaTabError.value = false //Indica que NO cambia el tab por error

        const url = `/solicitudes/update-estatus/${idSolicitud}/${idEstatus}`;

        try {
            await router.post(url, formData, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onStart:() => {
                    detenerIntervalo();
                },
                onSuccess: () => {
                    Swal.fire({ 
                        toast: true,
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        title: router.page.props.flash.success,
                        timer: 2000,
                        timerProgressBar: true
                    })
                    isSavingModal.value = false
                    dialogVisible.value = false
                    resetFormData()
                    iniciarIntervalo()
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

    const requisitos = ref([])

    const requisitosEntregados = ref([]); // Estado de los checkboxes: { 1: true, 2: false, ... }

    /**
     * Función para alternar el estado del checkbox al hacer clic en cualquier parte de la tarjeta.
     * @param {number} requisitoId - El ID del requisito.
     */
   const toggleRequisito = (idRequisito) => {
        // 1. Convertir el ID a ENTERO (crucial para que indexOf funcione correctamente)
        const id = parseInt(idRequisito);
        
        // 2. Verificar si el ID ya existe en el array
        const index = requisitosEntregados.value.indexOf(id);

        if (index === -1) {
            // Si NO existe, lo agregamos (marcar como Entregado)
            // Usamos .push() que garantiza la reactividad
            requisitosEntregados.value.push(id);
        } else {
            // Si SÍ existe, lo eliminamos (marcar como Pendiente)
            // Usamos .splice() que garantiza la reactividad
            requisitosEntregados.value.splice(index, 1);
        }
    };

    /**
     * Función principal para manejar la persistencia del estado en la API.
     * @param {Object} requisito - El objeto requisito completo.
     */

    const filtroObligatorio = ref(true); 

    // 3. Propiedad Computada (Reemplazo de computed)
    // Filtra la lista de requisitos cada vez que cambia el estado del filtro
    const requisitosFiltrados = computed(() => {
        // Si la lista de requisitos no existe, retorna un array vacío para evitar errores.
        if (!requisitos.value || requisitos.value.length === 0) {
            return [];
        }

        // Filtra basándose en el valor booleano de filtroObligatorio.value
        return requisitos.value.filter(r => 
            r.obligatorio === filtroObligatorio.value
        );
    });

    // 4. Métodos (Reemplazo de methods)
    /**
    * Cambia el estado del filtro.
    * @param {boolean} isObligatorio - true para Obligatorios, false para Opcionales.
    */
    const setFiltroDocObligatoria = (isObligatorio) => {
        filtroObligatorio.value = isObligatorio;
    };

    const esRequisitoEntregado = computed(() => (requisitoId) => {
        // Usamos includes() para verificar la presencia en el array de IDs
        return requisitosEntregados.value.includes(requisitoId);
    });

    const todosObligatoriosCompletados = computed(() => {

        if (requisitos.value.length === 0) {
            // Si no hay requisitos en absoluto, retorna 0 (según tu solicitud)
            return 1; 
        }
        
        // 1. Filtrar los requisitos para obtener solo los obligatorios
        const obligatorios = requisitos.value.filter(r => r.obligatorio === true);

        // Si no hay requisitos obligatorios en la lista, el resultado es true (ya están "completados").
        if (obligatorios.length === 0) {
            return true;
        }

        // 2. Usar .every() para verificar que CADA requisito obligatorio esté en la lista de entregados.
        // Usamos parseInt() en el ID del requisito por si acaso, para asegurar la comparación con los IDs en el array requisitosEntregados.value.
        return obligatorios.every(requisito => {
            const idNumerico = parseInt(requisito.id);
            return requisitosEntregados.value.includes(idNumerico);
        });
    });

    const estatusSolicitudSelectFiltrados = computed(() => {
        // Si TODOS los requisitos obligatorios están completados:
        
        if (!todosObligatoriosCompletados.value) {
            // Filtra el array original para EXCLUIR el estatus con ID 99.
            return estatusSolicitudSelect.value.filter(item => item.id !== 99);
        }
        
        // Si NO están completados O si la lista original ya está filtrada, devuelve el array original completo.
        return estatusSolicitudSelect.value;
    });

    // ... (Tus otras propiedades computadas y funciones)

    const requisitosSoloEntregados = computed(() => {
        // 1. Verificar si la lista base de requisitos existe para evitar 'Cannot read properties of undefined'.
        if (!requisitos.value || requisitos.value.length === 0) {
            return [];
        }


        // 2. Verificar que la función de chequeo de entrega esté disponible.
        if (!esRequisitoEntregado.value) {
            return [];
        }

        // 3. Retorna una lista filtrada.
        // ** IMPORTANTE: Usamos 'requisitos.value' para obtener el ARRAY COMPLETO de requisitos
        //             y no 'requisitosFiltrados' que puede estar mostrando solo obligatorios/opcionales. **
        return requisitos.value.filter(requisito => {
            // Chequeo de seguridad: convertimos a número antes de llamar a la función,
            // asumiendo que esRequisitoEntregado espera un ID numérico, como en 'todosObligatoriosCompletados'.
            const idNumerico = parseInt(requisito.id);

            // Ejecutamos la función obtenida de la propiedad computada (por eso el .value(id))
            return esRequisitoEntregado.value(idNumerico);
        });
    });

    const selectedEstatus = computed(() => {
        return estatusSolicitudSelectFiltrados.value.find(t => t.id === idEstatusSolicitud.value) || null
    })

    const textoBotonActualizar = computed(() => {
        return idEstatusSolicitud.value === 99 
            ? 'Aceptar Solicitud' // O el texto que prefieras para el ID 6
            : 'Actualizar Solicitud';
    });

    // function todosAsignados(solicitud) {
    //     const tramites = solicitud.tramites || [];
    //     const asignados = solicitud.tramites_asignados || [];

    //     return tramites.every(t => {
    //         const idDisponible = t.tramite ? t.tramite.id : t.id;
    //         return asignados.some(ta => ta.id_tramite === idDisponible);
    //     });
    // }

    function todosAsignados(solicitud) {
        const tramites = solicitud.tramites || [];
        const asignados = solicitud.tramites_asignados || [];

        return tramites.every(t => {
            const idDisponible = t.tramite ? t.tramite.id : t.id;
            
            // Buscamos si existe el trámite asignado y validamos que además tenga documento generado
            return asignados.some(ta => {
                const esMismoTramite = ta.id_tramite === idDisponible;
                const tieneDocumento = ta.documento_generado !== null && ta.documento_generado !== undefined;
                
                return esMismoTramite && tieneDocumento;
            });
        });
    }
</script>

<template>
    <el-dialog v-model="dialogVisible" :width="dialogWidth" :before-close="handleClose" :close-on-click-modal="false" :close-on-press-escape="false" top="6vh" style="border-radius: 12px !important; ">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-slate-100 pb-1">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-1.5 bg-color1-600 rounded-full"></div> <div>
                        <h2 class="text-xl font-bold text-slate-800">
                            {{ solicitudBloqueada ? 'Solicitud' : (paraEditarSolicitud ? 'Editar Solicitud' : 'Nueva Solicitud') }}
                        </h2>
                        <p v-if="paraEditarSolicitud" class="text-xs text-slate-400 font-medium tracking-wide uppercase">
                            {{ solicitudBloqueada ? 'Folio' : 'ID' }}: 
                            <span class="text-slate-600">
                                {{ ((solicitudBloqueada ? folioSolicitud : idSolicitudEditar) % 100000).toString().padStart(5, '0') }}
                            </span>
                        </p>
                    </div>
                </div>

                <div v-if="tramitesSeleccionados?.length && activeTab != 'tramite'" class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] uppercase font-bold text-slate-400 mr-2">
                        {{ tramitesSeleccionados.length === 1 ? 'Trámite Seleccionado:' : 'Trámites Seleccionados:' }}
                    </span>
                    <div v-for="tramiteId in tramitesSeleccionados" 
                        :key="tramiteId"
                        class="px-2 py-1 bg-white border border-color1-500 text-color1-600 text-xs font-medium rounded-lg shadow-sm">
                        {{ tiposTramites.flatMap((t) => t.tramites).find((t) => t.id === tramiteId)?.nombre }}
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
                left: 0;            
                display: flex;
                align-items: center;
                justify-content: center;
                /* El z-index debe ser lo suficientemente alto para cubrir el contenido del modal */
                z-index: 100000; 
                background-color: rgba(255, 255, 255, 0.85); /* Opacidad para destacar */
                width: 100%;
                height: 100%;">
            <svg class="animate-bounce" style="width: 2rem; height: 2rem; color: gray" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                <path
                    d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"/>
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
                <label class="block text-sm text-gray-500 dark:text-gray-400">Fecha de Ingreso</label>
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
            <div v-if="solicitudBloqueada" class="relative w-full md:w-auto flex flex-col items-center md:items-start">
                <label class="block text-sm text-gray-500 dark:text-gray-400">Fecha Aceptación</label>
                <input
                    v-model="fechaAceptacionSolicitud"
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
                </div>
                <el-select v-if="idEstatusSolicitudEditable" v-model="idEstatusSolicitud" placeholder="Selecciona un ESTATUS" class="custom-select">
                    <template #prefix>
                        <span 
                            v-if="selectedEstatus"
                            :style="{ backgroundColor: getColorById(idEstatusSolicitud) }" 
                            class="w-2.5 h-2.5 rounded-sm inline-block shrink-0 ml-2 mr-1">
                        </span>
                    </template>
                    <el-option class="custom-option" v-for="item in estatusSolicitudSelectFiltrados" :key="item.id" :label="item.nombre" :value="item.id" :style="{ color: item.color }">
                        <span class="w-2.5 h-2.5 rounded-sm inline-block mr-2" :style="{ backgroundColor: item.color }"></span>
                        {{ item.nombre }}
                    </el-option>
                </el-select>

                 <div v-else class="inline-flex items-center gap-2 px-4 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm"
                    :style="{ boxShadow: `0 0 10px ${colorEstatusSolicitud}30`, borderColor: colorEstatusSolicitud }">
                    <span class="w-2.5 h-2.5 rounded-full animate-pulse" 
                        :style="{ backgroundColor: colorEstatusSolicitud, boxShadow: `0 0 8px ${colorEstatusSolicitud}` }">
                    </span>
                    <span class="text-sm font-bold tracking-wide" :style="{ color: `color-mix(in srgb, ${colorEstatusSolicitud}, #0f172a 40%)` }">
                        {{ nombreEstatusSolicitud }}
                    </span>
                </div>
            </div>
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
                <li v-if="!tramitesSeleccionados?.includes(ID_CONSTANCIA_UBICACION) && tramitesSeleccionados.length > 0" class="inline-block md:me-2">
                    <a
                        href="#"
                        @click.prevent="handleTabClick('documentacion')"
                        :class="activeTab === 'documentacion' ? 'text-color1 border-color1' : 'hover:text-gray-700 hover:border-gray-400 dark:hover:text-gray-400'"
                        class="inline-flex items-center justify-center py-2 px-2 md:p-4 border-b-2 border-transparent rounded-t-lg group">
                       <svg
                            :class="activeTab === 'documentacion' ? 'text-color1' : 'text-gray-500 group-hover:text-gray-600 dark:text-gray-600 dark:group-hover:text-gray-400'"
                            class="w-4 h-4 me-2"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            fill="none"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                            <path d="M5 21h14" />
                            <path d="M5 18h14" />
                            <path d="M5 15h14" />
                        </svg>
                        <span class="hidden md:inline">Documentación</span>
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
                            class="pt-[10px] pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-white"
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
                                @click="habilitarCaptura('superficiePropiedadEditable')">
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
                <div class="relative z-0 mb-5 group peer w-full"
                :class="!tramitesSeleccionados?.includes(ID_CONSTANCIA_NUMERO_OFICIAL) ? 'md:w-[41%]' : 'md:w-[56%]'">
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
                        Vialidad
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
                <!-- Si el trámite es CONSTANCIA DE NÚMERO OFICIAL se oculta el número del domicilio -->
                <div v-if="!tramitesSeleccionados?.includes(ID_CONSTANCIA_NUMERO_OFICIAL)" class="relative z-0 mb-5 group peer w-full md:w-[15%]">
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
                <div class="relative z-0 mb-10 group peer w-full md:w-[25%]">
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
                <div class="relative z-0 mb-10 group peer w-full md:w-[73%]">
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
                <div class="relative z-0 mb-5 group peer w-full md:w-[78%]">
                    <input
                        v-model="domicilioNotificacionSolicitante"
                        ref="floatingDomicilioNotificacionSolicitanteRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': domicilioNotificacionSolicitanteEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !domicilioNotificacionSolicitanteEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': domicilioNotificacionSolicitanteEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!domicilioNotificacionSolicitanteEditable"/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="block">D  o  m  i  c  i  l  i  o &nbsp; d  e  &nbsp;  n  o  t  i  f  i  c  a  c  i  ó  n</span>
                        <button
                            v-if="!domicilioNotificacionSolicitanteEditable && !domicilioNotificacionSolicitanteBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('domicilioNotificacionSolicitanteEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
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
                <div class="relative z-0 mb-10 group peer w-full md:w-[25%]">
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
                <div class="relative z-0 mb-10 group peer w-full md:w-[73%]">
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
                <div class="relative z-0 mb-5 group peer w-full md:w-[78%]">
                    <input
                        v-model="domicilioNotificacionPropietario"
                        ref="floatingDomicilioNotificacionPropietarioRef"
                        type="text"
                        class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                        :class="[
                            {
                                'pb-1 py-2.5 px-0': domicilioNotificacionPropietarioEditable,
                                'bg-color3-50 p-0 m-0 mt-2': !domicilioNotificacionPropietarioEditable,
                                'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': domicilioNotificacionPropietarioEditable
                            }
                        ]"
                        placeholder=""
                        :disabled="!domicilioNotificacionPropietarioEditable"/>
                    <label
                        style="letter-spacing: -0.12em; z-index: 10"
                        class="flex peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-6 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1">
                        <span class="block">D  o  m  i  c  i  l  i  o &nbsp; d  e  &nbsp;  n  o  t  i  f  i  c  a  c  i  ó  n</span>
                        <button
                            v-if="!domicilioNotificacionPropietarioEditable && !domicilioNotificacionPropietarioBloqueado"
                            class="ml-2 bg-transparent text-gray-500 hover:text-color1"
                            @click="habilitarCaptura('domicilioNotificacionPropietarioEditable')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                <!-- Uso de `group-hover` para cambiar el color -->
                                <path
                                    d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                            </svg>
                        </button>
                    </label>
                </div>
                <div v-if="esSolicitanteEditable" class="relative z-0 mb-5 group peer w-full md:w-[20%]">
                    <select
                        v-model="esSolicitante"
                        class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-white p-2.5"
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
        <div v-if="activeTab === 'documentacion'" class="space-y-4">
            <div v-if="!solicitudBloqueada" class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    Marcar Documentación Entregada
                </h3>
                <div class="flex items-center space-x-3 bg-white transition duration-300">
                    <div class="flex space-x-2">
                        <span
                            @click="setFiltroDocObligatoria(true)"
                            :class="{
                                'bg-color1-700 text-white hover:bg-color1-600': filtroObligatorio === true,
                                'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 hover:text-gray-800 dark:hover:text-color1-400': filtroObligatorio !== true
                            }"
                            class="px-4 py-2 text-sm font-medium rounded-full cursor-pointer transition-all duration-200 ease-in-out text-center focus:outline-none"
                            role="button"
                            tabindex="0">
                            Doc. Obligatoria
                        </span>

                        <span
                            @click="setFiltroDocObligatoria(false)"
                            :class="{
                                'bg-color1-700 text-white hover:bg-color1-600': filtroObligatorio === false,
                                'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 hover:text-gray-900 dark:hover:text-color1-400': filtroObligatorio !== false
                            }"
                            class="px-4 py-2 text-sm font-medium rounded-full cursor-pointer transition-all duration-200 ease-in-out text-center focus:outline-none"
                            role="button"
                            tabindex="0">
                            Doc. Opcional
                        </span>
                    </div>
                </div>
            </div>
            <div v-if="!solicitudBloqueada" class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-h-[220px] overflow-y-auto overflow-x-hidden pr-2 pb-2">
                <template v-if="requisitosFiltrados && requisitosFiltrados.length">
                    <div
                        v-for="requisito in requisitosFiltrados"
                        :key="requisito.id"
                        class="flex items-center p-3 border rounded-xl transition duration-200 cursor-pointer"
                        :class="{
                            // Estilos para requisito entregado
                            'border-color2-400 bg-green-50 dark:bg-color2-900/30 shadow-md': esRequisitoEntregado(requisito.id),
                            // Estilos para requisito pendiente
                            'border-gray-200 hover:border-color3-400 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600': !esRequisitoEntregado(requisito.id)
                        }"
                        @click="toggleRequisito(requisito.id)"
                        :title="requisito.nombre"> 
                        <div class="ml-1 flex-grow min-w-0"> 
                            <label
                                :for="`entregado-${requisito.id}`"
                                class="text-xs font-medium block cursor-pointer truncate" 
                                :class="{
                                    'text-gray-900 dark:text-white': !esRequisitoEntregado(requisito.id),
                                    'text-color2-700 dark:text-color2-400': esRequisitoEntregado(requisito.id)
                                }">
                                {{ requisito.nombre }}
                            </label>
                            <p
                                class="text-xs mt-0.5 truncate"
                                :class="{
                                    'text-color2-600 dark:text-color2-400': esRequisitoEntregado(requisito.id),
                                    'text-gray-500 dark:text-gray-400': !esRequisitoEntregado(requisito.id)
                                }">
                                {{ requisitosEntregados.includes(requisito.id) ? '¡Documento Entregado!' : 'Pendiente de entrega' }}
                            </p>
                        </div>

                        <span v-if="esRequisitoEntregado(requisito.id)" class="ml-4 text-color2-700 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </div>
                </template>
                <div v-else class="col-span-full p-6 bg-color1-40 border-l-4 border-color1-600 text-color1-800 dark:bg-gray-700 dark:border-color1-600 dark:text-color1-400 sm:rounded-lg shadow-md">
                    <p v-if="filtroObligatorio" class="text-sm text-center">
                        No se encontró <b> Documentación Obligatoria</b> 
                    </p>
                    <p v-if="!filtroObligatorio" class="text-sm text-center">
                        No se encontró <b> Documentación Opcional</b> 
                    </p>
                </div>
            </div>
            <p v-if="!todosObligatoriosCompletados && !solicitudBloqueada" 
            class="border border-color1-40 ring-1 ring-color1-100 text-color1-800 rounded-md p-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-triangle-alert-icon lucide-triangle-alert flex-shrink-0">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>                
                <span>
                    El propietario/solicitante debe entregar toda la <b> Documentación Obligatoria </b>
                </span>
            </p>
            <div v-if="solicitudBloqueada" class="flex justify-between items-center w-full">
                <div v-if="solicitudBloqueada" class="w-full">
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center pl-1 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-color2-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Documentación Entregada
                    </h3>
                   <div v-if="requisitosSoloEntregados.length > 0" class="overflow-visible">
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1 list-disc pl-6 space-y-0">
                            <li v-for="requisito in requisitosSoloEntregados" :key="requisito.id" 
                                class="
                                    text-[0.8125rem] font-medium text-gray-700 dark:text-gray-200 
                                    hover:bg-green-50 dark:hover:bg-color2-900/40 transition duration-200 
                                    rounded-lg py-1 px-1 w-full 
                                    focus:outline-none focus:ring-2 focus:ring-color2-500
                                    relative cursor-default"
                                :title="requisito.nombre">                                 
                                <span>
                                    {{ requisito.nombre.length > 48 ? requisito.nombre.substring(0, 48) + '...' : requisito.nombre }}
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="p-6 bg-color1-50 border-l-4 border-color1-400 text-color1-800 dark:bg-gray-700 dark:border-color1-600 dark:text-color1-400 sm:rounded-lg shadow-md">
                        <p class="text-base">
                            <span class="font-extrabold">Aviso:</span> No hay requisitos entregados para mostrar.
                        </p>
                    </div>
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
           <div v-if="solicitudBloqueada" class="flex flex-col gap-2 p-3 bg-white border border-slate-200 rounded-lg">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="tramiteId in tramitesSeleccionados"
                        :key="tramiteId"
                        class="flex items-center gap-2 px-3 py-1.5 bg-color1-50 border border-color1-200 rounded-md shadow-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-color1-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-color1-500"></span>
                        </span>
                        
                        <span class="text-xs font-medium !text-color1-900">
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
                                            width: 915 / tiposTramites.length + 'px',
                                            minWidth: 915 / tiposTramites.length + 'px'
                                        }">
                                        <div
                                            v-if="tipoTramite.tramites[index - 1]"
                                            @click="actualizarTramitesSeleccionados(tipoTramite.tramites[index - 1].id)"
                                            :class="{
                                                'bg-color1-600 text-white font-bold border-color1-700': tramitesSeleccionados.includes(tipoTramite.tramites[index - 1].id),
                                                'text-gray-700 bg-gray-50 border-gray-400 hover:shadow-md hover:border-color1-500 hover:ring-1 hover:ring-color1-100 hover:text-color1-800': !tramitesSeleccionados.includes(
                                                    tipoTramite.tramites[index - 1].id
                                                )
                                            }"
                                            class="flex items-center justify-center h-full w-full rounded-2xl px-4 py-2 cursor-pointer border transition-colors hover:shadow-lg hover:-translate-y-1">
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
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-white p-2.5"
                            required>
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="(destino, index) in destinosObras" :key="index" :value="destino.id">
                                {{ destino.nombre }}
                            </option>
                        </select>
                        <label
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
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
                            class="pt-3 pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-white p-2.5"
                            required>
                            <option value="" disabled selected style="display: none"></option>
                            <option v-for="(sector, index) in sectores" :key="index" :value="sector.id">
                                {{ sector.nombre }}
                            </option>
                        </select>
                        <label
                            class="peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-90 peer-focus:-translate-y-9 peer-focus:scale-90 absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 origin-[0] peer-focus:text-color1 peer-focus:dark:text-color1"
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
                    class="flex items-center justify-center w-full h-60 border-2 border-gray-300 border-dashed rounded-lg bg-white dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 cursor-pointer transition-all duration-200 focus:outline-none focus:border-color1-500 focus:ring-1 focus:ring-color1-50" 
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
            <br>
           <div class="md:flex-1 flex md:items-center w-full md:w-auto md:gap-x-3 flex-wrap">
                <div class="relative z-0 mb-5 group peer w-full md:w-1/4">
                    <template v-if="tipoPropiedadReferenciaEditable">
                        <select
                            ref="floatingTipoPropiedadReferenciaRef"
                            v-model="tipoPropiedadReferencia"
                            @focus="handleFocusTipoPropiedadReferencia(true)"
                            @blur="handleFocusTipoPropiedadReferencia(false)"
                            class="pt-[10px] pl-0 pb-1 bg-transparent border-0 border-b-2 appearance-none text-gray-900 border-gray-300 w-full text-sm focus:outline-none focus:ring-0 focus:border-color1 block dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500 peer bg-white"
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
                                'scale-85 -translate-y-9': tipoPropiedadReferencia || isFocusedTipoPropiedadReferencia,
                                'scale-90 translate-y-[-11px]': !tipoPropiedadReferencia && !isFocusedTipoPropiedadReferencia
                            }"
                            :style="{
                                top: tipoPropiedadReferencia || isFocusedTipoPropiedadReferencia ? '25px' : '24px'
                            }">
                            Tipo de propiedad
                        </label>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreTipoPropiedadReferencia"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': tipoPropiedadReferenciaEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !tipoPropiedadReferenciaEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': tipoPropiedadReferenciaEditable
                                }
                            ]"
                            :disabled="!tipoPropiedadReferenciaEditable"
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'translate-y-0 scale-90': !nombreTipoPropiedadReferencia
                            }">
                            Tipo de propiedad
                            <button v-if="!tipoPropiedadReferenciaEditable && !tipoPropiedadReferenciaBloqueado" class="bg-transparent text-gray-500 hover:text-color1" @click="habilitarCaptura('tipoPropiedadReferenciaEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
                                    <path
                                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"
                                    />
                                </svg>
                            </button>
                        </label>
                    </template>
                </div>
                <div class="relative w-full md:w-[50%] mb-5">
                    <template v-if="idLocalidadReferenciaEditable">
                    <el-select
                        v-model="idLocalidadReferencia"
                        filterable
                        placeholder=" "
                        @focus="isFocusedLocalidadReferencia = true"
                        @blur="isFocusedLocalidadReferencia = false"
                        class="custom-select-down">
                        <el-option
                        v-for="item in localidades"
                        :key="item.id"
                        :label="item.nombre"
                        :value="item.id"
                        />
                    </el-select>

                    <span
                        class="absolute left-0 transition-all duration-300 pointer-events-none origin-top-left"
                        :class="{
                            // Caso 1: Sin valor y sin foco
                            'top-3 text-sm scale-90': idLocalidadReferencia == null && !isFocusedLocalidadReferencia,
                            // Caso 2: Sin valor y con foco
                            '-top-3.5 text-xs font-normal text-color1': idLocalidadReferencia == null && isFocusedLocalidadReferencia,
                            // Caso 3: Con valor y sin foco
                            '-top-3.5 text-sm': (idLocalidadReferencia != null && idLocalidadReferencia >= 0) && !isFocusedLocalidadReferencia,
                            // Caso 3: Con valor y con foco
                            '-top-3.5 text-sm scale-90 font-normal text-color1': (idLocalidadReferencia != null && idLocalidadReferencia >= 0) && isFocusedLocalidadReferencia,
                        }">
                        Localidad de la propiedad 
                    </span>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreLocalidadReferencia"
                            class="block w-full text-sm text-gray-900 border-0 appearance-none dark:text-white dark:focus:border-color1 focus:outline-none focus:ring-0 peer"
                            :class="[
                                {
                                    'pb-1 py-2.5 px-0': idLocalidadReferenciaEditable,
                                    'bg-color3-50 p-0 m-0 mt-2': !idLocalidadReferenciaEditable,
                                    'border-b-2 border-gray-300 focus:border-color1 dark:border-gray-600': idLocalidadReferenciaEditable
                                }
                            ]"
                            :disabled="!idLocalidadReferenciaEditable"
                            :placeholder="''"/>
                        <label
                            style="z-index: 10"
                            class="absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-85 top-3 -z-10 origin-[0] peer-focus:-translate-y-6 peer-focus:scale-90 peer-focus:text-color1 peer-focus:dark:text-color1"
                            :class="{
                                'translate-y-0 scale-90': !nombreLocalidadReferencia
                            }">
                            Localidad de la propiedad
                            <button v-if="!idLocalidadReferenciaEditable && !idLocalidadReferenciaBloqueado" class="bg-transparent text-gray-500 hover:text-color1" @click="habilitarCaptura('idLocalidadReferenciaEditable')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="12px" class="fill-gray-500 hover:fill-color1">
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
        <template #footer>
            <div class="flex justify-end mb-4 mr-2">
                <button
                    type="button"
                    class="mt-0 mr-3 bg-color3-100 hover:bg-color3-200 flex items-center justify-center text-gray-700 focus:ring-4 focus:ring-color3-300 font-medium rounded-full text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
                    @click="handleClose">
                    Cerrar
                </button>
                <button
                    v-if="paraEditarSolicitud && !solicitudBloqueada"
                    type="button"
                    class="mt-0 bg-color1-700 hover:bg-color1-800 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="actualizarSolicitud">
                    {{ textoBotonActualizar }}
                </button>
                <button
                    v-if="!paraEditarSolicitud"
                    type="button"
                    class="mt-0 bg-color1-700 hover:bg-color1-800 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="agregarSolicitud">
                    Guardar Solicitud
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
                    class="mt-2 bg-color1-700 hover:bg-color1-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-6 py-2 focus:outline-none dark:focus:ring-color1-700"
                    @click="acceptFile">
                    Aceptar
                </button>
                <button
                    v-if="permiteDescartarCroquis"
                    type="button"
                    class="mt-2 bg-color3-700 hover:bg-color3-600 flex items-center justify-center text-white focus:ring-4 focus:ring-color3-300 font-medium rounded-full text-sm px-6 py-2 focus:outline-none dark:focus:ring-color3-700"
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
                <thead class="text-sm text-gray-700 uppercase bg-white dark:bg-gray-700 dark:text-gray-400">
                    <template v-if="personasFiltradas && personasFiltradas.length > 0">
                        <tr>
                            <th colspan="2" class="px-0 py-1 text-left">RESULTADOS DE LA BÚSQUEDA</th>
                            <th></th>
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
                        <td class="px-4 py-2 w-1/2 text-xs font-medium">{{ persona.nombre + ' ' + persona.apellidos }}</td>
                        <td class="px-4 py-2 w-1/2" :style="{ fontSize: '11px' }"> {{ persona.curp }} </td>
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
                <thead class="text-sm text-gray-700 uppercase bg-white dark:bg-gray-700 dark:text-gray-400">
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
                v-model="nombreLocalidadBuscar"
                ref="floatingNombreLocalidadRef"
                type="text"
                placeholder="Escribe el NOMBRE de la LOCALIDAD"
                @input="debouncedFiltrarLocalidades"
                class="block w-full p-2 mb-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-color1 focus:border-color1 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:placeholder-gray-400"/>
            <button
                v-if="nombreLocalidadBuscar.length >= 5 && localidadesFiltradas && localidadesFiltradas.length === 0"
                type="button"
                @click="agregarLocalidad"
                class="absolute inset-y-0 right-0 px-4 text-white bg-color1 rounded-r-lg hover:bg-color1-600 focus:ring-2 focus:ring-color1">
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
                        style="border-left: none"/>
                </div>
            </div>
        </div>
        <div class="w-full mt-2 overflow-x-auto">
            <table class="min-w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-white dark:bg-gray-700 dark:text-gray-400">
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
                        @click="seleccionaLocalidad(localidad.id, localidad.nombre)">
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

    <section class="bg-white dark:bg-gray-900 p-3 sm:p-5">
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%]">
            <div class="flex flex-col md:flex-row items-start md:items-center md:mb-5 md:mr-2 justify-between">
                <h1 class="text-2xl font-bold ml-2">Solicitudes </h1>
                <button
                    ref="nuevaBtn"
                    @click="abreModalSolicitud"
                    type="button"
                    class="bg-color1-800 hover:bg-color1-700 flex items-center justify-center text-white focus:ring-4 focus:ring-color1-300 font-medium rounded-full text-sm px-10 py-2 focus:outline-none dark:focus:ring-color1-800 
                        mt-4 md:mt-0 
                        w-full md:w-auto"> + Nueva Solicitud
                </button>
            </div>
            <!-- <br/> -->
            <div class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 p-2 mb-1">
                <div class="flex items-center w-full md:w-[9%]">
                    <form class="flex items-center w-full">
                        <div class="relative w-full group">
                            <!-- Añade 'group' aquí -->
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg  xmlns="http://www.w3.org/2000/svg" width="18" height="18"  viewBox="0 0 24 24"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  
                                class="icon icon-tabler icons-tabler-outline icon-tabler-hash"
                                :class="{
                                    'text-gray-600 dark:text-gray-400': !(folioQuery.length > 0), /* Color normal si NO está indexado */
                                    'text-gray-400': (folioQuery.length > 0 && folioQuery != 'n' && folioQuery != 'N') /* gray-400 si SÍ está indexado/deshabilitado */
                                }">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 9l14 0" /><path d="M5 15l14 0" /><path d="M11 4l-4 16" /><path d="M17 4l-4 16" />
                            </svg>
                            </div>
                            <input
                                type="text"
                                id="simple-search"
                                class="custom-input bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-9 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                placeholder="Número"
                                v-model="numQuery"
                                @input="handleNumInput"
                                :disabled="folioQuery.length > 0 && folioQuery != 'n' && folioQuery != 'N'"/>
                            <button
                                v-if="numQuery"
                                @click=";((numQuery = ''), fetchSolicitudes(false))"
                                type="button"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 dark:bg-gray-700 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <svg class="mr-0 ml-4 w-4 h-4 text-current hover:text-color1-600 transition-colors" viewBox="0 -960 960 960" fill="currentColor">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Picker de fechas -->
                <div class="flex items-center w-full md:w-auto" ref="pickerWrapper">
                    <el-date-picker
                        v-model="rangoFechasIngresoQuery"
                        type="daterange"
                        unlink-panels
                        range-separator="-"
                        start-placeholder="-"
                        end-placeholder="-"
                        :shortcuts="shortcuts"
                        class="custom-date-picker w-full md:w-[260px]"
                        value-format="YYYY-MM-DD"
                        format="DD/MM/YYYY"
                        @change="onDateChange"
                        :prefix-icon="CustomCalendarUp"
                        :disabled="busquedaIndexada"
                        :clearable="false"/>
                </div>                    
                <!-- Input de búsqueda de PROPIETARIO/SOLICITANTE -->
                <div class="flex items-center w-full md:w-[25%]">
                    <form class="flex items-center w-full">
                        <div class="relative w-full group">
                            <!-- Añade 'group' aquí -->
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" 
                                    class="w-5 h-5"
                                    :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, /* Color normal si NO está indexado */
                                        'text-gray-400': busquedaIndexada /* gray-400 si SÍ está indexado/deshabilitado */
                                    }"                                    
                                    fill="none"                  viewBox="0 0 24 24"          stroke="currentColor"        stroke-width="2">            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="simple-search"
                                class="custom-input bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                placeholder="Propietario / Solicitante"
                                v-model="nombreQuery"
                                @input="fetchSolicitudes(false)"
                                :disabled="busquedaIndexada"/>
                            <button
                                v-if="nombreQuery"
                                @click=";((nombreQuery = ''), fetchSolicitudes(false))"
                                type="button"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 dark:bg-gray-700 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <svg class="mr-0 ml-4 w-4 h-4 text-current hover:text-color1-600 transition-colors" viewBox="0 -960 960 960" fill="currentColor">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="flex items-center w-full md:w-[18%]">
                    <form class="flex items-center w-full">
                        <div class="relative w-full group">
                            <!-- Añade 'group' aquí -->
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  
                            class="icon icon-tabler icons-tabler-outline icon-tabler-map-pin-code" 
                            :class="{
                                    'text-gray-600 dark:text-gray-400': !busquedaIndexada, /* Color normal si NO está indexado */
                                    'text-gray-400': busquedaIndexada /* gray-500 si SÍ está indexado/deshabilitado */
                                }"
                            fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M11.85 21.48a1.992 1.992 0 0 1 -1.263 -.58l-4.244 -4.243a8 8 0 1 1 13.385 -3.585" /><path d="M20 21l2 -2l-2 -2" /><path d="M17 17l-2 2l2 2" /></svg>
                            </div>
                            <input
                                type="text"
                                id="simple-search"
                                class="custom-input bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                placeholder="Clave Catastral"
                                v-model="claveCatastralQuery"
                                @input="fetchSolicitudes(false)"
                                :disabled="busquedaIndexada"/>
                            <button
                                v-if="claveCatastralQuery"
                                @click=";((claveCatastralQuery = ''), fetchSolicitudes(false))"
                                type="button"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500  dark:bg-gray-700 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <svg class="mr-0 ml-4 w-4 h-4 text-current hover:text-color1-600 transition-colors" viewBox="0 -960 960 960" fill="currentColor">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Select múltiple de tipos -->
                <div class="flex items-center w-full md:w-[23%]">
                    <el-select
                        v-model="tiposTramitesQuery"
                        placeholder="Tipos de Trámite"
                        class="custom-select w-full"
                        :class="['custom-select', busquedaIndexada ? 'is-disabled' : '']"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        :max-collapse-tags="1"
                        @change="fetchSolicitudes(false)"
                        :disabled="busquedaIndexada">
                        <!-- SVG icon in the prefix slot -->
                        <template #prefix>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1 ml-1"
                            :class="{
                                'text-gray-500 dark:text-gray-400': !busquedaIndexada, /* Color normal si NO está indexado */
                                'text-gray-400': busquedaIndexada /* gray-500 si SÍ está indexado/deshabilitado */
                            }"
                            viewBox="0 -960 960 960" fill="currentColor">
                                <path d="M160-480v240-480 240Zm400 360q17 0 28.5-11.5T600-160q0-17-11.5-28.5T560-200q-17 0-28.5 11.5T520-160q0 17 11.5 28.5T560-120Zm240-400q17 0 28.5-11.5T840-560q0-17-11.5-28.5T800-600q-17 0-28.5 11.5T760-560q0 17 11.5 28.5T800-520Zm-560 0h200v-80H240v80Zm0 160h200v-80H240v80Zm-80 200q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720H160v480h200v80H160ZM560-40q-50 0-85-35t-35-85q0-39 22.5-70t57.5-43v-127h240v-47q-35-12-57.5-43T680-560q0-50 35-85t85-35q50 0 85 35t35 85q0 39-22.5 70T840-447v127H600v47q35 12 57.5 43t22.5 70q0 50-35 85t-85 35Z"/>
                            </svg>
                        </template>

                        <el-option :style="getItemStyle(item.id - 1)" class="custom-option" v-for="item in tiposTramites" :key="item.id" :label="item.nombre" :value="item.id">
                            <span class="pl-2">
                                {{ item.nombre }}
                            </span>
                        </el-option>
                    </el-select>
                </div>
                <div class="flex items-center w-full md:w-[9%]">
                    <form class="flex items-center w-full">
                        <div class="relative w-full group">
                            <!-- Añade 'group' aquí -->
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                 <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  stroke="currentColor"  stroke-width="1.5"  stroke-linecap="round"  stroke-linejoin="round"  
                                 class="icon icon-tabler icons-tabler-outline icon-tabler-grid-pattern"
                                     :class="{
                                    'text-gray-600 dark:text-gray-400': !(numQuery.length > 0), /* Color normal si NO está indexado */
                                    'text-gray-400': (numQuery.length > 0) /* gray-400 si SÍ está indexado/deshabilitado */
                                }" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M10 8v8" /><path d="M14 8v8" /><path d="M8 10h8" /><path d="M8 14h8" /></svg>
                            </div>
                            <input
                                type="text"
                                id="simple-search"
                                class="custom-input bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-color1-500 focus:border-color1-500 block w-full pl-9 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                                placeholder="Folio"
                                v-model="folioQuery"
                                @input="handleFolioInput"
                                :disabled="numQuery.length > 0"/>
                            <button
                                v-if="folioQuery"
                                @click=";((folioQuery = ''), fetchSolicitudes(false))"
                                type="button"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 dark:bg-gray-700 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <svg class="mr-0 ml-4 w-4 h-4 text-current hover:text-color1-600 transition-colors" viewBox="0 -960 960 960" fill="currentColor">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="flex items-center w-full md:w-auto" ref="pickerWrapper">
                    <el-date-picker
                        v-model="rangoFechasAceptacionQuery"
                        type="daterange"
                        unlink-panels
                        range-separator="-"
                        start-placeholder="-"
                        end-placeholder="-"
                        :shortcuts="shortcuts"
                        class="custom-date-picker w-full md:w-[260px]"
                        value-format="YYYY-MM-DD"
                        format="DD/MM/YYYY"
                        @change="onDateChangeAceptacion"
                        :prefix-icon="CustomCalendarDown"
                        :disabled="busquedaIndexada"
                        :clearable="false"/>
                </div>                 
                <!-- FILTRO DE LOCALIDADES -->
                <div class="flex md:flex-row items-stretch md:items-center justify-end gap-2 w-full md:w-[25%]">
                    <div class="flex items-center w-full md:w-[100%]">
                       <button
                            :class="{
                                'disabled-button': solicitudes.length == 0 || busquedaIndexada,
                                'has-selections': parseInt(selectedCountTextLocalidades.replace(/[()]/g, '')) > 0
                            }"
                            :disabled="solicitudes.length == 0 || busquedaIndexada"
                            id="filterDropdownButton"
                            data-dropdown-toggle="filterDropdownLocalidades"                        
                            class="filter-button group w-full md:w-full flex items-center justify-between py-2 pl-2 pr-1 text-sm font-normal text-gray-600 focus:outline-none bg-white rounded-lg border border-gray-300 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:border-color1-500 focus:ring-1 focus:ring-color1-500 dark:focus:ring-color1-700 dark:bg-color1-800 dark:text-color1-400 dark:border-color1-600 dark:hover:text-white dark:hover:bg-color1-700"
                            type="button">                            
                            <div class="flex items-center">
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    aria-hidden="true" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-map-pin h-5 w-5 mr-2 text-gray-600">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" fill="none"/> 
                                    <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" fill="none" />
                                </svg>

                                <span v-if="selectedCountTextLocalidades.replace(/[()]/g, '') > 1"> Localidades {{ selectedCountTextLocalidades }} </span>
                                <span v-else> Localidad {{ selectedCountTextLocalidades }} </span>  
                            </div>
                            <div class="flex items-center">         
                                <svg
                                    v-if="parseInt(selectedCountTextLocalidades.replace(/[()]/g, '')) > 0"
                                    class="reset-icon mr-2 ml-2 w-4 h-4 text-current opacity-0 group-hover:opacity-100 group-focus:opacity-100 transition-opacity hover:text-color1-600"
                                    viewBox="0 -960 960 960"
                                    fill="currentColor"
                                    @click.prevent.stop="resetFiltroDocObligatoriasLocalidades">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    class="mr-1 w-3.5 h-3.5 text-gray-600" 
                                    fill="none" viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </button>
                        <div
                            id="filterDropdownLocalidades"
                            class="z-10 hidden w-full md:w-[22em] p-3 bg-white rounded-lg shadow dark:bg-gray-700"
                            style="position: absolute; top: 100%; left: 0; z-index: 50; margin-top: -8px">
                            <div class="flex flex-col w-full">
                                <div class="w-full">                                    
                                    <ul v-if="localidadesQuery && localidadesQuery.length > 0" class="text-sm max-h-32 overflow-y-auto pt-2 pl-3 mb-3 w-full" aria-labelledby="filterDropdownButton">
                                        <li v-for="localidad in localidadesQuery" :key="localidad.id" class="flex items-center mb-0 w-full">
                                            <input
                                                :id="`localidad-${localidad.id}`"
                                                type="checkbox"
                                                class="mb-1 w-4 h-4 bg-gray-100 border-gray-300 rounded text-color1-600 focus:ring-color1-300 dark:focus:ring-color1-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                                v-model="localidadesSelectQuery"
                                                :value="localidad.id"
                                                :disabled="localidad.count === 0"
                                                :class="{
                                                    'cursor-not-allowed opacity-50 text-gray-400': localidad.count === 0,
                                                    'cursor-pointer': localidad.count > 0,
                                                    'hover:border-color1-600 hover:border-2 cursor-pointer': localidad.count > 0
                                                }"/>
                                            <label
                                                :for="`localidad-${localidad.id}`"
                                                class="mb-1 ml-2 text-xs uppercase text-gray-600 dark:text-gray-100"
                                                :class="{
                                                    'cursor-not-allowed opacity-50 text-gray-400': localidad.count === 0,
                                                    'cursor-pointer': localidad.count > 0,
                                                    'hover:text-color1-600 cursor-pointer': localidad.count > 0
                                                }">
                                                {{ localidad.nombre }} ({{ localidad.count }})
                                            </label>
                                        </li>
                                    </ul>
                                </div>                                
                            </div>
                        </div>
                    </div>     
                </div>
                <div class="flex md:flex-row items-stretch md:items-center justify-end gap-2 w-full md:w-[18%]">
                    <!-- Botón Filtros -->
                    <div class="flex items-center w-full md:w-[100%]">
                       <button
                            :class="{
                                'disabled-button': solicitudes.length == 0 || busquedaIndexada, /* Simplificar a disabled-button */
                                'has-selections': parseInt(selectedCountText.replace(/[()]/g, '')) > 0
                            }"
                            :disabled="solicitudes.length == 0 || busquedaIndexada"
                            id="filterDropdownButton"
                            data-dropdown-toggle="filterDropdown"
                            class="filter-button group w-full md:w-full flex items-center justify-between py-2 pl-2 pr-1 text-sm font-normal text-gray-600 focus:outline-none bg-white rounded-lg border border-gray-300 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:border-color1-500 focus:ring-1 focus:ring-color1-500 dark:focus:ring-color1-700 dark:bg-color1-800 dark:text-color1-400 dark:border-color1-600 dark:hover:text-white dark:hover:bg-color1-700"
                            type="button">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    aria-hidden="true" 
                                    viewBox="0 0 24 24"  stroke="currentColor" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-filter h-4 w-5 mr-2 text-gray-600">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" 
                                        fill="none" />  
                                </svg>
                                + Filtros {{ selectedCountText }}
                            </div>
                            <div class="flex items-center">         
                                <svg
                                    v-if="parseInt(selectedCountText.replace(/[()]/g, '')) > 0"
                                    class="reset-icon mr-0 ml-2 w-4 h-4 text-current opacity-0 group-hover:opacity-100 group-focus:opacity-100 transition-opacity hover:text-color1-600"
                                    viewBox="0 -960 960 960"
                                    fill="currentColor"
                                    @click.prevent.stop="resetFiltroDocObligatorias">
                                    <path
                                        d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>

                                <svg xmlns="http://www.w3.org/2000/svg" 
                                        class="mr-1 ml-6 w-3.5 h-3.5 text-gray-600" 
                                        fill="none" viewBox="0 0 24 24" 
                                        stroke="currentColor" 
                                        stroke-width="1.5">                  
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
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
                </div>
                <div class="flex items-center w-full xs:w-1/2 md:w-[8%]">
                    <el-select
                        v-model="sortColumn"
                        class="custom-select w-full"
                        :class="['custom-select', busquedaIndexada ? 'is-disabled' : '']"
                        collapse-tags
                        collapse-tags-tooltip
                        :max-collapse-tags="1"
                        @change="sortTable"
                        :disabled="busquedaIndexada"
                        value-key="value">                        
                        <template #prefix>
                            <div class="flex items-center ml-1">
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    class="w-5 h-5 mr-2" 
                                    :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, 
                                        'text-gray-400': busquedaIndexada 
                                    }"
                                    viewBox="0 0 24 24" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M3 9l4 -4l4 4m-4 -4v14" />
                                    <path d="M21 15l-4 4l-4 -4m4 4v-14" />
                                </svg>
                                <span 
                                    v-if="getSelectedItem(sortColumn)"
                                    v-html="getSelectedItem(sortColumn).icon" 
                                    class="w-5 h-5 mr-1" :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, 
                                        'text-gray-400': busquedaIndexada 
                                    }">
                                </span>
                            </div>
                        </template>
                        <el-option
                            key="header-title"
                            label="Ordenar por..."
                            value="header-title"
                            disabled> 
                            <div class="flex items-center text-base font-semibold text-gray-500 py-1 border-b border-gray-200 cursor-default">
                                Ordenar por...
                            </div>
                        </el-option>                        
                        <el-option 
                            v-for="item in orderByItems" 
                            :key="item.value" 
                            :label="' '"
                            :value="item.value">
                            
                            <div class="flex items-center">
                                <span 
                                    v-html="item.icon" 
                                    class="w-5 h-5 mr-2">
                                </span>
                                <span>{{ item.nombre }}</span>
                            </div>                            
                        </el-option>
                    </el-select>
                </div>
                <div class="flex items-center w-full xs:w-1/2 md:w-[14%]">
                    <el-select
                        v-model="sortDirection" 
                        class="custom-select w-full"
                        :class="['custom-select', busquedaIndexada ? 'is-disabled' : '']"
                        @change="sortTable"
                        :disabled="busquedaIndexada"
                        value-key="value">
                        
                        <template #prefix>
                            <div class="flex items-center ml-1">
                                <span 
                                    v-html="getSortDirectionIcon(sortDirection)"
                                    class="w-5 h-5 mr-1"
                                    :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, 
                                        'text-gray-400': busquedaIndexada 
                                    }">
                                </span>
                                <span :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, 
                                        'text-gray-400': busquedaIndexada 
                                    }" v-if="sortDirection=='asc'">
                                    Ascendente
                                </span>
                                <span :class="{
                                        'text-gray-600 dark:text-gray-400': !busquedaIndexada, 
                                        'text-gray-400': busquedaIndexada 
                                    }" v-else>
                                    Descendente
                                </span>
                            </div>
                        </template>                        
                        <el-option 
                            label=" " 
                            value="asc"> 
                            <div class="flex items-center">
                                <span 
                                    v-html="getSortDirectionIcon('asc')" 
                                    class="w-5 h-5 mr-2">
                                </span>
                                <span>Ascendente</span>
                            </div>
                        </el-option>
                        <el-option 
                            label=" " 
                            value="desc"> 
                            <div class="flex items-center">
                                <span 
                                    v-html="getSortDirectionIcon('desc')" 
                                    class="w-5 h-5 mr-2">
                                </span>
                                <span>Descendente</span>
                            </div>
                        </el-option>
                    </el-select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="grid 
                            grid-cols-1           /* Por defecto: 1 columna (móvil) */
                            sm:grid-cols-2        /* En pantallas pequeñas (sm): 2 columnas */
                            lg:grid-cols-3        /* En pantallas grandes (lg): 3 columnas */
                            xl:grid-cols-4        /* En pantallas extra-grandes (xl): 4 columnas */
                            gap-4 px-2 py-4">                    
                    <div v-for="solicitud in solicitudes" :key="solicitud.id" 
                        @click="abreModalEditarSolicitud(solicitud)"
                        class="p-4 border rounded-lg shadow-lg bg-white dark:bg-gray-800 dark:border-gray-700 
                                hover:shadow-xl hover:ring-2 hover:ring-color1-500 transition duration-150 cursor-pointer">
                        
                        <div class="flex items-start justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                Solicitud # {{ solicitud.id ? (solicitud.id % 100000).toString().padStart(5, '0') : '-' }}
                            </span>
                            <span v-if="solicitud.folio && solicitud.aceptada?.activa == 1" class="mt-1">
                                <span class="inline-flex items-center justify-center rounded-lg px-2 py-1 text-sm font-bold bg-color1-50 text-color1-800">
                                FOLIO: {{ (solicitud.folio % 100000).toString().padStart(5, '0') }}
                                </span>
                            </span>
                            </div>

                            <div class="flex items-center space-x-0">
                                <button v-if="solicitud.id_estatus == 6" @click.stop="preguntaAceptarCancelarSolicitud(solicitud.id, solicitud.id_estatus)" 
                                    class="p-1 text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition duration-150 ease-in-out"
                                    title="Quitar cancelación">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="size-6">
                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                        <path d="M9 15l2 2l4 -4" />
                                    </svg>
                                </button>
                                <button v-if="solicitud.id_estatus == 99" @click.stop="preguntaAceptarCancelarSolicitud(solicitud.id, solicitud.id_estatus)" 
                                    class="p-1 text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition duration-150 ease-in-out"
                                    title="Cancelar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" /><path d="M10 12l4 5" /><path d="M10 17l4 -5" />
                                    </svg>
                                </button>
                                <button v-if="solicitud.id_estatus != 6" @click.stop="imprimirSolicitud(solicitud)" 
                                    class="p-1 text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition duration-150 ease-in-out"
                                    title="Imprimir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                    </svg>
                                </button>
                                <button v-if="solicitud.id_estatus == 99 && solicitud.tramites?.some(t => t.id_tramite === 4)" 
                                        @click.stop="abrirTramite(solicitud)" 
                                        class="p-1 w-9 h-9 text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition duration-150 ease-in-out"
                                        :title="todosAsignados(solicitud) ? 'Ver Trámite' : 'Iniciar Trámite'">
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        viewBox="0 0 24 24" 
                                        fill="none"
                                        stroke="currentColor" 
                                        stroke-width="1.33" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M19 8.268a2 2 0 0 1 1 1.732v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-8a2 2 0 0 1 2 -2h3" />
                                        <path d="M5 15.734a2 2 0 0 1 -1 -1.734v-8a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-3" />
                                        
                                        <!-- Reemplazamos el string JS por reactividad pura de Vue -->
                                        <path v-if="!todosAsignados(solicitud)" 
                                            d="M18 3.5h4M20 1.5v4" 
                                            stroke="currentColor" 
                                            stroke-width="1" />                                            
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="pt-1 space-y-2 text-sm">                            
                            <div v-if="solicitud.propiedad" class="border-b dark:border-gray-700 pb-2">
                                <span v-if="solicitud.id_contacto == solicitud.propiedad?.id_contacto" class="font-bold text-gray-700 dark:text-gray-300">Propietario/Solicitante: </span>
                                <p class="text-gray-600 dark:text-gray-400 break-words text-xs">
                                    <template v-if="solicitud.id_contacto == solicitud.propiedad?.id_contacto">
                                        {{ solicitud.propiedad.contacto.persona.nombre + ' ' + solicitud.propiedad.contacto.persona.apellidos }}
                                    </template>
                                    <template v-else-if="solicitud.propiedad">
                                        <span class="block mt-0">
                                            <span class="text-gray-700 font-bold dark:text-gray-300 text-sm block">Propietario:</span>
                                            <span class="text-gray-600 dark:text-gray-400 block -mt-0">
                                                {{ solicitud.propiedad.contacto.persona.nombre + ' ' + solicitud.propiedad.contacto.persona.apellidos }}
                                            </span>
                                        </span>

                                        <span class="block mt-1">
                                            <span class="text-gray-700 font-bold text-sm block">Solicitante:</span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 block -mt-0">
                                                {{ (solicitud.contacto.persona.nombre || '') + ' ' + (solicitud.contacto.persona.apellidos || '') }}
                                            </span>
                                        </span>
                                        <span v-if="solicitud.razon_social" class="block mt-1">
                                            <span class="text-gray-700 font-bold text-sm block">Organización / Razón Social:</span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 block -mt-0">
                                                {{ solicitud.razon_social.nombre }}
                                            </span>
                                        </span>
                                    </template>
                                    <template v-else>
                                        {{ solicitud.contacto.persona.nombre + ' ' + solicitud.contacto.persona.apellidos + (solicitud.razon_social ? ' «' + solicitud.razon_social.nombre + '»' : '') }}
                                    </template>
                                </p>
                            </div>
                            <div v-else class="border-b dark:border-gray-700 pb-2">
                                <span class="font-bold text-gray-700 dark:text-gray-300">Solicitante: </span>
                                <p class="text-gray-600 dark:text-gray-400 break-words text-xs">
                                    {{ (solicitud.contacto.persona.nombre || '') + ' ' + (solicitud.contacto.persona.apellidos || '') }}
                                </p>
                            </div>
                            <div class="flex flex-col space-y-1 pt-1">
                                <div>
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Fecha Ingreso: </span>
                                    <span class="text-gray-600 dark:text-gray-400 text-xs">{{ formatDate(solicitud.fecha_ingreso) }}</span>
                                </div>
                                <div v-if="solicitud.fecha_aceptacion">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Fecha Aceptación: </span>
                                    <span class="text-gray-600 dark:text-gray-400 text-xs">{{ formatDate(solicitud.fecha_aceptacion) }}</span>
                                </div>
                                <div v-if="solicitud.propiedad && solicitud.propiedad.clave_catastral" @mousedown.stop>
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Cve. Catastral: </span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 select-text cursor-text" @click.stop>{{ solicitud.propiedad.clave_catastral }}</span>
                                </div>
                                <div v-if="solicitud.propiedad && solicitud.propiedad.localidad">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Localidad: </span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ solicitud.propiedad.localidad.nombre }}</span>
                                </div>
                                <div v-if="solicitud.referencia?.localidad && solicitud.referencia?.localidad">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Localidad: </span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ solicitud.referencia.localidad.nombre }}</span>
                                </div>
                                <div class="flex items-baseline">
                                    <span class="font-bold text-gray-700 dark:text-gray-300 mr-1">Estatus:</span>
                                    <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400 font-semibold text-xs"
                                        :style="{ color: solicitud.estatus.color }">
                                        {{ solicitud.estatus.nombre }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2">
                                <span v-if="solicitud.tramites.length > 1" class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Trámites: </span>
                                <span v-else class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Trámite: </span>
                                <div v-if="solicitud.tramites && solicitud.tramites.length > 0" class="flex flex-wrap gap-x-2 gap-y-1">
                                      <span 
                                        v-for="sTramites in solicitud.tramites" 
                                        :key="sTramites.id" 
                                        class="text-gray-600 dark:text-gray-400 bg-gray-100  dark:bg-gray-700 px-2 py-0.5 rounded-md text-xs font-medium"
                                        
                                        :style="getBorderStyle(sTramites.tramite.tipo_tramite.id - 1)">
                                        
                                        {{ sTramites.tramite.nombre_abreviado }}
                                    </span>
                                </div>
                                <div v-else class="inline text-color1-500 text-xs">SIN TRÁMITES</div>
                            </div>                            
                        </div>
                    </div>
                </div>
            </div>
            <Pagination :data="pagination" @page-changed="handlePageChange" />
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


