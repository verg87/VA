<script setup>
import router from "@/router";
import axios from "axios";

(async () => {
    // const instance = axios.create({
    //     url: "",
    //     method: "post",
    //     baseURL: "http://127.0.0.1:8000/users",
    //     data: {
    //         "bla": "auth",
    //     }
    // });
    const response = await axios.post("http://127.0.0.1:8000/users", {type: "auth", data: {}});
    // const response = await instance.post();
    const status = response?.data?.status;

    // if (status === undefined || status === "error") {
    //     router.push({ path: "/sign-up" });
    // }
    console.log(response);
})();
</script>

<template>
    <p>This is a bank site page. You should be here only after authenticating</p>
</template>