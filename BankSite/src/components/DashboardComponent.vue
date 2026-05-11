<script setup>
import TransferHelperWindow from './TransferHelperWindow.vue';
import { ref } from 'vue';

const props = defineProps({
  cards: Array,
  currentView: String,
  transactions: Object,
  transferMatchedPhoneNumbers: Array,
});

const emit = defineEmits(
  [
    'view-transactions', 
    'view-dashboard', 
    'view-transfer', 
    'find-phone-number', 
    'open-transfer-modal', 
    'open-deposit-modal', 
    'open-card-creation-modal',
    'transfer-money'
  ]
);


const transferWindowVisibility = ref(false);
const selectedUser = ref(null);

const transfer = ref({
  amount: 0,
  card_id: null 
});

const isNegative = (transaction) => transaction.amount.startsWith("-");

const formatDate = (dateString) => {
  const date = new Date(dateString);
  let formatted = date.toLocaleString("en", { month: "long" }) + " " + date.getDate();

  if (date.getFullYear() < (new Date()).getFullYear()) {
    formatted += ` ${date.getFullYear()}`;
  }

  return formatted;
};

const byDateTime = (transactions) => {
  if (!transactions) {
    return {};
  }
  
  const sortedEntries = Object.entries(transactions).sort(([dateA], [dateB]) => {
    return new Date(dateB) - new Date(dateA);
  });

  return sortedEntries.reduce((acc, [date, transactionList]) => {
    const sortedTransactions = [...transactionList].sort((a, b) => {
      const dateTimeA = new Date(`${date} ${a.time}`);
      const dateTimeB = new Date(`${date} ${b.time}`);
      return dateTimeB - dateTimeA;
    });

    acc[date] = sortedTransactions;
    return acc;
  }, {});
}



const startTransfer = (user) => {
  selectedUser.value = user;
  transferWindowVisibility.value = true;
}

const closeTransferWindow = () => {
  transferWindowVisibility.value = false;
  selectedUser.value = null;
  transfer.value = { amount: 0, card_id: null };
}

const proceedTransfer = () => {
  const data = {
    phone_number: selectedUser.value.phone_number,
    ...transfer.value
  };

  emit('transfer-money', data);
  closeTransferWindow();
}

const getCardType = (card) => card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1);
const getCardBalance = (card) => `${card.amount}`.startsWith("-") ? `-$${card.amount.toString().slice(1)}` : `$${card.amount}`

</script>

