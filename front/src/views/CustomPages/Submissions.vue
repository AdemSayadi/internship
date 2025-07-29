<template>
    <div class="relative bg-transparent min-h-screen overflow-hidden">
        <!-- Animated Top Gradient Background Overlay -->
        <div
            class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl animate-pulse"
            aria-hidden="true"
            ref="topGradient"
        >
            <div
                class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] via-[#ff6b9d] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] transition-all duration-1000 ease-in-out"
                :style="{ transform: `rotate(${rotation}deg) scale(${scale})` }"
            />
        </div>

        <!-- Animated Bottom Gradient Background Overlay -->
        <div
            class="absolute inset-x-0 bottom-0 -z-10 transform-gpu overflow-hidden blur-3xl"
            aria-hidden="true"
            ref="bottomGradient"
        >
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
            <div class="mx-auto max-w-4xl py-16 sm:py-24 lg:py-32">
                <div class="text-center">
                    <h1
                        class="text-balance text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl transition-all duration-700 ease-out"
                        :class="{ 'animate-pulse': isLoaded }"
                        ref="title"
                    >
                        {{ displayedTitle }}<span class="animate-pulse">|</span>
                    </h1>
                    <p
                        class="mt-6 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8 transition-all duration-700 delay-300 ease-out transform"
                        :class="{ 'translate-y-0 opacity-100': isLoaded, 'translate-y-10 opacity-0': !isLoaded }"
                    >
                        Submit and review your code for {{ repository?.name || 'Repository' }}.
                    </p>
                </div>

                <div class="mt-10 flex justify-center">
                    <Button
                        class="group relative inline-flex items-center justify-center min-w-[160px] gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-md shadow-lg hover:shadow-xl transition-all duration-300 ease-out transform hover:scale-105"
                        @click="showDialog = true"
                        @mouseenter="onButtonHover"
                        @mouseleave="onButtonLeave"
                        :class="{ 'animate-bounce': buttonHovered }"
                    >
                        <!-- Icon manually added here -->
                        <i class="pi pi-plus text-white relative z-10"></i>
                        <span class="relative z-10">Add Submission</span>

                        <!-- Hover overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </Button>

                </div>

                <Dialog v-model:visible="showDialog" header="Add Code Submission" modal class="w-full max-w-md">
                    <form @submit.prevent="addSubmission" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <InputText v-model="form.title" class="w-full" placeholder="Submission Title" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Language</label>
                            <Dropdown
                                v-model="form.language"
                                :options="languages"
                                placeholder="Select Language"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code</label>
                            <Textarea v-model="form.code_content" class="w-full" rows="10" placeholder="Paste your code here" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">File Path (Optional)</label>
                            <InputText v-model="form.file_path" class="w-full" placeholder="/path/to/file" />
                        </div>
                        <Button
                            type="submit"
                            label="Submit"
                            icon="pi pi-check"
                            :loading="loading"
                            class="w-full bg-indigo-600 text-white"
                        />
                    </form>
                </Dialog>

                <DataTable
                    :value="submissions"
                    class="mt-8"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                >
                    <Column field="title" header="Title" sortable />
                    <Column field="language" header="Language" sortable />
                    <Column field="created_at" header="Submitted" sortable>
                        <template #body="slotProps">
                            {{ new Date(slotProps.data.created_at).toLocaleDateString() }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="slotProps">
                            <Button
                                label="View Reviews"
                                icon="pi pi-eye"
                                class="p-button-text"
                                @click="$router.push(`/reviews/${slotProps.data.id}`)"
                            />
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                class="p-button-text p-button-danger"
                                @click="deleteSubmission(slotProps.data.id)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="relative z-10">
            <Footer />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Navbar from '@/components/CustomComponents/Navbar.vue';
import Footer from '@/components/CustomComponents/Footer.vue';

// Animation and reactive data
const isLoaded = ref(false);
const rotation = ref(30);
const scale = ref(1);
const buttonHovered = ref(false);
const displayedTitle = ref('');
const headingText = 'Code Submissions';
const typewriterIndex = ref(0);
const particles = ref([]);
const showDialog = ref(false);
const loading = ref(false);
const form = ref({ title: '', language: '', code_content: '', file_path: '', repository_id: '' });
const submissions = ref([]);
const repository = ref(null);
const router = useRouter();
const route = useRoute();
const toast = useToast();

// Language options
const languages = ['php', 'javascript', 'python', 'java', 'cpp', 'ruby', 'go'];

// Mock data
const mockRepositories = [
    { id: 1, name: 'Project Alpha', url: 'https://github.com/user/project-alpha', provider: 'github', created_at: '2025-07-01' },
    { id: 2, name: 'Project Beta', url: null, provider: 'manual', created_at: '2025-07-15' },
];
const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
];

// Particles
const generateParticles = () => {
    particles.value = Array.from({ length: 20 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        y: Math.random() * 100,
        delay: Math.random() * 3,
        duration: 2 + Math.random() * 4,
    }));
};

// Typewriter effect
const typeWriter = () => {
    if (typewriterIndex.value < headingText.length) {
        displayedTitle.value += headingText.charAt(typewriterIndex.value);
        typewriterIndex.value++;
        setTimeout(typeWriter, 100);
    }
};

// Background animation
let animationId;
const animateBackground = () => {
    rotation.value += 0.2;
    scale.value = 1 + Math.sin(Date.now() * 0.001) * 0.1;
    animationId = requestAnimationFrame(animateBackground);
};

// Hover effects
const onButtonHover = () => {
    buttonHovered.value = true;
    setTimeout(() => (buttonHovered.value = false), 600);
};
const onButtonLeave = () => (buttonHovered.value = false);

// Fetch data
const fetchData = () => {
    const repoId = parseInt(route.params.repositoryId);
    repository.value = mockRepositories.find((repo) => repo.id === repoId) || null;
    if (!repository.value) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Repository not found', life: 3000 });
        router.push('/repositories');
        return;
    }
    submissions.value = mockSubmissions.filter((sub) => sub.repository_id === repoId);
    form.value.repository_id = repoId;
};

// Add submission
const addSubmission = () => {
    loading.value = true;
    setTimeout(() => {
        submissions.value.push({
            id: submissions.value.length + 1,
            repository_id: parseInt(form.value.repository_id),
            title: form.value.title,
            language: form.value.language,
            code_content: form.value.code_content,
            file_path: form.value.file_path || null,
            created_at: new Date().toISOString(),
            reviews: [],
        });
        showDialog.value = false;
        form.value = { title: '', language: '', code_content: '', file_path: '', repository_id: route.params.repositoryId };
        toast.add({ severity: 'success', summary: 'Success', detail: 'Submission added', life: 3000 });
        loading.value = false;
    }, 500);
};

// Delete submission
const deleteSubmission = (id) => {
    submissions.value = submissions.value.filter((sub) => sub.id !== id);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Submission deleted', life: 3000 });
};

// Lifecycle
onMounted(() => {
    generateParticles();
    setTimeout(() => {
        isLoaded.value = true;
        typeWriter();
        animateBackground();
        fetchData();
    }, 300);
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
});
</script>

<style scoped>
html,
body {
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}
@keyframes float {
    0%,
    100% {
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
</style>
