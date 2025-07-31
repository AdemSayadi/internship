<template>
    <div class="flex items-center justify-center h-screen">
        <div class="text-center">
            <svg class="animate-spin h-12 w-12 text-indigo-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-lg">Completing GitHub authentication...</p>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

onMounted(() => {
    // Send auth result back to opener window
    if (window.opener) {
        window.opener.postMessage({
            success: route.query.success !== 'false',
            token: route.query.token,
            user: route.query.user ? JSON.parse(route.query.user) : null
        }, 'http://localhost:5173'); // Your frontend URL

        window.close();
    } else {
        // Handle direct access (fallback)
        if (route.query.token) {
            localStorage.setItem('token', route.query.token);
            window.location.href = '/repositories';
        }
    }
});
</script>