<template>
  <div class="dashboard-layout">
    <aside class="dashboard-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-logo">Bank Inc.</h2>
      </div>
      <nav class="sidebar-nav">
        <a href="#" class="sidebar-link" :class="{ 'bg-gray-100 text-blue-600': props.currentView === 'dashboard' }" @click.prevent="emit('view-dashboard')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
          <span>Dashboard</span>
        </a>
        <a href="#" class="sidebar-link" :class="{ 'bg-gray-100 text-blue-600': props.currentView === 'transactions' }" @click.prevent="emit('view-transactions')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
          <span>Transactions</span>
        </a>
        <a href="#" class="sidebar-link" :class="{ 'bg-gray-100 text-blue-600': props.currentView === 'transfer' }" @click.prevent="emit('view-transfer')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
          <span>Transfer</span>
        </a>
        <a href="#" class="sidebar-link" @click.prevent="emit('open-deposit-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" /></svg>
          <span>Deposit</span>
        </a>
      </nav>
    </aside>

    <main class="dashboard-main">
      <header class="dashboard-header">
        <h1 class="header-title">{{ props.currentView.charAt(0).toUpperCase() + props.currentView.slice(1) }}</h1>
        <div class="header-actions" v-if="props.currentView === 'dashboard'">
          <button class="btn btn-secondary" @click="emit('view-transactions')">All Transactions</button>
          <button class="btn btn-primary" @click="emit('open-transfer-modal')">Transfer Money</button>
        </div>
        <div class="relative" v-if="props.currentView === 'transfer'">
          <TransferHelperWindow />
        </div>
      </header>

      <div v-if="props.currentView === 'dashboard'" class="dashboard-grid">
        <div class="dashboard-section">
           <div class="dashboard-card">
            <h2 class="card-title">Register a card</h2>
            <p class="text-gray-600 mb-4">Create your own credit card</p>
            <h2 class="card-title mt-8">Actions</h2>
            <button class="btn btn-primary w-full mb-4" @click="emit('open-card-creation-modal')">Create</button>
          </div>
           <div class="dashboard-card">
            <h2 class="card-title">Bonuses</h2>
            <p class="text-gray-600 mb-4">You have no active bonuses at the moment. Check back later!</p>
            <h2 class="card-title mt-8">Actions</h2>
            <button class="btn btn-primary w-full mb-4" @click="emit('open-deposit-modal')">Deposit Money</button>
          </div>
        </div>
        <div class="dashboard-card col-span-1 lg:col-span-2">
          <h2 class="card-title">Your Cards</h2>
          <div class="cards-box">
            <div v-for="(card, index) in cards" :key="index" class="card-item">
              <div class="card-header">
                  <h3 class="card-type">{{ card.card_type }}</h3>
              </div>
              <div class="card-number">{{ card.card_number }}</div>
              <div class="card-details">
                  <div class="card-balance">Balance: {{ getCardBalance(card) }}</div>
                  <div class="card-expiry">Expires: {{ card.expires_at }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="props.currentView === 'transactions'" class="dashboard-transactions">
        <div v-for="(transactionsForDate, date) in byDateTime(transactions)" :key="date" class="w-full">
          <p class="text-2xl font-bold mb-4">{{ formatDate(date) }}</p>
          <div v-for="(transaction, index) in transactionsForDate" :key="index" class="flex w-full h-fit bg-white p-4 justify-between rounded-2xl shadow-md mb-6">
            <div class="flex flex-col gap-2">
              <p class="text-xl font-semibold">{{ transaction.name }}</p>
              <p class="text-gray-600 italic">{{ transaction.type }}</p>
            </div>
            <div class="flex flex-col gap-2 text-right">
              <p class="text-xl font-semibold" :class="isNegative(transaction) ? 'text-red-600' : 'text-green-600'">{{ transaction.amount }}</p>
              <p class="text-gray-600 italic">{{ transaction.card_type }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="props.currentView === 'transfer'" class="dashboard-transfer">
        <div v-if="!transferWindowVisibility">
          <input @input="emit('find-phone-number', $event)" class="transfer-recipient-phone-input" type="number" id="recipient-phone" placeholder="e.g., +1234567890">
          <div v-if="props.transferMatchedPhoneNumbers.length <= 0" class="text-center mt-6">
            <p class="text-gray-500">There is no matching phone number...</p>
          </div>
          <div v-else class="transfer-user-list">
            <div v-for="(user, index) in props.transferMatchedPhoneNumbers" :key="index" class="transfer-user-item" @click="startTransfer(user)">
              <p>{{ user.phone_number }}</p>
              <p>{{ user.first_name }} {{ user.last_name }}</p>
            </div>
          </div>
        </div>
        <div v-else-if="transferWindowVisibility" class="transfer-form">
          <div class="form-group">
            <label for="card-transfer">Choose card:</label>
            <select v-model.number="transfer.card_id" id="card-transfer" class="modal-select">
              <option v-for="(card, index) in cards" :value="`${card.id}`" :key="index">{{ getCardType(card) }} ({{ card.card_number }})</option>
            </select>
          </div>
          <div class="form-group">
            <label for="transfer-amount">Amount:</label>
            <input v-model.number="transfer.amount" type="number" id="transfer-amount" class="modal-input" min="1" step="1" placeholder="e.g., 50.00"/>
          </div>
          <div class="transfer-form-actions">
            <button @click="closeTransferWindow" class="btn btn-secondary">Close</button>
            <button @click="proceedTransfer" class="btn btn-primary">Transfer</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>