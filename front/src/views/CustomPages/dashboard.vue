<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl animate-pulse" aria-hidden="true">
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] via-[#ff6b9d] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] transition-all duration-1000 ease-in-out"
                :style="{ transform: `rotate(${rotation}deg) scale(${scale})` }"
            />
        </div>

        <div class="absolute inset-x-0 bottom-0 -z-10 transform-gpu overflow-hidden blur-3xl" aria-hidden="true">
            <div
                class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#9089fc] via-[#7c3aed] to-[#ff80b5] opacity-25 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem] transition-all duration-1500 ease-in-out"
                :style="{ transform: `rotate(${-rotation}deg) scale(${scale})` }"
            />
        </div>

        <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
            <div
                v-for="particle in particles"
                :key="particle.id"
                class="absolute w-2 h-2 bg-gradient-to-r from-indigo-400 to-purple-400 rounded-full opacity-20 animate-bounce"
                :style="{
          left: particle.x + '%',
          top: particle.y + '%',
          animationDelay: particle.delay + 's',
          animationDuration: particle.duration + 's'
        }"
            />
        </div>

        <header class="absolute inset-x-0 top-0 z-50">
            <Navbar />
        </header>

        <div class="relative isolate px-6 pt-10 lg:px-8">
            <div class="mx-auto max-w-7xl py-16 sm:py-24 lg:py-32">
                <!-- Header -->
                <div class="text-center">
                    <h1 class="text-balance text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl transition-all duration-700 ease-out"
                        :class="{ 'animate-pulse': isLoaded }">
                        {{ displayedTitle }}<span class="animate-pulse">|</span>
                    </h1>
                    <p class="mt-6 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8 transition-all duration-700 delay-300 ease-out transform"
                       :class="{ 'translate-y-0 opacity-100': isLoaded, 'translate-y-10 opacity-0': !isLoaded }">
                        Overview of your code review activity and metrics
                    </p>
                </div>

                <!-- Metrics Grid -->
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

                <!-- Main Content Grid -->
                <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Score Radar Chart -->
                    <div class="lg:col-span-2">
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6 h-full">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Code Quality Scores</h3>
                            <Chart
                                type="radar"
                                :data="radarChartData"
                                :options="radarChartOptions"
                                class="h-80"
                            />
                        </div>
                    </div>

                    <!-- Progress Circles -->
                    <div class="space-y-6">
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Completion</h3>
                            <div class="flex justify-center">
                                <CircularProgress
                                    :value="metrics.completionRate"
                                    size="180"
                                    stroke-width="12"
                                    primary-color="#7c3aed"
                                    secondary-color="#e9d5ff"
                                >
                                    <span class="text-2xl font-bold text-gray-900">{{ metrics.completionRate }}%</span>
                                </CircularProgress>
                            </div>
                        </div>

                        <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Average Score</h3>
                            <div class="flex justify-center">
                                <CircularProgress
                                    :value="metrics.avgOverallScore"
                                    size="180"
                                    stroke-width="12"
                                    primary-color="#6366f1"
                                    secondary-color="#e0e7ff"
                                >
                                    <span class="text-2xl font-bold text-gray-900">{{ metrics.avgOverallScore }}</span>
                                </CircularProgress>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
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
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Navbar from '@/components/CustomComponents/Navbar.vue'
import Footer from '@/components/CustomComponents/Footer.vue'
import Chart from 'primevue/chart'
import CircularProgress from "@/components/CustomComponents/CircularProgress.vue";
import MetricCard from '@/components/CustomComponents/MetricCard.vue'

// Mock data
const mockRepositories = [
    { id: 1, name: 'Project Alpha', url: 'https://github.com/user/project-alpha', provider: 'github', created_at: '2025-07-01' },
    { id: 2, name: 'Project Beta', url: null, provider: 'manual', created_at: '2025-07-15' },
    { id: 3, name: 'Project Gamma', url: 'https://github.com/user/project-gamma', provider: 'github', created_at: '2025-07-20' },
]

const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
    { id: 3, repository_id: 2, title: 'Feature Y', language: 'java', code_content: 'public class Main { public static void main(String[] args) { System.out.println("Hello"); } }', file_path: 'src/Main.java', created_at: '2025-07-16', reviews: [] },
    { id: 4, repository_id: 3, title: 'Optimization', language: 'cpp', code_content: '#include <iostream>\n int main() { std::cout << "Optimized"; return 0; }', file_path: 'src/main.cpp', created_at: '2025-07-21', reviews: [] },
]

const mockReviews = [
    {
        id: 1,
        code_submission_id: 1,
        status: 'completed',
        overall_score: 85,
        complexity_score: 80,
        security_score: 90,
        maintainability_score: 85,
        bug_count: 2,
        ai_summary: 'Good code structure, but consider reducing complexity in loops.',
        suggestions: ['Use array methods instead of for loops', 'Add input validation'],
        created_at: '2025-07-02',
    },
    {
        id: 2,
        code_submission_id: 3,
        status: 'completed',
        overall_score: 90,
        complexity_score: 85,
        security_score: 95,
        maintainability_score: 90,
        bug_count: 0,
        ai_summary: 'Well-structured code with minimal issues.',
        suggestions: ['Consider adding comments for clarity'],
        created_at: '2025-07-16',
    },
]

