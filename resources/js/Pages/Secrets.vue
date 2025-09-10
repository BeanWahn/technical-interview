<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import SecretsList from '../Components/SecretsList.vue';

// Reactive data
const secrets = ref([])
const loading = ref(true)
const newSecret = ref('')


const createSecret = async () => {
    const response = await axios.post('/api/secrets', {
        content: newSecret.value
    })
    secrets.value.unshift(response.data)
}

const handleShare = async (secret) => {
    try {
        const response = await axios.post('/api/secrets/' + secret.id + '/shares', {
            secret_id: secret.id
        })
        if(secret.shares) {
            secret.shares.push(response.data.share)
        } else {
            secret.shares = [response.data.share]
        }

    } catch (error) {
        console.error('Error creating share:', error)
    }
}

// Event handlers
const handleEdit = async (updatedSecret) => {
    try {
        const response = await axios.put(`/api/secrets/${updatedSecret.id}`, {
            content: updatedSecret.content
        })

        // Update the secret in the local array
        const index = secrets.value.findIndex(s => s.id === updatedSecret.id)
        if (index !== -1) {
            secrets.value[index] = { ...secrets.value[index], ...response.data }
        }

        secrets.value = secrets.value.map(s => s.id === updatedSecret.id ? { ...s, decrypted_content: response.data.new_decrypted_content, encrypted_content: response.data.new_encrypted_content } : s)
    } catch (error) {
        console.error('Error updating secret:', error)
    }
}

const handleDelete = (secret) => {
    axios.delete(`/api/secrets/${secret.id}`)
        .then(response => {
            secrets.value = secrets.value.filter(s => s.id !== secret.id)
        })
        .catch(error => {
            console.error('Error deleting secret:', error)
        })
}

// Load secrets on component mount
onMounted(async () => {
    try {
        const response = await axios.get('/api/secret-content')
        secrets.value = response.data
    } catch (error) {
        console.error('Error loading secrets:', error)
    } finally {
        loading.value = false
    }
})
</script>

<template>

    <AppLayout title="Secrets">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Secrets
            </h2>
        </template>


        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Create Secret Form -->
                    <form @submit.prevent="newSecret.trim() && createSecret()" class="flex items-center mb-6">
                        <input
                            class="border border-gray-300 rounded-md p-2"
                            type="text"
                            v-model="newSecret"
                            placeholder="Enter your secret"
                        />
                        <button
                            class="ml-4 bg-blue-500 text-white px-4 py-2 rounded-md"
                            :class="{ 'opacity-50 cursor-not-allowed': !newSecret.trim() }"
                            type="submit"
                            :disabled="!newSecret.trim()"
                        >
                            Create New Secret
                        </button>
                    </form>

                    <!-- Loading State -->
                    <div v-if="loading" class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <span class="ml-2 text-gray-600">Loading secrets...</span>
                    </div>
                    <SecretsList
                        @share="handleShare"
                        v-else
                        :secrets="secrets"
                        @edit="handleEdit"
                        @delete="handleDelete"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
