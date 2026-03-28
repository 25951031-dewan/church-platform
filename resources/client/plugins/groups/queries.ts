import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface Group {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  rules: string | null;
  type: 'public' | 'private' | 'church_only';
  cover_image: string | null;
  member_count: number;
  is_featured: boolean;
  creator: {id: number; name: string; avatar: string | null};
  current_user_membership: {role: string; status: string} | null;
  approved_members_count: number;
  posts_count: number;
}

export interface GroupMember {
  id: number;
  user: {id: number; name: string; avatar: string | null};
  role: 'admin' | 'moderator' | 'member';
  status: 'approved' | 'pending' | 'invited';
  joined_at: string | null;
}

// --- Group queries ---

export function useGroups(params: Record<string, string | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['groups', params],
    queryFn: ({pageParam = 1}) =>
      apiClient.get('groups', {params: {...params, page: pageParam}}).then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useGroup(groupId: number | string) {
  return useQuery({
    queryKey: ['groups', groupId],
    queryFn: () => apiClient.get(`groups/${groupId}`).then(r => r.data.group),
  });
}

export function useGroupMembers(
  groupId: number | string,
  status: string = 'approved'
) {
  return useInfiniteQuery({
    queryKey: ['groups', groupId, 'members', status],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get(`groups/${groupId}/members`, {params: {status, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

// --- Mutations ---

export function useCreateGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: {name: string; description?: string; type?: string}) =>
      apiClient.post('groups', data).then(r => r.data.group),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups']});
    },
  });
}

export function useUpdateGroup(groupId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Group>) =>
      apiClient.put(`groups/${groupId}`, data).then(r => r.data.group),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups']});
    },
  });
}

export function useDeleteGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (groupId: number) =>
      apiClient.delete(`groups/${groupId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups']});
    },
  });
}

export function useJoinGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (groupId: number) =>
      apiClient.post(`groups/${groupId}/join`).then(r => r.data),
    onSuccess: (_data: any, groupId: number) => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId]});
      queryClient.invalidateQueries({queryKey: ['groups']});
    },
  });
}

export function useLeaveGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (groupId: number) =>
      apiClient.delete(`groups/${groupId}/leave`),
    onSuccess: (_data: any, groupId: number) => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId]});
      queryClient.invalidateQueries({queryKey: ['groups']});
    },
  });
}

export function useApproveMember(groupId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (memberId: number) =>
      apiClient.patch(`groups/${groupId}/members/${memberId}/approve`).then(r => r.data.member),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId, 'members']});
    },
  });
}

export function useRejectMember(groupId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (memberId: number) =>
      apiClient.delete(`groups/${groupId}/members/${memberId}/reject`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId, 'members']});
    },
  });
}

export function useChangeMemberRole(groupId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({memberId, role}: {memberId: number; role: string}) =>
      apiClient.patch(`groups/${groupId}/members/${memberId}/role`, {role}).then(r => r.data.member),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId, 'members']});
    },
  });
}

export function useRemoveMember(groupId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (memberId: number) =>
      apiClient.delete(`groups/${groupId}/members/${memberId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['groups', groupId, 'members']});
    },
  });
}
