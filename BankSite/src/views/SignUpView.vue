<script setup>
import { RouterLink } from "vue-router";
import { ref, computed } from "vue";
import router from "@/router";
import axios from "axios";

import "../assets/auth.css";

const currentActiveSignUpStage = ref("email");
const signUpData = ref({});

const validateSignUpFormFields = async (event) => {
  let data = {};

  for (const element of event.target) {
    if (element.tagName.toLowerCase() === "input") {
      data = {
        ...data,
        [element.id]: element.value,
      };
    }
  }

  if (data["password"] !== data["password-confirmation"]) {
    alert("Two password aren't matching");
    return;
  }

  if (Object.entries(data).some(([_, value]) => value === "")) {
    alert("Fields shouldn't be empty");
    return;
  }

  const response = await axios.post("/api/users/sign-up", {data});

  console.log(response);
  if (response.data.status === "success") {
    router.push({ path: "/bank" });
  } else {
    alert("Oops something is wrong");
  }
};

const stages = ref({
  email: {
    headerText: "Enter your email",
    inputType: "text",
    inputId: "email",
    hasEntered: false,
  },
  phoneNumber: {
    headerText: "Enter your phone number",
    inputType: "text",
    inputId: "phone-number",
    hasEntered: false,
  },
  names: {
    headerText: "Enter your first and last name",
    first: {
      inputType: "text",
      inputId: "name"
    },
    last: {
      inputType: "text",
      inputId: "lastname",
    },
    hasEntered: false,
  },
  password: {
    headerText: "Create a password",
    inputType: "password",
    inputId: "password",
    hasEntered: false,
  },
  passwordConfirmation: {
    headerText: "Confirm your password",
    inputType: "password",
    inputId: "password-confirmation",
    hasEntered: false,
  }
});

const processSignUpStage = () => {
  const stageName = currentActiveSignUpStage.value;
  let currentStageInputsValid = true;

  if (stageName === "names") {
    const firstNameField = document.getElementById(stages.value.names.first.inputId);
    const lastNameField = document.getElementById(stages.value.names.last.inputId);

    if (!firstNameField.value || !lastNameField.value) {
      alert("First name and last name shouldn't be empty");
      currentStageInputsValid = false;
    } else {
      signUpData.value = {
        ...signUpData.value,
        [firstNameField.id]: firstNameField.value,
        [lastNameField.id]: lastNameField.value,
      };
    }
  } else {
    const id = stages.value[stageName].inputId;
    const field = document.getElementById(id);

    if (!field || !field.value) {
      alert("Field shouldn't be empty");
      currentStageInputsValid = false;
    } else if (
      id === "password-confirmation" &&
      field.value !== signUpData.value["password"]
    ) {
      alert("Two passwords aren't matching");
      currentStageInputsValid = false;
    } else {
      signUpData.value = {
        ...signUpData.value,
        [id]: field.value,
      };
    }
  }

  if (!currentStageInputsValid) {
    return;
  }
  
  stages.value[stageName].hasEntered = true;

  const newStage = Object.entries(stages.value)
    .find(([key, value]) => !value.hasEntered);

  if (newStage) {
    currentActiveSignUpStage.value = newStage[0];
  } else {
    console.log("Sign Up process complete");
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

  // The 'Next' button should be visible until the last stage is processed and ready to submit
  return currentIndex < stageOrder.length - 1 && stage.hasEntered;
});

const changeStage = (event) => {
  const currentStageKey = currentActiveSignUpStage.value;
  const stageOrder = Object.keys(stages.value);
  const currentIndex = stageOrder.indexOf(currentStageKey);

  if (currentIndex - 1 >= 0 && event.target.id === "previous") {
    currentActiveSignUpStage.value = stageOrder[currentIndex - 1];
  } else if (currentIndex + 1 <= stageOrder.length && event.target.id === "next") {
    currentActiveSignUpStage.value = stageOrder[currentIndex + 1];
  }
}

const getFields = computed(() => {
  const stage = stages.value[currentActiveSignUpStage.value];
  const fields = Object.values(stage).filter((prop) => typeof prop === "object");

  return fields.length ? fields : [stage];
});
</script>

<template>
  <div style="display: flex; justify-content: space-between; width: fit-content">
    <button id="previous" v-show="getPreviousBtnVisibility" @click="changeStage">*back image</button>
    <p class="header-1">{{ stages[currentActiveSignUpStage].headerText }}</p>
    <button id="next" v-show="getNextBtnVisibility" @click="changeStage">*forward image</button>
  </div>
  <div v-for="item in getFields">
    <div class="field">
      <input :type=item.inputType :id=item.inputId>
    </div>
  </div>
  <div>
    <button @click="processSignUpStage">Next</button>
  </div>
  <RouterLink to="/login" class="redirect-btn"
    >Already have an account? Login</RouterLink
  >
</template>
