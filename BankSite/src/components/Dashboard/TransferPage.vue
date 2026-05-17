<script setup>
import { ref } from 'vue';
import axios from "axios";

const props = defineProps({
    cards: Array,
    getCardType: Function
});

const emit = defineEmits([
    "transfer-money"
]);

const inputTimeout = ref(null);
const transferMatchedPhoneNumbers = ref([]);
const transferWindowVisibility = ref(false);
const selectedUser = ref(null);

const transfer = ref({
    amount: 0,
    card_id: null,
});

const startTransfer = (user) => {
    selectedUser.value = user;
    transferWindowVisibility.value = true;
};

const closeTransferWindow = () => {
    transferWindowVisibility.value = false;
    selectedUser.value = null;
    transfer.value = { amount: 0, card_id: null };
};

const proceedTransfer = () => {
    const data = {
        phone_number: selectedUser.value.phone_number,
        ...transfer.value,
    };

    emit("transfer-money", data);
    closeTransferWindow();
};

const findPhoneNumber = (event) => {
    clearTimeout(inputTimeout.value);
    const phoneNumber = event.currentTarget.value;

    if (!/^\d+$/.test(phoneNumber)) {
        transferMatchedPhoneNumbers.value = [];
        return;
    }

    transferMatchedPhoneNumbers.value = transferMatchedPhoneNumbers.value.filter((ph) => ph === phoneNumber);

    inputTimeout.value = setTimeout(async () => {
        try {
            const user = (await axios.get("/api/users/", { params: { phone_number: phoneNumber } }))
                .data.data;

            transferMatchedPhoneNumbers.value.push(user);
        } catch (err) {
            if (axios.isAxiosError(err) && err?.response?.data?.message) {
                transferMatchedPhoneNumbers.value = [];
            } else {
                alert("Something went wrong...");
            }
        } 
    }, 300);
}
</script>

<template>
    <div class="dashboard-transfer">
        <div v-if="!transferWindowVisibility">
            <input @input="findPhoneNumber" class="transfer-recipient-phone-input" type="number"
                id="recipient-phone" placeholder="e.g., +1234567890" />

            <div v-if="transferMatchedPhoneNumbers.length <= 0" class="text-center mt-6">
                <p class="text-gray-500">There is no matching phone number...</p>
            </div>
            <div v-else class="transfer-user-list">
                <div v-for="(user, index) in transferMatchedPhoneNumbers" :key="index" class="transfer-user-item"
                        @click="startTransfer(user)">

                    <p>{{ user.phone_number }}</p>
                    <p>{{ user.first_name }} {{ user.last_name }}</p>
                </div>
            </div>
        </div>
        <div v-else-if="transferWindowVisibility" class="transfer-form">
            <legend class="text-2xl font-bold text-gray-700 mb-4">
                Transfer Information
            </legend>
            <div class="form-group">
                <label for="card-transfer">Choose card:</label>
                <select v-model.number="transfer.card_id" id="card-transfer" class="modal-select">
                    <option v-for="(card, index) in props.cards" :value="`${card.id}`" :key="index">
                        {{ props.getCardType(card) }} ({{ card.card_number }})
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label for="transfer-amount">Amount:</label>
                <input v-model.number="transfer.amount" type="number" id="transfer-amount" class="modal-input" min="1"step="1" placeholder="e.g., 50.00" />
            </div>
            <div class="transfer-form-actions">
                <button @click="closeTransferWindow" class="btn btn-secondary">Close</button>
                <button @click="proceedTransfer" class="btn btn-primary">Transfer</button>
            </div>
        </div>
    </div>
</template>