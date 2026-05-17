<script setup>
import { ref, computed } from 'vue';
import axios from "axios";

const isOpen = ref(false);
const newCard = ref({
    type: "credit",
    amount: 0,
    card_number: "",
    expires_at: "",
    cvv: ""
});
const isPrepaid = computed(() => newCard.value.type === "prepaid")

const open = () => {
    isOpen.value = !isOpen.value;
}

const close = () => {
    isOpen.value = false;
    newCard.value = { type: "credit", amount: 0, card_number: "", expires_at: "", cvv: "" };
}

const validate = () => {
    if (
        !["credit", "debit", "overdraft", "prepaid"].includes(newCard.value.type) 
        || !/^\d{4}\s\d{4}\s\d{4}\s\d{4}$/.test(newCard.value.card_number)
        || !/^\d\d\/\d\d$/.test(newCard.value.expires_at)
        || !/^\d{3}$/.test(newCard.value.cvv)
        || parseInt(newCard.value.expires_at.slice(0, 2)) >= 13
    ) {
        return false;
    }

    return true;
}

const create = async () => {
    if (!validate()) {
        alert("Invalid card registration field values");
        return;
    }

    const data = {
        "user_id": user.value.id,
        "card_type": newCard.value.type,
        "amount": newCard.value.amount,
        "card_number": newCard.value.card_number,
        "expires_at": newCard.value.expires_at,
        "cvv": newCard.value.cvv,
    };
    
    try {
        await axios.post("/api/bank/cards", {data});
    } catch (err) {
        if (axios.isAxiosError(err) && err?.response?.data?.message) {
            alert(err.response.data.message);
        } else {
            alert("Something went wrong...");
        }
    }
    
    close();
}
</script>

<template>
    <div class="dashboard-card">
        <div class="dashboard-card-description">
            <h2 class="card-title">Register a card</h2>
            <p class="text-gray-600 mb-4">Create your own credit card</p>
        </div>
        <button class="btn btn-primary w-full mb-4" @click="open">
            Create
        </button>
    </div>
    <div v-if="isOpen" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Register your card</h2>
                <button @click="close" class="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="card-type">Card Type:</label>
                    <select id="card-type" v-model="newCard.type" class="modal-select">
                        <option value="credit">Credit</option>
                        <option value="debit">Debit</option>
                        <option value="overdraft">Overdraft</option>
                        <option value="prepaid">Prepaid</option>
                    </select>
                </div>
                <div v-if="isPrepaid" class="form-group">
                    <label for="card-amount">Initial Amount:</label>
                    <input type="text" id="card-amount" v-model.number="newCard.amount" class="modal-input" min="0" maxlength="7"/>
                </div>
                <div class="form-group">
                    <label for="card-number">Card Number:</label>
                    <input type="text" id="card-number" v-model="newCard.card_number" class="modal-input" placeholder="1234 5678 9000 0000" maxlength="19"/>
                </div>
                <div class="form-group-double">
                    <div class="form-group">
                        <label for="expires-at">Expiration Date:</label>
                        <input type="text" id="expires-at" v-model="newCard.expires_at" class="modal-input" placeholder="MM/YY" maxlength="5"/>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV:</label>
                        <input type="text" id="cvv" v-model="newCard.cvv" class="modal-input" placeholder="CVV" maxlength="3">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="create" class="modal-create-btn">Create</button>
            </div>
        </div>
    </div>
</template>