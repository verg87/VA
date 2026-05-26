<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import router from "@/router";
import axios from "axios";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faArrowLeft } from "@fortawesome/free-solid-svg-icons";

import { getPhoneMaskInstance, initiate, validatePhoneInput } from "@/general/phoneInputCommon";

import "../assets/auth.css";

const step = ref("phone"); 
const phoneNumber = ref("+"); 
const password = ref("");
const isLoading = ref(false);

const phoneInputRef = ref(null);
let phoneMask;

const setupPhoneMask = () => {
    if (phoneInputRef.value && !phoneMask) {
        phoneMask = getPhoneMaskInstance(phoneInputRef);
        initiate(phoneMask);

        phoneMask.value = phoneNumber.value; 

        phoneMask.on('accept', () => {
            phoneNumber.value = phoneMask.value;
            validatePhoneInput(phoneMask); 
        });
    }
}

watch(step, async (newStep) => {
    if (newStep === 'phone') {
        await nextTick(); 
        setupPhoneMask();
    }
});

onMounted(setupPhoneMask);

const goToPasswordStep = () => {
    if (!phoneMask || phoneMask.unmaskedValue.length < 5) {
        alert("Please enter a valid phone number.");
        return;
    }
    step.value = "password";
};

const goToPhoneStep = () => {
    step.value = "phone";
};

const login = async () => {
    if (!password.value) {
        alert("Password should not be empty");
        return;
    }
    
    isLoading.value = true;

    try {
        const loginData = {
            "phone-number": phoneNumber.value.replaceAll(/\D/g, ""),
            "password": password.value
        };

        await axios.post("/api/users/login", { data: loginData });
        router.push({ path: "/bank" });
    } catch (err) {
        if (axios.isAxiosError(err) && err.response && err.response.status < 500) {
            alert("Invalid credentials");
        } else {
            alert("Something went wrong...");
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="main">
        <div class="navbar">
            <FontAwesomeIcon id="previous" :icon="faArrowLeft" v-show="step === 'password'" @click="goToPhoneStep" />
            <RouterLink to="/" class="link" v-show="step === 'phone'">Home</RouterLink>
            <RouterLink to="/sign-up" class="link">Sign up</RouterLink>
        </div>

        <div class="header-container">
            <p class="header-1">Login to your-bank</p>
        </div>

        <div v-if="step === 'phone'" class="w-full place-items-center">
            <div class="relative z-0 w-100 mb-6">
                <input class="input focus:outline-none focus:ring-0 focus:border-brand peer"
                       type="tel"
                       id="phone-number"
                       v-model="phoneNumber"
                       placeholder=" "
                       required
                       ref="phoneInputRef" />
                <label
                    class="label peer-focus:inset-s-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-5 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto"
                    for="phone-number">
                    Phone number
                </label>
            </div>
            <div>
                <button class="process-btn" @click="goToPasswordStep">Next</button>
            </div>
        </div>

        <div v-if="step === 'password'" class="w-full place-items-center">
            <div class="relative z-0 w-100 mb-6">
                <input class="input focus:outline-none focus:ring-0 focus:border-brand peer"
                       type="password"
                       id="password"
                       v-model="password"
                       placeholder=" "
                       required
                       @keyup.enter="login" />
                <label
                    class="label peer-focus:inset-s-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-5 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto"
                    for="password">
                    Password
                </label>
            </div>
            <div>
                <button class="process-btn" @click="login" :disabled="isLoading">
                    {{ isLoading ? 'Logging in...' : 'Login' }}
                </button>
            </div>
        </div>
    </div>

    <div class="footer">
        <p class="footer-text">© 2026 Your-Bank | <a href="#">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
    </div>
</template>
