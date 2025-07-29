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
                        Stay updated with your code review notifications.
                    </p>
                </div>

                <DataTable
                    :value="notifications"
                    class="mt-8"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                >
                    <Column field="message" header="Message" sortable />
                    <Column field="read" header="Status" sortable>
                        <template #body="slotProps">
              <span :class="slotProps.data.read ? 'text-green-600' : 'text-red-600'">
                {{ slotProps.data.read ? 'Read' : 'Unread' }}
              </span>
                        </template>
                    </Column>
                    <Column field="created_at" header="Date" sortable>
                        <template #body="slotProps">
                            {{ new Date(slotProps.data.created_at).toLocaleDateString() }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="slotProps">
                            <Button
                                v-if="!slotProps.data.read"
                                label="Mark as Read"
                                icon="pi pi-check"
                                class="p-button-text"
                                @click="markAsRead(slotProps.data.id)"
                            />
                            <Button
                                label="View Review"
                                icon="pi pi-eye"
                                class="p-button-text"
                                @click="$router.push(`/reviews/${slotProps.data.review.code_submission_id}`)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Navbar from '@/components/CustomComponents/Navbar.vue';
import Footer from '@/components/CustomComponents/Footer.vue';
import { useAuth } from '@/router/composables/useAuth';

// Animation and reactive data
const isLoaded = ref(false);
const rotation = ref(30);
const scale = ref(1);
const displayedTitle = ref('');
const headingText = 'Notifications';
const typewriterIndex = ref(0);
const particles = ref([]);
const notifications = ref([
    { id: 1, message: 'New review for Feature X', read: false, created_at: '2025-07-02', review: { code_submission_id: 1 } },
    { id: 2, message: 'Review updated for Bug Fix', read: true, created_at: '2025-07-03', review: { code_submission_id: 2 } },
]);
const router = useRouter();
const toast = useToast();

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

// Mark notification as read
const markAsRead = (id) => {
    const notification = notifications.value.find((n) => n.id === id);
    if (notification) {
        notification.read = true;
        toast.add({ severity: 'success', summary: 'Success', detail: 'Notification marked as read', life: 3000 });
    }
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
