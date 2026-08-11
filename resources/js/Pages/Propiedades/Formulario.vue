<script setup>
  import { ref, onMounted, onUnmounted, watch, computed, nextTick, reactive, onBeforeUnmount, toRaw } from 'vue';
  import { usePage, router } from '@inertiajs/vue3'; // Asumiendo que usas Inertia como en tu package.json
  import axios from 'axios';

  const emit = defineEmits(['save', 'cancel', 'toggle-doc']);
  const seccionEditando = ref(null)
  const enviandoFormulario = ref(false);
  const mensajeCarga = ref('Validando'); // Valor inicial

  const form = reactive({
      id: 0,
      claveCatastral: '',
      tipoPropiedad: null,
      calle: '',
      numero: '',
      idColonia: 0,
      coloniaTexto: '',
      colonia: null,
      idLocalidad: 0,
      localidadTexto: '',
      localidad: null,
      codigoPostal: '',
      referenciasUbicacion: '',
      superficie: 0,
      superficieConstruccion: 0,
      coordenadaUtmX: null,
      coordenadaUtmY: null,
      contacto: null,
      domicilioNotificacion: null,
      propietario: null,
      imgCroquis: '',
      idContacto: null
  })

  const formUbicacion = ref({})
  const formUbicacionOriginal = ref({})
  const formColonia = ref({})
  const formLocalidad = ref({})
  const formTecnica = ref({})
  const formTecnicaOriginal = ref({})
  const formPropietario = ref({})
  const formPropietarioOriginal = ref({})
  const formCroquis = ref(null)
  const formCroquisOriginal = ref(null)
  const isFocusedColonia = ref(false)
  const isFocusedLocalidad = ref(false)
  const isLoadingColonias = ref(false)
  const isLoadingLocalidades = ref(false)
  const suggestionsColonias = ref([])
  const suggestionsLocalidades = ref([])
  const imgCroquis = ref(null)

  // Limpiar timeouts al desmontar
  onUnmounted(() => {
  });
  
  const props = defineProps({
      selectedPropiedad: {
          type: Object,
          default: () => ({})
      },
      isPage: {
          type: Boolean,
          default: false
      },
      destinatarios: Array,
      documentoData: Object,
      todosEstatus: Array,
      tiposPropiedad: Array,
      tramite: Object,
      desdeTramite: Boolean,
      tipoTramite: String,
      campoEspecifico: String,
      tramiteData: {
        type: Object,
        default: () => ({ payload: {} })
    }
  });

    const obtenerColoniaById = async (id) => {
    try {
        // ✅ API REST pura: GET /api/colonias/{id}
        const response = await fetch(`/api/colonias/${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        
        if (!response.ok) {
            throw new Error('Colonia no encontrada')
        }
        
        const result = await response.json()
        return result.data
        
    } catch (error) {
        console.error('Error:', error)
        return null
    }
}

  const obtenerLocalidadById = async (id) => {
    try {
        // ✅ API REST pura: GET /api/localidades/{id}
        const response = await fetch(`/api/localidades/${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })

        if (!response.ok) {
            throw new Error('Localidad no encontrada')
        }
        
        const result = await response.json()
        return result.data
        
    } catch (error) {
        console.error('Error:', error)
        return null
    }
}

  const editableDomicilio = ref(null)
  const cargandoCroquis = ref(true)
  const conCroquisInicial = ref(false)
  const croquisSeleccionado = ref(false)

    const getNestedValue = (obj, path) => {
      return path.split('.').reduce((acc, part) => acc && acc[part], obj);
  };

    const obtenerErrores = (panel, tipoTramite) => {
      const errores = [];
      const requisitos = CONFIG_REQUISITOS[tipoTramite]?.[panel] || [];

      requisitos.forEach(req => {
          const valor = getNestedValue(form, req.campo);
          // Convertimos a string de forma segura
          const valorString = (valor !== null && valor !== undefined) ? String(valor).trim() : '';

          // 1. Validación de Obligatoriedad
          if (valorString === '') {
              errores.push(`El campo ${req.etiqueta} es obligatorio.`);
              return;
          }

          // 2. Validación de "numérico" (incluyendo decimales)
          if (req.tipo === 'numerico') {
              // Regex: números enteros o decimales (punto opcional)
              const esNumeroValido = /^\d+(\.\d+)?$/.test(valorString);
              
              if (!esNumeroValido) {
                  errores.push(`El campo ${req.etiqueta} debe ser un número válido (ej. 123.45).`);
                  return;
              }
          }

          // 3. Validación específica para Código Postal
          if (req.campo === 'codigoPostal' && !/^\d{5}$/.test(valorString)) {
              errores.push('El CÓDIGO POSTAL debe conformarse por 5 dígitos numéricos.');
          }
      });

      return errores;
  };

 

  watch(() => props.tramite, async(tramite) => {
    if (tramite) {
      form.id_tramite = tramite.id
      form.id_solicitud = tramite.id_solicitud
      form.folio_solicitud = tramite.solicitud.folio
      form.tipo_tramite = tramite.tipo_tramite?.nombre
      form.tipo_tramite_slug = tramite.tipo_tramite?.slug
    }
  }, { immediate: true })


const showNoResultsMessageColonias = ref(false)

const buscarColoniasREST = async (queryString, cb) => {

    // ✅ OCULTAR mensaje al iniciar nueva búsqueda
    showNoResultsMessageColonias.value = false

    if (!queryString || queryString.length < 2) {
        cb([])
        return
    }

    isLoadingColonias.value = true

    try {

        const response = await fetch(
            `/api/colonias?q=${encodeURIComponent(queryString)}&limit=20`
        )

        const result = await response.json()

        // ✅ IGNORAR RESPUESTAS VIEJAS
        if (queryString !== form.coloniaTexto) {
            return
        }

        const colonias = result.data || []

        suggestionsColonias.value = colonias

        const sugerencias = colonias.map(colonia => ({
            value: colonia.nombre,
            id: colonia.id,
            nombre: colonia.nombre,
        }))

        cb(sugerencias)

        // ✅ Mostrar mensaje SOLO cuando terminó
        showNoResultsMessageColonias.value =
            queryString === form.coloniaTexto &&
            colonias.length === 0

    } catch (error) {

        if (queryString === form.coloniaTexto) {

            suggestionsColonias.value = []

            showNoResultsMessageColonias.value = true
        }

        cb([])

    } finally {

        if (queryString === form.coloniaTexto) {
            isLoadingColonias.value = false
        }
    }
}

  watch(() => form.coloniaTexto, (nuevoValor) => {
    if (!nuevoValor || nuevoValor.trim() === '') {
      
      suggestionsColonias.value = [];
      showNoResultsMessageColonias.value = false;
      isLoadingColonias.value = false;
      
      form.colonia = null; 
    }
  });

  const labelClassesColonias = computed(() => {
      const hasValue = !!form.coloniaTexto;
      const isFocused = isFocusedColonia.value;
      
      if (isFocused) {
          return '-top-[18px] scale-90 text-color1-700';
      }
      if (hasValue) {
          return '-top-[18px] scale-100 text-gray-600';
      }
      return 'top-1 scale-90 text-gray-500';
  });

  const seleccionarColonia = (item) => {
      form.colonia = item
      form.idColonia = item.id
      form.coloniaTexto = item.nombre
  }

  const showNoResultsMessageLocalidades = ref(false)

  const buscarLocalidadesREST = async (queryString, cb) => {
      showNoResultsMessageLocalidades.value = false

      if (!queryString || queryString.length < 2) {
          isLoadingLocalidades.value = false
          cb([])
          return
      }
      isLoadingLocalidades.value = true
      try {
          const response = await fetch(
              `/api/localidades?q=${encodeURIComponent(queryString)}&limit=20`
          )
          const result = await response.json()
          if (queryString !== form.localidadTexto) {
              return
          }
          const localidades = result.data || []
          suggestionsLocalidades.value = localidades
          const sugerencias = localidades.map(localidad => ({
              value: localidad.nombre,
              id: localidad.id,
              nombre: localidad.nombre,
          }))

          cb(sugerencias)
          showNoResultsMessageLocalidades.value =
              queryString === form.localidadTexto &&
              localidades.length === 0

      } catch (error) {

          console.error(error)

          if (queryString === form.localidadTexto) {
              suggestionsLocalidades.value = []
              showNoResultsMessageLocalidades.value = true
          }

          cb([])

      } finally {

          if (queryString === form.localidadTexto) {
              isLoadingLocalidades.value = false
          }
      }
  }

  watch(() => form.localidadTexto, (nuevoValor) => {
    if (!nuevoValor || nuevoValor.trim() === '') {
      
      suggestionsLocalidades.value = [];
      showNoResultsMessageLocalidades.value = false;
      isLoadingLocalidades.value = false;
      
      form.localidad = null; 
    }
  });


  const labelClassesLocalidades = computed(() => {
      const hasValue = !!form.localidadTexto;
      const isFocused = isFocusedLocalidad.value;
      
      if (isFocused) {
          return '-top-[18px] scale-90 text-color1-700';
      }
      if (hasValue) {
          return '-top-[18px] scale-100 text-gray-600';
      }
      return 'top-1 scale-90 text-gray-500';
  });

    // Seleccionar colonia
  const seleccionarLocalidad = (item) => {
      form.localidad = item
      form.idLocalidad = item.id
      form.localidadTexto = item.nombre
  }

  const isSavingModal = ref(false)
  const isLoadingModal = ref(false)
  const isDocOpen = ref(false); // Inicialmente cerrado
  const documentacion = ref([])
  const registroCreado = ref(false)
  const registroActualizado = ref(false)
  const constanciaImpresa = ref(false)
  const esSoloLectura = computed(() => {
      return form.id_estatus == 99; // Suponiendo que 99 es 'Entregado'
  });

  watch(isDocOpen, (nuevoValor) => {
    emit('toggle-doc', nuevoValor);
});

