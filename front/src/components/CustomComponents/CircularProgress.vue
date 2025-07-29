<!-- CircularProgress.vue -->
<template>
    <div class="relative" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg class="w-full h-full" viewBox="0 0 100 100">
            <!-- Background circle -->
            <circle
                cx="50"
                cy="50"
                :r="radius"
                fill="none"
                :stroke="secondaryColor"
                :stroke-width="strokeWidth"
                stroke-dasharray="0 0"
            />
            <!-- Progress circle -->
            <circle
                cx="50"
                cy="50"
                :r="radius"
                fill="none"
                :stroke="primaryColor"
                :stroke-width="strokeWidth"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="strokeDashoffset"
                stroke-linecap="round"
            />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    value: { type: Number, default: 0 },
    size: { type: Number, default: 100 },
    strokeWidth: { type: Number, default: 10 },
    primaryColor: { type: String, default: '#6366f1' },
    secondaryColor: { type: String, default: '#e0e7ff' }
})

const radius = computed(() => 50 - props.strokeWidth / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
const strokeDashoffset = computed(() => circumference.value - (props.value / 100) * circumference.value)
</script>
