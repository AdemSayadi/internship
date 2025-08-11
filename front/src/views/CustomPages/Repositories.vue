<template>
    <MainLayout>
        <PageHeader title="Your Repositories" subtitle="Manage your repositories and submit code for AI-powered reviews." />

        <!-- Add Repository Buttons -->
        <div class="mt-10 flex justify-center gap-4">
            <StyledButton label="Add Local Repository" icon="plus" @click="showLocalDialog = true" />
            <StyledButton label="Add GitHub Repository" icon="github" @click="fetchAvailableGithubRepos" />
        </div>

        <!-- Local Repository Dialog -->
        <Dialog v-model:visible="showLocalDialog" header="Add Local Repository" modal class="w-full max-w-md">
            <form @submit.prevent="addRepository" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name*</label>
                    <InputText v-model="form.name" class="w-full" placeholder="Repository Name" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">URL</label>
                    <InputText v-model="form.url" class="w-full" placeholder="https://github.com/..." />
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

        <!-- GitHub Repositories Dialog -->
        <Dialog v-model:visible="showGithubDialog" header="Select GitHub Repositories" modal class="w-full max-w-2xl">
            <div v-if="loadingGithubRepos" class="text-center py-8">
                <i class="pi pi-spinner pi-spin text-2xl"></i>
                <p class="mt-2">Loading your GitHub repositories...</p>
            </div>

            <div v-else-if="githubError" class="text-center py-8 text-red-500">
                <i class="pi pi-exclamation-triangle text-2xl"></i>
                <p class="mt-2">{{ githubError }}</p>
                <Button
                    label="Connect GitHub Account"
                    icon="pi pi-github"
                    class="mt-4"
                    @click="$router.push('/integrations')"
                />
            </div>

            <div v-else class="space-y-4">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        {{ availableGithubRepos.length }} repositories found
                    </div>
                    <div class="text-sm font-medium">
                        Selected: {{ selectedGithubRepos.length }}
                    </div>
                </div>

                <DataTable
                    :value="availableGithubRepos"
                    selectionMode="multiple"
                    v-model:selection="selectedGithubRepos"
                    dataKey="id"
                    class="w-full"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                >
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                    <Column field="name" header="Name" sortable />
                    <Column field="full_name" header="Full Name" sortable />
                    <Column field="private" header="Visibility" sortable>
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.private ? 'Private' : 'Public'"
                                 :severity="slotProps.data.private ? 'info' : 'success'" />
                        </template>
                    </Column>
                    <Column field="html_url" header="URL">
                        <template #body="slotProps">
                            <a :href="slotProps.data.html_url" target="_blank" class="text-indigo-600 hover:underline">
                                {{ slotProps.data.html_url }}
                            </a>
                        </template>
                    </Column>
                </DataTable>

                <div class="flex justify-end gap-2 mt-4">
                    <Button
                        label="Cancel"
                        icon="pi pi-times"
                        class="p-button-secondary"
                        @click="showGithubDialog = false"
                    />
                    <Button
                        label="Add Selected"
                        icon="pi pi-plus"
                        :loading="addingGithubRepos"
                        :disabled="selectedGithubRepos.length === 0"
                        @click="addGithubRepositories"
                    />
                </div>
            </div>
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
            <Column field="provider" header="Provider" sortable>
                <template #body="slotProps">
                    <Tag :value="slotProps.data.provider"
                         :severity="getProviderSeverity(slotProps.data.provider)" />
                </template>
            </Column>
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
                            label="View Pull Requests"
                            icon="pi pi-git-merge"
                            class="p-button-text p-button-sm"
                            @click="viewPullRequests(slotProps.data)"
                        />
                        <Button
                            v-if="slotProps.data.provider === 'manual'"
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
                    <label class="block text-sm font-medium text-gray-700">Name*</label>
                    <InputText v-model="editForm.name" class="w-full" placeholder="Repository Name" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">URL</label>
                    <InputText v-model="editForm.url" class="w-full" placeholder="https://github.com/..." />
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
import Tag from 'primevue/tag';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import StyledButton from '@/components/CustomComponents/StyledButton.vue';
import { useAuth } from '@/utils/composables/useAuth';

// State
const showLocalDialog = ref(false);
const showGithubDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const loading = ref(false);
const loadingRepositories = ref(true);
const loadingGithubRepos = ref(false);
const addingGithubRepos = ref(false);
const updating = ref(false);
const deleting = ref(false);
const githubError = ref(null);

