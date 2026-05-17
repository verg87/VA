<script setup>
import { ref } from 'vue';

const isVisible = ref(false);

const toggleVisibility = () => {
  isVisible.value = !isVisible.value;
};
</script>

<template>
  <div class="relative">
    <button class="border-0 bg-gray-300 text-gray-800 rounded-full p-2 px-4.25 shadow-md hover:shadow-lg" @click="toggleVisibility">?</button>
    <div class="flex gap-2 bg-white rounded-2xl w-fit h-fit absolute right-0 top-0 shadow-md px-5 pb-4" v-if="isVisible">
      <div class="flex flex-col text-sm pt-4">
        <p class="w-44">If you don't know to whom you can transfer money, you can use next phone numbers:</p>
        <ul class="list-disc list-inside my-1">
          <li>9077134118</li>
          <li>9078552785</li>
        </ul>
        <p>These are test accounts and not actual people</p>
      </div>
      <button class="h-fit text-2xl pt-2" @click="toggleVisibility">&times;</button>
    </div>
  </div>
</template>