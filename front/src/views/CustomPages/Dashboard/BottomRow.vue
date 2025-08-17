<template>
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
            <Chart
                v-if="barChartData"
                type="bar"
                :data="barChartData"
                :options="barChartOptions"
                class="h-80"
            />
            <div v-else class="flex justify-center items-center h-80">
                <span class="text-gray-500">Loading statistics...</span>
            </div>
        </div>

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
                                        <template v-if="activity.type === 'Pull Request'">
                                            <svg class="h-4 w-4 text-white" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M7.177 3.073L9.573.677A.25.25 0 0110 .854v4.792a.25.25 0 01-.427.177L7.177 3.427a.25.25 0 010-.354zM3.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122v5.256a2.251 2.251 0 11-1.5 0V5.372A2.25 2.25 0 011.5 3.25zM11 2.5h-1V4h1a1 1 0 011 1v5.628a2.251 2.251 0 101.5 0V5A2.5 2.5 0 0011 2.5zm1 10.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM3.75 12a.75.75 0 100 1.5.75.75 0 000-1.5z"></path>
                                            </svg>
                                        </template>
                                        <i v-else class="pi text-white text-sm" :class="activityIcon(activity.type)"></i>
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
                    <li v-if="recentActivity.length === 0" class="text-center text-gray-500 py-4">
                        No recent activity
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineProps } from 'vue'
import Chart from 'primevue/chart'

const props = defineProps({
    monthlyStats: Object,
    recentActivity: Array
})

const barChartData = computed(() => {
    if (!props.monthlyStats) return null
    return {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
            {
                label: 'Code Submissions',
                backgroundColor: 'rgba(124, 58, 237, 0.7)',
                data: props.monthlyStats.code_submissions
            },
            {
                label: 'Pull Requests',
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                data: props.monthlyStats.pull_requests
            },
            {
                label: 'Reviews',
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                data: props.monthlyStats.reviews
            }
        ]
    }
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
    return type === 'Submission' ? 'pi-code' :
        type === 'Pull Request' ? 'pi-git-pull-request' :
            type === 'Review' ? 'pi-check-circle' :
                'pi-code' // default fallback
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const options = { month: 'short', day: 'numeric' }
    return date.toLocaleDateString('fr-FR', options)
}
</script>
