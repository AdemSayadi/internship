<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <AnimatedBackground />
        <FloatingParticles />
        <header class="absolute inset-x-0 top-0 z-50">
            <Navbar />
        </header>
        <div class="relative isolate px-6 pt-10 lg:px-8">
            <div class="mx-auto max-w-4xl py-16 sm:py-24 lg:py-32">
                <slot />
            </div>
        </div>
        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import Navbar from '@/components/CustomComponents/Navbar.vue';
import Footer from '@/components/CustomComponents/Footer.vue';
import AnimatedBackground from '@/components/CustomComponents/AnimatedBackground.vue';
import FloatingParticles from '@/components/CustomComponents/FloatingParticles.vue';
</script>
