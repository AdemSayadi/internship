<template>
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
</template>

<script setup>
import { ref } from 'vue'
import Chart from 'primevue/chart'
import CircularProgress from "@/views/CustomPages/Dashboard/CircularProgress.vue"

const metrics = ref({
    completionRate: 50,
    avgOverallScore: 87,
})

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
            data: [87, 82, 92, 87, 90] // Updated with actual values
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
</script>
