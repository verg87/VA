<script setup>
import { ref } from 'vue';
import axios from "axios";

const props = defineProps({
    user: Object,
    cards: Array,
    getCardType: Function,
});

const isOpened = ref(false);
const cardId = ref(null);

const open = () => {
    isOpened.value = !isOpened.value;
}

const close = () => {
    cardId.value = null;
    isOpened.value = false;
}

const filterCards = (cards) => {
    return cards.filter((card) => ["debit", "credit"].includes(card.card_type) && !card.is_main );
}

const change = async () => {
    const data = {
        "user_id": props.user.id,
        "card_id": cardId.value
    };

    try {
        await axios.post("/api/bank/change-main-account", {data});
    } catch (err) {
        if (axios.isAxiosError(err) && err?.response?.data?.message) {
            alert(err.response.data.message);
        } else {
            alert("Something went wrong...");
        }
    }
}
</script>

<template>
    <div class="dashboard-card">
        <div class="dashboard-card-description">
            <h2 class="card-title">Main account</h2>
            <p class="text-gray-600 mb-4">You can choose your card to be main. This means that all of the money transfers will go to the card you specified</p>
        </div>
        <button class="btn btn-primary w-full mb-4" @click="open">Choose</button>
    </div>
    <div v-if="isOpened" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Choose your main card</h2>
                <button @click="close" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="main-card">Choose card:</label>
                    <select v-model.number="cardId" id="main-card" class="modal-select">
                        <option v-for="(card, index) in filterCards(props.cards)" :value="`${card.id}`" :key="index">{{ props.getCardType(card) }} ({{ card.card_number }})</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="change" class="modal-create-btn">Change</button>
            </div>
        </div>
    </div>
</template>