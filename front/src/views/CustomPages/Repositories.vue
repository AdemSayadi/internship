<template>
    <MainLayout>
        <PageHeader title="Your Repositories" subtitle="Manage your repositories and submit code for AI-powered reviews." />
        <div class="mt-10 flex justify-center">
            <StyledButton label="Add Repository" icon="plus" @click="showDialog = true" />
        </div>
        <Dialog v-model:visible="showDialog" header="Add Repository" modal class="w-full max-w-md">
            <form @submit.prevent="addRepository" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <InputText v-model="form.name" class="w-full" placeholder="Repository Name" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">URL</label>
                    <InputText v-model="form.url" class="w-full" placeholder="https://github.com/..." />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Provider</label>
                    <Dropdown
                        v-model="form.provider"
                        :options="['github', 'gitlab', 'manual']"
                        placeholder="Select Provider"
                        class="w-full"
                        required
                    />
                </div>
                <Button
                    type="submit"
                    label="Submit"
                    icon="pi pi-check"
                    :loading="loading"
                    class="w-full bg-indigo-600 text-white"
                />
            </form>
        </Dialog>

        <!-- Loading state -->
        <div v-if="loadingRepositories" class="mt-8 text-center">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <p class="mt-2">Loading repositories...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="repositories.length === 0" class="mt-8 text-center py-12">
            <i class="pi pi-folder-open text-6xl text-gray-300"></i>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No repositories yet</h3>
            <p class="mt-2 text-gray-500">Get started by adding your first repository.</p>
        </div>

        <!-- Data table -->
        <DataTable
            v-else
            :value="repositories"
            class="mt-8"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20]"
        >
            <Column field="name" header="Name" sortable />
            <Column field="provider" header="Provider" sortable />
            <Column field="url" header="URL">
                <template #body="slotProps">
                    <a
                        v-if="slotProps.data.url"
                        :href="slotProps.data.url"
                        target="_blank"
                        class="text-indigo-600 hover:underline"
                    >
                        {{ slotProps.data.url }}
                    </a>
                    <span v-else class="text-gray-400">N/A</span>
                </template>
            </Column>
            <Column field="created_at" header="Created" sortable>
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
                </template>
            </Column>
            <Column header="Actions">
                <template #body="slotProps">
                    <div class="flex gap-2">
                        <Button
                            label="View Submissions"
                            icon="pi pi-eye"
                            class="p-button-text p-button-sm"
                            @click="$router.push(`/submissions/${slotProps.data.id}`)"
                        />
                        <Button
                            label="Edit"
                            icon="pi pi-pencil"
                            class="p-button-text p-button-sm"
                            @click="editRepository(slotProps.data)"
                        />
                        <Button
                            label="Delete"
                            icon="pi pi-trash"
                            class="p-button-text p-button-danger p-button-sm"
                            @click="confirmDelete(slotProps.data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Edit Dialog -->
        <Dialog v-model:visible="showEditDialog" header="Edit Repository" modal class="w-full max-w-md">
            <form @submit.prevent="updateRepository" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <InputText v-model="editForm.name" class="w-full" placeholder="Repository Name" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">URL</label>
                    <InputText v-model="editForm.url" class="w-full" placeholder="https://github.com/..." />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Provider</label>
                    <Dropdown
                        v-model="editForm.provider"
                        :options="['github', 'gitlab', 'manual']"
                        placeholder="Select Provider"
                        class="w-full"
                        required
                    />
                </div>
                <div class="flex gap-2">
                    <Button
                        type="submit"
                        label="Update"
                        icon="pi pi-check"
                        :loading="updating"
                        class="flex-1 bg-indigo-600 text-white"
                    />
                    <Button
                        type="button"
                        label="Cancel"
                        icon="pi pi-times"
                        class="flex-1 p-button-secondary"
                        @click="cancelEdit"
                    />
                </div>
            </form>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" header="Confirm Delete" modal class="w-full max-w-sm">
            <p class="mb-4">Are you sure you want to delete "{{ repositoryToDelete?.name }}"? This action cannot be undone.</p>
            <div class="flex gap-2 justify-end">
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    class="p-button-secondary"
                    @click="showDeleteDialog = false"
                />
                <Button
                    label="Delete"
                    icon="pi pi-trash"
                    class="p-button-danger"
                    :loading="deleting"
                    @click="deleteRepository"
                />
            </div>
        </Dialog>
    </MainLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import StyledButton from '@/components/CustomComponents/StyledButton.vue';
import { useAuth } from '@/utils/composables/useAuth';

// Reactive references
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

const router = useRouter();
const toast = useToast();

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

// Authentication check and initialization
const { checkAuth } = useAuth();

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }

    // Fetch repositories on component mount
    await fetchRepositories();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
