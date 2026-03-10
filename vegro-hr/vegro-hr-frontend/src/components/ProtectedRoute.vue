<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import useAuth from '../hooks/useAuth';

defineOptions({ name: 'ProtectedRoute' });

const router = useRouter();
const { user, isLoading, checkAuth } = useAuth();

const isAllowed = computed(() => Boolean(user.value));

onMounted(async () => {
  if (!user.value) {
    await checkAuth();
  }

  if (!user.value) {
    router.replace('/login');
  }
});
</script>

<template>
  <div v-if="isLoading" class="p-6 text-sm text-slate-400">
    Checking session...
  </div>
  <router-view v-else-if="isAllowed" />
</template>
