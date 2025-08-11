<template>
    <MainLayout>
        <PageHeader
            title="Pull Requests"
            :subtitle="`Manage pull requests ${repository ? 'for ' + repository.name : ''}`"
        />

        <!-- Filter Controls -->
        <div class="mt-6 flex flex-wrap gap-4 items-center">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Repository:</label>
                <Dropdown
                    v-model="selectedRepositoryId"
                    :options="repositoryOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Repositories"
                    class="w-48"
                    @change="fetchPullRequests"
                />
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">State:</label>
                <Dropdown
                    v-model="selectedState"
                    :options="stateOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All States"
                    class="w-32"
                    @change="fetchPullRequests"
                />
            </div>

            <Button
                label="Refresh"
                icon="pi pi-refresh"
                @click="fetchPullRequests"
                :loading="loading"
                class="ml-auto"
            />
        </div>

        <!-- Statistics Cards -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="pi pi-git-merge text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total PRs</p>
                        <p class="text-2xl font-semibold">{{ statistics.total || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="pi pi-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Open</p>
                        <p class="text-2xl font-semibold">{{ statistics.open || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="pi pi-check text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Merged</p>
                        <p class="text-2xl font-semibold">{{ statistics.merged || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <i class="pi pi-eye text-orange-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Reviewed</p>
                        <p class="text-2xl font-semibold">{{ statistics.reviewed || 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="mt-8 text-center">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <p class="mt-2">Loading pull requests...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="pullRequests.length === 0" class="mt-8 text-center py-12">
            <i class="pi pi-git-merge text-6xl text-gray-300"></i>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No pull requests found</h3>
            <p class="mt-2 text-gray-500">
                {{ selectedState || selectedRepositoryId ? 'Try adjusting your filters' : 'Pull requests will appear here when GitHub webhooks are triggered' }}
            </p>
        </div>

        <!-- Pull Requests Table -->
        <DataTable
            v-else
            :value="pullRequests"
            class="mt-8"
            :paginator="true"
            :rows="15"
            :rowsPerPageOptions="[10, 15, 25]"
            sortMode="multiple"
        >
            <Column field="title" header="Title" sortable>
                <template #body="slotProps">
                    <div>
                        <a
                            :href="slotProps.data.html_url"
                            target="_blank"
                            class="font-medium text-indigo-600 hover:text-indigo-900"
                        >
                            {{ slotProps.data.title }}
                        </a>
                        <p class="text-sm text-gray-500">
                            #{{ slotProps.data.github_pr_number }}
                        </p>
                    </div>
                </template>
            </Column>

            <Column field="repository.name" header="Repository" sortable />

            <Column field="state" header="State" sortable>
                <template #body="slotProps">
                    <Tag
                        :value="slotProps.data.state"
                        :severity="getStateSeverity(slotProps.data.state)"
                        class="capitalize"
                    />
                </template>
            </Column>

            <Column field="author_username" header="Author" sortable>
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <img
                            v-if="slotProps.data.author_avatar_url"
                            :src="slotProps.data.author_avatar_url"
                            :alt="slotProps.data.author_username"
                            class="w-6 h-6 rounded-full"
                        >
                        <span>{{ slotProps.data.author_username }}</span>
                    </div>
                </template>
            </Column>

            <Column field="head_branch" header="Branch" sortable>
                <template #body="slotProps">
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">
                        {{ slotProps.data.head_branch }}
                    </code>
                </template>
            </Column>

            <Column header="Reviews">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">
                            {{ slotProps.data.reviews?.length || 0 }} reviews
                        </span>
                        <Tag
                            v-if="hasAiReview(slotProps.data.reviews)"
                            value="AI"
                            severity="info"
                            class="text-xs"
                        />
                    </div>
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
                            label="View Reviews"
                            icon="pi pi-comments"
                            size="small"
                            text
                            @click="viewReviews(slotProps.data)"
                        />
                        <Button
                            v-if="slotProps.data.state === 'open'"
                            label="Trigger Review"
                            icon="pi pi-refresh"
                            size="small"
                            text
                            @click="triggerReview(slotProps.data)"
                        />
                        <Button
                            label="GitHub"
                            icon="pi pi-external-link"
                            size="small"
                            text
                            @click="() => window.open(slotProps.data.html_url, '_blank')"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Review Details Dialog -->
        <Dialog
            v-model:visible="showReviewDialog"
            header="Pull Request Reviews"
            modal
            class="w-full max-w-4xl"
        >
            <div v-if="selectedPullRequest" class="space-y-6">
                <!-- PR Info -->
                <div class="border-b pb-4">
                    <h3 class="text-lg font-semibold">{{ selectedPullRequest.title }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        PR #{{ selectedPullRequest.github_pr_number }} by {{ selectedPullRequest.author_username }}
                    </p>
                </div>

                <!-- Reviews List -->
                <div v-if="selectedPullRequestReviews.length > 0" class="space-y-4">
                    <h4 class="font-medium">Reviews ({{ selectedPullRequestReviews.length }})</h4>

                    <div
                        v-for="review in selectedPullRequestReviews"
                        :key="review.id"
                        class="border rounded-lg p-4"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <Tag
                                    :value="review.review_type === 'ai_auto' ? 'AI Review' : 'Manual Review'"
                                    :severity="review.review_type === 'ai_auto' ? 'info' : 'success'"
                                />
                                <Tag
                                    :value="review.status"
                                    :severity="getReviewStatusSeverity(review.status)"
                                />
                                <span v-if="review.score" class="text-sm font-medium">
                                    Score: {{ review.score }}/10
                                </span>
                            </div>
                            <span class="text-sm text-gray-500">
                                {{ formatDate(review.reviewed_at || review.created_at) }}
                            </span>
                        </div>

                        <div v-if="review.summary" class="mb-3">
                            <h5 class="font-medium mb-1">Summary:</h5>
                            <p class="text-sm text-gray-700">{{ review.summary }}</p>
                        </div>

                        <div v-if="review.feedback" class="mb-3">
                            <h5 class="font-medium mb-1">Detailed Feedback:</h5>
                            <div class="text-sm text-gray-700 whitespace-pre-line bg-gray-50 p-3 rounded">
                                {{ review.feedback }}
                            </div>
                        </div>

                        <!-- Issues -->
                        <div v-if="hasIssues(review)" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                            <div v-if="review.security_issues?.length">
                                <h6 class="font-medium text-red-700 mb-2">Security Issues</h6>
                                <div class="space-y-1">
                                    <div
                                        v-for="issue in review.security_issues"
                                        :key="issue.file + issue.issue"
                                        class="text-xs bg-red-50 p-2 rounded"
                                    >
                                        <strong>{{ issue.file }}:</strong> {{ issue.description }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="review.performance_issues?.length">
                                <h6 class="font-medium text-yellow-700 mb-2">Performance Issues</h6>
                                <div class="space-y-1">
                                    <div
                                        v-for="issue in review.performance_issues"
                                        :key="issue.file + issue.issue"
                                        class="text-xs bg-yellow-50 p-2 rounded"
                                    >
                                        <strong>{{ issue.file }}:</strong> {{ issue.description }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="review.code_quality_issues?.length">
                                <h6 class="font-medium text-blue-700 mb-2">Quality Issues</h6>
                                <div class="space-y-1">
                                    <div
                                        v-for="issue in review.code_quality_issues"
                                        :key="issue.file + issue.issue"
                                        class="text-xs bg-blue-50 p-2 rounded"
                                    >
                                        <strong>{{ issue.file }}:</strong> {{ issue.description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestions -->
                        <div v-if="review.suggestions?.length" class="mt-4">
                            <h6 class="font-medium text-green-700 mb-2">Suggestions</h6>
                            <div class="space-y-1">
                                <div
                                    v-for="suggestion in review.suggestions"
                                    :key="suggestion.file + suggestion.suggestion"
                                    class="text-xs bg-green-50 p-2 rounded"
                                >
                                    <strong>{{ suggestion.file }}:</strong> {{ suggestion.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-gray-500">
                    <i class="pi pi-comments text-2xl"></i>
                    <p class="mt-2">No reviews yet</p>
                </div>
            </div>
        </Dialog>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

// State
const loading = ref(false);
const pullRequests = ref([]);
const repositories = ref([]);
const statistics = ref({});
const selectedRepositoryId = ref(null);
const selectedState = ref(null);
const showReviewDialog = ref(false);
const selectedPullRequest = ref(null);
const selectedPullRequestReviews = ref([]);

const router = useRouter();
const toast = useToast();

// API base URL
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Computed properties
const repositoryOptions = computed(() => [
    { label: 'All Repositories', value: null },
    ...repositories.value.map(repo => ({
        label: repo.name,
        value: repo.id
    }))
]);

const stateOptions = [
    { label: 'All States', value: null },
    { label: 'Open', value: 'open' },
    { label: 'Closed', value: 'closed' },
    { label: 'Merged', value: 'merged' }
];

// Utility functions
const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    if (!token) {
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
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStateSeverity = (state) => {
    switch (state) {
        case 'open': return 'success';
        case 'merged': return 'info';
        case 'closed': return 'warning';
        default: return 'secondary';
    }
};

const getReviewStatusSeverity = (status) => {
    switch (status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
};

const hasAiReview = (reviews) => {
    return reviews?.some(review => review.review_type === 'ai_auto');
};

const hasIssues = (review) => {
    return (review.security_issues?.length > 0) ||
        (review.performance_issues?.length > 0) ||
        (review.code_quality_issues?.length > 0);
};

// API functions
const fetchPullRequests = async () => {
    try {
        loading.value = true;
        const headers = getAuthHeaders();
        if (!headers) return;

        const params = new URLSearchParams();
        if (selectedRepositoryId.value) params.append('repository_id', selectedRepositoryId.value);
        if (selectedState.value) params.append('state', selectedState.value);

        const url = `${API_BASE}/pull-requests${params.toString() ? '?' + params.toString() : ''}`;
        const response = await fetch(url, { headers });

        if (response.status === 401) {
            localStorage.removeItem('token');
            router.push('/auth/login1');
            return;
        }

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to fetch pull requests');
        }

        const data = await response.json();
        pullRequests.value = data.pull_requests?.data || data.pull_requests || [];

    } catch (error) {
        console.error('Error fetching pull requests:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load pull requests: ' + error.message,
            life: 5000,
        });
    } finally {
        loading.value = false;
    }
};

const fetchRepositories = async () => {
    try {
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/repositories`, { headers });

        if (response.ok) {
            const data = await response.json();
            repositories.value = data.repositories || [];
        }
    } catch (error) {
        console.error('Error fetching repositories:', error);
    }
};

const fetchStatistics = async () => {
    try {
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/pull-requests/statistics`, { headers });

        if (response.ok) {
            const data = await response.json();
            statistics.value = data.statistics || {};
        }
    } catch (error) {
        console.error('Error fetching statistics:', error);
    }
};

const viewReviews = async (pullRequest) => {
    try {
        selectedPullRequest.value = pullRequest;

        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/pull-requests/${pullRequest.id}/reviews`, { headers });

        if (response.ok) {
            const data = await response.json();
            selectedPullRequestReviews.value = data.reviews || [];
            showReviewDialog.value = true;
        } else {
            throw new Error('Failed to fetch reviews');
        }
    } catch (error) {
        console.error('Error fetching reviews:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load reviews: ' + error.message,
            life: 5000,
        });
    }
};

const triggerReview = async (pullRequest) => {
    try {
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/pull-requests/${pullRequest.id}/trigger-review`, {
            method: 'POST',
            headers
        });

        if (response.ok) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Review triggered successfully',
                life: 3000,
            });

            // Refresh the pull request data
            await fetchPullRequests();
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to trigger review');
        }
    } catch (error) {
        console.error('Error triggering review:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to trigger review: ' + error.message,
            life: 5000,
        });
    }
};

// Initialize
const { checkAuth } = useAuth();

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }

    // Check if there's a repository_id in the query parameters
    const { repository_id } = router.currentRoute.value.query;
    if (repository_id) {
        selectedRepositoryId.value = parseInt(repository_id);
    }

    await Promise.all([
        fetchRepositories(),
        fetchStatistics(),
        fetchPullRequests()
    ]);
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
