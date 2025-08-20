<template>
    <div class="relative">
        <button
            @click="toggleDropdown"
            class="relative p-2 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'bg-gray-100': showDropdown }"
            :aria-label="`Notifications. ${unreadCount} unread`"
        >
            <BellIcon class="h-6 w-6 text-gray-600" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse"
                :aria-label="`${unreadCount} unread notifications`"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showDropdown"
                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50 border border-gray-200"
                 @click.stop>
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllAsRead"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors"
                                :disabled="markingAllRead"
                            >
                                {{ markingAllRead ? 'Marking...' : 'Mark all read' }}
                            </button>
                            <button
                                @click="refreshNotifications"
                                class="text-gray-400 hover:text-gray-600 p-1 rounded"
                                :disabled="loading"
                                title="Refresh notifications"
                            >
                                <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-if="error" class="px-4 py-3 bg-red-50 border-b border-red-200">
                    <div class="flex items-center gap-2 text-red-600 text-sm">
                        <ExclamationTriangleIcon class="h-4 w-4" />
                        <span>{{ error }}</span>
                        <button @click="refreshNotifications" class="ml-auto text-red-700 hover:text-red-900 text-xs">
                            Retry
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading && notifications.length === 0" class="px-4 py-8 text-center">
                    <div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-blue-600 rounded-full"
                         role="status" aria-label="loading">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Loading notifications...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="!loading && notifications.length === 0" class="px-4 py-8 text-center">
                    <BellIcon class="h-8 w-8 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">No notifications</p>
                    <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
                </div>

                <!-- Notifications List -->
                <div v-else class="max-h-96 overflow-y-auto">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors group"
                        @click="handleNotificationClick(notification)"
                        :class="{ 'bg-blue-50': !notification.read }"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Status Indicator -->
                            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-2 transition-colors"
                                 :class="notification.read ? 'bg-gray-300' : 'bg-blue-500'"></div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <!-- Type Badge -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1"
                                      :class="getTypeStyle(notification.type)">
                                    {{ formatType(notification.type) }}
                                </span>

                                <!-- Title -->
                                <h4 class="font-medium text-sm text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                                    {{ notification.title }}
                                </h4>

                                <!-- Message -->
                                <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ notification.message }}</p>

                                <!-- Time -->
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(notification.created_at) }}</p>
                            </div>

                            <!-- Quick actions (shown on hover) -->
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    v-if="!notification.read"
                                    @click.stop="markSingleAsRead(notification.id)"
                                    class="text-blue-600 hover:text-blue-800 p-1 rounded text-xs"
                                    title="Mark as read"
                                >
                                    <CheckIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 bg-gray-50 rounded-b-lg">
                    <router-link
                        to="/notifications"
                        class="text-blue-600 text-sm hover:text-blue-800 font-medium transition-colors inline-flex items-center gap-1"
                        @click="showDropdown = false"
                    >
                        View all notifications
                        <ArrowRightIcon class="h-4 w-4" />
                    </router-link>
                </div>
            </div>
        </transition>

        <!-- Overlay to close dropdown when clicking outside -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="showDropdown = false"></div>
    </div>
</template>

