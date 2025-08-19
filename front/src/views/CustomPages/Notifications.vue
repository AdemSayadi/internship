<template>
    <MainLayout>
        <PageHeader title="Notifications" subtitle="Stay updated with your code review notifications." />

        <!-- Action buttons -->
        <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            <div class="flex gap-2">
                <Button
                    v-if="unreadCount > 0"
                    label="Mark All Read"
                    icon="pi pi-check"
                    class="p-button-outlined"
                    @click="handleMarkAllAsRead"
                />
                <Button
                    v-if="hasReadNotifications"
                    label="Clear Read"
                    icon="pi pi-trash"
                    class="p-button-outlined p-button-danger"
                    @click="handleClearReadNotifications"
                />
            </div>

            <!-- Filter buttons -->
            <div class="flex gap-2">
                <Button
                    :label="`All (${totalCount})`"
                    :class="currentFilter === 'all' ? 'p-button-primary' : 'p-button-outlined'"
                    @click="setFilter('all')"
                />
                <Button
                    :label="`Unread (${unreadCount})`"
                    :class="currentFilter === 'unread' ? 'p-button-primary' : 'p-button-outlined'"
                    @click="setFilter('unread')"
                />
                <Button
                    :label="`Read (${readCount})`"
                    :class="currentFilter === 'read' ? 'p-button-primary' : 'p-button-outlined'"
                    @click="setFilter('read')"
                />
            </div>
        </div>

        <!-- Notifications Table -->
        <DataTable
            :value="notifications"
            class="mt-8"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            :loading="loading"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} notifications"
            responsiveLayout="scroll"
        >
            <!-- Type Column -->
            <Column field="type" header="Type" sortable class="w-32">
                <template #body="slotProps">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getTypeStyle(slotProps.data.type)">
                        {{ formatType(slotProps.data.type) }}
                    </span>
                </template>
            </Column>

            <!-- Title Column -->
            <Column field="title" header="Title" sortable class="min-w-48">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full flex-shrink-0"
                             :class="slotProps.data.read ? 'bg-gray-300' : 'bg-blue-500'"></div>
                        <span class="font-medium text-gray-900">{{ slotProps.data.title }}</span>
                    </div>
                </template>
            </Column>

            <!-- Message Column -->
            <Column field="message" header="Message" class="min-w-64">
                <template #body="slotProps">
                    <p class="text-sm text-gray-600 line-clamp-2">{{ slotProps.data.message }}</p>
                </template>
            </Column>

            <!-- Status Column -->
            <Column field="read" header="Status" sortable class="w-24">
                <template #body="slotProps">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="slotProps.data.read ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ slotProps.data.read ? 'Read' : 'Unread' }}
                    </span>
                </template>
            </Column>

            <!-- Date Column -->
            <Column field="created_at" header="Date" sortable class="w-36">
                <template #body="slotProps">
                    <div class="text-sm">
                        <div class="font-medium text-gray-900">
                            {{ formatDate(slotProps.data.created_at) }}
                        </div>
                        <div class="text-gray-500">
                            {{ formatTime(slotProps.data.created_at) }}
                        </div>
                    </div>
                </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" class="w-40">
                <template #body="slotProps">
                    <div class="flex gap-2">
                        <Button
                            v-if="!slotProps.data.read"
                            icon="pi pi-check"
                            class="p-button-text p-button-success p-button-sm"
                            v-tooltip.top="'Mark as read'"
                            @click="handleMarkAsRead(slotProps.data.id)"
                        />
                        <Button
                            icon="pi pi-eye"
                            class="p-button-text p-button-info p-button-sm"
                            v-tooltip.top="'View details'"
                            @click="viewNotification(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            class="p-button-text p-button-danger p-button-sm"
                            v-tooltip.top="'Delete'"
                            @click="handleDeleteNotification(slotProps.data.id)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Empty State -->
        <div v-if="!loading && notifications.length === 0"
             class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
                <svg fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications found</h3>
            <p class="text-gray-500">
                {{ currentFilter === 'all' ? 'You don\'t have any notifications yet.' :
                currentFilter === 'unread' ? 'No unread notifications.' : 'No read notifications.' }}
            </p>
        </div>

        <!-- Notification Detail Dialog -->
        <Dialog
            v-model:visible="showDetailDialog"
            modal
            header="Notification Details"
            :style="{ width: '50rem' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">

            <div v-if="selectedNotification" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getTypeStyle(selectedNotification.type)">
                        {{ formatType(selectedNotification.type) }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <p class="text-gray-900">{{ selectedNotification.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <p class="text-gray-900">{{ selectedNotification.message }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="selectedNotification.read ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ selectedNotification.read ? 'Read' : 'Unread' }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                    <p class="text-gray-900">{{ formatFullDate(selectedNotification.created_at) }}</p>
                </div>

                <div v-if="selectedNotification.data" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional Data</label>
                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto">{{ JSON.stringify(selectedNotification.data, null, 2) }}</pre>
                </div>
            </div>

            <template #footer>
                <Button
                    v-if="selectedNotification && !selectedNotification.read"
                    label="Mark as Read"
                    icon="pi pi-check"
                    @click="markAsReadAndClose(selectedNotification.id)"
                    class="mr-2" />
                <Button
                    label="Close"
                    icon="pi pi-times"
                    @click="showDetailDialog = false"
                    class="p-button-secondary" />
            </template>
        </Dialog>
    </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

const notifications = ref([]);
const loading = ref(true);
const currentFilter = ref('all');
const showDetailDialog = ref(false);
const selectedNotification = ref(null);
const router = useRouter();
const { checkAuth } = useAuth();

const baseURL = 'http://localhost:8000/api';

const getAuthHeaders = () => ({
    'Authorization': `Bearer ${localStorage.getItem('token')}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
});

// Computed properties for counts
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);
const readCount = computed(() => notifications.value.filter(n => n.read).length);
const totalCount = computed(() => notifications.value.length);
const hasReadNotifications = computed(() => readCount.value > 0);

// Toast function
const showToast = (severity, summary, detail) => {
    // Simple console log for now - replace with your toast implementation
    console.log(`${severity.toUpperCase()}: ${summary} - ${detail}`);

    // If you have a global toast service, use it here
    // For example with PrimeVue:
    // const toast = useToast();
    // toast.add({ severity, summary, detail, life: 3000 });
};

const loadNotifications = async (filter = null) => {
    try {
        loading.value = true;
        let url = `${baseURL}/notifications`;

        if (filter === 'unread') {
            url += '?read=false';
        } else if (filter === 'read') {
            url += '?read=true';
        }

        const response = await fetch(url, {
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.data || data;
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        showToast('error', 'Error', 'Failed to load notifications');
    } finally {
        loading.value = false;
    }
};

const setFilter = (filter) => {
    currentFilter.value = filter;
    loadNotifications(filter === 'all' ? null : filter);
};

const handleMarkAsRead = async (id) => {
    try {
        const response = await fetch(`${baseURL}/notifications/${id}`, {
            method: 'PATCH',
            headers: getAuthHeaders(),
            body: JSON.stringify({ read: true })
        });

        if (response.ok) {
            const notification = notifications.value.find(n => n.id === id);
            if (notification) {
                notification.read = true;
            }

            showToast('success', 'Success', 'Notification marked as read');
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Error', 'Failed to mark notification as read');
    }
};

const handleMarkAllAsRead = async () => {
    try {
        const response = await fetch(`${baseURL}/notifications/mark-all-read`, {
            method: 'PATCH',
            headers: getAuthHeaders()
        });

        if (response.ok) {
            notifications.value.forEach(notification => {
                notification.read = true;
            });

            const data = await response.json();
            showToast('success', 'Success', data.message);
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Error', 'Failed to mark all notifications as read');
    }
};

const handleClearReadNotifications = async () => {
    try {
        const response = await fetch(`${baseURL}/notifications/clear-read`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        if (response.ok) {
            notifications.value = notifications.value.filter(n => !n.read);

            const data = await response.json();
            showToast('success', 'Success', data.message);
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Error', 'Failed to clear read notifications');
    }
};

const handleDeleteNotification = async (id) => {
    try {
        const response = await fetch(`${baseURL}/notifications/${id}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        if (response.ok) {
            notifications.value = notifications.value.filter(n => n.id !== id);
            showToast('success', 'Success', 'Notification deleted');
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Error', 'Failed to delete notification');
    }
};

const viewNotification = (notification) => {
    selectedNotification.value = notification;
    showDetailDialog.value = true;
};

const markAsReadAndClose = async (id) => {
    await handleMarkAsRead(id);
    showDetailDialog.value = false;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatFullDate = (date) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatType = (type) => {
    const typeMap = {
        'code_submission_created': 'Code Submission',
        'review_submitted': 'Review Started',
        'review_completed': 'Review Done',
        'pull_request_created': 'Pull Request',
        'pr_review_completed': 'PR Review Done'
    };
    return typeMap[type] || type.replace('_', ' ');
};

const getTypeStyle = (type) => {
    const styles = {
        'code_submission_created': 'bg-green-100 text-green-800',
        'review_submitted': 'bg-yellow-100 text-yellow-800',
        'review_completed': 'bg-blue-100 text-blue-800',
        'pull_request_created': 'bg-purple-100 text-purple-800',
        'pr_review_completed': 'bg-indigo-100 text-indigo-800'
    };
    return styles[type] || 'bg-gray-100 text-gray-800';
};

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1');
        return;
    }
    await loadNotifications();
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
