<template>
    <div>
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
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const rotation = ref(30)
const scale = ref(1)
const particles = ref([])

const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4,
    }))
}

let animationId
const animateBackground = () => {
    rotation.value += 0.2
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1
    animationId = requestAnimationFrame(animateBackground)
}

onMounted(() => {
    generateParticles()
    animateBackground()
})

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId)
})
</script>
