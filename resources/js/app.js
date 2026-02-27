import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';

import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

 
axios.defaults.headers.common['Accept'] = 'application/json';

 
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

 axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('access_token');
            router.push('/');
        }
        return Promise.reject(error);
    }
);

const app = createApp(App);

app.use(router);

app.use(Vue3Toastify, {
    autoClose: 3000,  
    position: 'top-right',
    theme: 'colored',  
    clearOnUrlChange: false,
});

app.mount('#app');