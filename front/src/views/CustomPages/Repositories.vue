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
        <DataTable
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
                    <a :href="slotProps.data.url" target="_blank" class="text-indigo-600 hover:underline">
                        {{ slotProps.data.url || 'N/A' }}
                    </a>
                </template>
            </Column>
            <Column header="Actions">
                <template #body="slotProps">
                    <Button
                        label="View Submissions"
                        icon="pi pi-eye"
                        class="p-button-text"
                        @click="$router.push(`/submissions/${slotProps.data.id}`)"
                    />
                    <Button
                        label="Delete"
                        icon="pi pi-trash"
                        class="p-button-text p-button-danger"
                        @click="deleteRepository(slotProps.data.id)"
                    />
                </template>
            </Column>
        </DataTable>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref} from 'vue';
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

const showDialog = ref(false);
const loading = ref(false);
const form = ref({ name: '', url: '', provider: '' });
const repositories = ref([
    { id: 1, name: 'Project Alpha', url: 'https://github.com/user/project-alpha', provider: 'github', created_at: '2025-07-01' },
    { id: 2, name: 'Project Beta', url: null, provider: 'manual', created_at: '2025-07-15' },
]);
const router = useRouter();
const toast = useToast();

const addRepository = () => {
    loading.value = true;
    setTimeout(() => {
        repositories.value.push({
            id: repositories.value.length + 1,
            name: form.value.name,
            url: form.value.url || null,
            provider: form.value.provider || 'manual',
            created_at: new Date().toISOString(),
        });
        showDialog.value = false;
        form.value = { name: '', url: '', provider: '' };
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Repository added',
            life: 3000,
        });
        loading.value = false;
    }, 500);
};

const deleteRepository = (id) => {
    repositories.value = repositories.value.filter((repo) => repo.id !== id);
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Repository deleted',
        life: 3000,
    });
};

const { checkAuth } = useAuth();
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
