<script setup>
import router from "@/router";
import axios from "axios";

(async () => {
    try {
        await axios.post("/api/users/auth", {data: {}});
    } catch (err) {
        try {
            await axios.post("/api/users/refresh-token", {data: {}});
        } catch (e) {
            router.push({ path: "/sign-up" });
        }
    }
})();

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
    <p>This is a bank site page. You should be here only after authenticating</p>

    <button @click="logOutUser">Log out</button>
</template>