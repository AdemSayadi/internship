<template>
    <div id="how-it-works" class="py-12 px-6 lg:px-20 mx-0 lg:mx-20 mb-25 relative overflow-hidden">
        <!-- Animated background pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none">
            <div class="absolute inset-0"></div>
        </div>

        <!-- Floating code symbols -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                v-for="symbol in codeSymbols"
                :key="symbol.id"
                class="absolute text-2xl opacity-10 animate-float"
                :style="{
                    left: symbol.x + '%',
                    top: symbol.y + '%',
                    animationDelay: symbol.delay + 's',
                    animationDuration: symbol.duration + 's'
                }"
            >
                {{ symbol.char }}
            </div>
        </div>

        <div class="relative z-10">
            <!-- Enhanced Header -->
            <div
                class="text-center mb-16 transform transition-all duration-700"
                :class="{ 'animate-fade-in-up': isVisible }"
                ref="headerSection"
            >
                <h2 class="font-normal text-4xl mb-4 bg-gradient-to-r from-gray-900 via-indigo-900 to-purple-900 bg-clip-text text-transparent">
                    How CodeGuard AI Works
                </h2>
                <p class="text-muted-color text-xl mb-8">
                    Streamline your code quality with our seamless AI-powered workflow
                </p>

                <!-- Interactive workflow progress bar -->
                <div class="max-w-4xl mx-auto">
                    <div class="relative">
                        <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 rounded-full transform -translate-y-1/2"></div>
                        <div
                            class="absolute top-1/2 left-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transform -translate-y-1/2 transition-all duration-2000 ease-out"
                            :style="{ width: progressWidth + '%' }"
                        ></div>
                        <div class="relative flex justify-between">
                            <div
                                v-for="(step, index) in steps"
                                :key="step.id"
                                class="flex flex-col items-center cursor-pointer group"
                                @click="activeStep = step.id"
                                @mouseenter="hoveredStep = step.id"
                                @mouseleave="hoveredStep = null"
                            >
                                <div
                                    class="w-6 h-6 rounded-full border-4 border-white transition-all duration-300 relative z-10"
                                    :class="[
                                        activeStep >= step.id || hoveredStep === step.id
                                            ? 'bg-gradient-to-r from-indigo-500 to-purple-500 scale-125 shadow-lg'
                                            : 'bg-gray-300 group-hover:bg-indigo-300'
                                    ]"
                                >
                                    <div
                                        v-if="activeStep >= step.id"
                                        class="absolute inset-0 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 animate-ping opacity-20"
                                    ></div>
                                </div>
                                <span
                                    class="text-xs mt-2 text-gray-600 transition-all duration-300 group-hover:text-indigo-600"
                                    :class="{ 'text-indigo-600 font-semibold': activeStep >= step.id }"
                                >
                                    Step {{ step.id }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Step Cards -->
            <div class="grid grid-cols-12 gap-6 relative">
                <!-- Animated connection lines -->
                <svg
                    class="absolute inset-0 w-full h-full pointer-events-none opacity-20"
                    style="z-index: 1"
                >
                    <defs>
                        <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#6366f1;stop-opacity:0" />
                            <stop offset="50%" style="stop-color:#8b5cf6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#ec4899;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path
                        v-for="(line, index) in connectionLines"
                        :key="index"
                        :d="line.path"
                        stroke="url(#lineGradient)"
                        stroke-width="2"
                        fill="none"
                        class="animate-draw-line"
                        :style="{ animationDelay: (index * 0.5) + 's' }"
                    />
                </svg>

                <div
                    v-for="(step, index) in steps"
                    :key="step.id"
                    class="col-span-12 md:col-span-6 relative z-10 transform transition-all duration-700"
                    :class="{ 'animate-fade-in-up': isVisible }"
                    :style="{ animationDelay: (0.3 + index * 0.15) + 's' }"
                    @mouseenter="activeStep = step.id"
                    ref="stepCards"
                >
                    <div
                        class="p-6 bg-white dark:bg-surface-900 rounded-lg shadow-md relative overflow-hidden group cursor-pointer transform transition-all duration-300 hover:scale-105 hover:-translate-y-2"
                        :class="{
                            'ring-2 ring-indigo-400 shadow-xl': activeStep === step.id,
                            'hover:shadow-2xl': activeStep !== step.id
                        }"
                        :style="{
                            boxShadow: activeStep === step.id
                                ? '0 25px 50px rgba(99, 102, 241, 0.2)'
                                : hoveredStep === step.id
                                    ? '0 20px 40px rgba(0,0,0,0.1)'
                                    : '0 4px 6px rgba(0,0,0,0.05)'
                        }"
                    >
                        <!-- Animated background gradient on hover -->
                        <div
                            class="absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity duration-300"
                            :class="step.bgGradient"
                        ></div>

                        <!-- Particle effects around active card -->
                        <div
                            v-if="activeStep === step.id"
                            v-for="particle in stepParticles"
                            :key="`${step.id}-${particle.id}`"
                            class="absolute w-1.5 h-1.5 rounded-full opacity-60"
                            :class="step.particleColor"
                            :style="{
                                left: particle.x + '%',
                                top: particle.y + '%',
                                animationDelay: particle.delay + 's'
                            }"
                            :class-list="`animate-ping`"
                        />

                        <!-- Step header with enhanced animations -->
                        <div class="flex items-center mb-4 relative z-10">
                            <div
                                class="flex items-center justify-center w-12 h-12 rounded-full font-semibold text-lg mr-4 relative overflow-hidden transition-all duration-300 group-hover:scale-110"
                                :class="[step.iconBg, step.iconColor]"
                            >
                                <!-- Animated background -->
                                <div
                                    class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                                    :class="step.iconHoverBg"
                                ></div>
                                <span
                                    class="relative z-10 transition-all duration-300"
                                    :class="{ 'animate-bounce': activeStep === step.id }"
                                >
                                    {{ step.id }}
                                </span>

                                <!-- Progress ring animation -->
                                <svg
                                    v-if="activeStep >= step.id"
                                    class="absolute inset-0 w-full h-full transform -rotate-90"
                                    viewBox="0 0 48 48"
                                >
                                    <circle
                                        cx="24"
                                        cy="24"
                                        r="20"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        fill="none"
                                        class="opacity-30"
                                    />
                                    <circle
                                        cx="24"
                                        cy="24"
                                        r="20"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        fill="none"
                                        class="animate-draw-circle"
                                        stroke-dasharray="125.66"
                                        stroke-dashoffset="0"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="font-semibold text-xl transition-all duration-300 group-hover:text-indigo-600 relative"
                                :class="step.titleColor"
                            >
                                <span class="bg-gradient-to-r from-gray-900 to-indigo-600 bg-clip-text text-transparent group-hover:from-indigo-600 group-hover:to-purple-600">
                                    {{ step.title }}
                                </span>
                                <!-- Animated underline -->
                                <div
                                    class="absolute bottom-0 left-0 h-0.5 bg-gradient-to-r from-indigo-400 to-purple-400 transition-all duration-300 group-hover:w-full"
                                    :style="{ width: activeStep === step.id ? '100%' : '0%' }"
                                ></div>
                            </h3>
                        </div>

                        <!-- Enhanced description with typing effect -->
                        <p
                            class="text-surface-600 dark:text-surface-200 text-base relative z-10 transition-all duration-300 group-hover:text-gray-700"
                            :class="{ 'animate-pulse': activeStep === step.id }"
                        >
                            {{ step.description }}
                        </p>

                        <!-- Interactive elements -->
                        <div class="mt-4 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                            <!-- Tech stack indicators -->
                            <div class="flex gap-2">
                                <div
                                    v-for="tech in step.techStack"
                                    :key="tech"
                                    class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 transition-all duration-200 hover:bg-indigo-100 hover:text-indigo-600"
                                >
                                    {{ tech }}
                                </div>
                            </div>

                            <!-- Status indicator -->
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-2 h-2 rounded-full transition-all duration-300"
                                    :class="activeStep >= step.id ? 'bg-green-400 animate-pulse' : 'bg-gray-300'"
                                ></div>
                                <span class="text-xs text-gray-500">
                                    {{ activeStep >= step.id ? 'Complete' : 'Pending' }}
                                </span>
                            </div>
                        </div>

                        <!-- Hover glow effect -->
                        <div
                            class="absolute inset-0 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                            :class="step.glowEffect"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'

