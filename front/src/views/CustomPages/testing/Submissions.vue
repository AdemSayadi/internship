<template>
    <MainLayout>
        <PageHeader
            title="Code Submissions"
            :subtitle="`Submit and review your code for ${repository?.name || 'Repository'}.`"
        />

        <!-- Action Buttons -->
        <div v-if="submissions.length > 0" class="mt-6 flex flex-wrap gap-3 justify-between items-center">
            <div class="flex gap-3">
                <StyledButton
                    label="New Submission"
                    icon="plus"
                    @click="showDialog = true"
                    class="bg-indigo-600 hover:bg-indigo-700"
                />
                <Button
                    v-if="hasUnreviewedSubmissions"
                    label="Auto-Review All"
                    icon="pi pi-magic-wand"
                    @click="autoReviewAll"
                    :loading="autoReviewing"
                    severity="secondary"
                />
            </div>

            <!-- Filter Options -->
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Filter:</label>
                <Dropdown
                    v-model="selectedFilter"
                    :options="filterOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Submissions"
                    class="w-40"
                    @change="fetchSubmissions"
                />
            </div>
        </div>

        <!-- Enhanced Dialog with AI Review Options -->
        <Dialog v-model:visible="showDialog" header="Add Code Submission" modal class="w-full max-w-2xl">
            <form @submit.prevent="addSubmission" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">File Path (Optional)</label>
                    <InputText v-model="form.file_path" class="w-full" placeholder="/path/to/file" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <Textarea
                        v-model="form.code_content"
                        class="w-full font-mono text-sm"
                        rows="12"
                        placeholder="Paste your code here"
                        required
                    />
                </div>

                <!-- AI Review Options -->
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                        <i class="pi pi-sparkles text-purple-600"></i>
                        AI Review Options
                    </h4>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="reviewOptions.autoReview"
                                inputId="auto-review"
                                binary
                            />
                            <label for="auto-review" class="text-sm">
                                Automatically trigger AI review after submission
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="reviewOptions.waitForCompletion"
                                inputId="wait-completion"
                                binary
                                :disabled="!reviewOptions.autoReview"
                            />
                            <label for="wait-completion" class="text-sm">
                                Wait for review completion before redirecting
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="reviewOptions.navigateToReview"
                                inputId="navigate-review"
                                binary
                            />
                            <label for="navigate-review" class="text-sm">
                                Go to review page after submission
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-4 border-t">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        @click="showDialog = false"
                    />
                    <Button
                        type="submit"
                        :label="reviewOptions.waitForCompletion ? 'Submit & Wait for Review' : 'Submit'"
                        icon="pi pi-check"
                        :loading="loading"
                        class="bg-indigo-600 text-white"
                    />
                </div>
            </form>
        </Dialog>

        <!-- Loading state -->
        <div v-if="tableLoading" class="mt-8 text-center">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <p class="mt-2">Loading submissions...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="submissions.length === 0" class="text-center py-12">
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

        <!-- Enhanced Data Table -->
        <DataTable
            v-else
            :value="filteredSubmissions"
            class="mt-8"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20]"
            :loading="tableLoading"
            sortField="created_at"
            :sortOrder="-1"
        >
            <Column field="title" header="Title" sortable>
                <template #body="slotProps">
                    <div>
                        <div class="font-medium">{{ slotProps.data.title }}</div>
                        <div v-if="slotProps.data.file_path" class="text-xs text-gray-500 font-mono">
                            {{ slotProps.data.file_path }}
                        </div>
                    </div>
                </template>
            </Column>

            <Column field="language" header="Language" sortable>
                <template #body="slotProps">
                    <Tag :value="slotProps.data.language" class="capitalize" />
                </template>
            </Column>

            <Column header="Review Status">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <Tag
                            :value="getSubmissionStatus(slotProps.data)"
                            :severity="getStatusSeverity(getSubmissionStatus(slotProps.data))"
                        />
                        <div v-if="getLatestReview(slotProps.data)" class="flex items-center gap-1">
                            <i class="pi pi-star-fill text-yellow-500 text-xs"></i>
                            <span class="text-xs font-medium">{{ getLatestReview(slotProps.data).score }}/10</span>
                        </div>
                    </div>
                </template>
            </Column>

            <Column field="created_at" header="Submitted" sortable>
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
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
                            @click="navigateToReviews(slotProps.data.id)"
                        />
                        <Button
                            v-if="!hasReviews(slotProps.data)"
                            label="AI Review"
                            icon="pi pi-sparkles"
                            size="small"
                            text
                            @click="triggerSingleReview(slotProps.data.id)"
                            :loading="reviewingItems.includes(slotProps.data.id)"
                        />
                        <Button
                            label="Delete"
                            icon="pi pi-trash"
                            size="small"
                            text
                            severity="danger"
                            @click="confirmDelete(slotProps.data.id)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Auto Review Progress Dialog -->
        <Dialog
            v-model:visible="showAutoReviewDialog"
            header="Auto-Reviewing Submissions"
            modal
            :closable="false"
            class="w-full max-w-md"
        >
            <div class="text-center py-4">
                <i class="pi pi-spinner pi-spin text-3xl text-indigo-600 mb-4"></i>
                <p class="mb-2">Processing {{ autoReviewProgress.total }} submissions...</p>
                <div class="bg-gray-200 rounded-full h-2 mb-2">
                    <div
                        class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${(autoReviewProgress.completed / autoReviewProgress.total) * 100}%` }"
                    ></div>
                </div>
                <p class="text-sm text-gray-600">
                    {{ autoReviewProgress.completed }} / {{ autoReviewProgress.total }} completed
                </p>
            </div>
        </Dialog>

        <!-- Confirmation Dialog -->
        <Dialog
            v-model:visible="showDeleteDialog"
            header="Confirm Delete"
            modal
            class="w-full max-w-md"
        >
            <p class="mb-4">Are you sure you want to delete this submission? This action cannot be undone.</p>
            <div class="flex gap-2 justify-end">
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showDeleteDialog = false"
                />
                <Button
                    label="Delete"
                    severity="danger"
                    @click="executeDelete"
                    :loading="deleting"
                />
            </div>
        </Dialog>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import StyledButton from "@/components/CustomComponents/StyledButton.vue";
import MainLayout from "@/components/CustomComponents/MainLayout.vue";
import PageHeader from "@/components/CustomComponents/PageHeader.vue";
import { useAuth } from "@/utils/composables/useAuth";
import { useSubmissions } from '@/utils/composables/useSubmissions';
import { useReviewFlow } from '@/utils/composables/useReviewFlow';

// Composables
const router = useRouter();
const toast = useToast();
const { checkAuth } = useAuth();

// Use existing submissions composable
const {
    showDialog,
    loading: submissionsLoading,
    tableLoading,
    form,
    submissions,
    repository,
    languages,
    fetchSubmissions,
    fetchRepository
} = useSubmissions();

// Use new review flow composable
const {
    loading: reviewFlowLoading,
    processing: reviewProcessing,
    submitCodeWithFlow,
    triggerSubmissionReview,
    autoReviewExisting,
    navigateToReview
} = useReviewFlow();

// Additional state for enhanced features
const selectedFilter = ref('all');
const reviewingItems = ref([]);
const autoReviewing = ref(false);
const showAutoReviewDialog = ref(false);
const autoReviewProgress = ref({ completed: 0, total: 0 });
const showDeleteDialog = ref(false);
const deleteItemId = ref(null);
const deleting = ref(false);

// Review options for new submissions
const reviewOptions = ref({
    autoReview: true,
    waitForCompletion: false,
    navigateToReview: true
});

// Computed properties
const loading = computed(() => submissionsLoading.value || reviewFlowLoading.value);

const filterOptions = [
    { label: 'All Submissions', value: 'all' },
    { label: 'Reviewed', value: 'reviewed' },
    { label: 'Pending Review', value: 'pending' },
    { label: 'Processing', value: 'processing' }
];

const filteredSubmissions = computed(() => {
    if (selectedFilter.value === 'all') return submissions.value;

    return submissions.value.filter(submission => {
        const status = getSubmissionStatus(submission);
        switch (selectedFilter.value) {
            case 'reviewed':
                return status === 'Reviewed';
            case 'pending':
                return status === 'Pending Review';
            case 'processing':
                return status === 'Processing';
            default:
                return true;
        }
    });
});

const hasUnreviewedSubmissions = computed(() => {
    return submissions.value.some(submission => !hasReviews(submission));
});

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getSubmissionStatus = (submission) => {
    if (!submission.reviews || submission.reviews.length === 0) return 'Pending Review';
    const latestReview = submission.reviews[0];
    return latestReview.status === 'completed' ? 'Reviewed' :
        latestReview.status === 'processing' ? 'Processing' : 'Pending Review';
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Reviewed': return 'success';
        case 'Processing': return 'info';
        case 'Pending Review': return 'warning';
        default: return 'secondary';
    }
};

const hasReviews = (submission) => {
    return submission.reviews && submission.reviews.length > 0;
};

const getLatestReview = (submission) => {
    if (!hasReviews(submission)) return null;
    return submission.reviews[0];
};

// Enhanced submission function
const addSubmission = async () => {
    try {
        const submissionData = { ...form.value };

        const result = await submitCodeWithFlow(submissionData, {
            autoReview: reviewOptions.value.autoReview,
            navigateToReview: reviewOptions.value.navigateToReview,
            waitForCompletion: reviewOptions.value.waitForCompletion
        });

        if (result) {
            // Reset form and close dialog
            Object.keys(form.value).forEach(key => {
                form.value[key] = '';
            });
            showDialog.value = false;

            // Refresh submissions if not navigating away
            if (!reviewOptions.value.navigateToReview) {
                await fetchSubmissions();
            }
        }

    } catch (error) {
        console.error('Error in submission flow:', error);
    }
};

// Navigation functions
const navigateToReviews = (submissionId) => {
    navigateToReview('submission', submissionId);
};

// Single review trigger
const triggerSingleReview = async (submissionId) => {
    try {
        reviewingItems.value.push(submissionId);

        const success = await triggerSubmissionReview(submissionId);
        if (success) {
            // Refresh submissions after a delay
            setTimeout(() => {
                fetchSubmissions();
            }, 2000);
        }

    } finally {
        reviewingItems.value = reviewingItems.value.filter(id => id !== submissionId);
    }
};

// Auto-review all unreviewed submissions
const autoReviewAll = async () => {
    try {
        autoReviewing.value = true;
        showAutoReviewDialog.value = true;

        const unreviewedSubmissions = submissions.value.filter(submission => !hasReviews(submission));

        autoReviewProgress.value = {
            completed: 0,
            total: unreviewedSubmissions.length
        };

        // Process submissions one by one
        for (let i = 0; i < unreviewedSubmissions.length; i++) {
            const submission = unreviewedSubmissions[i];

            try {
                await triggerSubmissionReview(submission.id, false);
                autoReviewProgress.value.completed++;

                // Small delay between requests
                if (i < unreviewedSubmissions.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            } catch (error) {
                console.error(`Error reviewing submission ${submission.id}:`, error);
                // Continue with next submission
            }
        }

        showAutoReviewDialog.value = false;

        toast.add({
            severity: 'success',
            summary: 'Auto-Review Complete',
            detail: `Started AI review for ${autoReviewProgress.value.completed} submissions`,
            life: 5000,
        });

        // Refresh submissions
        setTimeout(() => {
            fetchSubmissions();
        }, 3000);

    } catch (error) {
        console.error('Error in auto-review:', error);
        showAutoReviewDialog.value = false;

        toast.add({
            severity: 'error',
            summary: 'Auto-Review Failed',
            detail: 'Failed to start auto-review process',
            life: 5000,
        });
    } finally {
        autoReviewing.value = false;
    }
};

// Delete functions
const confirmDelete = (submissionId) => {
    deleteItemId.value = submissionId;
    showDeleteDialog.value = true;
};

const executeDelete = async () => {
    if (!deleteItemId.value) return;

    try {
        deleting.value = true;

        const headers = {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        const response = await fetch(`${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'}/submissions/${deleteItemId.value}`, {
            method: 'DELETE',
            headers
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to delete submission');
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Submission deleted successfully',
            life: 3000,
        });

        // Refresh submissions
        await fetchSubmissions();

    } catch (error) {
        console.error('Error deleting submission:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete submission: ' + error.message,
            life: 5000,
        });
    } finally {
        deleting.value = false;
        showDeleteDialog.value = false;
        deleteItemId.value = null;
    }
};

// Initialize
onMounted(async () => {
    if (!checkAuth()) {
        await router.push('/auth/login1');
        return;
    }

    await fetchRepository();
    await fetchSubmissions();
});
</script>
