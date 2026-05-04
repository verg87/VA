<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  cards: Array,
  currentView: String,
});

const emit = defineEmits(['view-transactions', 'view-dashboard', 'open-transfer-modal', 'open-deposit-modal']);

</script>

<template>
  <div class="dashboard-layout">
    <!-- Sidebar -->
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
        <a href="#" class="sidebar-link" @click.prevent="emit('open-transfer-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
          <span>Transfer</span>
        </a>
        <a href="#" class="sidebar-link" @click.prevent="emit('open-deposit-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" /></svg>
          <span>Deposit</span>
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
      <header class="dashboard-header">
        <h1 class="header-title">{{ props.currentView === 'dashboard' ? 'Dashboard' : props.currentView === 'transactions' ? 'Transactions' : '' }}</h1>
        <div class="header-actions" v-if="props.currentView === 'dashboard'">
          <button class="btn btn-secondary" @click="emit('view-transactions')">All Transactions</button>
          <button class="btn btn-primary" @click="emit('open-transfer-modal')">Transfer Money</button>
        </div>
      </header>

      <div v-if="props.currentView === 'dashboard'" class="dashboard-grid">
        <div class="dashboard-card col-span-1 lg:col-span-2">
          <h2 class="card-title">Your Cards</h2>
          <div class="cards-box">
            <div v-for="(card, index) in cards" :key="index" class="card-item">
              <div class="card-header">
                  <h3 class="card-type">{{ card.card_type }}</h3>
                  <!-- logo here -->
              </div>
              <div class="card-number">{{ card.card_number }}</div>
              <div class="card-details">
                  <div class="card-balance">Balance: ${{ card.amount.toFixed(2) }}</div>
                  <div class="card-expiry">Expires: {{ card.expires_at }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="dashboard-card">
          <h2 class="card-title">Bonuses</h2>
          <p class="text-gray-600 mb-4">You have no active bonuses at the moment. Check back later!</p>
          <h2 class="card-title mt-8">Actions</h2>
          <button class="btn btn-primary w-full mb-4" @click="emit('open-deposit-modal')">Deposit Money</button>
        </div>
      </div>

      <div v-else-if="props.currentView === 'transactions'" class="dashboard-transactions">
        <h2 class="header-title">Transactions</h2>
        <p>This is where your transaction history will be displayed.</p>
        <!-- Transaction list or filter components will go here -->
      </div>
    </main>
  </div>
</template>