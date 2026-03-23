<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'OrgChartPage' });

const { hasPermission, hasRole, user } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');
const matrix = ref([]);
const employees = ref([]);

const isHr = computed(() => hasRole(['hr']));
const canManage = computed(() => isHr.value || hasPermission('orgchart.manage'));
const departments = computed(() => (Array.isArray(matrix.value) ? matrix.value : []));

const departmentForm = ref({
  name: '',
  description: '',
  manager_id: null,
});

const isCreatingDepartment = ref(false);
const deletingDepartmentId = ref(null);
const savingDepartmentManagers = ref({});

const managerOptions = computed(() =>
  [...employees.value]
    .filter((employee) => employee?.user_id)
    .sort((a, b) => String(a?.name || '').localeCompare(String(b?.name || ''))),
);

const loadEmployees = async () => {
  try {
    const response = await apiClient.get('/api/employees', { params: { per_page: 2000 } });
    const payload = response?.data?.data;

    if (Array.isArray(payload)) {
      employees.value = payload;
      return;
    }

    if (Array.isArray(payload?.data)) {
      employees.value = payload.data;
      return;
    }

    employees.value = [];
  } catch {
    employees.value = [];
  }
};

const loadMatrix = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/org-chart/matrix');
    const payload = response?.data?.data ?? response?.data ?? [];
    matrix.value = Array.isArray(payload) ? payload : [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load organization chart.';
    matrix.value = [];
  } finally {
    isLoading.value = false;
  }
};

const formatLines = (lines = [], type) =>
  lines
    .filter((line) => String(line?.relationship_type || '') === type)
    .map((line) => line?.manager?.name)
    .filter(Boolean)
    .join(', ');

const wrapLabel = (value, maxChars = 20) => {
  const text = String(value || '').trim();
  if (!text) return ['-'];

  const words = text.split(/\s+/);
  const parts = [];
  let current = '';

  words.forEach((word) => {
    const candidate = current ? `${current} ${word}` : word;
    if (candidate.length <= maxChars) {
      current = candidate;
      return;
    }

    if (current) parts.push(current);
    current = word;
  });

  if (current) parts.push(current);
  return parts.slice(0, 3);
};

const layoutOverrides = ref({});
const overviewSvgRef = ref(null);
const isExportingPng = ref(false);
const isExportingPdf = ref(false);
const isEditingLayout = ref(false);
const draggingNodeId = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

const selectedTopUserId = ref(null);
const topTitle = ref('CEO');
const nodeMeta = ref({});
const selectedNodeId = ref(null);
const isSavingLayout = ref(false);

const executiveCandidates = computed(() => {
  const keywords = ['md', 'director', 'ceo', 'chief executive', 'managing director'];
  return managerOptions.value.filter((employee) => {
    const role = String(employee?.role || '').toLowerCase();
    const roles = Array.isArray(employee?.roles) ? employee.roles.map((r) => String(r || '').toLowerCase()) : [];
    const position = String(employee?.position || '').toLowerCase();

    return keywords.some((keyword) =>
      role.includes(keyword)
      || position.includes(keyword)
      || roles.some((r) => r.includes(keyword)),
    );
  });
});

const topExecutive = computed(() => {
  const selected = executiveCandidates.value.find((candidate) => Number(candidate?.user_id) === Number(selectedTopUserId.value));
  if (selected) return selected;
  return executiveCandidates.value[0] || null;
});

const loadLayoutOverrides = async () => {
  try {
    const response = await apiClient.get('/api/org-chart/layout');
    const payload = response?.data?.data ?? response?.data ?? {};

    layoutOverrides.value = payload?.layout && typeof payload.layout === 'object' ? payload.layout : {};
    selectedTopUserId.value = payload?.top_user_id != null ? Number(payload.top_user_id) : null;
    topTitle.value = String(payload?.top_title || 'CEO');
    nodeMeta.value = payload?.node_meta && typeof payload.node_meta === 'object' ? payload.node_meta : {};
    selectedNodeId.value = null;
  } catch {
    layoutOverrides.value = {};
    selectedTopUserId.value = null;
    topTitle.value = 'CEO';
    nodeMeta.value = {};
    selectedNodeId.value = null;
  }
};

