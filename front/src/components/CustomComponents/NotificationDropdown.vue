<template>
    <div class="relative">
        <button
            @click="toggleDropdown"
            class="relative p-2 hover:bg-gray-100 rounded-lg transition-colors"
            :class="{ 'bg-gray-100': showDropdown }"
        >
            <BellIcon class="h-6 w-6 text-gray-600" />
            <span v-if="unreadCount > 0"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
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
            <div v-if="showDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50 border border-gray-200">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                        >
                            Mark all read
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-4 py-8 text-center">
                    <div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-blue-600 rounded-full" role="status" aria-label="loading">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Loading notifications...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center">
                    <BellIcon class="h-8 w-8 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">No notifications</p>
                </div>

                <!-- Notifications List -->
                <div v-else class="max-h-96 overflow-y-auto">
                    <div v-for="notification in notifications" :key="notification.id"
                         class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                         @click="handleNotificationClick(notification)">
                        <div class="flex items-start gap-3">
                            <!-- Status Indicator -->
                            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-2"
                                 :class="notification.read ? 'bg-gray-300' : 'bg-blue-500'"></div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <!-- Type Badge -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1"
                                      :class="getTypeStyle(notification.type)">
                                    {{ formatType(notification.type) }}
                                </span>

                                <!-- Title -->
                                <h4 class="font-medium text-sm text-gray-900 truncate">{{ notification.title }}</h4>

                                <!-- Message -->
                                <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ notification.message }}</p>

                                <!-- Time -->
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(notification.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 bg-gray-50 rounded-b-lg">
                    <router-link
                        to="/notifications"
                        class="text-blue-600 text-sm hover:text-blue-800 font-medium transition-colors"
                        @click="showDropdown = false"
                    >
                        View all notifications →
                    </router-link>
                </div>
            </div>
        </transition>

        <!-- Overlay to close dropdown when clicking outside -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="showDropdown = false"></div>
    </div>
</template>

<script>
import { BellIcon } from '@heroicons/vue/24/outline'
import notificationService from '@/utils/services/NotificationService'

export default {
    name: 'NotificationsDropdown',
    components: { BellIcon },
    data() {
        return {
            showDropdown: false,
            notifications: [],
            unreadCount: 0,
            loading: false,
            unsubscribe: null
        }
    },
    async mounted() {
        // Subscribe to notification service updates
        this.unsubscribe = notificationService.subscribe(this.handleServiceUpdate)

        // Start polling and load initial data
        notificationService.startPolling()
        await this.loadInitialData()
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
                const [notifications, count] = await Promise.all([
                    notificationService.getRecentNotifications(),
                    notificationService.getUnreadCount()
                ])

                this.notifications = notifications
                this.unreadCount = count
                notificationService.lastUnreadCount = count
            } catch (error) {
                console.error('Error loading initial notification data:', error)
            } finally {
                this.loading = false
            }
        },

        handleServiceUpdate(type, data) {
            switch (type) {
                case 'new_notifications':
                    this.unreadCount = data.count
                    this.refreshNotifications()
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

        async refreshNotifications() {
            if (!this.showDropdown) {
                try {
                    this.notifications = await notificationService.getRecentNotifications()
                } catch (error) {
                    console.error('Error refreshing notifications:', error)
                }
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
                // Refresh notifications when opening dropdown
                await this.refreshNotifications()
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
            }
        },

        navigateToRelatedPage(notification) {
            const { type, data } = notification

            // Navigate to relevant page based on notification type
            switch (type) {
                case 'code_submission_created':
                    if (data.code_submission_id) {
                        this.$router.push(`/code-submissions/${data.code_submission_id}`)
                    }
                    break
                case 'review_submitted':
                case 'review_completed':
                    if (data.code_submission_id) {
                        this.$router.push(`/code-submissions/${data.code_submission_id}`)
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
            try {
                await notificationService.markAllAsRead()
                // Success feedback will be handled by the service notification
            } catch (error) {
                console.error('Error marking all as read:', error)
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
</style>
