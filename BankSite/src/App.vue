<script setup>
import { ref } from "vue";

const validateFormFields = () => console.log(1);
</script>

<template>
  <div class="panel">
    <form @submit.prevent="validateFormFields" action="" method="post" class="sign-up">
      <div class="field">
        <label for="name">Enter your name</label>
        <input type="text" id="name">
      </div>

      <div class="field">
        <label for="lastname">Enter your lastname</label>
        <input type="text" id="lastname">
      </div>
      
      <div class="field">
        <label for="phone-number">Enter your phone number</label>
        <input type="text" id="phone-number">
      </div>

      <div class="field">
        <label for="password">Create a password for your account</label>
        <input type="password" id="password">
      </div>
      
      <div class="field">
        <label for="password-confirmation">Confirm your password</label>
        <input type="password" id="password-confirmation">
      </div>

      <button type="submit">Click to register</button>
    </form>
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.sign-up {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.panel {
  width: fit-content;
  height: fit-content;
  padding: 40px;
  border-width: 2px;
  border-style: solid;
  border-radius: 24px;
}
</style>