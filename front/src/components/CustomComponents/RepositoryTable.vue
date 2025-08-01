<template>
    <DataTable :value="repositories" class="p-datatable-sm" :paginator="true" :rows="10">
        <Column field="name" header="Name" sortable />
        <Column field="provider" header="Provider" sortable>
            <template #body="{ data }">
                <Tag :value="data.provider"
                     :severity="data.provider === 'github' ? 'info' : 'warning'" />
            </template>
        </Column>
        <Column field="url" header="URL">
            <template #body="{ data }">
                <a v-if="data.url" :href="data.url" target="_blank" class="text-primary hover:underline">
                    {{ data.url }}
                </a>
                <span v-else>N/A</span>
            </template>
        </Column>
        <Column header="Actions" style="width: 200px">
            <template #body="{ data }">
                <Button
                    icon="pi pi-eye"
                    class="p-button-text p-button-sm"
                    @click="$emit('view-submissions', data.id)"
                />
                <Button
                    icon="pi pi-trash"
                    class="p-button-text p-button-sm p-button-danger"
                    @click="$emit('delete', data)"
                />
            </template>
        </Column>
    </DataTable>
</template>

<script setup>
defineProps({
    repositories: {
        type: Array,
        required: true
    }
})

defineEmits(['delete', 'view-submissions'])
</script>
