<script setup>
import router from "@/router";
import { ref, computed } from "vue"; 

import axios from "axios";

import ServerErrorComponent from "@/components/ServerErrorComponent.vue";
import SkeletonComponent from "@/components/SkeletonComponent.vue";
import DashboardComponent from "@/components/DashboardComponent.vue";

import "../assets/bank.css";

const isLoading = ref(true);
const isError = ref(false);
const user = ref(null);
const cards = ref(null);

const showCardCreationModal = ref(false);
const showTransferModal = ref(false);
const showDepositModal = ref(false);
const currentView = ref('dashboard');

const transferMatchedPhoneNumbers = ref([]);

const newCard = ref({
    type: "credit",
    amount: 0,
    card_number: "",
    expires_at: "",
    cvv: ""
});
const isPrepaid = computed(() => newCard.value.type === "prepaid");
const getCardType = (card) => card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1);

const deposit = ref({
    type: "",
    amount: 0, 
    card_id: null
});

const transfer = ref({
    phone_number: "",
    amount: 0,
    card_id: null 
});

const openCardCreaionModal = () => {
    showCardCreationModal.value = true;
};

const closeCardCreationModal = () => {
    showCardCreationModal.value = false;
    newCard.value = { type: "credit", amount: 0, card_number: "", expires_at: "", cvv: "" }; 
};

const openTransferModal = () => {
    showTransferModal.value = true;
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    transfer.value = { phone_number: "", amount: 0, card_id: null };
};

const openDepositModal = () => {
    showDepositModal.value = true;
};

const closeDepositModal = () => {
    showDepositModal.value = false;
    deposit.value = { type: "", amount: 0, card_id: null };
};

const findPhoneNumber = (event) => {
    console.log(event);
}

const validateCardInfo = () => {
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

const createCard = async () => {
    if (!validateCardInfo()) {
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
    
    closeCardCreationModal();
};

const depositMoney = async () => {
    const data = {
        "user_id": user.value.id,
        "type": deposit.value.type,
        "amount": deposit.value.amount,
        "card_id": deposit.value.card_id
    };

    console.log(data);

    try {
        await axios.post("/api/bank/deposit", {data});
    } catch (err) {
        if (axios.isAxiosError(err) && err?.response?.data?.message) {
            alert(err.response.data.message);
        } else {
            alert("Something went wrong...");
        }
    }

    closeDepositModal();
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

        <DashboardComponent 
            :cards="cards"
            :currentView="currentView"
            :transfer-matched-phone-numbers="transferMatchedPhoneNumbers"
            @view-transactions="currentView = 'transactions'"
            @view-dashboard="currentView = 'dashboard'"
            @view-transfer="currentView = 'transfer'"
            @find-phone-number="findPhoneNumber"
            @open-transfer-modal="openTransferModal"
            @open-deposit-modal="openDepositModal"
            @open-card-creation-modal="openCardCreaionModal"
        />

        <div v-if="showCardCreationModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">Register your card</h2>
                    <button @click="closeCardCreationModal" class="modal-close-btn">&times;</button>
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
                    <button @click="createCard" class="modal-create-btn">Create</button>
                </div>
            </div>
        </div>

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
                        <select v-for="(card, index) in cards" v-model.number="transfer.card_id" id="card-transfer" class="modal-select">
                            <option :value="`${card.id}`" :key="index">{{ getCardType(card) }} ({{ card.card_number }})</option>
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

        <div v-if="showDepositModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">Deposit Money</h2>
                    <button @click="closeDepositModal" class="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="deposit-amount">Amount to Deposit:</label>
                        <input type="number" v-model.number="deposit.amount" id="deposit-amount" class="modal-input" min="0.01" step="0.01" placeholder="e.g., 100.00"/>
                    </div>
                    <div class="form-group">
                        <label for="card-deposit">Choose card:</label>
                        <select v-for="(card, index) in cards" v-model.number="deposit.card_id" id="card-deposit" class="modal-select">
                            <option :value="`${card.id}`" :key="index">{{ getCardType(card) }} ({{ card.card_number }})</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="deposit-method">Method:</label>
                        <select v-model="deposit.type" id="deposit-method" class="modal-select">
                            <option value="bank">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="cash">Cash (at ATM)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="depositMoney" class="btn btn-primary">Confirm Deposit</button>
                </div>
            </div>
        </div>
    </div>
</template>