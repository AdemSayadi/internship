<template>
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
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
    isLoaded: Boolean
})

const displayedTitle = ref('')
const showCursor = ref(true)
const headingText = 'Dashboard'
const typewriterIndex = ref(0)

const typeWriter = () => {
    if (typewriterIndex.value < headingText.length) {
        displayedTitle.value += headingText.charAt(typewriterIndex.value)
        typewriterIndex.value++
        setTimeout(typeWriter, 100)
    } else {
        // Blink cursor effect
        setInterval(() => {
            showCursor.value = !showCursor.value
        }, 500)
    }
}

watch(() => props.isLoaded, (newVal) => {
    if (newVal) {
        typeWriter()
    }
})

onMounted(() => {
    if (props.isLoaded) {
        typeWriter()
    }
})
</script>