const saveLayoutOverrides = async () => {
  if (!canManage.value) return;

  isSavingLayout.value = true;

  try {
    await apiClient.put('/api/org-chart/layout', {
      layout: layoutOverrides.value || {},
      top_user_id: selectedTopUserId.value || null,
      top_title: String(topTitle.value || '').trim() || 'CEO',
      node_meta: nodeMeta.value || {},
    });
  } catch {
    errorMessage.value = 'Unable to save chart layout right now.';
  } finally {
    isSavingLayout.value = false;
  }
};

const resetChartLayout = async () => {
  layoutOverrides.value = {};
  nodeMeta.value = {};
  selectedNodeId.value = null;
  await saveLayoutOverrides();
};

const toggleEditLayout = async () => {
  isEditingLayout.value = !isEditingLayout.value;
  if (!isEditingLayout.value) {
    draggingNodeId.value = null;
    await saveLayoutOverrides();
  }
};

const getSvgPoint = (event) => {
  const svg = overviewSvgRef.value;
  if (!svg) return null;

  const rect = svg.getBoundingClientRect();
  if (!rect.width || !rect.height) return null;

  return {
    x: ((event.clientX - rect.left) / rect.width) * overviewChart.value.width,
    y: ((event.clientY - rect.top) / rect.height) * overviewChart.value.height,
  };
};

const startNodeDrag = (event, node) => {
  if (!canManage.value || !isEditingLayout.value) return;
  if (node.id === 'root') return;

  const point = getSvgPoint(event);
  if (!point) return;

  selectedNodeId.value = node.id;
  draggingNodeId.value = node.id;
  dragOffset.value = {
    x: point.x - Number(node.x),
    y: point.y - Number(node.y),
  };
};

const stopNodeDrag = () => {
  if (!draggingNodeId.value) return;
  draggingNodeId.value = null;
  saveLayoutOverrides();
};

const selectedNode = computed(() => {
  if (!selectedNodeId.value) return null;
  return overviewChart.value.nodes.find((node) => node.id === selectedNodeId.value) || null;
});

const ensureNodeMetaSeeded = (node) => {
  if (!node?.id) return;
  const existing = nodeMeta.value[node.id] || {};

  nodeMeta.value = {
    ...nodeMeta.value,
    [node.id]: {
      width: Number(existing.width ?? node.width),
      height: Number(existing.height ?? node.height),
      line1: String(existing.line1 ?? node.lines?.[0] ?? ''),
      line2: String(existing.line2 ?? node.lines?.[1] ?? ''),
      line3: String(existing.line3 ?? node.lines?.[2] ?? ''),
    },
  };
};

const selectNode = (node) => {
  selectedNodeId.value = node?.id || null;
  if (node) ensureNodeMetaSeeded(node);
};

const updateSelectedNodeMeta = (field, value) => {
  if (!selectedNode.value?.id) return;
  const id = selectedNode.value.id;
  const existing = nodeMeta.value[id] || {};
  nodeMeta.value = {
    ...nodeMeta.value,
    [id]: {
      ...existing,
      [field]: value,
    },
  };
};

