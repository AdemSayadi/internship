import { ref } from 'vue';

export function useAuth() {
    const isAuthenticated = ref(!!localStorage.getItem('token'));

    const checkAuth = () => {
        isAuthenticated.value = !!localStorage.getItem('token');
        return isAuthenticated.value;
    };

    return { isAuthenticated, checkAuth };
}
