import axios from 'axios';

window.axios = axios;

 
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

 
window.axios.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    
 
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
});

 
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