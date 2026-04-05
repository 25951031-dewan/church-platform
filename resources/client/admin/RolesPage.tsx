import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Shield, Plus, Edit, Trash2, Users, X, Check } from 'lucide-react';
import { useNotificationStore } from '@app/common/stores';

interface Role {
  id: number;
  name: string;
  slug: string;
  permissions?: Permission[];
  users_count?: number;
  description?: string;
}

interface Permission {
  id: number;
  name: string;
  slug: string;
  group?: string;
}

interface CreateRoleRequest {
  name: string;
  description?: string;
  permissions: number[];
}

interface RoleFormProps {
  role?: Role;
  onClose: () => void;
  onSubmit: (data: CreateRoleRequest) => void;
}

function RoleForm({ role, onClose, onSubmit }: RoleFormProps) {
  const [name, setName] = useState(role?.name || '');
  const [description, setDescription] = useState(role?.description || '');
  const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
    role?.permissions?.map(p => p.id) || []
  );

  const { data: permissions } = useQuery({
    queryKey: ['admin-permissions'],
    queryFn: () => apiClient.get<Permission[]>('permissions').then(r => r.data),
  });

  // Group permissions by category
  const permissionGroups = permissions?.reduce((acc, permission) => {
    const group = permission.group || 'Other';
    if (!acc[group]) acc[group] = [];
    acc[group].push(permission);
    return acc;
  }, {} as Record<string, Permission[]>) || {};

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit({
      name: name.trim(),
      description: description.trim() || undefined,
      permissions: selectedPermissions,
    });
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div className="bg-[#161920] border border-white/10 rounded-xl w-full max-w-2xl max-h-[80vh] overflow-hidden">
        <div className="flex items-center justify-between p-6 border-b border-white/10">
          <h3 className="text-lg font-semibold text-white">
            {role ? 'Edit Role' : 'Create Role'}
          </h3>
          <button onClick={onClose} className="text-gray-400 hover:text-white">
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex flex-col h-full">
          <div className="p-6 space-y-4 overflow-y-auto flex-1">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Role Name
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white placeholder:text-gray-500 focus:border-indigo-500 focus:outline-none"
                placeholder="Enter role name"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Description
              </label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={2}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white placeholder:text-gray-500 focus:border-indigo-500 focus:outline-none"
                placeholder="Optional role description"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-3">
                Permissions
              </label>
              <div className="space-y-4">
                {Object.entries(permissionGroups).map(([group, groupPermissions]) => (
                  <div key={group} className="border border-white/5 rounded-lg p-3">
                    <h4 className="font-medium text-white mb-2">{group}</h4>
                    <div className="space-y-2">
                      {groupPermissions.map((permission) => (
                        <label key={permission.id} className="flex items-center gap-3">
                          <input
                            type="checkbox"
                            checked={selectedPermissions.includes(permission.id)}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setSelectedPermissions([...selectedPermissions, permission.id]);
                              } else {
                                setSelectedPermissions(selectedPermissions.filter(id => id !== permission.id));
                              }
                            }}
                            className="rounded border-white/20 bg-[#0C0E12]"
                          />
                          <div>
                            <span className="text-sm text-white">{permission.name}</span>
                            <code className="text-xs text-gray-500 ml-2">{permission.slug}</code>
                          </div>
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div className="flex items-center justify-end gap-3 p-6 border-t border-white/10">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-400 hover:text-white transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!name.trim()}
              className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Check size={18} />
              {role ? 'Update' : 'Create'} Role
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function RolesPage() {
  const [showForm, setShowForm] = useState(false);
  const [editingRole, setEditingRole] = useState<Role | undefined>();
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  const { data: roles, isLoading } = useQuery({
    queryKey: ['admin-roles'],
    queryFn: () => apiClient.get<{ roles: Role[] }>('roles').then(r => r.data.roles || []),
  });

  const createMutation = useMutation({
    mutationFn: (data: CreateRoleRequest) => apiClient.post('roles', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
      setShowForm(false);
      success('Role created successfully');
    },
    onError: () => error('Failed to create role'),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: CreateRoleRequest }) => 
      apiClient.put(`roles/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
      setEditingRole(undefined);
      success('Role updated successfully');
    },
    onError: () => error('Failed to update role'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => apiClient.delete(`roles/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-roles'] });
      success('Role deleted successfully');
    },
    onError: () => error('Failed to delete role'),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-white flex items-center gap-2">
          <Shield size={24} />
          Roles & Permissions
        </h1>
        <button
          onClick={() => setShowForm(true)}
          className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <Plus size={18} />
          Create Role
        </button>
      </div>

      <div className="bg-[#161920] border border-white/5 rounded-xl overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-400">Loading roles...</div>
        ) : roles?.length === 0 ? (
          <div className="p-8 text-center">
            <Shield size={48} className="mx-auto text-gray-600 mb-4" />
            <p className="text-gray-400">No roles found</p>
            <button
              onClick={() => setShowForm(true)}
              className="mt-3 text-indigo-400 hover:text-indigo-300"
            >
              Create your first role
            </button>
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-white/5">
                <th className="text-left px-4 py-3 text-gray-400 font-medium">Role</th>
                <th className="text-left px-4 py-3 text-gray-400 font-medium">Users</th>
                <th className="text-left px-4 py-3 text-gray-400 font-medium">Permissions</th>
                <th className="text-right px-4 py-3 text-gray-400 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {roles?.map(role => (
                <tr key={role.id} className="border-b border-white/5 hover:bg-white/[0.03] transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-start gap-3">
                      <Shield size={16} className="text-indigo-400 mt-0.5" />
                      <div>
                        <div className="font-medium text-white">{role.name}</div>
                        {role.description && (
                          <div className="text-xs text-gray-500 mt-1">{role.description}</div>
                        )}
                        <code className="text-xs text-gray-500 mt-1">{role.slug}</code>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2 text-gray-400">
                      <Users size={14} />
                      <span>{role.users_count || 0}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-gray-400">
                      {role.permissions?.length || 0} permissions
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                      <button
                        onClick={() => setEditingRole(role)}
                        className="p-2 text-gray-400 hover:text-indigo-400 transition-colors"
                        title="Edit role"
                      >
                        <Edit size={16} />
                      </button>
                      <button
                        onClick={() => {
                          if (confirm(`Delete role "${role.name}"?`)) {
                            deleteMutation.mutate(role.id);
                          }
                        }}
                        className="p-2 text-gray-400 hover:text-red-400 transition-colors"
                        title="Delete role"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {showForm && (
        <RoleForm
          onClose={() => setShowForm(false)}
          onSubmit={(data) => createMutation.mutate(data)}
        />
      )}

      {editingRole && (
        <RoleForm
          role={editingRole}
          onClose={() => setEditingRole(undefined)}
          onSubmit={(data) => updateMutation.mutate({ id: editingRole.id, data })}
        />
      )}
    </div>
  );
}
