import './bootstrap';
import '../css/app.css';
// import '../css/custom.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import es from 'element-plus/es/locale/lang/es'
import dayjs from 'dayjs'

dayjs.Ls.es ??= {}
dayjs.Ls.es.weekStart = 1

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${appName} | ${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app =  createApp({ render: () => h(App, props) })
            app.use(plugin)
            app.use(ZiggyVue, Ziggy)
            app.use(ElementPlus, {
            locale: es,
            })
            app.use(VueSweetalert2),
            window.Swal =  app.config.globalProperties.$swal

            app.mount(el)
    },
    progress: {
        color: '#7b003a',
    },
});

