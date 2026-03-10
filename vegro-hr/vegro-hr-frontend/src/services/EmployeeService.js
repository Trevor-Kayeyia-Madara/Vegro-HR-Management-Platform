import apiClient from '../api/apiClient';

const getEmployees = (params = {}) => apiClient.get('/api/employees', { params });

const getEmployee = (id) => apiClient.get(`/api/employees/${id}`);

const createEmployee = (payload) => apiClient.post('/api/employees', payload);

const updateEmployee = (id, payload) => apiClient.put(`/api/employees/${id}`, payload);

const deleteEmployee = (id) => apiClient.delete(`/api/employees/${id}`);

export default {
  getEmployees,
  getEmployee,
  createEmployee,
  updateEmployee,
  deleteEmployee,
};
