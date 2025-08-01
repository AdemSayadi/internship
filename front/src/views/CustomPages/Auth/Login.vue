<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <!-- Animated Top Gradient -->
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl animate-pulse" aria-hidden="true">
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] via-[#ff6b9d] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] transition-all duration-1000 ease-in-out"
                :style="{ transform: `rotate(${rotation}deg) scale(${scale})` }"
            />
        </div>

        <!-- Animated Bottom Gradient -->
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
                    <!-- Animated Title -->
                    <h2
                        class="text-3xl font-bold text-center mb-2 transition-all duration-700 ease-out"
                        :class="{ 'animate-pulse': isLoaded }"
                        ref="titleEl"
                    >
                        {{ displayedTitle }}<span class="animate-pulse">|</span>
                    </h2>
                    <h3 class="text-xl text-center mb-6 text-gray-600">
                        {{ showSetPassword ? 'Set Password' : 'Login' }}
                    </h3>

                    <div class="mb-5 text-center">
                        <div v-if="error" class="text-red-600 text-center mb-2">{{ error }}</div>
                        <div v-if="success" class="text-green-600 text-center mb-2">{{ success }}</div>
                        <button v-if="loading" disabled class="w-full mt-2 bg-gray-400 text-white py-2 rounded-md font-semibold">Loading...</button>
                    </div>

                    <!-- Regular Login Form -->
                    <form v-if="!showSetPassword" @submit.prevent="onSubmit">
                        <input v-model="form.email" type="email" placeholder="Email" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <input v-model="form.password" type="password" placeholder="Password" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <button class="w-full mt-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2 rounded-md font-semibold hover:scale-105 transition">Login</button>
                    </form>

                    <!-- Set Password Form -->
                    <form v-else @submit.prevent="onSetPassword">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                            <p class="text-sm text-blue-800">
                                Your account ({{ githubUserEmail }}) was created with GitHub. Set a password to also login with email/password.
                            </p>
                        </div>
                        <input v-model="passwordForm.email" type="email" placeholder="Email" readonly class="w-full mb-4 px-4 py-2 rounded border border-gray-300 bg-gray-100 focus:outline-none" />
                        <input v-model="passwordForm.password" type="password" placeholder="New Password (min 8 characters)" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required minlength="8" />
                        <input v-model="passwordForm.password_confirmation" type="password" placeholder="Confirm Password" class="w-full mb-4 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" required />
                        <button class="w-full mt-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2 rounded-md font-semibold hover:scale-105 transition">Set Password</button>
                        <button type="button" @click="resetForms" class="w-full mt-2 bg-gray-500 text-white py-2 rounded-md font-semibold hover:scale-105 transition">Back to Login</button>
                    </form>

                    <div v-if="!showSetPassword" class="mt-6 text-center">
                        <span class="text-gray-500">or</span>
                        <button @click="goToGitHub" class="ml-2 text-indigo-600 font-semibold hover:underline">Login with GitHub</button>
                    </div>

                    <div v-if="!showSetPassword" class="mt-4 text-center text-sm">
                        Don't have an account?
                        <router-link to="/auth/signup1" class="text-indigo-600 font-semibold hover:underline">Sign Up</router-link>
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
import { useRouter } from 'vue-router'
import {handleGitHubAuth} from "@/utils/auth";
const router = useRouter()

// DOM ref for title element
const titleEl = ref(null)

// Typewriter animation
const isLoaded = ref(false)
const displayedTitle = ref('')
const headingText = 'CodeGuard AI'
const typewriterIndex = ref(0)

const typeWriter = () => {
    if (typewriterIndex.value < headingText.length) {
        displayedTitle.value += headingText.charAt(typewriterIndex.value)
        typewriterIndex.value++
        setTimeout(typeWriter, 100)
    }
}

// Form state
const form = ref({
    email: '',
    password: ''
})

const passwordForm = ref({
    email: '',
    password: '',
    password_confirmation: ''
})

const error = ref('')
const success = ref('')
const loading = ref(false)
const showSetPassword = ref(false)
const githubUserEmail = ref('')

const resetForms = () => {
    showSetPassword.value = false
    error.value = ''
    success.value = ''
    githubUserEmail.value = ''
    passwordForm.value = {
        email: '',
        password: '',
        password_confirmation: ''
    }
}

const onSubmit = async () => {
    error.value = ''
    success.value = ''
    loading.value = true

    try {
        const response = await fetch('http://localhost:8000/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(form.value),
        })

        const data = await response.json()
        console.log('Login response:', data)

        if (!response.ok) {
            if (data.requires_github_login) {
                // Show option to set password
                githubUserEmail.value = form.value.email
                passwordForm.value.email = form.value.email
                showSetPassword.value = true
                error.value = ''
            } else {
                throw new Error(data.message || 'Login failed')
            }
            return
        }

        // Store token and redirect
        localStorage.setItem('token', data.access_token)

        // Ensure router is available
        if (router) {
            await router.push('/repositories')
        } else {
            window.location.href = '/repositories'
        }

    } catch (err) {
        console.error('Login error:', err)
        error.value = err.message || 'Network error. Please try again.'
    } finally {
        loading.value = false
    }
}

const onSetPassword = async () => {
    error.value = ''
    success.value = ''
    loading.value = true

    if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        error.value = 'Passwords do not match'
        loading.value = false
        return
    }

    if (passwordForm.value.password.length < 8) {
        error.value = 'Password must be at least 8 characters long'
        loading.value = false
        return
    }

    try {
        const response = await fetch('http://localhost:8000/api/auth/set-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(passwordForm.value),
        })

        const data = await response.json()
        console.log('Set password response:', data)

        if (!response.ok) {
            throw new Error(data.message || 'Failed to set password')
        }

        success.value = 'Password set successfully! You can now login with email/password.'

        // Store the token from the response
        if (data.access_token) {
            localStorage.setItem('token', data.access_token)
        }

        setTimeout(() => {
            resetForms()
            // Optionally redirect to repositories or stay on login
            // await router.push('/repositories')
        }, 2000)

    } catch (err) {
        console.error('Set password error:', err)
        error.value = err.message || 'Failed to set password'
    } finally {
        loading.value = false
    }
}

const goToGitHub = async () => {
    try {
        const { redirect } = await handleGitHubAuth();
        await router.push(redirect || '/repositories');
    } catch (err) {
        error.value = err.message;
    }
};

// Animated background
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
    setTimeout(() => {
        isLoaded.value = true
        typeWriter()
    }, 300)
})

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId)
})
</script>

<style scoped>
html, body {
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}
@keyframes float {
    0%, 100% {
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
.relative.z-10 > div:last-child {
    margin-bottom: 0;
}
</style>
