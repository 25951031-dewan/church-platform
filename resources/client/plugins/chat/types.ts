export interface User {
  id: number;
  name: string;
  avatar: string | null;
}

export interface Message {
  id: number;
  conversation_id: number;
  user_id: number;
  body: string | null;
  type: 'text' | 'image' | 'file' | 'audio';
  file_entry_id: number | null;
  user: User;
  created_at: string;
  deleted_at?: string | null; // For admin soft-deleted messages
}

export interface Conversation {
  id: number;
  type: 'direct' | 'group';
  name: string | null;
  display_name: string;
  users: User[];
  latest_message: {
    id: number;
    body: string | null;
    type: string;
    user: { id: number; name: string };
    created_at: string;
  } | null;
  unread_count: number;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