const overviewChart = computed(() => {
  const list = departments.value || [];
  const topNode = { width: 220, height: 86 };
  const deptNode = { width: 190, height: 82 };
  const employeeNode = { width: 178, height: 74 };

  const outerPadding = 40;
  const sectionGap = 44;
  const childGap = 16;

  const topY = 24;
  const deptY = 188;
  const empY = 350;

  let cursorX = outerPadding;
  const deptLayout = [];

  list.forEach((department) => {
    const team = Array.isArray(department?.employees) ? department.employees : [];
    const childrenWidth = team.length
      ? (team.length * employeeNode.width) + ((team.length - 1) * childGap)
      : 0;

    const blockWidth = Math.max(deptNode.width, childrenWidth || 0);
    const deptX = cursorX + ((blockWidth - deptNode.width) / 2);

    const employeeNodes = team.map((employee, index) => {
      const childStartX = cursorX + ((blockWidth - childrenWidth) / 2);
      const x = childStartX + (index * (employeeNode.width + childGap));

      return {
        id: `emp-${department.id}-${employee.id}`,
        x,
        y: empY,
        width: employeeNode.width,
        height: employeeNode.height,
        lines: [
          ...wrapLabel(employee?.name, 18).slice(0, 2),
          String(employee?.position || 'Employee'),
        ],
      };
    });

    deptLayout.push({
      department,
      x: deptX,
      y: deptY,
      width: deptNode.width,
      height: deptNode.height,
      blockWidth,
      employeeNodes,
    });

    cursorX += blockWidth + sectionGap;
  });

  const minWidth = 900;
  const chartWidth = Math.max(minWidth, cursorX - sectionGap + outerPadding);
  const topX = (chartWidth - topNode.width) / 2;

  const ownerName = topExecutive.value?.name || 'MD / Director / CEO';
  const resolvedTopTitle = String(topTitle.value || '').trim() || 'CEO';

  const baseNodes = [
    {
      id: 'root',
      x: topX,
      y: topY,
      width: topNode.width,
      height: topNode.height,
      tone: 'root',
      lines: [resolvedTopTitle, ownerName],
    },
    ...deptLayout.flatMap((d) => {
      const deptNodeData = {
        id: `dept-${d.department.id}`,
        x: d.x,
        y: d.y,
        width: d.width,
        height: d.height,
        tone: 'department',
        lines: [
          ...wrapLabel(d.department?.name, 19).slice(0, 2),
          d.department?.manager?.name ? `HOD: ${d.department.manager.name}` : 'HOD: Unassigned',
        ],
      };

      const teamNodes = d.employeeNodes.map((child) => ({
        ...child,
        tone: 'employee',
      }));

      return [deptNodeData, ...teamNodes];
    }),
  ];

  const overrides = layoutOverrides.value || {};
  const nodes = baseNodes.map((node) => {
    const override = overrides[node.id];
    if (!override) return node;

    return {
      ...node,
      x: Number(override?.x ?? node.x),
      y: Number(override?.y ?? node.y),
    };
  });

  const connections = [];
  deptLayout.forEach((d) => {
    const deptId = `dept-${d.department.id}`;
    connections.push({ from: 'root', to: deptId });
    d.employeeNodes.forEach((employeeNode) => {
      connections.push({ from: deptId, to: employeeNode.id });
    });
  });

  const nodeById = Object.fromEntries(nodes.map((node) => [node.id, node]));
  const lines = [];

  connections.forEach((connection) => {
    const fromNode = nodeById[connection.from];
    const toNode = nodeById[connection.to];
    if (!fromNode || !toNode) return;

    const fromCenterX = fromNode.x + (fromNode.width / 2);
    const fromBottomY = fromNode.y + fromNode.height;
    const toCenterX = toNode.x + (toNode.width / 2);
    const toTopY = toNode.y;

    const elbowY = Math.min(toTopY - 20, fromBottomY + 24);

    lines.push({ x1: fromCenterX, y1: fromBottomY, x2: fromCenterX, y2: elbowY });
    lines.push({ x1: fromCenterX, y1: elbowY, x2: toCenterX, y2: elbowY });
    lines.push({ x1: toCenterX, y1: elbowY, x2: toCenterX, y2: toTopY });
  });

  return {
    width: chartWidth,
    height: 470,
    nodes,
    lines,
  };
});

const onOverviewPointerMove = (event) => {
  if (!draggingNodeId.value || !isEditingLayout.value) return;

  const point = getSvgPoint(event);
  if (!point) return;

  const node = overviewChart.value.nodes.find((n) => n.id === draggingNodeId.value);
  if (!node) return;

  const maxX = Math.max(20, overviewChart.value.width - node.width - 20);
  const maxY = Math.max(20, overviewChart.value.height - node.height - 20);

  const x = Math.max(20, Math.min(maxX, point.x - dragOffset.value.x));
  const y = Math.max(20, Math.min(maxY, point.y - dragOffset.value.y));

  layoutOverrides.value = {
    ...layoutOverrides.value,
    [node.id]: { x, y },
  };
};
const getOverviewSvgMarkup = () => {
  const svg = overviewSvgRef.value;
  if (!svg) return null;

  const clone = svg.cloneNode(true);
  clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
  clone.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

  const width = overviewChart.value?.width || 1200;
  const height = overviewChart.value?.height || 470;
  clone.setAttribute('viewBox', `0 0 ${width} ${height}`);
  clone.setAttribute('width', String(width));
  clone.setAttribute('height', String(height));

  return new XMLSerializer().serializeToString(clone);
};

