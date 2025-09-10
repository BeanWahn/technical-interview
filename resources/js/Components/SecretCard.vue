<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 transition-shadow duration-200">
        <div class="flex items-start justify-between sm:flex-row flex-col">
            <div class="flex-1 min-w-0 w-full">
                <!-- Secret Content -->
                <div class="mb-2">
                    <!-- Display mode -->
                    <div v-if="!isEditing" class="text-sm text-gray-700 bg-gray-50 p-3 rounded border font-mono overflow-x-scroll">
                        {{ displayContent || 'No content' }}
                    </div>
                    <!-- Edit mode -->
                    <div v-else>
                        <textarea
                            ref="editTextarea"
                            v-model="editContent"
                            class="w-full text-sm text-gray-700 bg-white p-3 rounded border font-mono resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            rows="3"
                            placeholder="Enter your secret content..."
                            @keydown.enter.prevent="submitEdit"
                            @keydown.escape="cancelEdit"
                        ></textarea>
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

                <!-- Encryption Toggle -->
                <div v-if="secret.is_encrypted" class="mt-2">
                    <button
                        @click="toggleEncryptionView"
                        class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg v-if="!showDecrypted" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                        </svg>
                        {{ showDecrypted ? 'Hide' : 'Show' }} {{ showDecrypted ? 'Encrypted' : 'Decrypted' }}
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 sm:ml-4 ml-0">
                <!-- Display mode buttons -->
                <template v-if="!isEditing">
                    <!-- Edit Button -->
                    <button
                        @click="startEdit"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 mt-4"
                    >
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>

                    <!-- Delete Button -->
                    <button
                        @click="$emit('delete', secret)"
                        class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 mt-4"
                    >
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </template>

                <!-- Edit mode buttons -->
                <template v-else>
                    <!-- Submit Button -->
                    <button
                        @click="submitEdit"
                        :disabled="!editContent.trim() || editContent === (secret.decrypted_content || secret.encrypted_content) || isSaving"
                        class="inline-flex items-center px-3 py-1.5 border border-green-300 shadow-sm text-xs font-medium rounded text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="!isSaving" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>

                    <!-- Cancel Button -->
                    <button
                        @click="cancelEdit"
                        :disabled="isSaving"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </button>
                </template>
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
                    <span v-if="shares && shares.length > 0" class="text-xs text-gray-500">({{ shares.length }})</span>
                </h4>
            </div>

            <!-- Loading state -->
            <div v-if="loadingShares" class="text-center py-4">
                <div class="text-sm text-gray-500">
                    Loading share links...
                </div>
            </div>

            <!-- No shares message -->
            <div v-else-if="!shares || (shares && shares.length === 0)" class="text-center py-4">
                <div class="text-sm text-gray-500">
                    No share links created yet. Click "Generate Share Link" above to create one.
                </div>
            </div>

            <!-- Shares List -->
            <div v-if="shares && shares.length > 0" class="space-y-2">
                <div
                    v-for="share in shares"
                    :key="share.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border"
                >
                    <div class="flex-1 min-w-0 w-full">
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
                                v-else-if="share.is_disabled"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                            >
                                Disabled
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
                            <span>Access: {{ share.access_count }}/1</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 ml-3">
                        <!-- Copy Button -->
                        <button
                            @click="copyToClipboard(share.url); showToast('URL Copied to Clipboard')"
                            class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :title="`Copy ${share.url}`"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <Transition name="fade">
                            <div
                                v-if="toastVisible"
                                class="fixed bottom-6 right-6 bg-green-500 text-white px-4 py-2 rounded shadow-lg text-sm z-50"
                                style="min-width: 180px; text-align: center;"
                            >
                                {{ toastMessage }}
                            </div>
                        </Transition>

                        <!-- Revoke Button -->
                        <button
                            @click="revokeShare(share.id)"
                            class="inline-flex items-center px-2 py-1 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="share.is_used || share.is_expired || share.is_disabled"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Re-enable Button -->
                        <button
                            v-if="share.is_disabled"
                            @click="reenableShare(share.id)"
                            class="inline-flex items-center px-2 py-1 border border-green-300 shadow-sm text-xs font-medium rounded text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            :disabled="!share.is_disabled"
                            title="Re-enable this share"
                        >
                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, ref, computed, onMounted, watch, nextTick } from 'vue'
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
const shares = ref(props.secret.shares)
const loadingShares = ref(false)
const toastVisible = ref(false)
const toastMessage = ref('')
const isEditing = ref(false)
const editContent = ref('')
const editTextarea = ref(null)
const isSaving = ref(false)
const showDecrypted = ref(false) // Show encrypted content by default

const showToast = (message) => {
    toastVisible.value = true
    toastMessage.value = message
    setTimeout(() => {
        toastVisible.value = false
    }, 3000)
}

// Computed property for display content
const displayContent = computed(() => {
    if (props.secret.is_encrypted) {
        return showDecrypted.value ? props.secret.decrypted_content : props.secret.encrypted_content
    }
    return props.secret.decrypted_content || props.secret.encrypted_content
})

// Toggle encryption view
const toggleEncryptionView = () => {
    showDecrypted.value = !showDecrypted.value
}

// Edit mode functions
const startEdit = () => {
    isEditing.value = true
    // Use decrypted content for editing
    editContent.value = props.secret.decrypted_content || props.secret.encrypted_content
    // Focus the textarea after the DOM updates
    nextTick(() => {
        if (editTextarea.value) {
            editTextarea.value.focus()
            editTextarea.value.select()
        }
    })
}

const cancelEdit = () => {
    isEditing.value = false
    editContent.value = ''
}

const submitEdit = async () => {
    const currentContent = props.secret.decrypted_content || props.secret.encrypted_content
    if (editContent.value.trim() && editContent.value !== currentContent) {
        isSaving.value = true
        try {
            // Emit the edit event with the updated secret
            emit('edit', {
                ...props.secret,
                content: editContent.value.trim()
            })
            isEditing.value = false
            editContent.value = ''
        } finally {
            isSaving.value = false
        }
    } else {
        isEditing.value = false
        editContent.value = ''
    }
}
// Copy URL to clipboard
const copyToClipboard = async (url) => {
    try {
        await navigator.clipboard.writeText(url)
        showToast('URL Copied to Clipboard')
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

// Re-enable a share
const reenableShare = async (shareId) => {
    try {
        await axios.put(`/api/shares/${shareId}/reenable`)
        shares.value = shares.value.map(share => share.id === shareId ? { ...share, is_disabled: false } : share)
    } catch (error) {
        console.error('Error re-enabling share:', error)
    }
}

// Revoke a share
const revokeShare = async (shareId) => {
    try {
        await axios.put(`/api/shares/${shareId}/revoke`)
        shares.value = shares.value.map(share => share.id === shareId ? { ...share, is_disabled: true } : share)
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
</script>
