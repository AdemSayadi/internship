<template>
    <MainLayout>
        <PageHeader
            title="Code Reviews"
            :subtitle="`AI-powered reviews for ${submission?.title || 'Submission'}.`"
        />

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-gray-600">Loading submission and reviews...</span>
        </div>

        <!-- Submission Details -->
        <div v-else-if="submission" class="mt-10">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">{{ submission.title }}</h2>
                        <p class="mt-2 text-gray-600">Language: {{ submission.language }}</p>
                        <p class="mt-1 text-gray-600">
                            Submitted: {{ formatDate(submission.created_at) }}
                        </p>
                        <p v-if="submission.file_path" class="mt-1 text-gray-600">
                            File: {{ submission.file_path }}
                        </p>
                        <p v-if="submission.repository" class="mt-1 text-gray-600">
                            Repository: {{ submission.repository.name }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <!-- AI Review Button with custom styling -->
                        <button
                            @click="createReview"
                            :disabled="isCreatingReview || hasPendingReview"
                            class="group relative inline-flex items-center justify-center min-w-[160px] gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-md shadow-lg hover:shadow-xl transition-all duration-300 ease-out transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                            :class="{ 'animate-bounce': buttonHovered }"
                            @mouseenter="onButtonHover"
                            @mouseleave="onButtonLeave"
                        >
                            <i :class="`pi ${isCreatingReview ? 'pi-spin pi-spinner' : 'pi-bolt'} text-white relative z-10`"></i>
                            <span class="relative z-10">
                                <span v-if="isCreatingReview">Creating Review...</span>
                                <span v-else-if="hasPendingReview">Review in Progress</span>
                                <span v-else>New AI Review</span>
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-md"></div>
                        </button>

                        <button
                            @click="refreshReviews"
                            :disabled="loading"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                        >
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Code Content -->
                <div class="mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Code Content</h3>
                    <pre class="bg-gray-100 p-4 rounded-md overflow-x-auto text-sm border">{{ submission.code_content }}</pre>
                </div>
            </div>

            <!-- Reviews Table -->
            <div class="mt-8 bg-white shadow-lg rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Reviews ({{ reviews.length }})
                    </h3>
                </div>

                <DataTable
                    :value="reviews"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                    :loading="reviewsLoading"
                    class="p-datatable-sm"
                    emptyMessage="No reviews found for this submission."
                >
                    <Column field="status" header="Status" sortable>
                        <template #body="slotProps">
                            <span :class="getStatusClass(slotProps.data.status)">
                                {{ capitalizeFirst(slotProps.data.status) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="overall_score" header="Overall Score" sortable>
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <span class="font-medium">{{ slotProps.data.overall_score || 0 }}</span>
                                <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-blue-500 h-2 rounded-full transition-all"
                                        :style="{ width: `${slotProps.data.overall_score || 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="complexity_score" header="Complexity" sortable>
                        <template #body="slotProps">
                            <span class="font-medium">{{ slotProps.data.complexity_score || 0 }}</span>
                        </template>
                    </Column>

                    <Column field="security_score" header="Security" sortable>
                        <template #body="slotProps">
                            <span class="font-medium">{{ slotProps.data.security_score || 0 }}</span>
                        </template>
                    </Column>

                    <Column field="maintainability_score" header="Maintainability" sortable>
                        <template #body="slotProps">
                            <span class="font-medium">{{ slotProps.data.maintainability_score || 0 }}</span>
                        </template>
                    </Column>

                    <Column field="bug_count" header="Bugs" sortable>
                        <template #body="slotProps">
                            <span
                                :class="slotProps.data.bug_count > 0 ? 'text-red-600 font-medium' : 'text-green-600'"
                            >
                                {{ slotProps.data.bug_count || 0 }}
                            </span>
                        </template>
                    </Column>

                    <Column field="ai_summary" header="AI Summary" style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="max-w-xs">
                                <p class="text-sm text-gray-700 truncate" :title="slotProps.data.ai_summary">
                                    {{ slotProps.data.ai_summary || 'N/A' }}
                                </p>
                            </div>
                        </template>
                    </Column>

                    <Column field="suggestions" header="Suggestions" style="min-width: 250px">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.suggestions && slotProps.data.suggestions.length" class="max-w-sm">
                                <ul class="text-sm space-y-1">
                                    <li
                                        v-for="(suggestion, index) in slotProps.data.suggestions.slice(0, 2)"
                                        :key="index"
                                        class="flex items-start"
                                    >
                                        <span class="inline-block w-1.5 h-1.5 bg-blue-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span class="text-gray-700">{{ suggestion }}</span>
                                    </li>
                                    <li v-if="slotProps.data.suggestions.length > 2" class="text-xs text-gray-500 italic">
                                        +{{ slotProps.data.suggestions.length - 2 }} more suggestions
                                    </li>
                                </ul>
                            </div>
                            <span v-else class="text-gray-500 text-sm">N/A</span>
                        </template>
                    </Column>

                    <Column field="created_at" header="Created" sortable>
                        <template #body="slotProps">
                            <span class="text-sm text-gray-600">
                                {{ formatDate(slotProps.data.created_at) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 100px">
                        <template #body="slotProps">
                            <div class="flex space-x-2">
                                <button
                                    @click="viewReviewDetails(slotProps.data)"
                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                    title="View Details"
                                >
                                    View
                                </button>
                                <button
                                    @click="deleteReview(slotProps.data.id)"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                    title="Delete Review"
                                >
                                    Delete
                                </button>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="mt-10 text-center py-12">
            <div class="text-red-600 text-lg">{{ error }}</div>
            <button
                @click="fetchSubmission"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Try Again
            </button>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

// Reactive data
const submission = ref(null);
const reviews = ref([]);
const loading = ref(true);
const reviewsLoading = ref(false);
const isCreatingReview = ref(false);
const error = ref(null);

const router = useRouter();
const route = useRoute();
const toast = useToast();
const { checkAuth } = useAuth();

// API Base URL - adjust this to match your Laravel API
const API_BASE_URL = 'http://localhost:8000/api';

// Helper function to make authenticated API requests (reusing your pattern)
const apiRequest = async (endpoint, options = {}) => {
    // Try different possible token storage keys
    const token = localStorage.getItem('auth_token') ||
        localStorage.getItem('token') ||
        localStorage.getItem('access_token') ||
        sessionStorage.getItem('auth_token') ||
        sessionStorage.getItem('token');

    if (!token) {
        throw new Error('No authentication token found. Please log in again.');
    }

    const defaultOptions = {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    };

    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers,
        },
    };

    const response = await fetch(`${API_BASE_URL}${endpoint}`, mergedOptions);

    if (!response.ok) {
        if (response.status === 401) {
            // Clear potentially invalid token
            localStorage.removeItem('auth_token');
            localStorage.removeItem('token');
            sessionStorage.removeItem('auth_token');
            sessionStorage.removeItem('token');
            throw new Error('Authentication failed. Please log in again.');
        }

        const errorData = await response.json().catch(() => ({ message: 'An error occurred' }));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    return response.json();
};

// Computed properties
const hasPendingReview = computed(() => {
    return reviews.value.some(review => review.status === 'pending');
});

// Animation state for button
const buttonHovered = ref(false);

const onButtonHover = () => {
    buttonHovered.value = true;
};

const onButtonLeave = () => {
    buttonHovered.value = false;
};

// Fetch submission details
const fetchSubmission = async () => {
    try {
        loading.value = true;
        error.value = null;

        const submissionId = route.params.submissionId;
        const data = await apiRequest(`/code-submissions/${submissionId}`);

        if (data.success) {
            submission.value = data.submission;
            // If the submission has reviews, use them; otherwise fetch separately
            if (submission.value.reviews) {
                reviews.value = submission.value.reviews;
            } else {
                await fetchReviews();
            }
        } else {
            throw new Error(data.message || 'Failed to fetch submission');
        }
    } catch (err) {
        console.error('Error fetching submission:', err);

        if (err.message.includes('Authentication failed')) {
            toast.add({
                severity: 'error',
                summary: 'Authentication Error',
                detail: 'Please log in again',
                life: 3000
            });
            router.push('/auth/login1');
        } else {
            error.value = err.message || 'Failed to load submission';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.value,
                life: 5000
            });
        }
    } finally {
        loading.value = false;
    }
};

// Mock reviews data - keeping for now
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
        suggestions: ['Use array methods instead of for loops', 'Add input validation', 'Consider breaking down complex functions'],
        created_at: '2025-07-02',
    },
    {
        id: 2,
        code_submission_id: 1,
        status: 'pending',
        overall_score: 0,
        complexity_score: 0,
        security_score: 0,
        maintainability_score: 0,
        bug_count: 0,
        ai_summary: null,
        suggestions: [],
        created_at: '2025-08-03',
    }
];

