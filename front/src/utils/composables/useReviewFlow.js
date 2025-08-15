// composables/useReviewFlow.js
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

export function useReviewFlow() {
    const router = useRouter();
    const toast = useToast();

    // State
    const loading = ref(false);
    const processing = ref(false);

    // API base URL
    const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

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

    // Create submission and optionally trigger AI review
    const createSubmissionWithReview = async (submissionData, autoReview = true) => {
        try {
            loading.value = true;

            const headers = getAuthHeaders();
            if (!headers) return null;

            // Create submission
            const response = await fetch(`${API_BASE}/submissions`, {
                method: 'POST',
                headers,
                body: JSON.stringify(submissionData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create submission');
            }

            const data = await response.json();
            const submissionId = data.submission.id;

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Code submission created successfully',
                life: 3000,
            });

            // Optionally trigger AI review immediately
            if (autoReview) {
                await triggerSubmissionReview(submissionId, false); // Don't show toast again
            }

            return { submissionId, submission: data.submission };

        } catch (error) {
            console.error('Error creating submission:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to create submission: ' + error.message,
                life: 5000,
            });
            return null;
        } finally {
            loading.value = false;
        }
    };

    // Trigger AI review for submission
    const triggerSubmissionReview = async (submissionId, showToast = true) => {
        try {
            processing.value = true;

            const headers = getAuthHeaders();
            if (!headers) return false;

            const response = await fetch(`${API_BASE}/submissions/${submissionId}/review`, {
                method: 'POST',
                headers
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to trigger AI review');
            }

            if (showToast) {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'AI review started! Processing your code...',
                    life: 3000,
                });
            }

            return true;

        } catch (error) {
            console.error('Error triggering review:', error);
            if (showToast) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to trigger AI review: ' + error.message,
                    life: 5000,
                });
            }
            return false;
        } finally {
            processing.value = false;
        }
    };

    // Trigger AI review for pull request
    const triggerPullRequestReview = async (pullRequestId, showToast = true) => {
        try {
            processing.value = true;

            const headers = getAuthHeaders();
            if (!headers) return false;

            const response = await fetch(`${API_BASE}/pull-requests/${pullRequestId}/trigger-review`, {
                method: 'POST',
                headers
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to trigger AI review');
            }

            if (showToast) {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'AI review started for pull request!',
                    life: 3000,
                });
            }

            return true;

        } catch (error) {
            console.error('Error triggering PR review:', error);
            if (showToast) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to trigger AI review: ' + error.message,
                    life: 5000,
                });
            }
            return false;
        } finally {
            processing.value = false;
        }
    };

    // Check review status
    const checkReviewStatus = async (type, id) => {
        try {
            const headers = getAuthHeaders();
            if (!headers) return null;

            const endpoint = type === 'submission'
                ? `submissions/${id}/reviews`
                : `pull-requests/${id}/reviews`;

            const response = await fetch(`${API_BASE}/${endpoint}`, { headers });

            if (!response.ok) {
                throw new Error('Failed to fetch review status');
            }

            const data = await response.json();
            return data.reviews || [];

        } catch (error) {
            console.error('Error checking review status:', error);
            return null;
        }
    };

    // Poll for review completion
    const pollReviewStatus = async (type, id, callback, maxAttempts = 30) => {
        let attempts = 0;

        const poll = async () => {
            attempts++;

            const reviews = await checkReviewStatus(type, id);
            if (!reviews) return;

            const latestReview = reviews[0];

            if (latestReview && (latestReview.status === 'completed' || latestReview.status === 'failed')) {
                callback(latestReview);
                return;
            }

            if (attempts >= maxAttempts) {
                callback(null); // Timeout
                return;
            }

            // Poll every 3 seconds
            setTimeout(poll, 3000);
        };

        poll();
    };

    // Navigate to review page
    const navigateToReview = (type, id) => {
        router.push({
            name: 'reviews',
            params: { id },
            query: { type }
        });
    };

    // Submit code with complete flow
    const submitCodeWithFlow = async (submissionData, options = {}) => {
        const {
            autoReview = true,
            navigateToReview: shouldNavigate = true,
            waitForCompletion = false
        } = options;

        try {
            // Step 1: Create submission
            const result = await createSubmissionWithReview(submissionData, autoReview);
            if (!result) return null;

            const { submissionId } = result;

            // Step 2: Wait for review completion if requested
            if (waitForCompletion && autoReview) {
                toast.add({
                    severity: 'info',
                    summary: 'Processing',
                    detail: 'Waiting for AI review to complete...',
                    life: 3000,
                });

                return new Promise((resolve) => {
                    pollReviewStatus('submission', submissionId, (review) => {
                        if (review) {
                            if (review.status === 'completed') {
                                toast.add({
                                    severity: 'success',
                                    summary: 'Review Complete',
                                    detail: `AI review completed with score: ${review.score}/10`,
                                    life: 5000,
                                });
                            } else if (review.status === 'failed') {
                                toast.add({
                                    severity: 'error',
                                    summary: 'Review Failed',
                                    detail: 'AI review failed to complete',
                                    life: 5000,
                                });
                            }
                        } else {
                            toast.add({
                                severity: 'warning',
                                summary: 'Review Timeout',
                                detail: 'Review is taking longer than expected. Check back later.',
                                life: 5000,
                            });
                        }

                        // Step 3: Navigate to review page
                        if (shouldNavigate) {
                            navigateToReview('submission', submissionId);
                        }

                        resolve({ submissionId, review });
                    });
                });
            } else {
                // Step 3: Navigate immediately if not waiting
                if (shouldNavigate) {
                    navigateToReview('submission', submissionId);
                }

                return { submissionId };
            }

        } catch (error) {
            console.error('Error in submit code flow:', error);
            return null;
        }
    };

    // Auto-review existing submissions/PRs
    const autoReviewExisting = async (items, type = 'submission') => {
        const results = {
            successful: 0,
            failed: 0,
            skipped: 0
        };

        for (const item of items) {
            try {
                // Skip if already has reviews
                const existingReviews = await checkReviewStatus(type, item.id);
                if (existingReviews && existingReviews.length > 0) {
                    results.skipped++;
                    continue;
                }

                // Trigger review
                const success = type === 'submission'
                    ? await triggerSubmissionReview(item.id, false)
                    : await triggerPullRequestReview(item.id, false);

                if (success) {
                    results.successful++;
                } else {
                    results.failed++;
                }

                // Add small delay between requests
                await new Promise(resolve => setTimeout(resolve, 500));

            } catch (error) {
                console.error(`Error auto-reviewing ${type} ${item.id}:`, error);
                results.failed++;
            }
        }

        // Show summary toast
        toast.add({
            severity: results.failed > 0 ? 'warning' : 'success',
            summary: 'Auto-Review Complete',
            detail: `${results.successful} started, ${results.failed} failed, ${results.skipped} skipped`,
            life: 5000,
        });

        return results;
    };

    return {
        // State
        loading,
        processing,

        // Core functions
        createSubmissionWithReview,
        triggerSubmissionReview,
        triggerPullRequestReview,
        checkReviewStatus,
        pollReviewStatus,

        // Navigation
        navigateToReview,

        // Complete flows
        submitCodeWithFlow,
        autoReviewExisting
    };
}
