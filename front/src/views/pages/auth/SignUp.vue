<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <!-- Animated Top Gradient Background Overlay -->
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl animate-pulse" aria-hidden="true">
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] via-[#ff6b9d] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] transition-all duration-1000 ease-in-out"
                :style="{ transform: `rotate(${rotation}deg) scale(${scale})` }"
            />
        </div>
        <!-- Animated Bottom Gradient Background Overlay -->
        <div class="absolute inset-x-0 bottom-0 -z-10 transform-gpu overflow-hidden blur-3xl" aria-hidden="true">
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
            <div class="mx-auto max-w-md py-32 sm:py-48 lg:py-56">
                <div class="bg-white/80 rounded-xl shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-center mb-6">Sign Up</h2>
                    <form @submit.prevent="onSubmit">
                        <input v-model="form.name" type="text" placeholder="Name" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <input v-model="form.email" type="email" placeholder="Email" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <input v-model="form.password" type="password" placeholder="Password" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <input v-model="form.password_confirmation" type="password" placeholder="Confirm Password" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <button class="w-full mt-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2 rounded-md font-semibold hover:scale-105 transition">Sign Up</button>
                    </form>
                    <div class="mt-6 text-center">
                        <span class="text-gray-500">or</span>
                        <button @click="goToGitHub" class="ml-2 text-indigo-600 font-semibold hover:underline">Sign up with GitHub</button>
                    </div>
                    <div class="mt-4 text-center text-sm">
                        Already have an account?
                        <router-link to="/auth/login" class="text-indigo-600 font-semibold hover:underline">Login</router-link>
                    </div>
                </div>
            </div>
        </div>
        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import Navbar from '@/components/CustomComponents/Navbar.vue'
import Footer from '@/components/CustomComponents/Footer.vue'

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const onSubmit = () => {
    // TODO: Implement registration logic (API call)
    alert('Sign up submitted: ' + JSON.stringify(form.value))
}

const goToGitHub = () => {
    // TODO: Implement GitHub OAuth redirect
    alert('Redirect to GitHub OAuth')
}

// Animation logic (copied from Index.vue)
const rotation = ref(30)
const scale = ref(1)
const particles = ref([])
let animationId

const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4
    }))
}
const animateBackground = () => {
    rotation.value += 0.2
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1
    animationId = requestAnimationFrame(animateBackground)
}
onMounted(() => {
    generateParticles()
    animateBackground()
})
onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId)
})
</script>

<style scoped>
/* Copy styles from Index.vue for consistency */
html, body { height: 100%; margin: 0; overflow-x: hidden; }
@keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
.animate-float { animation: float 3s ease-in-out infinite; }
html { scroll-behavior: smooth; }
.relative.z-10 > div:last-child { margin-bottom: 0; }
</style>