const form = ref({ name: '', url: '', provider: 'manual' });
const editForm = ref({ name: '', url: '', provider: 'manual' });
const repositories = ref([]);
const availableGithubRepos = ref([]);
const selectedGithubRepos = ref([]);
const repositoryToEdit = ref(null);
const repositoryToDelete = ref(null);

const router = useRouter();
const toast = useToast();

// API base URL
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Utility functions
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

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getProviderSeverity = (provider) => {
    switch (provider) {
        case 'github': return 'info';
        case 'gitlab': return 'warning';
        default: return 'success';
    }
};

// Repository operations
const fetchRepositories = async () => {
    try {
        loadingRepositories.value = true;
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/repositories`, { headers });

        if (response.status === 401) {
            localStorage.removeItem('token');
            router.push('/auth/login1');
            return;
        }

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        repositories.value = data.repositories || [];
    } catch (error) {
        console.error('Error fetching repositories:', error);

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

const addRepository = async () => {
    if (!form.value.name) {
        toast.add({
            severity: 'warn',
            summary: 'Validation Error',
            detail: 'Name is required',
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
        form.value = { name: '', url: '', provider: 'manual' };
        showLocalDialog.value = false;

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

const editRepository = (repository) => {
    repositoryToEdit.value = repository;
    editForm.value = {
        name: repository.name,
        url: repository.url || '',
        provider: repository.provider
    };
    showEditDialog.value = true;
};

const updateRepository = async () => {
    if (!editForm.value.name) {
        toast.add({
            severity: 'warn',
            summary: 'Validation Error',
            detail: 'Name is required',
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

const cancelEdit = () => {
    showEditDialog.value = false;
    repositoryToEdit.value = null;
    editForm.value = { name: '', url: '', provider: 'manual' };
};

const confirmDelete = (repository) => {
    repositoryToDelete.value = repository;
    showDeleteDialog.value = true;
};

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

// GitHub operations
const fetchAvailableGithubRepos = async () => {
    try {
        loadingGithubRepos.value = true;
        githubError.value = null;
        showGithubDialog.value = true;
        selectedGithubRepos.value = [];

        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/github/fetch-repos`, { headers });

        if (!response.ok) {
            const errorData = await response.json();
            if (errorData.message.includes('No GitHub token')) {
                githubError.value = 'Please connect your GitHub account first';
                return;
            }
            throw new Error(errorData.message || 'Failed to fetch GitHub repositories');
        }

        const data = await response.json();
        availableGithubRepos.value = data.repositories || [];
    } catch (error) {
        console.error('Error fetching GitHub repositories:', error);
        githubError.value = error.message || 'Failed to fetch GitHub repositories';
    } finally {
        loadingGithubRepos.value = false;
    }
};

const addGithubRepositories = async () => {
    if (selectedGithubRepos.value.length === 0) return;

    try {
        addingGithubRepos.value = true;
        const headers = getAuthHeaders();
        if (!headers) return;

        const reposToAdd = selectedGithubRepos.value.map(repo => ({
            name: repo.name,
            url: repo.html_url,
            provider: 'github',
            github_repo_id: repo.id,
            full_name: repo.full_name,
            private: repo.private // Add this if your database supports it
        }));

        console.log('Sending these repositories to backend:', reposToAdd); // Debug log

        const response = await fetch(`${API_BASE}/repositories/batch`, {
            method: 'POST',
            headers: {
                ...headers,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ repositories: reposToAdd })
        });

        console.log('Response status:', response.status); // Debug log

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error('Detailed error from backend:', errorData); // Debug log
            throw new Error(errorData.message || 'Failed to add GitHub repositories. Status: ' + response.status);
        }

        const data = await response.json();
        repositories.value.push(...data.repositories);
        showGithubDialog.value = false;
        selectedGithubRepos.value = [];

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Added ${data.repositories.length} GitHub repositories`,
            life: 5000,
        });
    } catch (error) {
        console.error('Full error adding GitHub repositories:', error); // Debug log
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'Failed to add GitHub repositories. Check console for details.',
            life: 5000,
        });
    } finally {
        addingGithubRepos.value = false;
    }
};

// Navigation function for Pull Requests
const viewPullRequests = (repository) => {
    router.push({
        path: '/pull-requests',
        query: { repository_id: repository.id }
    });
};

// Initialize
const { checkAuth } = useAuth();

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }
    await fetchRepositories();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
