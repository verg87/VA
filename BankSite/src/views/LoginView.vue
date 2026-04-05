<script setup>
import { RouterLink } from "vue-router";
import { ref, computed } from "vue";
import router from "@/router";
import axios from "axios";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faArrowLeft, faArrowRight } from "@fortawesome/free-solid-svg-icons";

import "../assets/auth.css";

const currentActiveSignUpStage = ref("phoneNumber");
const signUpData = ref({});

const stages = ref({
    phoneNumber: {
        header: "Phone number",
        type: "text",
        id: "phone-number",
        hasEntered: false,
        value: "",
    },
    password: {
        header: "Password",
        type: "password",
        id: "password",
        hasEntered: false,
        value: "",
    },
});

const register = async () => {
    if (Object.values(signUpData.value).some((prop) => !prop || prop === "")) {
        alert("Fields shouldn't be empty");
        return;
    } 

    try {
        await axios.post("/api/users/login", {data});
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
};

const processSignUpStage = async () => {
    const stageName = currentActiveSignUpStage.value;
    const stage = stages.value[stageName];

    signUpData.value = {
        ...signUpData.value,
        [stage.id]: stage.value,
    };

    stages.value[stageName].hasEntered = true;

    const newStage = Object.entries(stages.value).find(
        ([key, value]) => !value.hasEntered,
    );

    if (newStage) {
        currentActiveSignUpStage.value = newStage[0];
    } else {
        await register();
    }
};

const getPreviousBtnVisibility = computed(() => {
    return currentActiveSignUpStage.value !== "phoneNumber";
});

const getNextBtnVisibility = computed(() => {
    const currentStageKey = currentActiveSignUpStage.value;
    const stage = stages.value[currentStageKey];
    const stageOrder = Object.keys(stages.value);
    const currentIndex = stageOrder.indexOf(currentStageKey);

    return currentIndex < stageOrder.length - 1 && stage.hasEntered;
});

const changeStage = (event) => {
    const stageOrder = Object.keys(stages.value);
    const currentIndex = stageOrder.indexOf(currentActiveSignUpStage.value);
    const newIndex =
        event.currentTarget.id === "previous" ? currentIndex - 1 : currentIndex + 1;

    if (newIndex >= 0 && newIndex < stageOrder.length) {
        currentActiveSignUpStage.value = stageOrder[newIndex];
    }
};
</script>

<template>
    <div class="main">
        <div class="navbar">
            <FontAwesomeIcon id="previous" :icon="faArrowLeft" v-show="getPreviousBtnVisibility" @click="changeStage" />
            <RouterLink to="/" class="link" v-show="!getPreviousBtnVisibility">Home</RouterLink>
            <RouterLink to="/sign-up" class="link" v-show="!getNextBtnVisibility">Sign up</RouterLink>
            <FontAwesomeIcon id="next" :icon="faArrowRight" v-show="getNextBtnVisibility" @click="changeStage" />
        </div>
        <div class="header-container">
            <p class="header-1">Login to your-bank</p>
        </div>
        <div class="relative z-0 w-100 mb-6">
            <input class="input focus:outline-none focus:ring-0 focus:border-brand peer"
                :type="stages[currentActiveSignUpStage].type" :id="stages[currentActiveSignUpStage].id"
                v-model="stages[currentActiveSignUpStage].value" placeholder=" " required />
            <label
                class="label peer-focus:inset-s-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-5 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto"
                :for="stages[currentActiveSignUpStage].id">
                {{ stages[currentActiveSignUpStage].header }}
            </label>
        </div>
        <div>
            <button class="process-btn" @click="processSignUpStage">Next</button>
        </div>
    </div>
    <div class="footer">
        <p class="footer-text">© 2026 Your-Bank | <a href="/login">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
    </div>
</template>