const mockNotifications = [
    { id: 1, message: 'New review for Feature X', read: false, created_at: '2025-07-02', review: { code_submission_id: 1 } },
    { id: 2, message: 'Review updated for Feature Y', read: true, created_at: '2025-07-16', review: { code_submission_id: 3 } },
]

// Metrics
const metrics = ref({
    repositories: 0,
    submissions: 0,
    reviews: 0,
    unreadNotifications: 0,
    completionRate: 0,
    avgOverallScore: 0,
    avgComplexityScore: 0,
    avgSecurityScore: 0,
    avgMaintainabilityScore: 0,
    totalBugs: 0,
})

// Chart Data
const radarChartData = ref({
    labels: ['Overall', 'Complexity', 'Security', 'Maintainability', 'Bugs Fixed'],
    datasets: [
        {
            label: 'Average Scores',
            backgroundColor: 'rgba(99, 102, 241, 0.2)',
            borderColor: 'rgba(99, 102, 241, 1)',
            pointBackgroundColor: 'rgba(99, 102, 241, 1)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
            data: [85, 80, 90, 85, 80]
        }
    ]
})

const radarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        r: {
            angleLines: { display: true },
            suggestedMin: 0,
            suggestedMax: 100,
            ticks: { stepSize: 20 }
        }
    },
    plugins: {
        legend: { display: false }
    }
}

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

// Recent activity
const recentActivity = ref([])

// Animation and reactive data
const isLoaded = ref(false)
const rotation = ref(30)
const scale = ref(1)
const displayedTitle = ref('')
const headingText = 'Dashboard'
const typewriterIndex = ref(0)
const particles = ref([])
const router = useRouter()
const toast = useToast()

// Computed properties
const activityIcon = (type) => {
    return type === 'Submission' ? 'pi-code' : 'pi-check-circle'
}

const formatDate = (dateString) => {
    const options = { month: 'short', day: 'numeric' }
    return new Date(dateString).toLocaleDateString(undefined, options)
}

// Methods
const computeMetrics = () => {
    metrics.value.repositories = mockRepositories.length
    metrics.value.submissions = mockSubmissions.length
    metrics.value.reviews = mockReviews.length
    metrics.value.unreadNotifications = mockNotifications.filter(n => !n.read).length

    // Completion rate: % of submissions with at least one review
    const submissionsWithReviews = new Set(mockReviews.map(r => r.code_submission_id)).size
    metrics.value.completionRate = mockSubmissions.length
        ? Math.round((submissionsWithReviews / mockSubmissions.length) * 100)
        : 0

    // Average scores
    const reviewCount = mockReviews.length
    metrics.value.avgOverallScore = reviewCount
        ? Math.round(mockReviews.reduce((sum, r) => sum + r.overall_score, 0) / reviewCount )
        : 0
    metrics.value.avgComplexityScore = reviewCount
        ? Math.round(mockReviews.reduce((sum, r) => sum + r.complexity_score, 0) / reviewCount )
        : 0
    metrics.value.avgSecurityScore = reviewCount
        ? Math.round(mockReviews.reduce((sum, r) => sum + r.security_score, 0) / reviewCount )
        : 0
    metrics.value.avgMaintainabilityScore = reviewCount
        ? Math.round(mockReviews.reduce((sum, r) => sum + r.maintainability_score, 0) / reviewCount )
        : 0
    metrics.value.totalBugs = mockReviews.reduce((sum, r) => sum + r.bug_count, 0)

    // Update chart data
    radarChartData.value.datasets[0].data = [
        metrics.value.avgOverallScore,
        metrics.value.avgComplexityScore,
        metrics.value.avgSecurityScore,
        metrics.value.avgMaintainabilityScore,
        100 - (metrics.value.totalBugs * 5) // Inverse scale for bugs
    ]

    // Recent activity: Combine submissions and reviews
    recentActivity.value = [
        ...mockSubmissions.map(s => ({
            type: 'Submission',
            title: s.title,
            created_at: s.created_at,
            id: s.id,
        })),
        ...mockReviews.map(r => ({
            type: 'Review',
            title: `Review for ${mockSubmissions.find(s => s.id === r.code_submission_id)?.title || 'Submission'}`,
            created_at: r.created_at,
            id: r.code_submission_id,
        })),
    ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 5)
}

// Particles
const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4,
    }))
}

// Typewriter effect
const typeWriter = () => {
    if (typewriterIndex.value < headingText.length) {
        displayedTitle.value += headingText.charAt(typewriterIndex.value)
        typewriterIndex.value++
        setTimeout(typeWriter, 100)
    }
}

// Background animation
let animationId
const animateBackground = () => {
    rotation.value += 0.2
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1
    animationId = requestAnimationFrame(animateBackground)
}

// Lifecycle
onMounted(() => {
    generateParticles()
    setTimeout(() => {
        isLoaded.value = true
        typeWriter()
        animateBackground()
        computeMetrics()
    }, 300)
})

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId)
})
</script>

<style scoped>
html,
body {
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

html {
    scroll-behavior: smooth;
}
</style>
