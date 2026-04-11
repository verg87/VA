<script setup>
import router from "@/router";
import { ref } from "vue";

import axios from "axios";

import ServerErrorComponent from "@/components/ServerErrorComponent.vue";
import SkeletonComponent from "@/components/SkeletonComponent.vue";

import "../assets/bank.css";

const isLoading = ref(true);
const isError = ref(false);
const user = ref(null);

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
            user.value = (await axios.post("/api/users/")).data;
        }
    } catch (err) {
        isError.value = true;
    }
    
    isLoading.value = false;
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
                <span>Welcome, {{ user?.data?.first_name }} {{ user?.data?.last_name }}</span>
                <button @click="logOutUser" class="logout-btn">Log Out</button>
            </div>
        </nav>
        <div class="container mx-auto p-4">
            <h1 class="text-3xl font-bold mb-4">Your Bank Dashboard</h1>
            <p>Welcome to your personal banking portal. Here you can manage your accounts, view transactions, and more.</p>
        </div>
    </div>
</template>