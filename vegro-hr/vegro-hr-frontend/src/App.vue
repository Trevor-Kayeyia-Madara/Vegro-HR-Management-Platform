<script setup>
import { onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import useAuth from './hooks/useAuth';

const { checkAuth } = useAuth();
const route = useRoute();

const shouldCheck = () => route.path.startsWith('/dashboard');

const runCheck = () => {
  if (!shouldCheck()) return;
  const token = localStorage.getItem('vegro_hr_token');
  if (token) {
    checkAuth();
  }
};

onMounted(() => {
  runCheck();
});

watch(
  () => route.fullPath,
  () => {
    runCheck();
  },
);
</script>

<template>
  <router-view />
</template>

<style scoped></style>
