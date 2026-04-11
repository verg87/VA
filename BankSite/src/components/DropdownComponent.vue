<script setup>
import '../assets/dropdown.css';

const props = defineProps({
  btnName: String,
  options: Array,
});

const showDropdown = (event) => {
    const dropdownIdToShow = event.currentTarget.getAttribute("data-dropdown-toggle");
    const dropdownToShow = document.getElementById(dropdownIdToShow);

    if (dropdownToShow.classList.contains("hidden")) {
        dropdownToShow.classList.remove("hidden");
    } else {
        dropdownToShow.classList.add("hidden");
    }
}

</script>

<template>
    <div class="dropdown-container">
        <button @click="showDropdown" :id="`${btnName}-dropdown-button`" :data-dropdown-toggle="`${btnName}-dropdown`" class="dropdown-button" type="button">
            {{ btnName }}
            <svg class="dropdown-arrow" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
            </svg>
        </button>

        <div :id="`${btnName}-dropdown`" class="dropdown-menu hidden">
            <ul class="dropdown-list" :aria-labelledby="`${btnName}-dropdown-button`">
                <li v-for="(option, index) in props.options" :key="index">
                    <a href="#" class="dropdown-item">{{ option }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>