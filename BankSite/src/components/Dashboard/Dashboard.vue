<script setup>
import { ref, computed } from "vue";

import TransferHelperWindow from "./TransferHelperWindow.vue";

import CardCreationSection from "./CardCreationSection.vue";
import BonusesSection from "./BonusesSection.vue";
import AccountChangerSection from "./AccountChangerSection.vue";

import TransferPage from "./TransferPage.vue";
import DepositModal from "./DepositModal.vue";

const props = defineProps({
  cards: Array,
  currentView: String,
  transactions: Object,
  transferMatchedPhoneNumbers: Array,
  transferMoney: Function,
});

const emit = defineEmits([
  "view-transactions",
  "view-dashboard",
  "view-transfer",
  "find-phone-number",
  "open-transfer-modal",
  "transfer-money",
]);

const showDepositModal = ref(false);

const deposit = ref({
    type: "",
    amount: 0, 
    card_id: null
});

const openDepositModal = () => {
  showDepositModal.value = true;
};

const closeDepositModal = () => {
  showDepositModal.value = false;
  deposit.value = { type: "", amount: 0, card_id: null };
};

const getAmountColor = (transaction) =>
  transaction.amount.startsWith("-")
    ? "text-red-600"
    : transaction.amount.startsWith("+")
      ? "text-green-600"
      : "";

const hasMainCard = computed(() => {
  return (
    props.cards !== null &&
    props.cards.filter((card) => card["is_main"]).length > 0
  );
});

const formatDate = (dateString) => {
  const date = new Date(dateString);
  let formatted =
    date.toLocaleString("en", { month: "long" }) + " " + date.getDate();

  if (date.getFullYear() < new Date().getFullYear()) {
    formatted += ` ${date.getFullYear()}`;
  }

  return formatted;
};

const byDateTime = (transactions) => {
  if (!transactions) {
    return {};
  }

  const sortedEntries = Object.entries(transactions).sort(
    ([dateA], [dateB]) => {
      return new Date(dateB) - new Date(dateA);
    },
  );

  return sortedEntries.reduce((acc, [date, transactionList]) => {
    const sortedTransactions = [...transactionList].sort((a, b) => {
      const dateTimeA = new Date(`${date} ${a.time}`);
      const dateTimeB = new Date(`${date} ${b.time}`);
      return dateTimeB - dateTimeA;
    });

    acc[date] = sortedTransactions;
    return acc;
  }, {});
};

const getCardType = (card) =>
  card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1);

const getCardBalance = (card) =>
  `${card.amount}`.startsWith("-")
    ? `-$${card.amount.toString().slice(1)}`
    : `$${card.amount}`;

const depositMoney = async () => {
  const data = {
    "user_id": user.value.id,
    "type": deposit.value.type,
    "amount": deposit.value.amount,
    "card_id": deposit.value.card_id
  };

  console.log(data);

  try {
    await axios.post("/api/bank/deposit", { data });
  } catch (err) {
    if (axios.isAxiosError(err) && err?.response?.data?.message) {
      alert(err.response.data.message);
    } else {
      alert("Something went wrong...");
    }
  }

  closeDepositModal();
}
</script>

<template>
  <div class="dashboard-layout">
    <aside class="dashboard-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-logo">Bank Inc.</h2>
      </div>
      <nav class="sidebar-nav">
        <a href="#" class="sidebar-link" :class="{
          'bg-gray-100 text-blue-600': props.currentView === 'dashboard',
        }" @click.prevent="emit('view-dashboard')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
          <span>Dashboard</span>
        </a>
        <a href="#" class="sidebar-link" :class="{
          'bg-gray-100 text-blue-600': props.currentView === 'transactions',
        }" @click.prevent="emit('view-transactions')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <span>Transactions</span>
        </a>
        <a href="#" class="sidebar-link" :class="{
          'bg-gray-100 text-blue-600': props.currentView === 'transfer',
        }" @click.prevent="emit('view-transfer')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
          </svg>
          <span>Transfer</span>
        </a>
        <a href="#" class="sidebar-link" @click.prevent="openDepositModal">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
          </svg>
          <span>Deposit</span>
        </a>
      </nav>
    </aside>

    <main class="dashboard-main">
      <header class="dashboard-header">
        <h1 class="header-title">
          {{
            props.currentView.charAt(0).toUpperCase() +
            props.currentView.slice(1)
          }}
        </h1>
        <div class="header-actions" v-if="props.currentView === 'dashboard'">
          <button class="btn btn-secondary" @click="emit('view-transactions')">
            All Transactions
          </button>
          <button class="btn btn-primary" @click="emit('open-transfer-modal')">
            Transfer Money
          </button>
        </div>
        <div class="relative" v-if="props.currentView === 'transfer'">
          <TransferHelperWindow />
        </div>
      </header>

      <div v-if="props.currentView === 'dashboard'" class="dashboard-grid">
        <div class="dashboard-section">
          <CardCreationSection></CardCreationSection>
          <BonusesSection @open-deposit-modal="openDepositModal"></BonusesSection>
          <AccountChangerSection :cards="props.cards" :getCardType="getCardType"></AccountChangerSection>
        </div>
        <div class="dashboard-card col-span-1 lg:col-span-2">
          <h2 class="card-title">Your Cards</h2>
          <div class="cards-box">
            <div v-for="(card, index) in cards" :key="index" class="card-item">
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
      </div>

      <div v-else-if="props.currentView === 'transactions'" class="dashboard-transactions">
        <div v-for="(transactionsForDate, date) in byDateTime(transactions)" :key="date" class="w-full">
          <p class="text-2xl font-bold mb-4">{{ formatDate(date) }}</p>
          <div v-for="(transaction, index) in transactionsForDate" :key="index"
            class="flex w-full h-fit bg-white p-4 justify-between rounded-2xl shadow-md mb-6">
            <div class="flex flex-col gap-2">
              <p class="text-xl font-semibold">{{ transaction.name }}</p>
              <p class="text-gray-600 italic">{{ transaction.type }}</p>
            </div>
            <div class="flex flex-col gap-2 text-right">
              <p class="text-xl font-semibold" :class="getAmountColor(transaction)">
                {{ transaction.amount }}
              </p>
              <p class="text-gray-600 italic">{{ transaction.card_type }}</p>
            </div>
          </div>
        </div>
      </div>

      <TransferPage 
        v-else-if="props.currentView === 'transfer' && hasMainCard"
        :cards="props.cards"
        :getCardType="getCardType"
        @transfer-money="props.transferMoney"
      />
      <div v-else-if="props.currentView === 'transfer' && !hasMainCard">
        <p class="text-center text-lg font-semibold">
          You need to register a card, either credit or debit to transfer money
        </p>
      </div>
      <DepositModal
        :cards="props.cards"
        :deposit="deposit"
        :showDepositModal="showDepositModal"
        :getCardType="getCardType"
        @close-deposit-modal="closeDepositModal"
        @deposit-money="depositMoney"
      />
    </main>
  </div>
</template>