const downloadBlob = (blob, filename) => {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
};

const exportOverviewAsPng = async () => {
  const markup = getOverviewSvgMarkup();
  if (!markup) return;

  isExportingPng.value = true;

  try {
    const width = overviewChart.value?.width || 1200;
    const height = overviewChart.value?.height || 470;
    const svgBlob = new Blob([markup], { type: 'image/svg+xml;charset=utf-8' });
    const svgUrl = URL.createObjectURL(svgBlob);

    await new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
          URL.revokeObjectURL(svgUrl);
          reject(new Error('Canvas context unavailable.'));
          return;
        }

        ctx.fillStyle = '#e5e7eb';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(image, 0, 0, width, height);

        canvas.toBlob((blob) => {
          URL.revokeObjectURL(svgUrl);
          if (!blob) {
            reject(new Error('Unable to generate PNG file.'));
            return;
          }

          downloadBlob(blob, 'organization-chart.png');
          resolve();
        }, 'image/png');
      };

      image.onerror = () => {
        URL.revokeObjectURL(svgUrl);
        reject(new Error('Unable to render SVG image.'));
      };

      image.src = svgUrl;
    });
  } catch {
    errorMessage.value = 'Unable to export PNG right now.';
  } finally {
    isExportingPng.value = false;
  }
};

const exportOverviewAsPdf = () => {
  const markup = getOverviewSvgMarkup();
  if (!markup) return;

  isExportingPdf.value = true;

  try {
    const printWindow = window.open('', '_blank', 'width=1280,height=900');
    if (!printWindow) {
      errorMessage.value = 'Please allow popups to export PDF.';
      return;
    }

    printWindow.document.write(`
      <!doctype html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Organization Chart</title>
          <style>
            body {
              margin: 0;
              background: #e5e7eb;
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
              color: #0f172a;
            }
            .page {
              padding: 24px;
            }
            h1 {
              text-align: center;
              margin: 0 0 20px;
              font-size: 34px;
              font-weight: 700;
            }
            .chart-wrap {
              background: #e5e7eb;
              overflow: hidden;
            }
            svg {
              width: 100%;
              height: auto;
              display: block;
            }
            @media print {
              body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
              .page { padding: 0; }
            }
          </style>
        </head>
        <body>
          <div class="page">
            <h1>Organization Chart</h1>
            <div class="chart-wrap">${markup}</div>
          </div>
          <script>
            setTimeout(() => {
              window.print();
              setTimeout(() => window.close(), 350);
            }, 250);
          <\/script>
        </body>
      </html>
    `);

    printWindow.document.close();
  } finally {
    isExportingPdf.value = false;
  }
};

const createDepartment = async () => {
  if (!canManage.value || !departmentForm.value.name.trim()) return;

  isCreatingDepartment.value = true;
  errorMessage.value = '';

  try {
    await apiClient.post('/api/departments', {
      name: departmentForm.value.name.trim(),
      description: departmentForm.value.description?.trim() || null,
      manager_id: departmentForm.value.manager_id || null,
    });

    departmentForm.value = {
      name: '',
      description: '',
      manager_id: null,
    };

    await loadMatrix();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to create department.';
  } finally {
    isCreatingDepartment.value = false;
  }
};

const updateDepartmentManager = async (department, managerUserId) => {
  if (!canManage.value || !department?.id) return;

  savingDepartmentManagers.value[department.id] = true;

  try {
    await apiClient.put(`/api/departments/${department.id}`, {
      name: department.name,
      description: department.description || null,
      manager_id: managerUserId || null,
    });

    await loadMatrix();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update department head.';
  } finally {
    savingDepartmentManagers.value[department.id] = false;
  }
};

