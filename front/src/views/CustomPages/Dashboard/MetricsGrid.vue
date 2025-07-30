<template>
    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard
            title="Repositories"
            :value="metrics.repositories"
            icon="pi pi-folder"
            color="bg-indigo-600"
            trend="up"
        />
        <MetricCard
            title="Submissions"
            :value="metrics.submissions"
            icon="pi pi-code"
            color="bg-purple-600"
            trend="up"
        />
        <MetricCard
            title="Reviews"
            :value="metrics.reviews"
            icon="pi pi-check-circle"
            color="bg-blue-600"
            trend="up"
        />
        <MetricCard
            title="Unread Notifications"
            :value="metrics.unreadNotifications"
            icon="pi pi-bell"
            color="bg-red-600"
            trend="down"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import MetricCard from '@/views/CustomPages/Dashboard/MetricCard.vue'

// Initialize with actual values
const metrics = ref({
    repositories: 3,
    submissions: 4,
    reviews: 2,
    unreadNotifications: 1,
    completionRate: 50,
    avgOverallScore: 87,
    avgComplexityScore: 82,
    avgSecurityScore: 92,
    avgMaintainabilityScore: 87,
    totalBugs: 2
})
</script>
