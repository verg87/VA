<script setup>
const emit = defineEmits([
    "open-deposit-modal"
]);
</script>

<template>
    <div class="dashboard-card">
        <div class="dashboard-card-description">
            <h2 class="card-title">Bonuses</h2>
            <p class="text-gray-600 mb-4">You have no active bonuses at the moment. Check back later!</p>
        </div>
        <button class="btn btn-primary w-full mb-4" @click="emit('open-deposit-modal')">Deposit Money</button>
    </div>
</template>