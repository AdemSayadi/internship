<template>
    <div class="webhook-management">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold">Webhook Configuration</h3>
            <Button
                label="Setup Webhooks"
                icon="pi pi-cog"
                @click="showWebhookDialog = true"
            />
        </div>

        <!-- Webhook Status for GitHub Repositories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="repo in githubRepositories"
                :key="repo.id"
                class="border rounded-lg p-4 bg-white"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ repo.name }}</h4>
                        <p class="text-sm text-gray-500 mt-1">{{ repo.full_name }}</p>

                        <div class="mt-3 flex items-center gap-2">
                            <Tag
                                :value="repo.webhook_enabled ? 'Webhook Active' : 'No Webhook'"
                                :severity="repo.webhook_enabled ? 'success' : 'warning'"
                                class="text-xs"
                            />
                            <span v-if="repo.webhook_created_at" class="text-xs text-gray-500">
                                Setup {{ formatDate(repo.webhook_created_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-1">
                        <Button
                            v-if="!repo.webhook_enabled"
                            icon="pi pi-plus"
                            size="small"
                            text
                            @click="createWebhook(repo)"
                            :loading="creatingWebhook === repo.id"
                            v-tooltip="'Create Webhook'"
                        />
                        <Button
                            v-else
                            icon="pi pi-trash"
                            size="small"
                            text
                            severity="danger"
                            @click="deleteWebhook(repo)"
                            :loading="deletingWebhook === repo.id"
                            v-tooltip="'Delete Webhook'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="githubRepositories.length === 0" class="text-center py-8">
            <i class="pi pi-github text-4xl text-gray-300"></i>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No GitHub repositories found</h3>
            <p class="mt-2 text-gray-500">Connect GitHub repositories to enable webhook-based pull request reviews.</p>
        </div>

        <!-- Webhook Setup Dialog -->
        <Dialog v-model:visible="showWebhookDialog" header="Webhook Setup Guide" modal class="w-full max-w-2xl">
            <div class="space-y-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-medium text-blue-900 mb-2">What are webhooks?</h4>
                    <p class="text-sm text-blue-800">
                        Webhooks allow GitHub to automatically notify our system when pull requests are created,
                        updated, or merged. This enables automatic code reviews and real-time updates.
                    </p>
                </div>

                <div>
                    <h4 class="font-medium mb-3">Automatic Setup</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        We can automatically configure webhooks for your GitHub repositories.
                        This requires the <code class="bg-gray-100 px-1 rounded">admin:repo_hook</code> permission.
                    </p>

                    <div class="flex gap-2">
                        <Button
                            label="Auto-Setup All Repositories"
                            icon="pi pi-magic-wand"
                            @click="autoSetupWebhooks"
                            :loading="autoSetupLoading"
                        />
                        <Button
                            label="Check Permissions"
                            icon="pi pi-shield"
                            severity="secondary"
                            @click="checkPermissions"
                        />
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h4 class="font-medium mb-3">Manual Setup</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        If automatic setup doesn't work, you can manually configure webhooks in your GitHub repository settings.
                    </p>

                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL:</label>
                            <div class="flex gap-2">
                                <InputText
                                    :value="webhookUrl"
                                    readonly
                                    class="flex-1 font-mono text-sm"
                                />
                                <Button
                                    icon="pi pi-copy"
                                    size="small"
                                    @click="copyWebhookUrl"
                                    v-tooltip="'Copy URL'"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content Type:</label>
                            <code class="text-sm bg-white px-2 py-1 rounded border">application/json</code>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Events:</label>
                            <div class="flex gap-2">
                                <Tag value="Pull requests" />
                                <Tag value="Pushes" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a
                            href="https://docs.github.com/en/developers/webhooks-and-events/webhooks/creating-webhooks"
                            target="_blank"
                            class="text-indigo-600 hover:text-indigo-900 text-sm"
                        >
                            GitHub Webhook Documentation <i class="pi pi-external-link ml-1"></i>
                        </a>
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h4 class="font-medium mb-3">Webhook Events</h4>
                    <div class="text-sm text-gray-600 space-y-2">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-600"></i>
                            <span><strong>Pull Request Opened:</strong> Automatically triggers code review</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-600"></i>
                            <span><strong>Pull Request Updated:</strong> Re-analyzes changed code</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-check-circle text-green-600"></i>
                            <span><strong>Pull Request Closed/Merged:</strong> Updates status</span>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Webhook Test Dialog -->
        <Dialog v-model:visible="showTestDialog" header="Test Webhook" modal class="w-full max-w-md">
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Send a test payload to verify webhook configuration.
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repository:</label>
                    <Dropdown
                        v-model="selectedTestRepo"
                        :options="githubRepositories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select repository"
                        class="w-full"
                    />
                </div>

                <div class="flex gap-2 justify-end">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        @click="showTestDialog = false"
                    />
                    <Button
                        label="Send Test"
                        @click="testWebhook"
                        :loading="testingWebhook"
                        :disabled="!selectedTestRepo"
                    />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';

// Props
const props = defineProps({
    repositories: {
        type: Array,
        default: () => []
    }
});

// Emits
const emit = defineEmits(['webhook-created', 'webhook-deleted', 'repositories-updated']);

// State
const showWebhookDialog = ref(false);
const showTestDialog = ref(false);
const creatingWebhook = ref(null);
const deletingWebhook = ref(null);
const autoSetupLoading = ref(false);
const testingWebhook = ref(false);
const selectedTestRepo = ref(null);

const toast = useToast();

// API base URL
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Computed
const githubRepositories = computed(() => {
    return props.repositories.filter(repo => repo.provider === 'github');
});

const webhookUrl = computed(() => {
    return `${window.location.origin}/api/webhooks/github`;
});

// Utility functions
const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    if (!token) return null;

    return {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Webhook operations
const createWebhook = async (repository) => {
    try {
        creatingWebhook.value = repository.id;

        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/repositories/${repository.id}/webhook`, {
            method: 'POST',
            headers
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to create webhook');
        }

        const data = await response.json();

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Webhook created for ${repository.name}`,
            life: 3000,
        });

        emit('webhook-created', { repository: repository.id, webhook: data });
        emit('repositories-updated');

    } catch (error) {
        console.error('Error creating webhook:', error);

        // Handle specific error cases
        if (error.message.includes('permission') || error.message.includes('403')) {
            toast.add({
                severity: 'error',
                summary: 'Permission Error',
                detail: 'Insufficient permissions. Please grant admin:repo_hook permission.',
                life: 7000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to create webhook: ' + error.message,
                life: 5000,
            });
        }
    } finally {
        creatingWebhook.value = null;
    }
};

const deleteWebhook = async (repository) => {
    try {
        deletingWebhook.value = repository.id;

        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/repositories/${repository.id}/webhook`, {
            method: 'DELETE',
            headers
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to delete webhook');
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Webhook deleted for ${repository.name}`,
            life: 3000,
        });

        emit('webhook-deleted', { repository: repository.id });
        emit('repositories-updated');

    } catch (error) {
        console.error('Error deleting webhook:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete webhook: ' + error.message,
            life: 5000,
        });
    } finally {
        deletingWebhook.value = null;
    }
};