const deleteDepartment = async (department) => {
  if (!canManage.value || !department?.id) return;

  const ok = window.confirm(`Delete department "${department.name}"?`);
  if (!ok) return;

  deletingDepartmentId.value = department.id;

  try {
    await apiClient.delete(`/api/departments/${department.id}`);
    await loadMatrix();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete department.';
  } finally {
    deletingDepartmentId.value = null;
  }
};

const isManagerModalOpen = ref(false);
const activeEmployee = ref(null);
const selectedFunctional = ref([]);
const selectedDotted = ref([]);
const isSaving = ref(false);
const modalError = ref('');

const openManagers = (employee) => {
  activeEmployee.value = employee;
  const lines = Array.isArray(employee?.reporting_lines) ? employee.reporting_lines : [];

  selectedFunctional.value = lines
    .filter((line) => String(line?.relationship_type || '') === 'functional')
    .map((line) => Number(line?.manager?.id))
    .filter(Boolean);

  selectedDotted.value = lines
    .filter((line) => String(line?.relationship_type || '') === 'dotted')
    .map((line) => Number(line?.manager?.id))
    .filter(Boolean);

  modalError.value = '';
  isManagerModalOpen.value = true;
};

const closeManagers = () => {
  isManagerModalOpen.value = false;
  activeEmployee.value = null;
};

const saveManagers = async () => {
  if (!activeEmployee.value?.id) return;

  isSaving.value = true;
  modalError.value = '';

  try {
    await apiClient.put(`/api/employees/${activeEmployee.value.id}/managers`, {
      functional_manager_ids: selectedFunctional.value,
      dotted_manager_ids: selectedDotted.value,
    });

    await loadMatrix();
    closeManagers();
  } catch (error) {
    modalError.value = error?.response?.data?.message || 'Unable to save reporting lines.';
  } finally {
    isSaving.value = false;
  }
};

