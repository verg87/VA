<script setup>
const props = defineProps({
    cards: Array
});

const getCardBalance = (card) =>
  `${card.amount}`.startsWith("-")
    ? `-$${card.amount.toString().slice(1)}`
    : `$${card.amount}`;
</script>

<template>
    <div class="dashboard-card col-span-1 lg:col-span-2">
        <h2 class="card-title">Your Cards</h2>
        <div class="cards-box">
            <div v-for="(card, index) in props.cards" :key="index" class="card-item">
                <div class="card-header">
                    <h3 class="card-type">{{ card.card_type }}</h3>
                    <h4 class="card-main">{{ card.is_main ? "Main" : "" }}</h4>
                </div>
                <div class="card-number">{{ card.card_number }}</div>
                <div class="card-details">
                    <div class="card-balance">
                        Balance: {{ getCardBalance(card) }}
                    </div>
                    <div class="card-expiry">Expires: {{ card.expires_at }}</div>
                </div>
            </div>
        </div>
    </div>
</template>