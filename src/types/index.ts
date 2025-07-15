export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  bio?: string;
  location?: string;
  is_private: boolean;
}

export interface Track {
  id: number;
  title: string;
  artist: string;
  album?: string;
  genre: string;
  duration: number;
  file_path: string;
  cover_image?: string;
  is_public: boolean;
  play_count: number;
  user: User;
}

export interface Playlist {
  id: number;
  name: string;
  description?: string;
  cover_image?: string;
  is_public: boolean;
  is_collaborative: boolean;
  user: User;
  tracks: Track[];
}
