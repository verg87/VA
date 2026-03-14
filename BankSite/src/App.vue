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
  justify-content: center;
  align-items: center;
}
</style>