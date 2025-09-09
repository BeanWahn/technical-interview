<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 transition-shadow duration-200">
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <!-- Secret Content -->
                <div class="mb-2">
                    <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded border font-mono">
                        {{ secret.content || 'No content' }}
                    </div>
                </div>

                <!-- Metadata -->
                <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span>
                        Created: {{ formatDate(secret.created_at) }}
                    </span>
                    <span v-if="secret.updated_at !== secret.created_at">
                        Updated: {{ formatDate(secret.updated_at) }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
                <!-- View/Edit Button -->
                <button
                    @click="$emit('edit', secret)"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </button>

                <!-- Delete Button -->
                <button
                    @click="$emit('delete', secret)"
                    class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        <!-- Share Button -->
        <button
            @click="$emit('share', secret)"
            class="mt-4 inline-flex items-center px-3 py-1.5 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
            </svg>
            Generate Share Link
        </button>

        <!-- Share Links Section -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-900">
                    Share Links
                    <span v-if="shares.length > 0" class="text-xs text-gray-500">({{ shares.length }})</span>
                </h4>
                <button
                    @click="loadShares"
                    class="text-xs text-blue-600 hover:text-blue-800"
                    :disabled="loadingShares"
                >
                    {{ loadingShares ? 'Loading...' : 'Refresh' }}
                </button>
            </div>

            <!-- Loading state -->
            <div v-if="loadingShares" class="text-center py-4">
                <div class="text-sm text-gray-500">
                    Loading share links...
                </div>
            </div>

            <!-- No shares message -->
            <div v-else-if="shares.length === 0" class="text-center py-4">
                <div class="text-sm text-gray-500">
                    No share links created yet. Click "Generate Share Link" above to create one.
                </div>
            </div>

            <!-- Shares List -->
            <div v-if="shares.length > 0" class="space-y-2">
                <div
                    v-for="share in shares"
                    :key="share.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="text-xs font-mono text-gray-600 truncate">
                                {{ share.url }}
                            </span>
                            <!-- Status Badges -->
                            <span
                                v-if="share.is_expired"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                            >
                                Expired
                            </span>
                            <span
                                v-else-if="share.is_used"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                            >
                                Used
                            </span>
                            <span
                                v-else-if="share.can_be_accessed"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                            >
                                Active
                            </span>
                        </div>
                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                            <span>Expires: {{ formatDate(share.expires_at) }}</span>
                            <span>Access: {{ share.access_count }}/{{ share.max_access_count }}</span>
                            <span v-if="share.notes" class="text-gray-600">{{ share.notes }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 ml-3">
                        <!-- Copy Button -->
                        <button
                            @click="copyToClipboard(share.url)"
                            class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :title="`Copy ${share.url}`"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>

                        <!-- Revoke Button -->
                        <button
                            @click="revokeShare(share.id)"
                            class="inline-flex items-center px-2 py-1 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            :disabled="share.is_used || share.is_expired"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, ref, onMounted, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
    secret: {
        type: Object,
        required: true
    }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'share'])

// Reactive data
const shares = ref([])
const loadingShares = ref(false)

// Load shares for this secret
const loadShares = async () => {
    loadingShares.value = true
    try {
        const response = await axios.get(`/api/secrets/${props.secret.id}/shares`)
        shares.value = response.data.shares || []
    } catch (error) {
        console.error('Error loading shares:', error)
        shares.value = []
    } finally {
        loadingShares.value = false
    }
}

// Copy URL to clipboard
const copyToClipboard = async (url) => {
    try {
        await navigator.clipboard.writeText(url)
        // You could add a toast notification here
        console.log('URL copied to clipboard:', url)
    } catch (error) {
        console.error('Failed to copy to clipboard:', error)
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = url
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
    }
}

// Revoke a share
const revokeShare = async (shareId) => {
    try {
        await axios.delete(`/api/shares/${shareId}`)
        // Reload shares after revoking
        await loadShares()
    } catch (error) {
        console.error('Error revoking share:', error)
    }
}

// Helper function to format dates
const formatDate = (dateString) => {
    if (!dateString) return 'Unknown'

    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Load shares when component mounts
onMounted(() => {
    loadShares()
})
</script>
