<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import router from "@/router";
import axios from "axios";

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faArrowRight } from "@fortawesome/free-solid-svg-icons";

import { getPhoneMaskInstance, initiate, validatePhoneInput } from "@/general/phoneInputCommon";

import "../assets/auth.css";

const isLoading = ref(false);

const formData = ref({
  email: "",
  phoneNumber: "+",
  firstName: "",
  lastName: "",
  password: "",
  passwordConfirmation: "",
});

const steps = ['email', 'phoneNumber', 'firstName', 'lastName', 'password', 'passwordConfirmation'];
const currentStepIndex = ref(0);
const currentStep = computed(() => steps[currentStepIndex.value]);

const isFirstStep = computed(() => currentStepIndex.value === 0);
const isLastStep = computed(() => currentStepIndex.value === steps.length - 1);

const canShowForwardArrow = () => {
  const nextField = steps[currentStepIndex.value + 1] ?? null;

  return nextField && 
    currentStepIndex.value === steps.length - 1 && formData.value[nextField] !== "" ||
    (nextField === "phoneNumber" && formData.value[nextField] !== "+");
}

const phoneInputRef = ref(null);
let phoneMask;

const setupPhoneMask = () => {
  if (phoneInputRef.value && !phoneMask) {
    phoneMask = getPhoneMaskInstance(phoneInputRef);
    initiate(phoneMask);
    phoneMask.value = formData.value.phoneNumber;

    phoneMask.on('accept', () => {
      formData.value.phoneNumber = phoneMask.value;
      validatePhoneInput(phoneMask);
    });
  }
}

watch(currentStep, async (newStep) => {
  if (newStep === 'phoneNumber') {
    await nextTick();
    setupPhoneMask();
  }
});

const nextStep = () => {
  const currentField = currentStep.value;
  const currentValue = formData.value[currentField];

  if (!currentValue || (currentField === 'phoneNumber' && phoneMask && phoneMask.unmaskedValue.length < 5)) {
    alert("Field is empty or invalid. Please fill it out before proceeding.");
    return;
  }

  if (!isLastStep.value) {
    currentStepIndex.value++;
  } else {
    register();
  }
};

const prevStep = () => {
  if (!isFirstStep.value) {
    currentStepIndex.value--;
  }
};

const register = async () => {
  const data = formData.value;
  if (Object.values(data).some(prop => !prop || prop === "")) {
    alert("All fields are required.");
    return;
  }
  if (data.password !== data.passwordConfirmation) {
    alert("Passwords do not match.");
    return;
  }

  isLoading.value = true;
  try {
    const apiData = {
      "email": data.email,
      "phone-number": data.phoneNumber.replaceAll(/\D/g, ""),
      "name": data.firstName,
      "lastname": data.lastName,
      "password": data.password,
      "password-confirmation": data.passwordConfirmation,
    };
    
    await axios.post("/api/users/sign-up", { data: apiData });
    router.push({ path: "/bank" });

  } catch (err) {
    if (axios.isAxiosError(err) && err.response && err.response.status < 500) {
      alert(err.response.data.message || "An error occurred.");
    } else {
      alert("Something went wrong...");
    }
  } finally {
    isLoading.value = false;
  }
};

const labels = {
  email: "Email address",
  phoneNumber: "Phone number",
  firstName: "First name",
  lastName: "Last name",
  password: "Password",
  passwordConfirmation: "Confirm password",
};

</script>

<template>
  <div class="main">
    <div class="navbar">
      <FontAwesomeIcon id="previous" :icon="faArrowLeft" v-show="!isFirstStep" @click="prevStep" />
      <RouterLink to="/" class="link" v-show="isFirstStep">Home</RouterLink>
      <RouterLink to="/login" class="link" v-show="!canShowForwardArrow()">Login</RouterLink>
      <FontAwesomeIcon id="next" :icon="faArrowRight" v-show="canShowForwardArrow()" @click="nextStep" />
    </div>
    <div class="header-container">
      <p class="header-1">Register to your-bank</p>
    </div>

    <div class="relative z-0 w-100 mb-6">
      <input v-if="currentStep === 'email'" type="email" v-model="formData.email" @keyup.enter="nextStep" id="email" class="input peer" placeholder=" " required />
      <input v-if="currentStep === 'phoneNumber'" type="tel" v-model="formData.phoneNumber" @keyup.enter="nextStep" id="phoneNumber" class="input peer" placeholder=" " required ref="phoneInputRef" />
      <input v-if="currentStep === 'firstName'" type="text" v-model="formData.firstName" @keyup.enter="nextStep" id="firstName" class="input peer" placeholder=" " required />
      <input v-if="currentStep === 'lastName'" type="text" v-model="formData.lastName" @keyup.enter="nextStep" id="lastName" class="input peer" placeholder=" " required />
      <input v-if="currentStep === 'password'" type="password" v-model="formData.password" @keyup.enter="nextStep" id="password" class="input peer" placeholder=" " required />
      <input v-if="currentStep === 'passwordConfirmation'" type="password" v-model="formData.passwordConfirmation" @keyup.enter="nextStep" id="passwordConfirmation" class="input peer" placeholder=" " required />

      <label 
        class="label peer-focus:inset-s-0 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-5 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto"
        :for="currentStep"
      >
        {{ labels[currentStep] }}
      </label>
    </div>

    <div>
      <button class="process-btn" @click="nextStep" :disabled="isLoading">
        <span v-if="isLoading">Processing...</span>
        <span v-else>{{ isLastStep ? 'Sign Up' : 'Next' }}</span>
      </button>
    </div>
  </div>
  <div class="footer">
    <p class="footer-text">© 2026 Your-Bank | <a href="#">Terms of service</a> | <a href="https://github.com/verg87">See More...</a> | English</p>
  </div>
</template>
