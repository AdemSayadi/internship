<!-- MetricCard.vue -->
<template>
    <div class="bg-white/80 backdrop-blur-sm rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 truncate">{{ title }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ value }}</p>
            </div>
            <div :class="`${color} rounded-lg p-3`">
                <i :class="`pi ${icon} text-white text-xl`"></i>
            </div>
        </div>
        <div v-if="trend" class="mt-4 flex items-center">
      <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
        trend === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
      }`">
        <i :class="`pi pi-arrow-${trend} mr-1`"></i>
        {{ trend === 'up' ? 'Increased' : 'Decreased' }}
      </span>
            <span class="ml-2 text-sm text-gray-500">vs last week</span>
        </div>
    </div>
</template>
