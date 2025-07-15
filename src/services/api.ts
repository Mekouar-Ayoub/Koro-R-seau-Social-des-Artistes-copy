// src/services/api.ts
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Configuration axios
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter le token d'authentification
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Intercepteur pour gérer les erreurs d'authentification
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// Services d'authentification
export const authAPI = {
  login: (credentials: { email: string; password: string }) =>
    apiClient.post<ApiResponse<{ user: any; access_token: string }>>('/auth/login', credentials),

  register: (userData: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    bio?: string;
    location?: string;
  }) =>
    apiClient.post<ApiResponse<{ user: any; access_token: string }>>('/auth/register', userData),

  logout: () => apiClient.post('/auth/logout'),

  getCurrentUser: () => apiClient.get<ApiResponse<{ user: any; stats: any }>>('/auth/me'),

  updateProfile: (data: FormData) =>
    apiClient.put<ApiResponse<{ user: any }>>('/auth/profile', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  updatePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    apiClient.put('/auth/password', data),
};

// Services utilisateurs
export const usersAPI = {
  getUsers: (params?: { search?: string; genre?: string; location?: string; page?: number }) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/users', { params }),

  getUser: (userId: number) =>
    apiClient.get<ApiResponse<{ user: any; stats: any; relations: any }>>(`/users/${userId}`),

  searchUsers: (query: string) =>
    apiClient.get<ApiResponse<{ users: any[]; tracks: any[]; playlists: any[] }>>('/users/search', {
      params: { q: query },
    }),

  followUser: (userId: number) =>
    apiClient.post<ApiResponse<{ is_following: boolean; followers_count: number }>>(`/users/${userId}/follow`),

  unfollowUser: (userId: number) =>
    apiClient.delete<ApiResponse<{ is_following: boolean; followers_count: number }>>(`/users/${userId}/unfollow`),

  getFollowers: (userId: number, page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>(`/users/${userId}/followers`, {
      params: { page },
    }),

  getFollowing: (userId: number, page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>(`/users/${userId}/following`, {
      params: { page },
    }),
};

// Services musique
export const musicAPI = {
  // Morceaux
  getTracks: (params?: { genre?: string; user_id?: number; search?: string; sort?: string; page?: number }) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/music/tracks', { params }),

  getTrack: (trackId: number) =>
    apiClient.get<ApiResponse<{ track: any; user_info: any }>>(`/music/tracks/${trackId}`),

  uploadTrack: (data: FormData) =>
    apiClient.post<ApiResponse<{ track: any }>>('/music/tracks', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  updateTrack: (trackId: number, data: FormData) =>
    apiClient.put<ApiResponse<{ track: any }>>(`/music/tracks/${trackId}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  deleteTrack: (trackId: number) => apiClient.delete(`/music/tracks/${trackId}`),

  streamTrack: (trackId: number) => `${API_BASE_URL}/music/tracks/${trackId}/stream`,

  recordPlay: (trackId: number, data: { duration_listened: number; completed?: boolean }) =>
    apiClient.post(`/music/tracks/${trackId}/play`, data),

  // Genres
  getGenres: () => apiClient.get<ApiResponse<{ genres: any[] }>>('/music/genres'),

  getTracksByGenre: (genre: string, page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>(`/music/genres/${genre}/tracks`, {
      params: { page },
    }),

  // Posts musicaux
  getPosts: (params?: { user_id?: number; genre?: string; with_location?: boolean; page?: number }) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/music/posts', { params }),

  getPost: (postId: number) =>
    apiClient.get<ApiResponse<{ post: any; user_info: any }>>(`/music/posts/${postId}`),

  createPost: (data: {
    track_id: number;
    content?: string;
    latitude?: number;
    longitude?: number;
    location_name?: string;
    location_type?: string;
    is_location_public?: boolean;
  }) => apiClient.post<ApiResponse<{ post: any }>>('/music/posts', data),

  updatePost: (postId: number, data: any) =>
    apiClient.put<ApiResponse<{ post: any }>>(`/music/posts/${postId}`, data),

  deletePost: (postId: number) => apiClient.delete(`/music/posts/${postId}`),

  getFeed: (page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/music/posts/feed', { params: { page } }),

  getDiscover: (page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/music/posts/discover', { params: { page } }),

  getTrending: (page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/music/posts/trending', { params: { page } }),

  getNearbyPosts: (params: {
    latitude: number;
    longitude: number;
    radius?: number;
    genre?: string;
    page?: number;
  }) => apiClient.get<ApiResponse<PaginatedResponse<any>>>('/location/nearby-posts', { params }),
};

// Services playlists
export const playlistsAPI = {
  getPlaylists: (params?: { user_id?: number; search?: string; collaborative?: boolean; page?: number }) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/playlists', { params }),

  getPlaylist: (playlistId: number) =>
    apiClient.get<ApiResponse<{ playlist: any; user_info: any }>>(`/playlists/${playlistId}`),

  createPlaylist: (data: FormData) =>
    apiClient.post<ApiResponse<{ playlist: any }>>('/playlists', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  updatePlaylist: (playlistId: number, data: FormData) =>
    apiClient.put<ApiResponse<{ playlist: any }>>(`/playlists/${playlistId}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  deletePlaylist: (playlistId: number) => apiClient.delete(`/playlists/${playlistId}`),

  addTrack: (playlistId: number, data: { track_id: number; position?: number }) =>
    apiClient.post<ApiResponse<{ track: any; position: number }>>(`/playlists/${playlistId}/tracks`, data),

  removeTrack: (playlistId: number, trackId: number) =>
    apiClient.delete(`/playlists/${playlistId}/tracks/${trackId}`),

  reorderTracks: (playlistId: number, trackIds: number[]) =>
    apiClient.put(`/playlists/${playlistId}/tracks/reorder`, { track_ids: trackIds }),

  addCollaborator: (playlistId: number, userId: number) =>
    apiClient.post<ApiResponse<{ collaborator: any }>>(`/playlists/${playlistId}/collaborators`, {
      user_id: userId,
    }),

  removeCollaborator: (playlistId: number, userId: number) =>
    apiClient.delete(`/playlists/${playlistId}/collaborators/${userId}`),
};

// Services sociaux
export const socialAPI = {
  // Likes
  like: (data: { likeable_type: string; likeable_id: number }) =>
    apiClient.post<ApiResponse<{ is_liked: boolean; likes_count: number }>>('/social/like', data),

  unlike: (data: { likeable_type: string; likeable_id: number }) =>
    apiClient.delete<ApiResponse<{ is_liked: boolean; likes_count: number }>>('/social/unlike', { data }),

  // Commentaires
  getComments: (params: {
    commentable_type: string;
    commentable_id: number;
    sort?: string;
    page?: number;
  }) => apiClient.get<ApiResponse<PaginatedResponse<any>>>('/social/comments', { params }),

  createComment: (data: {
    commentable_type: string;
    commentable_id: number;
    content: string;
    parent_id?: number;
  }) => apiClient.post<ApiResponse<{ comment: any }>>('/social/comments', data),

  updateComment: (commentId: number, content: string) =>
    apiClient.put<ApiResponse<{ comment: any }>>(`/social/comments/${commentId}`, { content }),

  deleteComment: (commentId: number) => apiClient.delete(`/social/comments/${commentId}`),

  getUserLikes: (page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/social/my-likes', { params: { page } }),

  getUserComments: (page?: number) =>
    apiClient.get<ApiResponse<PaginatedResponse<any>>>('/social/my-comments', { params: { page } }),
};

// Services de géolocalisation
export const locationAPI = {
  geocode: (address: string, country?: string) =>
    apiClient.get<ApiResponse<any[]>>('/location/geocode', {
      params: { address, country },
    }),

  reverseGeocode: (latitude: number, longitude: number, zoom?: number) =>
    apiClient.get<ApiResponse<any>>('/location/reverse-geocode', {
      params: { latitude, longitude, zoom },
    }),

  getPopularLocations: (limit?: number) =>
    apiClient.get<ApiResponse<{ locations: any[] }>>('/location/popular', {
      params: { limit },
    }),

  getLocationStats: (latitude: number, longitude: number, radius?: number) =>
    apiClient.get<ApiResponse<{ area_stats: any; top_genres: any[] }>>('/location/stats', {
      params: { latitude, longitude, radius },
    }),

  getHeatmapData: (bounds: { north: number; south: number; east: number; west: number }) =>
    apiClient.get<ApiResponse<{ points: any[]; bounds: any; total_points: number }>>('/location/heatmap', {
      params: bounds,
    }),
};

export default apiClient;