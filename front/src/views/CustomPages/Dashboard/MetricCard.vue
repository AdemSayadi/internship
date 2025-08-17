<template>
    <div
        class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6 text-center transition-all duration-300 hover:shadow-xl"
    >
        <!-- If icon is a string, render with <i> -->
        <i v-if="typeof icon === 'string'" :class="`${icon} text-2xl`"></i>

        <!-- If icon is a component or slot -->
        <component v-else :is="icon" class="mx-auto w-6 h-6" />

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
    icon: [String, Object], // <-- allow string OR component
    color: String,
    trend: String,
});
</script>
