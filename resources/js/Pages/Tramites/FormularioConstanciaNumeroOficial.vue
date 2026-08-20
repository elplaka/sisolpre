<script setup>
  import { ref, onMounted, onUnmounted, watch, computed, nextTick, resolveTransitionHooks } from 'vue';
  import { useForm, router, usePage } from '@inertiajs/vue3'; // Asumiendo que usas Inertia como en tu package.json
  import axios from 'axios';
  
  const props = defineProps({
      initialData: {
          type: Object,
          default: () => ({})
      },
      isPage: {
          type: Boolean,
          default: false
      },
      destinatarios: Array,
      selectedTramite: Object,
      documentoData: Object,
      todosEstatus: Array,  //Ando jalando los estatus de la base de datos para de ahí obtener los colores
  });

  const isSavingModal = ref(false)
  const isLoadingModal = ref(false)
  const isProcessingModal = ref(false)
  const isSavingGeo = ref(false)
  const isDocOpen = ref(false); // Inicialmente cerrado
  const documentacion = ref([])
  const registroCreado = ref(false)
  const registroActualizado = ref(false)
  const constanciaImpresa = ref(false)
  const esSoloLectura = computed(() => {
      return form.id_estatus == 99; // Suponiendo que 99 es 'Entregado'
  });
  const consecutivoInput = ref(null);
  const codigoPostalRef = ref(null);

  watch(isDocOpen, (nuevoValor) => {
    emit('toggle-doc', nuevoValor);
});

  const emit = defineEmits(['save', 'cancel', 'toggle-doc']);

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

          generarPrefijoBase();

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

      // 2. Pasamos esos valores directamente al useForm
      const form = useForm(obtenerValoresIniciales());

  //     const prefijoBase = computed(() => {
  //       const fechaBase = form.fecha_emision ? new Date(form.fecha_emision + 'T00:00:00') : new Date();
        
  //       const dia = String(fechaBase.getDate()).padStart(2, '0');
  //       const mes = String(fechaBase.getMonth() + 1).padStart(2, '0');
  //       const anio = String(fechaBase.getFullYear()).slice(-2);
        
  //       const tipoId = String(props.initialData?.tipo_tramite?.tipo_tramite?.id || '0').padStart(3, '0');
        
  //       return `${dia}.${mes}.${anio}/DPU/${tipoId}`;
  // });

  // Definimos la variable para el prefijo
  const prefijoBase = ref('');

  // Función que debes llamar justo antes de abrir o al abrir la ventana modal
  const generarPrefijoBase = () => {
    const fechaBase = form.fecha_emision ? new Date(form.fecha_emision + 'T00:00:00') : new Date();
    
    const dia = String(fechaBase.getDate()).padStart(2, '0');
    const mes = String(fechaBase.getMonth() + 1).padStart(2, '0');
    const anio = String(fechaBase.getFullYear()).slice(-2);
    
    const tipoId = String(props.initialData?.tipo_tramite?.tipo_tramite?.id || '0').padStart(3, '0');
    
    prefijoBase.value = `${dia}.${mes}.${anio}/DPU/${tipoId}`;

    form.prefijo_base = prefijoBase.value + '/';
};

  // watch(prefijoBase, (nuevoValor) => {
  //   if (esSoloLectura.value) return;
  //     form.prefijo_base = nuevoValor + '/';
  // }, { immediate: true });

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

          if (!form.coordenada_utm_x || !form.coordenada_utm_y || !form.referencias_ubicacion)
          {
              showGeoData.value = true
              codigoPostalRef.value?.focus();
              return Swal.fire({
                  icon: 'info',
                  title: 'Datos faltantes',
                  text: 'Debes capturar información referente a la ubicación técnica de la propiedad.',
                  confirmButtonText: 'Entendido',
                  confirmButtonColor: '#d33',
              });
          }

          ejecutarEnvio();
      };

      const confirmaAprobacion = async () => {
         const result = await Swal.fire({
            title: '<span class="text-3xl font-black text-gray-800">¿Confirmar Aprobación?</span>',
            html: `
                  <div class="mt-4 text-left">
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl mb-6">
                      <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm text-amber-800 font-bold">
                          Verifique la información antes de continuar:
                        </p>
                      </div>
                    </div>

                    <div class="space-y-6 px-1 relative">
                      <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-gray-100"></div>

                      <div class="relative flex items-start gap-4">
                        <div class="z-10 flex-shrink-0 w-6 h-6 bg-color2-500 rounded-full flex items-center justify-center shadow-sm">
                          <span class="text-[10px] text-white font-bold">1</span>
                        </div>
                        <div>
                          <p class="text-[11px] uppercase font-black text-gray-400 tracking-wider">Asignación Oficial</p>
                          <p class="text-[13px] text-gray-600 leading-tight">
                            Se asignará el número <span class="text-gray-900 font-black text-sm">${form.numero_asignado} (${form.numero_asignado_letra})</span> a la propiedad
                          </p>
                        </div>
                      </div>

                      <div class="relative flex items-start gap-4">
                        <div class="z-10 flex-shrink-0 w-6 h-6 bg-color3-500 rounded-full flex items-center justify-center shadow-sm">
                          <span class="text-[10px] text-white font-bold">2</span>
                        </div>
                        <div>
                          <p class="text-[11px] uppercase font-black text-gray-400 tracking-wider mb-1">Registro de Propiedad</p>
                          <p class="text-[13px] text-gray-600 leading-tight">
                            Vinculación del número oficial a la clave catastral <br> <span class="text-gray-900 font-black">${claveCatastralFormateada.value}</span>
                          </p>
                        </div>
                      </div>

                      <div class="relative flex items-start gap-4">
                        <div class="z-10 flex-shrink-0 w-6 h-6 bg-color4-500 rounded-full flex items-center justify-center shadow-sm">
                          <span class="text-[10px] text-white font-bold">3</span>
                        </div>
                        <div>
                          <p class="text-[11px] uppercase font-black text-gray-400 tracking-wider mb-1">Emisión de Constancia</p>
                          <p class="text-[13px] text-gray-600 leading-tight">
                            Disponibilidad inmediata de la <span class="text-gray-900 font-bold uppercase decoration-purple-200">Constancia de Número Oficial</span> tras la aprobación del trámite
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, aprobar y asignar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusConfirm: false,
            customClass: {
              popup: 'rounded-2xl',
              confirmButton: 'rounded-lg px-5 py-2.5',
              cancelButton: 'rounded-lg px-5 py-2.5'
            }
          });

         return result?.isConfirmed || false
      };
      
      const notificarExito = (mensaje) => {
        if (!mensaje) return;
        Swal.fire({
          toast: true,
          icon: 'success',
          position: 'top-end',
          showConfirmButton: false,
          title: mensaje,
          timer: 2000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.style.zIndex = '99999';
          }
        });
      };

      const procesarCierre = (esAprobado) => {
        registroCreado.value = false;
        registroActualizado.value = false;
        constanciaImpresa.value = false;
        
        form.consecutivo = String(form.consecutivo).padStart(4, '0');

        if (esAprobado) {
          registroCreado.value = true;
          // Aquí no hacemos emit('save') para que el modal del PDF (registroCreado) sea visible
        } else {
          emit('save'); // Cerramos y refrescamos la tabla para otros estatus
        }
      };

  const ejecutarEnvio = async () => {
      try {
        if (![3, 4, 5].includes(form.id_estatus)) {
            form.motivo_justificacion = null;
        }

        form.prefijo_base = prefijoBase.value + '/';
          await router.post('/constancias-numero-oficial/store', form, {
              onSuccess: async (page) => {
                if (page.props.flash.necesitaConfirmacion) {
                  const confirmado = await confirmaAprobacion();

                  if (confirmado) {
                    sinDatosInicialesGeo.value = false
                    setTimeout(() => {
                    router.post('/constancias-numero-oficial/store', {
                      ...form,
                      confirmacion_final: true
                    }, {
                      onStart: () => {
                        isProcessingModal.value = true
                        isSavingModal.value = true;
                      },
                      onSuccess: async (newPage) => {
                        form.id_constancia = newPage.props.flash.idConstancia;
                        const response = await fetch(`/constancias-numero-oficial/get-constancia/${encodeURIComponent(form.id_constancia)}`, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })
                
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor')
                        }
                        const data = await response.json()
                        const nuevaProp = data.constancia?.propiedad;

                        if (nuevaProp) 
                        {
                          form.propiedad = { ...nuevaProp };
                        }
                        notificarExito(newPage.props.flash.success);
                        procesarCierre(true);
                      },
                      onFinish: () => {
                        isProcessingModal.value = false
                        isSavingModal.value = false;
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
                        isProcessingModal.value = false
                      },
                    });
                    }, 300);
                  }
                  else {
                    isProcessingModal.value = false;
                    isSavingModal.value = false;
                  }
                  return; // Detenemos aquí para que no ejecute el código de abajo
                }
                else
                {
                  isProcessingModal.value = false;
                }

                // CASO 2: GUARDADO DIRECTO (Rechazos o correcciones)
                if (page.props.flash.success) {
                  notificarExito(page.props.flash.success);
                  procesarCierre(form.id_estatus == 2);
                }
              },
              onStart: () => {
                  isProcessingModal.value = true;
              },
              onSuccess: async (newPage) => {
                        form.id_constancia = newPage.props.flash.idConstancia;
                        notificarExito(newPage.props.flash.success);
                        procesarCierre(true);
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
                isProcessingModal.value = false;

               Swal.fire({
                    icon: hasValidationErrors ? 'warning' : 'error',
                    title: hasValidationErrors ? 'Revisa los campos' : 'Error',
                    // IMPORTANTE: Aplicamos estilo CSS directo para forzar las viñetas
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
            },
            onFinish: () => {
              if (!isSavingModal.value) {
                  isSavingModal.value = false;
              }
              isProcessingModal.value = false;
            }
          });
      } catch (err) {
          console.error('Error inesperado al procesar:', err);
      }
  };

  const asignarPropietarioPorDefecto = () => {
    // Solo asignamos si el valor actual es null o vacío
    if (!form.nombre_destinatario && props.destinatarios.length > 0) {
      
      // Buscamos cualquier elemento cuyo tipo sea PROPIETARIO o PROPIETARIA
      const propietario = props.destinatarios.find(d => 
        d.tipo === 'PROPIETARIO' || d.tipo === 'PROPIETARIA'
      );

      if (propietario) {
        form.nombre_destinatario = propietario.nombre;
        // También es recomendable asignar el carácter de una vez si tienes ese campo
        form.caracter_destinatario = propietario.tipo; 
      }
    }
  };

  const claveCatastralFormateada = computed({
    get() {
      // Si no hay valor, devolvemos vacío
      if (!form.clave_catastral) return '';
      
      // Limpiamos por si acaso y aplicamos el formato de 3 en 3
      return form.clave_catastral
        .replace(/\D/g, '')
        .substring(0, 18)
        .replace(/(\d{3})(?=\d)/g, '$1 ');
    },
    set(newValue) {
      // Cuando el usuario escribe, guardamos el valor SIN espacios en el form real
      form.clave_catastral = newValue.replace(/\D/g, '').substring(0, 18);
    }
  });

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

  const page = usePage();

  onMounted(() => {

        const urlParams = new URLSearchParams(window.location.search);
    
        if (urlParams.get('mostrar_exito') === '1') {
            Swal.fire({
                toast: true,
                icon: 'success',
                title: 'Propiedad actualizada exitosamente',
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }

        if (!props.documentoData) {
            asignarPropietarioPorDefecto();
        }

        consecutivoInput.value?.focus();

        // Ajustamos la altura del textarea una sola vez al inicio
        setTimeout(() => ajustarAltura(), 100); 
        document.addEventListener('mousedown', closeOnClickOutside);
    });

    onUnmounted(() => {
      document.removeEventListener('mousedown', closeOnClickOutside);
    });

    // Opcional: Si los destinatarios tardan en cargar (por ser asíncronos), vigilamos el cambio
    watch(() => props.destinatarios, () => {
      asignarPropietarioPorDefecto();
    }, { immediate: true });

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

  const toggleManual = () => {
      if (!puedeEditarManual.value) return;

      if (!esManual.value) {
          // Al abrir candado: Nos aseguramos de tener el valor actual
          form.numero_asignado_letra = numeroALetrasEspecial(form.numero_asignado);
          esManual.value = true;
      } else {
          // Al cerrar (Check): Simplemente bloqueamos, el v-model ya guardó los cambios
          esManual.value = false;
      }
  };

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
        else
        {
          if (form.codigo_postal)
          {
            partes.push(`C.P. ${form.codigo_postal}`);
          }
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

    const generarConstancia = async () => {
        try {
        // isLoadingModal.value = true;

        const validacionExitosa = await actualizarTramite(true);

        // Si la función devolvió 'false' (el usuario canceló o hubo error)
        if (!validacionExitosa) {
            // isLoadingModal.value = false;
            return false; // Detenemos todo
        }

        // isLoadingModal.value = false;

        if (!form.id_tramite) {
            Swal.fire({
                icon: 'error',
                title: '<span class="text-2xl font-black text-gray-800">Error de Identificación</span>',
                text: 'No se localizó el ID del trámite actual para generar el documento.',
                confirmButtonText: 'Entendido',
                customClass: { confirmButton: 'rounded-full px-5 py-2 font-semibold' }
            });
            return false;
        }

        const pdfWindow = window.open('', '_blank');
        

        if (!pdfWindow) {
            Swal.fire({
                icon: 'warning',
                title: 'Ventana bloqueada',
                text: 'El navegador bloqueó la ventana emergente. Permite ventanas emergentes para este sitio e inténtalo nuevamente.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Mostrar inmediatamente pantalla de carga
        pdfWindow.document.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Generando documento...</title>
                <style>
                    * {
                        box-sizing: border-box;
                    }

                    body {
                        margin: 0;
                        height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-family: Arial, sans-serif;
                        background: #f8fafc;
                        color: #334155;
                    }

                    .contenedor {
                        text-align: center;
                    }

                    .spinner {
                        width: 45px;
                        height: 45px;
                        margin: 0 auto 18px;
                        border: 4px solid #efefef;
                        border-top-color: #bcbcbc;
                        border-radius: 50%;
                        animation: spin 0.8s linear infinite;
                    }

                    .titulo {
                        font-size: 18px;
                        font-weight: 700;
                        margin-bottom: 6px;
                    }

                    .texto {
                        font-size: 14px;
                        color: #64748b;
                    }

                    @keyframes spin {
                        to {
                            transform: rotate(360deg);
                        }
                    }
                </style>
            </head>

            <body>
                <div class="contenedor">
                    <div class="spinner"></div>

                    <div class="titulo">
                        Generando constancia
                    </div>

                    <div class="texto">
                        Preparando el documento PDF...
                    </div>
                </div>
            </body>
            </html>
        `);

        pdfWindow.document.close();

        try {

          isLoadingModal.value = true;

          const token = document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content');

          const response = await fetch(
              '/constancias-numero-oficial/generar',
              {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                      'X-CSRF-TOKEN': token
                  },
                  body: new URLSearchParams({
                      id: form.id_tramite
                  })
              }
          );

          if (!response.ok) {
              throw new Error(`Error HTTP ${response.status}`);
          }

          const blob = await response.blob();

          const url = URL.createObjectURL(blob);

          pdfWindow.location.href = url;

          setTimeout(() => {
              URL.revokeObjectURL(url);
          }, 10000);

      } catch (error) {

          console.error('Error generando PDF:', error);

          if (pdfWindow && !pdfWindow.closed) {
              pdfWindow.document.body.innerHTML = `
                  <div style="
                      height:100vh;
                      display:flex;
                      align-items:center;
                      justify-content:center;
                      font-family:Arial,sans-serif;
                      background:#f8fafc;
                  ">
                      <div style="
                          text-align:center;
                          max-width:420px;
                          padding:30px;
                      ">
                          <div style="
                              font-size:45px;
                              margin-bottom:15px;
                          ">⚠️</div>

                          <div style="
                              font-size:20px;
                              font-weight:bold;
                              color:#334155;
                              margin-bottom:8px;
                          ">
                              No se pudo generar el documento
                          </div>

                          <div style="
                              font-size:14px;
                              color:#64748b;
                          ">
                              Ocurrió un problema al preparar la constancia.
                              Puedes cerrar esta ventana e intentarlo nuevamente.
                          </div>
                      </div>
                  </div>
              `;
          }

      } finally {

          isLoadingModal.value = false;

      }
        constanciaImpresa.value = true
    } catch (error) {
        console.error("Error en el flujo:", error);
        Swal.fire({
            icon: 'error',
            title: '<span class="text-2xl font-black text-gray-800">Error del Sistema</span>',
            text: 'Ocurrió un problema inesperado al procesar y generar el PDF de la constancia.',
            confirmButtonText: 'Cerrar',
            customClass: { confirmButton: 'rounded-full px-5 py-2 font-semibold bg-red-600 text-white' }
        });
    } finally {
        isLoadingModal.value = false;
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

        // form.prefijo_base = prefijoBase.value + '/';

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
                    // IMPORTANTE: Aplicamos estilo CSS directo para forzar las viñetas
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

    const formatCamel = (text) => {
      if (!text) return '';
      return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
    };

  const finalizarEntrega = async () => {

      const result = await Swal.fire({
        title: '¿Confirmar entrega física?',
        icon: 'warning',
        html: `
          <div class="text-left space-y-4">
            <p class="text-sm text-gray-600">
              Al registrar la <strong>entrega física</strong>, el trámite se dará por finalizado. 
              A partir de ese momento, el registro quedará bloqueado y <strong>no será posible realizar cambios</strong>.
            </p>

            
            <div class="bg-white border border-amber-100 rounded-xl shadow-sm overflow-hidden">
        <div class="bg-amber-50/80 px-4 py-2.5 border-b border-amber-100 flex items-center gap-2">
          <div class="text-amber-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <p class="text-[11px] font-bold text-amber-900 uppercase tracking-widest">Verificación de seguridad</p>
        </div>

        <div class="p-4">
          <ul class="space-y-2.5">
            <li class="flex gap-3 items-start">
              <div class="mt-0.5 w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                <span class="text-[9px] font-bold text-amber-600">01</span>
              </div>
              <span class="text-[13px] text-gray-700 leading-tight">La constancia física ha sido validada contra el registro del sistema.</span>
            </li>
            <li class="flex gap-3 items-start">
              <div class="mt-0.5 w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                <span class="text-[9px] font-bold text-amber-600">02</span>
              </div>
              <span class="text-[13px] text-gray-700 leading-tight">El número oficial coincide exactamente con el documento impreso.</span>
            </li>
            <li class="flex gap-3 items-start">
              <div class="mt-0.5 w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                <span class="text-[9px] font-bold text-amber-600">03</span>
              </div>
              <span class="text-[13px] text-gray-700 leading-tight">La documentación fue entregada al ciudadano o ciudadana.</span>
            </li>
          </ul>
        </div>
      </div>

            <div class="flex items-center gap-3 mt-4">
        <input type="checkbox" id="confirmCheckbox" class="w-4 h-4 text-green-700 rounded focus:ring-green-500 cursor-pointer">
        <label for="confirmCheckbox" class="text-sm font-semibold text-gray-700 cursor-pointer">
          He verificado la información y los requisitos, y deseo finalizar el proceso.
        </label>
      </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar y finalizar',
        cancelButtonText: 'Revisar detalles',
        confirmButtonColor: '#15803d',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        didOpen: () => {
          const confirmBtn = Swal.getConfirmButton();
          confirmBtn.disabled = true;
          
          const checkbox = Swal.getHtmlContainer().querySelector('#confirmCheckbox');
          checkbox.addEventListener('change', () => {
            confirmBtn.disabled = !checkbox.checked;
          });
        },
        preConfirm: () => {
          const checkbox = Swal.getHtmlContainer().querySelector('#confirmCheckbox');
          if (!checkbox.checked) {
            // Mensaje neutro e institucional
            Swal.showValidationMessage('Es necesario confirmar la exactitud de los datos para proceder.');
            return false;
          }
        }
      });  

      if (result.isConfirmed) 
      {
        isSavingModal.value = true

        const entregaConfirmada = await confirmarEntrega();

        // Si la función devolvió 'false' (el usuario canceló o hubo error)
        if (!entregaConfirmada) {
            isSavingModal.value = false;
            return false; // Detenemos todo
        }

        router.post('/constancias-numero-oficial/entregar', form, {
          preserveScroll: true,
          onStart: () => {
            isSavingModal.value = true;
          },
          onFinish: () => {
            isSavingModal.value = false;
          },
          onSuccess: (page) => {
            Swal.fire({
              title: '¡Entregado!',
              text: page.props.flash.success || 'El trámite se finalizó con éxito',
              icon: 'success',
              timer: 3000,
              showConfirmButton: false
            });
            
            emit('save');
          },
          onError: (errors) => {
            isSavingModal.value = false;
            
            const esErrorDeRed = !errors || Object.keys(errors).length === 0;
            if (esErrorDeRed) {
              return Swal.fire({
                icon: 'warning',
                title: 'Conexión interrumpida',
                text: 'No se pudo establecer contacto con el servidor.',
                confirmButtonColor: '#3085d6',
              });
            }

            const errorMessage = Object.values(errors)
              .map(err => `<li style="margin-bottom: 7px;">${err}</li>`)
              .join('');

            Swal.fire({
              icon: 'warning',
              title: 'Revisa los campos',
              html: `
                <div style="text-align: left; font-size: 12pt;">
                  <ul style="list-style-type: disc !important; padding-left: 25px !important; margin-top: 10px;">
                    ${errorMessage}
                  </ul>
                </div>`,
              confirmButtonText: 'Aceptar',
              width: '400px',
              didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) swalContainer.style.setProperty('z-index', '99999', 'important');
              }
            });
          }
        });
      }
  };

  const reabrirTramite = () => {
      form.id_estatus = null;
    };

    const errorConsecutivo = ref('');

  const validarConsecutivo = async () => {
      errorConsecutivo.value = '';

      if (!form.consecutivo) {
          return;
      }

      try {
          const response = await axios.post('/constancias-numero-oficial/validar-consecutivo', {
              consecutivo: form.consecutivo,
              anio: form.fecha_emision
          });

          if (response.data.existe) {

              errorConsecutivo.value = `El consecutivo ${form.consecutivo} ya fue utilizado`;
              form.consecutivo = '';

          }

      } catch (error) {

          console.error('Error validando consecutivo:', error);

      }
    };

  const formatearConsecutivo = () => {
      // Solo actuamos si hay algo escrito
      if (form.consecutivo) {
          // .toString() asegura que tratamos con texto
          // .padStart(4, '0') rellena con '0' a la izquierda hasta llegar a 4 caracteres
          form.consecutivo = form.consecutivo.toString().padStart(4, '0');
      }
  };

  const guardarGeo = () => {
      return new Promise((resolve) => {
        // --- 1. VALIDACIONES PREVIAS ---
      const errores = [];

      // Validación Código Postal (5 dígitos numéricos)
      if (form.propiedad?.codigo_postal)
      {
        form.codigo_postal = null
      }

      const regexCP = /^\d{5}$/;
      if (!form.propiedad?.codigo_postal && !regexCP.test(form.codigo_postal)) {
        errores.push('El campo <b>Código Postal</b> debe contener exactamente 5 números.');
      }

      // Validación Coordenada X UTM (Numérico y máximo 15 caracteres)
      if (!form.coordenada_utm_x || isNaN(form.coordenada_utm_x)) {
        errores.push('La <b>Coordenada X UTM</b> debe ser un valor numérico.');
      } else if (String(form.coordenada_utm_x).length > 15) {
        errores.push('La <b>Coordenada X UTM</b> no debe exceder los 15 caracteres (incluyendo decimales).');
      }

      // Validación Coordenada Y UTM (Numérico y máximo 15 caracteres)
      if (!form.coordenada_utm_y || isNaN(form.coordenada_utm_y)) {
        errores.push('La <b>Coordenada Y UTM</b> debe ser un valor numérico.');
      } else if (String(form.coordenada_utm_y).length > 15) {
        errores.push('La <b>Coordenada Y UTM</b> no debe exceder los 15 caracteres (incluyendo decimales).');
      }

      if (!form.referencias_ubicacion) {
        errores.push('Las <b>Referencias de Ubicación</b> no pueden estar vacías.');
      }

      if (Object.keys(errores).length > 0)
      {
        const hasValidationErrors = errores && Object.keys(errores).length > 0;
        const errorMessage = hasValidationErrors 
          ? Object.values(errores).map(err => `<li style="margin-bottom: 7px;">${err}</li>`).join('') 
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
    }
    else 
    {
      showGeoData.value = false
    }
    });
  };

  const coordenadaXFormateada = computed({
    get() {
      let valor = form.coordenada_utm_x;
      if (valor === null || valor === undefined || valor === '') return '';
      
      // Convertimos a número y formateamos con 2 decimales y comas
      return Number(valor).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    set(nuevoValor) {
      // Si necesitas editarlo, limpiamos comas para guardar solo el número
      const limpio = nuevoValor.replace(/,/g, '');
      form.coordenada_utm_x = limpio;
    }
  });

  const coordenadaYFormateada = computed({
    get() {
      let valor = form.coordenada_utm_y;
      if (valor === null || valor === undefined || valor === '') return '';
      
      // Convertimos a número y formateamos con 2 decimales y comas
      return Number(valor).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    set(nuevoValor) {
      // Si necesitas editarlo, limpiamos comas para guardar solo el número
      const limpio = nuevoValor.replace(/,/g, '');
      form.coordenada_utm_y = limpio;
    }
  });

  const formatearFecha = (fecha) => {
      if (!fecha) return '';
      const [year, month, day] = fecha.split('-');
      return `${day}-${month}-${year}`;
  };

  const confirmarEdicionPropiedad = (campo = null) => {
    // 🌟 ACCIÓN COMÚN: La petición que se ejecuta al confirmar cualquiera de las dos alertas
    // const redirigirAEdicion = () => {
    //     isLoadingModal.value = true;
    //     router.post(`/propiedades/edit`, {
    //         id_propiedad: form.propiedad.id,
    //         id_tramite: form.id_tramite,
    //         desde_tramite: true,
    //         campo_especifico: campo
    //     });
    // };

    const redirigirAEdicion = () => {
        isLoadingModal.value = true;

        let payload = {};

        payload = { numero_asignado: form.numero_asignado };

        router.post(`/propiedades/edit`, {
            id_propiedad: form.propiedad.id,
            id_tramite: form.id_tramite,
            desde_tramite: true,
            campo_especifico: campo,
            tramite_data: {
                payload: payload
            }
        });
    };

    if ((registroActualizado && form.id_estatus == 2) || (registroCreado && form.id_estatus == 2) || (props.documentoData && form.id_estatus_original == 2)) {
        // 1. Deducimos el sujeto gramatical por defecto
        let sujetoConArticulo = 'de la propiedad';

        // 2. Si es el propietario, aplicamos tu filtro inteligente por CURP 👤✨
        if (campo === 'nombre_propietario') {
            const curp = form.propiedad?.contacto?.persona?.curp || '';
            const esFemenino = curp.charAt(10).toUpperCase() === 'M';
            sujetoConArticulo = esFemenino ? 'de la propietaria' : 'del propietario';
        }

        Swal.fire({
            title: '<span class="leading-tight text-2xl font-black text-gray-900 block mb-1">¡Constancia lista para imprimir!</span>',
            html: `
                <div class="text-justify mt-2 text-sm flex flex-col gap-3">
                    <p class="text-[18px] text-gray-600 text-center leading-tight">
                        ¿Estás seguro de que deseas modificar los datos ${sujetoConArticulo}?
                    </p>
                    
                    <div class="bg-red-50 border-l-4 border-color1-500 p-3 rounded-r-xl text-color1-700 text-[12px] leading-normal">
                        <div class="flex gap-2">
                            <svg class="w-4 h-4 text-color1-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <span class="font-bold block mb-0.5 uppercase tracking-wide text-[13px] text-color1-700">Aviso de Impresión</span>
                                El documento PDF ya se encuentra listo para su emisión con la información actual. Cualquier modificación de último momento alterará el expediente, por lo que será obligatorio volver a validar la coherencia de todos los datos antes de imprimir la constancia definitiva.
                            </div>
                        </div>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, ir a modificar',
            cancelButtonText: 'Volver al trámite',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-3xl p-6',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                redirigirAEdicion();
            }
        });

        return;
    }

    // 1. Valores por defecto (Para campos generales de la propiedad)
    let titulo = '¿Modificar datos de la propiedad?';
    let texto = 'El sistema abrirá un formulario externo donde se podrá modificar la propiedad.';

    // 2. Si el campo específico es el propietario, calculamos el género según la CURP
    if (campo === 'nombre_propietario') {
        const curp = form.propiedad?.contacto?.persona?.curp || '';
        const esFemenino = curp.charAt(10).toUpperCase() === 'M';
        
        // 🚀 LA CLAVE: Manejamos las contracciones gramaticales correctas aquí
        const sujetoConArticulo = esFemenino ? 'de la propietaria' : 'del propietario';

        titulo = `¿Modificar datos ${sujetoConArticulo}?`;
        texto = `El sistema abrirá un formulario externo donde se podrán actualizar los datos ${sujetoConArticulo}.`;
    }

    // 3. Lanzamos el SweetAlert original
    Swal.fire({
        title: `<span class="leading-tight text-2xl font-bold mb-2 text-gray-900">${titulo}</span>`,
        text: texto,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, ir a modificar',
        cancelButtonText: 'Volver al trámite',
        reverseButtons: true,
        customClass: {
            title: 'block',
            popup: 'rounded-3xl p-6',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            redirigirAEdicion();
        }
    });
};

