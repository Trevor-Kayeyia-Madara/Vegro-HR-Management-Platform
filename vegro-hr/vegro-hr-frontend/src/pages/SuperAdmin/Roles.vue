<script setup>
import { onMounted, ref } from 'vue';

defineOptions({ name: 'SuperAdminRolesPage' });

const templates = ref([]);
const templateForm = ref({
  name: '',
  description: '',
  roles: '',
  modules: '',
});

const storageKey = 'superadmin_role_templates';

const loadTemplates = () => {
  try {
    const stored = localStorage.getItem(storageKey);
    templates.value = stored ? JSON.parse(stored) : [];
  } catch (error) {
    templates.value = [];
  }
};

const persistTemplates = () => {
  localStorage.setItem(storageKey, JSON.stringify(templates.value));
};

const addTemplate = () => {
  if (!templateForm.value.name) return;
  templates.value.unshift({
    id: Date.now(),
    name: templateForm.value.name.trim(),
    description: templateForm.value.description.trim(),
    roles: templateForm.value.roles
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean),
    modules: templateForm.value.modules
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean),
  });
  templateForm.value = { name: '', description: '', roles: '', modules: '' };
  persistTemplates();
};

const removeTemplate = (id) => {
  templates.value = templates.value.filter((item) => item.id !== id);
  persistTemplates();
};

onMounted(loadTemplates);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <section class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Super Admin
        </p>
        <h1 class="text-3xl font-semibold sm:text-4xl">System Roles</h1>
        <p class="max-w-2xl text-sm text-slate-300/70 sm:text-base">
          Super Admin controls tenant access. Company admins define RBAC roles and permissions
          inside each company. Use this space for system-level access templates and policies.
        </p>
      </section>

      <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">System policy</p>
          <h2 class="mt-2 text-lg font-semibold">Global access templates</h2>
          <p class="mt-3 text-sm text-slate-300/80">
            Define default role packs that are applied when a new company is created.
            Company admins can customize afterward.
          </p>
          <div class="mt-5 grid gap-3">
            <input
              v-model="templateForm.name"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Template name"
            />
            <input
              v-model="templateForm.description"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Short description"
            />
            <input
              v-model="templateForm.roles"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Default roles (comma separated)"
            />
            <input
              v-model="templateForm.modules"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Modules enabled (comma separated)"
            />
          </div>
          <button
            class="mt-4 rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200"
            type="button"
            @click="addTemplate"
          >
            Add template
          </button>
        </article>

        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Security</p>
          <h2 class="mt-2 text-lg font-semibold">System permissions</h2>
          <p class="mt-3 text-sm text-slate-300/80">
            Reserve global permissions such as tenant suspension, license enforcement,
            and cross-company reporting.
          </p>
          <button
            class="mt-4 rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200"
            type="button"
            disabled
          >
            Review system rules
          </button>
        </article>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Templates library</p>
        <h2 class="mt-2 text-lg font-semibold">Default access packs</h2>
        <div class="mt-4 grid gap-3">
          <article
            v-for="template in templates"
            :key="template.id"
            class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="font-semibold">{{ template.name }}</p>
                <p class="mt-1 text-xs text-slate-400">
                  {{ template.description || 'No description' }}
                </p>
              </div>
              <button
                class="rounded-full border border-white/10 px-3 py-1 text-[10px] text-slate-200"
                type="button"
                @click="removeTemplate(template.id)"
              >
                Remove
              </button>
            </div>
            <div class="mt-3 flex flex-wrap gap-2 text-[10px] text-slate-300/80">
              <span
                v-for="role in template.roles"
                :key="role"
                class="rounded-full border border-white/10 px-2 py-1"
              >
                {{ role }}
              </span>
              <span
                v-for="module in template.modules"
                :key="module"
                class="rounded-full border border-emerald-400/30 px-2 py-1 text-emerald-200"
              >
                {{ module }}
              </span>
            </div>
          </article>
          <p v-if="!templates.length" class="text-sm text-slate-400">
            No templates created yet. Add one to define default access packs for new companies.
          </p>
        </div>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Company admin note</p>
        <h2 class="mt-2 text-lg font-semibold">Tenant-level RBAC</h2>
        <p class="mt-3 text-sm text-slate-300/80">
          Company admins manage employee roles, permissions, and access policies inside
          their tenant dashboard. Super Admin focuses on company access and licensing.
        </p>
      </section>
    </div>
  </div>
</template>