// Reactive state
const isVisible = ref(false)
const activeStep = ref(1)
const hoveredStep = ref<number | null>(null)
const isDemoRunning = ref(false)
const headerSection = ref(null)
const stepCards = ref([])

// Animation data
const codeSymbols = ref([])
const stepParticles = ref([])
const connectionLines = ref([])

// Steps data with enhanced styling
const steps = ref([
    {
        id: 1,
        title: 'User Onboarding & Authentication',
        description: 'Visit the CodeGuard AI platform, register or log in using Laravel Sanctum + Vue Auth, and access your personalized dashboard with a secure JWT token for API authentication.',
        iconBg: 'bg-indigo-200',
        iconColor: 'text-indigo-700',
        iconHoverBg: 'bg-indigo-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-indigo-50 to-purple-50',
        glowEffect: 'shadow-indigo-200/50',
        particleColor: 'bg-indigo-400',
        techStack: ['Laravel', 'Sanctum', 'JWT']
    },
    {
        id: 2,
        title: 'Repository Setup or Manual Upload',
        description: 'Connect a GitHub/GitLab repository via OAuth and set up webhooks for commit events, or manually upload code via drag-and-drop or the code editor with auto-detected language support.',
        iconBg: 'bg-cyan-200',
        iconColor: 'text-cyan-700',
        iconHoverBg: 'bg-cyan-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-cyan-50 to-blue-50',
        glowEffect: 'shadow-cyan-200/50',
        particleColor: 'bg-cyan-400',
        techStack: ['OAuth', 'Webhooks', 'Git']
    },
    {
        id: 3,
        title: 'Code Submission Processing',
        description: 'The backend queues a ProcessCodeReviewJob or ProcessCommitJob via Redis, pulling relevant code files or diff data for analysis.',
        iconBg: 'bg-yellow-200',
        iconColor: 'text-yellow-700',
        iconHoverBg: 'bg-yellow-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-yellow-50 to-orange-50',
        glowEffect: 'shadow-yellow-200/50',
        particleColor: 'bg-yellow-400',
        techStack: ['Redis', 'Queue', 'Jobs']
    },
    {
        id: 4,
        title: 'AI Review Initiation',
        description: 'The AIAnalysisService constructs a language-specific prompt and sends it to OpenAI or Claude via API for comprehensive code analysis.',
        iconBg: 'bg-pink-200',
        iconColor: 'text-pink-700',
        iconHoverBg: 'bg-pink-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-pink-50 to-rose-50',
        glowEffect: 'shadow-pink-200/50',
        particleColor: 'bg-pink-400',
        techStack: ['OpenAI', 'Claude', 'API']
    },
    {
        id: 5,
        title: 'AI Response Processing',
        description: 'The JSON response, including bug reports, security concerns, code improvements, and scores, is parsed, stored in the database, and marked as completed.',
        iconBg: 'bg-purple-200',
        iconColor: 'text-purple-700',
        iconHoverBg: 'bg-purple-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-purple-50 to-violet-50',
        glowEffect: 'shadow-purple-200/50',
        particleColor: 'bg-purple-400',
        techStack: ['JSON', 'Database', 'Parser']
    },
    {
        id: 6,
        title: 'User Review Interaction',
        description: 'Receive real-time notifications via Laravel broadcasting, view line-by-line feedback, bug severity, score breakdowns, and AI-generated summaries on the Review Results Dashboard.',
        iconBg: 'bg-blue-200',
        iconColor: 'text-blue-700',
        iconHoverBg: 'bg-blue-300',
        titleColor: 'text-surface-900 dark:text-surface-0',
        bgGradient: 'bg-gradient-to-br from-blue-50 to-indigo-50',
        glowEffect: 'shadow-blue-200/50',
        particleColor: 'bg-blue-400',
        techStack: ['Broadcasting', 'WebSocket', 'Dashboard']
    }
])

