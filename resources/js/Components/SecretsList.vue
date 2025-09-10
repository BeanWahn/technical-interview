<template>
    <div class="space-y-4">
        <!-- Header Section -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
                Your Secrets
            </h3>
            <div class="text-sm text-gray-500">
                {{ secrets.length }} {{ secrets.length === 1 ? 'secret' : 'secrets' }}
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="secrets.length === 0" class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No secrets yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first secret.</p>
        </div>

        <!-- Secrets List -->
        <div v-else class="space-y-3">
            <SecretCard
                v-for="secret in secrets"
                :key="secret.id"
                :secret="secret"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
                @share="$emit('share', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import SecretCard from './SecretCard.vue'

// Props
const props = defineProps({
    secrets: {
        type: Array,
        default: () => [],
        required: true
    }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'toggle-encryption', 'share'])
</script>
