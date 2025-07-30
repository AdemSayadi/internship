<template>
    <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-xl">
        <i :class="`pi ${icon} text-2xl`"></i>
        <h3 class="mt-2 text-lg font-semibold text-gray-900">{{ title }}</h3>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ value }}</p>
        <span v-if="trend === 'up'" class="text-green-500 text-sm">↑</span>
        <span v-else-if="trend === 'down'" class="text-red-500 text-sm">↓</span>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    value: [Number, String],
    icon: String,
    color: String,
    trend: String,
});
</script>
