import apiClient from '../api/apiClient';

const getEmployees = (params = {}) => apiClient.get('/api/employees', { params });

const getEmployee = (id) => apiClient.get(`/api/employees/${id}`);

const createEmployee = (payload) => apiClient.post('/api/employees', payload);

const updateEmployee = (id, payload) => apiClient.put(`/api/employees/${id}`, payload);

const deleteEmployee = (id) => apiClient.delete(`/api/employees/${id}`);
const getEmployeeLeaveBalances = (id) => apiClient.get(`/api/employees/${id}/leave-balances`);
const getEmployeeLeaveSummary = (id) => apiClient.get(`/api/employees/${id}/leave-summary`);
const syncLeaveBalancesDefaults = () => apiClient.post('/api/employees/leave-balances/sync-defaults');
const getEmployeeManagers = (id) => apiClient.get(`/api/employees/${id}/managers`);
const syncEmployeeManagers = (id, payload) => apiClient.put(`/api/employees/${id}/managers`, payload);

export default {
  getEmployees,
  getEmployee,
  createEmployee,
  updateEmployee,
  deleteEmployee,
  getEmployeeLeaveBalances,
  getEmployeeLeaveSummary,
  syncLeaveBalancesDefaults,
  getEmployeeManagers,
  syncEmployeeManagers,
};
