<template>
    <Button
        class="group relative inline-flex items-center justify-center min-w-[160px] gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-md shadow-lg hover:shadow-xl transition-all duration-300 ease-out transform hover:scale-105"
        :class="{ 'animate-bounce': buttonHovered }"
        @mouseenter="onButtonHover"
        @mouseleave="onButtonLeave"
        v-bind="$attrs"
    >
        <i :class="`pi pi-${icon} text-white relative z-10`"></i>
        <span class="relative z-10">{{ label }}</span>
        <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    </Button>
</template>

<script setup>
import { ref } from 'vue';
import Button from 'primevue/button';

defineProps({
    label: { type: String, required: true },
    icon: { type: String, required: true },
});

const buttonHovered = ref(false);

const onButtonHover = () => {
    buttonHovered.value = true;
    setTimeout(() => (buttonHovered.value = false), 600);
};

const onButtonLeave = () => (buttonHovered.value = false);
</script>
