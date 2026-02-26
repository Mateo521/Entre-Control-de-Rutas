import axios from 'axios';

window.axios = axios;

// Le indicamos a Laravel que nuestras peticiones son asíncronas y esperan JSON
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// INTERCEPTOR: Antes de que salga cualquier petición, ejecuta esta función
window.axios.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    
    // Si tenemos un token guardado, lo adjuntamos como Bearer Token
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
});

// Interceptor de respuesta (Opcional pero recomendado):
// Si el token expira y Laravel nos devuelve un error 401 (No Autorizado), 
// cerramos sesión automáticamente.
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('access_token');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);