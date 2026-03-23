<script setup>
import { computed, onMounted, ref } from 'vue';
import permissionService from '../../services/permissionService';

defineOptions({ name: 'RoleMatrixPage' });

const isLoading = ref(true);
const isSaving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const roles = ref([]);
const permissions = ref([]);
const roleSelections = ref({});

const unwrapMatrix = (response) => {
  const data = response?.data?.data || response?.data || {};
  return {
    roles: data.roles || [],
    permissions: data.permissions || [],
  };
};

const loadMatrix = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await permissionService.getRoleMatrix();
    const data = unwrapMatrix(response);
    roles.value = data.roles;
    permissions.value = data.permissions;
    roleSelections.value = data.roles.reduce((acc, role) => {
      acc[role.id] = new Set((role.permissions || []).map((perm) => perm.id));
      return acc;
    }, {});
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load role matrix.';
  } finally {
    isLoading.value = false;
  }
};

const groupedPermissions = computed(() => {
  const groups = {};
  for (const permission of permissions.value) {
    const group = permission.group || 'General';
    if (!groups[group]) groups[group] = [];
    groups[group].push(permission);
  }
  return groups;
});

const togglePermission = (roleId, permissionId) => {
  const set = roleSelections.value[roleId];
  if (!set) return;
  if (set.has(permissionId)) {
    set.delete(permissionId);
  } else {
    set.add(permissionId);
  }
};

const saveRolePermissions = async (roleId) => {
  isSaving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const ids = Array.from(roleSelections.value[roleId] || []);
    await permissionService.updateRolePermissions(roleId, ids);
    successMessage.value = 'Permissions updated successfully.';
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update permissions.';
  } finally {
    isSaving.value = false;
  }
};

onMounted(loadMatrix);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            Access Control
          </p>
          <h1 class="text-3xl font-semibold">Access Matrix</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Define which dashboards and modules each role can access across your enterprise.
          </p>
        </div>
        <button
          class="rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="loadMatrix"
          :disabled="isLoading"
        >
          Refresh
        </button>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Roles</p>
          <p class="mt-2 text-2xl font-semibold">{{ roles.length }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Permissions</p>
          <p class="mt-2 text-2xl font-semibold">{{ permissions.length }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Groups</p>
          <p class="mt-2 text-2xl font-semibold">{{ Object.keys(groupedPermissions).length }}</p>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>
      <p
        v-if="successMessage"
        class="rounded-2xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100"
      >
        {{ successMessage }}
      </p>

      <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="max-h-[75vh] overflow-auto">
          <div class="min-w-[860px]">
            <table class="w-full text-left text-xs sm:text-sm">
              <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
                <tr>
                  <th class="px-6 py-4 font-medium">Role</th>
                  <th class="px-6 py-4 font-medium">Permissions</th>
                  <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="3">
                    Loading role matrix...
                  </td>
                </tr>
                <tr v-for="role in roles" :key="role.id" class="align-top hover:bg-white/5">
                  <td class="px-6 py-4 font-medium text-slate-100">
                    <p>{{ role.title }}</p>
                    <p class="text-xs text-slate-400">{{ role.description || '—' }}</p>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-col gap-4">
                      <div v-for="(groupPermissions, groupName) in groupedPermissions" :key="groupName">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400">{{ groupName }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                          <label
                            v-for="permission in groupPermissions"
                            :key="permission.id"
                            class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-200"
                          >
                            <input
                              type="checkbox"
                              class="h-3 w-3 accent-emerald-400"
                              :checked="roleSelections[role.id]?.has(permission.id)"
                              @change="togglePermission(role.id, permission.id)"
                            />
                            <span>{{ permission.label }}</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button
                      class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
                      type="button"
                      :disabled="isSaving"
                      @click="saveRolePermissions(role.id)"
                    >
                      {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>
                  </td>
                </tr>
                <tr v-if="!isLoading && !roles.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="3">
                    No roles found.
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

