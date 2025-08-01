<template>
    <MainLayout>
        <PageHeader title="Code Reviews" :subtitle="`AI-powered reviews for ${submission?.title || 'Submission'}.`" />
        <div v-if="submission" class="mt-10">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-900">{{ submission.title }}</h2>
                <p class="mt-2 text-gray-600">Language: {{ submission.language }}</p>
                <p class="mt-2 text-gray-600">Submitted: {{ new Date(submission.created_at).toLocaleDateString() }}</p>
                <pre class="mt-4 bg-gray-100 p-4 rounded-md overflow-x-auto">{{ submission.code_content }}</pre>
            </div>

            <DataTable
                :value="submission.reviews"
                class="mt-8"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
            >
                <Column field="status" header="Status" sortable />
                <Column field="overall_score" header="Overall Score" sortable />
                <Column field="complexity_score" header="Complexity" sortable />
                <Column field="security_score" header="Security" sortable />
                <Column field="maintainability_score" header="Maintainability" sortable />
                <Column field="bug_count" header="Bugs" sortable />
                <Column field="ai_summary" header="AI Summary">
                    <template #body="slotProps">
                        {{ slotProps.data.ai_summary || 'N/A' }}
                    </template>
                </Column>
                <Column field="suggestions" header="Suggestions">
                    <template #body="slotProps">
                        <ul v-if="slotProps.data.suggestions" class="list-disc pl-5">
                            <li v-for="(suggestion, index) in slotProps.data.suggestions" :key="index">
                                {{ suggestion }}
                            </li>
                        </ul>
                        <span v-else>N/A</span>
                    </template>
                </Column>
            </DataTable>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

const submission = ref(null);
const router = useRouter();
const route = useRoute();
const toast = useToast();

// Mock data
const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
];
const mockReviews = [
    {
        id: 1,
        code_submission_id: 1,
        status: 'completed',
        overall_score: 85,
        complexity_score: 80,
        security_score: 90,
        maintainability_score: 85,
        bug_count: 2,
        ai_summary: 'Good code structure, but consider reducing complexity in loops.',
        suggestions: ['Use array methods instead of for loops', 'Add input validation'],
        created_at: '2025-07-02',
    },
];

// Fetch submission
const fetchSubmission = () => {
    const subId = parseInt(route.params.submissionId);
    submission.value = mockSubmissions.find((sub) => sub.id === subId) || null;
    if (!submission.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Submission not found', life: 3000 });
        return;
    }
    submission.value.reviews = mockReviews.filter((review) => review.code_submission_id === subId);
};

const { checkAuth } = useAuth();
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
    fetchSubmission();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
