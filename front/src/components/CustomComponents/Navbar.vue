<script setup>
import { Dialog, DialogPanel } from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import {useAuth} from "@/router/composables/useAuth";

const router = useRouter();
const mobileMenuOpen = ref(false);


const { isAuthenticated } = useAuth();
const handleLogout = async () => {
    try {
        await fetch("http://localhost:8000/api/auth/logout", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
        });
        localStorage.removeItem("token");
        await router.push("/auth/login1");
    } catch (error) {
        console.error("Logout failed:", error);
    }
};

const navigation = [
    { name: 'Repositories', href: '/repositories' },
    { name: 'Submissions', href: '#' },
    { name: 'Code Reviews', href: '#' },
    { name: 'Notifications', href: '/notifications' },
    { name: 'Dashboard', href: '#' },
]
</script>

<template>
    <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
        <div class="flex lg:flex-1">
            <a href="/index" class="-m-1.5 p-1.5 flex items-center gap-2">
                <span class="sr-only">CodeGuard AI</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-auto text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <title>CodeGuard AI</title>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
                    11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623
                    5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
                    0-6.1-1.248-8.25-3.285Z" />
                </svg>
                CodeGuard AI
            </a>
        </div>

        <div class="flex lg:hidden">
            <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700" @click="mobileMenuOpen = true">
                <span class="sr-only">Open main menu</span>
                <Bars3Icon class="size-6" aria-hidden="true" />
            </button>
        </div>
        <div class="hidden lg:flex lg:gap-x-12">
            <a v-for="item in navigation" :key="item.name" :href="item.href" class="text-sm/6 font-semibold text-gray-900">{{ item.name }}</a>
        </div>
        <div class="hidden lg:flex lg:flex-1 lg:justify-end">
            <template v-if="!isAuthenticated">
                <a href="/auth/login1" class="text-sm/6 font-semibold text-gray-900">Log In / Sign Up <span aria-hidden="true">&rarr;</span></a>
            </template>
            <template v-else>
                <button @click="handleLogout" class="text-sm/6 font-semibold text-gray-900">
                    Log Out <span aria-hidden="true">&rarr;</span>
                </button>
            </template>
        </div>
    </nav>

    <Dialog class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
        <div class="fixed inset-0 z-50" />
        <DialogPanel class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
            <div class="flex items-center justify-between">
                <a href="/index" class="-m-1.5 p-1.5 flex items-center gap-2">
                    <span class="sr-only">CodeGuard AI</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-auto text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <title>CodeGuard AI</title>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
                    11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623
                    5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
                    0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    CodeGuard AI
                </a>
                <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700" @click="mobileMenuOpen = false">
                    <span class="sr-only">Close menu</span>
                    <XMarkIcon class="size-6" aria-hidden="true" />
                </button>
            </div>
            <div class="mt-6 flow-root">
                <div class="-my-6 divide-y divide-gray-500/10">
                    <div class="space-y-2 py-6">
                        <a v-for="item in navigation" :key="item.name" :href="item.href" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">{{ item.name }}</a>
                    </div>
                    <div class="py-6">
                        <template v-if="!isLoggedIn">
                            <a href="/auth/login1" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Log in / Sign up</a>
                        </template>
                        <template v-else>
                            <button @click="handleLogout" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 w-full text-left">
                                Logout
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </DialogPanel>
    </Dialog>
</template>

<style scoped lang="scss"></style>
