<template>
    <MainLayout>
        <PageHeader title="Code Submissions" :subtitle="`Submit and review your code for ${repository?.name || 'Repository'}.`" />

        <!-- Loading state -->
        <div v-if="tableLoading" class="mt-8 text-center">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <p class="mt-2">Loading submissions...</p>
        </div>

        <template v-else>
            <!-- Add Submission button - shown only when submissions are loaded -->
            <div v-if="submissions.length > 0" class="mt-10 flex justify-center">
                <StyledButton label="Add Submissions" icon="plus" @click="showDialog = true" />
            </div>

            <!-- Empty state -->
            <div v-if="submissions.length === 0" class=" text-center py-12">
                <i class="pi pi-upload text-6xl text-gray-300"></i>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No submissions yet</h3>
                <p class="mt-2 mb-10 text-gray-500 max-w-sm mx-auto">
                    Get started by submitting your first code for AI-powered review and feedback.
                </p>
                <div class="mt-6">
                    <StyledButton
                        label="Submit Your First Code"
                        icon="plus"
                        @click="showDialog = true"
                        class="bg-indigo-600 hover:bg-indigo-700"
                    />
                </div>
                <div class="mt-10 text-sm text-gray-400">
                    Or connect your GitHub repository for automatic pull request reviews
                </div>
            </div>

            <!-- Data table - only show when there are submissions -->
            <DataTable
                v-if="submissions.length > 0"
                :value="submissions"
                class="mt-8"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
                :loading="tableLoading"
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
                        <div class="flex gap-2">
                            <Button
                                label="View Reviews"
                                icon="pi pi-comments"
                                size="small"
                                text
                                @click="() => $router.push(`/reviews/${slotProps.data.id}`)"
                            />
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                size="small"
                                text
                                severity="danger"
                                @click="() => deleteSubmission(slotProps.data.id)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </template>

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
    </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { useSubmissions } from '@/utils/composables/useSubmissions';
import {useAuth} from "@/utils/composables/useAuth";
import StyledButton from "@/components/CustomComponents/StyledButton.vue";
import MainLayout from "@/components/CustomComponents/MainLayout.vue";
import PageHeader from "@/components/CustomComponents/PageHeader.vue";
import router from "@/router";

const {
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
} = useSubmissions();

const { checkAuth } = useAuth();
onMounted(async () => {
    if (!checkAuth()) {
        await router.push('/auth/login1');
        return;
    }
    await fetchRepository();
    await fetchSubmissions();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
