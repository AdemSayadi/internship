<template>
    <footer class="relative isolate overflow-hidden  py-20 px-6 lg:px-20 text-gray-900">
        <!-- Matching Gradient Background Shape (bottom right) -->
        <div
            class="absolute inset-x-0 top-[-12rem] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[-24rem]"
            aria-hidden="true"
        >
            <div
                class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]
               -translate-x-1/2 "/>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-10 text-center md:text-left">
            <!-- Branding -->
            <div>
                <h3 class="text-xl font-semibold flex items-center justify-center md:justify-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <title>CodeGuard AI</title>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
              11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623
              5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
              0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    CodeGuard AI
                </h3>
                <p class="mt-4 text-sm text-gray-600">
                    AI-powered code reviews. Smarter commits, safer releases.
                </p>
            </div>

            <!-- Navigation -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Explore</h4>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li><a href="#" class="hover:text-indigo-600 transition">Features</a></li>
                    <li><a href="#" class="hover:text-indigo-600 transition">Pricing</a></li>
                    <li><a href="#" class="hover:text-indigo-600 transition">Docs</a></li>
                    <li><a href="#" class="hover:text-indigo-600 transition">Contact</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Newsletter</h4>
                <p class="text-sm text-gray-700 mb-4">Get product updates and feature announcements.</p>
                <form @submit.prevent class="flex flex-col sm:flex-row items-center gap-2">
                    <input
                        type="email"
                        placeholder="Your email"
                        class="w-full sm:w-auto flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500"
                    >
                        Subscribe
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-10 border-t border-gray-200 pt-6 text-center text-sm text-gray-500">
            © {{ new Date().getFullYear() }} CodeGuard AI. All rights reserved.
        </div>
    </footer>
</template>

<script setup>
</script>