const abrirGeoData = async () => {

    showGeoData.value = true;

    if (showGeoData.value) {
        await nextTick();
        codigoPostalRef.value?.focus();
    }

};

  const datosGeoCompletos = computed(() => {
      return !!form.codigo_postal &&
              !!form.coordenada_utm_x &&
              !!form.coordenada_utm_y &&
              !!form.referencias_ubicacion;
  });


</script>

<template>
  <div :class="['relative overflow-hidden', isPage ? 'mx-auto my-0 bg-white' : 'rounded-2xl bg-white shadow-2xl']"> 
     <form @submit.prevent="handleSubmit" class="flex flex-col md:flex-row" :class="[form.id_estatus == 99 ? 'min-h-[350px]' : 'min-h-[685px]']">
      <div :class="[
        'flex-grow md:w-[70%] border-r border-gray-100', 
        isPage ? 'pl-8 pr-8 pt-6' : 'p-8']">
        <div 
            class="flex flex-col md:flex-row items-end justify-between border-b pb-4 gap-4 transition-all duration-500"
            :class="[ esSoloLectura ? 'mb-4' : 'mb-6' ]">
            <div class="w-full md:w-auto">
                <h2 v-if="(registroCreado || props.documentoData)" 
                    :class="[
                        'font-extrabold text-gray-800 leading-tight', 
                        isPage ? 'text-2xl' : 'text-xl']">
                    Trámite de Constancia de Número Oficial
                </h2>

                <h2 v-else 
                    :class="[
                        'font-extrabold text-gray-800 leading-tight', 
                        isPage ? 'text-2xl' : 'text-xl']">
                    Completar Trámite de Constancia de Número Oficial 
                </h2>
                <p class="text-sm text-gray-500 mt-1 uppercase tracking-wider">
                    ID. TRÁMITE: 
                    <span class="text-md font-bold text-color1-700">
                        {{ String(initialData?.id).slice(-4).padStart(4, '0') }}
                    </span>
                    &nbsp; | &nbsp; Folio de Solicitud: 
                    <span class="text-md font-bold text-color1-700">
                        {{ String(initialData?.solicitud?.folio).slice(-4).padStart(4, '0') }}
                    </span>
                </p>
            </div>
            <div class="relative w-full md:w-[130px]"> 
               <el-date-picker
                    v-if="!esSoloLectura"
                    v-model="form.fecha_emision"
                    class="!w-full font-bold custom-datepicker"
                    type="date"
                    format="DD-MM-YYYY"
                    :clearable=false
                    value-format="YYYY-MM-DD"
                    @change="generarPrefijoBase"/>

                <div v-else 
                    class="w-full flex items-center px-[3px] text-sm font-bold text-gray-800 border-b border-transparent cursor-not-allowed select-none antialiased tabular-nums">
                    {{ formatearFecha(form.fecha_emision) }}
                </div>
                <label class="absolute pl-[3px] text-[12px] font-bold text-gray-500 duration-300 transform -translate-y-7 scale-100 top-2 left-0 origin- pointer-events-none uppercase tracking-wide">
                    Fecha de Emisión
                </label>
            </div>
        </div>
        <div v-if="form.id_estatus != 99" class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-2 text-color1-700 font-bold uppercase text-xs tracking-widest">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
              </svg>
              Datos de la Constancia 
          </div>
          <div class="relative" v-if="sinDatosInicialesGeo || (!form.referencias_ubicacion || !form.coordenada_utm_x || !form.coordenada_utm_y)"> 
            <button 
              type="button" 
              @click="abrirGeoData()" 
              class="group absolute -top-5 right-0 z-2 p-2 rounded-xl bg-white/80 backdrop-blur-sm text-gray-400 hover:bg-color1-400 hover:text-white shadow-sm border border-gray-100 transition-all">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="absolute top-1 right-1 flex h-2.5 w-2.5">
                  <span
                      class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping"
                      :class="datosGeoCompletos
                          ? 'bg-green-500'
                          : 'bg-color1-500'"
                  ></span>

                  <span
                      class="relative inline-flex rounded-full h-2.5 w-2.5"
                      :class="datosGeoCompletos
                          ? 'bg-green-500'
                          : 'bg-color1-400'"
                  ></span>
              </span>
            </button>
          </div>
        </div>
        <section 
          class="transition-all duration-500"
          :class="{ 'space-y-8': !esSoloLectura, 'space-y-2': esSoloLectura }">
          <div class="grid grid-cols-1 md:grid-cols-[4fr_6fr] items-end w-full gap-x-6"
          :class="[esSoloLectura ? 'gap-y-4' : 'gap-y-8']">
            <div 
            class="relative transition-all duration-300"
            :class="[
              esSoloLectura 
                ? 'bg-gray-100 pt-6 pb-1 px-2 rounded-xl' 
                : 'mt-3']">
              <div class="flex items-end gap-2">
                <div class="group flex-grow h-8 relative transition-colors duration-300 border-b-2" 
                :class="[
                  esSoloLectura 
                  ? 'border-transparent' 
                  : 'border-gray-300 focus-within:border-color1-600'
                ]">
                  <div        
                    class="relative transition-colors duration-300">                
                    <div class="flex items-baseline">
                      <span :class="[esSoloLectura ? 'cursor-not-allowed font-bold' : '']" 
                            class="text-sm text-gray-900 select-none whitespace-nowrap leading-none">
                          <label class="absolute text-sm duration-300 transform -translate-y-[21px] top-1 left-0 pointer-events-none text-gray-500 scale-100 font-normal 
                                        origin-left 
                                        group-focus-within:text-color1-600 group-focus-within:scale-90">
                            Número de Oficio
                          </label>
                         <input 
                            v-if="!isLocked" 
                            ref="inputPrefijo"
                            v-model="form.prefijo_base" 
                            @blur="isLocked = true"
                            :disabled="esSoloLectura" 
                            class="font-bold text-gray-800 border border-gray-400 rounded px-1 py-0.5 text-sm focus:outline-none focus:border-color1-600 focus:ring-0 leading-none m-0 shadow-sm"
                            :class="[isLocked ? 'w-[126px]' : 'w-[138px]']"/>
                        <template v-else>
                          <span 
                          class="font-bold border-0 p-0 text-sm focus:ring-0 leading-none m-0"
                          :class="[esSoloLectura ? 'cursor-not-allowed font-bold text-gray-800' : 'text-gray-400']">
                          {{ form.prefijo_base }} 
                          </span>
                        </template>
                      </span>
                       <div class="group flex-grow relative transition-colors duration-300">
                          <div class="flex flex-col items-start">
                              <input 
                                  type="text"
                                  ref="consecutivoInput"
                                  v-model="form.consecutivo"
                                  @blur="formatearConsecutivo"
                                  @focus="errorConsecutivo = ''"
                                  @change="validarConsecutivo()"
                                  maxlength="4"
                                  :disabled="esSoloLectura"
                                  placeholder="####"
                                  class="w-[40px] px-[1px] font-bold text-gray-800 text-sm bg-transparent border-0 focus:outline-none focus:ring-0 peer uppercase leading-none"
                                  :class="isLocked ? 'opacity-100' : 'opacity- cursor-not-allowed pointer-events-none'">                           
                          </div>
                      </div>
                    </div>
                    <p 
                        v-if="errorConsecutivo"
                        class="mb-1 pl-2 rounded-b-lg flex items-center gap-1 text-xs bg-red-700 text-white whitespace-nowrap font-medium">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            class="w-3 h-3 flex-shrink-0 animate-[warningPulse_1.2s_ease-in-out_infinite]"
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                            stroke-width="2">
                            <path 
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" 
                            />
                        </svg>

                        {{ errorConsecutivo }}
                    </p>
                  </div>
                </div>
                <button 
                  type="button" 
                  @click="habilitarEdicion"
                  class="p-1 transition-colors duration-200 shrink-0"
                  :class="[isLocked ? 'text-gray-400 hover:text-color1-600' : 'text-color1-600']"
                  :title="isLocked ? 'Editar prefijo' : 'Bloquear edición'"
                  v-if="!esSoloLectura">
                  <svg v-if="isLocked" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </button>
              </div>
            </div>
            <div 
              class="relative"
              :class="[
              esSoloLectura 
                ? 'bg-gray-100 pt-6 pb-1 px-2 rounded-xl' 
                : '']">
              <div class="flex items-end gap-2">
                <div class="group flex-grow">
                  <div 
                    class="relative transition-colors border-gray-300 duration-300"
                    :class="[esSoloLectura ? '' : 'border-b-2']">
                    <input 
                      type="text" 
                      v-model="claveCatastralFormateada" 
                      readonly
                      placeholder=" "
                      tabindex=-1
                      class="block w-full px-0 py-1 bg-transparent font-bold border-0 appearance-none text-gray-800 focus:outline-none focus:ring-0 peer uppercase text-sm"
                      :class="[esSoloLectura ? 'cursor-not-allowed' : '']"/>
                      <label class="absolute text-sm duration-300 transform -translate-y-[23px] top-1 left-0 pointer-events-none text-gray-500 scale-100 font-normal 
                                  origin-left">
                      Clave Catastral
                    </label>
                  </div>
                </div>
                <div v-if="!esSoloLectura" class="w-6 h-6 mb-1 p-1 shrink-0 invisible"></div>
              </div>
            </div>            
          </div>
          <div class="grid gap-y-8 items-end w-full transition-all duration-500"
            :class="[
              (form.coordenada_utm_x || form.coordenada_utm_y) && !showGeoData
              ? 'grid-cols-1 md:grid-cols-[5fr_2.5fr_2.5fr]' 
              : 'grid-cols-1'
            ]">
            <div 
              class="relative flex gap-2 items-end transition-all duration-300"
              :class="{ 'bg-gray-100 pt-6 pb-1 p-2 rounded-xl': esSoloLectura }">
              <div class="flex-grow flex items-end h-8 relative">
                <input 
                  type="text"
                  :value="form.nombre_destinatario || 'SIN NOMBRE ASIGNADO'"
                  readonly
                  placeholder=" "
                  tabindex="-1"
                  class="block w-full mb-0 px-0 py-1 font-bold text-gray-800 bg-transparent border-0 appearance-none focus:outline-none focus:ring-0 peer uppercase truncate text-sm"
                  :class="[
                    esSoloLectura 
                      ? 'cursor-not-allowed py-[5.5px]' 
                      : 'border-b-2 border-gray-300 focus:border-gray-300 py-[3.5px]'
                  ]"/>
                <label 
                  class="absolute text-sm text-gray-500 duration-300 transform pointer-events-none whitespace-nowrap origin-left"
                  :class="[
                    esSoloLectura 
                      ? 'top-1 -translate-y-5' 
                      : 'top-1 -translate-y-[21px]'
                  ]">
                  Nombre de{{ form.propiedad?.contacto?.persona?.curp?.charAt(10) == 'M' ? ' la Propietaria' : 'l Propietario' }} 
                </label>
              </div>
              <div v-if="!esSoloLectura" 
                  @click="confirmarEdicionPropiedad('nombre_propietario')"
                  class="mb-0.5 p-1 text-gray-400 hover:text-color1-600 transition-colors duration-200 shrink-0 cursor-pointer"
                  :title="`Modificar ${form.propiedad?.contacto?.persona?.curp?.charAt(10) == 'M' ? 'propietaria' : 'propietario'}`">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
              </div>
            </div> 
            <div v-if="form.coordenada_utm_x && !showGeoData" 
            class="relative flex items-end transition-all duration-300 ml-4"
               :class="[
              esSoloLectura 
                ? 'bg-gray-100 pt-6 pb-1 px-2 rounded-xl' 
                : '']">
              <div class="relative flex-grow">
                <input 
                  type="text" 
                  :value="coordenadaXFormateada"
                  readonly
                  placeholder=" "
                  tabindex=-1
                  class="block w-full px-0 py-1 font-bold bg-transparent border-0 appearance-none text-gray-800 focus:border-gray-300 focus:outline-none focus:ring-0 peer uppercase text-sm"
                  :class="[esSoloLectura ? 'cursor-not-allowed' : 'border-b-2 border-gray-300']"/>
                <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-[23px] scale-100 top-1 origin-0 peer-placeholder-shown:-translate-y-1 peer-placeholder-shown:scale-100 peer-focus:scale-100 peer-focus:-translate-y-6 pointer-events-none whitespace-nowrap">
                  Coordenada X UTM
                </label>
              </div>
              <div v-if="!esSoloLectura" 
                  @click="confirmarEdicionPropiedad('coordenada_x_utm')"
                  class="mb-0.5 p-1 text-gray-400 hover:text-color1-600 transition-colors duration-200 shrink-0 cursor-pointer"
                  title="Modificar Coordenada X UTM">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
              </div>
            </div>
            <div v-if="form.coordenada_utm_y && !showGeoData" 
            class="relative flex items-end transition-all duration-300 ml-4"
            :class="[
            esSoloLectura 
              ? 'bg-gray-100 pt-6 pb-1 px-2 rounded-xl' 
              : '']">
              <div class="relative flex-grow">
                <input 
                  type="text" 
                  :value="coordenadaYFormateada"
                  readonly
                  placeholder=" "
                  tabindex=-1
                  class="block w-full px-0 font-bold py-1 bg-transparent border-0 appearance-none text-gray-800 focus:border-gray-300 focus:outline-none focus:ring-0 peer uppercase text-sm"
                  :class="[esSoloLectura ? 'cursor-not-allowed' : 'border-b-2 border-gray-300']"/>
                <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-[23px] scale-100 top-1 origin-0 peer-placeholder-shown:-translate-y-1 peer-placeholder-shown:scale-100 peer-focus:scale-100 peer-focus:-translate-y-6 pointer-events-none whitespace-nowrap">
                  Coordenada Y UTM
                </label>
              </div>
              <div v-if="!esSoloLectura" 
                  @click="confirmarEdicionPropiedad('coordenada_y_utm')"
                  class="mb-0.5 p-1 text-gray-400 hover:text-color1-600 transition-colors duration-200 shrink-0 cursor-pointer"
                  title="Modificar Coordenada Y UTM">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
              </div>
            </div>
          </div>
          <div class="relative w-full flex items-center"
             :class="[
              esSoloLectura 
                ? 'mt-4 bg-gray-100 pt-6 pb-1 px-2 rounded-xl' 
                : '']">
            <div class="relative flex-grow">
              <textarea ref="textareaDireccion" 
              :value="direccionFormateada" rows="1" readonly 
              :class="[ esSoloLectura ? 'cursor-not-allowed mb-0 font-bold' : 'border-b-2 border-gray-300 font-bold' ]"
              class="block w-full px-0 py-1 bg-transparent border-0 text-gray-800 focus:border-gray-300 focus:outline-none focus:ring-0 peer uppercase text-sm"></textarea>
              <div v-if="form.referencias_ubicacion && !showGeoData" 
              class="block pl-[1px] w-full text-[11px] text-gray-800 uppercase leading-none truncate"
              :class="[ esSoloLectura ? '-mt-1 cursor-not-allowed font-bold pb-1' : 'mt-2 font-bold' ]">
                {{ form.referencias_ubicacion }}
              </div>
              <label class="absolute text-sm text-gray-500 duration-300 transform -translate-y-[23px] scale-100 top-1 origin-[0] peer-placeholder-shown:-translate-y-2 peer-placeholder-shown:scale-90 pointer-events-none whitespace-nowrap">
                Domicilio del {{ formatCamel(form.propiedad?.tipo?.nombre || 'terreno') }}
              </label>
            </div>
            <div v-if="!esSoloLectura" 
                  @click="confirmarEdicionPropiedad('domicilio_propiedad')"
                  class="mb-0.5 p-1 text-gray-400 hover:text-color1-600 transition-colors duration-200 shrink-0 cursor-pointer"
                  title="Modificar domicilio">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
              </div>
          </div>
          <div 
            class="flex flex-col md:flex-row items-end transition-all duration-500"
            :class="[ esSoloLectura ? 'gap-4' : 'gap-8' ]">
            <div class="relative w-full md:w-[22%] transition-all duration-300 flex items-end"
            :class="[
              esSoloLectura 
                ? 'bg-color1-40 pt-5 pb-1 px-2 rounded-xl' 
                : '']">
              <div class="relative flex-grow">
                <input 
                  type="text" 
                  v-model="form.numero_asignado" 
                  :disabled="esSoloLectura"
                  class="block w-full px-0 pt-1 pb-0 bg-transparent border-0 appearance-none text-color1-700 font-bold focus:outline-none focus:ring-0 peer uppercase"
                  :class="[ 
                    esSoloLectura 
                      ? 'cursor-not-allowed font-bold text-xl' 
                      : 'border-b-2 border-gray-300 !text-lg focus:border-color1-600' 
                  ]" 
                  placeholder=" "/>
                <label 
                  class="absolute text-sm duration-300 transform origin-left pointer-events-none whitespace-nowrap"
                  :class="[
                    esSoloLectura 
                      ? 'top-2 -translate-y-6 scale-100 text-color1-700' 
                      : 'top-2 -translate-y-6 scale-100 text-gray-500 peer-placeholder-shown:-translate-y-4 peer-placeholder-shown:scale-90 peer-focus:text-color1-600 peer-focus:-translate-y-6 peer-focus:scale-90'
                  ]">
                  Número Oficial Asignado
                </label>
              </div>
            </div>
            <div class="relative w-full md:w-[78%] flex items-end"
            :class="[
                  esSoloLectura 
                    ? 'bg-color1-40 pt-5 pb-1 px-2 rounded-xl' 
                    : ''
                ]">
              <div class="relative flex-grow">
              <input 
                  type="text" 
                  v-model="form.numero_asignado_letra" 
                  @input="e => valorManual = e.target.value" 
                  :readonly="!esManual || esSoloLectura" 
                  :disabled="esSoloLectura"
                  placeholder="" 
                  class="block w-full px-0 pt-1 pb-0 bg-transparent border-0 appearance-none text-color1-700 font-bold focus:border-gray-300 focus:outline-none focus:ring-0 peer uppercase text-sm overflow-hidden text-ellipsis whitespace-nowrap"
                  :class="[
                    esSoloLectura ? 'cursor-not-allowed font-bold text-xl' : 'border-b-2 !text-lg',
                    esManual && !esSoloLectura 
                      ? 'focus:border-color1-600 border-gray-400' 
                      : 'focus:border-gray-300 border-gray-300'
                  ]">
                <label class="absolute text-sm duration-300 transform -translate-y-6 scale-100 top-2 origin-[0] peer-placeholder-shown:-translate-y-4 peer-placeholder-shown:scale-90 pointer-events-none whitespace-nowrap"
                :class="[
                    esSoloLectura 
                      ? 'text-color1-700' 
                      : 'text-gray-500'
                  ]">
                  Número Oficial en Letra
                </label>
              </div>
              <button v-if="!esSoloLectura" type="button" @click="toggleManual" :disabled="!puedeEditarManual" class="ml-2 mb-1 p-1 rounded-md transition-all shrink-0" :class="[!puedeEditarManual ? 'text-gray-200' : (esManual ? 'text-color1-600 bg-color1-50' : 'text-gray-400 hover:bg-gray-100')]">
                <svg v-if="!esManual" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </button>
            </div>
          </div>          
        </section>
        <section v-if="!esSoloLectura" class="mt-0 pt-6">
          <div class="flex items-center gap-2 mb-4 text-color1-700 font-bold uppercase text-xs tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Resolución de la Autoridad Municipal
          </div>

          <div class="flex flex-col h-[140px]"> 
            <div class="flex-1">
              <div class="bg-gray-50 h-auto px-6 py-4 rounded-2xl border border-gray-200 shadow-inner">
                <p v-if="!form.id_estatus" class="text-[12px] text-gray-500 mb-4 font-medium italic">
                  Seleccione el ESTATUS del trámite para procesar la información:
                </p>         
                <div class="flex flex-nowrap items-center gap-2 w-full">
                  <template v-for="status in [
                      {id: 2, label: 'Aprobar', activeLabel: 'Aprobar Trámite', activeLabelActual: 'Trámite Aprobado', color_db: todosEstatus.find(e => e.id === 2)?.color || 'gray', icon: 'M5 13l4 4L19 7', desc: 'El trámite quedará listo para poder imprimir la constancia.'},
                      {id: 3, label: 'Suspender', activeLabel: 'Suspender Trámite', activeLabelActual: 'Trámite Suspendido', color_db: todosEstatus.find(e => e.id === 3)?.color || 'gray', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', desc: 'El trámite quedará pausado hasta nueva revisión.'},
                      {id: 4, label: 'Rechazar', activeLabel: 'Rechazar Trámite', activeLabelActual: 'Trámite Rechazado', color_db: todosEstatus.find(e => e.id === 4)?.color || 'gray', icon: 'M6 18L18 6M6 6l12 12', desc: 'El trámite no cumple con los requisitos establecidos.'},
                      {id: 5, label: 'Cancelar', activeLabel: 'Cancelar Trámite', activeLabelActual: 'Trámite Cancelado', color_db: todosEstatus.find(e => e.id === 5)?.color || 'gray', icon: 'M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z M15 9L9 15 M9 9L15 15', desc: 'Se anula el seguimiento y validez de este trámite.'}
                    ]" :key="status.id">
                    <template v-if="!form.id_estatus || form.id_estatus === status.id">
                      <div :class="[
                          'flex flex-row items-stretch transition-all duration-300 min-w-0',
                          form.id_estatus === status.id ? 'flex-1' : 'flex-1'
                        ]">
                        <button 
                          type="button" 
                          @click="form.id_estatus = status.id"
                          :style="[
                            form.id_estatus === status.id 
                              ? { backgroundColor: status.color_db, borderColor: status.color_db } 
                              : { '--hover-color': status.color_db } // Guardamos el color para el hover
                          ]"
                          :class="[
                            'flex items-center justify-center gap-2 py-2 border rounded-xl transition-all duration-300 group px-4',
                            form.id_estatus === status.id 
                              ? 'h-[42px] text-white shadow-lg cursor-default pointer-events-none w-min min-w-[240px]' 
                              : 'h-[38px] bg-white border-gray-200 text-gray-500 hover:scale-105 hover:border-[var(--hover-color)] flex-1 w-full'
                          ]">
                          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="status.icon" />
                          </svg>
                          <div class="flex flex-col items-start leading-tight min-w-max overflow-hidden">
                            <span v-if="form.id_estatus === status.id" class="text-[8px] uppercase opacity-70 font-bold whitespace-nowrap">
                              {{ form.id_estatus === form.id_estatus_original || registroCreado ? 'Estatus Actual' : 'Nueva Acción Seleccionada' }}
                            </span>
                            <span class="text-[12px] font-black uppercase tracking-wider whitespace-nowrap">
                              {{ obtenerEtiqueta(status) }}
                            </span>
                          </div>
                        </button>
                        <template v-if="!registroCreado">
                          <div v-if="form.id_estatus === status.id && status.desc && !(form.id_estatus == form.id_estatus_original && form.id_estatus == 2) && !(registroActualizado && form.id_estatus == 2)" 
                            :style="{ 
                              borderLeftColor: status.color_db, 
                              backgroundColor: `color-mix(in srgb, ${status.color_db}, white 90%)` 
                            }"
                            class="hidden md:flex flex-1 py-2 px-4 items-center rounded-r-xl ml-2 border-l-4 shadow-sm border-y border-r border-gray-100/50 min-w-0">
                            <span :style="{ color: status.color_db }" 
                                  class="text-[10px] font-bold uppercase leading-tight filter brightness-75 line-clamp-2">
                              {{ status.desc }}
                            </span>
                          </div>
                        </template>
                      </div>
                    </template>
                  </template>
                  <div v-if="form.id_estatus" class="flex items-center flex-shrink-0">
                    <button 
                    v-if="!esSoloLectura"
                      @click="form.id_estatus = null" 
                      type="button"
                      title="Cambiar Estatus"
                      class="p-2 text-gray-400 hover:text-red-600 rounded-full transition-all duration-200 hover:rotate-45 active:scale-90">
                      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                  </div>
                </div>
                <transition name="fade">
                  <div v-if="form.id_estatus === 2" class="mt-2 animate-fade-in">
                    <div v-if="!props.documentoData && ((registroCreado || registroActualizado) && !constanciaImpresa)" 
                        :style="{ 
                          backgroundColor: `color-mix(in srgb, ${todosEstatus.find(e => e.id === 2)?.color || '#15803d'}, white 90%)`, 
                          borderColor: `color-mix(in srgb, ${todosEstatus.find(e => e.id === 2)?.color || '#15803d'}, white 70%)` 
                        }"
                        class="px-4 py-3 border rounded-xl flex items-center gap-4">
                      <div :style="{ backgroundColor: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="px-2 py-2 rounded-lg shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div>
                        <p :style="{ color: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="text-[11px] font-bold uppercase tracking-wider filter brightness-75">SIGUIENTE PASO</p>
                        <p :style="{ color: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="text-[12px] font-medium italic filter brightness-50">
                          El estatus se ha actualizado a "Aprobado". Una vez que se genere la constancia, podrás registrar la entrega física.
                        </p>
                      </div>
                    </div>
                    <div v-if="(form.id_estatus_original == 2 && props.documentoData) || constanciaImpresa" 
                        :style="{ 
                          backgroundColor: `color-mix(in srgb, ${todosEstatus.find(e => e.id === 2)?.color || '#15803d'}, white 90%)`, 
                          borderColor: `color-mix(in srgb, ${todosEstatus.find(e => e.id === 2)?.color || '#15803d'}, white 70%)` 
                        }"
                        class="px-4 py-2.5 mt-2 border rounded-xl flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <div :style="{ backgroundColor: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="p-1.5 rounded-lg shadow-sm">
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        <div>
                          <p :style="{ color: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="text-[11px] font-bold uppercase tracking-wider filter brightness-75">Paso Final</p>
                          <p :style="{ color: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }" class="text-[12px] font-medium italic filter brightness-50">La constancia está lista. ¿Se entregó físicamente?</p>
                        </div>
                      </div>
                      <button type="button" 
                        @click="finalizarEntrega" 
                        :disabled="form.processing"
                        :style="{ backgroundColor: todosEstatus.find(e => e.id === 2)?.color || '#15803d' }"
                        class="w-[210px] h-[36px] ml-2 py-5 text-white text-sm font-medium rounded-full shadow-md flex items-center justify-center gap-2 group transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <svg v-else class="w-4 h-4 transition-transform group-hover:rotate-12 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>

                        <span>
                          {{ form.processing ? 'Procesando...' : 'Marcar como Entregado' }}
                        </span>
                      </button>
                    </div>
                  </div>
                </transition>
                <div v-if="[3, 4, 5].includes(form.id_estatus)" class="mt-6 animate-fade-in">
                  <div class="relative w-full">
                    <textarea 
                      v-model="form.motivo_justificacion" 
                      rows="1" 
                      placeholder="Escriba aquí el motivo del por qué no se aprueba el trámite..."
                      class="block w-full px-4 py-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:ring-1 focus:ring-color1-500 focus:border-color1-500 outline-none resize-none shadow-sm"></textarea>
                    <div class="absolute -top-2 left-3 bg-gray-50 px-1 text-[11px] font-bold text-gray-600 uppercase tracking-tighter">
                      Justificación Obligatoria
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
        <section v-else class="mt-4">
          <div v-if="form.id_estatus == 99" class="relative inline-block mb-4" style="z-index: 9999">
            <button 
              @click="showDocs = !showDocs" 
              type="button"
              class="group flex items-center gap-3 px-4 py-2 rounded-xl bg-white border-2 border-gray-100 hover:border-green-200 hover:shadow-md transition-all duration-300 active:scale-95">
              <div class="flex -space-x-2">
                <div class="w-3 h-3 rounded-full bg-green-500 border-2 border-white z-20"></div>
                <div class="w-3 h-3 rounded-full bg-gray-200 border-2 border-white z-10"></div>
              </div>
              <div class="flex flex-col items-start leading-none">
                <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Documentación Entregada</span>
                <span class="text-[10px] font-bold uppercase transition-colors"
                      :class="form.documentacion_entregada?.length === documentacion?.length ? 'text-green-600' : 'text-red-600'">
                  {{ form.documentacion_entregada?.length === documentacion?.length ? 'Expediente Completo' : 'Expediente Incompleto' }}
                    ({{ form.documentacion_entregada?.length || 0 }}/{{ documentacion?.length }})
                </span>
              </div>
              <div class="ml-2 pl-2 border-l border-gray-100">
                <svg 
                  class="w-4 h-4 text-gray-400 transition-transform duration-300" 
                  :class="{'rotate-180 text-green-600': showDocs}" 
                  fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </div>
            </button>
           <div 
              v-if="showDocs" ref="popupRef"
              class="absolute z-50000 left-[calc(100%+12px)] top-[50%] -translate-y-[50%] w-80 bg-white rounded-2xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.3)] border border-gray-100 animate-in fade-in slide-in-from-left-3 duration-300 origin-left">
              <div class="absolute top-1/2 -left-1.5 w-3 h-3 bg-white border-l border-b border-gray-100 rotate-45 -translate-y-1/2 z-0"></div>
              <div class="relative z-10 overflow-hidden rounded-2xl bg-white flex flex-col">
                <div class="px-4 py-3 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center shrink-0">
                  <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Documentos Validados</span>
                  </div>
                  <button @click="showDocs = false" class="text-gray-300 hover:text-gray-500 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                      <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <div class="min-h-[240px] max-h-[500px] overflow-y-auto p-2 custom-scrollbar bg-white">
                  <div 
                    v-for="doc in documentacion" 
                    :key="doc.id_requisito" 
                    class="group/item flex items-center gap-3 p-2 rounded-xl mb-0.5 transition-all"
                    :class="form.documentacion_entregada?.includes(doc.id_requisito) ? 'hover:bg-green-50' : 'hover:bg-red-50/50'">
                    
                    <div class="shrink-0 flex items-center justify-center w-5 h-5">
                      <div v-if="form.documentacion_entregada?.includes(doc.id_requisito)" 
                          class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="5">
                          <path d="M5 13l4 4L19 7" />
                        </svg>
                      </div>
                      <div v-else class="w-4 h-4 rounded-full border-2 border-red-500 bg-red-50 flex items-center justify-center animate-pulse">
                        <svg class="w-2 h-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="6">
                          <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </div>
                    </div>

                    <span class="text-[10px] font-bold uppercase leading-none transition-colors"
                          :class="form.documentacion_entregada?.includes(doc.id_requisito) ? 'text-gray-700' : 'text-gray-400'">
                      {{ doc.nombre }}
                    </span>
                  </div>
                </div>

                <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex justify-center shrink-0">
                  <span class="text-[8px] font-black text-gray-400 uppercase tracking-tighter text-center">
                    Para modificar estos documentos, use 'Habilitar Edición'
                  </span>
                </div>
              </div>
            </div>
          </div>        
          <div class="bg-white h-[105px] px-8 py-0 rounded-2xl border-2 border-green-100 shadow-sm flex items-center transition-all animate-in fade-in zoom-in duration-500 overflow-hidden relative group">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500"></div>
            <div class="flex items-center gap-6 w-full">
              <div class="flex-shrink-0 w-14 h-14 bg-green-100 rounded-full flex items-center justify-center border-4 border-white shadow-sm">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white shadow-inner">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-1">
                  <span class="px-2 py-0.5 bg-green-500 text-white text-[9px] font-black rounded-md uppercase tracking-wider">
                    EXPEDIENTE CERRADO
                  </span>
                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    ID: # {{ String(form.id_tramite).slice(-4).padStart(4, '0') }}
                  </span>
                </div>
                
                <h3 class="text-xl font-black text-gray-900 leading-tight uppercase tracking-tight">
                  Trámite Entregado <span class="text-green-600">Exitosamente</span>
                </h3>
                
                <p class="text-[11px] text-gray-500 font-medium truncate">
                  Finalizado el 
                  <span class="text-gray-800 font-bold">
                    {{ new Date(form.fecha_fin).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                  </span>
                </p>
              </div>

              <div v-if="$page.props.auth.user?.permissions.includes('reabrir_tramites')" 
                  class="flex flex-col items-end border-l border-gray-100 pl-6 gap-1">
                <button @click="reabrirTramite"
                        type="button"
                        class="group/btn text-green-600 flex items-center gap-2 px-3 py-2 rounded-full bg-green-50 hover:bg-green-600 text-gray-500 hover:text-white transition-all duration-200 border border-green-400 hover:border-green-200 shadow-sm">
                  <svg class="w-4 h-4 transition-transform group-hover/btn:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                  </svg>
                  <div class="flex flex-col items-start leading-none">
                    <span class="text-[12px] font-black uppercase">Habilitar Edición</span>
                    <span class="text-[8px] uppercase opacity-70 font-bold">Reabrir para correcciones</span>
                  </div>
                </button>
              </div>
            </div>
          </div>
          <div v-if="!isPage" class="flex justify-end space-x-4 mt-4">
            <button type="button" @click="$emit('cancel')" 
              class="px-8 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300 transition-all">
              Cerrar
            </button> 
          </div>
        </section>
        <div v-if="form.id_estatus != 99" class="flex justify-end space-x-4 mt-4">
          <button type="button" @click="$emit('cancel')" 
            class="px-8 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300 transition-all">
            Cancelar
          </button> 
          <button v-if="(registroActualizado && form.id_estatus == 2) || (registroCreado && form.id_estatus == 2) || (props.documentoData && form.id_estatus_original == 2)" 
          type="button" @click="generarConstancia"
          class="inline-flex items-center gap-2 px-8 py-2.5 text-sm font-medium text-white bg-color1-400 rounded-full hover:bg-color1-500 transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Generar Constancia </span>
          </button>  
          <button type="submit" 
            :class="[
              'px-6 py-2.5 text-sm font-bold text-white rounded-full shadow-lg transition-all active:scale-95 flex items-center gap-2',
              [2, 3, 4, 5].includes(form.id_estatus) ? 'bg-color1-700 hover:bg-color1-800' : 'bg-gray-700 hover:bg-gray-800'
            ]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
            <span v-if="props.documentoData && (form.id_estatus != form.id_estatus_original)">
              {{ etiquetasEstatus[form.id_estatus] || (props.documentoData ? 'Actualizar Trámite' : 'Confirmar Datos') }}
            </span>
            <span v-else>
                {{ 
                    (
                        (form.id_estatus && (form.id_estatus == form.id_estatus_original)) || 
                        (form.id_estatus == 2 && registroCreado)
                    ) 
                    ? 'Actualizar Trámite' 
                    : 'Confirmar Datos' 
                }}
            </span>
          </button>
        </div>
        <div
          v-if="isSavingModal"
          style="position: absolute; 
              top: 0;             
              left: 0;            
              display: flex;
              align-items: center;
              justify-content: center;
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
          class="fixed inset-0 flex flex-col items-center justify-center transition-opacity duration-300"
          style="
           position: absolute; 
              top: 0;             
              left: 0;            
              display: flex;
              align-items: center;
              justify-content: center;
              z-index: 100000; 
              background-color: rgba(255, 255, 255, 0.85); /* Opacidad para destacar */
              width: 100%;
              height: 100%;">
          <div class="relative flex items-center justify-center">
            <div class="w-16 h-16 border-4 border-gray-200 border-t-color1-600 rounded-full animate-spin"></div>
            <svg class="absolute w-6 h-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
          </div>

          <div class="mt-4 flex flex-col items-center">
            <span class="text-lg font-bold text-gray-700 tracking-wide">Procesando</span>
            <span class="text-sm text-gray-500 animate-pulse">Por favor, espera un momento...</span>
          </div>
        </div>
        <div v-if="isLoadingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
            <div class="flex items-center">
                <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-300">Cargando...</span>
            </div>
        </div>
        <div v-if="form.id_estatus == 99 && showDocs" 
            class="fixed inset-0 bg-black/20 backdrop-blur-[2px]" 
            style="z-index: 9998;">
        </div>

        <div v-if="form.id_estatus == 99 && showDocs" 
            class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 mb-4" 
            style="z-index: 9999">
        </div>
      </div>
      <div v-if="documentacion.length > 0 && form.id_estatus != 99" 
        :class="['transition-all duration-500 ease-in-out border-l border-gray-100 flex flex-col', 
        isDocOpen ? 'md:w-[26%] bg-gray-50/50' : 'md:w-16 bg-gray-100 cursor-pointer hover:bg-gray-200']" @click="!isDocOpen ? isDocOpen = true : null">
        <div class="pt-6 pb-4 px-6 flex items-center justify-between bg-white/50 backdrop-blur-sm sticky top-0 z-2">
            <div v-if="isDocOpen" class="flex items-center gap-2 overflow-hidden">
                <div class="p-1.5 bg-color1-600 rounded-lg shadow-sm shadow-color1-200">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-[12px] ml-2 font-black text-gray-800 uppercase tracking-widest">Validar Requisitos</h3>
            </div>
            <button v-if="isDocOpen" @click.stop="isDocOpen = false" class="text-gray-400 hover:text-color1-600 transition-colors p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <div v-else class="flex flex-col items-center gap-4 py-4 w-full">
                <div class="p-1.5 rounded-full transition-all bg-color1-50 text-color1-700 animate-soft-pulse">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div 
                    :class="[
                      'absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-bold text-white shadow-sm transition-colors duration-300',
                      form.documentacion_entregada?.length === documentacion?.length 
                        ? 'bg-color2-500' 
                        : 'bg-red-500'
                    ]">
                      {{ form.documentacion_entregada?.length ?? 0 }}
                  </div>
                </div>
                <span class="text-color1-700 font-bold uppercase text-xs tracking-widest vertical-text">Documentación &nbsp; Entregada</span>
                <div v-if="(form.documentacion_entregada?.length ?? 0) < documentacion?.length" 
                    class="mt-4 flex flex-col items-center gap-1">
                    <span class="text-[9px] font-black text-red-500 uppercase vertical-text tracking-tighter">
                        Incompleto
                    </span>
                    <div class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></div>
                </div>

                <div v-else class="mt-4 flex flex-col items-center gap-1">
                    <span class="text-[9px] font-black text-color2-500 uppercase vertical-text tracking-tighter">
                        Validado
                    </span>
                    <svg class="w-3 h-3 text-color2-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                    </svg>
                </div>      
            </div>
        </div>
        <div v-if="isDocOpen" class="py-3 px-5 mx-6 mt-2 mb-2 rounded-xl bg-color1-50 border border-color1-100">
            <div class="flex gap-3">
                <svg class="w-4 h-4 text-color1-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-[12px]">
                    Verifica el expediente. <strong>Marca</strong> los requisitos válidos.
                </p>
            </div>
        </div>
        <div v-if="isDocOpen" class="w-full flex-grow justify-center overflow-hidden flex flex-col min-h-0">
          <div class="pr-7 pl-9 py-2 w-full space-y-3 justify-center overflow-y-auto custom-scrollbar animate-fade-in max-h-[600px]">
              <label v-for="(doc, index) in documentacion" :key="index" 
                  :class="[
                      'w-full flex items-start gap-3 px-4 py-3 rounded-2xl border transition-all duration-300 cursor-pointer group relative overflow-hidden',
                      form.documentacion_entregada?.includes(doc.id_requisito) 
                      ? 'bg-color2-50/50 border-color2-200 shadow-sm hover:border-color2-600 hover:shadow-md' 
                      : 'bg-white border-gray-100 hover:border-color1-200 hover:shadow-md'
                  ]">
                  <div :class="['absolute left-0 top-0 bottom-0 w-1 transition-all', form.documentacion_entregada?.includes(doc.id_requisito) ? 'bg-color2-200 group-hover:bg-color2-600' : 'bg-transparent group-hover:bg-color1-300']"></div>
                  <div class="relative flex items-center justify-center mt-0.5">
                      <input 
                        type="checkbox" 
                        v-model="form.documentacion_entregada" 
                        :value="doc.id_requisito" 
                        class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border-2 border-gray-300 
                              transition-all 
                              bg-white
                              checked:bg-color2-600 checked:border-color2-600 
                              focus:outline-none focus:ring-2 focus:ring-color2-200"/>
                      <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4.5">
                          <path d="M5 13l4 4L19 7" />
                      </svg>
                  </div>
                  <div class="flex flex-col gap-1">
                    <!-- <el-tooltip :content="doc.requisito_documentacion?.nombre" effect="customized" :show-after="250" -->
                    <!-- :hide-after="0"> -->
                    <span :class="['text-[10px] font-bold uppercase tracking-tight leading-tight transition-colors line-clamp-2', form.documentacion_entregada?.includes(doc.id_requisito) ? 'text-color2-600 group-hover:text-color2-800' : 'text-gray-600 group-hover:text-gray-900']">
                        {{ doc.nombre }}
                    </span>
                    <!-- </el-tooltip> -->
                  </div>
              </label>
            </div>
        </div>
        <div v-if="isDocOpen" class="p-6 bg-white border-t border-gray-100">
            <div class="flex flex-col gap-2">
                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                    <span class="text-gray-400">Progreso</span>
                    <span :class="form.documentacion_entregada?.length === documentacion?.length ? 'text-color2-600' : 'text-color1-700'">
                        {{ form.documentacion_entregada?.length ?? 0 }} de {{ documentacion?.length }}
                    </span>
                </div>
                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-color2-500 transition-all duration-500 ease-out"
                      :style="`width: ${((form.documentacion_entregada?.length ?? 0) / (documentacion?.length || 1)) * 100}%`"
                    ></div>
                </div>
            </div>
        </div>
      </div>      
    </form>
  </div>
  <transition name="fade">
      <div v-if="showGeoData" @click="showGeoData = false" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-"></div>
  </transition>
  <transition name="slide-rtl">
      <div v-if="showGeoData" 
          class="fixed right-0 top-0 h-full w-[350px] bg-white shadow-[-20px_0_50px_rgba(0,0,0,0.1)] z- border-l border-gray-100 p-8 flex flex-col">
          
          <div class="flex items-center justify-between mb-2">
              <div>
                  <h3 class="text-color1-700 font-black text-xl leading-tight">Ubicación Técnica</h3>
                  <p class="text-[11px] text-gray-400 font-bold uppercase mt-1">Georreferenciación del {{ form.tipo_propiedad }}</p>
              </div>
              <button @click="showGeoData = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
          </div>

          <div class="mb-6 bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col h-auto">
              <div class="h-1.5 bg-color1-500 w-full flex-shrink-0 opacity-80"></div>
              
              <div class="p-4 flex flex-col gap-3">
                  <div class="flex items-start gap-3">
                      <div class="p-2 bg-color1-50 rounded-xl flex-shrink-0">
                          <svg class="w-5 h-5 text-color1-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          </svg>
                      </div>
                      <div class="min-w-0">
                          <span class="text-[9px] font-black text-color1-600 uppercase tracking-widest block mb-0.5">Domicilio</span>
                          <p class="text-[11px] font-black text-gray-800 leading-tight uppercase">
                              {{ direccionFormateada }}
                          </p>
                      </div>
                  </div>

                  <div class="pt-0 border-t border-gray-50 flex flex-col gap-2">
                      <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Número Oficial en Asignación</span>
                      
                      <div class="flex items-center gap-2">
                          <span class="text-[11px] font-mono font-black text-gray-700 bg-gray-100 px-2 py-1 rounded-md border border-gray-200">
                              {{ form.numero_asignado || 'S/N' }}
                          </span>
                          
                          <div class="flex-grow">
                              <p class="text-[10px] font-bold text-gray-500 uppercase italic leading-none">
                                  {{ form.numero_asignado_letra }}
                              </p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <div class="flex items-center gap-3 mb-8">
              <div class="relative flex items-center justify-center h-5 w-5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-color1-400 opacity-20"></span>
                  <svg class="relative w-3.5 h-3.5 text-color1-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                  </svg>
              </div>
              <span class="text-[11px] font-black text-gray-700 uppercase tracking-widest whitespace-nowrap">
                  Completar Información
              </span>
              <div class="h-[1px] flex-grow bg-gray-100"></div>
          </div>
          <div class="space-y-7 flex-grow">
              <div v-if="!form.propiedad?.codigo_postal" class="relative">
                  <input type="text" ref="codigoPostalRef" v-model="form.codigo_postal" maxlength="5"
                      class="block w-full px-0 py-1 text-sm bg-transparent border-0 border-b-2 border-gray-200 focus:outline-none focus:ring-0 focus:border-color1-600 peer font-bold" placeholder="" />
                  <label class="absolute text-sm text-gray-500 duration-300 transform top-2 origin- pointer-events-none whitespace-nowrap
                    /* POSICIÓN ARRIBA (Por defecto) */
                    -translate-y-6 translate-x-0 scale-100 
                    
                    /* POSICIÓN ABAJO (Cuando el input está vacío) */
                    peer-placeholder-shown:-translate-y-2 peer-placeholder-shown:-translate-x-1 peer-placeholder-shown:scale-90
                    
                    /* CUANDO TIENE FOCUS (Aseguramos que se quede arriba/azul) */
                    peer-focus:-translate-y-6 peer-focus:translate-x-0 peer-focus:scale-100 peer-focus:text-color1-600">
                    Código Postal
                </label>
              </div>
              <div class="relative">
                  <input type="text" v-model="form.coordenada_utm_x"
                      class="block w-full px-0 py-1 text-sm bg-transparent border-0 border-b-2 border-gray-200 focus:outline-none focus:ring-0 focus:border-color1-600 peer font-bold" placeholder=" " />
                  <label class="absolute text-sm text-gray-500 duration-300 transform top-2 origin- pointer-events-none whitespace-nowrap
                    /* POSICIÓN ARRIBA (Por defecto) */
                    -translate-y-6 translate-x-0 scale-100 
                    
                    /* POSICIÓN ABAJO (Cuando el input está vacío) */
                    peer-placeholder-shown:-translate-y-2 peer-placeholder-shown:-translate-x-2 peer-placeholder-shown:scale-90
                    
                    /* CUANDO TIENE FOCUS (Aseguramos que se quede arriba/azul) */
                    peer-focus:-translate-y-6 peer-focus:translate-x-0 peer-focus:scale-100 peer-focus:text-color1-600">
                    Coordenada UTM (X)
                  </label>
              </div>
              <div class="relative">
                  <input type="text" v-model="form.coordenada_utm_y"
                      class="block w-full px-0 py-1 text-sm bg-transparent border-0 border-b-2 border-gray-200 focus:outline-none focus:ring-0 focus:border-color1-600 peer font-bold" placeholder=" " />
                  <label class="absolute text-sm text-gray-500 duration-300 transform top-2 origin- pointer-events-none whitespace-nowrap
                    /* POSICIÓN ARRIBA (Por defecto) */
                    -translate-y-6 translate-x-0 scale-100 
                    
                    /* POSICIÓN ABAJO (Cuando el input está vacío) */
                    peer-placeholder-shown:-translate-y-2 peer-placeholder-shown:-translate-x-2 peer-placeholder-shown:scale-90
                    
                    /* CUANDO TIENE FOCUS (Aseguramos que se quede arriba/azul) */
                    peer-focus:-translate-y-6 peer-focus:translate-x-0 peer-focus:scale-100 peer-focus:text-color1-600">
                    Coordenada UTM (Y)
                  </label>
              </div>
              <div class="relative pt-0 mt-0">
                  <label class="block text-[14px] text-gray-500 mb-2">Referencias de Ubicación</label>
                  <textarea 
                      v-model="form.referencias_ubicacion" 
                      rows="3" 
                      class="w-full placeholder:text-gray-400 placeholder:font-normal placeholder:text-xs placeholder:italic text-sm p-3 font-bold bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-color1-600 focus:ring-1 focus:ring-color1-600 transition-all resize-none uppercase"
                      placeholder="Ejemplo: Entre calle Hidalgo y Av. Juárez...">
                  </textarea>
              </div>
          </div>
          <div class="pt-4">
              <button @click="guardarGeo()" 
                  class="w-full py-2 bg-color1-600 text-white rounded-full font-bold text-[15px] hover:bg-color1-700 transition-all">
                  Aceptar
              </button>
          </div>
          <transition name="fade">
          <div v-if="isSavingGeo" 
              class="absolute inset-0 z- bg-white/60 backdrop-blur-[2px] flex flex-col items-center justify-center rounded-l-3xl">
              
              <div class="w-48 flex flex-col items-center gap-3">
                  <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden relative">
                      <div class="absolute inset-y-0 left-0 bg-color1-600 w-1/2 rounded-full animate-[loading_1.5s_infinite_ease-in-out]"></div>
                  </div>
                  <div class="flex items-center gap-2">
                      <span class="flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-color1-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-color1-500"></span>
                      </span>
                      <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Guardando Datos</span>
                  </div>
              </div>
              <!-- <div class="mt-4 flex flex-col items-center">
                  <span class="text-[11px] font-black text-color1-700 uppercase tracking-[0.2em] animate-pulse">
                      Guardando Datos
                  </span>
                  <span class="text-[9px] text-gray-400 font-bold uppercase mt-1">
                      Actualizando Georreferenciación
                  </span>
              </div> -->
          </div>
        </transition>
      </div>
  </transition>
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
@keyframes warningPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
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
/* Modificamos el input interno para usar el gris oscuro de Tailwind */
:deep(.custom-datepicker input.el-input__inner) {
    color: theme('colors.gray.800') !important;                  
    -webkit-text-fill-color: theme('colors.gray.800') !important; 
}

/* Opcional: Si quieres que el icono también haga juego con ese gris */
:deep(.custom-datepicker .el-input__icon) {
    color: theme('colors.gray.800') !important;
}
</style>