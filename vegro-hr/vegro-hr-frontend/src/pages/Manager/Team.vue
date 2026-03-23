<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'ManagerTeamPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const teamMembers = ref([]);
const searchQuery = ref('');

const unwrapList = (response) => {
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

const loadTeam = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/employees/my-department');
    teamMembers.value = unwrapList(response);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load team members.';
  } finally {
    isLoading.value = false;
  }
};

const filteredTeam = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return teamMembers.value;
  return teamMembers.value.filter((member) => {
    const name = member?.name?.toLowerCase() || '';
    const email = member?.email?.toLowerCase() || '';
    const role = (member?.roles || []).join(' ').toLowerCase();
    return name.includes(query) || email.includes(query) || role.includes(query);
  });
});

onMounted(loadTeam);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Manager</p>
          <h1 class="text-3xl font-semibold">Department Team</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Employees in your managed department.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search team..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="loadTeam"
          >
            Refresh
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="max-h-[72vh] overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-[640px] w-full text-left text-xs sm:text-sm">
              <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
                <tr>
                  <th class="px-6 py-4 font-medium">Employee</th>
                  <th class="px-6 py-4 font-medium">Email</th>
                  <th class="px-6 py-4 font-medium">Department</th>
                  <th class="px-6 py-4 font-medium">Role</th>
                  <th class="px-6 py-4 font-medium">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="5">
                    Loading team members...
                  </td>
                </tr>
                <tr v-for="member in filteredTeam" :key="member.id" class="hover:bg-white/5">
                  <td class="px-6 py-4 text-slate-100">
                    {{ member.name || 'â€”' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">{{ member.email || 'â€”' }}</td>
                  <td class="px-6 py-4 text-slate-300/80">{{ member.department || 'â€”' }}</td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ (member.roles || []).join(', ') || member.role || 'â€”' }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="rounded-full px-3 py-1 text-xs"
                      :class="member.status === 'active'
                        ? 'bg-emerald-400/10 text-emerald-200'
                        : 'bg-slate-400/10 text-slate-300/70'"
                    >
                      {{ member.status || 'â€”' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!isLoading && !filteredTeam.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="5">
                    No team members found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

