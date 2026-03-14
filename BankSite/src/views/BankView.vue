<script setup>
import router from "@/router";
import axios from "axios";

(async () => {
    const response = await axios.post("http://localhost:8000/src/api.php", {type: "auth", data: {}});

    if (response.data.status === "error") {
        router.push({ path: "/sign-up" });
    }
})();
</script>

<template>
    <p>This is a bank site page. You should be here only after authenticating</p>
</template>