onMounted(async () => {
  await Promise.all([loadEmployees(), loadMatrix(), loadLayoutOverrides()]);
});
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Organization Structure</p>
          <h1 class="text-3xl font-semibold">Org Chart + Hierarchy Designer</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-300/70">
            Build your company structure with departments, HOD assignments, and employee reporting lines.
          </p>
        </div>

        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="loadMatrix"
        >
          Refresh
        </button>
      </header>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section v-if="isLoading" class="rounded-3xl border border-white/10 bg-white/5 p-6 text-sm text-slate-300/80">
        Loading organization chart...
      </section>

      <template v-else>
        <section v-if="canManage" class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Structure Designer</p>
              <h2 class="mt-2 text-xl font-semibold">Create Department + Set HOD</h2>
            </div>
          </div>

          <div class="mt-4 grid gap-3 lg:grid-cols-4">
            <input
              v-model="departmentForm.name"
              type="text"
              placeholder="Department name"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
            <input
              v-model="departmentForm.description"
              type="text"
              placeholder="Description (optional)"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
            <select
              v-model="departmentForm.manager_id"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option :value="null">Select HOD (optional)</option>
              <option
                v-for="manager in managerOptions"
                :key="`new-dept-manager-${manager.user_id}`"
                :value="Number(manager.user_id)"
              >
                {{ manager.name }}{{ manager.position ? ` - ${manager.position}` : '' }}
              </option>
            </select>
            <button
              class="h-11 rounded-xl border border-emerald-300/40 bg-emerald-300/10 px-4 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
              type="button"
              :disabled="isCreatingDepartment || !departmentForm.name.trim()"
              @click="createDepartment"
            >
              {{ isCreatingDepartment ? 'Creating...' : 'Add Department' }}
            </button>
          </div>
        </section>

        <section class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">Org Chart View</p>
              <h2 class="mt-2 text-xl font-semibold">Departments, HODs, and Team Members</h2>
            </div>
          </div>

          <div class="rounded-3xl border border-slate-300/60 bg-slate-200 p-6 text-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-700">Organization Chart</p>
                <h3 class="mt-1 text-3xl font-semibold tracking-[0.03em]">Visual Company Structure</h3>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <select
                  v-if="canManage"
                  v-model="selectedTopUserId"
                  class="h-10 rounded-full border border-slate-400/70 bg-white px-4 text-xs font-semibold uppercase tracking-[0.08em] text-slate-800 outline-none"
                  @change="saveLayoutOverrides"
                >
                  <option :value="null">Top: auto (MD/Director/CEO)</option>
                  <option
                    v-for="leader in executiveCandidates"
                    :key="`leader-${leader.user_id}`"
                    :value="Number(leader.user_id)"
                  >
                    {{ leader.name }}
                  </option>
                </select>
                <input
                  v-if="canManage"
                  v-model="topTitle"
                  type="text"
                  maxlength="60"
                  placeholder="Top title (e.g. CEO)"
                  class="h-10 rounded-full border border-slate-400/70 bg-white px-4 text-xs font-semibold uppercase tracking-[0.08em] text-slate-800 outline-none"
                  @blur="saveLayoutOverrides"
                />
                <button
                  v-if="canManage"
                  class="rounded-full border border-slate-400/70 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-800 transition hover:bg-slate-100"
                  type="button"
                  @click="toggleEditLayout"
                >
                  {{ isEditingLayout ? 'Done Editing' : 'Edit Layout' }}
                </button>
                <button
                  v-if="canManage"
                  class="rounded-full border border-slate-400/70 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-800 transition hover:bg-slate-100"
                  type="button"
                  @click="resetChartLayout"
                >
                  Reset Layout
                </button>
                <button
                  class="rounded-full border border-slate-400/70 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-800 transition hover:bg-slate-100 disabled:opacity-60"
                  type="button"
                  :disabled="isExportingPng"
                  @click="exportOverviewAsPng"
                >
                  {{ isExportingPng ? 'Exporting PNG...' : 'Export PNG' }}
                </button>
                <button
                  class="rounded-full border border-slate-400/70 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-800 transition hover:bg-slate-100 disabled:opacity-60"
                  type="button"
                  :disabled="isExportingPdf"
                  @click="exportOverviewAsPdf"
                >
                  {{ isExportingPdf ? 'Preparing PDF...' : 'Export PDF' }}
                </button>
              </div>
            </div>

            <p class="mt-3 text-xs text-slate-600">Top node is MD/Director/CEO. HR remains a regular department below.</p>
            <p v-if="isEditingLayout" class="mt-1 text-xs text-slate-600">Drag department and employee nodes to rearrange the chart.</p>
            <p v-if="isSavingLayout" class="mt-1 text-xs text-slate-500">Saving layout...</p>

            <div v-if="isEditingLayout && selectedNode && selectedNode.id !== 'root'" class="mt-3 rounded-2xl border border-slate-300 bg-white p-3">
              <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Selected Node: {{ selectedNode.id }}</p>
              <div class="mt-2 grid gap-2 md:grid-cols-5">
                <input
                  :value="nodeMeta[selectedNode.id]?.line1 || selectedNode.lines?.[0] || ''"
                  type="text"
                  placeholder="Line 1"
                  class="h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-800 outline-none"
                  @input="updateSelectedNodeMeta('line1', $event.target.value)"
                  @blur="saveLayoutOverrides"
                />
                <input
                  :value="nodeMeta[selectedNode.id]?.line2 || selectedNode.lines?.[1] || ''"
                  type="text"
                  placeholder="Line 2"
                  class="h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-800 outline-none"
                  @input="updateSelectedNodeMeta('line2', $event.target.value)"
                  @blur="saveLayoutOverrides"
                />
                <input
                  :value="nodeMeta[selectedNode.id]?.line3 || selectedNode.lines?.[2] || ''"
                  type="text"
                  placeholder="Line 3"
                  class="h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-800 outline-none"
                  @input="updateSelectedNodeMeta('line3', $event.target.value)"
                  @blur="saveLayoutOverrides"
                />
                <input
                  :value="nodeMeta[selectedNode.id]?.width || selectedNode.width"
                  type="number"
                  min="80"
                  max="520"
                  class="h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-800 outline-none"
                  @input="updateSelectedNodeMeta('width', Number($event.target.value) || selectedNode.width)"
                  @blur="saveLayoutOverrides"
                />
                <input
                  :value="nodeMeta[selectedNode.id]?.height || selectedNode.height"
                  type="number"
                  min="50"
                  max="320"
                  class="h-9 rounded-lg border border-slate-300 px-3 text-xs text-slate-800 outline-none"
                  @input="updateSelectedNodeMeta('height', Number($event.target.value) || selectedNode.height)"
                  @blur="saveLayoutOverrides"
                />
              </div>
            </div>

            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-300 bg-slate-200 p-2">
              <svg
                ref="overviewSvgRef"
                :viewBox="`0 0 ${overviewChart.width} ${overviewChart.height}`"
                :width="overviewChart.width"
                :height="overviewChart.height"
                class="min-w-full"
                preserveAspectRatio="xMidYMin meet"
                @pointermove="onOverviewPointerMove"
                @pointerup="stopNodeDrag"
                @pointerleave="stopNodeDrag"
              >
                <defs>
                  <linearGradient id="org-root-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#9bd22f" />
                    <stop offset="100%" stop-color="#6ca11c" />
                  </linearGradient>
                  <linearGradient id="org-department-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#16b3d3" />
                    <stop offset="100%" stop-color="#0788a8" />
                  </linearGradient>
                  <linearGradient id="org-employee-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#ff9a1b" />
                    <stop offset="100%" stop-color="#f27b00" />
                  </linearGradient>
                  <filter id="org-card-shadow" x="-20%" y="-20%" width="160%" height="160%">
                    <feDropShadow dx="0" dy="2" stdDeviation="2.3" flood-opacity="0.32" flood-color="#1f2937" />
                  </filter>
                </defs>

                <line
                  v-for="(line, index) in overviewChart.lines"
                  :key="`overview-line-${index}`"
                  :x1="line.x1"
                  :y1="line.y1"
                  :x2="line.x2"
                  :y2="line.y2"
                  stroke="#9aa9b6"
                  stroke-width="2"
                />

                <g v-for="node in overviewChart.nodes" :key="node.id" filter="url(#org-card-shadow)" @pointerdown.prevent="startNodeDrag($event, node)" @click.stop="selectNode(node)">
                  <rect
                    :x="node.x"
                    :y="node.y"
                    :width="node.width"
                    :height="node.height"
                    rx="11"
                    :class="isEditingLayout && node.id !== 'root' ? 'cursor-move' : ''"
                    :fill="node.tone === 'root' ? 'url(#org-root-gradient)' : (node.tone === 'department' ? 'url(#org-department-gradient)' : 'url(#org-employee-gradient)')"
                    stroke="#ffffff"
                    stroke-opacity="0.28"
                  />

                  <text
                    v-for="(lineText, index) in node.lines"
                    :key="`${node.id}-text-${index}`"
                    :x="node.x + (node.width / 2)"
                    :y="node.y + 28 + (index * 16)"
                    text-anchor="middle"
                    fill="#ffffff"
                    font-size="12"
                    font-family="Segoe UI, Tahoma, Geneva, Verdana, sans-serif"
                    font-weight="600"
                    style="user-select: none"
                  >
                    {{ lineText }}
                  </text>
                </g>
              </svg>
            </div>
          </div>

          <div v-if="!departments.length" class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center text-sm text-slate-300/80">
            No departments found yet.
          </div>

          <div v-else class="grid gap-6 xl:grid-cols-2">
            <article
              v-for="department in departments"
              :key="department.id"
              class="rounded-3xl border border-white/10 bg-white/5 p-6"
            >
              <header class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-semibold text-slate-100">{{ department.name }}</h3>
                  <p class="mt-1 text-xs text-slate-400">{{ department.description || 'No description' }}</p>
                  <p class="mt-2 text-xs uppercase tracking-[0.22em] text-slate-400">
                    {{ Number(department?.employees_count || 0).toLocaleString() }} employees
                  </p>
                </div>

                <button
                  v-if="canManage"
                  class="rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20 disabled:opacity-60"
                  type="button"
                  :disabled="deletingDepartmentId === department.id"
                  @click="deleteDepartment(department)"
                >
                  {{ deletingDepartmentId === department.id ? 'Deleting...' : 'Delete Dept' }}
                </button>
              </header>

              <div class="mt-4 rounded-2xl border border-emerald-300/20 bg-emerald-300/5 p-4">
                <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-200/80">Head of Department (HOD)</p>

                <div v-if="canManage" class="mt-2">
                  <select
                    :disabled="savingDepartmentManagers[department.id]"
                    :value="department?.manager?.id || ''"
                    class="h-10 w-full rounded-xl border border-white/10 bg-slate-950/50 px-3 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40 disabled:opacity-60"
                    @change="updateDepartmentManager(department, Number($event.target.value) || null)"
                  >
                    <option value="">No HOD selected</option>
                    <option
                      v-for="manager in managerOptions"
                      :key="`dept-${department.id}-${manager.user_id}`"
                      :value="Number(manager.user_id)"
                    >
                      {{ manager.name }}{{ manager.position ? ` - ${manager.position}` : '' }}
                    </option>
                  </select>
                </div>

                <p v-else class="mt-2 text-sm font-semibold text-slate-100">
                  {{ department?.manager?.name || '-' }}
                </p>
              </div>

              <div class="mt-5">
                <p class="mb-3 text-[11px] uppercase tracking-[0.2em] text-slate-400">Department Employees</p>

                <div v-if="!(department.employees || []).length" class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-400">
                  No employees in this department.
                </div>

                <div v-else class="space-y-3">
                  <div
                    v-for="employee in department.employees"
                    :key="employee.id"
                    class="rounded-2xl border border-white/10 bg-slate-950/40 p-4"
                  >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <p class="text-sm font-semibold text-slate-100">{{ employee.name }}</p>
                        <p class="text-xs text-slate-400">{{ employee.position || '-' }}</p>
                      </div>

                      <button
                        v-if="canManage"
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openManagers(employee)"
                      >
                        Edit hierarchy
                      </button>
                    </div>

                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                      <p class="rounded-xl border border-white/10 bg-slate-900/60 px-3 py-2 text-xs text-slate-300">
                        <span class="text-slate-400">Functional:</span>
                        {{ formatLines(employee.reporting_lines || [], 'functional') || '-' }}
                      </p>
                      <p class="rounded-xl border border-white/10 bg-slate-900/60 px-3 py-2 text-xs text-slate-300">
                        <span class="text-slate-400">Dotted:</span>
                        {{ formatLines(employee.reporting_lines || [], 'dotted') || '-' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </section>
      </template>
    </div>

    <div
      v-if="isManagerModalOpen"
      class="vegro-modal-viewport"
      @click.self="closeManagers"
    >
      <div class="vegro-modal max-w-2xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Hierarchy Setup</p>
            <h2 class="mt-2 text-2xl font-semibold">{{ activeEmployee?.name || 'Employee' }}</h2>
            <p class="mt-1 text-sm text-slate-300/70">Set functional and dotted-line managers.</p>
          </div>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeManagers"
          >
            Close
          </button>
        </div>

        <p
          v-if="modalError"
          class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
        >
          {{ modalError }}
        </p>

        <div class="mt-6 max-h-[62dvh] overflow-y-auto pr-1 grid gap-4 sm:grid-cols-2">
          <label class="block text-xs text-slate-300/80">
            <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Functional managers</span>
            <select
              v-model="selectedFunctional"
              multiple
              class="h-40 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option
                v-for="manager in managerOptions"
                :key="`functional-${manager.user_id}`"
                :value="Number(manager.user_id)"
              >
                {{ manager.name }}{{ manager.position ? ` - ${manager.position}` : '' }}
              </option>
            </select>
          </label>

          <label class="block text-xs text-slate-300/80">
            <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Dotted managers</span>
            <select
              v-model="selectedDotted"
              multiple
              class="h-40 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option
                v-for="manager in managerOptions"
                :key="`dotted-${manager.user_id}`"
                :value="Number(manager.user_id)"
              >
                {{ manager.name }}{{ manager.position ? ` - ${manager.position}` : '' }}
              </option>
            </select>
          </label>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeManagers"
          >
            Cancel
          </button>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
            type="button"
            :disabled="isSaving"
            @click="saveManagers"
          >
            {{ isSaving ? 'Saving...' : 'Save Hierarchy' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>



