const autoSetupWebhooks = async () => {
    try {
        autoSetupLoading.value = true;

        const headers = getAuthHeaders();
        if (!headers) return;

        const repositoriesWithoutWebhooks = githubRepositories.value.filter(repo => !repo.webhook_enabled);

        if (repositoriesWithoutWebhooks.length === 0) {
            toast.add({
                severity: 'info',
                summary: 'Info',
                detail: 'All repositories already have webhooks configured.',
                life: 3000,
            });
            return;
        }

        const response = await fetch(`${API_BASE}/webhooks/batch-setup`, {
            method: 'POST',
            headers,
            body: JSON.stringify({
                repository_ids: repositoriesWithoutWebhooks.map(repo => repo.id)
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to setup webhooks');
        }

        const data = await response.json();

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Setup ${data.successful} webhooks successfully. ${data.failed} failed.`,
            life: 5000,
        });

        emit('repositories-updated');
        showWebhookDialog.value = false;

    } catch (error) {
        console.error('Error setting up webhooks:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to setup webhooks: ' + error.message,
            life: 5000,
        });
    } finally {
        autoSetupLoading.value = false;
    }
};

const checkPermissions = async () => {
    try {
        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/github/permissions`, { headers });

        if (!response.ok) {
            throw new Error('Failed to check permissions');
        }

        const data = await response.json();

        if (data.has_webhook_permission) {
            toast.add({
                severity: 'success',
                summary: 'Permissions OK',
                detail: 'You have the required permissions to manage webhooks.',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Missing Permissions',
                detail: 'You need admin:repo_hook permission to manage webhooks automatically.',
                life: 7000,
            });
        }

    } catch (error) {
        console.error('Error checking permissions:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to check permissions: ' + error.message,
            life: 5000,
        });
    }
};

const testWebhook = async () => {
    try {
        testingWebhook.value = true;

        const headers = getAuthHeaders();
        if (!headers) return;

        const response = await fetch(`${API_BASE}/repositories/${selectedTestRepo.value}/webhook/test`, {
            method: 'POST',
            headers
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to test webhook');
        }

        toast.add({
            severity: 'success',
            summary: 'Test Successful',
            detail: 'Webhook test payload sent successfully.',
            life: 3000,
        });

        showTestDialog.value = false;

    } catch (error) {
        console.error('Error testing webhook:', error);
        toast.add({
            severity: 'error',
            summary: 'Test Failed',
            detail: 'Webhook test failed: ' + error.message,
            life: 5000,
        });
    } finally {
        testingWebhook.value = false;
    }
};

const copyWebhookUrl = async () => {
    try {
        await navigator.clipboard.writeText(webhookUrl.value);
        toast.add({
            severity: 'success',
            summary: 'Copied',
            detail: 'Webhook URL copied to clipboard',
            life: 2000,
        });
    } catch (error) {
        console.error('Failed to copy:', error);
        toast.add({
            severity: 'error',
            summary: 'Copy Failed',
            detail: 'Failed to copy URL to clipboard',
            life: 3000,
        });
    }
};
</script>

<style scoped>
.webhook-management {
    @apply space-y-6;
}

code {
    @apply bg-gray-100 px-1 py-0.5 rounded text-sm;
}
</style>
