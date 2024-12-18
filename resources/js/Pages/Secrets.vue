<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';


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
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4">
                    Content
                </div>
            </div>
        </div>
    </AppLayout>
</template>
