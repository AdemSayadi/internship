<script setup>
import { Dialog, DialogPanel } from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "@/utils/composables/useAuth";
import NotificationsDropdown from "@/components/CustomComponents/NotificationDropdown.vue";

const router = useRouter();
const mobileMenuOpen = ref(false);
const showGitHubConnect = ref(false);
const userProfile = ref(null);
const { isAuthenticated } = useAuth();

const fetchUserProfile = async () => {
    try {
        const response = await fetch("http://localhost:8000/api/auth/user", {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: 'application/json'
            },
        });

        if (response.ok) {
            const data = await response.json();
            userProfile.value = data.user;
            showGitHubConnect.value = isAuthenticated.value && !data.user.github_id;
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error("Failed to fetch user profile:", error);
        // If token is invalid, redirect to login
        if (error.message.includes('401')) {
            localStorage.removeItem('token');
            await router.push('/auth/login1');
        }
    }
};

const connectGitHub = async () => {
    try {
        const response = await fetch('http://localhost:8000/api/auth/github', {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.url) {
                window.location.href = data.url;
            }
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        console.error("GitHub connection failed:", error);
    }
};

const handleLogout = async () => {
    try {
        await fetch("http://localhost:8000/api/auth/logout", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                Accept: 'application/json'
            },
        });
    } catch (error) {
        console.error("Logout API failed:", error);
    } finally {
        // Always remove token and redirect, even if API call fails
        localStorage.removeItem("token");
        await router.push("/auth/login1");
    }
};

onMounted(() => {
    if (isAuthenticated.value) {
        fetchUserProfile();
    }
});

// Public navigation items (always visible)
const publicNavigation = [
    { name: 'Home', href: '/' },
];

// Private navigation items (only for authenticated users)
const privateNavigation = [
    { name: 'Repositories', href: '/repositories' },
    { name: 'Pull Requests', href: '/pull-requests' },
    { name: 'Notifications', href: '/notifications' },
    { name: 'Dashboard', href: '/dashboard' },
];

// Computed navigation based on auth status
const navigation = computed(() => {
    if (isAuthenticated.value) {
        return [...publicNavigation, ...privateNavigation];
    }
    return publicNavigation;
});
</script>

<template>
    <nav class="flex items-center justify-between p-6 lg:px-8 " aria-label="Global">
        <div class="flex lg:flex-1">
            <router-link to="/" class="-m-1.5 p-1.5 flex items-center gap-2 hover:opacity-80 transition-opacity">
                <span class="sr-only">CodeGuard AI</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-auto text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
                  11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623
                  5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
                  0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span class="text-xl font-bold text-gray-900">CodeGuard AI</span>
            </router-link>
        </div>

        <!-- Mobile Menu Button -->
        <div class="flex lg:hidden">
            <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 hover:bg-gray-100 transition-colors" @click="mobileMenuOpen = true">
                <span class="sr-only">Open main menu</span>
                <Bars3Icon class="size-6" aria-hidden="true" />
            </button>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden lg:flex lg:gap-x-12">
            <router-link
                v-for="item in navigation"
                :key="item.name"
                :to="item.href"
                class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500 transition-colors"
                active-class="text-indigo-600"
            >
                {{ item.name }}
            </router-link>
        </div>

        <!-- Desktop User Actions -->
        <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:items-center lg:gap-4">
            <template v-if="!isAuthenticated">
                <router-link to="/auth/login1" class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500 transition-colors">
                    Log in <span aria-hidden="true">&rarr;</span>
                </router-link>
            </template>
            <template v-else>
                <!-- Notifications Dropdown -->
                <NotificationsDropdown />

                <!-- GitHub Connect Button -->
                <button
                    v-if="showGitHubConnect"
                    @click="connectGitHub"
                    class="flex items-center gap-2 text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-500 transition-colors px-3 py-2 rounded-lg hover:bg-gray-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                    </svg>
                    Connect GitHub
                </button>

                <!-- User Profile Section -->
                <div class="flex items-center gap-3">
                    <!-- User Info -->
                    <div v-if="userProfile" class="text-sm">
                        <span class="text-gray-700 font-medium">{{ userProfile.name }}</span>
                        <span v-if="userProfile.email" class="text-gray-500 text-xs block">{{ userProfile.email }}</span>
                    </div>

                    <!-- Logout Button -->
                    <button
                        @click="handleLogout"
                        class="text-sm font-semibold leading-6 text-gray-900 hover:text-red-600 transition-colors px-3 py-2 rounded-lg hover:bg-red-50"
                        title="Logout"
                    >
                        Log out <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </template>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <Dialog as="div" class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
        <div class="fixed inset-0 z-50" />
        <DialogPanel class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
            <div class="flex items-center justify-between">
                <router-link to="/" class="-m-1.5 p-1.5 flex items-center gap-2" @click="mobileMenuOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-auto text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
                    11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623
                    5.176-1.332 9-6.30 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
                    0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <span class="text-xl font-bold text-gray-900">CodeGuard AI</span>
                </router-link>
                <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700 hover:bg-gray-100 transition-colors" @click="mobileMenuOpen = false">
                    <span class="sr-only">Close menu</span>
                    <XMarkIcon class="size-6" aria-hidden="true" />
                </button>
            </div>
            <div class="mt-6 flow-root">
                <div class="-my-6 divide-y divide-gray-500/10">
                    <!-- Navigation Links -->
                    <div class="space-y-2 py-6">
                        <router-link
                            v-for="item in navigation"
                            :key="item.name"
                            :to="item.href"
                            @click="mobileMenuOpen = false"
                            class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50 transition-colors"
                            active-class="bg-gray-50 text-indigo-600"
                        >
                            {{ item.name }}
                        </router-link>
                    </div>

                    <!-- User Actions -->
                    <div class="py-6">
                        <template v-if="!isAuthenticated">
                            <router-link
                                to="/auth/login1"
                                @click="mobileMenuOpen = false"
                                class="-mx-3 block rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50 transition-colors"
                            >
                                Log in
                            </router-link>
                        </template>
                        <template v-else>
                            <!-- User Profile Info -->
                            <div v-if="userProfile" class="px-3 py-2 mb-4 bg-gray-50 rounded-lg">
                                <div class="text-sm font-medium text-gray-900">{{ userProfile.name }}</div>
                                <div v-if="userProfile.email" class="text-sm text-gray-500">{{ userProfile.email }}</div>
                            </div>

                            <!-- Mobile Notifications Link -->
                            <router-link
                                to="/notifications"
                                @click="mobileMenuOpen = false"
                                class="-mx-3 flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                Notifications
                            </router-link>

                            <!-- GitHub Connect Button (Mobile) -->
                            <button
                                v-if="showGitHubConnect"
                                @click="connectGitHub"
                                class="-mx-3 w-full text-left flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                                </svg>
                                Connect GitHub
                            </button>

                            <!-- Logout Button (Mobile) -->
                            <button
                                @click="handleLogout"
                                class="-mx-3 w-full text-left flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-semibold leading-7 text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Log out
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </DialogPanel>
    </Dialog>
</template>

<style scoped>
.router-link-active {
    color: #4f46e5;
    font-weight: 600;
}

/* Ensure mobile menu appears above other content */
.fixed {
    z-index: 9999;
}
</style>
