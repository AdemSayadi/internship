<template>
    <div class="flex items-center justify-center h-screen">
        <div class="text-center">
            <svg v-if="!processed" class="animate-spin h-12 w-12 text-indigo-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <div v-if="error" class="text-red-600 mb-4">
                <svg class="h-12 w-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-semibold">Authentication Failed</p>
                <p class="text-sm mt-2">{{ error }}</p>
            </div>

            <div v-else-if="success && !processed" class="text-green-600 mb-4">
                <svg class="h-12 w-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-lg font-semibold">Authentication Successful</p>
                <p class="text-sm mt-2">Redirecting...</p>
            </div>

            <div v-else-if="!processed">
                <p class="text-lg">Completing GitHub authentication...</p>
            </div>

            <div v-if="processed" class="text-gray-600">
                <p class="text-sm">You can close this window now.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const processed = ref(false);
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
            }

            // Also store token as fallback
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('token', token);
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
            }
        }

        processed.value = true;

        // Close window after a short delay
        setTimeout(() => {
            if (window.opener) {
                window.close();
            } else {
                // Fallback redirect if not in popup
                if (isSuccess) {
                    window.location.href = '/repositories';
                } else {
                    window.location.href = '/auth/login1?github_error=1';
                }
            }
        }, 2000);

    } catch (err) {
        console.error('Error processing auth callback:', err);
        error.value = 'Failed to process authentication result';
        processed.value = true;

        if (window.opener) {
            window.opener.postMessage({
                success: false,
                error: error.value
            }, 'http://localhost:5173');

            setTimeout(() => window.close(), 2000);
        }
    }
};
</script>
