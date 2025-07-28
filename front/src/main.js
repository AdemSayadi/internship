import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import axios from 'axios';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import 'lucide-vue-next'; // Import Lucide icons
import './assets/tailwind.css'; // Tailwind CSS
import '@/assets/styles.scss'; // Custom SCSS styles

// Configure Axios
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// Create Vue app
const app = createApp(App);

// Use Pinia for state management
app.use(createPinia());

// Use Vue Router
app.use(router);

// Use PrimeVue with Aura theme and services
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.app-dark'
        }
    }
});
app.use(ToastService);
app.use(ConfirmationService);

// Mount the app
app.mount('#app');
