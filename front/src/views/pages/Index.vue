<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <!-- Animated Top Gradient Background Overlay -->
        <div
            class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl animate-pulse"
            aria-hidden="true"
            ref="topGradient"
        >
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] via-[#ff6b9d] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] transition-all duration-1000 ease-in-out"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
                :style="{ transform: `rotate(${rotation}deg) scale(${scale})` }"
            />
        </div>

        <!-- Animated Bottom Gradient Background Overlay -->
        <div
            class="absolute inset-x-0 bottom-0 -z-10 transform-gpu overflow-hidden blur-3xl"
            aria-hidden="true"
            ref="bottomGradient"
        >
            <div
                class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#9089fc] via-[#7c3aed] to-[#ff80b5] opacity-25 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem] transition-all duration-1500 ease-in-out"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
                :style="{ transform: `rotate(${-rotation}deg) scale(${scale})` }"
            />
        </div>

        <!-- Floating particles -->
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
            <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
                <div class="text-center">
                    <!-- Animated title with typing effect -->
                    <h1
                        class="text-balance text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl transition-all duration-700 ease-out"
                        :class="{ 'animate-pulse': isLoaded }"
                        ref="title"
                    >
                        {{ displayedTitle }}
                        <span class="animate-pulse">|</span>
                    </h1>

                    <!-- Animated subtitle -->
                    <p
                        class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8 transition-all duration-700 delay-300 ease-out transform"
                        :class="{ 'translate-y-0 opacity-100': isLoaded, 'translate-y-10 opacity-0': !isLoaded }"
                    >
                        Your code, your way. Use Code Guard to enhance your code quality and security with AI code reviews.
                    </p>

                    <!-- Interactive buttons with hover effects -->
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <button
                            @mouseenter="onButtonHover"
                            @mouseleave="onButtonLeave"
                            class="group relative overflow-hidden rounded-md bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 ease-out transform hover:scale-105 hover:-translate-y-1"
                            :class="{ 'animate-bounce': buttonHovered }"
                        >
                            <span class="relative z-10">Get started</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </button>

                        <a
                            href="#"
                            class="text-sm/6 font-semibold text-gray-900 hover:text-indigo-600 transition-colors duration-200 group"
                            @mouseenter="arrowHovered = true"
                            @mouseleave="arrowHovered = false"
                        >
                            Learn more
                            <span
                                aria-hidden="true"
                                class="inline-block transition-transform duration-200 group-hover:translate-x-1"
                                :class="{ 'animate-pulse': arrowHovered }"
                            >
                                →
                            </span>
                        </a>
                    </div>

                    <!-- Interactive stats/metrics -->
                    <div class="mt-16 grid grid-cols-3 gap-8 opacity-75">
                        <div
                            class="text-center cursor-pointer transition-all duration-300 hover:scale-110"
                            @mouseenter="statsHovered = 'users'"
                            @mouseleave="statsHovered = null"
                        >
                            <div class="text-2xl font-bold text-indigo-600">{{ animatedStats.users }}+</div>
                            <div class="text-sm text-gray-500">Active Users</div>
                        </div>
                        <div
                            class="text-center cursor-pointer transition-all duration-300 hover:scale-110"
                            @mouseenter="statsHovered = 'reviews'"
                            @mouseleave="statsHovered = null"
                        >
                            <div class="text-2xl font-bold text-purple-600">{{ animatedStats.reviews }}K+</div>
                            <div class="text-sm text-gray-500">Code Reviews</div>
                        </div>
                        <div
                            class="text-center cursor-pointer transition-all duration-300 hover:scale-110"
                            @mouseenter="statsHovered = 'bugs'"
                            @mouseleave="statsHovered = null"
                        >
                            <div class="text-2xl font-bold text-pink-600">{{ animatedStats.bugs }}%</div>
                            <div class="text-sm text-gray-500">Bugs Prevented</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Components with proper spacing -->
        <div class="relative z-10">
            <FeaturesWidget />
            <HowItWorks />
            <!-- Fixed footer spacing -->
            <div class="mt-0">
                <Footer />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Navbar from '../../components/CustomComponents/Navbar.vue'
import FeaturesWidget from "@/components/CustomComponents/FeaturesWidget.vue";
import HowItWorks from "@/components/CustomComponents/HowItWorks.vue";
import Footer from "../../components/CustomComponents/Footer.vue";

// Reactive data
const isLoaded = ref(false)
const rotation = ref(30)
const scale = ref(1)
const buttonHovered = ref(false)
const arrowHovered = ref(false)
const statsHovered = ref(null)
const displayedTitle = ref('')
const title = 'CodeGuard AI'
const typewriterIndex = ref(0)

// Animated stats
const animatedStats = ref({
    users: 0,
    reviews: 0,
    bugs: 0
})

const targetStats = {
    users: 10000,
    reviews: 500,
    bugs: 95
}

// Floating particles
const particles = ref([])

// Generate floating particles
const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4
    }))
}

// Typewriter effect
const typeWriter = () => {
    if (typewriterIndex.value < title.length) {
        displayedTitle.value += title.charAt(typewriterIndex.value)
        typewriterIndex.value++
        setTimeout(typeWriter, 100)
    }
}

// Animate stats
const animateStats = () => {
    const duration = 2000
    const startTime = Date.now()

    const animate = () => {
        const elapsed = Date.now() - startTime
        const progress = Math.min(elapsed / duration, 1)

        // Easing function
        const easeOutQuart = 1 - Math.pow(1 - progress, 4)

        animatedStats.value.users = Math.floor(targetStats.users * easeOutQuart)
        animatedStats.value.reviews = Math.floor(targetStats.reviews * easeOutQuart)
        animatedStats.value.bugs = Math.floor(targetStats.bugs * easeOutQuart)

        if (progress < 1) {
            requestAnimationFrame(animate)
        }
    }

    animate()
}

// Background animation
let animationId
const animateBackground = () => {
    rotation.value += 0.2
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1
    animationId = requestAnimationFrame(animateBackground)
}

// Button hover handlers
const onButtonHover = () => {
    buttonHovered.value = true
    setTimeout(() => {
        buttonHovered.value = false
    }, 600)
}

const onButtonLeave = () => {
    buttonHovered.value = false
}

// Lifecycle hooks
onMounted(() => {
    generateParticles()

    // Initial load animation
    setTimeout(() => {
        isLoaded.value = true
        typeWriter()
        animateStats()
        animateBackground()
    }, 300)
})

onUnmounted(() => {
    if (animationId) {
        cancelAnimationFrame(animationId)
    }
})
</script>

<style scoped>
html, body {
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}

/* Custom animations */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Remove any potential footer margin issues */
.relative.z-10 > div:last-child {
    margin-bottom: 0;
}
</style>
