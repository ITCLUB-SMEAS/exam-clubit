import { createApp, h } from 'vue'
import { createInertiaApp, router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import 'sweetalert2/dist/sweetalert2.min.css';
import axios from 'axios';
import { a11yDirective } from './composables/useAccessibility';

// Configure axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Handle 419 CSRF token mismatch globally
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

// Handle Inertia 419 errors
router.on('invalid', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault();
        window.location.href = '/';
    }
});

window.axios = axios;

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue')
    return pages[`./Pages/${name}.vue`]()
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
    app.config.globalProperties.route = route
    
    // Translation helper - use this.$page.props.translations
    app.config.globalProperties.__ = function(key, replacements = {}) {
        const translations = this.$page?.props?.translations || {};
        let translation = translations[key] || key;
        
        Object.keys(replacements).forEach(r => {
            translation = translation.replace(`:${r}`, replacements[r]);
        });
        
        return translation;
    };
    
    app
    //set mixins
    .mixin({
        methods: {

          examTimeRangeChecker: function (start_time, end_time) {
            return new Date() >= new Date(start_time) && new Date() <= new Date(end_time)
          },

          examTimeStartChecker: function (start_time) {
            return new Date() < new Date(start_time)
          },

          examTimeEndChecker: function (end_time) {
            return new Date() > new Date(end_time)
          }

        },
    })
    .use(plugin)
    .directive('a11y', a11yDirective)
    .mount(el)
  },
  progress: {
    // The delay after which the progress bar will appear, in milliseconds...
    delay: 250,

    // The color of the progress bar...
    color: '#29d',

    // Whether to include the default NProgress styles...
    includeCSS: true,

    // Whether the NProgress spinner will be shown...
    showSpinner: false,
  },
})