// Fetch reviews for the submission - using mock data
const fetchReviews = async () => {
    try {
        reviewsLoading.value = true;

        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));

        // Filter mock reviews for this specific submission
        const submissionId = parseInt(route.params.submissionId);
        reviews.value = mockReviews.filter(review => review.code_submission_id === submissionId);

    } catch (err) {
        console.error('Error fetching reviews:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: err.message || 'Failed to load reviews',
            life: 3000
        });
    } finally {
        reviewsLoading.value = false;
    }
};

// Create new review - mock implementation
const createReview = async () => {
    if (!submission.value || hasPendingReview.value) return;

    try {
        isCreatingReview.value = true;

        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Create mock review
        const newReview = {
            id: Date.now(), // Simple ID generation
            code_submission_id: submission.value.id,
            status: 'pending',
            overall_score: 0,
            complexity_score: 0,
            security_score: 0,
            maintainability_score: 0,
            bug_count: 0,
            ai_summary: null,
            suggestions: [],
            created_at: new Date().toISOString(),
        };

        // Add to reviews array
        reviews.value.unshift(newReview);

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'AI review initiated successfully',
            life: 3000
        });

        // Simulate completion after 3 seconds
        setTimeout(() => {
            const reviewIndex = reviews.value.findIndex(r => r.id === newReview.id);
            if (reviewIndex !== -1) {
                reviews.value[reviewIndex] = {
                    ...newReview,
                    status: 'completed',
                    overall_score: Math.floor(Math.random() * 30) + 70, // 70-100
                    complexity_score: Math.floor(Math.random() * 30) + 70,
                    security_score: Math.floor(Math.random() * 30) + 70,
                    maintainability_score: Math.floor(Math.random() * 30) + 70,
                    bug_count: Math.floor(Math.random() * 5),
                    ai_summary: 'AI analysis completed. Code shows good structure with some areas for improvement.',
                    suggestions: [
                        'Consider adding more comments for better documentation',
                        'Review variable naming conventions',
                        'Add error handling for edge cases'
                    ]
                };

                toast.add({
                    severity: 'info',
                    summary: 'Review Complete',
                    detail: 'AI review has been completed',
                    life: 3000
                });
            }
        }, 3000);

    } catch (err) {
        console.error('Error creating review:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: err.message || 'Failed to create review',
            life: 5000
        });
    } finally {
        isCreatingReview.value = false;
    }
};

