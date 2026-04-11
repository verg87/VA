<script setup>
import { RouterLink } from "vue-router";

import DropdownComponent from "@/components/DropdownComponent.vue";

import "../assets/home.css";

</script>
<template>
    <div class="home-main blue-gradient">
        <div class="home-navbar">
            <p class="home-navbar-title">Your-Bank</p>
            <div class="home-navbar-extra-sources">
                <a href="#" class="home-link">Product</a>
                <DropdownComponent btn-name="services" :options='["Investing", "Earnings", "Dashboard"]'></DropdownComponent>
                <DropdownComponent btn-name="community" :options='["Forum", "FAQ"]'></DropdownComponent>
                <a href="#" class="home-link">About</a>
            </div>
            <div class="home-link-container">
                <RouterLink to="/login" class="home-link">Login</RouterLink>
                <RouterLink to="/sign-up" class="home-link">Sign up</RouterLink>
            </div>
        </div>
        <div class="home-header-container">
            <p class="home-header-1">Welcome to Your-Bank</p>
            <p class="home-header-2">Your trusted partner in finance.</p>
        </div>
        <div class="home-content">
            <p class="home-text">
                At Your-Bank, we provide secure, reliable, and convenient banking services. 
                Manage your finances with ease, from anywhere in the world. 
                Join us today and experience the future of banking.
            </p>
        </div>
    </div>
    <div class="home-footer">
        <p class="home-footer-text">© 2026 Your-Bank | <a href="#">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
    </div>
</template>