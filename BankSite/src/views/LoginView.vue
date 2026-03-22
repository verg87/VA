<script setup>
import { RouterLink } from "vue-router";
import router from "@/router";
import axios from "axios";

import "../assets/auth.css";

const validateLoginFormFields = async (event) => {
    let data = {};

    for (const element of event.target) {
        if (element.tagName.toLowerCase() === "input") {
            data = {
                ...data,
                [element.id]: element.value,
            };
        }
    }

    if (Object.entries(data).some(([_, value]) => value === "")) {
        alert("Fields shouldn't be empty");
        return;
    }

    try {
        await axios.post("/api/users", {
            type: "login",
            data
        });
    } catch (err) {
        if (axios.isAxiosError(err)) {
            if (err.response && err.response.status < 500) {
                alert("Invalid credentials");
                return;
            }
        }
        
        alert("Something went wrong...");
        return;
    }

    router.push({ path: "/bank" });
}
</script>

<template>
    <p class="header-1">Sign In Here</p>
    <div class="panel">
        <p class="header-2">Login</p>
        <form @submit.prevent="validateLoginFormFields" action="" method="post" class="auth">
            <div class="field">
                <label for="phone-number">Enter your phone number</label>
                <input type="text" id="phone-number" />
            </div>

            <div class="field">
                <label for="password">Enter your password</label>
                <input type="password" id="password" />
            </div>

            <button type="submit">Click to login</button>
        </form>
    </div>
    <RouterLink to="/sign-up" class="redirect-btn">Dont have an account? Sign Up</RouterLink>
</template>