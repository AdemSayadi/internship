// utils/composables/useNotifications.js
import { ref, onMounted, onUnmounted, computed } from 'vue'
import notificationService from '@/utils/services/NotificationService'

export function useNotifications(options = {}) {
    const {
        autoStart = true,
        pollingInterval = 30000,
        onNewNotification = null,
        onError = null
    } = options

    const notifications = ref([])
    const unreadCount = ref(0)
    const loading = ref(false)
    const error = ref(null)
    const unsubscribe = ref(null)

    // Computed properties
    const readCount = computed(() => notifications.value.filter(n => n.read).length)
    const totalCount = computed(() => notifications.value.length)
    const hasReadNotifications = computed(() => readCount.value > 0)
    const hasUnreadNotifications = computed(() => unreadCount.value > 0)

    // Service event handler
    const handleServiceUpdate = (type, data) => {
        switch (type) {
            case 'new_notifications':
                unreadCount.value = data.count
                if (onNewNotification) {
                    onNewNotification(data.newCount)
                }
                break
            case 'notification_read':
                updateNotificationRead(data.id)
                break
            case 'all_notifications_read':
                markAllNotificationsRead()
                break
            case 'notification_deleted':
                removeNotification(data.id)
                break
            case 'read_notifications_cleared':
                removeReadNotifications()
                break
        }
    }

    // Local state management
    const updateNotificationRead = (id) => {
        const notification = notifications.value.find(n => n.id === id)
        if (notification && !notification.read) {
            notification.read = true
            unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
    }

    const markAllNotificationsRead = () => {
        notifications.value.forEach(notification => {
            notification.read = true
        })
        unreadCount.value = 0
    }

    const removeNotification = (id) => {
        const notification = notifications.value.find(n => n.id === id)
        if (notification && !notification.read) {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value = notifications.value.filter(n => n.id !== id)
    }

    const removeReadNotifications = () => {
        notifications.value = notifications.value.filter(n => !n.read)
    }

    // API wrapper methods
    const loadNotifications = async (filter = null) => {
        try {
            loading.value = true
            error.value = null
            notifications.value = await notificationService.getNotifications(filter)
        } catch (err) {
            error.value = err.message
            if (onError) {
                onError(err)
            }
            console.error('Error loading notifications:', err)
        } finally {
            loading.value = false
        }
    }

    const loadUnreadCount = async () => {
        try {
            unreadCount.value = await notificationService.getUnreadCount()
            notificationService.lastUnreadCount = unreadCount.value
        } catch (err) {
            console.error('Error loading unread count:', err)
        }
    }

    const markAsRead = async (id) => {
        try {
            await notificationService.markAsRead(id)
            return true
        } catch (err) {
            if (onError) {
                onError(err)
            }
            return false
        }
    }

    const markAllAsRead = async () => {
        try {
            await notificationService.markAllAsRead()
            return true
        } catch (err) {
            if (onError) {
                onError(err)
            }
            return false
        }
    }

    const deleteNotification = async (id) => {
        try {
            await notificationService.deleteNotification(id)
            return true
        } catch (err) {
            if (onError) {
                onError(err)
            }
            return false
        }
    }

    const clearReadNotifications = async () => {
        try {
            await notificationService.clearReadNotifications()
            return true
        } catch (err) {
            if (onError) {
                onError(err)
            }
            return false
        }
    }

    const refreshNotifications = async () => {
        await loadNotifications()
        await loadUnreadCount()
    }

    // Utility methods
    const formatDate = (date) => notificationService.formatDate(date)
    const formatType = (type) => notificationService.formatType(type)
    const getTypeStyle = (type) => notificationService.getTypeStyle(type)

    // Lifecycle management
    onMounted(() => {
        if (autoStart) {
            // Subscribe to service updates
            unsubscribe.value = notificationService.subscribe(handleServiceUpdate)

            // Start polling if not already started
            notificationService.startPolling(pollingInterval)

            // Load initial data
            refreshNotifications()
        }
    })

    onUnmounted(() => {
        if (unsubscribe.value) {
            unsubscribe.value()
        }
    })

    return {
        // State
        notifications,
        unreadCount,
        readCount,
        totalCount,
        loading,
        error,
        hasReadNotifications,
        hasUnreadNotifications,

        // Methods
        loadNotifications,
        loadUnreadCount,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        clearReadNotifications,
        refreshNotifications,

        // Utilities
        formatDate,
        formatType,
        getTypeStyle,

        // Service control
        startPolling: () => notificationService.startPolling(pollingInterval),
        stopPolling: () => notificationService.stopPolling(),
    }
}
