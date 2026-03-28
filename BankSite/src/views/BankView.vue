<script setup>
import router from "@/router";
import { ref } from "vue";

import axios from "axios";

import SkeletonComponent from "@/components/SkeletonComponent.vue";

const isLoading = ref(true);
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
            user.value = (await axios.post("/api/users/")).data.data;
        }
    } catch (err) {
        router.push({ path: "/sign-up" });
        isLoading.value = false;
    }
    
    isLoading.value = false;
})();

console.log(user.value);

const logOutUser = async (event) => {
    try {
        await axios.post("/api/users/log-out", {data: {}});
    } catch (err) {
        if (axios.isAxiosError(err)) {
            if (err.response && err.response.status < 500) {
                router.push({path: "/sign-up"});
                return;
            }
        }

        alert("Something went wrong, sorry. Try later");
        return;
    }
}
</script>

<template>
    <SkeletonComponent v-if="isLoading"/>
    <div v-if="!isLoading" class="all-page">
        <p>This is a bank site page. You should be here only after authenticating</p>

        <button @click="logOutUser">Log out</button>
    </div>
</template>