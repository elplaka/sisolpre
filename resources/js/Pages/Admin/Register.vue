<template>
  <AdminLayout>
    <div class="max-w-md mx-auto mt-10">
    <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Registrar Usuario</h1>
    <form @submit.prevent="register" class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-4 border border-gray-200">
      <!-- Campo Nombre -->
      <div class="mb-5">
        <label for="name" class="block text-gray-700 text-sm font-semibold mb-2">Nombre</label>
        <input type="text" v-model="form.name" id="name"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none" 
          placeholder="Ingresa tu nombre" required />
      </div>
      
      <!-- Campo Email -->
      <div class="mb-5">
        <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Correo Electrónico</label>
        <input type="email" v-model="form.email" id="email"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
          placeholder="Ingresa tu correo" required />
      </div>

      <!-- Campo Contraseña -->
      <div class="mb-5">
        <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Contraseña</label>
        <input type="password" v-model="form.password" id="password"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
          placeholder="Ingresa tu contraseña" required />
      </div>

      <!-- Confirmar Contraseña -->
      <div class="mb-6">
        <label for="password_confirmation" class="block text-gray-700 text-sm font-semibold mb-2">Confirmar Contraseña</label>
        <input type="password" v-model="form.password_confirmation" id="password_confirmation"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
          placeholder="Confirma tu contraseña" required />
      </div>

      <!-- Botón con colores seleccionables -->
      <div class="flex items-center justify-center">
        <button type="submit" class="btn-1 px-5 py-2.5 text-white font-bold rounded-md focus:outline-none">
          Registrar
        </button>
      </div>
    </form>
</div>
</AdminLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Pages/Admin/Components/AdminLayout.vue'

export default {
  setup() {
    const form = useForm({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
    });

    const register = () => {
      form.post(route('admin.register.post'));
    };

    return { form, register };
  },
  components: {
    AdminLayout,
  }
};

</script>