// Refresh reviews
const refreshReviews = async () => {
    await fetchReviews();
    toast.add({
        severity: 'info',
        summary: 'Refreshed',
        detail: 'Reviews updated',
        life: 2000
    });
};

// Delete review
const deleteReview = async (reviewId) => {
    if (!confirm('Are you sure you want to delete this review?')) return;

    try {
        const data = await apiRequest(`/reviews/${reviewId}`, {
            method: 'DELETE'
        });

        if (data.success) {
            reviews.value = reviews.value.filter(review => review.id !== reviewId);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Review deleted successfully',
                life: 3000
            });
        } else {
            throw new Error(data.message || 'Failed to delete review');
        }
    } catch (err) {
        console.error('Error deleting review:', err);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: err.message || 'Failed to delete review',
            life: 5000
        });
    }
};

// View review details (placeholder for future implementation)
const viewReviewDetails = (review) => {
    toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'Review details feature coming soon',
        life: 3000
    });
};

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

const capitalizeFirst = (str) => {
    return str.charAt(0).toUpperCase() + str.slice(1);
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full',
        'completed': 'px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full',
        'failed': 'px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full'
    };
    return classes[status] || 'px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full';
};

// Lifecycle
onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }

    await fetchSubmission();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';

/* Custom styles for better visual hierarchy */
.p-datatable .p-datatable-tbody > tr > td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.p-datatable .p-datatable-thead > tr > th {
    padding: 1rem 0.75rem;
    background-color: #f9fafb;
    font-weight: 600;
    color: #374151;
}

/* Loading animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.animate-bounce {
    animation: bounce 1s ease-in-out infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .p-datatable .p-datatable-tbody > tr > td,
    .p-datatable .p-datatable-thead > tr > th {
        padding: 0.5rem;
    }
}
</style>