//CHECKPOINT: Ando dando formato a las validaciones de campos al guardar y ver cuál es el valor del largo mínimo permitido en los campos

  const isLocked = ref(true);
  const showDocs = ref(false);
  const popupRef = ref(null);
  const showGeoData = ref(false);
  const sinDatosInicialesGeo = ref(false);
  
  const closeOnClickOutside = (event) => {
    // Si el popup está abierto Y el clic NO fue dentro del popup ni del botón que lo abre
    if (showDocs.value && popupRef.value && !popupRef.value.contains(event.target)) {
      // Opcional: Verifica si el clic fue en el botón para evitar conflictos de doble toggle
      if (!event.target.closest('button')) {
        showDocs.value = false;
      }
    }
  };

  // 1. Definimos una función que determine los valores iniciales reales
  const obtenerValoresIniciales = () => {
      let docsNormalizados = [];
      // Si viene de una edición (documentoData existe)
      if (props.documentoData) {
          const d = props.documentoData;
          // Normalizamos: d.tramite.documentos trae los modelos RequisitoDocumentacion directamente
          docsNormalizados = (d.tramite?.documentos || []).map(doc => ({
              id_requisito: doc.id,
              nombre: doc.nombre,
              entregado_previamente: doc.pivot?.entregado // Por si necesitas saber el estado original
          }));

          documentacion.value = docsNormalizados;

          sinDatosInicialesGeo.value = false;
          if (!d.propiedad?.coordenada_utm_x || 
              !d.propiedad?.coordenada_utm_y ||
              !d.propiedad?.referencias_ubicacion)
              {
                sinDatosInicialesGeo.value = true;
              }


          return {
              id_constancia: d.id,
              id_tramite: d.id_tramite,
              fecha_emision: d.fecha_emision,
              prefijo_base: d.prefijo_oficio,
              consecutivo: String(d.consecutivo_oficio).padStart(4, '0'),
              fecha_expiracion: d.fecha_expiracion,
              propiedad: d.propiedad,
              clave_catastral: d.propiedad?.clave_catastral || '',
              nombre_destinatario: `${d.propiedad?.contacto?.persona?.nombre || ''} ${d.propiedad?.contacto?.persona?.apellidos || ''}`.trim(),
              tipo_propiedad: d.propiedad?.tipo.nombre || 'UNDEFINED',
              numero_asignado: d.numero_asignado,
              numero_asignado_letra: d.numero_asignado_letra,
              motivo_justificacion: d.tramite?.justificacion?.motivo,
              documentacion_solicitud: d.documentacion_solicitud,
              documentacion_entregada: d.tramite?.documentos
                ?.filter(doc => doc.pivot?.entregado)
                .map(doc => doc.id) || [],
              id_estatus: d.tramite?.id_estatus,
              id_estatus_original: d.tramite?.id_estatus,
              fecha_fin: d.tramite?.fecha_fin,
              codigo_postal: d.propiedad?.codigo_postal,
              referencias_ubicacion: d.propiedad?.referencias_ubicacion,
              coordenada_utm_x: d.propiedad?.coordenada_utm_x,
              coordenada_utm_y: d.propiedad?.coordenada_utm_y,
          };
      }

      docsNormalizados = (props.initialData?.solicitud?.documentos || []).map(doc => ({
          id_requisito: doc.id_requisito_documentacion,
          nombre: doc.requisito_documentacion?.nombre || 'Documento',
      }));

      documentacion.value = docsNormalizados;


      sinDatosInicialesGeo.value = false;
      if (!props.initialData?.propiedad?.coordenada_utm_x || 
          !props.initialData?.propiedad?.coordenada_utm_y ||
          !props.initialData?.propiedad?.referencias_ubicacion)
          {
             sinDatosInicialesGeo.value = true;
          }

      // Si es un trámite nuevo (initialData)
      return {
          consecutivo: '',
          prefijo_base: '', // Usamos el computed aquí
          id_tramite: props.initialData?.id || null,
          fecha_emision: new Date().toISOString().split('T')[0],
          fecha_expiracion: new Date().toISOString().split('T')[0],
          propiedad: props.initialData?.propiedad || null,
          clave_catastral: props.initialData?.propiedad?.clave_catastral || props.initialData?.clave_catastral || '',
          tipo_propiedad: props.initialData?.propiedad?.tipo?.nombre || 'PrediO',
          numero_asignado: '',
          numero_asignado_letra: '',
          motivo_justificacion: null,
          documentacion_solicitud: props.initialData?.solicitud?.documentos || null,
          documentacion_entregada: [],
          coordenada_utm_x: props.initialData?.propiedad?.coordenada_utm_x,
          coordenada_utm_y: props.initialData?.propiedad?.coordenada_utm_y,
          codigo_postal: props.initialData?.propiedad?.codigo_postal,
          referencias_ubicacion: props.initialData?.propiedad?.referencias_ubicacion,
      };
  };

      const prefijoBase = computed(() => {
        // Si no hay fecha en el form, usamos la de hoy como fallback

        const fechaBase = form.fecha_emision ? new Date(form.fecha_emision + 'T00:00:00') : new Date();
        
        // Extraemos los datos de la fecha seleccionada
        const dia = String(fechaBase.getDate()).padStart(2, '0');
        const mes = String(fechaBase.getMonth() + 1).padStart(2, '0');
        const anio = String(fechaBase.getFullYear()).slice(-2);
        
        const tipoId = String(props.initialData?.tipo_tramite?.tipo_tramite?.id || '0').padStart(3, '0');
        
        return `${dia}.${mes}.${anio}/DPU/${tipoId}`;
  });

  watch(prefijoBase, (nuevoValor) => {
    if (esSoloLectura.value) return;
      form.prefijo_base = nuevoValor + '/';
  }, { immediate: true });

  const confirmarEntrega = async() => {
    const faltantes = documentacion.value.filter(d => 
            !form.documentacion_entregada.includes(d.id_requisito)
        );

        const tieneRequisitosPendientes = (form.documentacion?.length > 0) && !!props.documentoData;

        if (tieneRequisitosPendientes && form.documentacion_entregada.length === 0) {
            isDocOpen.value = true
            return Swal.fire({
                icon: 'error',
                title: 'Sin validación de requisitos',
                text: 'Debes validar al menos un requisito de documentación para proceder.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33',
            });
        }

        if (faltantes.length > 0) {
            const listaFaltantes = faltantes.map(f => `<li>${f.nombre}</li>`).join('');
            
            const resultado = await Swal.fire({
                icon: 'warning',
                title: '<span style="line-height: 1.0; display: block;">Documentación faltante de validar </span>',
                html: `
                    <div style="text-align: left; font-size: 10.5pt;">
                        <p>No se han validado todos los requisitos. Faltan los siguientes:</p>
                        <ul style="list-style-type: disc; padding-left: 20px; color: #d33; margin-top: 10px;">
                            ${listaFaltantes}
                        </ul>
                        <p style="margin-top: 15px; font-weight: bold;">¿Deseas marcar como entregado de todas formas?</p>
                    </div>`,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6e7881',
                confirmButtonText: 'Sí, marcar como entregado',
                cancelButtonText: 'No, revisar',
                reverseButtons: true, // Pone el botón de cancelar a la izquierda (mejor UX)
                
            });

            if (!resultado.isConfirmed) 
            {
              isDocOpen.value = true
              return false;
            }
        }
        return true;
    }

      const confirmarDatosConstancia = async () => {
          const faltantes = documentacion.value.filter(d => 
              !form.documentacion_entregada.includes(d.id_requisito)
          );

          const tieneRequisitosPendientes = (form.documentacion?.length > 0) && !!props.documentoData;

          if (tieneRequisitosPendientes && form.documentacion_entregada.length === 0) {
              isDocOpen.value = true
              return Swal.fire({
                  icon: 'error',
                  title: 'Sin validación de requisitos',
                  text: 'Debes validar al menos un requisito de documentación para proceder.',
                  confirmButtonText: 'Entendido',
                  confirmButtonColor: '#d33',
              });
          }

          if (faltantes.length > 0) {
              const listaFaltantes = faltantes.map(f => `<li>${f.nombre}</li>`).join('');
              
              const resultado = await Swal.fire({
                  icon: 'warning',
                  title: '<span style="line-height: 1.0; display: block;">¿Continuar con documentos pendientes?</span>',
                  html: `
                      <div style="text-align: left; font-size: 10.5pt;">
                          <p>No se han validado todos los requisitos. Faltan los siguientes:</p>
                          <ul style="list-style-type: disc; padding-left: 20px; color: #d33; margin-top: 10px;">
                              ${listaFaltantes}
                          </ul>
                          <p style="margin-top: 15px; font-weight: bold;">¿Deseas confirmar los datos del trámite de todos modos?</p>
                      </div>`,
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#6e7881',
                  confirmButtonText: 'Sí, confirmar datos',
                  cancelButtonText: 'No, revisar',
                  reverseButtons: true, // Pone el botón de cancelar a la izquierda (mejor UX)
                  
              });

              if (!resultado.isConfirmed) 
              {
                isDocOpen.value = true
                return;
              }
          }

          // if (!form.coordenada_utm_x || !form.coordenada_utm_y || !form.referencias_ubicacion)
          // {
          //     showGeoData.value = true
          //     return Swal.fire({
          //         icon: 'info',
          //         title: 'Datos faltantes',
          //         text: 'Debes capturar información referente a la ubicación técnica de la propiedad.',
          //         confirmButtonText: 'Entendido',
          //         confirmButtonColor: '#d33',
          //     });
          // }

          // ejecutarEnvio();
      };


  // Vigilamos los cambios en el modelo del select
  watch(() => form.nombre_destinatario, (nuevoNombre) => {
      if (!nuevoNombre) {
          form.caracter_destinatario = '';
          return;
      }

      // Buscamos a la persona en el arreglo de destinatarios
      const personaEncontrada = props.destinatarios.find(d => d.nombre === nuevoNombre);

      if (personaEncontrada) {
          // Aquí hacemos la magia: asignamos el tipo (con el género ya procesado)
          form.caracter_destinatario = personaEncontrada.tipo;
      } else if (nuevoNombre === 'OTRO') {
          // Si elige OTRO, limpiamos para que el usuario escriba manualmente
          form.caracter_destinatario = '';
      }
  });


    onUnmounted(() => {
      document.removeEventListener('mousedown', closeOnClickOutside);
    });

 
      const esManual = ref(false); // Estado del candado
      const valorManual = ref(''); // Para cuando el usuario edite a mano

      const numeroALetrasEspecial = (input) => {
      if (!input) return '';
      const strInput = String(input).trim().toUpperCase();
      const matchNum = strInput.match(/^\d+/);
      if (!matchNum) return strInput;

      const numeroParte = parseInt(matchNum[0]);
      let sufijoParte = strInput.substring(matchNum[0].length).replace(/[-"]/g, '').trim();

      const convertir = (n) => {
          const unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
          const decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
          const especiales = { 
              11: 'ONCE', 12: 'DOCE', 13: 'TRECE', 14: 'CATORCE', 15: 'QUINCE', 
              16: 'DIECISÉIS', 17: 'DIECISIETE', 18: 'DIECIOCHO', 19: 'DIECINUEVE', 
              21: 'VEINTIUNO', 22: 'VEINTIDÓS', 23: 'VEINTITRÉS', 24: 'VEINTICUATRO', 
              25: 'VEINTICINCO', 26: 'VEINTISÉIS', 27: 'VEINTISIETE', 28: 'VEINTIOCHO', 29: 'VEINTINUEVE' 
          };
          const cientos = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

          if (n === 0) return 'CERO';
          if (n >= 1000) {
              const m = Math.floor(n / 1000);
              const resto = n % 1000;
              return (m === 1 ? 'MIL' : convertir(m) + ' MIL') + (resto > 0 ? ' ' + convertir(resto) : '');
          }
          if (n >= 100) {
              if (n === 100) return 'CIEN';
              const c = Math.floor(n / 100);
              return cientos[c] + (n % 100 > 0 ? ' ' + convertir(n % 100) : '');
          }
          if (n >= 10) {
              if (especiales[n]) return especiales[n];
              return decenas[Math.floor(n / 10)] + (n % 10 > 0 ? ' Y ' + unidades[n % 10] : '');
          }
          return unidades[n];
      };

      let resultado = convertir(numeroParte);
      return sufijoParte ? `${resultado} "${sufijoParte}"` : resultado;
  };

  // 3. REACTIVIDAD CRÍTICA: El Watcher
  // Este es el que actualiza el valor automáticamente mientras no esté el candado abierto
  watch(() => form.numero_asignado, (nuevoValor) => {
      if (!esManual.value) {
          form.numero_asignado_letra = numeroALetrasEspecial(nuevoValor);
      }
  });

  // 4. Control del Botón (Toggle)
  const puedeEditarManual = computed(() => {
      return form.numero_asignado && String(form.numero_asignado).trim().length > 0;
  });


  const direccionFormateada = computed(() => {
        const p = form.propiedad;
        if (!p) return "";

        let partes = [];

        // 1. Calle y Número
        let calle = p.calle || "";
        partes.push(calle);

        // 2. Colonia con validación de prefijos
        if (p.colonia?.nombre) {
            const nombreCol = p.colonia.nombre.trim().toUpperCase();
            // Si NO empieza con COL o INF, añadimos el prefijo COL.
            if (!nombreCol.startsWith("COL") && !nombreCol.startsWith("INF")) {
            partes.push(`COL. ${p.colonia.nombre}`);
            } else {
            partes.push(p.colonia.nombre);
            }
        }

        // 2.1 Código Postal
        if (p.codigo_postal) {
            partes.push(`C.P. ${p.codigo_postal}`);
        }


        // 3. Localidad
        if (p.localidad?.nombre) {
            partes.push(p.localidad.nombre);
        }

        // Unimos todo con comas para que se vea ordenado
        return partes.filter(part => part !== "").join(", ");
    });

    const textareaDireccion = ref(null);

    const ajustarAltura = () => {
      const el = textareaDireccion.value;
      if (el) {
        el.style.height = 'auto'; // Resetea para recalcular
        el.style.height = (el.scrollHeight + 2) + 'px';
      }
    };

    // Vigilamos el computed. Cuando cambie la dirección, el textarea crece o encoge.
    watch(() => direccionFormateada.value, async () => {
      await nextTick(); // Esperamos a que Vue actualice el DOM
      ajustarAltura();
    }, { immediate: true });

    onMounted(() => {
      ajustarAltura();
    });

    const isInputFocused = ref(false);

    const inputPrefijo = ref(null);

    const habilitarEdicion = async () => {
      isLocked.value = !isLocked.value;

      // Si acabamos de desbloquear (entrar en modo edición)
      if (!isLocked.value) {
        await nextTick();
        const el = inputPrefijo.value;
        if (el) {
          el.focus();
          // El cursor irá al final del texto actual de form.prefijo_base
          const length = el.value.length;
          el.setSelectionRange(length, length);
        }
      }
    };

    const etiquetasEstatus = {
      2: 'Confirmar Aprobación',
      3: 'Confirmar Suspensión',
      4: 'Confirmar Rechazo',
      5: 'Confirmar Cancelación',
    };

    const handleSubmit = () => {
      // Evaluamos si el documento ya existe
      if (props.documentoData || registroCreado.value) {
        actualizarTramite();
      } else {
        confirmarDatosConstancia();
      }
    };

     const actualizarTramite = async (aImprimir = false) => {
          // 1. Obtener IDs requeridos y faltantes
          const faltantes = documentacion.value.filter(d => 
              !form.documentacion_entregada.includes(d.id_requisito)
          );

          const tieneRequisitosPendientes = (form.documentacion?.length > 0) && !!props.documentoData;

          if (tieneRequisitosPendientes && form.documentacion_entregada.length === 0) {
              return Swal.fire({
                  icon: 'error',
                  title: 'Sin validación de requisitos',
                  text: 'Debes validar al menos un requisito de documentación para proceder.',
                  confirmButtonText: 'Entendido',
                  confirmButtonColor: '#d33',
              });
          }

          // --- UX: CASO 2: FALTAN ALGUNOS (ADVERTENCIA) ---
          if (faltantes.length > 0) {
              const listaFaltantes = faltantes.map(f => `<li>${f.nombre}</li>`).join('');

              const textoConfirmacion = aImprimir 
            ? 'Sí, imprimir constancia' 
            : 'Sí, actualizar trámite';

            const textoPregunta = aImprimir 
            ? '¿Deseas imprimir la constancia de todos modos?' 
            : '¿Deseas actualizar la información del trámite de todos modos?';
              
              const resultado = await Swal.fire({
                  icon: 'warning',
                  title: '<span style="line-height: 1.0; display: block;">¿Continuar con documentos pendientes?</span>',
                  html: `
                      <div style="text-align: left; font-size: 10.5pt;">
                          <p>No se han validado todos los requisitos. Faltan los siguientes:</p>
                          <ul style="list-style-type: disc; padding-left: 20px; color: #d33; margin-top: 10px;">
                              ${listaFaltantes}
                          </ul>
                          <p style="margin-top: 15px; font-weight: bold;">${textoPregunta}</p>
                      </div>`,
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#6e7881',
                  confirmButtonText: textoConfirmacion,
                  cancelButtonText: 'No, revisar',
                  reverseButtons: true // Pone el botón de cancelar a la izquierda (mejor UX)
              });

              // Si el usuario cancela o cierra el modal, detenemos la función
              if (!resultado.isConfirmed) return false;
          }

          // --- PROCESO DE ENVÍO FINAL ---
          const exitoEnEnvio = await enviarDatosTramite(aImprimir);

          // Solo devolvemos true si el envío fue realmente exitoso
          return exitoEnEnvio;
      };

    

    const enviarDatosTramite = (aImprimir = false) => {
      return new Promise((resolve) => {
      try {
        if (![3, 4, 5].includes(form.id_estatus)) {
            form.motivo_justificacion = null;
        }

        form.prefijo_base = prefijoBase.value + '/';
          router.post('/constancias-numero-oficial/update', form, {
              onSuccess: (page) => {
                if (!aImprimir){
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
                registroActualizado.value = false
                if (form.id_estatus == 2)
                {
                   if (!aImprimir) registroActualizado.value = true
                   form.id_estatus_original = form.id_estatus
                }
                else
                { 
                  emit('save')
                }
                resolve(true)
              },
              onStart: () => {
                  if (aImprimir) isLoadingModal.value = true;
                  else isSavingModal.value = true;
              },
              onFinish: () => {
                  if (aImprimir) isLoadingModal.value = false;
                  else isSavingModal.value = false;

                  resolve(false);
              },
              onError: async (errors) => {
                const esErrorDeRed = !errors || Object.keys(errors).length === 0;

                if (esErrorDeRed) {
                    return Swal.fire({
                        icon: 'warning',
                        title: 'Conexión interrumpida',
                        text: 'No se pudo establecer contacto con el servidor. Verifica tu conexión a internet e intenta de nuevo.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6',
                    });
                }

                const hasValidationErrors = errors && Object.keys(errors).length > 0;
                const errorMessage = hasValidationErrors 
                  ? Object.values(errors).map(err => `<li style="margin-bottom: 7px;">${err}</li>`).join('') 
                  : '<li>Hubo un error al guardar la información.</li>';

                isSavingModal.value = false

               Swal.fire({
                    icon: hasValidationErrors ? 'warning' : 'error',
                    title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                    html: `
                        <div style="text-align: left; font-size: 12pt;">
                            <ul style="list-style-type: disc !important; padding-left: 25px !important; margin-top: 10px;">
                                ${errorMessage}
                            </ul>
                        </div>`,
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
                resolve(false);
            }
          });
      } catch (err) {
          console.error('Error inesperado al procesar:', err);
      }
    });
  };

  const obtenerEtiqueta = (status) => {
      // Si no es el estatus seleccionado por el usuario, mostrar label simple
      if (form.id_estatus !== status.id) {
        return status.label;
      }

      // Si está seleccionado y es el original
      if (form.id_estatus === form.id_estatus_original) {
        return status.activeLabelActual || status.activeLabel;
      }

      // Se valida así porque cuando el estatus es 2 no se cierra la ventana al guardar
      if (form.id_estatus == 2 && registroCreado.value)
      {
        return status.activeLabelActual
      }

      // Si está seleccionado y es una nueva acción
      return status.activeLabel;
    };

    const formatearClaveVisual = (clave) => {
        if (!clave) return []

        return clave.match(/.{1,3}/g) || []
    }

    // Variable para controlar si mostramos la lista de opciones o solo el seleccionado
    const editandoTipo = ref(false);

    // Obtenemos el objeto del tipo seleccionado actualmente para mostrar su nombre
    const tipoSeleccionado = computed(() => {
        return props.tiposPropiedad.find(t => t.id === form.tipoPropiedad?.id) || null;
    });

    // Función para seleccionar y cerrar automáticamente
    const seleccionarTipo = (id) => {
        form.tipoPropiedad.id = id;
        editandoTipo.value = false;
    };

    // Estado del flip
    const isFlipped = ref(false)

    // Métodos
    const toggleFlip = () => {
        isFlipped.value = !isFlipped.value
    }

     const formatearDireccionObjetos = (p) => {
        if (!p) return { principal: 'Sin datos', localidad: '' };

        try {
            // Bloque 1: Calle, Número, Col, CP
            const partesCalle = [];
            if (p.calle?.trim()) partesCalle.push(p.calle.trim());
            
            // 2. Manejo de Número (Lógica solicitada)
            const numRaw = p.numero?.toString().trim().toLowerCase() || '';
            
            // Definimos qué valores consideramos como "sin número"
            const esNumeroInvalido = 
                numRaw === '' || 
                numRaw === '0' || 
                numRaw === 's/n' || 
                numRaw.replace(/\s/g, '') === 's/n'; // maneja 's / n'

            if (!esNumeroInvalido && props.tipoTramite != 'constancia-de-numero-oficial') {
                partesCalle.push(`N° ${p.numero.trim()}`);
            }
            
           if (p.colonia?.nombre) {
                const nom = p.colonia.nombre.trim();
                const upper = nom.toUpperCase();
                const palabrasExcluidas = ["COL", "COLONIA", "COL.",
                "INFONAVIT", "INF.", "INFO.", "INFO", "INF", 
                "FRACCIONAMIENTO", "FRACC", "FRACC.", "FRAC", "FRAC."];
                
                const debeExcluir = palabrasExcluidas.some(palabra => upper.startsWith(palabra));
                
                partesCalle.push(!debeExcluir ? `COL. ${nom}` : nom);
            }
            if (p.codigoPostal) partesCalle.push(`C.P. ${p.codigoPostal}`);

            // Bloque 2: Localidad
            const localidad = p.localidad?.nombre ? p.localidad.nombre.trim() : '';

            return {
                principal: partesCalle.join(', ') || '—',
                localidad: localidad
            };
        } catch (e) {
            return { principal: 'Error en formato', localidad: '' };
        }
    };

    const dragging = ref(false)
    const previewCroquis = ref(null)
    const archivoCroquis = ref(null)
    const abrirZoomCroquis = ref(false)
    const previewTemporalCroquis = ref(null)
    const archivoTemporalCroquis = ref(null)
    const desdePanel = ref(false)

    const onCroquisSelected = (e) => {
    const file = e.target.files[0]

    if (!file) return

    archivoTemporalCroquis.value = file
    previewTemporalCroquis.value = URL.createObjectURL(file)

    descartarCroquisConfirmado.value = false
  }

const eliminarPreviewCroquis = () => {

    if (previewCroquis.value) {
        URL.revokeObjectURL(previewCroquis.value)
    }

    archivoCroquis.value = null

    previewCroquis.value = null

    abrirZoomCroquis.value = false

    // limpiar input file
    if (inputCroquis.value) {
        inputCroquis.value.value = null
    }

    cambiosCroquis.value = hayCambiosCroquis()
}

const inputCroquis = ref(null)

onBeforeUnmount(() => {

    if (previewCroquis.value) {
        URL.revokeObjectURL(previewCroquis.value)
    }
})

const zoomPreviewCroquis = ref(1)
watch(previewTemporalCroquis, (value) => {
  if (value) {
    zoomPreviewCroquis.value = 1
  }
})

const confirmarCroquisTemporal = () => {
  archivoCroquis.value = archivoTemporalCroquis.value
  previewCroquis.value = previewTemporalCroquis.value
  croquisSeleccionado.value = true

  archivoTemporalCroquis.value = null
  previewTemporalCroquis.value = null

  zoomPreviewCroquis.value = 1
}

const cancelarCroquisTemporal = () => {

  // Liberar memoria del object URL
  if (previewTemporalCroquis.value) {
    URL.revokeObjectURL(previewTemporalCroquis.value)
  }

  // Limpiar temporales
  archivoTemporalCroquis.value = null
  previewTemporalCroquis.value = null

  // Reset zoom
  zoomPreviewCroquis.value = 1

  // Limpiar input file
  if (inputCroquis.value) {
    inputCroquis.value.value = null
  }
}

  const ajustarZoom = (factor) => {
      if (factor === 1) zoomPreviewCroquis.value = 1; // Reset
      else zoomPreviewCroquis.value = Math.max(1, Math.min(6, zoomPreviewCroquis.value + factor)); // Límites entre 1x y 4x
  };

  const onDropCroquis = (event) => {

      dragging.value = false

      const files = event.dataTransfer.files

      if (!files || !files.length) return

      const file = files[0]

      if (!file.type.startsWith('image/')) {
          return
      }

      archivoTemporalCroquis.value = file
      previewTemporalCroquis.value = URL.createObjectURL(file)

      descartarCroquisConfirmado.value = false
  }

  const eliminarCroquisConfirmado = ref(false)
  const descartarCroquisConfirmado = ref(false)

  const confirmarEliminarCroquis = async (confirmar = false) => {

      // Si no requiere confirmación
      if (!confirmar) {

          eliminarPreviewCroquis()

          descartarCroquisConfirmado.value = true
          form.imgCroquis = null
          previewCroquis.value = null

          cambiosCroquis.value = !conCroquisInicial.value

          await Swal.fire({
              title: 'Croquis descartado',
              text: 'Ahora puedes seleccionar un nuevo archivo.',
              icon: 'success',
              timer: 1800,
              showConfirmButton: false,
              customClass: {
                  popup: 'rounded-[28px]'
              }
          })

          return
      }

      const result = await Swal.fire({
        title: '¿Eliminar croquis?',
        text: 'La propiedad dejará de contar con un croquis registrado.',
        icon: 'warning',

        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',

        reverseButtons: true,
        focusCancel: true,

        customClass: {
            popup: 'rounded-[28px]',
            confirmButton: `
                bg-color1-600 hover:bg-color1-700
                text-white font-bold
                px-6 py-3 rounded-full
                mx-2 transition-all duration-200
            `,
            cancelButton: `
                bg-gray-200 hover:bg-gray-300
                text-gray-700 font-semibold
                px-6 py-3 rounded-full
                mx-2 transition-all duration-200
            `
        },

        buttonsStyling: false
    })

    if (!result.isConfirmed) return

    eliminarCroquisConfirmado.value = true
    eliminarPreviewCroquis()

    form.imgCroquis = null
    previewCroquis.value = null

    await Swal.fire({
        title: 'Croquis eliminado',
        text: 'La eliminación definitiva se aplicará cuando guardes los cambios.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false,

        customClass: {
            popup: 'rounded-[28px]'
        }
    })

  }

  const croquisRestaurado = ref(false)

  const restaurarCroquisOriginal = () => { 
      
       previewCroquis.value = `/storage/croquis/${imgCroquis.value}`;

       if (inputCroquis.value) {
            inputCroquis.value.value = ''
        }

        dragging.value = false

        croquisRestaurado.value = true
        eliminarCroquisConfirmado.value = false
        form.imgCroquis = imgCroquis.value
        cambiosCroquis.value = false
    }

  

    const cambiosUbicacion = ref(false)

    const abrirEditarUbicacion = () => 
    {
      seccionEditando.value = 'ubicacion'
      
      formUbicacion.value = {
          calle: form.calle,
          numero: form.numero,
          idColonia: form.idColonia,
          coloniaTexto: form.coloniaTexto,
          idLocalidad: form.idLocalidad,
          localidadTexto: form.localidadTexto,
          codigoPostal: form.codigoPostal,
          referenciasUbicacion: form.referenciasUbicacion
      }

      formColonia.value = {
        id: form.colonia.id,
        nombre: form.colonia.nombre
      }

      formLocalidad.value = {
        id: form.localidad.id,
        nombre: form.localidad.nombre
      }
    }

    // const hayCambiosUbicacion = () => { 
    //     return [
    //         'tipoPropiedad.id',
    //         'calle',
    //         'numero',
    //         'idColonia',
    //         'idLocalidad',
    //         'codigoPostal',
    //         'referenciasUbicacion'
    //     ].some(campo =>
    //     String(form[campo] ?? '').trim() !==
    //     String(formUbicacionOriginal.value[campo] ?? '').trim())
    // }

    const hayCambiosUbicacion = () => {
      const campos = ['tipoPropiedad.id', 'calle', 'numero', 'idColonia', 'idLocalidad', 'codigoPostal', 'referenciasUbicacion'];
      
      // Función auxiliar para obtener valores de objetos anidados (ej: "a.b.c")
      const getValue = (obj, path) => {
          return path.split('.').reduce((acc, part) => acc && acc[part], obj);
      };

      return campos.some(campo => {
          // Usamos la función auxiliar para obtener el valor real
          const valorActual = String(getValue(form, campo) ?? '').trim();
          const valorOriginal = String(getValue(formUbicacionOriginal.value, campo) ?? '').trim();
          
          if (valorActual !== valorOriginal) {
              return true;
          }
          return false;
      });
  };

  const aceptarEditarUbicacion = () => {
      // Obtenemos el tipo de trámite (asegúrate de tenerlo accesible)
      const tipoTramite = props.tipoTramite; 
      const errores = obtenerErrores('Ubicación', tipoTramite);

      if (errores.length > 0) {
          const esUnicoError = errores.length === 1;
          
          Swal.fire({
              title: esUnicoError ? 'Atención: Dato incompleto' : 'Atención: Datos inválidos',
              icon: 'warning',
              html: `
                  <ul style="text-align: left; list-style-type: disc; padding-left: 20px; line-height: 1.2;">
                      ${errores.map(err => `<li style="margin-bottom: 5px;">${err}</li>`).join('')}
                  </ul>
              `,
              confirmButtonText: 'Entendido',
              confirmButtonColor: '#9d023b'
          });
          return;
      }

      seccionEditando.value = null;
      cambiosUbicacion.value = hayCambiosUbicacion();
  };  
  
  const cancelarEditarUbicacion = () => 
    {
      Object.assign(form, formUbicacion.value); 

      form.colonia.id = formColonia.value.id
      form.colonia.nombre = formColonia.value.nombre
      form.localidad.id = formLocalidad.value.id
      form.localidad.nombre = formLocalidad.value.nombre

      seccionEditando.value = null
    }
    
    const cambiosTecnica = ref(false)

    const abrirEditarTecnica = () => 
    {
      seccionEditando.value = 'tecnica'
      
      formTecnica.value = {
          superficie: form.superficie,
          superficieConstruccion: form.superficieConstruccion,
          coordenadaUtmX: form.coordenadaUtmX,
          coordenadaUtmY: form.coordenadaUtmY,
      }
    }

    const hayCambiosTecnica = () => {
        return [
            'superficie',
            'superficieConstruccion',
            'coordenadaUtmX',
            'coordenadaUtmY'
        ].some(campo => form[campo] !== formTecnicaOriginal.value[campo])
    }

    const aceptarEditarTecnica = () => {
      const tipoTramite = props.tipoTramite; 
      const errores = obtenerErrores('Información Técnica', tipoTramite);

      if (errores.length > 0) {
          const esUnicoError = errores.length === 1;
          
          Swal.fire({
              title: esUnicoError ? 'Atención: Dato incompleto' : 'Atención: Datos inválidos',
              icon: 'warning',
              html: `
                  <ul style="text-align: left; list-style-type: disc; padding-left: 20px; line-height: 1.2;">
                      ${errores.map(err => `<li style="margin-bottom: 5px;">${err}</li>`).join('')}
                  </ul>
              `,
              confirmButtonText: 'Entendido',
              confirmButtonColor: '#9d023b'
          });
          return;
      } 

      // 3. Si no hay errores, procedemos con el guardado
      seccionEditando.value = null;
      cambiosTecnica.value = hayCambiosTecnica();
  };

    const cancelarEditarTecnica = () => 
    {
      Object.assign(form, formTecnica.value); 
      seccionEditando.value = null
    }

    const cambiosPropietario = ref(false)

    const abrirEditarPropietario = () => 
    {
      seccionEditando.value = 'propietario'
      
      formPropietario.value.nombre = form.propietario.nombre;
      formPropietario.value.apellidos = form.propietario.apellidos;
      formPropietario.value.email = form.contacto.email;
      formPropietario.value.telefono = form.contacto.telefono;
      formPropietario.value.domicilioNotificacion = form.domicilioNotificacion;
    }

    const hayCambiosPropietario = () => {
          return (
              JSON.stringify(form.propietario) !== JSON.stringify(formPropietarioOriginal.value.propietario)
              ||
              JSON.stringify(form.contacto) !== JSON.stringify(formPropietarioOriginal.value.contacto)
              ||
              String(form.domicilioNotificacion ?? '').trim() !==
              String(formPropietarioOriginal.value.domicilioNotificacion ?? '').trim()
          )
      }

    const aceptarEditarPropietario = () => 
    {
      const tipoTramite = props.tipoTramite; 
      const errores = obtenerErrores('Propietario', tipoTramite);

      if (errores.length > 0) {
          const esUnicoError = errores.length === 1;
          
          Swal.fire({
              title: esUnicoError ? 'Atención: Dato incompleto' : 'Atención: Datos inválidos',
              icon: 'warning',
              html: `
                  <ul style="text-align: left; list-style-type: disc; padding-left: 20px; line-height: 1.2;">
                      ${errores.map(err => `<li style="margin-bottom: 5px;">${err}</li>`).join('')}
                  </ul>
              `,
              confirmButtonText: 'Entendido',
              confirmButtonColor: '#9d023b'
          });
          return;
      }

      seccionEditando.value = null
      cambiosPropietario.value = hayCambiosPropietario()
    }

    const cancelarEditarPropietario = () => 
    {
      form.propietario.nombre = formPropietario.value.nombre;
      form.propietario.apellidos = formPropietario.value.apellidos;
      form.contacto.email = formPropietario.value.email;
      form.contacto.telefono = formPropietario.value.telefono;
      form.domicilioNotificacion = formPropietario.value.domicilioNotificacion;

      seccionEditando.value = null
    }

    const cambiosCroquis = ref(false)

    const abrirEditarCroquis = () => 
    {
      seccionEditando.value = 'croquis'
      if (croquisRestaurado.value)
      {
        form.imgCroquis = imgCroquis.value
        previewCroquis.value = null; 
      }
      croquisRestaurado.value = false;
      descartarCroquisConfirmado.value = false;
      eliminarCroquisConfirmado.value = false
      archivoCroquis.value = null    
      formCroquis.value = form.imgCroquis
    }

    const hayCambiosCroquis = () => {
        return (
            form.imgCroquis != formCroquis.value
        )
    }

    const aceptarEditarCroquis = () => 
    {
      seccionEditando.value = null
      eliminarCroquisConfirmado.value = false
      if (previewCroquis)
      {
         form.imgCroquis = imgCroquis.value
      }

      if (!croquisRestaurado.value)
      {
        cambiosCroquis.value = true
      }
    }

    const cancelarEditarCroquis = () => 
    {
      form.imgCroquis = formCroquis.value; 
      seccionEditando.value = null
    }

    const verCroquis = () => 
    {
      abrirZoomCroquis.value = true; 
      zoomPreviewCroquis.value = 1; 
      if (!previewCroquis.value)
      {
        previewCroquis.value = `/storage/croquis/${form.imgCroquis}`;
      }
      desdePanel.value = true
    }

    // Alerta para Descartar Cambios
const confirmarDescarte = () => {
    Swal.fire({
        title: '¿Descartar cambios pendientes?',
        text: 'Se perderán todas las modificaciones realizadas en la propiedad y regresarás al trámite.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // Rojo para acción destructiva
        cancelButtonColor: '#6b7280', // Gris neutral
        confirmButtonText: 'Sí, descartar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-3xl',
        }
    }).then((result) => {
        if (result.isConfirmed) {
             router.get(`/tramites/edit/${form.tipo_tramite_slug}`, {
                id_tramite: form.id_tramite,
                desde_propiedad: true,  //acá voy, es una bandera indicando que se llamó la edición desde un trámite
            });
        }
    });
};

const confirmarGuardado = () => {
    // 🚀 1. VALIDACIÓN CLIENTE: Si hay datos incompletos en el Front, bloqueamos el guardado
    if (hayDatosIncompletos.value) {
        // Construimos una lista HTML visualmente atractiva con los campos faltantes por panel
        let listaErroresHtml = '<div class="text-left mt-3 text-sm flex flex-col gap-2.5">';
        
        Object.entries(bloquesFaltantes.value).forEach(([nombrePanel, campos]) => {
            listaErroresHtml += `
                <div class="bg-red-50 border-l-4 border-color1-500 p-2.5 rounded-r-xl">
                    <span class="text-[11px] font-bold text-color1-800 uppercase tracking-wider block mb-1">
                        ${nombrePanel}
                    </span>
                    <div class="flex flex-wrap gap-1">
                        ${campos.map(item => `
                            <span class="px-2 py-0.5 bg-white text-color1-700 rounded-md font-semibold text-[11px] uppercase border border-color1-200 shadow-3xs">
                                ${item.etiqueta}
                            </span>
                        `).join('')}
                    </div>
                </div>
            `;
        });
        
        listaErroresHtml += '</div>';

        // Disparamos la alerta de bloqueo local
        Swal.fire({
            icon: 'warning',
            title: '<span class="text-3xl font-black text-gray-800 block mb-1">Información Incompleta</span>',
            html: `
                <p class="text-gray-600 text-[15px] text-justify leading-snug">
                    No es posible guardar las modificaciones porque el formulario aún no cumple con los requisitos obligatorios del trámite actual. Por favor, captura lo siguiente:
                </p>
                ${listaErroresHtml}
            `,
            confirmButtonText: 'Entendido, ir a corregir',
            customClass: {
                popup: 'rounded-3xl p-6',
                confirmButton: 'rounded-full px-6 py-2.5 font-semibold bg-amber-500 hover:bg-amber-600 text-white border-0'
            }
        });

        return; // Detiene la ejecución por completo
    }

    // 🚀 2. Si pasó la validación local, mandamos a validar al Servidor (Petición 1: confirmado = false)
    guardarPropiedad(false);
};


  const getNombrePanelPropietario = () => {
      // Verificamos si existe el dato, sino default a 'Propietario'
      const genero = form.propietario?.curp?.charAt(10);
      return genero === 'M' ? 'Propietaria' : 'Propietario';
  };

  const guardarPropiedad = (confirmado = false) => {
    const propiedadData = {
        id_tipo: form.tipoPropiedad.id,
        clave_catastral: form.claveCatastral,
        calle: form.calle,
        numero: form.numero,
        id_colonia: form.idColonia,
        id_localidad: form.idLocalidad,
        codigo_postal: form.codigoPostal,
        referencias_ubicacion: form.referenciasUbicacion,
        superficie: form.superficie,
        superficie_construccion: form.superficieConstruccion,
        img_croquis: form.imgCroquis,
        coordenada_utm_x: form.coordenadaUtmX,
        coordenada_utm_y: form.coordenadaUtmY,
        id_contacto: form.idContacto,
        id_persona: form.propietario.id,
        curp_propietario: form.propietario.curp,
        nombre_propietario: form.propietario.nombre,
        apellidos_propietario: form.propietario.apellidos,
        email_propietario: form.contacto.email,
        telefono_propietario: form.contacto.telefono,
        domicilio_notificacion: form.domicilioNotificacion,
        id_tramite: form.id_tramite,
        desde_propiedad: props.desdeTramite,
        
        // Flag clave que procesará Laravel para decidir si frena o escribe en BD
        confirmado: confirmado 
    };

      mensajeCarga.value = confirmado 
        ? 'Guardando' 
        : 'Validando';

    enviandoFormulario.value = true; // Activa tu overlay de carga

    axios.post('/propiedades/store', propiedadData)
        .then((response) => {
            // 💡 CASO A: Los datos pasaron las validaciones de Laravel, pero requiere confirmación de historial
            if (response.data.requiere_confirmacion) {
                enviandoFormulario.value = false; // Apagamos temporalmente el overlay para mostrar el modal

                Swal.fire({
                    title: '<span class="text-3xl font-black text-gray-800 block mb-1">¿Guardar modificaciones?</span>',
                    html: `
                        <div class="text-justify mt-2">
                            <p class="text-lg text-gray-600 leading-tight mb-4">
                                Los cambios se aplicarán al expediente de la propiedad y volverás al trámite en curso.
                            </p>

                            <div class="bg-color1-40 border-l-4 border-color1-500 p-3 rounded-r-xl mb-3">
                                <div class="flex gap-2.5">
                                    <svg class="w-5 h-5 text-color1-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="pr-2">
                                        <p class="text-xs text-color1-700 font-bold uppercase tracking-wider mb-0.5">Control de Historial</p>
                                        <p class="text-[12px] text-color1-800 leading-tight">
                                            Este registro pasará a ser la <span class="font-bold">propiedad activa</span>. Las versiones anteriores en el catálogo histórico se marcarán como inactivas de forma automática.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded-r-xl">
                                <div class="flex gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <div class="pr-2">
                                        <p class="text-xs text-green-800 font-bold uppercase tracking-wider mb-0.5">Integridad de Expedientes</p>
                                        <p class="text-[12px] text-green-900 leading-tight">
                                            <span class="font-bold">No se afectarán</span> solicitudes ni trámites anteriores finalizados. Los cambios impactan exclusivamente al <span class="font-bold text-green-950">trámite actual</span>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Revisar todavía',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-3xl p-6',
                    },
                    didOpen: () => {
                        const swalContainer = document.querySelector('.swal2-container');
                        if (swalContainer) swalContainer.style.setProperty('z-index', '99999', 'important');
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reejecutamos enviando TRUE de manera directa (Petición 2: Persistencia)
                        guardarPropiedad(true);
                    }
                });

                return; // Frenamos la resolución para esperar la decisión del usuario
            }

            router.visit(route('tramites.edit', {
                tipo_tramite: form.tipo_tramite_slug,
                id_tramite: form.id_tramite,
                desde_propiedad: true,
                mostrar_exito: '1'
            }), {
                method: 'get'
            });

            seccionEditando.value = null; // Cierra sección de edición
            
            if (response.data.ubicacion) {
                form.domicilioNotificacion = response.data.ubicacion.domicilio_notificacion;
            }
        })
        .catch((error) => {
            // Evaluamos los errores de validación estructurados del backend (422)
            if (error.response && error.response.status === 422) {
                const erroresBackend = error.response.data;

                const nombrePanelPropietario = getNombrePanelPropietario();
                
                const mapeoPaneles = {
                    'id_tipo': 'Ubicación', 'calle': 'Ubicación', 'numero': 'Ubicación', 'id_colonia': 'Ubicación', 'id_localidad': 'Ubicación', 'codigo_postal': 'Ubicación', 'clave_catastral': 'Ubicación',
                    'superficie': 'Información técnica', 'superficie_construccion': 'Información técnica', 'coordenada_utm_x': 'Información técnica', 'coordenada_utm_y': 'Información técnica',
                    'curp_propietario': nombrePanelPropietario, 
                    'nombre_propietario': nombrePanelPropietario, 
                    'apellidos_propietario': nombrePanelPropietario, 
                    'email_propietario': nombrePanelPropietario, 
                    'telefono_propietario': nombrePanelPropietario, 
                    'domicilio_notificacion': nombrePanelPropietario,
                    'img_croquis': 'Croquis'
                };


                const panelesConErrores = {};

                Object.keys(erroresBackend).forEach((campo) => {
                    const nombrePanel = mapeoPaneles[campo] || 'General';
                    if (!panelesConErrores[nombrePanel]) {
                        panelesConErrores[nombrePanel] = [];
                    }
                    panelesConErrores[nombrePanel].push(erroresBackend[campo][0] || erroresBackend[campo]);
                });

                const totalCamposConError = Object.keys(erroresBackend).length;
                const esUnSoloError = totalCamposConError === 1;

                const tituloModal = esUnSoloError 
                    ? '<span style="font-size: 1.1em; font-weight: 600; color: #1f2937; line-height: 1.1; display: block;">Revisa el campo requerido</span>'
                    : '<span style="font-size: 1.1em; font-weight: 600; color: #1f2937;">Revisa la información</span>';

                const textoIntro = esUnSoloError
                    ? 'Se requiere corregir un campo en el formulario antes de continuar:'
                    : 'Se requiere la corrección de algunos campos distribuidos en los paneles del formulario:';

                let htmlErrores = '';

               Object.keys(panelesConErrores).forEach((panel) => {
                    htmlErrores += `
                        <div style="margin-top: 18px; text-align: left;">
                          <span style="display: inline-block; font-size: 0.70em; font-weight: 700; color: #4b5563; border: 1px solid #d1d5db; padding: 2px 10px; border-radius: 999px; text-transform: uppercase;">
                              Panel: ${panel}
                          </span>
                          <div style="margin-top: 10px; background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 10px 14px; border-radius: 6px;">
                              <ul style="margin: 0; padding-left: 20px; list-style-type: disc; list-style-position: outside; color: #991b1b; font-size: 0.88em; line-height: 1.2;">
                    `;

                    panelesConErrores[panel].forEach((mensaje) => {
                        // Reducción del margen inferior para compactar aún más la lista
                        htmlErrores += `<li style="margin-bottom: 4px; text-align: justify;">${mensaje}</li>`;
                    });

                    htmlErrores += `</ul></div></div>`;
                });

                Swal.fire({
                    icon: 'error',
                    title: tituloModal,
                    html: `
                        <div style="color: #4b5563; font-size: 0.95em; text-align: justify; margin-bottom: 5px;">${textoIntro}</div>
                        <div style="max-height: 50vh; overflow-y: auto; scrollbar-width: thin; padding-right: 5px;">
                            ${htmlErrores}
                        </div>
                    `,
                    confirmButtonText: 'Entendido, corregir',
                    confirmButtonColor: '#3b82f6',
                    background: '#ffffff',
                    customClass: { popup: 'custom-saas-popup', confirmButton: 'custom-saas-button' },
                    padding: '2rem',
                    didOpen: () => {
                        const swalContainer = document.querySelector('.swal2-container');
                        if (swalContainer) swalContainer.style.setProperty('z-index', '99999', 'important');
                    }
                });
            } else {
                // Fallas generales de servidor (500)
               const mensajeError = (error.response && error.response.data && error.response.data.message) 
        ? error.response.data.message 
        : (error.message || 'Error desconocido al conectar con el servidor.');

    // 2. Usamos ese mensaje en el Swal
    Swal.fire({
        toast: true,
        icon: 'error',
        position: 'top-end',
        showConfirmButton: false,
        title: 'Error de conexión',
        text: mensajeError, // <--- Aquí mostramos el error real
        timer: 5000,        // Un poco más de tiempo para que alcancen a leer
        timerProgressBar: true,
        background: '#ffffff',
        color: '#1f2937',
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) swalContainer.style.setProperty('z-index', '99999', 'important');
        }
    });
            }
        })
        .finally(() => {
            // El overlay se apaga únicamente si el ciclo asíncrono se completó
            // (Ya sea que falló la petición o se guardó exitosamente en la segunda vuelta)
            if (seccionEditando.value === null || !enviandoFormulario.value) {
                enviandoFormulario.value = false;
            }
        });
    };

  const volverAlTramite = () => {
      Swal.fire({
          title: 'Regresar al expediente',
          text: 'El sistema te llevará al trámite original. No hubo cambios en la propiedad.',
          icon: 'info',
          confirmButtonColor: '#0f766e', // Tu color1-600
          confirmButtonText: 'Entendido',
          customClass: {
              popup: 'rounded-3xl',
              confirmButton: 'rounded-full px-6 py-2 font-semibold'
          }
      }).then((result) => {
          if (result.isConfirmed) {
            //   router.post(`/tramites/edit/${form.tipo_tramite_slug}`, {
            //     id_tramite: form.id_tramite,
            //     desde_propiedad: true,  //acá voy, es una bandera indicando que se llamó la edición desde un trámite
            // });
            router.get(`/tramites/edit/${form.tipo_tramite_slug}`, {
                id_tramite: form.id_tramite,
                desde_propiedad: true,  //acá voy, es una bandera indicando que se llamó la edición desde un trámite
            });
          }
      });
  };

  const CONFIG_REQUISITOS = {
      'constancia-de-numero-oficial': {
          'Ubicación': [
              { campo: 'calle', etiqueta: 'VIALIDAD' },
              { campo: 'localidad', etiqueta: 'LOCALIDAD' },
              { campo: 'codigoPostal', etiqueta: 'CÓDIGO POSTAL' },
              { campo: 'referenciasUbicacion', etiqueta: 'REFERENCIAS' }
          ],
          'Información Técnica': [
              { campo: 'coordenadaUtmX', etiqueta: 'COORD. UTM X', tipo: 'numerico' },
              { campo: 'coordenadaUtmY', etiqueta: 'COORD. UTM Y', tipo: 'numerico'  }
          ],
          'Propietario': [
              { campo: 'propietario.nombre', etiqueta: 'NOMBRE' },
              { campo: 'propietario.apellidos', etiqueta: 'APELLIDOS' },
          ],
      },
      'uso-de-suelo': {
          'Información Técnica': [
              { campo: 'superficie', etiqueta: 'Superficie' },
              { campo: 'uso_propuesto', etiqueta: 'Uso Propuesto' }
          ]
      }
  };

  const CONFIG_EDITABLES = {
      'constancia-de-numero-oficial': [
          'tipoPropiedad',
          'calle',
          'localidad',
          'codigoPostal',
          'referenciasUbicacion',
          'coordenadaUtmX',
          'coordenadaUtmY',
          'propietario.nombre',
          'propietario.apellidos'
      ],

      'uso-de-suelo': [
          'coordenadaUtmX',
          'coordenadaUtmY'
      ]
  };

  const esEditable = (campo) => {
      const editables = CONFIG_EDITABLES[props.tipoTramite] || [];
      return editables.includes(campo);
  };

  const bloquesFaltantes = computed(() => {
      if (!props.desdeTramite || !CONFIG_REQUISITOS[props.tipoTramite]) return {};

      const esquemaTramite = CONFIG_REQUISITOS[props.tipoTramite];
      const resultado = {};

      // Función auxiliar para acceder a propiedades anidadas (ej: "propietario.nombre")
      const getDeepValue = (obj, path) => {
          return path.split('.').reduce((acc, part) => acc && acc[part], obj);
      };

      Object.keys(esquemaTramite).forEach(nombrePanel => {
          const vacios = esquemaTramite[nombrePanel].filter(item => {
              const valor = getDeepValue(form, item.campo);
              
              // Verificamos si está vacío: null, undefined, ""
              // Si el tipo es 'numerico', consideramos 0 como válido si es necesario
              return valor === null || valor === undefined || valor === '';
          });
          
          if (vacios.length > 0) {
              resultado[nombrePanel] = vacios;
          }
      });

      return resultado;
  });

  const hayDatosIncompletos = computed(() => {
      return Object.keys(bloquesFaltantes.value).length > 0;
  });

  const tieneCambiosPendientes = computed(() => {
      return cambiosUbicacion.value || 
            cambiosTecnica.value   || 
            cambiosPropietario.value || 
            cambiosCroquis.value;
  });

  const panelTieneIncompletos = (nombrePanel) => {
      // Si el panel existe dentro de nuestros bloques faltantes, significa que debe datos
      return !!bloquesFaltantes.value[nombrePanel];
  };

   watch(() => props.selectedPropiedad, async(nuevaPropiedad) => {
      if (nuevaPropiedad) {
          // Si hay propiedad seleccionada, cargar sus datos
          form.id = nuevaPropiedad.id || 0
          form.claveCatastral = nuevaPropiedad.clave_catastral || ''
          form.tipoPropiedad = nuevaPropiedad.tipo || null
          form.calle = nuevaPropiedad.calle || ''
          form.numero = nuevaPropiedad.numero || ''
          form.coloniaTexto = nuevaPropiedad.colonia?.nombre || ''
          form.colonia = nuevaPropiedad.colonia || null
          form.idColonia = nuevaPropiedad.colonia?.id || null
          form.localidadTexto = nuevaPropiedad.localidad?.nombre || ''
          form.localidad = nuevaPropiedad.localidad || null
          form.idLocalidad = nuevaPropiedad.localidad?.id || null
          form.codigoPostal = nuevaPropiedad.codigo_postal || ''
          form.referenciasUbicacion = nuevaPropiedad.referencias_ubicacion || ''
          form.superficie = nuevaPropiedad.superficie || 0,
          form.superficieConstruccion = nuevaPropiedad.superficie_construccion || 0,
          form.coordenadaUtmX = nuevaPropiedad.coordenada_utm_x || null
          form.coordenadaUtmY = nuevaPropiedad.coordenada_utm_y || null
          form.contacto = nuevaPropiedad.contacto || null
          form.domicilioNotificacion = nuevaPropiedad.contacto?.domicilio_notificacion?.direccion || null
          form.idContacto = nuevaPropiedad.id_contacto || null
          form.propietario = nuevaPropiedad.contacto?.persona || null
          form.imgCroquis = nuevaPropiedad.img_croquis || ''

          imgCroquis.value = nuevaPropiedad.img_croquis || ''
          conCroquisInicial.value = nuevaPropiedad.img_croquis ? true : false

          if (props.desdeTramite)
          {
              if (props.campoEspecifico == 'nombre_propietario')
              {
                seccionEditando.value = 'propietario'
                abrirEditarPropietario()
              }
              if (props.campoEspecifico == 'domicilio_propiedad')
              {
                seccionEditando.value = 'ubicacion'
                abrirEditarUbicacion()
              }
              if (props.campoEspecifico == 'coordenada_x_utm' || props.campoEspecifico == 'coordenada_y_utm' )
              {
                seccionEditando.value = 'tecnica'
                abrirEditarTecnica()
              }
          }

          cargandoCroquis.value = true

          if (editableDomicilio.value) {
            editableDomicilio.value.innerText = form.domicilioNotificacion || ''
          }

          suggestionsColonias.value = 1
          suggestionsLocalidades.value = 1

          if (nuevaPropiedad.idColonia) {
            const colonia = await obtenerColoniaById(nuevaPropiedad.id_colonia)
            if (colonia) {
                form.idColonia = colonia.id
                form.coloniaTexto = colonia.nombre
            }
            else 
            {
                suggestionsColonias.value = 0
            }
          }
          if (nuevaPropiedad.id_localidad !== null) {
            const localidad = await obtenerLocalidadById(nuevaPropiedad.id_localidad)
            if (localidad) {
                form.idLocalidad = localidad.id
                form.localidadTexto = localidad.nombre
            }
            else
            {
                suggestionsLocalidades.value = 0
            }
          }
      } else {
          // Si es nueva propiedad, limpiar form
          form.calle = ''
          form.numero = ''
          form.coloniaTexto = ''
          form.idColonia = 0
          form.localidadTexto = ''
          form.idLocalidad = 0
          form.codigoPostal = ''
      } 
      
      formUbicacionOriginal.value = {
          tipoPropiedad: {
              id: form.tipoPropiedad.id
          },
          calle: form.calle,
          numero: form.numero,
          idColonia: form.idColonia,
          idLocalidad: form.idLocalidad,
          codigoPostal: form.codigoPostal,
          referenciasUbicacion: form.referenciasUbicacion
      }

      formTecnicaOriginal.value = {
          superficie: form.superficie,
          superficieConstruccion: form.superficieConstruccion,
          coordenadaUtmX: form.coordenadaUtmX,
          coordenadaUtmY: form.coordenadaUtmY,
      }

      formPropietarioOriginal.value = {
          propietario: structuredClone(toRaw(form.propietario)),
          contacto: structuredClone(toRaw(form.contacto)),
          domicilioNotificacion: form.domicilioNotificacion
      }

      formCroquisOriginal.value = form.imgCroquis
    }, { immediate: true })
</script>

<template>
  <div :class="['relative overflow-hidden', isPage ? 'mx-auto my-5' : 'rounded-2xl bg-white shadow-2xl']"> 
     <form @submit.prevent="handleSubmit" class="flex flex-col md:flex-row">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div 
          v-if="enviandoFormulario" 
          class="fixed inset-0 z-[999999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm">
          <div class="flex flex-col items-center p-6 bg-white rounded-2xl shadow-xl max-w-xs text-center">
            <div class="relative flex items-center justify-center w-16 h-16 mb-4">
              <div class="absolute w-full h-full border-4 border-color1-100 rounded-full"></div>
              <div class="absolute w-full h-full border-4 border-t-color1-600 border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin"></div>
            </div>
            
            <h3 :key="mensajeCarga" class="text-base font-bold text-gray-900 mb-1">
              {{ mensajeCarga === 'Validando' ? 'Validando información' : 'Guardando cambios' }}
            </h3>
            <p :key="mensajeCarga" class="text-xs text-gray-500 px-2">
              {{ mensajeCarga === 'Validando' 
                ? 'Estamos verificando que los datos cumplan con los requisitos del trámite.' 
                : 'El sistema está registrando los cambios y actualizando el historial.' }}
            </p>
          </div>
        </div>
      </Transition>
      <div :class="[isPage ? 'px-6' : 'p-8', 'flex-grow md:w-[70%] border-r border-gray-100']">
          <!-- <div class="pb-4 mb-4 bg-white">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-1">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Propiedad </h2>
                    <div class="flex items-center gap-1 mt-0">
                      <div>
                          <div class="flex items-center gap-1 p-0 leading-none">
                              <span class="text-xs font-bold text-gray-400 uppercase p-0">ID:</span>
                              <span class="text-xs font-semibold text-gray-800 p-0">{{ form?.id }}</span>
                              <span class="text-xs font-bold text-gray-400 uppercase p-0">•</span>
                          </div>
                      </div>                    
                      <div>
                          <div class="flex items-center gap-1 leading-none">
                              <span class="text-xs font-bold text-gray-400 uppercase">Clave Catastral:</span>
                              <span class="text-[14px] font-bold text-color1-700 whitespace-nowrap">
                                  <span v-for="(grupo, index) in formatearClaveVisual(form?.claveCatastral)" 
                                      :key="index" class="mr-[2px]">
                                      {{ grupo }}
                                  </span>
                              </span>
                          </div>
                      </div>
                  </div>
                </div> 
                <div class="flex items-start gap-2 self-start">
                  <button @click="$emit('cancel')" class="p-1 rounded-lg hover:bg-gray-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
            </div>
        </div> -->
        <div class="flex flex-col bg-white md:flex-row w-full justify-between items-start gap-6">
            <div :class="[isPage ? 'px-1' : 'p-8', 'flex-grow md:w-[70%] bg-white']">
                <div class="pb-4 mb-4">
                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold text-gray-800">Propiedad</h2>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                            <div class="flex items-center gap-1 leading-none">
                                <span class="text-xs font-bold text-gray-400 uppercase">ID:</span>
                                <span class="text-xs font-semibold text-gray-800">{{ form?.id }}</span>
                                <span class="text-xs font-bold text-gray-400 uppercase ml-1">•</span>
                            </div>
                            <div class="flex items-center gap-1 leading-none">
                                <span class="text-xs font-bold text-gray-400 uppercase">Clave Catastral:</span>
                                <span class="text-[14px] font-bold text-color1-700 whitespace-nowrap">
                                    <span v-for="(grupo, index) in formatearClaveVisual(form?.claveCatastral)" 
                                          :key="index" class="mr-[2px]">
                                        {{ grupo }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="!isPage" class="flex items-center justify-end mt-4">
                        <button @click="$emit('cancel')" 
                                class="p-1.5 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="desdeTramite" class="w-full bg-white md:w-auto flex justify-end items-start shrink-0">
              <div class="w-full bg-white mb-4 md:w-auto flex justify-end items-start md:mt-2 shrink-0">
                <div class="rounded-xl p-4 pl-5 pr-6 bg-color1-50/60 border border-color1-100/70 flex items-center gap-6 shadow-sm max-w-full">
                  <div class="flex items-center gap-6 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="w-7 h-7 rounded-xl bg-color1-500/10 flex items-center justify-center text-color1-600 shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                      </div>
                      <div class="flex flex-col min-w-0">
                        <p class="text-[13px] font-bold text-color1-700 leading-relaxed flex items-center gap-1.5 min-w-0">
                          <span class="text-color1-600 font-semibold truncate">Propiedad Vinculada</span>
                          <span class="text-color1-300 font-normal">|</span>{{ form.tipo_tramite }}
                        </p>
                        <span class="text-[11px] font-bold text-color1-400">
                          TRÁMITE #{{ String(form.id_tramite).slice(-4).padStart(4, '0') }} • SOLICITUD #{{ String(form.id_solicitud).slice(-4).padStart(4, '0') }}
                        </span>
                      </div>
                    </div>

                    <div v-if="props.tipoTramite == 'constancia-de-numero-oficial'" 
                        class="flex items-center gap-3 pl-6 border-l border-color1-200/50 h-8">
                      <div class="flex flex-col">
                        <span class="text-[9px] font-bold uppercase tracking-widest text-color1-500 opacity-80">
                          {{ props.tramiteData.payload.numero_asignado ? 'Núm. Asignado' : 'Núm. Asignado' }}
                        </span>
                        <span class="text-[12px] font-bold uppercase text-color1-800" :class="!props.tramiteData.payload.numero_asignado ? 'text-amber-600' : ''">
                          {{ props.tramiteData.payload.numero_asignado ? '# ' + props.tramiteData.payload.numero_asignado : 'S/N' }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <button v-if="!(cambiosUbicacion || cambiosTecnica || cambiosPropietario || cambiosCroquis)" @click="volverAlTramite()" 
                          class="shrink-0 ml-auto inline-flex items-center gap-1 px-4 py-2 rounded-full bg-color1-600 hover:bg-color1-800 text-white text-[10px] font-semibold transition-all uppercase tracking-widest shadow-sm">
                    Volver al Trámite
                  </button>
                </div>
              </div>
            </div>
        </div>
        <section>
          <div class="flex flex-col lg:flex-row gap-8">
            <div 
              class="w-full flex flex-col gap-6 transition-all duration-500"
              :class="[
                  isPage && seccionEditando === 'ubicacion'
                    ? 'lg:w-[80%]'
                    : isPage && (seccionEditando === 'propietario' || seccionEditando === 'croquis') 
                      ? 'lg:w-[25%]'
                      : isPage 
                        ? 'lg:w-[50%]' 
                        : seccionEditando === 'propietario'
                          ? 'lg:w-[30%]'
                          : seccionEditando === 'croquis'
                            ? 'lg:w-[20%]'
                            : seccionEditando === 'ubicacion'
                              ? 'lg:w-[80%]'
                              : 'lg:w-[60%]']">
              <div class="perspective-[2000px]">
                <div
                    class="relative transition-all duration-500 [transform-style:preserve-3d]"
                    :class="seccionEditando === 'ubicacion' || seccionEditando === 'tecnica'
                        ? 'rotate-y-180'
                        : ''">
                  <div class="[backface-visibility:hidden] transition-all duration-500"
                  :class="seccionEditando === 'propietario' || seccionEditando === 'croquis'
                      ? 'opacity-50 pointer-events-none scale-[0.99]'
                      : ''">
                    <div class="relative z-10">
                      <div class="flip-card h-full" :class="{ 'flipped': isFlipped }">
                        <div 
                          class="p-5 bg-white rounded-3xl border border-gray-200 shadow-md transition-all duration-300"
                          :class="isPage ? 'h-[30vh]' : '', seccionEditando === 'ubicacion'
                              ? 'ring-2 ring-color1-200 shadow-xl'
                              : ''">
                          <div class="flex justify-between items-start pl-3" :class="cambiosUbicacion ? 'mb-1' : 'mb-3'">
                            <div class="flex flex-col flex-1 min-w-0">
                              <div class="flex items-center gap-2 text-color1-700 font-bold uppercase text-[11px] tracking-widest">
                                  <div class="w-7 h-7 rounded-xl bg-color1-100/70 flex shrink-0 items-center justify-center">
                                      <svg class="w-3.5 h-3.5 text-color1-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                      </svg>
                                  </div>

                                  <span class="flex items-center gap-2 truncate">
                                      Ubicación del {{ tipoSeleccionado ? tipoSeleccionado.nombre : 'PREDIO' }}
                                  </span>
                              </div>
                              <div class="ml-7 flex flex-wrap items-center gap-2 mt-1">
                                <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div
                                        v-if="cambiosUbicacion && (seccionEditando != 'propietario' && seccionEditando != 'croquis')"
                                        class="inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-amber-700 bg-amber-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        Cambios pendientes
                                    </div>
                                </transition>
                                <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div
                                        v-if="panelTieneIncompletos('Ubicación')"
                                        class="inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-color2-700 bg-color2-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-color2-500"></span>
                                        Datos incompletos
                                    </div>
                                </transition>
                              </div>
                            </div>
                            <button
                                v-if="seccionEditando != 'propietario' && seccionEditando != 'croquis'"
                                @click="abrirEditarUbicacion()"
                                class="px-4 py-2 bg-color1-600 hover:bg-color1-700 text-white text-[10px] font-semibold rounded-full uppercase tracking-widest transition-all duration-300 transform hover:scale-105 shadow-sm">
                                Editar Datos
                            </button>
                          </div>
                          <div class="space-y-1">
                            <div class="rounded-lg p-3 bg-gray-50/50">
                              <div class="relative pl-5 ml-1">
                                <div class="absolute left-0 top-0 h-full w-[3px] rounded-full bg-gradient-to-b from-color1-500 via-color1-600 to-color1-400"></div>
                                <div class="flex-1">
                                  <div class="flex items-start gap-2">
                                      <span class="text-gray-800 font-bold text-sm leading-tight text-justify"
                                      :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">
                                          {{ formatearDireccionObjetos(form).principal?.toUpperCase() }}
                                      </span>
                                  </div>
                                  <div class="flex items-start gap-2 pt-2">
                                      <span class="text-gray-500 text-xs line-clamp-2 truncate">
                                        {{ form.referenciasUbicacion ? form.referenciasUbicacion.toUpperCase() : 'SIN REFERENCIAS DE UBICACIÓN' }}
                                      </span>
                                  </div>
                                  <div v-if="form.localidadTexto" class="flex items-center gap-2 mt-2 pt-3 pb-1 border-t border-gray-200">
                                      <div class="w-2 h-2 rounded-full bg-color1-500 flex-shrink-0"></div>                                                   
                                      <span class="text-gray-700 font-normal text-[13px] leading-none truncate">
                                          {{ formatearDireccionObjetos(form).localidad }}
                                      </span>
                                  </div>
                                </div>
                              </div>
                            </div>           
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div
                    class="absolute inset-0 rotate-y-180 [backface-visibility:hidden]"
                    :class="seccionEditando === 'ubicacion'
                      ? 'z-10 pointer-events-auto'
                      : 'z-0 pointer-events-none'">
                    <transition
                      enter-active-class="transition-all duration-100 ease-out"
                      enter-from-class="opacity-0 max-h-0 translate-y-2"
                      enter-to-class="opacity-100 max-h-[2000px] translate-y-0"
                      leave-active-class="transition-all duration-50 ease-in"
                      leave-from-class="opacity-100 max-h-[2000px]"
                      leave-to-class="opacity-0 max-h-0">
                      <div
                        v-if="seccionEditando === 'ubicacion'"
                        class="overflow-visible">
                        <div class="rounded-[26px] bg-gradient-to-br from-color1-300/80 via-color1-100/40 to-color1-400/70 p-[2px] shadow-sm shadow-color1-200/60 transition-all duration-300">
                          <div class="bg-white rounded-[24px] px-8 py-6 overflow-visible">
                            <div class="flex items-center justify-between mb-2">
                              <div class="flex items-center gap-3 mb-2">
                                <div class="w-1.5 h-10 rounded-full bg-gradient-to-b from-color1-500 to-color1-700"></div>
                                  <div class="flex flex-col justify-center leading-none">
                                      <p class="text-[10px] uppercase tracking-[0.35em] text-color1-500 font-bold mb-1">
                                          Editar Datos
                                      </p>
                                      <h2 class="text-[17px] font-bold tracking-[0.08em] text-color1-700 uppercase">
                                          Ubicación
                                      </h2>
                                  </div>
                                </div>
                              </div>
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-8 items-end mb-8">
                              <div class="md:col-span-6">
                                <div class="flex items-center gap-4 w-full">
                                  <label
                                      :class="[
                                        'text-sm duration-300 transform origin-left whitespace-nowrap',
                                        editandoTipo ? 'text-color1-600 scale-90' : 'text-gray-500'
                                      ]">
                                      Tipo
                                  </label>
                                  <transition name="fade-slide">
                                    <div
                                        v-if="editandoTipo"
                                        class="flex bg-gray-100 rounded-full px-4 py-2 flex-1 flex-wrap items-center gap-2">
                                        <button
                                            v-for="tipo in tiposPropiedad"
                                            :key="tipo.id"
                                            type="button"
                                            @click="seleccionarTipo(tipo.id)"
                                            :class="form.tipoPropiedad.id === tipo.id
                                                ? 'bg-color1-50 text-color1-400 border-color1-100 hover:bg-color1-300 hover:border-color1-500 hover:border-white hover:text-white'
                                                : 'bg-white text-gray-600 border-gray-200 hover:border-color1-300 hover:text-color1-600'"
                                            class="flex-1 border rounded-full py-2 px-4 text-xs font-semibold transition-all duration-200 transform active:scale-95">
                                            {{ tipo.nombre }}
                                        </button>
                                    </div>
                                  </transition>
                                </div>
                                <div class="relative">
                                  <transition name="fade-slide">
                                    <div v-if="!editandoTipo" key="lectura" 
                                      class="flex items-center group cursor-pointer"
                                      @click="editandoTipo = true">
                                      <div class="flex-1 border-b-2 border-gray-300 py-1 transition-colors ">
                                          <span :class="tipoSeleccionado ? 'text-gray-800' : 'text-gray-400'" class="font-bold text-sm">
                                              {{ tipoSeleccionado ? tipoSeleccionado.nombre : 'Seleccionar tipo...' }}
                                          </span>
                                      </div>
                                      <button 
                                          type="button"
                                          @click.stop="editandoTipo = true"
                                          class="ml-2 py-1.5 px-3 text-gray-400 cursor-pointer hover:text-color1-600 transition-all rounded-full hover:bg-gray-100">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                          </svg>
                                      </button>
                                    </div>
                                  </transition>
                                </div>
                              </div>
                            </div>
                           <div class="flex flex-col md:flex-row gap-6 items-end mb-8 w-full">            
                              <div class="relative group flex-1 w-full">
                                <input type="text" 
                                  v-model="form.calle"
                                  :disabled="esSoloLectura" 
                                  placeholder=" "
                                  class="block font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer uppercase text-sm"
                                  :class="{ 'border-transparent font-bold bg-gray-100 rounded-t-lg px-2': esSoloLectura }">
                                <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600">
                                  Vialidad
                                </label>
                              </div>
                              <div v-if="desdeTramite && props.tipoTramite != 'constancia-de-numero-oficial'" 
                                  class="relative group w-full md:w-[60px] flex-shrink-0">
                                <input type="text" v-model="form.numero" :disabled="esSoloLectura" placeholder=" "
                                  class="block font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer uppercase text-sm"
                                  :class="{ 'border-transparent font-bold bg-gray-100 rounded-t-lg px-2': esSoloLectura }">
                                <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600">
                                  Número
                                </label>
                              </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-[1fr] gap-8 items-end mb-[33px]">
                              <div class="relative">
                                  <el-autocomplete
                                      v-model="form.coloniaTexto"
                                      :fetch-suggestions="buscarColoniasREST"
                                      :trigger-on-focus="true"
                                      placeholder=" "
                                      @select="seleccionarColonia"
                                      @focus="isFocusedColonia = true"
                                      @blur="isFocusedColonia = false"
                                      class="custom-autocomplete w-full font-bold !text-red-800"
                                      :class="{ 'has-value': form.coloniaTexto, 'is-focused': isFocusedColonia }"
                                      :disabled="esSoloLectura"
                                      clearable>
                                      <template #default="{ item }">
                                          <div class="flex justify-between items-center">
                                              <span>{{ item.nombre }}</span>
                                          </div>
                                      </template>
                                      <template #suffix>
                                        <div v-if="isLoadingColonias" class="flex items-center justify-center w-4 h-4">
                                            <div class="w-3 h-3 border-2 border-gray-300 border-t-color1-700 rounded-full animate-spin"></div>
                                        </div>
                                      </template>
                                      <template #loading>
                                        <div class="flex flex-col items-center justify-center min-h-[80px] gap-2 text-sm text-gray-500">
                                            <div class="w-4 h-4 border-2 border-gray-300 border-t-color1-700 rounded-full animate-spin"></div>
                                            <span>Cargando...</span>
                                        </div>
                                      </template>
                                  </el-autocomplete>
                                  <div 
                                      v-if="showNoResultsMessageColonias && isFocusedColonia" 
                                      class="absolute z-10 w-full bg-white border border-gray-200 rounded-b-lg shadow-lg mt-1 py-4 text-center"
                                      style="top: 100%; left: 0;">
                                      <div class="text-gray-500 text-sm">
                                          <i class="fas fa-search mb-2 block"></i>
                                          No se encontraron colonias
                                      </div>
                                  </div>
                                  <label 
                                    class="absolute text-sm left-0 transition-all duration-300 ease-out pointer-events-none origin-left"
                                    :class="{ labelClassesColonias,
                                        '-top-[18px] scale-90 text-color1-700': isFocusedColonia,
                                        '-top-[18px] scale-100 text-gray-500': !isFocusedColonia && form.coloniaTexto,
                                        'top-1 scale-90 text-gray-500': !isFocusedColonia && !form.coloniaTexto
                                    }">
                                    <span>Colonia</span>
                                      <span class="text-xs text-gray-400/70 font-normal transition-opacity duration-200 ml-1"
                                            :class="(isFocusedColonia || form.coloniaTexto) ? 'opacity-0' : 'opacity-100'">
                                        (Opcional)
                                      </span>
                                  </label>
                              </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_2fr] gap-8 items-end mb-[33px]">
                              <div class="relative group">
                                    <input type="text" v-model="form.codigoPostal" 
                                    :disabled="esSoloLectura" placeholder=" "
                                        class="block font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer uppercase text-sm"
                                        :class="{ 'border-transparent font-bold bg-gray-100 rounded-t-lg px-2': esSoloLectura }">
                                    <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600">
                                        Código Postal
                                    </label>
                              </div>
                              <div class="relative">
                                  <el-autocomplete
                                      v-model="form.localidadTexto"
                                      :fetch-suggestions="buscarLocalidadesREST"
                                      :trigger-on-focus="true"
                                      placeholder=" "
                                      @select="seleccionarLocalidad"
                                      @focus="isFocusedLocalidad = true"
                                      @blur="isFocusedLocalidad = false"
                                      class="custom-autocomplete font-bold w-full"
                                      :class="{ 'has-value': form.localidadTexto, 'is-focused': isFocusedLocalidad }"
                                      :disabled="esSoloLectura"
                                      clearable>
                                      <template #default="{ item }">
                                          <div class="flex justify-between items-center">
                                              <span>{{ item.nombre }}</span>
                                          </div>
                                      </template>
                                      <template #suffix>
                                        <div v-if="isLoadingLocalidades" class="flex items-center justify-center w-4 h-4">
                                            <div class="w-3 h-3 border-2 border-gray-300 border-t-color1-700 rounded-full animate-spin"></div>
                                        </div>
                                      </template>
                                      <template #loading>
                                        <div class="flex flex-col items-center justify-center min-h-[80px] gap-2 text-sm text-gray-500">
                                            <div class="w-4 h-4 border-2 border-gray-300 border-t-color1-700 rounded-full animate-spin"></div>
                                            <span>Cargando...</span>
                                        </div>
                                      </template>                     
                                  </el-autocomplete>
                                  <div 
                                      v-if="showNoResultsMessageLocalidades && isFocusedLocalidad" 
                                      class="absolute z-10 w-full bg-white border border-gray-200 rounded-b-lg shadow-lg mt-1 py-4 text-center"
                                      style="top: 100%; left: 0;">
                                      <div class="text-gray-500 text-sm">
                                          <i class="fas fa-search mb-2 block"></i>
                                          No se encontraron localidades
                                      </div>
                                  </div>
                                  <label 
                                    class="absolute text-sm left-0 transition-all duration-300 ease-out pointer-events-none origin-left"
                                    :class="{ labelClassesLocalidades,
                                        '-top-[18px] scale-90 text-color1-700': isFocusedLocalidad,
                                        '-top-[18px] scale-100 text-gray-500': !isFocusedLocalidad && form.localidadTexto,
                                        'top-1 scale-90 text-gray-500': !isFocusedLocalidad && !form.localidadTexto
                                    }">
                                    Localidad
                                  </label>
                              </div>
                            </div>
                            <div class="relative group mb-4">
                                <textarea v-model="form.referenciasUbicacion" rows="1" :disabled="esSoloLectura" placeholder="Agrega referencias para ubicar el domicilio, Ej. Entre qué calles.."
                                    class="block uppercase font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer text-sm resize-none placeholder:italic placeholder:text-[12px] placeholder:leading-[24px] placeholder:text-gray-400 placeholder:font-normal"></textarea>
                                <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-100 peer-focus:scale-90 peer-focus:text-color1-600">
                                    Referencias
                                </label>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="cancelarEditarUbicacion"
                                    class="w-36 px-5 py-2.5 rounded-full bg-gray-200 border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-300 transition-all duration-200 hover:scale-[1.02]">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="aceptarEditarUbicacion"
                                    class="w-36 px-5 py-2.5 rounded-full bg-color1-600 hover:bg-color1-700 text-white text-sm font-semibold shadow-color1-200/50 transition-all duration-200 hover:scale-[1.02]">
                                    Aceptar
                                </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </transition>
                  </div>
                  <div class="[backface-visibility:hidden] transition-all duration-500"
                    :class="seccionEditando === 'ubicacion'
                      ? 'opacity-0 -translate-y-2 pointer-events-none mt-0'
                      : 'opacity-100 translate-y-0 mt-6'">
                    <div class="relative">
                      <div 
                        class="p-5 bg-white rounded-3xl border border-gray-200 shadow-md transition-all duration-300"
                        :class="isPage ? 'h-[30vh]' : '', seccionEditando === 'ubicacion' || seccionEditando === 'propietario' || seccionEditando === 'croquis'
                            ? 'opacity-50 pointer-events-none scale-[0.99]'
                            : ''">
                        <div class="flex justify-between items-center pl-3" :class="cambiosTecnica ? 'mb-1' : 'mb-3'">
                          <div class="flex flex-col flex-1 min-w-0">
                            <div class="flex items-center gap-2 text-color1-700 font-bold uppercase text-[11px] tracking-widest">
                              <div class="w-7 h-7 rounded-xl bg-color1-100/70 flex shrink-0 items-center justify-center">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-vector-square-icon lucide-vector-square w-3.5 h-3.5 text-color1-600">
                                    <path d="M19.5 7a24 24 0 0 1 0 10"/><path d="M4.5 7a24 24 0 0 0 0 10"/><path d="M7 19.5a24 24 0 0 0 10 0"/><path d="M7 4.5a24 24 0 0 1 10 0"/><rect x="17" y="17" width="5" height="5" rx="1"/><rect x="17" y="2" width="5" height="5" rx="1"/><rect x="2" y="17" width="5" height="5" rx="1"/><rect x="2" y="2" width="5" height="5" rx="1"/>
                                  </svg>
                              </div>
                              <span class="truncate">
                              Información Técnica
                              </span>
                            </div>
                              <div class="ml-7 flex flex-wrap items-center gap-2 mt-1">
                                <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div
                                        v-if="cambiosTecnica && (seccionEditando != 'propietario' && seccionEditando != 'croquis')"
                                        class="inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-amber-700 bg-amber-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        Cambios pendientes
                                    </div>
                                </transition>
                                <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div
                                        v-if="panelTieneIncompletos('Información Técnica')"
                                        class="inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-color2-700 bg-color2-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-color2-500"></span>
                                        Datos incompletos
                                    </div>
                                </transition>
                              </div>
                          </div>
                          <button v-if="seccionEditando != 'propietario' && seccionEditando != 'croquis'"
                              @click="abrirEditarTecnica()"
                              class="px-4 py-2 bg-color1-600 hover:bg-color1-700 text-white text-[10px] font-semibold rounded-full uppercase tracking-widest transition-all duration-300 transform hover:scale-105 shadow-sm">
                              Editar Datos
                          </button>
                        </div>
                        <div class="space-y-1">
                          <div class="rounded-lg p-3 bg-gray-50/50">
                            <div class="relative pl-5 ml-1">
                              <div class="absolute left-0 top-0 h-full w-[3px] rounded-full bg-gradient-to-b from-color1-500 via-color1-600 to-color1-400"></div>
                              <div class="flex-1">
                                <div class="grid grid-cols-2 gap-4">
                                  <div>
                                      <div class="text-gray-800 font-bold text-base" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">{{ Number(form.superficie || 0).toFixed(2) }} <span class="text-xs font-normal text-gray-500">m²</span></div>
                                      <div class="text-[9px] text-gray-400 uppercase tracking-wider truncate" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">Superficie</div>
                                  </div>
                                  <div>
                                      <div class="text-gray-800 font-bold text-base" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">{{ Number(form.superficieConstruccion || 0).toFixed(2) }} <span class="text-xs font-normal text-gray-500">m²</span></div>
                                      <div class="text-[9px] text-gray-400 uppercase tracking-wider truncate" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">Superficie en Construcción</div>
                                  </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-1 pt-1 border-t border-gray-200">
                                  <div>
                                      <div class="text-gray-800 font-semibold text-base" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">
                                          {{
                                              form.coordenadaUtmX !== null && form.coordenadaUtmX !== ''
                                                  ? Number(form.coordenadaUtmX).toFixed(2)
                                                  : '---'
                                          }}
                                      </div>
                                      <div class="text-[9px] text-gray-400 uppercase tracking-wider truncate">COORD. UTM X</div>
                                  </div>
                                  <div>
                                      <div class="text-gray-800 font-semibold text-base" :class="{ 'truncate': seccionEditando === 'propietario' || seccionEditando === 'croquis'}">
                                          {{
                                              form.coordenadaUtmY !== null && form.coordenadaUtmY !== ''
                                                  ? Number(form.coordenadaUtmY).toFixed(2)
                                                  : '---'
                                          }}
                                      </div>
                                      <div class="text-[9px] text-gray-400 uppercase tracking-wider truncate">COORD. UTM Y</div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>           
                        </div>
                      </div>
                    </div>
                  </div>
                  <div
                      class="absolute inset-0 rotate-y-180 [backface-visibility:hidden]"
                      :class="seccionEditando === 'tecnica'
                        ? 'z-10 pointer-events-auto'
                        : 'z-0 pointer-events-none'">
                    <transition
                        enter-active-class="transition-all duration-100 ease-out"
                        enter-from-class="opacity-0 max-h-0 translate-y-2"
                        enter-to-class="opacity-100 max-h-[2000px] translate-y-0"
                        leave-active-class="transition-all duration-50 ease-in"
                        leave-from-class="opacity-100 max-h-[2000px]"
                        leave-to-class="opacity-0 max-h-0">
                        <div
                          v-if="seccionEditando === 'tecnica'"
                          class="overflow-visible">
                            <div class="rounded-[26px] bg-gradient-to-br from-color1-300/80 via-color1-100/40 to-color1-400/70 p-[2px] shadow-sm shadow-color1-200/60 transition-all duration-300">
                              <div class="bg-white rounded-[24px] px-8 py-6 overflow-visible">
                                <div class="flex items-center justify-between mb-5">
                                  <div class="flex items-center gap-3 mb-8">
                                    <div class="w-1.5 h-10 rounded-full bg-gradient-to-b from-color1-500 to-color1-700"></div>
                                      <div class="flex flex-col justify-center leading-none">
                                          <p class="text-[10px] uppercase tracking-[0.35em] text-color1-500 font-bold mb-1">
                                              Editar Datos
                                          </p>
                                          <h2 class="text-[17px] font-bold tracking-[0.08em] text-color1-700 uppercase">
                                              Información Técnica
                                          </h2>
                                      </div>
                                    </div>
                                  </div>
                                <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr] gap-6 items-end mb-8">            
                                    <div class="relative group w-full">
                                      <input
                                        type="text"
                                        v-model="form.superficie"
                                        :readonly="!esEditable('superficie')"
                                        placeholder=" "
                                        class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm pr-8"
                                        :class="[
                                          esEditable('superficie')
                                            ? 'focus:border-color1-600'
                                            : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                        ]"/>

                                      <span class="absolute right-0 bottom-1.5 text-xs font-normal text-gray-500 pointer-events-none">
                                        m²
                                      </span>

                                      <label
                                        :class="[
                                          'absolute text-sm origin-left duration-300 pointer-events-none',
                                          esEditable('superficie')
                                            ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                            : (
                                                form.superficie
                                                  ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                  : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                              )
                                        ]">
                                        Superficie
                                      </label>
                                    </div>
                                    <div class="relative group w-full">
                                      <input
                                        type="text"
                                        v-model="form.superficieConstruccion"
                                        :readonly="!esEditable('superficieConstruccion')"
                                        placeholder=" "
                                        class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm pr-8"
                                        :class="[
                                          esEditable('superficieConstruccion')
                                            ? 'focus:border-color1-600'
                                            : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                        ]"/>

                                      <span class="absolute right-0 bottom-1.5 text-xs font-normal text-gray-500 pointer-events-none">
                                        m²
                                      </span>

                                      <label
                                        :class="[
                                          'absolute text-sm origin-left duration-300 pointer-events-none',
                                          esEditable('superficieConstruccion')
                                            ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                            : (
                                                form.superficieConstruccion
                                                  ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                  : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                              )
                                        ]">
                                        Superficie en Construcción
                                      </label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr] gap-6 items-end mb-8">            
                                    <div class="relative group w-full">
                                        <input type="text" v-model="form.coordenadaUtmX" min="0"
                                              step="any" :disabled="esSoloLectura" placeholder=" "
                                            class="block font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer uppercase text-sm"
                                            :class="{ 'border-transparent font-bold bg-gray-100 rounded-t-lg px-2': esSoloLectura }">
                                        <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600">
                                            Coordenada UTM X
                                        </label>
                                    </div>
                                    <div class="relative group w-full">
                                        <input type="text" v-model="form.coordenadaUtmY" min="0"
                                              step="any" :disabled="esSoloLectura" placeholder=" "
                                            class="block font-bold text-gray-800 w-full px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-color1-600 peer uppercase text-sm"
                                            :class="{ 'border-transparent font-bold bg-gray-100 rounded-t-lg px-2': esSoloLectura }">
                                        <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-100 top-1 origin-left peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600">
                                            Coordenada UTM Y
                                        </label>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-0">
                                    <button
                                        type="button"
                                        @click="cancelarEditarTecnica"
                                        class="w-36 px-5 py-2.5 rounded-full bg-gray-200 border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-300 transition-all duration-200 hover:scale-[1.02]">
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        @click="aceptarEditarTecnica"
                                        class="w-36 px-5 py-2.5 rounded-full bg-color1-600 hover:bg-color1-700 text-white text-sm font-semibold shadow-color1-200/50 transition-all duration-200 hover:scale-[1.02]">
                                        Aceptar
                                    </button>
                                </div>
                              </div>
                            </div>
                        </div>
                    </transition>
                  </div>
                </div>
              </div>
            </div>
            <div 
              class="w-full flex flex-col gap-6 transition-all duration-500"
              :class="[
                  isPage && seccionEditando === 'ubicacion'
                      ? 'lg:w-[20%]'
                  : isPage && (seccionEditando === 'propietario' || seccionEditando === 'croquis') 
                      ? 'lg:w-[75%]'
                  : isPage 
                      ? 'lg:w-[50%]' 
                      : seccionEditando === 'propietario'
                          ? 'lg:w-[70%]'
                          : seccionEditando === 'croquis'
                              ? 'lg:w-[80%]'
                              : seccionEditando === 'ubicacion'
                                  ? 'lg:w-[20%]'
                                  : 'lg:w-[40%]']">
                <div class="perspective-[2000px]">
                  <div
                      class="relative transition-all duration-500 [transform-style:preserve-3d]"
                      :class="seccionEditando === 'propietario' || seccionEditando === 'croquis'
                          ? 'rotate-y-180'
                          : ''">
                    <div class="[backface-visibility:hidden] transition-all duration-500">
                      <div class="sticky top-4">
                        <div 
                          class="p-5 bg-white rounded-3xl border border-gray-200 shadow-md transition-all duration-300"
                          :class="isPage ? 'h-[30vh]' : '', seccionEditando === 'ubicacion' || seccionEditando === 'tecnica'
                              ? 'opacity-50 pointer-events-none scale-[0.99]'
                              : ''">
                            <div class="flex justify-between items-start pl-3" :class="cambiosPropietario ? 'mb-0' : 'mb-3'">
                              <div class="flex flex-col flex-1 min-w-0">  
                                <div class="flex items-center gap-2 text-color1-700 font-bold uppercase text-[11px] tracking-widest">
                                      <div class="w-7 h-7 rounded-xl bg-color1-100/70 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                      </div>
                                    {{ form.propietario?.curp?.charAt(10) === 'M' ? 'Propietaria' : 'Propietario' }} 
                                </div>
                                <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div
                                        v-if="cambiosPropietario && (seccionEditando != 'ubicacion' && seccionEditando != 'tecnica')"
                                        class="ml-7 inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-amber-700 bg-amber-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        Cambios pendientes
                                    </div>
                                </transition>
                                </div>
                                <button v-if="seccionEditando != 'ubicacion' && seccionEditando != 'tecnica' "
                                    @click="abrirEditarPropietario()"
                                    class="px-4 py-2 bg-color1-600 hover:bg-color1-700 text-white text-[10px] font-semibold rounded-full uppercase tracking-widest transition-all duration-300 transform hover:scale-105 shadow-sm">
                                    {{ form.propietario ? 'Editar Datos' : 'Asignar' }}
                                </button>
                            </div>
                            <div class="space-y-1">
                              <div class="rounded-lg p-3 bg-gray-50/50">
                                  <div v-if="form.propietario" class="relative pl-5 ml-1">
                                      <div class="absolute left-0 top-0 h-full w-[3px] rounded-full bg-gradient-to-b from-color1-500 via-color1-600 to-color1-400"></div>
                                      <div class="flex-1">
                                          <div class="flex items-baseline justify-between flex-wrap gap-1">
                                              <span class="text-gray-800 font-bold text-sm truncate">{{ form.propietario.nombre.toUpperCase() || '—' }} {{ form.propietario.apellidos.toUpperCase() || '—' }}</span>
                                          </div>
                                          <div class="flex flex-wrap text-xs">
                                              <span class="text-gray-500 truncate">{{ form.propietario.curp.toUpperCase() || '—' }}</span>
                                          </div>
                                          
                                          <div class="flex flex-col text-xs mt-1.5 leading-tight" :class="isPage ? 'gap-2' : 'gap-0.5'">
                                              <div class="flex" :class="isPage ? 'flex-col gap-1.5' : 'flex-row items-center justify-between gap-4'">
                                                  <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                                    <svg 
                                                        xmlns="http://www.w3.org/2000/svg" 
                                                        width="24" 
                                                        height="24" 
                                                        viewBox="0 0 24 24" 
                                                        fill="rgb(143, 0, 48,0.20)" 
                                                        stroke="currentColor" 
                                                        stroke-width="2" 
                                                        stroke-linecap="round" 
                                                        stroke-linejoin="round" 
                                                        class="lucide lucide-mail-icon lucide-mail text-color1-400 shrink-0 transition-all duration-300"
                                                        :class="isPage ? 'w-3.5 h-3.5' : 'w-2.5 h-2.5'">
                                                        <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/>
                                                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                                                    </svg>
                                                    <span 
                                                        class="text-gray-500 truncate"
                                                        :class="isPage ? 'text-[12px]' : 'text-[10px]'">
                                                          {{ form.contacto?.email?.toLowerCase() || '—' }}
                                                      </span>
                                                  </div>
                                                  
                                                  <div class="flex items-center gap-1.5 min-w-0 flex-1" :class="{ 'mt-1': isPage }">
                                                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="rgb(143, 0, 48,0.70)" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone-icon lucide-phone text-color1-400 shrink-0"
                                                      :class="isPage ? 'w-3.5 h-3.5' : 'w-2.5 h-2.5'">
                                                          <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>
                                                      </svg>
                                                      <span 
                                                        class="text-gray-500 truncate"
                                                        :class="isPage ? 'text-[12px]' : 'text-[10px]'">
                                                        {{ form.contacto?.telefono || '—' }}
                                                    </span>
                                                  </div>
                                              </div>
                                              
                                              <div class="flex items-center gap-1.5 min-w-0 flex-1" :class="isPage ? 'mt-0' : 'mt-1'">
                                                  <svg class="text-color1-400 flex-shrink-0 "
                                                   :class="isPage ? 'w-3.5 h-4 -mt-[1px]' : 'w-2.5 h-2.5 mt-[1px]'"
                                                  fill="rgb(143, 0, 48,0.20)" stroke="currentColor" viewBox="0 0 24 24">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                  </svg>
                                                  <span 
                                                      class="text-gray-500 text-[10px] leading-tight"
                                                      :class="[{ 'truncate': seccionEditando === 'ubicacion' }, isPage ? 'text-[12px]' : 'text-[10px]' ]">
                                                      {{ form.domicilioNotificacion?.toUpperCase() || '—' }}
                                                  </span>
                                              </div>  
                                          </div>
                                      </div>
                                  </div>
                                  <div v-else class="flex gap-3 items-center">
                                      <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl border border-gray-200 flex items-center justify-center">
                                          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                          </svg>
                                      </div>
                                      <div class="flex-1">
                                          <p class="text-gray-500 text-sm">Sin propietario asignado</p>
                                      </div>
                                  </div>
                              </div>           
                          </div>
                        </div>
                      </div>
                    </div>
                    <div
                      class="absolute inset-0 rotate-y-180 [backface-visibility:hidden]"
                      :class="seccionEditando === 'propietario'
                        ? 'z-10 pointer-events-auto'
                        : 'z-0 pointer-events-none'">
                    <transition
                        enter-active-class="transition-all duration-100 ease-out"
                        enter-from-class="opacity-0 max-h-0 translate-y-2"
                        enter-to-class="opacity-100 max-h-[2000px] translate-y-0"
                        leave-active-class="transition-all duration-50 ease-in"
                        leave-from-class="opacity-100 max-h-[2000px]"
                        leave-to-class="opacity-0 max-h-0">
                        <div
                          v-if="seccionEditando === 'propietario'"
                          class="overflow-visible">
                            <div class="rounded-[26px] bg-gradient-to-br from-color1-300/80 via-color1-100/40 to-color1-400/70 p-[2px] shadow-sm shadow-color1-200/60 transition-all duration-300">
                              <div class="bg-white rounded-[24px] px-8 py-6 overflow-visible">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex items-center gap-3 mb-4">
                                      <div class="w-1.5 h-10 rounded-full bg-gradient-to-b from-color1-500 to-color1-700"></div>
                                      <div class="flex flex-col justify-center leading-none">
                                          <p class="text-[10px] uppercase tracking-[0.35em] text-color1-500 font-bold mb-1">
                                              Editar datos
                                          </p>
                                          <h2 class="text-[17px] font-bold tracking-[0.08em] text-color1-700 uppercase">
                                            {{ form.propietario?.curp?.charAt(10) === 'M' ? 'Propietaria' : 'Propietario' }}
                                          </h2>
                                      </div>
                                  </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[1fr_2fr] gap-8 items-end mb-8">            
                                  <div class="relative group w-full">
                                      <input
                                          type="text"
                                          v-model="form.propietario.curp"
                                          :readonly="!esEditable('propietario.curp')"
                                          placeholder=" "
                                          class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm"
                                          :class="[
                                              esEditable('propietario.curp')
                                                  ? 'focus:border-color1-600'
                                                  : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                          ]"/>
                                      <label
                                        :class="[
                                            'absolute text-sm origin-left duration-300',
                                            esEditable('propietario.curp')
                                                ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                                : (
                                                    form.propietario.curp
                                                        ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                        : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                                  )
                                        ]">
                                        CURP
                                    </label>
                                  </div>  
                                  <div class="relative group w-full">
                                      <input
                                          type="text"
                                          v-model="form.propietario.nombre"
                                          :readonly="!esEditable('propietario.nombre')"
                                          placeholder=" "
                                          class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm"
                                          :class="[
                                              esEditable('propietario.nombre')
                                                  ? 'focus:border-color1-600'
                                                  : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                          ]"/>
                                      <label
                                          :class="[
                                              'absolute text-sm origin-left duration-300',
                                              esEditable('propietario.nombre')
                                                  ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                                  : (
                                                      form.propietario.nombre
                                                          ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                          : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                                    )
                                          ]">
                                          Nombre
                                      </label>
                                  </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[1fr] gap-1 items-end mb-8">            
                                    <div class="relative group w-full">
                                      <input
                                          type="text"
                                          v-model="form.propietario.apellidos"
                                          :readonly="!esEditable('propietario.apellidos')"
                                          placeholder=" "
                                          class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm"
                                          :class="[
                                              esEditable('propietario.apellidos')
                                                  ? 'focus:border-color1-600'
                                                  : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                          ]"/>
                                      <label
                                          :class="[
                                              'absolute text-sm origin-left duration-300',
                                              esEditable('propietario.apellidos')
                                                  ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                                  : (
                                                      form.propietario.apellidos
                                                          ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                          : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                                    )
                                          ]">
                                          Apellidos
                                      </label>
                                  </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-[3fr_1fr] gap-8 items-end mb-8">            
                                  <div class="relative group w-full">
                                    <input
                                        type="text"
                                        v-model="form.contacto.email"
                                        :readonly="!esEditable('contacto.email')"
                                        placeholder=" "
                                        class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 lowercase text-sm"
                                        :class="[
                                            esEditable('contacto.email')
                                                ? 'focus:border-color1-600'
                                                : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                        ]"/>

                                    <label
                                        :class="[
                                            'absolute text-sm origin-left duration-300',
                                            esEditable('contacto.email')
                                                ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                                : (
                                                    form.contacto.email
                                                        ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                        : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                                  )
                                        ]">
                                        Correo electrónico
                                    </label>
                                  </div>
                                  <div class="relative group w-full">
                                      <input
                                          type="text"
                                          v-model="form.contacto.telefono"
                                          :readonly="!esEditable('contacto.telefono')"
                                          placeholder=" "
                                          class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 uppercase text-sm"
                                          :class="[
                                              esEditable('contacto.telefono')
                                                  ? 'focus:border-color1-600'
                                                  : 'font-bold text-gray-600 bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                          ]"/>

                                      <label
                                          :class="[
                                              'absolute text-sm origin-left duration-300',
                                              esEditable('contacto.telefono')
                                                  ? 'text-gray-500 transform -translate-y-6 scale-100 top-1 peer-placeholder-shown:scale-90 peer-placeholder-shown:-translate-y-1 peer-focus:scale-90 peer-focus:-translate-y-6 peer-focus:text-color1-600'
                                                  : (
                                                      form.contacto.telefono
                                                          ? 'text-gray-500 -translate-y-6 scale-100 top-1'
                                                          : 'text-gray-500 scale-90 -translate-y-1 top-1'
                                                    )
                                          ]">
                                          Teléfono
                                      </label>
                                  </div>
                                </div>
                                <div class="grid grid-cols-1 gap-1 items-end mb-5">
                                  <div class="relative w-full group">
                                    <div class="relative border-gray-300 transition-colors flex items-end">
                                     <input
                                        type="text"
                                        v-model="form.domicilioNotificacion"
                                        :readonly="!esEditable('domicilioNotificacion')"
                                        placeholder=" "
                                        class="peer w-full block font-bold text-gray-800 px-0 py-1 bg-transparent border-0 border-b-2 border-gray-300 appearance-none focus:outline-none focus:ring-0 text-sm transition-colors"
                                        :class="[
                                          // Si es editable: cambia a color1-600 en focus
                                          esEditable('domicilioNotificacion') 
                                            ? 'focus:border-color1-600' 
                                            // Si NO es editable: forzamos a que el border se mantenga gris (o transparente) incluso en focus
                                            : 'bg-gray-100 rounded-t-lg cursor-default focus:border-gray-300'
                                        ]"/>
                                      <label 
                                        class="absolute text-sm duration-300 origin-left transition-all left-0 pointer-events-none
                                              /* 1. ESTADO BASE (Tiene datos Y NO tiene focus) */
                                              -translate-y-[30px] scale-100 text-gray-500
                                              
                                              /* 2. ESTADO VACÍO (Peer-placeholder-shown) */
                                              peer-placeholder-shown:-translate-y-[10px] 
                                              peer-placeholder-shown:scale-90 
                                              peer-placeholder-shown:text-gray-500
                                              
                                              /* 3. ESTADO FOCUS */
                                              peer-focus:-translate-y-[30px] 
                                              peer-focus:scale-90 
                                              peer-focus:text-color1-600"
                                        :class="{ 
                                          /* 4. EXCEPCIÓN: Si NO es editable */
                                          /* Si tiene datos: Forzamos arriba (30px) y escala 100 */
                                          /* Si NO tiene datos: Forzamos abajo (10px) y escala 90 */
                                          
                                          // Anulamos el efecto del focus con ! para que el label no se mueva al hacer clic
                                          'peer-focus:!-translate-y-[30px] peer-focus:!scale-100 peer-focus:!text-gray-500': !esEditable('domicilioNotificacion') && form.domicilioNotificacion,
                                          'peer-focus:!-translate-y-[10px] peer-focus:!scale-90 peer-focus:!text-gray-500': !esEditable('domicilioNotificacion') && !form.domicilioNotificacion
                                        }">
                                        Domicilio de notificación
                                      </label>
                                    </div>
                                  </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-3">
                                    <button
                                        type="button"
                                        @click="cancelarEditarPropietario"
                                        class="w-36 px-5 py-2.5 rounded-full bg-gray-200 border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-300 transition-all duration-200 hover:scale-[1.02]">
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        @click="aceptarEditarPropietario"
                                        class="w-36 px-5 py-2.5 rounded-full bg-color1-600 hover:bg-color1-700 text-white text-sm font-semibold shadow-color1-200/50 transition-all duration-200 hover:scale-[1.02]">
                                        Aceptar
                                    </button>
                                </div>
                              </div>
                            </div>
                        </div>
                    </transition>
                    </div>
                    <div
                      class="[backface-visibility:hidden] transition-all duration-500"
                      :class="seccionEditando === 'propietario'
                        ? 'opacity-0 -translate-y-2 pointer-events-none mt-0'
                        : 'opacity-100 translate-y-0 mt-6'">
                      <div 
                        class="bg-white rounded-3xl border border-gray-200 shadow-md h-full transition-all duration-300"
                        :class="seccionEditando === 'ubicacion' || seccionEditando === 'tecnica'
                            ? 'opacity-50 pointer-events-none scale-[0.99]'
                            : ''">
                        <div class="p-5" :class="isPage ? 'h-[30vh]' : ''">
                          <div class="flex justify-between items-start mb-3 pl-3">
                            <div class="flex-1">
                              <div class="flex items-center gap-2 text-color1-700 font-bold uppercase text-[11px] tracking-widest">
                                <div class="w-7 h-7 rounded-xl bg-color1-100/70 flex items-center justify-center">
                                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                  </svg>
                                </div>
                                Croquis 
                              </div>
                              <transition
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1">
                                    <div v-if="cambiosCroquis && (seccionEditando != 'ubicacion' && seccionEditando != 'tecnica') " class="flex items-start gap-3">
                                      <div v-if="eliminarCroquisConfirmado || descartarCroquisConfirmado" class="ml-7 inline-flex items-center gap-1
                                              text-[11px] font-medium
                                              text-amber-700 bg-amber-50
                                              px-2 rounded-full w-fit">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                          Pendiente de eliminación
                                      </div>
                                      <div v-else-if="!croquisRestaurado" class="ml-7 inline-flex items-center gap-1
                                                text-[11px] font-medium
                                                text-amber-700 bg-amber-50
                                                px-2 rounded-full w-fit">
                                          <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                            Pendiente de guardar 
                                      </div>
                                    </div>
                                </transition>
                            </div>
                            <button v-if="seccionEditando != 'ubicacion' && seccionEditando != 'tecnica' && props.tipoTramite != 'constancia-de-numero-oficial' "     
                              @click="abrirEditarCroquis"
                              class="px-4 py-2 bg-color1-600 hover:bg-color1-700 text-white text-[10px] font-semibold rounded-full uppercase tracking-widest transition-all duration-300 transform hover:scale-105 shadow-sm">
                              {{ form.imgCroquis ? 'Cambiar' : 'Agregar' }}
                            </button>
                          </div>             
                          <div class="space-y-1">
                          <div class="rounded-lg p-3 bg-gray-50/50">
                              <div v-if="form.imgCroquis || previewCroquis" class="flex gap-3 items-center">
                                  
                                  <div class="relative flex-shrink-0 bg-gradient-to-br from-color1-50 to-color1-40 rounded-2xl border border-color1-100 shadow-sm overflow-hidden transition-all duration-300"
                                      :class="[
                                          seccionEditando === 'ubicacion' || seccionEditando === 'tecnica' ? 'w-full h-24' : '',
                                          seccionEditando !== 'ubicacion' && seccionEditando !== 'tecnica' && isPage ? 'w-72 h-24' : '',
                                          seccionEditando !== 'ubicacion' && seccionEditando !== 'tecnica' && !isPage ? 'w-20 h-20' : ''
                                      ]">
                                      
                                      <div v-if="cargandoCroquis"
                                          class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px] z-10">
                                          <div class="w-6 h-6 border-2 border-color1-200 border-t-color1-600 rounded-full animate-spin"></div>
                                      </div>

                                      <img :src="previewCroquis || `/storage/croquis/${form.imgCroquis}`"
                                          :key="previewCroquis || form.imgCroquis"
                                          :alt="form.imgCroquis"
                                          @load="cargandoCroquis = false"
                                          @error="cargandoCroquis = false"
                                          class="object-cover transition-all duration-300"
                                          :class="[
                                              cargandoCroquis ? 'opacity-0' : 'opacity-100',
                                              seccionEditando === 'tecnica' ? 'w-full h-24 scale-200' : 'w-full h-full',
                                              seccionEditando !== 'tecnica' && isPage ? 'scale-125' : ''
                                          ]">  
                                  </div>                           
                                  
                                  <div v-if="seccionEditando != 'ubicacion' && seccionEditando != 'tecnica'" 
                                      class="flex-1 flex items-center gap-3 transition-all duration-300"
                                      :class="isPage ? 'ml-3 max-w-[180px]' : 'ml-6'">
                                      
                                      <button @click="verCroquis()"
                                              class="group flex-1 inline-flex items-center justify-center gap-2
                                                    rounded-full bg-color2-700 hover:bg-color2-800
                                                    hover:border-color2-700 border border-color2-600
                                                    text-white text-[13px] font-semibold
                                                    transition-all duration-200 hover:scale-[1.02] active:scale-95"
                                              :class="isPage ? 'px-5 py-2' : 'px-3 py-1.5'">
                                          <svg class="w-4 h-4 transition-transform duration-200 group-hover:scale-110"
                                              fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                          </svg>
                                          <div class="leading-tight text-left">
                                              <div>Ver</div>
                                          </div>
                                      </button>

                                      <button v-if="props.tipoTramite != 'constancia-de-numero-oficial'" @click="confirmarEliminarCroquis(!croquisSeleccionado)"
                                              class="group flex-1 inline-flex items-center justify-center gap-2
                                                    rounded-full bg-white hover:bg-color1-40
                                                    border border-gray-200 hover:border-color1-600
                                                    text-gray-500 hover:text-color1-600 text-[13px] font-semibold
                                                    transition-all duration-200 active:scale-95"
                                              :class="isPage ? 'px-5 py-2' : 'px-4 py-1.5'">
                                          <svg class="w-4 h-4 transition-transform duration-200"
                                              fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                          </svg>
                                          <div class="leading-tight text-left">
                                              <div v-if="croquisSeleccionado && !croquisRestaurado">Descartar</div>
                                              <div v-else>Eliminar</div>
                                          </div>
                                      </button>
                                  </div>
                              </div>
                              <div v-else class="flex gap-3 items-start">
                                  <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl border border-gray-200 shadow-sm flex flex-col items-center justify-center">
                                      <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                      </svg>
                                  </div>
                                  <div v-if="(eliminarCroquisConfirmado || descartarCroquisConfirmado) && cambiosCroquis">
                                      <div class="flex-1 items-start gap-3 ml-2">
                                          <div class="inline-flex items-center px-0 py-1 rounded-xl border border-amber-50">
                                              <p class="text-xs text-gray-500 leading-relaxed text-justify">
                                                  La propiedad quedará sin un croquis registrado cuando guardes los cambios.
                                              </p>
                                          </div>                                    
                                      </div>
                                  </div>
                                  <div v-else class="flex-1 text-center py-4 truncate">
                                      <p class="text-gray-500 text-sm truncate">No hay croquis cargado</p>
                                  </div>
                              </div>
                          </div>   
                          </div>
                        </div>        
                      </div>
                    </div>
                    <div class="absolute inset-0 rotate-y-180 [backface-visibility:hidden]">
                      <transition
                        enter-active-class="transition-all duration-100 ease-out"
                        enter-from-class="opacity-0 max-h-0 translate-y-2"
                        enter-to-class="opacity-100 max-h-[2000px] translate-y-0"
                        leave-active-class="transition-all duration-50 ease-in"
                        leave-from-class="opacity-100 max-h-[2000px]"
                        leave-to-class="opacity-0 max-h-0">
                        <div
                        v-if="seccionEditando === 'croquis'"
                        class="overflow-visible">
                          <div class="rounded-[26px] bg-gradient-to-br from-color1-300/80 via-color1-100/40 to-color1-400/70 p-[2px] shadow-sm shadow-color1-200/60 transition-all duration-300">
                            <div class="bg-white rounded-[24px] px-8 pt-6 pb-4 overflow-visible">
                              <div class="flex items-center gap-3 mb-4">
                                <div class="w-1.5 h-10 rounded-full bg-gradient-to-b from-color1-500 to-color1-700"></div>
                                <div class="flex flex-col justify-center leading-none">
                                    <p class="text-[10px] uppercase tracking-[0.35em] text-color1-500 font-bold mb-1">
                                        Cambiar archivo
                                    </p>
                                    <h2 class="text-[17px] font-bold tracking-[0.08em] text-color1-700 uppercase">
                                        Croquis
                                    </h2>
                                </div>
                              </div>
                              <div v-if="!previewCroquis" class="mb-6 rounded-2xl border border-color1-100 bg-color1-50 px-4 py-4">
                                <div class="flex gap-3 items-start">
                                  <div class="mt-0.5 text-color1-700">
                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                      </svg>
                                  </div>
                                  <div v-if="!descartarCroquisConfirmado && !eliminarCroquisConfirmado">
                                      <p class="text-sm uppercase tracking-[0.1em] font-semibold text-color1-700 mb-1">
                                         ¡¡ Importante !!
                                      </p>
                                      <p class="text-xs text-color1-700 leading-relaxed">
                                         Carga un nuevo archivo si deseas reemplazar el croquis actual.
                                         Recuerda guardar los cambios más adelante para confirmar la actualización.
                                      </p>
                                  </div>
                                  <div v-if="!descartarCroquisConfirmado && eliminarCroquisConfirmado">
                                      <p class="text-sm uppercase tracking-[0.1em] font-semibold text-color1-700 mb-1">
                                         Agregar croquis
                                      </p>
                                      <p class="text-xs text-color1-700 leading-relaxed">
                                          Selecciona un archivo para asociar un croquis a esta propiedad.
                                      </p>
                                  </div>
                                  <div v-if="descartarCroquisConfirmado">
                                    <p class="text-sm uppercase tracking-[0.1em] font-semibold text-color1-700 mb-1">
                                        Selecciona un nuevo croquis
                                    </p>
                                    <p class="text-xs text-color1-700 leading-relaxed">
                                        El nuevo croquis reemplazará automáticamente el archivo anterior al guardar los cambios.
                                    </p>
                                  </div>                                  
                                </div>
                              </div>
                              <div v-if="previewCroquis"
                                class="mb-6 rounded-2xl border border-color2-200 bg-color2-50 px-4 py-4">
                                <div class="flex gap-3 items-start">
                                    <div class="mt-0.5 text-color2-600 shrink-0">
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div v-if="croquisRestaurado">
                                      <p class="text-sm uppercase tracking-[0.1em] font-semibold text-color2-700 mb-1">
                                          Croquis original restaurado
                                      </p>

                                      <p class="text-xs text-emerald-700 leading-relaxed">
                                          Actualmente se encuentra activo el croquis almacenado originalmente en el sistema.
                                      </p>
                                    </div>
                                    <div v-else class="min-w-0">
                                        <p class="text-sm uppercase tracking-[0.1em] font-bold text-color2-800 mb-1">
                                            Nuevo croquis
                                        </p>
                                        <p class="text-xs text-color2-700 leading-relaxed">
                                            {{
                                                conCroquisInicial
                                                    ? 'El nuevo archivo reemplazará el croquis actual una vez que guardes los cambios.'
                                                    : 'La propiedad tendrá este croquis asociado una vez que guardes los cambios.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                              </div>
                              <div
                                @dragover.prevent="dragging = true"
                                @dragleave="dragging = false"
                                @drop.prevent="onDropCroquis"
                                class="relative rounded-[28px] border-2 border-dashed transition-all duration-300 overflow-hidden"
                                :class="dragging
                                  ? 'border-color1-500 bg-color1-50 scale-[1.01]'
                                  : 'border-gray-300 bg-gray-50 hover:border-color1-300 hover:bg-color1-50/40'">
                                <input
                                    type="file"
                                    ref="inputCroquis"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onCroquisSelected">
                                <div
                                    v-if="!previewCroquis"
                                    @click="inputCroquis.click()"
                                    class="cursor-pointer px-8 py-6 flex flex-col items-center justify-center text-center">
                                    <div class="w-16 h-16 rounded-2xl bg-white shadow-sm border border-gray-200 flex items-center justify-center mb-5">
                                          <svg class="w-8 h-8 text-color1-500 dark:text-color1-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                              <path
                                                  stroke="currentColor"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                          </svg>
                                    </div>
                                    <p class="text-sm font-bold text-color1-700 mb-0 uppercase tracking-wide">
                                        Arrastra tu croquis aquí
                                    </p>
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        o haz clic para seleccionar un archivo
                                    </p>
                                    <p class="mt-4 text-[10px] uppercase tracking-[0.25em] text-gray-400">
                                        JPG • JPEG • PNG
                                    </p>
                                </div>
                                <div
                                  v-else
                                  class="relative group">
                                  <div v-if="!croquisRestaurado" class="absolute top-4 right-4 z-10 flex gap-2">
                                    <button v-if="conCroquisInicial"
                                        type="button"
                                        @click="restaurarCroquisOriginal"
                                        class="group px-4 h-9 rounded-full
                                              bg-white/90 backdrop-blur-md
                                              border border-white/40
                                              text-gray-700 text-[11px] uppercase tracking-wider font-bold
                                              shadow-[0_8px_30px_rgba(0,0,0,0.12)]
                                              hover:bg-white hover:shadow-xl
                                              transition-all duration-200
                                              active:scale-95
                                              flex items-center gap-2">

                                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" 
                                              viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                              class="lucide lucide-undo-icon lucide-undo">
                                              <path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>
                                              </svg>
                                        <span>
                                            Restaurar Original
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmarEliminarCroquis(false)"
                                        class="group px-4 h-9 rounded-full
                                              bg-white/90 backdrop-blur-md
                                              border border-white/40
                                              text-color1-500 text-[11px] uppercase tracking-wider font-bold
                                              shadow-[0_8px_30px_rgba(0,0,0,0.12)]
                                              hover:bg-red-50 hover:shadow-xl
                                              transition-all duration-200
                                              active:scale-95
                                              flex items-center gap-2">                                        
                                        <svg xmlns="http://www.w3.org/2000/svg" 
                                        width="18" height="18" 
                                        viewBox="0 0 24 24" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round" 
                                        class="lucide lucide-x-icon lucide-x">
                                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                                        </svg>
                                        <span>
                                            Descartar
                                        </span>
                                    </button>
                                </div>
                                  <div
                                    @click="abrirZoomCroquis = true; zoomPreviewCroquis = 2; desdePanel = false"
                                    class="relative overflow-hidden rounded-[26px] cursor-zoom-in">
                                    <img
                                      :src="previewCroquis"
                                      class="w-full h-[170px] object-cover transition-transform duration-500 group-hover:scale-105">
                                    <div
                                      class="absolute inset-0 bg-gradient-to-t
                                            from-black/50 via-black/10 to-transparent
                                            opacity-0 group-hover:opacity-100
                                            transition-all duration-300
                                            flex items-end justify-center p-5">
                                      <div
                                        class="px-4 py-2 rounded-full bg-white/90 backdrop-blur
                                              text-[11px] uppercase tracking-wider
                                              font-bold text-color1-700 shadow-md">
                                        Click para ampliar
                                      </div>
                                    </div>
                                  </div>

                                  <div class="px-5 py-4 bg-white border-t border-gray-100 flex items-center justify-between">
                                    <div class="min-w-0">
                                      <div v-if="croquisRestaurado" class="flex items-center gap-2">
                                          <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                          <p class="text-sm font-semibold text-gray-800 truncate">
                                              Croquis original del sistema
                                          </p>
                                      </div>
                                      <p v-else class="text-sm font-semibold text-gray-800 truncate flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-color1-400 inline-block rounded-full"></span>
                                        {{ archivoCroquis?.name }} 
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="flex items-center justify-end gap-3 pt-6">
                                  <button v-if="!(croquisRestaurado && !eliminarCroquisConfirmado)"
                                      type="button"
                                      @click="cancelarEditarCroquis"
                                      class="w-36 px-5 py-2.5 rounded-full bg-gray-200 border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-300 transition-all duration-200 hover:scale-[1.02]">
                                      Cancelar 
                                  </button>
                                  <button
                                    v-if="!croquisRestaurado || (croquisRestaurado && !eliminarCroquisConfirmado)"
                                    type="button"
                                    @click="aceptarEditarCroquis"
                                    :disabled="!archivoCroquis"
                                    :class="!archivoCroquis
                                        ? 'bg-color1-200 text-white cursor-not-allowed'
                                        : 'bg-color1-600 hover:bg-color1-700 text-white hover:scale-[1.02] shadow-color1-200/50'"
                                    class="w-36 px-5 py-2.5 rounded-full text-sm font-semibold transition-all duration-200">
                                    Aceptar
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </transition>
                    </div>
                </div>
              </div>
              <div
                  v-if="(!seccionEditando && hayDatosIncompletos) || (!seccionEditando && tieneCambiosPendientes)"
                  class="fixed left-1/2 -translate-x-1/2 bg-white border border-gray-200 shadow-2xl z-10 flex items-center transition-all duration-300 backdrop-blur-xs animate-fade-in-up
                        w-full" 
                  :class="[
                      hayDatosIncompletos && tieneCambiosPendientes ? 'max-w-4xl' : 'max-w-3xl',
                      desdeTramite 
                          ? 'bottom-6 lg:left-[calc(50%+128px)] rounded-2xl px-8 py-4 gap-6' 
                          : 'bottom-4 rounded-3xl px-8 py-4 gap-4'
                  ]">
                  <div class="min-w-0">
                      <p v-if="hayDatosIncompletos && tieneCambiosPendientes" class="text-md font-bold text-gray-900 flex items-center gap-1.5">
                          <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                          Tienes cambios pendientes y datos por completar
                      </p>
                      <p v-else-if="hayDatosIncompletos && !tieneCambiosPendientes" class="text-md font-bold text-gray-900 flex items-center gap-1.5">
                          <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                          Tienes datos por completar
                      </p>
                      <p v-else class="text-md font-bold text-gray-900 flex items-center gap-1.5">
                          <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                          Tienes cambios pendientes
                      </p>
                    <div class="text-xs text-gray-500 mt-0.5 leading-normal">
                        <template v-if="hayDatosIncompletos && !tieneCambiosPendientes">
                            <div v-if="props.tipoTramite == 'constancia-de-numero-oficial'" class="flex flex-col gap-1.5">
                                <span class="font-medium text-gray-700">Para continuar con el trámite, es obligatorio registrar la siguiente información:</span>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-0.5">
                                    <div v-if="!form.codigoPostal || !form.referenciasUbicacion" class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">UBICACIÓN:</span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="!form.codigoPostal" class="px-1.5 py-0.5 bg-color1-50 text-color1-700 rounded-md font-medium text-[10px] uppercase border border-color1-100">
                                                Código Postal
                                            </span>
                                            <span v-if="!form.referenciasUbicacion" class="px-1.5 py-0.5 bg-color1-50 text-color1-700 rounded-md font-medium text-[10px] uppercase border border-color1-100">
                                                Referencias
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="!form.coordenadaUtmX  || !form.coordenadaUtmY " class="flex items-center gap-1.5 border-l border-gray-200 pl-3 first:border-0 first:pl-0">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">INFORMACIÓN TÉCNICA:</span>
                                        <div class="flex items-center gap-1">
                                            <span v-if="!form.coordenadaUtmX" class="px-1.5 py-0.5 bg-color1-50 text-color1-700 rounded-md font-medium text-[10px] uppercase border border-color1-100">
                                                COORD. UTM X
                                            </span>
                                            <span v-if="!form.coordenadaUtmY" class="px-1.5 py-0.5 bg-color1-50 text-color1-700 rounded-md font-medium text-[10px] uppercase border border-color1-100">
                                                COORD. UTM Y
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                       <template v-else>
                        <span v-if="hayDatosIncompletos && tieneCambiosPendientes">
                          {{ desdeTramite 
                              ? 'Al guardar asegurarás tus cambios actuales, pero recuerda que aún faltan campos obligatorios para continuar el trámite.' 
                              : 'Los cambios se aplicarán en el sistema, pero el expediente de la propiedad permanecerá en estado incompleto.' 
                          }}
                        </span>
                        <span v-else>
                          {{ desdeTramite 
                              ? 'Cualquier acción te regresará de forma automática al trámite en progreso.' 
                              : 'Las modificaciones realizadas aún no se han aplicado definitivamente.' 
                          }}
                        </span>
                      </template>
                    </div>
                  </div>
                  <div class="flex items-center gap-4 shrink-0"
                  :class="hayDatosIncompletos && tieneCambiosPendientes ? 'ml-10' : ''">
                      <template v-if="tieneCambiosPendientes">
                          <button 
                              @click="confirmarDescarte"
                              class="px-5 py-2 text-sm font-semibold rounded-full border border-gray-200 text-gray-600 bg-white
                                    transition-all duration-200 ease-in-out
                                    hover:bg-gray-50 hover:-translate-y-0.5
                                    active:translate-y-0 active:scale-95">
                              Descartar cambios
                          </button>

                          <button 
                              @click="confirmarGuardado" 
                              class="px-5 py-2 text-sm font-bold rounded-full bg-color1-600 text-white shadow-sm
                                    transition-all duration-200 ease-in-out
                                    hover:bg-color1-700 hover:shadow-md hover:-translate-y-0.5
                                    active:translate-y-0 active:scale-95">
                              Guardar cambios
                          </button>
                      </template>

                      <template v-else>
                      </template>
                  </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </form>
  </div>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95">

    <div
      v-if="previewTemporalCroquis"
      class="fixed inset-0 z-[999] bg-black/70 backdrop-blur-sm flex items-center justify-center p-6">
        <div class="relative w-full max-w-4xl max-h-[92vh] rounded-[32px] bg-white shadow-2xl overflow-hidden">
        <div class="absolute top-5 right-5 z-20 flex items-center gap-2">
          <button
            @click="zoomPreviewCroquis = Math.max(1, zoomPreviewCroquis - 0.1)"
            :disabled="zoomPreviewCroquis === 1"
            :class="zoomPreviewCroquis === 1
                  ? 'bg-gray-100 text-gray-200 cursor-not-allowed'
                  : 'bg-black/50 text-white hover:bg-black/70 text-white bg-black/50 hover:bg-black/70'"
            class="w-10 h-10 rounded-full 
                   text-xl transition-all duration-200
                  flex items-center justify-center hover:scale-105">
            -
          </button>
          <div
              class="min-w-[72px] h-10 px-4 rounded-full
                    bg-color1-40 backdrop-blur
                    text-color1-500 text-xs font-bold tracking-wide
                    flex items-center justify-center">

              {{ Math.round(zoomPreviewCroquis * 100) }}%
          </div>
          <button
            @click="zoomPreviewCroquis = Math.min(6, zoomPreviewCroquis + 0.1)"
            class="w-10 h-10 rounded-full bg-black/50 hover:bg-black/70
                  text-white text-xl transition-all duration-200
                  flex items-center justify-center hover:scale-105">
            +
          </button>
          <button
              @click="zoomPreviewCroquis = 1"
              :disabled="zoomPreviewCroquis === 1"
              :class="zoomPreviewCroquis === 1
                  ? 'bg-white/10 text-gray-300 cursor-not-allowed'
                  : 'bg-black/50 hover:bg-black/70 text-white'"
              class="px-4 h-10 rounded-full text-[11px] uppercase tracking-wider font-semibold transition-all duration-200">
              
              Tamaño Original
          </button>
          <button
            @click="cancelarCroquisTemporal"
            class="w-8 h-8 rounded-full hover:bg-gray-200 ml-10
                  text-gray-600 text-md transition-all duration-200
                  flex items-center justify-center hover:scale-105">
            ✕
          </button>
        </div>

        <div class="px-8 py-6 border-b border-gray-100">
          <p class="text-[10px] uppercase tracking-[0.35em] text-color1-500 font-bold mb-1">
            Vista preliminar
          </p>

          <h2 class="text-xl font-bold text-color1-700 uppercase tracking-wide">
            Croquis seleccionado
          </h2>
        </div>

        <div class="bg-gray-100 h-[65vh] overflow-hidden rounded-b-2xl">
          <div
            class="w-full h-full overflow-auto p-6 flex transition-all duration-300"
            @wheel.prevent="manejarZoomRueda"
            :class="[
              zoomPreviewCroquis > 1 
                ? 'items-start justify-start' 
                : 'items-center justify-center cursor-default'
            ]">
            <div 
              class="min-w-max min-h-max flex items-center justify-center"
              :class="{ 'm-auto': zoomPreviewCroquis > 1 }">
              
              <img
                :src="previewTemporalCroquis"
                draggable="false"
                :style="{
                  transformOrigin: 'top left',
                  transform: zoomPreviewCroquis > 1 ? `scale(${zoomPreviewCroquis})` : 'none',
                  
                  maxWidth: zoomPreviewCroquis === 1 ? '100%' : 'none',
                  maxHeight: zoomPreviewCroquis === 1 ? '58vh' : 'none'
                }"
                  class="object-contain rounded-2xl shadow-lg select-none transition-transform duration-300 ease-out"
                @error="(e) => { 
                  e.target.src = '/images/placeholder-error.png'; 
                  e.target.classList.add('opacity-40');
                }">
            </div>
          </div>
        </div>
        
        <div class="px-8 py-5 flex justify-end gap-3">
        <button
          @click="cancelarCroquisTemporal"
          class="w-40 px-5 py-3 rounded-full
                bg-gray-100 hover:bg-gray-200
                text-gray-700 text-sm hover:font-bold
                transition-all duration-200">

          Cancelar
        </button>
        <button
            @click="confirmarCroquisTemporal"
            class="w-40 px-5 py-3 rounded-full
                  bg-color1-600 hover:bg-color1-700
                  text-white text-sm font-semibold
                  transition-all duration-200
                  flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
            <span>
                Usar Croquis
            </span>
        </button>
      </div>
    </div>
  </div>
</Transition>
<div v-if="abrirZoomCroquis" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
  <div class="relative w-full max-w-5xl bg-white rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[80vh]">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50 shrink-0 select-none">
        <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 shrink-0 select-none">
          <div class="flex items-center gap-3 mr-6 select-none">
              <div class="p-2 bg-color1-50 text-color1-700 rounded-xl hidden sm:block shadow-inner">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                  </svg>
              </div>
              <div>
                  <h3 v-if="desdePanel && !croquisSeleccionado" class="text-md font-black text-color1-700 tracking-[0.1em] uppercase">
                      Croquis Registrado
                  </h3>
                  <h3 v-else class="text-md font-black text-color1-700 tracking-[0.1em] uppercase">
                      Croquis Seleccionado
                  </h3>
                  <p class="text-xs text-gray-500 font-medium transition-all duration-200">
                      Usa los controles de zoom para ampliar los detalles
                  </p>
              </div>
          </div>
          <div class="flex items-center gap-1.5 bg-white border border-gray-200 p-1 rounded-xl shadow-sm">
              <button 
                  type="button" 
                  @click="ajustarZoom(-0.4)" 
                  :disabled="zoomPreviewCroquis <= 1" 
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition-all"
                  title="Reducir Zoom">
                  <span class="text-lg font-bold">-</span>
              </button>

              <div class="px-2.5 py-1 bg-gray-50 rounded-md border border-gray-100 min-w-[55px] text-center">
                  <span class="text-xs font-bold text-gray-700">
                      {{ (zoomPreviewCroquis * 100).toFixed(0) }}%
                  </span>
              </div>

              <button 
                  type="button" 
                  @click="ajustarZoom(0.4)" 
                  :disabled="zoomPreviewCroquis >= 4" 
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition-all"
                  title="Aumentar Zoom">
                  <span class="text-lg font-bold">+</span>
              </button>
              <div class="w-px h-5 bg-gray-200 mx-1"></div>
              <button 
                  type="button" 
                  @click="ajustarZoom(1)" 
                  :disabled="zoomPreviewCroquis === 1"
                  class="px-2.5 h-8 flex items-center justify-center rounded-lg text-[11px] font-bold tracking-wide uppercase transition-all"
                  :class="[zoomPreviewCroquis === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-color1-600 hover:bg-color1-50 hover:text-color1-700']">
                  Tamaño Original
              </button>
          </div>
      </div>
        <button 
            @click="abrirZoomCroquis = false" 
            type="button"
            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-95"
            title="Cerrar ventana">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
      <div
          class="flex-1 overflow-auto p-0 bg-color1-900 flex items-center justify-center relative select-none custom-scrollbar">
          <div 
              class="inline-block transition-transform duration-150 ease-out origin-center"
              :style="{ transform: `scale(${zoomPreviewCroquis})` }">
              
              <img
                  :src="previewCroquis"
                  draggable="false"
                  class="max-h-[50vh] max-w-full w-auto h-auto object-contain select-none rounded shadow-lg"
                  @error="manejarErrorImagen">
          </div>
      </div>
      <div  class="px-5 sm:px-7 py-4 bg-white border-t border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 shrink-0">
            <div :class="desdePanel ? 'w-[550px]' : 'min-w-[240px]'">
                <div v-if="!desdePanel" class="flex items-start gap-3">
                  <div
                        class="w-10 h-10 rounded-2xl bg-color1-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 text-color1-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">
                            Propiedad
                        </p>
                        <p class="text-xs font-bold text-gray-800 truncate">
                            {{ form.claveCatastral }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ formatearDireccionObjetos(form).principal }},
                            {{ formatearDireccionObjetos(form).localidad }}
                        </p>
                  </div>
                </div>
                <div v-else class="flex items-start gap-3 items-center">
                  <div class="w-10 h-10 rounded-2xl bg-color1-50 flex items-center justify-center shrink-0">
                      <svg xmlns="http://www.w3.org/2000/svg"
                          class="w-5 h-5 text-color1-600"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor">

                          <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                  </div>
                  <div v-if="!croquisSeleccionado" class="min-w-0">
                      <p class="text-xs text-gray-600 leading-relaxed">
                          Actualmente esta propiedad cuenta con este croquis registrado en el sistema. 
                          
                          <span v-if="props.tipoTramite !== 'constancia-de-numero-oficial'">
                              Si deseas actualizarlo, debes seleccionar el botón de <b> Cambiar Croquis </b>.
                          </span>
                      </p>
                  </div>
                  <div v-else class="min-w-[800px] items-center">
                      <p class="text-xs text-gray-600 leading-relaxed">
                          Para actualizar el croquis en esta propiedad será necesario guardar los cambios más adelante.
                      </p>
                  </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-6">
              <button
                  v-if="desdePanel && !croquisSeleccionado && props.tipoTramite !== 'constancia-de-numero-oficial'"
                  type="button"
                  @click="seccionEditando='croquis'; previewCroquis = null; abrirZoomCroquis = false"
                  class="group px-6 py-2.5 rounded-full bg-color1-700 hover:bg-color1-600 hover:font-bold text-white transition-all duration-200 active:scale-95 shadow-lg shadow-color2-900/15 flex items-center justify-center gap-2 min-w-[220px]">
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 transition-transform duration-200 group-hover:scale-110"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor">

                      <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" />
                  </svg>
                  <span class="text-sm">
                      Cambiar Croquis
                  </span>
              </button>
              <button
                    type="button"
                    @click="abrirZoomCroquis = false; previewCroquis = false"
                    class="px-10 py-2.5 rounded-full text-sm bg-gray-300 hover:bg-gray-400 hover:font-bold text-gray-800 transition-all active:scale-95">
                    Cerrar
                </button>
            </div>
      </div>
  </div>  
</div>
</template>
<style>
  /* Sin el atributo scoped para que alcance al body */
  .el-popper.is-customized {
    padding: 6px 12px;
    background: #ffffff !important;
    color: #1a1a1a !important; /* Color del texto */
    border: none !important;
    font-size: 8pt !important;
  }

  .el-popper.is-customized .el-popper__arrow::before {
    background: #ffffff !important; /* Un solo color sólido suele funcionar mejor en la flecha */
  }

  .el-date-table td.current .el-date-table-cell__text {
    color: #ffffff !important;
}
</style>
<style scoped>
  /* Animación de desvanecimiento para el fondo */
  .fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
  .fade-enter-from, .fade-leave-to { opacity: 0; }

  /* Animación de deslizamiento (Right to Left) */
  .slide-rtl-enter-active, .slide-rtl-leave-active {
      transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .slide-rtl-enter-from, .slide-rtl-leave-to {
      transform: translateX(100%);
  }
</style>

<style scoped>
  .custom-datepicker label {
    top: 11px !important; /* Bajamos el label un poco para nivelarlo con el otro */
  }

  .custom-datepicker :deep(.el-input) {
    vertical-align: baseline !important;
  }

  .custom-datepicker:focus-within label {
      color: var(--color-1-1) !important;
      transform: translateY(-1.5rem) scale(0.9) !important;
  }

  input[type="checkbox"]:focus:checked {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
  }

  input[type="checkbox"]:focus:not(:checked) {
    background-color: #ffffff !important;
    border-color: #d1d5db !important; 
  }

  input[type="checkbox"]:hover:checked {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
  }

  .custom-scrollbar {
      scrollbar-gutter: stable; /* Esto evita que el contenido "salte" cuando aparece el scrollbar */
  }

  form {
      height: 700px; /* Ajusta este valor a la altura deseada de tu trámite */
      max-height: 90vh;
  }

  .custom-scrollbar::-webkit-scrollbar {
    width: 8px;
  }
  .custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
  }
  .vertical-text {
    /* Escribe el texto de arriba hacia abajo */
    writing-mode: vertical-rl;
    /* Lo gira 180 grados para que sea legible desde la derecha */
    transform: rotate(180deg);
    white-space: nowrap;
  }

  /* Animación de entrada para los elementos */
  .animate-fade-in {
    animation: fadeIn 0.05s ease-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes soft-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
  }
  .animate-soft-pulse {
    animation: soft-pulse 2s infinite ease-in-out;
  }
</style> 

<style>
  /* Usamos la clase padre para no romper otros componentes del sistema */
  .custom-datepicker.el-date-editor {
    height: 24px !important; /* Altura total reducida */
  }

  /* 2. AJUSTAR EL WRAPPER PARA QUE NO LO APLASTE */
.custom-datepicker .el-input__wrapper {
    background-color: transparent !important;
    box-shadow: none !important;
    border-bottom: transparent !important; /* Línea invisible */

}

/* 3. ASEGURAR QUE EL INPUT NO TAPE AL ICONO */
.custom-datepicker .el-input__inner {
    flex-grow: 1 !important;
    padding-right: 0 !important;
    text-align: left;
}

  /* Quita el resplandor azul (box-shadow) en todos los estados */
.custom-datepicker .el-input__wrapper,
.custom-datepicker .el-input__wrapper:hover,
.custom-datepicker .el-input__wrapper.is-focus {
    box-shadow: none !important;
    outline: none !important;
    /* Anulamos la variable de color de enfoque de Element Plus */
    --el-input-focus-border-color: transparent !important;
    --el-input-hover-border-color: #d1d5db !important; /* Mantiene el color gris al pasar el mouse */
}

/* Quita el borde azul que aparece en Chrome/Safari por accesibilidad */
.custom-datepicker .el-input__inner:focus {
    outline: none !important;
    box-shadow: none !important;
}

.custom-datepicker .el-input__suffix {
    display: flex !important;
    align-items: flex-end !important;
    margin-bottom: 2px !important; /* Ajuste fino para nivelar con el texto */
    color: #9ca3af !important; /* text-gray-400 */
}

.custom-datepicker .el-input__icon {
    font-size: 14px !important;
    color: #9ca3af !important; /* Gris suave para que no distraiga */
}
</style>

<style scoped>
/* 1. Estilo base para tu datepicker (opcional) */
:deep(.custom-datepicker .el-input__inner) {
  color: #111827; /* text-gray-600 */
}

/* 2. Cuando tenga la clase base Y ADEMÁS esté deshabilitado por Element Plus */
:deep(.custom-datepicker.is-disabled .el-input__inner) {
  color: #111827 !important;
  -webkit-text-fill-color: #111827 !important;
  cursor: not-allowed !important;
  font-weight: bold !important;
}

  :deep(.custom-datepicker.is-disabled .el-input__wrapper) {
    border-bottom: none !important; 
  }
</style>

<style scoped>
/* 1. Resetear el Wrapper (el contenedor gris/azul de Element) */
.custom-datepicker :deep(.el-input__wrapper) {
  display: flex !important;
  padding: 0 !important; /* Cero espacio interno */
  background: transparent !important;
  box-shadow: none !important;
  justify-content: flex-start !important;
  align-items: flex-end !important;
  gap: 0 !important; /* Elimina espacio automático entre icono y texto */
}

/* 2. Forzar que el prefijo no ocupe más de lo necesario */
.custom-datepicker :deep(.el-input__prefix) {
  margin: 0 !important;
  padding: 0 !important;
  display: flex !important;
  width: 18px !important; /* Ancho fijo mínimo para el icono */
  justify-content: flex-start !important;
  color: #2a2c2f !important;
}

/* 3. El SVG: Ajustar tamaño para que no robe espacio */
.custom-datepicker :deep(.el-input__icon) {
  width: 16px !important;
  height: 16px !important;
  margin: 0 !important;
}

/* 4. EL INPUT: Aquí ganamos el espacio para el '6' */
.custom-datepicker :deep(.el-input__inner) {
  flex: 1 !important;
  width: 100% !important;
  padding: 0 !important;
  margin: 0 0 0 2px !important; /* Solo 2px de separación con el icono */
  text-align: left !important;
  font-size: 13px !important;
  height: 24px !important;
  /* Forzamos que el texto use todo el ancho disponible */
  min-width: 0 !important; 
}

/* 5. ELIMINAR EL BOTÓN DE LIMPIAR SI APARECE (Suffix) */
/* A veces Element Plus reserva espacio a la derecha para el ícono de 'X' */
.custom-datepicker :deep(.el-input__suffix) {
  display: none !important; 
}
</style>

<style>
@keyframes loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
</style>

<style scoped>
/* Estilos del Flip Card */
.flip-card {
    background-color: var(--color1-700) !important;
    perspective: 1000px;
    min-height: auto;
}

.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.6s;
    transform-style: preserve-3d;
    background-color: var(--color1-700) !important;
}

.flip-card.flipped .flip-card-inner {
    transform: rotateY(180deg);
}

.flip-card-front, .flip-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

.flip-card-front {
    background-color: var(--color1-700);
    color: white;
}

.flip-card-back {
    background-color: white;
    transform: rotateY(180deg);
    overflow-y: auto;
}

/* Scroll personalizado */
.flip-card-back::-webkit-scrollbar {
    width: 6px;
}

.flip-card-back::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.flip-card-back::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.flip-card-back::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Animaciones */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-3px);
}
</style>

<style scoped>
/* Estilizado elegante para el viewport del mapa de bits */
.custom-scrollbar::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #a0a0a0; /* Combinación perfecta con bg-gray-950 */
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #374151; /* Color gris oscuro elegante discreto */
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #4b5563;
}
</style>