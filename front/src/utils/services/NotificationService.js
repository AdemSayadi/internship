// utils/services/NotificationService.js
class NotificationService {
    constructor() {
        this.baseURL = 'http://localhost:8000/api'
        this.listeners = new Set()
        this.pollingInterval = null
        this.isPolling = false
        this.lastUnreadCount = 0
    }

    getAuthHeaders() {
        return {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }

    // Subscribe to notification updates
    subscribe(callback) {
        this.listeners.add(callback)
        return () => this.listeners.delete(callback)
    }

    // Notify all subscribers
    notify(type, data) {
        this.listeners.forEach(callback => {
            try {
                callback(type, data)
            } catch (error) {
                console.error('Error in notification listener:', error)
            }
        })
    }

    // Start polling for updates
    startPolling(interval = 30000) {
        if (this.isPolling) return

        this.isPolling = true
        this.pollingInterval = setInterval(async () => {
            await this.checkForUpdates()
        }, interval)

        // Initial check
        this.checkForUpdates()
    }

    // Stop polling
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval)
            this.pollingInterval = null
            this.isPolling = false
        }
    }

    // Check for new notifications
    async checkForUpdates() {
        try {
            const count = await this.getUnreadCount()

            // If count increased, we have new notifications
            if (count > this.lastUnreadCount) {
                this.notify('new_notifications', {
                    count,
                    newCount: count - this.lastUnreadCount
                })
            }

            this.lastUnreadCount = count
        } catch (error) {
            console.error('Error checking for updates:', error)
        }
    }

    // API Methods
    async getNotifications(filter = null) {
        let url = `${this.baseURL}/notifications`

        if (filter === 'unread') {
            url += '?read=false'
        } else if (filter === 'read') {
            url += '?read=true'
        }

        const response = await fetch(url, {
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        const data = await response.json()
        return data.data || data
    }

    async getRecentNotifications() {
        const response = await fetch(`${this.baseURL}/notifications/recent`, {
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        const data = await response.json()
        return data.data || data
    }

    async getUnreadCount() {
        const response = await fetch(`${this.baseURL}/notifications/unread-count`, {
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        const data = await response.json()
        return data.unread_count || 0
    }

    async markAsRead(id) {
        const response = await fetch(`${this.baseURL}/notifications/${id}`, {
            method: 'PATCH',
            headers: this.getAuthHeaders(),
            body: JSON.stringify({ read: true })
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        this.notify('notification_read', { id })
        return await response.json()
    }

    async markAllAsRead() {
        const response = await fetch(`${this.baseURL}/notifications/mark-all-read`, {
            method: 'PATCH',
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        this.notify('all_notifications_read', {})
        return await response.json()
    }

    async deleteNotification(id) {
        const response = await fetch(`${this.baseURL}/notifications/${id}`, {
            method: 'DELETE',
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        this.notify('notification_deleted', { id })
        return await response.json()
    }

    async clearReadNotifications() {
        const response = await fetch(`${this.baseURL}/notifications/clear-read`, {
            method: 'DELETE',
            headers: this.getAuthHeaders()
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        this.notify('read_notifications_cleared', {})
        return await response.json()
    }

    // Utility methods
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

    formatType(type) {
        const typeMap = {
            'code_submission_created': 'Code Submission',
            'review_submitted': 'Review Started',
            'review_completed': 'Review Done',
            'pull_request_created': 'Pull Request',
            'pr_review_completed': 'PR Review Done'
        }
        return typeMap[type] || type.replace('_', ' ')
    }

    getTypeStyle(type) {
        const styles = {
            'code_submission_created': 'bg-green-100 text-green-800',
            'review_submitted': 'bg-yellow-100 text-yellow-800',
            'review_completed': 'bg-blue-100 text-blue-800',
            'pull_request_created': 'bg-purple-100 text-purple-800',
            'pr_review_completed': 'bg-indigo-100 text-indigo-800'
        }
        return styles[type] || 'bg-gray-100 text-gray-800'
    }
}

// Create singleton instance
const notificationService = new NotificationService()

export default notificationService
