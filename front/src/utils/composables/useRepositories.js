import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuth } from '@/utils/composables/useAuth';

export const useRepositories = () => {
    const router = useRouter();
    const toast = useToast();
    const { checkAuth } = useAuth();

    // State
    const showDialog = ref(false);
    const showEditDialog = ref(false);
    const showDeleteDialog = ref(false);
    const loading = ref(false);
    const loadingRepositories = ref(true);
    const updating = ref(false);
    const deleting = ref(false);

    const form = ref({ name: '', url: '', provider: '' });
    const editForm = ref({ name: '', url: '', provider: '' });
    const repositories = ref([]);
    const repositoryToEdit = ref(null);
    const repositoryToDelete = ref(null);

    // API base URL - you might want to move this to a config file
    const API_BASE = 'http://localhost:8000/api';

    // Utility function to get auth headers
    const getAuthHeaders = () => {
        const token = localStorage.getItem('token');
        if (!token) {
            console.error('No token found in localStorage');
            router.push('/auth/login1');
            return null;
        }
        return {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    };

    // Format date for display
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    // Fetch repositories from API
    const fetchRepositories = async () => {
        try {
            loadingRepositories.value = true;
            const headers = getAuthHeaders();
            if (!headers) return; // Token not found, already redirected

            console.log('Fetching repositories with headers:', headers);

            const response = await fetch(`${API_BASE}/repositories`, {
                headers
            });

            console.log('Response status:', response.status);

            if (response.status === 401) {
                console.error('Unauthorized - redirecting to login');
                localStorage.removeItem('token');
                router.push('/auth/login1');
                return;
            }

            if (!response.ok) {
                const errorData = await response.json();
                console.error('API Error:', errorData);
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Repositories data:', data);
            repositories.value = data.repositories || [];
        } catch (error) {
            console.error('Error fetching repositories:', error);

            // If it's an auth error, redirect to login
            if (error.message.includes('401') || error.message.includes('Unauthorized')) {
                localStorage.removeItem('token');
                router.push('/auth/login1');
                return;
            }

            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load repositories: ' + error.message,
                life: 5000,
            });
        } finally {
            loadingRepositories.value = false;
        }
    };

    // Add new repository
    const addRepository = async () => {
        if (!form.value.name || !form.value.provider) {
            toast.add({
                severity: 'warn',
                summary: 'Validation Error',
                detail: 'Name and Provider are required',
                life: 3000,
            });
            return;
        }

        try {
            loading.value = true;
            const response = await fetch(`${API_BASE}/repositories`, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: JSON.stringify(form.value)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create repository');
            }

            const data = await response.json();
            repositories.value.push(data.repository);

            // Reset form and close dialog
            form.value = { name: '', url: '', provider: '' };
            showDialog.value = false;

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Repository added successfully',
                life: 3000,
            });
        } catch (error) {
            console.error('Error adding repository:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Failed to add repository',
                life: 5000,
            });
        } finally {
            loading.value = false;
        }
    };

    // Edit repository
    const editRepository = (repository) => {
        repositoryToEdit.value = repository;
        editForm.value = {
            name: repository.name,
            url: repository.url || '',
            provider: repository.provider
        };
        showEditDialog.value = true;
    };

    // Update repository
    const updateRepository = async () => {
        if (!editForm.value.name || !editForm.value.provider) {
            toast.add({
                severity: 'warn',
                summary: 'Validation Error',
                detail: 'Name and Provider are required',
                life: 3000,
            });
            return;
        }

        try {
            updating.value = true;
            const response = await fetch(`${API_BASE}/repositories/${repositoryToEdit.value.id}`, {
                method: 'PUT',
                headers: getAuthHeaders(),
                body: JSON.stringify(editForm.value)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update repository');
            }

            const data = await response.json();

            // Update the repository in the list
            const index = repositories.value.findIndex(repo => repo.id === repositoryToEdit.value.id);
            if (index !== -1) {
                repositories.value[index] = data.repository;
            }

            cancelEdit();

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Repository updated successfully',
                life: 3000,
            });
        } catch (error) {
            console.error('Error updating repository:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Failed to update repository',
                life: 5000,
            });
        } finally {
            updating.value = false;
        }
    };

    // Cancel edit
    const cancelEdit = () => {
        showEditDialog.value = false;
        repositoryToEdit.value = null;
        editForm.value = { name: '', url: '', provider: '' };
    };

    // Confirm delete
    const confirmDelete = (repository) => {
        repositoryToDelete.value = repository;
        showDeleteDialog.value = true;
    };

    // Delete repository
    const deleteRepository = async () => {
        try {
            deleting.value = true;
            const response = await fetch(`${API_BASE}/repositories/${repositoryToDelete.value.id}`, {
                method: 'DELETE',
                headers: getAuthHeaders()
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to delete repository');
            }

            // Remove from local list
            repositories.value = repositories.value.filter(repo => repo.id !== repositoryToDelete.value.id);

            showDeleteDialog.value = false;
            repositoryToDelete.value = null;

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Repository deleted successfully',
                life: 3000,
            });
        } catch (error) {
            console.error('Error deleting repository:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Failed to delete repository',
                life: 5000,
            });
        } finally {
            deleting.value = false;
        }
    };

    return {
        // State
        showDialog,
        showEditDialog,
        showDeleteDialog,
        loading,
        loadingRepositories,
        updating,
        deleting,
        form,
        editForm,
        repositories,
        repositoryToDelete,

        // Methods
        formatDate,
        fetchRepositories,
        addRepository,
        editRepository,
        updateRepository,
        cancelEdit,
        confirmDelete,
        deleteRepository
    };
};
