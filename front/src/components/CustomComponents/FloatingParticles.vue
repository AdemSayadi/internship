<template>
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
</template>

<script setup>
import { ref, onMounted } from 'vue';

const particles = ref([]);

const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4,
    }));
};

onMounted(() => {
    generateParticles();
});
</script>
