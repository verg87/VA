<script setup>
import { useRoute } from 'vue-router';
const route = useRoute();
</script>

<template>
  <div :class="route.meta.wrapperClass">
    <router-view/>
  </div>
</template>

<style>
.auth-wrapper {
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
}
/* @layer components {
  .auth-wrapper {
    @apply flex flex-col items-center h-fit
  }
} */
</style>