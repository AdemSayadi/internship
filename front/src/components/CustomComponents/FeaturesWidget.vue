<template>
    <div id="features" class="py-6 px-6 lg:px-20 mx-0 lg:mx-20 mb-12 relative overflow-hidden">
        <div class="grid grid-cols-12 gap-4 justify-center relative z-10">
            <!-- Hero Testimonial with enhanced animations -->
            <div
                class="col-span-12 mb-20 p-2 md:p-20 rounded-[20px] transform transition-all duration-700 hover:scale-[1.02] hover:shadow-2xl cursor-pointer group"
                :class="{ 'animate-fade-in-up': isVisible }"
                style="background: linear-gradient(0deg, rgba(255,255,255,0.6), rgba(255,255,255,0.6)), radial-gradient(77.36% 256.97% at 77.36% 57.52%, #efe1af 0%, #c3dcfa 100%)"
                @mouseenter="heroHovered = true"
                @mouseleave="heroHovered = false"
                ref="heroSection"
            >
                <!-- Animated sparkles around hero -->
                <div
                    v-for="sparkle in sparkles"
                    :key="sparkle.id"
                    class="absolute w-2 h-2 bg-yellow-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                    :style="{
                        left: sparkle.x + '%',
                        top: sparkle.y + '%',
                        animationDelay: sparkle.delay + 's'
                    }"
                    :class="`animate-ping`"
                />

                <div class="flex flex-col justify-center items-center text-center px-4 py-4 md:py-0 relative">
                    <h1 class="text-gray-900 text-3xl font-semibold flex items-center gap-2 transform transition-all duration-500 group-hover:scale-105">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            fill="none"
                            class="w-6 h-6 mt-1 transition-all duration-300 group-hover:text-indigo-600 group-hover:rotate-12"
                            aria-hidden="true"
                            :class="{ 'animate-pulse': heroHovered }"
                        >
                            <title>CodeGuard AI Logo</title>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                        <span class="bg-gradient-to-r from-gray-900 to-indigo-900 bg-clip-text text-transparent">
                            CodeGuard AI
                        </span>
                    </h1>
                    <span class="text-gray-600 text-2xl mt-2 transition-all duration-300 group-hover:text-indigo-600">
                        AI-Powered Code Intelligence
                    </span>
                    <p class="text-gray-900 text-2xl mt-6 max-w-[800px] transition-all duration-500 group-hover:text-gray-800">
                        <span class="relative">
                            "CodeGuard AI has transformed how we ship code — instant, insightful reviews right from our commits.
                            The platform helps our team write cleaner, safer, and more maintainable code at scale."
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 transform translate-x-full group-hover:translate-x-[-100%] transition-transform duration-1000"></div>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Enhanced Section Header -->
            <div
                class="col-span-12 text-center mt-10 mb-6 transform transition-all duration-700"
                :class="{ 'animate-fade-in-up': isVisible }"
                style="animation-delay: 0.2s"
            >
                <h2 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl bg-gradient-to-r from-gray-900 via-indigo-900 to-purple-900 bg-clip-text text-transparent">
                    Platform Features
                </h2>
                <p class="text-muted-color text-2xl opacity-80">
                    Powerful tools to supercharge your code quality workflow
                </p>

                <!-- Animated underline -->
                <div class="flex justify-center mt-4">
                    <div class="w-24 h-1 bg-gradient-to-r from-indigo-400 to-purple-400 rounded-full transform transition-all duration-700 hover:w-32"></div>
                </div>
            </div>

            <!-- Enhanced Feature Cards -->
            <div
                v-for="(feature, index) in features"
                :key="feature.id"
                class="col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 transform transition-all duration-700"
                :class="{ 'animate-fade-in-up': isVisible }"
                :style="{ animationDelay: (0.4 + index * 0.2) + 's' }"
                @mouseenter="() => featureHovered = feature.id"
                @mouseleave="() => featureHovered = null"
                ref="featureCards"
            >
                <div
                    class="min-h-40 p-0.5 rounded-[10px] relative overflow-hidden group cursor-pointer transform transition-all duration-300 hover:scale-105 hover:-translate-y-2"
                    :class="feature.gradientClass"
                    :style="{
                        boxShadow: featureHovered === feature.id ? '0 20px 40px rgba(0,0,0,0.1)' : '0 4px 6px rgba(0,0,0,0.05)'
                    }"
                >
                    <!-- Animated border glow -->
                    <div
                        class="absolute inset-0 rounded-[10px] opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        :class="feature.glowClass"
                    ></div>

                    <!-- Floating particles around card -->
                    <div
                        v-for="particle in cardParticles"
                        :key="`${feature.id}-${particle.id}`"
                        class="absolute w-1 h-1 bg-white rounded-full opacity-0 group-hover:opacity-60 transition-opacity duration-300"
                        :style="{
                            left: particle.x + '%',
                            top: particle.y + '%',
                            animationDelay: particle.delay + 's'
                        }"
                        :class="`animate-ping`"
                    />

                    <div class="p-6 bg-white dark:bg-surface-900 min-h-full rounded-md relative z-10 transform transition-all duration-300 group-hover:bg-opacity-95">
                        <!-- Enhanced icon with animations -->
                        <div
                            class="flex items-center justify-center mb-4 w-14 h-14 rounded-[10px] transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-6"
                            :class="feature.iconBgClass"
                        >
                            <i
                                class="text-2xl transition-all duration-300 group-hover:scale-125"
                                :class="[feature.iconClass, feature.iconColorClass, { 'animate-bounce': featureHovered === feature.id }]"
                                aria-hidden="true"
                            ></i>
                        </div>

                        <!-- Enhanced title with gradient text -->
                        <h3 class="mb-2 font-semibold text-xl transition-all duration-300 group-hover:text-indigo-600">
                            <span class="bg-gradient-to-r from-gray-900 to-indigo-600 bg-clip-text text-transparent group-hover:from-indigo-600 group-hover:to-purple-600">
                                {{ feature.title }}
                            </span>
                        </h3>

                        <!-- Enhanced description with typing effect on hover -->
                        <p class="text-surface-600 dark:text-surface-200 text-base transition-all duration-300 group-hover:text-gray-700">
                            {{ feature.description }}
                        </p>

                        <!-- Interactive progress bar -->
                        <div class="mt-4 overflow-hidden">
                            <div
                                class="h-1 bg-gradient-to-r rounded-full transition-all duration-700 ease-out"
                                :class="feature.progressClass"
                                :style="{
                                    width: featureHovered === feature.id ? '100%' : '0%',
                                    opacity: featureHovered === feature.id ? 1 : 0
                                }"
                            ></div>
                        </div>

                        <!-- Hover indicator -->
                        <div
                            class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0"
                        >
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

