import { createRouter, createWebHistory } from 'vue-router'

import HomeView from "@/views/HomeView.vue";
import SignUpView from '@/views/SignUpView.vue';
import BankView from '@/views/BankView.vue';
import LoginView from '@/views/LoginView.vue';

const routes = [
  { path: "/", component: HomeView },
  { path: "/sign-up", component: SignUpView, meta: { wrapperClass: "auth-wrapper" }},
  { path: "/login", component: LoginView, meta: { wrapperClass: "auth-wrapper" } },
  { path: "/bank", component: BankView },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router
