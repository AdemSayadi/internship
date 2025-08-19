<template>
    <div class="relative">
        <button @click="toggleDropdown" class="relative p-2">
            <BellIcon class="h-6 w-6" />
            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ unreadCount }}
            </span>
        </button>

        <div v-if="showDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 border">
            <div class="py-2">
                <div class="px-4 py-2 border-b">
                    <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                </div>

                <div v-if="loading" class="px-4 py-8 text-center">
                    <div class="animate-spin inline-block w-6 h-6 border-[3px] border-current border-t-transparent text-blue-600 rounded-full" role="status" aria-label="loading">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>

                <div v-else-if="notifications.length === 0" class="px-4 py-8 text-center text-gray-500 text-sm">
                    No notifications
                </div>

                <div v-else class="max-h-96 overflow-y-auto">
                    <div v-for="notification in notifications" :key="notification.id"
                         class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                         @click="markAsRead(notification.id)">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full flex-shrink-0 mt-2"
                                 :class="notification.read ? 'bg-gray-300' : 'bg-blue-500'"></div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-sm text-gray-900 truncate">{{ notification.title }}</h4>
                                <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ notification.message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(notification.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t px-4 py-2">
                    <router-link to="/notifications" class="text-blue-600 text-sm hover:underline" @click="showDropdown = false">
                        View all notifications
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Overlay to close dropdown when clicking outside -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="showDropdown = false"></div>
    </div>
</template>

<script>
import { BellIcon } from '@heroicons/vue/24/outline'

export default {
    name: 'NotificationsDropdown',
    components: { BellIcon },
    data() {
        return {
            showDropdown: false,
            notifications: [],
            unreadCount: 0,
            loading: false,
            pollingInterval: null
        }
    },
    async mounted() {
        await this.loadNotifications()
        await this.loadUnreadCount()
        this.startPolling()
    },
    beforeUnmount() {
        this.stopPolling()
    },
    methods: {
        async loadNotifications() {
            try {
                this.loading = true
                const response = await fetch('http://localhost:8000/api/notifications/recent', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                })

                if (response.ok) {
                    const data = await response.json()
                    this.notifications = data.data
                }
            } catch (error) {
                console.error('Error loading notifications:', error)
            } finally {
                this.loading = false
            }
        },

        async loadUnreadCount() {
            try {
                const response = await fetch('http://localhost:8000/api/notifications/unread-count', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                })

                if (response.ok) {
                    const data = await response.json()
                    this.unreadCount = data.unread_count || 0
                }
            } catch (error) {
                console.error('Error loading unread count:', error)
            }
        },

        startPolling() {
            this.pollingInterval = setInterval(async () => {
                await this.loadUnreadCount()
                // Only refresh notifications if dropdown is closed to avoid disruption
                if (!this.showDropdown) {
                    await this.loadNotifications()
                }
            }, 30000) // Poll every 30 seconds
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval)
                this.pollingInterval = null
            }
        },

        async toggleDropdown() {
            this.showDropdown = !this.showDropdown
            if (this.showDropdown) {
                // Refresh notifications when opening dropdown
                await this.loadNotifications()
            }
        },

        async markAsRead(notificationId) {
            try {
                const response = await fetch(`http://localhost:8000/api/notifications/${notificationId}`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ read: true })
                })

                if (response.ok) {
                    const notification = this.notifications.find(n => n.id === notificationId)
                    if (notification && !notification.read) {
                        notification.read = true
                        this.unreadCount = Math.max(0, this.unreadCount - 1)
                    }
                }
            } catch (error) {
                console.error('Error marking notification as read:', error)
            }
        },

        formatDate(date) {
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
</style>
