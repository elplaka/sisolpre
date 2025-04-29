<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-500 bg-opacity-75">
    <div class="bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full">
      <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4 w-full">
        <div class="text-center sm:text-left w-full">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
            <strong> NUEVO USUARIO </strong>
          </h3>
          <div class="mt-4 w-full">
            <form class="w-full">
              <div class="w-full mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="name" id="name" v-model="form.name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
              </div>
              <div class="w-full mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input type="email" name="email" id="email" v-model="form.email" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
              </div>
              <div class="w-full mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" id="password" v-model="form.password" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
              </div>
              <div class="w-full mb-4">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" id="password_confirmation" v-model="form.password_confirmation" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
              </div>
              <div class="mt-4 flex justify-end space-x-3 w-full">
                <button type="button" @click="register" class="btn-1 inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                  ACEPTAR
                </button>
                <button type="button" @click="closeModal" class="btn-3 inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                  CERRAR
                </button>
              </div>
              <button @click="showAlert">Mostrar Alerta</button>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import Swal from 'sweetalert2';
import { defineEmits } from 'vue';

const emit = defineEmits(['close']); // Definir los eventos emitibles

const form = ref({
  name: '',
  email: '',
  password: '123',
  password_confirmation: '123'
});

const register = () => {
  Inertia.post(route('admin.usuarios.store'), form.value, {
     onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Usuario creado exitosamente 2',
        showConfirmButton: false,
        timer: 8500
      });

      // Cerrar el modal después de un breve retraso para permitir que SweetAlert se muestre
      setTimeout(() => {
        closeModal();
      }, 8500);
    },
    onError: (errors) => {
      console.log('Errores recibidos:', errors); // Log para errores
    }
  });
};


const showAlert = () => {
  Swal.fire({
    icon: 'success',
    title: '¡SweetAlert2 funciona correctamente!',
    showConfirmButton: false,
    timer: 1500
  });
};

const closeModal = () => {
  emit('close');
};
</script>

<style scoped>
/* Puedes añadir estilos personalizados aquí */
</style>

  
<!-- <template>
  <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-500 bg-opacity-75">
    <div class="bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full">
      <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4 w-full">
        <div class="text-center sm:text-left w-full">
          <button @click="showAlert">Mostrar Alerta</button>
         </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Swal from 'sweetalert2';

const showAlert = () => {
  Swal.fire({
    icon: 'success',
    title: '¡SweetAlert2 funciona correctamente!',
    showConfirmButton: false,
    timer: 1500
  });
};
</script> -->


  