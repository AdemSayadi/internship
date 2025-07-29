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
            <div class="mx-auto max-w-4xl py-16 sm:py-24 lg:py-32">
                <div class="text-center">
                    <h1
                        class="text-balance text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl transition-all duration-700 ease-out"
                        :class="{ 'animate-pulse': isLoaded }"
                        ref="title"
                    >
                        {{ displayedTitle }}<span class="animate-pulse">|</span>
                    </h1>
                    <p
                        class="mt-6 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8 transition-all duration-700 delay-300 ease-out transform"
                        :class="{ 'translate-y-0 opacity-100': isLoaded, 'translate-y-10 opacity-0': !isLoaded }"
                    >
                        AI-powered reviews for {{ submission?.title || 'Submission' }}.
                    </p>
                </div>

                <div v-if="submission" class="mt-10">
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <h2 class="text-2xl font-semibold text-gray-900">{{ submission.title }}</h2>
                        <p class="mt-2 text-gray-600">Language: {{ submission.language }}</p>
                        <p class="mt-2 text-gray-600">Submitted: {{ new Date(submission.created_at).toLocaleDateString() }}</p>
                        <pre class="mt-4 bg-gray-100 p-4 rounded-md overflow-x-auto">{{ submission.code_content }}</pre>
                    </div>

                    <DataTable
                        :value="submission.reviews"
                        class="mt-8"
                        :paginator="true"
                        :rows="10"
                        :rowsPerPageOptions="[5, 10, 20]"
                    >
                        <Column field="status" header="Status" sortable />
                        <Column field="overall_score" header="Overall Score" sortable />
                        <Column field="complexity_score" header="Complexity" sortable />
                        <Column field="security_score" header="Security" sortable />
                        <Column field="maintainability_score" header="Maintainability" sortable />
                        <Column field="bug_count" header="Bugs" sortable />
                        <Column field="ai_summary" header="AI Summary">
                            <template #body="slotProps">
                                {{ slotProps.data.ai_summary || 'N/A' }}
                            </template>
                        </Column>
                        <Column field="suggestions" header="Suggestions">
                            <template #body="slotProps">
                                <ul v-if="slotProps.data.suggestions" class="list-disc pl-5">
                                    <li v-for="(suggestion, index) in slotProps.data.suggestions" :key="index">
                                        {{ suggestion }}
                                    </li>
                                </ul>
                                <span v-else>N/A</span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Navbar from '@/components/CustomComponents/Navbar.vue';
import Footer from '@/components/CustomComponents/Footer.vue';
import {useAuth} from "@/router/composables/useAuth";

// Animation and reactive data
const isLoaded = ref(false);
const rotation = ref(30);
const scale = ref(1);
const displayedTitle = ref('');
const headingText = 'Code Reviews';
const typewriterIndex = ref(0);
const particles = ref([]);
const submission = ref(null);
const router = useRouter();
const route = useRoute();
const toast = useToast();

// Mock data
const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
];
const mockReviews = [
    {
        id: 1,
        code_submission_id: 1,
        status: 'completed',
        overall_score: 85,
        complexity_score: 80,
        security_score: 90,
        maintainability_score: 85,
        bug_count: 2,
        ai_summary: 'Good code structure, but consider reducing complexity in loops.',
        suggestions: ['Use array methods instead of for loops', 'Add input validation'],
        created_at: '2025-07-02',
    },
];

// Particles
const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4,
    }));
};

// Typewriter effect
const typeWriter = () => {
    if (typewriterIndex.value < headingText.length) {
        displayedTitle.value += headingText.charAt(typewriterIndex.value);
        typewriterIndex.value++;
        setTimeout(typeWriter, 100);
    }
};

// Background animation
let animationId;
const animateBackground = () => {
    rotation.value += 0.2;
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1;
    animationId = requestAnimationFrame(animateBackground);
};

// Fetch submission
const fetchSubmission = () => {
    const subId = parseInt(route.params.submissionId);
    submission.value = mockSubmissions.find((sub) => sub.id === subId) || null;
    if (!submission.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Submission not found', life: 3000 });
        // router.push('/repositories');
        return;
    }
    submission.value.reviews = mockReviews.filter((review) => review.code_submission_id === subId);
};

const { checkAuth } = useAuth();
// Lifecycle
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
    generateParticles();
    setTimeout(() => {
        isLoaded.value = true;
        typeWriter();
        animateBackground();
        fetchSubmission();
    }, 300);
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
});
</script>

<style scoped>
html,
body {
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}
.animate-float {
    animation: float 3s ease-in-out infinite;
}
html {
    scroll-behavior: smooth;
}
</style>
