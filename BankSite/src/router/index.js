import { createRouter, createWebHistory } from 'vue-router'

import HomeView from "@/views/HomeView.vue";
import SignUpView from '@/views/SignUpView.vue';
import BankView from '@/views/BankView.vue';
import LoginView from '@/views/LoginView.vue';

const routes = [
  { path: "/", component: HomeView, meta: {title: "Home"} },
  { path: "/sign-up", component: SignUpView, meta: { wrapperClass: "auth-wrapper", title: "Sign Up" }},
  { path: "/login", component: LoginView, meta: { wrapperClass: "auth-wrapper", title: "Login" } },
  { path: "/bank", component: BankView, meta: {title: "Bank"} },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router
