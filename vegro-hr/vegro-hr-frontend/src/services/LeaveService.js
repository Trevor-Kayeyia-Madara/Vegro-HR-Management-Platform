import apiClient from '../api/apiClient';

const unwrap = (response) => response?.data?.data ?? response?.data ?? null;

const LeaveService = {
  async getRequests(endpoint = '/api/leave-requests/all', params = {}) {
    const response = await apiClient.get(endpoint, { params });
    return unwrap(response);
  },

  async createRequest(payload) {
    const response = await apiClient.post('/api/leave-requests', payload);
    return unwrap(response);
  },

  async updateRequest(id, payload) {
    const response = await apiClient.put(`/api/leave-requests/${id}`, payload);
    return unwrap(response);
  },

  async deleteRequest(id) {
    const response = await apiClient.delete(`/api/leave-requests/${id}`);
    return unwrap(response);
  },

  async approveRequest(id) {
    const response = await apiClient.post(`/api/leave-requests/${id}/approve`);
    return unwrap(response);
  },

  async rejectRequest(id) {
    const response = await apiClient.post(`/api/leave-requests/${id}/reject`);
    return unwrap(response);
  },

  async getApprovers() {
    const response = await apiClient.get('/api/leave-requests/approvers');
    return unwrap(response);
  },

  async getLeaveTypes() {
    const response = await apiClient.get('/api/leave-types');
    return unwrap(response) || [];
  },

  async updateLeaveType(type, payload) {
    const response = await apiClient.put(`/api/leave-types/${type}`, payload);
    return unwrap(response);
  },

  async resetLeaveTypeDefaults() {
    const response = await apiClient.post('/api/leave-types/reset-defaults');
    return unwrap(response);
  },
};

export default LeaveService;
