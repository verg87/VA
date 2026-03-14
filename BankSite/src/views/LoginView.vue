<script setup>
import { RouterLink } from "vue-router";

import "../assets/auth.css"
</script>

<template>
    <p class="header-1">Sign In Here</p>
    <div class="panel">
        <p class="header-2">Login</p>
        <form @submit.prevent="validateFormFields" action="" method="post" class="auth">
            <div class="field">
                <label for="name">Enter your name</label>
                <input type="text" id="name" />
            </div>

            <div class="field">
                <label for="lastname">Enter your lastname</label>
                <input type="text" id="lastname" />
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