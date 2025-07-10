/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

// import axios from 'axios'
// window.axios = axios

// window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// /**
//  * Echo exposes an expressive API for subscribing to channels and listening
//  * for events that are broadcast by Laravel. Echo and event broadcasting
//  * allows your team to easily build robust real-time web applications.
//  */

// import Echo from 'laravel-echo'

// import Pusher from 'pusher-js'
// window.Pusher = Pusher

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss']
// })

// // ¡Importante para depuración!
// // Activa el modo de depuración de Echo
// window.Echo.connector.pusher.connection.bind('state_change', (states) => {
//     console.log('Pusher connection state changed:', states)
// })
// window.Echo.connector.pusher.connection.bind('connected', () => {
//     console.log('Pusher connected!')
// })

// // CAMBIO CLAVE AQUÍ: Usa connection.bind para errores relacionados con la conexión
// window.Echo.connector.pusher.connection.bind('error', (err) => {
//     console.error('Pusher connection error:', err)
//     // Puedes inspeccionar err.error.data.code y err.error.data.message para más detalles
//     if (err.error.data.code === 4004) {
//         console.error('Pusher error 4004: App Key no válida o Cluster incorrecto.')
//     }
// })
