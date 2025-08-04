import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';

export const useNotifications = () => {
    const toast = useToast();

    // State
    const notifications = ref([]);
    const loading = ref(false);
    const unreadCount = ref(0);

    // API Base URL - adjust this to match your Laravel API
    const API_BASE_URL = 'http://localhost:8000/api';

    // Helper function to make authenticated API requests (reusing pattern from useSubmissions)
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
    const unreadNotifications = computed(() => {
        return notifications.value.filter(notification => !notification.read);
    });

    const readNotifications = computed(() => {
        return notifications.value.filter(notification => notification.read);
    });

    // Fetch all notifications
    const fetchNotifications = async (filters = {}) => {
        try {
            loading.value = true;

            let endpoint = '/notifications';
            const queryParams = new URLSearchParams();

            if (filters.read !== undefined) {
                queryParams.append('read', filters.read);
            }

            if (queryParams.toString()) {
                endpoint += `?${queryParams.toString()}`;
            }

            const data = await apiRequest(endpoint);

            if (data.success) {
                notifications.value = data.notifications.data || data.notifications;
                updateUnreadCount();
            } else {
                throw new Error(data.message || 'Failed to fetch notifications');
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
            handleApiError(error, 'Failed to fetch notifications');
        } finally {
            loading.value = false;
        }
    };

    // Create a new notification
    const createNotification = async (reviewId, message) => {
        try {
            const data = await apiRequest('/notifications', {
                method: 'POST',
                body: JSON.stringify({
                    review_id: reviewId,
                    message: message
                })
            });

            if (data.success) {
                // Add to the beginning of the notifications array
                notifications.value.unshift(data.notification);
                updateUnreadCount();

                // Show toast notification
                toast.add({
                    severity: 'info',
                    summary: 'New Notification',
                    detail: message,
                    life: 4000
                });

                return data.notification;
            } else {
                throw new Error(data.message || 'Failed to create notification');
            }
        } catch (error) {
            console.error('Error creating notification:', error);
            // Don't show error toast for notifications as they're not critical
            return null;
        }
    };

    // Mark notification as read
    const markAsRead = async (notificationId) => {
        try {
            const data = await apiRequest(`/notifications/${notificationId}`, {
                method: 'PUT',
                body: JSON.stringify({
                    read: true
                })
            });

            if (data.success) {
                // Update local state
                const notification = notifications.value.find(n => n.id === notificationId);
                if (notification) {
                    notification.read = true;
                    updateUnreadCount();
                }

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Notification marked as read',
                    life: 3000
                });

                return true;
            } else {
                throw new Error(data.message || 'Failed to mark notification as read');
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
            handleApiError(error, 'Failed to mark notification as read');
            return false;
        }
    };

    // Mark all notifications as read
    const markAllAsRead = async () => {
        try {
            const data = await apiRequest('/notifications/mark-all-read', {
                method: 'POST'
            });

            if (data.success) {
                // Update local state
                notifications.value.forEach(notification => {
                    notification.read = true;
                });
                updateUnreadCount();

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: data.message,
                    life: 3000
                });

                return true;
            } else {
                throw new Error(data.message || 'Failed to mark all notifications as read');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
            handleApiError(error, 'Failed to mark all notifications as read');
            return false;
        }
    };

    // Delete notification
    const deleteNotification = async (notificationId) => {
        try {
            const data = await apiRequest(`/notifications/${notificationId}`, {
                method: 'DELETE'
            });

            if (data.success) {
                // Remove from local state
                notifications.value = notifications.value.filter(n => n.id !== notificationId);
                updateUnreadCount();

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Notification deleted successfully',
                    life: 3000
                });

                return true;
            } else {
                throw new Error(data.message || 'Failed to delete notification');
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
            handleApiError(error, 'Failed to delete notification');
            return false;
        }
    };

    // Clear all read notifications
    const clearRead = async () => {
        try {
            const data = await apiRequest('/notifications/clear-read', {
                method: 'DELETE'
            });

            if (data.success) {
                // Remove read notifications from local state
                notifications.value = notifications.value.filter(n => !n.read);
                updateUnreadCount();

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: data.message,
                    life: 3000
                });

                return true;
            } else {
                throw new Error(data.message || 'Failed to clear read notifications');
            }
        } catch (error) {
            console.error('Error clearing read notifications:', error);
            handleApiError(error, 'Failed to clear read notifications');
            return false;
        }
    };

    // Get unread count from server
    const fetchUnreadCount = async () => {
        try {
            const data = await apiRequest('/notifications/unread-count');

            if (data.success) {
                unreadCount.value = data.unread_count;
                return data.unread_count;
            } else {
                throw new Error(data.message || 'Failed to fetch unread count');
            }
        } catch (error) {
            console.error('Error fetching unread count:', error);
            return 0;
        }
    };

    // Update local unread count
    const updateUnreadCount = () => {
        unreadCount.value = notifications.value.filter(n => !n.read).length;
    };

    // Helper function to handle API errors
    const handleApiError = (error, defaultMessage) => {
        if (error.message.includes('Authentication failed')) {
            toast.add({
                severity: 'error',
                summary: 'Authentication Error',
                detail: 'Please log in again',
                life: 3000
            });
            // You might want to redirect to login here
            return;
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || defaultMessage,
            life: 3000
        });
    };

    // Utility function to format notification date
    const formatNotificationDate = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;

        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours}h ago`;

        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays}d ago`;

        return date.toLocaleDateString();
    };

    // Create notification for review submission
    const notifyReviewSubmitted = async (reviewId, submissionTitle) => {
        const message = `Code review initiated for "${submissionTitle}"`;
        return await createNotification(reviewId, message);
    };

    // Create notification for review completion
    const notifyReviewCompleted = async (reviewId, submissionTitle, overallScore) => {
        const message = `Code review completed for "${submissionTitle}" (Score: ${overallScore})`;
        return await createNotification(reviewId, message);
    };

    return {
        // State
        notifications,
        loading,
        unreadCount,

        // Computed
        unreadNotifications,
        readNotifications,

        // Methods
        fetchNotifications,
        createNotification,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        clearRead,
        fetchUnreadCount,
        updateUnreadCount,
        formatNotificationDate,

        // Helper methods for reviews
        notifyReviewSubmitted,
        notifyReviewCompleted
    };
};
