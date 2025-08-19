import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuth } from '@/utils/composables/useAuth';

export const useSubmissions = () => {
    const router = useRouter();
    const route = useRoute();
    const toast = useToast();
    const { checkAuth } = useAuth();

    // State
    const showDialog = ref(false);
    const loading = ref(false);
    const tableLoading = ref(false);
    const form = ref({
        title: '',
        language: '',
        code_content: '',
        file_path: '',
        repository_id: ''
    });
    const submissions = ref([]);
    const repository = ref(null);
    const languages = ['php', 'javascript', 'python', 'java', 'cpp', 'ruby', 'go'];

    // API Base URL - adjust this to match your Laravel API
    const API_BASE_URL = 'http://localhost:8000/api';

    // Helper function to make authenticated API requests
    const apiRequest = async (endpoint, options = {}) => {
        // Try different possible token storage keys
        const token = localStorage.getItem('auth_token') ||
            localStorage.getItem('token') ||
            localStorage.getItem('access_token') ||
            sessionStorage.getItem('auth_token') ||
            sessionStorage.getItem('token');

        console.log('Token found:', token ? 'Yes' : 'No');
        console.log('Making request to:', `${API_BASE_URL}${endpoint}`);

        if (!token) {
            console.error('No authentication token found');
            throw new Error('No authentication token found. Please log in again.');
        }

        const defaultOptions = {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers,
            },
        };

        console.log('Request headers:', mergedOptions.headers);

        const response = await fetch(`${API_BASE_URL}${endpoint}`, mergedOptions);

        console.log('Response status:', response.status);

        if (!response.ok) {
            if (response.status === 401) {
                console.error('Authentication failed - token may be expired');
                // Clear potentially invalid token
                localStorage.removeItem('auth_token');
                localStorage.removeItem('token');
                sessionStorage.removeItem('auth_token');
                sessionStorage.removeItem('token');
                throw new Error('Authentication failed. Please log in again.');
            }

            const errorData = await response.json().catch(() => ({ message: 'An error occurred' }));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        return response.json();
    };

    const fetchRepository = async () => {
        try {
            const repoId = parseInt(route.params.repositoryId);
            console.log('Fetching repository:', repoId);

            // Additional validation
            if (!repoId || isNaN(repoId) || repoId <= 0) {
                throw new Error('Invalid repository ID');
            }

            const data = await apiRequest(`/repositories/${repoId}`);

            if (data.success) {
                repository.value = data.repository;
                form.value.repository_id = repoId;
            } else {
                throw new Error('Repository not found');
            }
        } catch (error) {
            console.error('Error fetching repository:', error);

            if (error.message.includes('Authentication failed')) {
                toast.add({
                    severity: 'error',
                    summary: 'Authentication Error',
                    detail: 'Please log in again',
                    life: 3000
                });
                router.push('/auth/login1');
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'Repository not found',
                    life: 3000
                });
                router.push('/repositories');
            }
        }
    };

    const fetchSubmissions = async () => {
        try {
            tableLoading.value = true;
            const repoId = parseInt(route.params.repositoryId);

            const data = await apiRequest(`/code-submissions?repository_id=${repoId}`);

            if (data.success) {
                submissions.value = data.submissions.data || data.submissions;
            } else {
                throw new Error('Failed to fetch submissions');
            }
        } catch (error) {
            console.error('Error fetching submissions:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Failed to fetch submissions',
                life: 3000
            });
        } finally {
            tableLoading.value = false;
        }
    };

    const addSubmission = async () => {
        try {
            loading.value = true;

            const data = await apiRequest('/code-submissions', {
                method: 'POST',
                body: JSON.stringify({
                    title: form.value.title,
                    language: form.value.language,
                    code_content: form.value.code_content,
                    file_path: form.value.file_path || null,
                    repository_id: parseInt(form.value.repository_id)
                })
            });

            if (data.success) {
                await fetchSubmissions(); // Refresh the list
                showDialog.value = false;
                form.value = {
                    title: '',
                    language: '',
                    code_content: '',
                    file_path: '',
                    repository_id: parseInt(route.params.repositoryId)
                };
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Submission created successfully',
                    life: 3000
                });
            } else {
                throw new Error(data.message || 'Failed to create submission');
            }
        } catch (error) {
            console.error('Error creating submission:', error);
            handleApiError(error, 'Failed to create submission');
        } finally {
            loading.value = false;
        }
    };

    const deleteSubmission = async (id) => {
        try {

            const data = await apiRequest(`/code-submissions/${id}`, {
                method: 'DELETE'
            });

            if (data.success) {
                await fetchSubmissions(); // Refresh the list
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Submission deleted successfully',
                    life: 3000
                });
            } else {
                throw new Error(data.message || 'Failed to delete submission');
            }
        } catch (error) {
            console.error('Error deleting submission:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Failed to delete submission',
                life: 3000
            });
        }
    };

    // Helper function to handle API errors including validation errors
    const handleApiError = async (error, defaultMessage) => {
        try {
            // Try to parse validation errors from the response
            const response = await fetch(error.response?.url || '', {
                method: 'HEAD'
            }).catch(() => null);

            if (response?.status === 422) {
                // Handle validation errors
                const errorData = await response.json().catch(() => ({}));
                const validationErrors = errorData.errors;

                if (validationErrors) {
                    Object.keys(validationErrors).forEach(field => {
                        validationErrors[field].forEach(message => {
                            toast.add({
                                severity: 'error',
                                summary: 'Validation Error',
                                detail: message,
                                life: 5000
                            });
                        });
                    });
                    return;
                }
            }
        } catch (e) {
            // Fallback to regular error handling
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || defaultMessage,
            life: 3000
        });
    };

    return {
        // State
        showDialog,
        loading,
        tableLoading,
        form,
        submissions,
        repository,
        languages,

        // Methods
        addSubmission,
        deleteSubmission,
        fetchSubmissions,
        fetchRepository
    };
};
