<template>
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6 h-full">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Code Quality Scores</h3>
                <Chart
                    v-if="isChartDataValid"
                    type="radar"
                    :data="radarChartData"
                    :options="radarChartOptions"
                    class="h-80"
                />
                <div v-else class="flex justify-center items-center h-80">
                    <span class="text-gray-500">No data available for chart</span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Completion</h3>
                <div class="flex justify-center">
                    <CircularProgress
                        v-if="isCompletionRateValid"
                        :value="completionRate"
                        :size="180"
                        :stroke-width="12"
                        primary-color="#7c3aed"
                        secondary-color="#e9d5ff"
                    >
                        <span class="text-2xl font-bold text-gray-900">{{ completionRate }}%</span>
                    </CircularProgress>
                    <div v-else class="flex justify-center items-center h-64">
                        <span class="text-gray-500">No completion data</span>
                    </div>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Average Score</h3>
                <div class="flex justify-center">
                    <CircularProgress
                        v-if="isAvgScoreValid"
                        :value="avgScore"
                        :size="180"
                        :stroke-width="12"
                        primary-color="#6366f1"
                        secondary-color="#e0e7ff"
                    >
                        <span class="text-2xl font-bold text-gray-900">{{ Math.round(avgScore) }}</span>
                    </CircularProgress>
                    <div v-else class="flex justify-center items-center h-64">
                        <span class="text-gray-500">No average score data</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, computed, watch } from 'vue'
import Chart from 'primevue/chart'
import CircularProgress from '@/views/CustomPages/Dashboard/CircularProgress.vue'

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({})
    },
    radarChartData: {
        type: Object,
        default: () => ({
            labels: [],
            datasets: []
        })
    }
})

// Computed properties to handle property name mismatches
const completionRate = computed(() => {
    return props.metrics?.completion_rate ?? props.metrics?.completionRate ?? 0
})

const avgScore = computed(() => {
    return props.metrics?.avg_overall_score ?? props.metrics?.avgOverallScore ?? 0
})

// Validation computed properties
const isChartDataValid = computed(() => {
    return props.radarChartData &&
        props.radarChartData.datasets &&
        props.radarChartData.datasets.length > 0 &&
        props.radarChartData.datasets[0].data &&
        props.radarChartData.datasets[0].data.some(val => val > 0)
})

const isCompletionRateValid = computed(() => {
    return completionRate.value !== undefined && completionRate.value !== null && !isNaN(completionRate.value)
})

const isAvgScoreValid = computed(() => {
    return avgScore.value !== undefined && avgScore.value !== null && !isNaN(avgScore.value)
})

// Watch for prop changes (for debugging)
watch(() => props.metrics, (newMetrics) => {
    console.log('MainContentGrid - Updated metrics:', newMetrics)
}, { deep: true })

watch(() => props.radarChartData, (newData) => {
    console.log('MainContentGrid - Updated radar data:', newData)
}, { deep: true })

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
