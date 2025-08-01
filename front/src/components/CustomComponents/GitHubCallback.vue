<template>
    <div class="flex items-center justify-center h-screen">
        <div class="text-center">
            <div v-if="success" class="text-green-600 mb-4">
                <svg class="h-12 w-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-lg font-semibold">Authentication Successful</p>
            </div>

            <div v-else-if="error" class="text-red-600 mb-4">
                <svg class="h-12 w-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-semibold">Authentication Failed</p>
            </div>

            <p class="text-sm text-gray-600">Closing window...</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const success = ref(false);
const error = ref('');

onMounted(() => {
    processAuthCallback();
});

const processAuthCallback = () => {
    try {
        console.log('GitHub callback route query:', route.query);

        const isSuccess = route.query.success === 'true';
        const token = route.query.token;
        const userStr = route.query.user;
        const errorMsg = route.query.error;

        success.value = isSuccess;

        if (isSuccess && token) {
            // Parse user data if available
            let userData = null;
            if (userStr) {
                try {
                    userData = JSON.parse(userStr);
                } catch (e) {
                    console.warn('Failed to parse user data:', e);
                }
            }

            const messageData = {
                success: true,
                token: token,
                user: userData
            };

            console.log('Sending success message to parent:', messageData);

            // Send message to parent window (the main app)
            if (window.opener) {
                window.opener.postMessage(messageData, 'http://localhost:5173');
                // Close immediately after sending message
                window.close();
            } else {
                // Store token as fallback
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('token', token);
                }
                window.location.href = '/repositories';
            }

        } else {
            error.value = errorMsg || 'Authentication failed';

            const messageData = {
                success: false,
                error: error.value
            };

            console.log('Sending error message to parent:', messageData);

            if (window.opener) {
                window.opener.postMessage(messageData, 'http://localhost:5173');
                // Close after a brief delay for error display
                setTimeout(() => window.close(), 1000);
            } else {
                setTimeout(() => {
                    window.location.href = '/auth/login1?github_error=1';
                }, 1000);
            }
        }

    } catch (err) {
        console.error('Error processing auth callback:', err);
        error.value = 'Failed to process authentication result';

        if (window.opener) {
            window.opener.postMessage({
                success: false,
                error: error.value
            }, 'http://localhost:5173');

            setTimeout(() => window.close(), 1000);
        }
    }
};
</script>
