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
    </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { useSubmissions } from '@/utils/composables/useSubmissions';

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

onMounted(async () => {
    await fetchRepository();
    await fetchSubmissions();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
