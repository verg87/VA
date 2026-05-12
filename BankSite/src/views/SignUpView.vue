<script setup>
import { RouterLink } from "vue-router";
import { ref, computed } from "vue";
import router from "@/router";
import axios from "axios";

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faArrowRight } from "@fortawesome/free-solid-svg-icons";

import "../assets/auth.css";

const currentActiveSignUpStage = ref("email");
const signUpData = ref({});

const stages = ref({
  email: {
    header: "Email address",
    type: "text",
    id: "email",
    hasEntered: false,
    value: ""
  },
  phoneNumber: {
    header: "Phone number",
    type: "text",
    id: "phone-number",
    hasEntered: false,
    value: ""
  },
  firstName: {
    header: "First name",
    type: "text",
    id: "name",
    hasEntered: false,
    value: ""
  },
  lastName: {
    header: "Last name",
    type: "text",
    id: "lastname",
    hasEntered: false,
    value: ""
  },
  password: {
    header: "Password",
    type: "password",
    id: "password",
    hasEntered: false,
    value: ""
  },
  passwordConfirmation: {
    header: "Confirm password",
    type: "password",
    id: "password-confirmation",
    hasEntered: false,
    value: ""
  }
});

const register = async () => {
  if (Object.values(signUpData.value).some((prop) => !prop || prop === "")) {
    alert("Fields should not be empty");
    return;
  } else if (signUpData.value["password"] !== signUpData.value["password-confirmation"]) {
    alert("Two passwords aren't matching");
    return;
  }

  let response;

  try {
    response = await axios.post("/api/users/sign-up", { data: signUpData.value });
  } catch (err) {
    if (axios.isAxiosError(err)) {
      if (err.response && err.response.status < 500) {
        alert(err.response.data.message);
        return;
      }
    }
        
    alert("Something went wrong...");
    return;
  }
  
  if (response.data.status === "success") {
    router.push({ path: "/bank" });
  }
};

const processSignUpStage = async () => {
  const stageName = currentActiveSignUpStage.value;
  const stage = stages.value[stageName];

  if (stage.value === "") {
    alert("Field should not be empty");
    return;
  }

  signUpData.value = {
    ...signUpData.value,
    [stage.id]: stage.value,
  };

  stages.value[stageName].hasEntered = true;

  const newStage = Object.entries(stages.value)
    .find(([key, value]) => !value.hasEntered);

  if (newStage) {
    currentActiveSignUpStage.value = newStage[0];
  } else {
    await register();
  }
}

const getPreviousBtnVisibility = computed(() => {
  return currentActiveSignUpStage.value !== "email";
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
  const currentStageData = stages.value[currentActiveSignUpStage.value];
  const newIndex = event.currentTarget.id === "previous" ? currentIndex - 1 : currentIndex + 1;

  if (currentStageData.value === "" && newIndex > currentIndex) {
    alert("Field should not be empty");
    return;
  }

  if (newIndex >= 0 && newIndex < stageOrder.length) {
    currentActiveSignUpStage.value = stageOrder[newIndex];
  }
}
</script>

<template>
  <div class="main">
    <div class="navbar">
      <FontAwesomeIcon id="previous" :icon="faArrowLeft" v-show="getPreviousBtnVisibility" @click="changeStage" />
      <RouterLink to="/" class="link" v-show="!getPreviousBtnVisibility">Home</RouterLink>
      <RouterLink to="/login" class="link" v-show="!getNextBtnVisibility">Login</RouterLink>
      <FontAwesomeIcon id="next" :icon="faArrowRight" v-show="getNextBtnVisibility" @click="changeStage" />
    </div>
    <div class="header-container">
      <p class="header-1">Register to your-bank</p>
    </div>
    <div class="relative z-0 w-100 mb-6">
      <input class="input focus:outline-none focus:ring-0 focus:border-brand peer"
        :type="stages[currentActiveSignUpStage].type" :id="stages[currentActiveSignUpStage].id"
        v-model.trim="stages[currentActiveSignUpStage].value" placeholder=" " required>
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
    <p class="footer-text">© 2026 Your-Bank | <a href="#">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
  </div>
</template>
