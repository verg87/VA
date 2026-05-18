<script setup>
import router from "@/router";
import { ref } from "vue"; 

import axios from "axios";

import ServerErrorComponent from "@/components/ServerErrorComponent.vue";
import SkeletonComponent from "@/components/SkeletonComponent.vue";
import Dashboard from "@/components/Dashboard/Dashboard.vue";

import "../assets/bank.css";

const isLoading = ref(true);
const isError = ref(false);
const user = ref(null);
const cards = ref(null);

const showTransferModal = ref(false);
const currentView = ref('dashboard');
const transactionsHistory = ref({});

const getCardType = (card) => card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1);

const transfer = ref({
    phone_number: "",
    amount: 0,
    card_id: null 
});

const openTransferModal = () => {
    showTransferModal.value = true;
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    transfer.value = { phone_number: "", amount: 0, card_id: null };
};

const transferMoneyFromDashboard = (data) => {
    transfer.value.phone_number = data.phone_number;
    transfer.value.amount = data.amount;
    transfer.value.card_id = data.card_id;

    transferMoney();
}

const transferMoney = async () => {
    const data = {
        "user_id": user.value.id,
        "receiver_phone_number": transfer.value.phone_number,
        "amount": transfer.value.amount,
        "card_id": transfer.value.card_id
    };

    try {
        await axios.post("/api/bank/transfer", {data});
    } catch (err) {
        if (axios.isAxiosError(err) && err?.response?.data?.message) {
            alert(err.response.data.message);
        } else {
            alert("Something went wrong...");
        }
    }

    closeTransferModal();
}

(async () => {
    try {
        await axios.post("/api/users/auth");
    } catch (err) {
        try {
            await axios.post("/api/users/refresh-token");
        } catch (e) {
            router.push({ path: "/sign-up" });
            isLoading.value = false;
        }
    }

    try {
        if (!user.value) {
            user.value = (await axios.post("/api/users/")).data.data;
        }
    } catch (err) {
        isError.value = true;
    }
    
    isLoading.value = false;

    try {
        const data = {"user_id": user.value.id};

        cards.value = (await axios.get("/api/bank/cards", {params: data})).data.data;
        transactionsHistory.value = (await axios.get("/api/bank/transactions", {params: data})).data.data;

        console.log(cards.value);
    } catch (err) {
    }
})();

const logOutUser = async () => {
    try {
        await axios.post("/api/users/log-out", {data: {}});
    } catch (err) {
        if (axios.isAxiosError(err)) {
            if (err.response && err.response.status < 400) {
                router.push({path: "/sign-up"});
                return;
            }
        }
        alert("Something went wrong, sorry. Try later");
        return;
    }
    router.push({path: "/sign-up"});
}
</script>

<template>
    <ServerErrorComponent v-if="isError"/>
    <SkeletonComponent v-if="isLoading && !isError"/>
    <div v-if="!isLoading && !isError" class="all-page">
        <nav class="bank-navbar">
            <div class="bank-nav-links">
                <RouterLink to="/" class="bank-nav-link">Home</RouterLink>
                <RouterLink to="/bank" class="bank-nav-link">Bank</RouterLink>
            </div>
            <div class="bank-user-info">
                <span>Welcome, {{ user?.first_name }} {{ user?.last_name }}</span>
                <button @click="logOutUser" class="logout-btn">Log Out</button>
            </div>
        </nav>

        <Dashboard 
            :user="user"
            :cards="cards"
            :currentView="currentView"
            :transactions="transactionsHistory"
            :transfer-money="transferMoneyFromDashboard"
            @view-transactions="currentView = 'transactions'"
            @view-dashboard="currentView = 'dashboard'"
            @view-transfer="currentView = 'transfer'"
            @open-transfer-modal="openTransferModal"
            @transfer-money="transferMoneyFromDashboard"
        />

        <div v-if="showTransferModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">Transfer Money</h2>
                    <button @click="closeTransferModal" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="recipient-phone">Recipient Phone Number:</label>
                        <input v-model="transfer.phone_number" type="text" id="recipient-phone" class="modal-input" placeholder="e.g., +1234567890"/>
                    </div>
                    <div class="form-group">
                        <label for="card-transfer">Choose card:</label>
                        <select v-model.number="transfer.card_id" id="card-transfer" class="modal-select">
                            <option v-for="(card, index) in cards" :value="`${card.id}`" :key="index">{{ getCardType(card) }} ({{ card.card_number }})</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transfer-amount">Amount:</label>
                        <input v-model.number="transfer.amount" type="number" id="transfer-amount" class="modal-input" min="0.01" step="0.01" placeholder="e.g., 50.00"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="transferMoney" class="btn btn-primary">Proceed Transfer</button>
                </div>
            </div>
        </div>
    </div>
</template>