<script>
import {
    BellIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'
import notificationService from '@/utils/services/NotificationService'

export default {
    name: 'NotificationsDropdown',
    components: {
        BellIcon,
        ArrowPathIcon,
        ExclamationTriangleIcon,
        CheckIcon,
        ArrowRightIcon
    },
    data() {
        return {
            showDropdown: false,
            notifications: [],
            unreadCount: 0,
            loading: false,
            error: null,
            markingAllRead: false,
            unsubscribe: null
        }
    },
    async mounted() {
        try {
            // Subscribe to notification service updates
            this.unsubscribe = notificationService.subscribe(this.handleServiceUpdate)

            // Start polling and load initial data
            notificationService.startPolling()
            await this.loadInitialData()
        } catch (error) {
            this.error = 'Failed to initialize notifications'
            console.error('Notification initialization error:', error)
        }
    },
    beforeUnmount() {
        // Clean up
        if (this.unsubscribe) {
            this.unsubscribe()
        }
        notificationService.stopPolling()
    },
    methods: {
        async loadInitialData() {
            try {
                this.loading = true
                this.error = null

                const [notifications, count] = await Promise.all([
                    notificationService.getRecentNotifications(),
                    notificationService.getUnreadCount()
                ])

                this.notifications = notifications
                this.unreadCount = count
                notificationService.lastUnreadCount = count
            } catch (error) {
                console.error('Error loading initial notification data:', error)
                this.error = 'Failed to load notifications'
            } finally {
                this.loading = false
            }
        },

        async refreshNotifications() {
            if (this.loading) return

            try {
                this.loading = true
                this.error = null

                if (this.showDropdown) {
                    // If dropdown is open, refresh the list
                    this.notifications = await notificationService.getRecentNotifications()
                }

                this.unreadCount = await notificationService.getUnreadCount()
                notificationService.lastUnreadCount = this.unreadCount
            } catch (error) {
                console.error('Error refreshing notifications:', error)
                this.error = 'Failed to refresh notifications'
            } finally {
                this.loading = false
            }
        },

        handleServiceUpdate(type, data) {
            switch (type) {
                case 'new_notifications':
                    this.unreadCount = data.count
                    // Show a subtle notification for new notifications
                    if (data.newCount > 0) {
                        this.showNewNotificationFeedback(data.newCount)
                    }
                    // Refresh the list if dropdown is open
                    if (this.showDropdown) {
                        this.refreshNotificationsList()
                    }
                    break
                case 'notification_read':
                    this.updateNotificationRead(data.id)
                    break
                case 'all_notifications_read':
                    this.markAllNotificationsRead()
                    break
                case 'notification_deleted':
                    this.removeNotification(data.id)
                    break
            }
        },

        showNewNotificationFeedback(count) {
            // You could show a toast notification here
            // For now, just console.log
            console.log(`${count} new notification${count > 1 ? 's' : ''} received`)
        },

        async refreshNotificationsList() {
            try {
                this.notifications = await notificationService.getRecentNotifications()
            } catch (error) {
                console.error('Error refreshing notification list:', error)
            }
        },

        updateNotificationRead(id) {
            const notification = this.notifications.find(n => n.id === id)
            if (notification && !notification.read) {
                notification.read = true
                this.unreadCount = Math.max(0, this.unreadCount - 1)
            }
        },

        markAllNotificationsRead() {
            this.notifications.forEach(notification => {
                notification.read = true
            })
            this.unreadCount = 0
        },

        removeNotification(id) {
            const notification = this.notifications.find(n => n.id === id)
            if (notification && !notification.read) {
                this.unreadCount = Math.max(0, this.unreadCount - 1)
            }
            this.notifications = this.notifications.filter(n => n.id !== id)
        },

        async toggleDropdown() {
            this.showDropdown = !this.showDropdown
            if (this.showDropdown) {
                // Clear any errors when opening
                this.error = null
                // Refresh notifications when opening dropdown
                await this.refreshNotificationsList()
            }
        },

        async handleNotificationClick(notification) {
            try {
                // Mark as read if not already read
                if (!notification.read) {
                    await notificationService.markAsRead(notification.id)
                }

                // Navigate based on notification type
                this.navigateToRelatedPage(notification)

                // Close dropdown
                this.showDropdown = false
            } catch (error) {
                console.error('Error handling notification click:', error)
                this.error = 'Failed to process notification'
            }
        },

        async markSingleAsRead(id) {
            try {
                await notificationService.markAsRead(id)
            } catch (error) {
                console.error('Error marking notification as read:', error)
                this.error = 'Failed to mark as read'
            }
        },

        navigateToRelatedPage(notification) {
            const { type, data } = notification

            // Navigate to relevant page based on notification type
            switch (type) {
                case 'code_submission_created':
                    if (data.code_submission_id) {
                        this.$router.push(`/submissions/${data.code_submission_id}`)
                    }
                    break
                case 'review_submitted':
                case 'review_completed':
                    if (data.code_submission_id) {
                        this.$router.push(`/submissions/${data.code_submission_id}`)
                    } else if (data.review_id) {
                        this.$router.push(`/reviews/${data.review_id}`)
                    }
                    break
                case 'pull_request_created':
                case 'pr_review_completed':
                    if (data.pull_request_id) {
                        this.$router.push(`/pull-requests/${data.pull_request_id}`)
                    }
                    break
                default:
                    // Default to notifications page
                    this.$router.push('/notifications')
                    break
            }
        },

        async markAllAsRead() {
            if (this.markingAllRead || this.unreadCount === 0) return

            try {
                this.markingAllRead = true
                this.error = null
                await notificationService.markAllAsRead()
                // Success feedback will be handled by the service notification
            } catch (error) {
                console.error('Error marking all as read:', error)
                this.error = 'Failed to mark all as read'
            } finally {
                this.markingAllRead = false
            }
        },

        formatDate(date) {
            return notificationService.formatDate(date)
        },

        formatType(type) {
            return notificationService.formatType(type)
        },

        getTypeStyle(type) {
            return notificationService.getTypeStyle(type)
        }
    }
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar for notifications list */
.max-h-96::-webkit-scrollbar {
    width: 4px;
}

.max-h-96::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.max-h-96::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Smooth animations for notification items */
.group {
    transition: all 0.15s ease-in-out;
}
</style>