// Computed progress width
const progressWidth = computed(() => {
    return (activeStep.value / steps.value.length) * 100
})

// Generate floating code symbols
const generateCodeSymbols = () => {
    const symbols = ['{ }', '< />', '[ ]', '( )', '=>', '&&', '||', '===', '!==', '++', '--', '/*', '*/', '//', '::']
    codeSymbols.value = Array.from({ length: 15 }, (_, i) => ({
        id: i,
        char: symbols[Math.floor(Math.random() * symbols.length)],
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 5,
        duration: 4 + Math.random() * 6
    }))
}

// Generate step particles
const generateStepParticles = () => {
    stepParticles.value = Array.from({ length: 8 }, (_, i) => ({
        id: i,
        x: 10 + Math.random() * 80,
        y: 10 + Math.random() * 80,
        delay: Math.random() * 2
    }))
}

// Generate connection lines (simplified for demo)
const generateConnectionLines = () => {
    connectionLines.value = [
        { path: "M 50 100 Q 200 50 350 100" },
        { path: "M 350 200 Q 500 150 650 200" },
        { path: "M 50 300 Q 200 250 350 300" }
    ]
}

// Intersection Observer for scroll animations
const observeElements = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    isVisible.value = true
                    // Start auto-progression after a delay
                    setTimeout(() => {
                        startAutoProgression()
                    }, 1000)
                }
            })
        },
        { threshold: 0.1 }
    )

    if (headerSection.value) {
        observer.observe(headerSection.value)
    }

    return observer
}

// Auto progression through steps
const startAutoProgression = () => {
    let currentStep = 1
    const interval = setInterval(() => {
        if (currentStep <= steps.value.length) {
            activeStep.value = currentStep
            currentStep++
        } else {
            clearInterval(interval)
        }
    }, 800)
}

// Lifecycle
onMounted(() => {
    generateCodeSymbols()
    generateStepParticles()
    generateConnectionLines()

    setTimeout(() => {
        const observer = observeElements()

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

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    33% {
        transform: translateY(-10px) rotate(5deg);
    }
    66% {
        transform: translateY(5px) rotate(-3deg);
    }
}

@keyframes draw-line {
    0% {
        stroke-dasharray: 0 1000;
    }
    100% {
        stroke-dasharray: 1000 0;
    }
}

@keyframes draw-circle {
    0% {
        stroke-dashoffset: 125.66;
    }
    100% {
        stroke-dashoffset: 0;
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-draw-line {
    animation: draw-line 2s ease-in-out forwards;
}

.animate-draw-circle {
    animation: draw-circle 1s ease-in-out forwards;
}

/* Enhanced transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Performance optimizations */
.transform {
    transform: translateZ(0);
    backface-visibility: hidden;
}

/* Custom gradients and effects */
.bg-gradient-to-br {
    background-attachment: fixed;
}

/* Smooth hover transitions */
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
</style>
