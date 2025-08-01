<template>
    <MainLayout>
        <PageHeader title="Code Submissions" :subtitle="`Submit and review your code for ${repository?.name || 'Repository'}.`" />
        <div class="mt-10 flex justify-center">
            <StyledButton label="Add Submission" icon="plus" @click="showDialog = true" />
        </div>

        <Dialog v-model:visible="showDialog" header="Add Code Submission" modal class="w-full max-w-md">
            <form @submit.prevent="addSubmission" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <InputText v-model="form.title" class="w-full" placeholder="Submission Title" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Language</label>
                    <Dropdown
                        v-model="form.language"
                        :options="languages"
                        placeholder="Select Language"
                        class="w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <Textarea v-model="form.code_content" class="w-full" rows="10" placeholder="Paste your code here" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">File Path (Optional)</label>
                    <InputText v-model="form.file_path" class="w-full" placeholder="/path/to/file" />
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
            :value="submissions"
            class="mt-8"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20]"
        >
            <Column field="title" header="Title" sortable />
            <Column field="language" header="Language" sortable />
            <Column field="created_at" header="Submitted" sortable>
                <template #body="slotProps">
                    {{ new Date(slotProps.data.created_at).toLocaleDateString() }}
                </template>
            </Column>
            <Column header="Actions">
                <template #body="slotProps">
                    <Button
                        label="View Reviews"
                        icon="pi pi-eye"
                        class="p-button-text"
                        @click="$router.push(`/reviews/${slotProps.data.id}`)"
                    />
                    <Button
                        label="Delete"
                        icon="pi pi-trash"
                        class="p-button-text p-button-danger"
                        @click="deleteSubmission(slotProps.data.id)"
                    />
                </template>
            </Column>
        </DataTable>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import StyledButton from '@/components/CustomComponents/StyledButton.vue';
import { useAuth } from '@/utils/composables/useAuth';

const showDialog = ref(false);
const loading = ref(false);
const form = ref({ title: '', language: '', code_content: '', file_path: '', repository_id: '' });
const submissions = ref([]);
const repository = ref(null);
const router = useRouter();
const route = useRoute();
const toast = useToast();

const languages = ['php', 'javascript', 'python', 'java', 'cpp', 'ruby', 'go'];

const mockRepositories = [
    { id: 1, name: 'Project Alpha', url: 'https://github.com/user/project-alpha', provider: 'github', created_at: '2025-07-01' },
    { id: 2, name: 'Project Beta', url: null, provider: 'manual', created_at: '2025-07-15' },
];
const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
];

const fetchData = () => {
    const repoId = parseInt(route.params.repositoryId);
    repository.value = mockRepositories.find((repo) => repo.id === repoId) || null;
    if (!repository.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Repository not found', life: 3000 });
        router.push('/repositories');
        return;
    }
    submissions.value = mockSubmissions.filter((sub) => sub.repository_id === repoId);
    form.value.repository_id = repoId;
};

const addSubmission = () => {
    loading.value = true;
    setTimeout(() => {
        submissions.value.push({
            id: submissions.value.length + 1,
            repository_id: parseInt(form.value.repository_id),
            title: form.value.title,
            language: form.value.language,
            code_content: form.value.code_content,
            file_path: form.value.file_path || null,
            created_at: new Date().toISOString(),
            reviews: [],
        });
        showDialog.value = false;
        form.value = { title: '', language: '', code_content: '', file_path: '', repository_id: route.params.repositoryId };
        toast.add({ severity: 'success', summary: 'Success', detail: 'Submission added', life: 3000 });
        loading.value = false;
    }, 500);
};

const deleteSubmission = (id) => {
    submissions.value = submissions.value.filter((sub) => sub.id !== id);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Submission deleted', life: 3000 });
};

const { checkAuth } = useAuth();
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
    fetchData();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
