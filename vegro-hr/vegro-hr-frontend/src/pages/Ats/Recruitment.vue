<script setup>
import { computed, ref } from 'vue';
import useAuth from '../../hooks/useAuth';
import JobPostingsPanel from './JobPostingsPanel.vue';
import ApplicationsPanel from './ApplicationsPanel.vue';
import CandidatesPanel from './CandidatesPanel.vue';

defineOptions({ name: 'RecruitmentPage' });

const { hasPermission } = useAuth();
const canManage = computed(() => hasPermission('recruitment.manage'));

const activeTab = ref('jobs');

const tabs = computed(() => {
  const base = [
    { key: 'jobs', label: 'Job postings' },
    { key: 'applications', label: 'Applications' },
  ];
  if (canManage.value) base.push({ key: 'candidates', label: 'Candidates' });
  return base;
});
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            ATS recruitment
          </p>
          <h1 class="text-3xl font-semibold">Hiring pipeline</h1>
          <p class="mt-2 max-w-2xl text-sm text-slate-300/70">
            Track jobs, candidates, and applications with stage updates and audit trails.
          </p>
        </div>
        <span
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200"
        >
          {{ canManage ? 'HR manage' : 'Read-only' }}
        </span>
      </header>

      <nav class="flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] transition"
          :class="
            activeTab === tab.key
              ? 'border-emerald-300/40 bg-emerald-300/10 text-emerald-200'
              : 'border-white/10 bg-white/5 text-slate-200 hover:bg-white/10'
          "
          type="button"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </nav>

      <JobPostingsPanel v-if="activeTab === 'jobs'" />
      <ApplicationsPanel v-else-if="activeTab === 'applications'" />
      <CandidatesPanel v-else />
    </div>
  </div>
</template>

