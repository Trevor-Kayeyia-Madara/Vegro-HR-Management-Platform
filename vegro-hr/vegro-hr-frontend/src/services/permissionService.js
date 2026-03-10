import apiClient from '../api/apiClient';

const getPermissions = () => apiClient.get('/api/permissions');

const getRoleMatrix = () => apiClient.get('/api/roles/permissions/matrix');

const updateRolePermissions = (roleId, permissionIds) =>
  apiClient.put(`/api/roles/${roleId}/permissions`, {
    permission_ids: permissionIds,
  });

export default {
  getPermissions,
  getRoleMatrix,
  updateRolePermissions,
};
