import {
    useInfiniteQuery,
    useMutation,
    useQuery,
    useQueryClient,
} from '@tanstack/react-query';
import axios from 'axios';

export interface Church {
    id: number;
    name: string;
    slug: string;
    status: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    state?: string;
    country?: string;
    latitude?: number;
    longitude?: number;
    denomination?: string;
    short_description?: string;
    description?: string;
    mission_statement?: string;
    vision_statement?: string;
    website?: string;
    logo?: string;
    logo_url?: string;
    cover_photo?: string;
    cover_photo_url?: string;
    primary_color?: string;
    year_founded?: number;
    service_hours?: Record<string, string>;
    social_links?: Record<string, string>;
    is_featured: boolean;
    is_verified: boolean;
    approved_members_count?: number;
    published_pages_count?: number;
    reactions_count?: number;
    current_user_membership?: { role: string; status: string; joined_at: string } | null;
    is_church_admin?: boolean;
}

export interface ChurchMember {
    id: number;
    church_id: number;
    user_id: number;
    role: string;
    status: string;
    joined_at: string;
    user?: { id: number; name: string; avatar?: string; email?: string };
}

export interface ChurchPage {
    id: number;
    church_id: number;
    title: string;
    slug: string;
    body?: string;
    sort_order: number;
    is_published: boolean;
}

export function useChurchDirectory(filters: Record<string, string> = {}) {
    return useInfiniteQuery({
        queryKey: ['churches', filters],
        queryFn: ({ pageParam = 1 }) =>
            axios
                .get('/api/v1/churches', { params: { ...filters, page: pageParam } })
                .then(r => r.data),
        getNextPageParam: (last: any) =>
            last.current_page < last.last_page ? last.current_page + 1 : undefined,
        initialPageParam: 1,
    });
}

export function useChurch(churchId: number) {
    return useQuery({
        queryKey: ['church', churchId],
        queryFn: () => axios.get(`/api/v1/churches/${churchId}`).then(r => r.data.church),
    });
}

export function useJoinChurch() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (churchId: number) =>
            axios.post(`/api/v1/churches/${churchId}/join`).then(r => r.data),
        onSuccess: (_, churchId) => {
            qc.invalidateQueries({ queryKey: ['church', churchId] });
        },
    });
}

export function useLeaveChurch() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (churchId: number) =>
            axios.delete(`/api/v1/churches/${churchId}/leave`).then(r => r.data),
        onSuccess: (_, churchId) => {
            qc.invalidateQueries({ queryKey: ['church', churchId] });
        },
    });
}

export function useChurchMembers(churchId: number) {
    return useInfiniteQuery({
        queryKey: ['church-members', churchId],
        queryFn: ({ pageParam = 1 }) =>
            axios
                .get(`/api/v1/churches/${churchId}/members`, { params: { page: pageParam } })
                .then(r => r.data),
        getNextPageParam: (last: any) =>
            last.current_page < last.last_page ? last.current_page + 1 : undefined,
        initialPageParam: 1,
    });
}

export function useRemoveMember() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ churchId, userId }: { churchId: number; userId: number }) =>
            axios.delete(`/api/v1/churches/${churchId}/members/${userId}`),
        onSuccess: (_, { churchId }) => {
            qc.invalidateQueries({ queryKey: ['church-members', churchId] });
        },
    });
}

export function useUpdateMemberRole() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ churchId, userId, role }: { churchId: number; userId: number; role: string }) =>
            axios
                .patch(`/api/v1/churches/${churchId}/members/${userId}/role`, { role })
                .then(r => r.data),
        onSuccess: (_, { churchId }) => {
            qc.invalidateQueries({ queryKey: ['church-members', churchId] });
        },
    });
}

export function useChurchPages(churchId: number) {
    return useQuery({
        queryKey: ['church-pages', churchId],
        queryFn: () =>
            axios.get(`/api/v1/churches/${churchId}/pages`).then(r => r.data.pages as ChurchPage[]),
    });
}

export function useChurchPage(churchId: number, pageId: number) {
    return useQuery({
        queryKey: ['church-page', churchId, pageId],
        queryFn: () =>
            axios
                .get(`/api/v1/churches/${churchId}/pages/${pageId}`)
                .then(r => r.data.page as ChurchPage),
    });
}

export function useVerifyChurch() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (churchId: number) =>
            axios.patch(`/api/v1/churches/${churchId}/verify`).then(r => r.data),
        onSuccess: (_, churchId) => {
            qc.invalidateQueries({ queryKey: ['church', churchId] });
            qc.invalidateQueries({ queryKey: ['churches'] });
        },
    });
}

export function useFeatureChurch() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (churchId: number) =>
            axios.patch(`/api/v1/churches/${churchId}/feature`).then(r => r.data),
        onSuccess: (_, churchId) => {
            qc.invalidateQueries({ queryKey: ['church', churchId] });
            qc.invalidateQueries({ queryKey: ['churches'] });
        },
    });
}
