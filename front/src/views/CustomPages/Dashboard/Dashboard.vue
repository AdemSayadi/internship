<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <AnimatedBackground />

        <header class="absolute inset-x-0 top-0 z-50">
            <Navbar />
        </header>

        <div class="relative isolate px-6 pt-10 lg:px-8">
            <div class="mx-auto max-w-7xl py-16 sm:py-24 lg:py-32">
                <DashboardHeader :is-loaded="isLoaded" />
                <div v-if="isLoaded && !isError">
                    <MetricsGrid :metrics="metrics" />
                    <MainContentGrid :metrics="metrics" :radar-chart-data="radarChartData" />
                    <BottomRow :monthly-stats="monthlyStats" :recent-activity="recentActivity" />
                </div>
                <div v-else-if="isError" class="flex flex-col justify-center items-center py-12">
                    <div class="text-red-500 text-lg mb-4">{{ errorMessage }}</div>
                    <button
                        @click="retryFetch"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        Retry
                    </button>
                </div>
                <div v-else class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600">Loading dashboard...</span>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Navbar from '@/components/CustomComponents/Navbar.vue'
import Footer from '@/components/CustomComponents/Footer.vue'
import AnimatedBackground from '@/views/CustomPages/Dashboard/AnimatedBackground.vue'
import DashboardHeader from '@/views/CustomPages/Dashboard/DashboardHeader.vue'
import MetricsGrid from '@/views/CustomPages/Dashboard/MetricsGrid.vue'
import MainContentGrid from '@/views/CustomPages/Dashboard/MainContentGrid.vue'
import BottomRow from '@/views/CustomPages/Dashboard/BottomRow.vue'
import { useAuth } from '@/utils/composables/useAuth'
import router from '@/router'

const isLoaded = ref(false)
const isError = ref(false)
const errorMessage = ref('')

const metrics = ref({
    repositories: 0,
    code_submissions: 0,
    pull_requests: 0,
    total_reviews: 0,
    completionRate: 0,
    avgOverallScore: 0,
    avgComplexityScore: 0,
    avgSecurityScore: 0,
    avgMaintainabilityScore: 0,
    totalBugs: 0,
    completion_rate: 0,
    avg_overall_score: 0,
    avg_complexity_score: 0,
    avg_security_score: 0,
    avg_maintainability_score: 0,
    total_bugs: 0
})

const radarChartData = ref({
    labels: ['Overall', 'Complexity', 'Security', 'Maintainability', 'Bugs Fixed'],
    datasets: [{
        label: 'Average Scores',
        backgroundColor: 'rgba(99, 102, 241, 0.2)',
        borderColor: 'rgba(99, 102, 241, 1)',
        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
        data: [0, 0, 0, 0, 0]
    }]
})

const monthlyStats = ref({
    code_submissions: [],
    pull_requests: [],
    reviews: []
})
const recentActivity = ref([])

const { checkAuth } = useAuth()
const API_BASE = 'http://localhost:8000/api'

const fetchDashboardStats = async () => {
    isLoaded.value = false
    isError.value = false
    errorMessage.value = ''

    try {
        const token = localStorage.getItem('token')

        if (!token) {
            router.push('/auth/login1')
            return
        }

        const response = await fetch(`${API_BASE}/dashboard/stats`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })

        if (response.status === 401) {
            localStorage.removeItem('token')
            router.push('/auth/login1')
            return
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        if (!data.success || !data.stats) {
            throw new Error('Invalid API response structure')
        }

        // Update metrics with proper mapping
        const stats = data.stats
        metrics.value = {
            repositories: stats.repositories || 0,
            code_submissions: stats.code_submissions || 0,
            pull_requests: stats.pull_requests || 0,
            total_reviews: stats.total_reviews || 0,
            completionRate: stats.completion_rate || 0,
            avgOverallScore: stats.avg_overall_score || 0,
            avgComplexityScore: stats.avg_complexity_score || 0,
            avgSecurityScore: stats.avg_security_score || 0,
            avgMaintainabilityScore: stats.avg_maintainability_score || 0,
            totalBugs: stats.total_bugs || 0,
            // Keep snake_case versions too
            completion_rate: stats.completion_rate || 0,
            avg_overall_score: stats.avg_overall_score || 0,
            avg_complexity_score: stats.avg_complexity_score || 0,
            avg_security_score: stats.avg_security_score || 0,
            avg_maintainability_score: stats.avg_maintainability_score || 0,
            total_bugs: stats.total_bugs || 0
        }

        // Update radar chart data with "Bugs Fixed" score calculation
        // Formula: Higher score = fewer bugs (inverted scale)
        // If 0 bugs: 100 points
        // If bugs exist: 100 - (bugs / (bugs + 10) * 100)
        // This creates a diminishing penalty as bug count increases
        const bugsFixedScore = stats.total_bugs > 0
            ? Math.max(0, 100 - (stats.total_bugs / (stats.total_bugs + 10) * 100))
            : 100

        radarChartData.value.datasets[0].data = [
            stats.avg_overall_score || 0,
            stats.avg_complexity_score || 0,
            stats.avg_security_score || 0,
            stats.avg_maintainability_score || 0,
            bugsFixedScore
        ]

        monthlyStats.value = stats.monthly_stats || {
            code_submissions: Array(12).fill(0),
            pull_requests: Array(12).fill(0),
            reviews: Array(12).fill(0)
        }

        recentActivity.value = stats.latest_activity || []

    } catch (error) {
        console.error('Error fetching dashboard stats:', error)
        isError.value = true
        errorMessage.value = `Failed to load dashboard: ${error.message}`
    } finally {
        isLoaded.value = true
    }
}

const retryFetch = () => {
    fetchDashboardStats()
}

onMounted(async () => {
    if (!checkAuth()) {
        router.push('/auth/login1')
        return
    }

    await fetchDashboardStats()
})
</script>
