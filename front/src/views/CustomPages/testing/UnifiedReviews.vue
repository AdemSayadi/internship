<template>
    <MainLayout>
        <PageHeader
            :title="pageTitle"
            :subtitle="pageSubtitle"
        />

        <!-- Back Navigation -->
        <div class="mt-4 mb-6">
            <Button
                label="Back"
                icon="pi pi-arrow-left"
                text
                @click="goBack"
                class="text-indigo-600 hover:text-indigo-900"
            />
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <i class="pi pi-spinner pi-spin text-2xl"></i>
            <p class="mt-2">Loading review details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
            <h3 class="mt-2 text-lg font-medium text-red-900">Error Loading Review</h3>
            <p class="mt-1 text-red-700">{{ error }}</p>
            <Button
                label="Try Again"
                icon="pi pi-refresh"
                class="mt-4"
                @click="fetchData"
            />
        </div>

        <!-- Main Content -->
        <div v-else-if="item" class="space-y-8">
            <!-- Item Details Card -->
            <Card class="border-0 shadow-lg">
                <template #header>
                    <div class="flex items-center justify-between p-6 pb-0">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i :class="itemTypeIcon" class="text-indigo-600 text-xl"></i>
                            </div>
                            <div>
                                <Tag
                                    :value="itemType === 'submission' ? 'Manual Submission' : 'GitHub PR'"
                                    :severity="itemType === 'submission' ? 'success' : 'info'"
                                />
                                <h2 class="text-xl font-semibold mt-1">{{ item.title }}</h2>
                            </div>
                        </div>

                        <!-- AI Review Button -->
                        <Button
                            v-if="canTriggerReview"
                            label="Trigger AI Review"
                            icon="pi pi-magic-wand"
                            @click="triggerAiReview"
                            :loading="triggeringReview"
                            class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white border-0"
                        />
                    </div>
                </template>

                <template #content>
                    <div class="p-6 pt-4">
                        <!-- Submission Details -->
                        <div v-if="itemType === 'submission'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                                    <Tag :value="item.language" class="capitalize" />
                                </div>
                                <div v-if="item.file_path">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">File Path</label>
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ item.file_path }}</code>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Submitted</label>
                                    <span class="text-sm text-gray-600">{{ formatDate(item.created_at) }}</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Repository</label>
                                    <span class="text-sm">{{ item.repository?.name || 'Not specified' }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <Tag
                                        :value="getSubmissionStatus(item)"
                                        :severity="getStatusSeverity(getSubmissionStatus(item))"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- PR Details -->
                        <div v-else class="space-y-6">
                            <!-- PR Meta Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Repository</label>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ item.repository?.name }}</span>
                                        <Button
                                            icon="pi pi-external-link"
                                            size="small"
                                            text
                                            @click="() => window.open(item.html_url, '_blank')"
                                            v-tooltip="'View on GitHub'"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                                    <div class="flex items-center gap-2">
                                        <img
                                            v-if="item.author_avatar_url"
                                            :src="item.author_avatar_url"
                                            :alt="item.author_username"
                                            class="w-6 h-6 rounded-full"
                                        >
                                        <span>{{ item.author_username }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <Tag
                                        :value="item.state"
                                        :severity="getStateSeverity(item.state)"
                                        class="capitalize"
                                    />
                                </div>
                            </div>

                            <!-- Branch Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Source Branch</label>
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ item.head_branch }}</code>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Branch</label>
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ item.base_branch }}</code>
                                </div>
                            </div>

                            <!-- PR Description -->
                            <div v-if="item.body">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <div class="bg-gray-50 p-4 rounded-lg text-sm whitespace-pre-line">
                                    {{ item.body }}
                                </div>
                            </div>

                            <!-- Changed Files -->
                            <div v-if="item.changed_files?.length">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Changed Files ({{ item.changed_files.length }})
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div
                                        v-for="file in item.changed_files"
                                        :key="file.filename"
                                        class="bg-gray-50 p-3 rounded-lg"
                                    >
                                        <div class="flex items-center gap-2 mb-2">
                                            <i :class="getFileIcon(file.filename)" class="text-gray-600"></i>
                                            <span class="text-sm font-mono truncate">{{ file.filename }}</span>
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-gray-600">
                                            <span v-if="file.additions" class="text-green-600">
                                                +{{ file.additions }}
                                            </span>
                                            <span v-if="file.deletions" class="text-red-600">
                                                -{{ file.deletions }}
                                            </span>
                                            <Tag
                                                :value="file.status"
                                                :severity="getFileStatusSeverity(file.status)"
                                                class="text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Code Content (for submissions) -->
                        <div v-if="itemType === 'submission' && item.code_content" class="mt-6">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700">Code Content</label>
                                <Button
                                    icon="pi pi-copy"
                                    size="small"
                                    text
                                    @click="copyCode"
                                    v-tooltip="'Copy code'"
                                />
                            </div>
                            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm"><code>{{ item.code_content }}</code></pre>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Reviews Section -->
            <Card class="border-0 shadow-lg">
                <template #header>
                    <div class="flex items-center justify-between p-6 pb-0">
                        <h3 class="text-lg font-semibold">AI Reviews</h3>
                        <Tag
                            :value="`${reviews.length} ${reviews.length === 1 ? 'Review' : 'Reviews'}`"
                            severity="info"
                        />
                    </div>
                </template>

                <template #content>
                    <div class="p-6 pt-4">
                        <!-- Empty Reviews State -->
                        <div v-if="reviews.length === 0" class="text-center py-12">
                            <i class="pi pi-comments text-6xl text-gray-300"></i>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No reviews yet</h3>
                            <p class="mt-2 text-gray-500 max-w-sm mx-auto">
                                {{ itemType === 'submission'
                                ? 'Click "Trigger AI Review" above to get your code analyzed'
                                : 'AI reviews will appear here automatically or when manually triggered'
                                }}
                            </p>
                        </div>

                        <!-- Reviews List -->
                        <div v-else class="space-y-6">
                            <div
                                v-for="(review, index) in reviews"
                                :key="review.id"
                                class="border rounded-lg p-6 hover:shadow-md transition-shadow"
                                :class="{ 'border-indigo-200 bg-indigo-50/30': index === 0 }"
                            >
                                <!-- Review Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-purple-100 rounded-lg">
                                            <i class="pi pi-sparkles text-purple-600"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <Tag value="AI Review" severity="info" />
                                                <Tag
                                                    :value="review.status"
                                                    :severity="getReviewStatusSeverity(review.status)"
                                                />
                                                <span v-if="review.score" class="text-sm font-medium">
                                                    Score: {{ review.score }}/10
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                {{ formatDate(review.reviewed_at || review.created_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Review Actions -->
                                    <div class="flex gap-2">
                                        <Button
                                            icon="pi pi-download"
                                            size="small"
                                            text
                                            @click="exportReview(review)"
                                            v-tooltip="'Export review'"
                                        />
                                        <Button
                                            icon="pi pi-share-alt"
                                            size="small"
                                            text
                                            @click="shareReview(review)"
                                            v-tooltip="'Share review'"
                                        />
                                    </div>
                                </div>

                                <!-- Review Summary -->
                                <div v-if="review.summary" class="mb-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Summary</h5>
                                    <p class="text-gray-700 bg-white p-3 rounded-lg border">{{ review.summary }}</p>
                                </div>

                                <!-- Detailed Feedback -->
                                <div v-if="review.feedback" class="mb-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Detailed Feedback</h5>
                                    <div class="bg-white p-4 rounded-lg border text-gray-700 whitespace-pre-line">
                                        {{ review.feedback }}
                                    </div>
                                </div>

                                <!-- Issues Grid -->
                                <div v-if="hasIssues(review)" class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                                    <!-- Security Issues -->
                                    <div v-if="review.security_issues?.length" class="bg-red-50 p-4 rounded-lg border border-red-100">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="pi pi-shield text-red-600"></i>
                                            <h6 class="font-medium text-red-800">Security Issues ({{ review.security_issues.length }})</h6>
                                        </div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(issue, idx) in review.security_issues"
                                                :key="`security-${idx}`"
                                                class="text-sm bg-white p-2 rounded border"
                                            >
                                                <div class="font-medium text-red-700 mb-1">{{ issue.file || issue.line }}</div>
                                                <div class="text-red-600">{{ issue.description || issue.issue }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Performance Issues -->
                                    <div v-if="review.performance_issues?.length" class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="pi pi-clock text-yellow-600"></i>
                                            <h6 class="font-medium text-yellow-800">Performance Issues ({{ review.performance_issues.length }})</h6>
                                        </div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(issue, idx) in review.performance_issues"
                                                :key="`perf-${idx}`"
                                                class="text-sm bg-white p-2 rounded border"
                                            >
                                                <div class="font-medium text-yellow-700 mb-1">{{ issue.file || issue.line }}</div>
                                                <div class="text-yellow-600">{{ issue.description || issue.issue }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Code Quality Issues -->
                                    <div v-if="review.code_quality_issues?.length" class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                        <div class="flex items-center gap-2 mb-3">
                                            <i class="pi pi-code text-blue-600"></i>
                                            <h6 class="font-medium text-blue-800">Quality Issues ({{ review.code_quality_issues.length }})</h6>
                                        </div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(issue, idx) in review.code_quality_issues"
                                                :key="`quality-${idx}`"
                                                class="text-sm bg-white p-2 rounded border"
                                            >
                                                <div class="font-medium text-blue-700 mb-1">{{ issue.file || issue.line }}</div>
                                                <div class="text-blue-600">{{ issue.description || issue.issue }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Suggestions -->
                                <div v-if="review.suggestions?.length" class="bg-green-50 p-4 rounded-lg border border-green-100">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i class="pi pi-lightbulb text-green-600"></i>
                                        <h6 class="font-medium text-green-800">Suggestions ({{ review.suggestions.length }})</h6>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(suggestion, idx) in review.suggestions"
                                            :key="`suggestion-${idx}`"
                                            class="text-sm bg-white p-2 rounded border"
                                        >
                                            <div class="font-medium text-green-700 mb-1">{{ suggestion.file || suggestion.line }}</div>
                                            <div class="text-green-600">{{ suggestion.description || suggestion.suggestion }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Review Actions Footer -->
                                <div class="flex items-center justify-between pt-4 border-t mt-4">
                                    <div class="flex items-center gap-4">
                                        <Button
                                            label="Mark as Resolved"
                                            icon="pi pi-check"
                                            size="small"
                                            text
                                            @click="markReviewResolved(review)"
                                            v-if="review.status !== 'resolved'"
                                        />
                                        <Button
                                            label="Add Comment"
                                            icon="pi pi-comment"
                                            size="small"
                                            text
                                            @click="addComment(review)"
                                        />
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Review #{{ review.id }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

// State
const loading = ref(true);
const error = ref(null);
const item = ref(null);
const reviews = ref([]);
const triggeringReview = ref(false);
const itemType = ref('submission'); // 'submission' or 'pullrequest'

const route = useRoute();
const router = useRouter();
const toast = useToast();

// API base URL
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Computed properties
const pageTitle = computed(() => {
    if (!item.value) return 'Review Details';
    return itemType.value === 'submission' ? 'Submission Review' : 'Pull Request Review';
});

const pageSubtitle = computed(() => {
    if (!item.value) return 'Loading...';
    return itemType.value === 'submission'
        ? `Manual code submission review and analysis`
        : `GitHub pull request #${item.value.github_pr_number} review and analysis`;
});

const itemTypeIcon = computed(() => {
    return itemType.value === 'submission' ? 'pi pi-upload' : 'pi pi-git-merge';
});

const canTriggerReview = computed(() => {
    if (!item.value) return false;

    // For submissions, always allow triggering reviews
    if (itemType.value === 'submission') return true;

    // For PRs, only allow if it's open
    return item.value.state === 'open';
});

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

const getSubmissionStatus = (submission) => {
    if (reviews.value.length === 0) return 'Pending Review';
    const latestReview = reviews.value[0];
    return latestReview.status === 'completed' ? 'Reviewed' : 'Processing';
};

const getStatusSeverity = (status) => {
    switch (status.toLowerCase()) {
        case 'reviewed':
        case 'completed':
            return 'success';
        case 'processing':
            return 'info';
        case 'pending review':
        case 'pending':
            return 'warning';
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
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

const getFileIcon = (filename) => {
    const ext = filename.split('.').pop()?.toLowerCase();
    switch (ext) {
        case 'js':
        case 'jsx':
        case 'ts':
        case 'tsx':
            return 'pi pi-code';
        case 'vue':
            return 'pi pi-code';
        case 'php':
            return 'pi pi-code';
        case 'css':
        case 'scss':
        case 'sass':
            return 'pi pi-palette';
        case 'html':
        case 'htm':
            return 'pi pi-code';
        case 'json':
            return 'pi pi-file';
        case 'md':
        case 'txt':
            return 'pi pi-file-text';
        default:
            return 'pi pi-file';
    }
};

const getFileStatusSeverity = (status) => {
    switch (status) {
        case 'added': return 'success';
        case 'modified': return 'info';
        case 'removed': return 'danger';
        default: return 'secondary';
    }
};

const hasIssues = (review) => {
    return (review.security_issues?.length > 0) ||
        (review.performance_issues?.length > 0) ||
        (review.code_quality_issues?.length > 0);
};

// API functions
const fetchData = async () => {
    try {
        loading.value = true;
        error.value = null;

        const id = route.params.id;
        const type = route.query.type || 'submission'; // Default to submission for backward compatibility

        itemType.value = type;

        const headers = getAuthHeaders();
        if (!headers) return;

        // Determine the endpoint based on type
        const endpoint = type === 'pullrequest'
            ? `pull-requests/${id}`
            : `submissions/${id}`;

        // Fetch item details
        const itemResponse = await fetch(`${API_BASE}/${endpoint}`, { headers });

        if (itemResponse.status === 401) {
            localStorage.removeItem('token');
            router.push('/auth/login1');
            return;
        }

        if (!itemResponse.ok) {
            const errorData = await itemResponse.json();
            throw new Error(errorData.message || 'Failed to fetch item details');
        }

        const itemData = await itemResponse.json();
        item.value = type === 'pullrequest' ? itemData.pull_request : itemData.submission;

        // Fetch reviews
        const reviewsEndpoint = type === 'pullrequest'
            ? `pull-requests/${id}/reviews`
            : `submissions/${id}/reviews`;

        const reviewsResponse = await fetch(`${API_BASE}/${reviewsEndpoint}`, { headers });

        if (reviewsResponse.ok) {
            const reviewsData = await reviewsResponse.json();
            reviews.value = reviewsData.reviews || [];
        }

    } catch (err) {
        console.error('Error fetching data:', err);
        error.value = err.message;
    } finally {
        loading.value = false;
    }
};

const triggerAiReview = async () => {
    try {
        triggeringReview.value = true;

        const headers = getAuthHeaders();
        if (!headers) return;

        const id = route.params.id;
        const endpoint = itemType.value === 'pullrequest'
            ? `pull-requests/${id}/trigger-review`
            : `submissions/${id}/review`;

        const response = await fetch(`${API_BASE}/${endpoint}`, {
            method: 'POST',
            headers
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to trigger AI review');
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'AI review triggered successfully! Processing...',
            life: 3000,
        });

        // Refresh reviews after a short delay
        setTimeout(() => {
            fetchData();
        }, 2000);

    } catch (err) {
        console.error('Error triggering review:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to trigger AI review: ' + err.message,
            life: 5000,
        });
    } finally {
        triggeringReview.value = false;
    }
};

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(item.value.code_content);
        toast.add({
            severity: 'success',
            summary: 'Copied',
            detail: 'Code copied to clipboard',
            life: 2000,
        });
    } catch (err) {
        console.error('Failed to copy code:', err);
        toast.add({
            severity: 'error',
            summary: 'Copy Failed',
            detail: 'Failed to copy code to clipboard',
            life: 3000,
        });
    }
};

const exportReview = (review) => {
    const data = {
        review_id: review.id,
        item_type: itemType.value,
        item_title: item.value.title,
        review_date: review.reviewed_at || review.created_at,
        score: review.score,
        summary: review.summary,
        feedback: review.feedback,
        security_issues: review.security_issues,
        performance_issues: review.performance_issues,
        code_quality_issues: review.code_quality_issues,
        suggestions: review.suggestions
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `review-${review.id}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const shareReview = async (review) => {
    const shareData = {
        title: `Code Review for ${item.value.title}`,
        text: `Check out this AI code review with a score of ${review.score}/10`,
        url: window.location.href
    };

    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch (err) {
            console.log('Share cancelled or failed');
            // Fallback to clipboard
            await copyToClipboard(window.location.href);
        }
    } else {
        // Fallback to clipboard
        await copyToClipboard(window.location.href);
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        toast.add({
            severity: 'success',
            summary: 'Link Copied',
            detail: 'Review link copied to clipboard',
            life: 2000,
        });
    } catch (err) {
        console.error('Failed to copy to clipboard:', err);
    }
};

const markReviewResolved = async (review) => {
    try {
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/reviews/${review.id}/resolve`, {
            method: 'POST',
            headers
        });

        if (!response.ok) {
            throw new Error('Failed to mark review as resolved');
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Review marked as resolved',
            life: 3000,
        });

        // Refresh data
        await fetchData();

    } catch (err) {
        console.error('Error marking review as resolved:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to mark review as resolved',
            life: 3000,
        });
    }
};

const addComment = (review) => {
    // This could open a dialog or navigate to a comment section
    toast.add({
        severity: 'info',
        summary: 'Coming Soon',
        detail: 'Comment functionality will be available soon',
        life: 3000,
    });
};

const goBack = () => {
    // Determine where to go back based on item type
    if (itemType.value === 'pullrequest') {
        router.push('/pull-requests');
    } else {
        router.push('/submissions');
    }
};

// Initialize
const { checkAuth } = useAuth();

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }

    await fetchData();
});

</script>
