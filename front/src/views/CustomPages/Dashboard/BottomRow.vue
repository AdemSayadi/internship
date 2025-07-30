<template>
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Score Distribution Chart -->
        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Score Distribution</h3>
            <Chart
                type="bar"
                :data="barChartData"
                :options="barChartOptions"
                class="h-80"
            />
        </div>

        <!-- Recent Activity Timeline -->
        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="flow-root">
                <ul class="-mb-8">
                    <li v-for="(activity, index) in recentActivity" :key="index">
                        <div class="relative pb-8">
                            <span v-if="index !== recentActivity.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                  <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                    <i class="pi text-white text-sm" :class="activityIcon(activity.type)"></i>
                  </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-800">
                                            <span class="font-medium text-gray-900">{{ activity.title }}</span>
                                        </p>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        <time :datetime="activity.created_at">{{ formatDate(activity.created_at) }}</time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Chart from 'primevue/chart'

const recentActivity = ref([
    { type: 'Submission', title: 'Feature X', created_at: '2025-07-02' },
    { type: 'Review', title: 'Review for Feature Y', created_at: '2025-07-16' },
    { type: 'Submission', title: 'Feature Y', created_at: '2025-07-16' },
    { type: 'Submission', title: 'Bug Fix', created_at: '2025-07-03' },
    { type: 'Submission', title: 'Optimization', created_at: '2025-07-21' },
])

const barChartData = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
    datasets: [
        {
            label: 'Submissions',
            backgroundColor: 'rgba(124, 58, 237, 0.7)',
            data: [12, 19, 15, 22, 18, 25, 30]
        },
        {
            label: 'Reviews',
            backgroundColor: 'rgba(99, 102, 241, 0.7)',
            data: [8, 15, 12, 18, 14, 20, 25]
        }
    ]
})

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: { beginAtZero: true }
    },
    plugins: {
        legend: { position: 'top' }
    }
}

const activityIcon = (type) => {
    return type === 'Submission' ? 'pi-code' : 'pi-check-circle'
}

const formatDate = (dateString) => {
    const options = { month: 'short', day: 'numeric' }
    return new Date(dateString).toLocaleDateString(undefined, options)
}
</script>
