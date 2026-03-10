<script setup>
import { onMounted } from 'vue';
import useAuth from './hooks/useAuth';

const { checkAuth } = useAuth();

onMounted(() => {
  checkAuth();
});
</script>

<template>
  <router-view />
</template>

<style scoped></style>
