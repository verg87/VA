<script setup>
import router from "@/router";
import { ref, computed } from "vue"; // Import computed

import axios from "axios";

import ServerErrorComponent from "@/components/ServerErrorComponent.vue";
import SkeletonComponent from "@/components/SkeletonComponent.vue";

import "../assets/bank.css";

const isLoading = ref(true);
const isError = ref(false);
const user = ref(null);
const cards = ref(null);

// Modal related refs and methods
const showCardCreationModal = ref(false);
const newCard = ref({
    type: 'credit', // Default card type
    amount: 0,
});
const isPrepaid = computed(() => newCard.value.type === 'prepaid');

const openModal = () => {
    showCardCreationModal.value = true;
};

const closeModal = () => {
    showCardCreationModal.value = false;
    newCard.value = { type: 'credit', amount: 0 }; // Reset form on close
};

const createCard = async () => {
    const data = {};

    data["user_id"] = user.value["id"];
    data["card_type"] = newCard.value.type;
    data["amount"] = newCard.value.amount;
    
    try {
        await axios.post("/api/bank/cards", {data});
    } catch (err) {
        if (axios.isAxiosError(err) && err?.response?.data?.message) {
            alert(err.response.data.message);
        } else {
            alert("Something went wrong...");
        }
    }
    
    closeModal();
};


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
            console.log(user.value);
        }
    } catch (err) {
        isError.value = true;
    }
    
    isLoading.value = false;
})();

(async () => {
    try {
        cards.value = await axios.get("/api/bank/cards", {data: {"user_id": user.value["user_id"]}});
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
        <div v-show="!cards" class="bank-dashboard">
            <h1 class="text-3xl font-bold mb-4">Your Bank Dashboard</h1>
            <p class="text-xl">Welcome to your personal banking portal. Here you can manage your accounts, view transactions, and more.</p>
            <div class="card-create-container">
                <p class="text-lg">It seems like you don't have a card yet. Lets fix that</p>
                <button @click="openModal" class="card-create-btn">Create a card</button>
            </div>
        </div>
        <div v-show="cards">
            <div v-for="(card, index) in cards" class="cards-box">
                <div :key="index">{{ card["card_type"] }}</div>
            </div>
        </div>

        <!-- Card Creation Modal -->
        <div v-if="showCardCreationModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">Register your card</h2>
                    <button @click="closeModal" class="modal-close-btn">&times;</button>
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
                        <input type="number" id="card-amount" v-model.number="newCard.amount" class="modal-input" min="0" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="createCard" class="modal-create-btn">Create</button>
                </div>
            </div>
        </div>
    </div>
</template>