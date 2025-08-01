<template>
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
            {{ subtitle }}
        </p>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
});

const isLoaded = ref(false);
const displayedTitle = ref('');
const typewriterIndex = ref(0);

const typeWriter = (text) => {
    // Reset state before starting new animation
    displayedTitle.value = '';
    typewriterIndex.value = 0;

    const animate = () => {
        if (typewriterIndex.value < text.length) {
            displayedTitle.value += text.charAt(typewriterIndex.value);
            typewriterIndex.value++;
            setTimeout(animate, 100);
        }
    };
    animate();
};

onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
        typeWriter(props.title);
    }, 300);
});

// Watch for title changes to re-run the animation
watch(
    () => props.title,
    (newTitle) => {
        isLoaded.value = false;
        displayedTitle.value = '';
        typewriterIndex.value = 0;
        setTimeout(() => {
            isLoaded.value = true;
            typeWriter(newTitle);
        }, 300);
    }
);
</script>
