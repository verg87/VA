<script setup>
import router from "@/router";
import axios from "axios";

(async () => {
    try {
        await axios.post("/api/users", {type: "auth", data: {}});
    } catch (err) {
        router.push({ path: "/sign-up" });
    }
})();
</script>

<template>
    <p>This is a bank site page. You should be here only after authenticating</p>
</template>