<script setup>
import { RouterLink } from "vue-router";
import { ref, computed } from "vue";
import router from "@/router";
import axios from "axios";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faArrowLeft, faArrowRight } from "@fortawesome/free-solid-svg-icons";

import "../assets/auth.css";

const currentActiveLoginStage = ref("phoneNumber");
const loginData = ref({});

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

const login = async () => {
    if (Object.values(loginData.value).some((prop) => !prop || prop === "")) {
        alert("Fields should not be empty");
        return;
    } 

    try {
        await axios.post("/api/users/login", {data: loginData.value});
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

const processLoginStage = async () => {
    const stageName = currentActiveLoginStage.value;
    const stage = stages.value[stageName];

    if (stage.value === "") {
        alert("Field should not be empty");
        return;
    }

    loginData.value = {
        ...loginData.value,
        [stage.id]: stage.value,
    };

    stages.value[stageName].hasEntered = true;

    const newStage = Object.entries(stages.value).find(
        ([key, value]) => !value.hasEntered,
    );

    if (newStage) {
        currentActiveLoginStage.value = newStage[0];
    } else {
        await login();
    }
};

const getPreviousBtnVisibility = computed(() => {
    return currentActiveLoginStage.value !== "phoneNumber";
});

const getNextBtnVisibility = computed(() => {
    const currentStageKey = currentActiveLoginStage.value;
    const stage = stages.value[currentStageKey];
    const stageOrder = Object.keys(stages.value);
    const currentIndex = stageOrder.indexOf(currentStageKey);

    return currentIndex < stageOrder.length - 1 && stage.hasEntered;
});

const changeStage = (event) => {
    const stageOrder = Object.keys(stages.value);
    const currentIndex = stageOrder.indexOf(currentActiveLoginStage.value);
    const currentStageData = stages.value[currentActiveLoginStage.value];
    const newIndex =
        event.currentTarget.id === "previous" ? currentIndex - 1 : currentIndex + 1;

    if (currentStageData.value === "" && newIndex > currentIndex) {
        alert("Field should not be empty");
        return;
    }

    if (newIndex >= 0 && newIndex < stageOrder.length) {
        currentActiveLoginStage.value = stageOrder[newIndex];
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
                :type="stages[currentActiveLoginStage].type" :id="stages[currentActiveLoginStage].id"
                v-model="stages[currentActiveLoginStage].value" placeholder=" " required />
            <label
                class="label peer-focus:inset-s-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-5 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto"
                :for="stages[currentActiveLoginStage].id">
                {{ stages[currentActiveLoginStage].header }}
            </label>
        </div>
        <div>
            <button class="process-btn" @click="processLoginStage">Next</button>
        </div>
    </div>
    <div class="footer">
        <p class="footer-text">© 2026 Your-Bank | <a href="/login">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
    </div>
</template>
