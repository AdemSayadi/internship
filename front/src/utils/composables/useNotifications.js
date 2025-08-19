// utils/composables/useNotifications.js
// Optional: Create a composable for better state management

import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'

export function useNotifications() {
    const notifications = ref([])
    const unreadCount = ref(0)
    const loading = ref(false)
    const pollingInterval = ref(null)
    const toast = useToast()

    const loadNotifications = async (filter = null) => {
        try {
            loading.value = true
            let url = '/api/notifications'

            if (filter === 'unread') {
                url += '?read=false'
            } else if (filter === 'read') {
                url += '?read=true'
            }

            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            })

            if (response.ok) {
                const data = await response.json()
                notifications.value = data.data || data
            }
        } catch (error) {
            console.error('Error loading notifications:', error)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load notifications',
                life: 3000
            })
        } finally {
            loading.value = false
        }
    }

    const loadUnreadCount = async () => {
        try {
            const response = await fetch('/api/notifications/unread-count', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            })

            if (response.ok) {
                const data = await response.json()
                unreadCount.value = data.unread_count || 0
            }
        } catch (error) {
            console.error('Error loading unread count:', error)
        }
    }

    const markAsRead = async (id) => {
        try {
            const response = await fetch(`/api/notifications/${id}`, {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ read: true })
            })

            if (response.ok) {
                const notification = notifications.value.find(n => n.id === id)
                if (notification && !notification.read) {
                    notification.read = true
                    unreadCount.value = Math.max(0, unreadCount.value - 1)
                }
                return true
            }
        } catch (error) {
            console.error('Error marking as read:', error)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to mark notification as read',
                life: 3000
            })
        }
        return false
    }

    const markAllAsRead = async () => {
        try {
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'PATCH',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            })

            if (response.ok) {
                notifications.value.forEach(notification => {
                    notification.read = true
                })
                unreadCount.value = 0

                const data = await response.json()
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: data.message,
                    life: 3000
                })
                return true
            }
        } catch (error) {
            console.error('Error marking all as read:', error)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to mark all notifications as read',
                life: 3000
            })
        }
        return false
    }

    const deleteNotification = async (id) => {
        try {
            const response = await fetch(`/api/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            })

            if (response.ok) {
                const notification = notifications.value.find(n => n.id === id)
                if (notification && !notification.read) {
                    unreadCount.value = Math.max(0, unreadCount.value - 1)
                }
                notifications.value = notifications.value.filter(n => n.id !== id)

                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Notification deleted',
                    life: 3000
                })
                return true
            }
        } catch (error) {
            console.error('Error deleting notification:', error)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to delete notification',
                life: 3000
            })
        }
        return false
    }

    const clearReadNotifications = async () => {
        try {
            const response = await fetch('/api/notifications/clear-read', {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                }
            })

            if (response.ok) {
                notifications.value = notifications.value.filter(n => !n.read)

                const data = await response.json()
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: data.message,
                    life: 3000
                })
                return true
            }
        } catch (error) {
            console.error('Error clearing read notifications:', error)
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to clear read notifications',
                life: 3000
            })
        }
        return false
    }

    const startPolling = () => {
        pollingInterval.value = setInterval(() => {
            loadUnreadCount()
        }, 30000) // Poll every 30 seconds
    }

    const stopPolling = () => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value)
            pollingInterval.value = null
        }
    }

    const formatDate = (date) => {
        const now = new Date()
        const notificationDate = new Date(date)
        const diffInHours = (now - notificationDate) / (1000 * 60 * 60)

        if (diffInHours < 1) {
            const diffInMinutes = Math.floor((now - notificationDate) / (1000 * 60))
            return diffInMinutes <= 1 ? 'Just now' : `${diffInMinutes}m ago`
        } else if (diffInHours < 24) {
            return `${Math.floor(diffInHours)}h ago`
        } else {
            const diffInDays = Math.floor(diffInHours / 24)
            return diffInDays === 1 ? '1 day ago' : `${diffInDays} days ago`
        }
    }

    const formatType = (type) => {
        const typeMap = {
            'code_submission_created': 'Code Submission',
            'review_completed': 'Review Done',
            'pull_request_created': 'Pull Request',
            'pr_review_completed': 'PR Review Done',
            'review_submitted': 'Review Started'
        }
        return typeMap[type] || type.replace('_', ' ')
    }

    const getTypeStyle = (type) => {
        const styles = {
            'code_submission_created': 'bg-green-100 text-green-800',
            'review_completed': 'bg-blue-100 text-blue-800',
            'pull_request_created': 'bg-purple-100 text-purple-800',
            'pr_review_completed': 'bg-indigo-100 text-indigo-800',
            'review_submitted': 'bg-yellow-100 text-yellow-800'
        }
        return styles[type] || 'bg-gray-100 text-gray-800'
    }

    return {
        notifications,
        unreadCount,
        loading,
        loadNotifications,
        loadUnreadCount,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        clearReadNotifications,
        startPolling,
        stopPolling,
        formatDate,
        formatType,
        getTypeStyle
    }
}