// Reactive state
const isVisible = ref(false)
const heroHovered = ref(false)
const featureHovered = ref<number | null>(null)
const heroSection = ref(null)
const featureCards = ref([])

// Animation data
const floatingElements = ref([])
const sparkles = ref([])
const cardParticles = ref([])

// Features data with enhanced styling
const features = ref([
    {
        id: 1,
        title: 'AI Code Review',
        description: 'Get instant, intelligent suggestions on bugs, performance, and security issues.',
        iconClass: 'pi pi-fw pi-code',
        iconColorClass: 'text-yellow-700',
        iconBgClass: 'bg-yellow-200 group-hover:bg-yellow-300',
        gradientClass: 'bg-gradient-to-br from-yellow-100/40 to-slate-200/30',
        glowClass: 'bg-gradient-to-br from-yellow-300/20 to-slate-300/10',
        progressClass: 'from-yellow-400 to-orange-400'
    },
    {
        id: 2,
        title: 'Git Integration',
        description: 'Seamlessly connects with GitHub and GitLab to trigger reviews on every commit.',
        iconClass: 'pi pi-fw pi-github',
        iconColorClass: 'text-cyan-700',
        iconBgClass: 'bg-cyan-200 group-hover:bg-cyan-300',
        gradientClass: 'bg-gradient-to-br from-cyan-100/40 to-purple-100/30',
        glowClass: 'bg-gradient-to-br from-cyan-300/20 to-purple-300/10',
        progressClass: 'from-cyan-400 to-blue-400'
    },
    {
        id: 3,
        title: 'Actionable Insights',
        description: 'Visualize your code quality metrics and track improvements over time.',
        iconClass: 'pi pi-fw pi-chart-line',
        iconColorClass: 'text-indigo-700',
        iconBgClass: 'bg-indigo-200 group-hover:bg-indigo-300',
        gradientClass: 'bg-gradient-to-br from-indigo-100/40 to-pink-100/30',
        glowClass: 'bg-gradient-to-br from-indigo-300/20 to-pink-300/10',
        progressClass: 'from-indigo-400 to-purple-400'
    }
])

// Generate floating elements
const generateFloatingElements = () => {
    floatingElements.value = Array.from({ length: 8 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        rotation: Math.random() * 360,
        delay: Math.random() * 5,
        duration: 3 + Math.random() * 4
    }))
}

// Generate sparkles
const generateSparkles = () => {
    sparkles.value = Array.from({ length: 12 }, (_, i) => ({
        id: i,
        x: 10 + Math.random() * 80,
        y: 10 + Math.random() * 80,
        delay: Math.random() * 3
    }))
}

// Generate card particles
const generateCardParticles = () => {
    cardParticles.value = Array.from({ length: 6 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 2
    }))
}

// Intersection Observer for scroll animations
const observeElements = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    isVisible.value = true
                }
            })
        },
        { threshold: 0.1 }
    )

    if (heroSection.value) {
        observer.observe(heroSection.value)
    }

    return observer
}

// Lifecycle
onMounted(() => {
    generateFloatingElements()
    generateSparkles()
    generateCardParticles()

    // Delay initial animation
    setTimeout(() => {
        const observer = observeElements()

        // Cleanup observer on unmount
        onUnmounted(() => {
            observer.disconnect()
        })
    }, 100)
})
</script>

<style scoped>
/* Custom animations */
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}

/* Smooth scrolling and performance */
* {
    transform-origin: center;
}

/* Custom gradient text animation */
@keyframes gradient-x {
    0%, 100% {
        background-size: 200% 200%;
        background-position: left center;
    }
    50% {
        background-size: 200% 200%;
        background-position: right center;
    }
}

.animate-gradient-x {
    animation: gradient-x 3s ease infinite;
}

/* Enhanced hover effects */
.group:hover .animate-shimmer {
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

/* Smooth transitions for all interactive elements */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Performance optimizations */
.transform {
    transform: translateZ(0);
    backface-visibility: hidden;
}
</style>
