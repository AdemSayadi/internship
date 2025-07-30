<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <AnimatedBackground />

        <header class="absolute inset-x-0 top-0 z-50">
            <Navbar />
        </header>

        <div class="relative isolate px-6 pt-10 lg:px-8">
            <div class="mx-auto max-w-7xl py-16 sm:py-24 lg:py-32">
                <DashboardHeader :is-loaded="isLoaded" />
                <MetricsGrid />
                <MainContentGrid />
                <BottomRow />
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Navbar from '@/components/CustomComponents/Navbar.vue'
import Footer from '@/components/CustomComponents/Footer.vue'
import AnimatedBackground from '@/views/CustomPages/Dashboard/AnimatedBackground.vue'
import DashboardHeader from '@/views/CustomPages/Dashboard/DashboardHeader.vue'
import MetricsGrid from '@/views/CustomPages/Dashboard/MetricsGrid.vue'
import MainContentGrid from '@/views/CustomPages/Dashboard/MainContentGrid.vue'
import BottomRow from '@/views/CustomPages/Dashboard/BottomRow.vue'
import {useAuth} from "@/router/composables/useAuth";
import router from "@/router";

const isLoaded = ref(false)

const { checkAuth } = useAuth();
// Lifecycle
onMounted(() => {
    if (!checkAuth()) {
        router.push('/auth/login1');
    }
    isLoaded.value = true
})
</script>
