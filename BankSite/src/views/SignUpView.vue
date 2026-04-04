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
    header: "Enter your email",
    type: "text",
    id: "email",
    hasEntered: false,
    value: ""
  },
  phoneNumber: {
    header: "Enter your phone number",
    type: "text",
    id: "phone-number",
    hasEntered: false,
    value: ""
  },
  firstName: {
    header: "Enter your first name",
    type: "text",
    id: "name",
    hasEntered: false,
    value: ""
  },
  lastName: {
    header: "Enter your last name",
    type: "text",
    id: "lastname",
    hasEntered: false,
    value: ""
  },
  password: {
    header: "Create a password",
    type: "password",
    id: "password",
    hasEntered: false,
    value: ""
  },
  passwordConfirmation: {
    header: "Confirm your password",
    type: "password",
    id: "password-confirmation",
    hasEntered: false,
    value: ""
  }
});

const register = async () => {
  if (Object.values(signUpData.value).some((prop) => !prop || prop === "")) {
    alert("Field shouldn't be empty");
    return;
  } else if (signUpData.value["password"] !== signUpData.value["passwordConfirmation"]) {
    alert("Two passwords aren't matching");
    return;
  }

  const response = await axios.post("/api/users/sign-up", {data: signUpData.value});

  console.log(response);
  if (response.data.status === "success") {
    router.push({ path: "/bank" });
  } else {
    alert("Oops something is wrong");
  }
};

const processSignUpStage = async () => {
  const stageName = currentActiveSignUpStage.value;
  const stage = stages.value[stageName];

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
  const newIndex = event.currentTarget.id === "previous" ? currentIndex - 1 : currentIndex + 1;

  if (newIndex >= 0 && newIndex < stageOrder.length) {
    currentActiveSignUpStage.value = stageOrder[newIndex];
  }
}
</script>

<template>
  <div class="signup-header">
    <FontAwesomeIcon id="previous" :icon="faArrowLeft" v-show="getPreviousBtnVisibility" @click="changeStage"/>
    <p class="header-1">{{ stages[currentActiveSignUpStage].header }}</p>
    <FontAwesomeIcon id="next" :icon="faArrowRight" v-show="getNextBtnVisibility" @click="changeStage"/>
  </div>
  <div class="signup-container-input">
    <input 
      class="signup-input"
      :type="stages[currentActiveSignUpStage].type" 
      :id="stages[currentActiveSignUpStage].id" 
      v-model="stages[currentActiveSignUpStage].value"
    >
    <label class="signup-label" :for="stages[currentActiveSignUpStage].id">Email</label>
  </div>
  <div>
    <button @click="processSignUpStage">Next</button>
  </div>
  <RouterLink to="/login" class="redirect-btn"
    >Already have an account? Login</RouterLink
  >
</template>
