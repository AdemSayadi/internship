<template>
    <MainLayout>
        <PageHeader title="Notifications" subtitle="Stay updated with your code review notifications." />
        <DataTable
            :value="notifications"
            class="mt-8"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20]"
        >
            <Column field="message" header="Message" sortable />
            <Column field="read" header="Status" sortable>
                <template #body="slotProps">
                    <span :class="slotProps.data.read ? 'text-green-600' : 'text-red-600'">
                        {{ slotProps.data.read ? 'Read' : 'Unread' }}
                    </span>
                </template>
            </Column>
            <Column field="created_at" header="Date" sortable>
                <template #body="slotProps">
                    {{ new Date(slotProps.data.created_at).toLocaleDateString() }}
                </template>
            </Column>
            <Column header="Actions">
                <template #body="slotProps">
                    <Button
                        v-if="!slotProps.data.read"
                        label="Mark as Read"
                        icon="pi pi-check"
                        class="p-button-text"
                        @click="markAsRead(slotProps.data.id)"
                    />
                    <Button
                        label="View Review"
                        icon="pi pi-eye"
                        class="p-button-text"
                        @click="$router.push(`/reviews/${slotProps.data.review.code_submission_id}`)"
                    />
                </template>
            </Column>
        </DataTable>
    </MainLayout>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import MainLayout from '@/components/CustomComponents/MainLayout.vue';
import PageHeader from '@/components/CustomComponents/PageHeader.vue';
import { useAuth } from '@/utils/composables/useAuth';

const notifications = ref([
    { id: 1, message: 'New review for Feature X', read: false, created_at: '2025-07-02', review: { code_submission_id: 1 } },
    { id: 2, message: 'Review updated for Bug Fix', read: true, created_at: '2025-07-03', review: { code_submission_id: 2 } },
]);
const router = useRouter();
const toast = useToast();

const markAsRead = (id) => {
    const notification = notifications.value.find((n) => n.id === id);
    if (notification) {
        notification.read = true;
        toast.add({ severity: 'success', summary: 'Success', detail: 'Notification marked as read', life: 3000 });
    }
};

const { checkAuth } = useAuth();
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
});
</script>

<style scoped>
@import '@/assets/GlobalStyles.css';
</style>
