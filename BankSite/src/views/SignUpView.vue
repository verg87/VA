<script setup>
import { RouterLink } from "vue-router";
import router from "@/router";
import axios from "axios";

import "../assets/auth.css"

const validateFormFields = async (event) => {
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

  const response = await axios.post("http://localhost:8000", {
    type: "sign-up",
    data,
  });

  console.log(response);
  if (response.data.status === "success" && response.data.id) {
    router.push({ path: "/bank" });
  } else {
    alert("Oops something is wrong");
  }
};
</script>

<template>
  <p class="header-1">Register Here</p>
  <div class="panel">
    <p class="header-2">Sign Up</p>
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
        <label for="phone-number">Enter your phone number</label>
        <input type="text" id="phone-number" />
      </div>

      <div class="field">
        <label for="password">Create a password for your account</label>
        <input type="password" id="password" />
      </div>

      <div class="field">
        <label for="password-confirmation">Confirm your password</label>
        <input type="password" id="password-confirmation" />
      </div>

      <button type="submit">Click to register</button>
    </form>
  </div>
  <RouterLink to="/login" class="redirect-btn">Already have an account? Login</RouterLink>
</template>