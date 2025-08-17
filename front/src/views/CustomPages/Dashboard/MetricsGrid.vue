<template>
    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard
            title="Repositories"
            :value="metrics.repositories"
            icon="pi pi-folder"
            trend="up"
        />
        <MetricCard
            title="Code Submissions"
            :value="metrics.code_submissions"
            icon="pi pi-code"
            trend="up"
        />

        <!-- Custom SVG card styled like MetricCard -->
        <div
            class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-xl"
        >
            <svg
                class="mx-auto text-xl #747b8a"
                width="1em"
                height="1em"
                viewBox="0 0 16 16"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M7.177 3.073L9.573.677A.25.25 0 0110 .854v4.792a.25.25 0 01-.427.177L7.177 3.427a.25.25 0 010-.354zM3.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122v5.256a2.251 2.251 0 11-1.5 0V5.372A2.25 2.25 0 011.5 3.25zM11 2.5h-1V4h1a1 1 0 011 1v5.628a2.251 2.251 0 101.5 0V5A2.5 2.5 0 0011 2.5zm1 10.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM3.75 12a.75.75 0 100 1.5.75.75 0 000-1.5z"
                />
            </svg>
            <h3 class="mt-2 text-lg font-semibold text-gray-900">Pull Requests</h3>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ metrics.pull_requests }}
            </p>
            <span class="text-green-500 text-sm">↑</span>
        </div>

        <MetricCard
            title="Total Reviews"
            :value="metrics.total_reviews"
            icon="pi pi-check-circle"
            trend="up"
        />
    </div>
</template>

<script setup>
import MetricCard from '@/views/CustomPages/Dashboard/MetricCard.vue'

defineProps({
    metrics: Object
})
</script>
