<script setup>
const props = defineProps({
    showDepositModal: Boolean,
    deposit: Object,
    cards: Array,
    getCardType: Function,
});

const emit = defineEmits([
    "close-deposit-modal",
    "deposit-money"
])
</script>

<template>
    <div v-if="props.showDepositModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Deposit Money</h2>
                <button @click="emit('close-deposit-modal')" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="deposit-amount">Amount to Deposit:</label>
                    <input type="number" v-model.number="props.deposit.amount" id="deposit-amount" class="modal-input" min="0" step="1" placeholder="e.g., 100.00" />
                </div>
                <div class="form-group">
                    <label for="card-deposit">Choose card:</label>
                    <select v-model.number="props.deposit.card_id" id="card-deposit" class="modal-select">
                        <option v-for="(card, index) in props.cards" :value="`${card.id}`" :key="index">
                            {{ props.getCardType(card) }} ({{ card.card_number }})
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deposit-method">Method:</label>
                    <select v-model="props.deposit.type" id="deposit-method" class="modal-select">
                        <option value="transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="cash">Cash (at ATM)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="emit('deposit-money')" class="btn btn-primary">Confirm Deposit</button>
            </div>
        </div>
    </div>
</template>