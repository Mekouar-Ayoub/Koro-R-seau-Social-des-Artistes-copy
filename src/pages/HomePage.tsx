// src/pages/HomePage.tsx
import React, { useEffect, useState } from 'react';
import { useAppSelector } from '../store';
import { musicAPI, usersAPI } from '../services/api';
import LoadingSpinner from '../components/ui/LoadingSpinner';

interface Track {
  id: number;
  title: string;
  artist: string;
  genre: string;
  duration: number;
  cover_image?: string;
  user: {
    id: number;
    name: string;
    avatar?: string;
  };
}

interface User {
  id: number;
  name: string;
  bio?: string;
  avatar?: string;
  location?: string;
}

const HomePage: React.FC = () => {
  const { user } = useAppSelector((state) => state.auth);
  const [tracks, setTracks] = useState<Track[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [genres, setGenres] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // Récupérer les données en parallèle
        const [tracksRes, usersRes, genresRes] = await Promise.all([
          musicAPI.getTracks({ sort: 'recent' }),
          usersAPI.getUsers(),
          musicAPI.getGenres(),
        ]);

        setTracks(tracksRes.data.data.data.slice(0, 6)); // 6 premiers morceaux
        setUsers(usersRes.data.data.data.slice(0, 8)); // 8 premiers utilisateurs
        setGenres(genresRes.data.data.genres.slice(0, 12)); // 12 premiers genres
        
      } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const formatDuration = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-96">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* En-tête de bienvenue */}
      <div className="text-center py-8 bg-gradient-to-r from-music-500 to-secondary-500 rounded-2xl text-white">
        <h1 className="text-4xl font-bold mb-4">
          Bienvenue sur Kore, {user?.name} ! 🎵
        </h1>
        <p className="text-xl opacity-90 max-w-2xl mx-auto">
          Découvrez de nouveaux morceaux, connectez-vous avec des musiciens et explorez la musique autour de vous
        </p>
      </div>

      {/* Statistiques rapides */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-3 bg-music-100 rounded-lg">
              <svg className="w-6 h-6 text-music-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Morceaux</p>
              <p className="text-2xl font-bold text-gray-900">{tracks.length}+</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-3 bg-secondary-100 rounded-lg">
              <svg className="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Artistes</p>
              <p className="text-2xl font-bold text-gray-900">{users.length}+</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-3 bg-green-100 rounded-lg">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2m3 0h4a2 2 0 012 2v1H3V6a2 2 0 012-2h4zM3 10h18v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Genres</p>
              <p className="text-2xl font-bold text-gray-900">{genres.length}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-3 bg-yellow-100 rounded-lg">
              <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Lieux</p>
              <p className="text-2xl font-bold text-gray-900">24+</p>
            </div>
          </div>
        </div>
      </div>

      {/* Morceaux récents */}
      <div>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Morceaux récents</h2>
          <button className="text-music-600 hover:text-music-700 font-medium">
            Voir tout →
          </button>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {tracks.map((track) => (
            <div key={track.id} className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
              <div className="flex items-start space-x-4">
                <div className="w-16 h-16 bg-gradient-to-br from-music-400 to-music-600 rounded-lg flex items-center justify-center">
                  {track.cover_image ? (
                    <img src={track.cover_image} alt={track.title} className="w-full h-full object-cover rounded-lg" />
                  ) : (
                    <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-gray-900 truncate">{track.title}</h3>
                  <p className="text-sm text-gray-600 truncate">{track.artist}</p>
                  <div className="flex items-center justify-between mt-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-music-100 text-music-800">
                      {track.genre}
                    </span>
                    <span className="text-xs text-gray-500">{formatDuration(track.duration)}</span>
                  </div>
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <div className="w-6 h-6 bg-gray-300 rounded-full"></div>
                  <span className="text-sm text-gray-600">{track.user.name}</span>
                </div>
                <button className="p-2 text-music-600 hover:bg-music-50 rounded-lg transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Artistes à découvrir */}
      <div>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Artistes à découvrir</h2>
          <button className="text-music-600 hover:text-music-700 font-medium">
            Voir tout →
          </button>
        </div>
        
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          {users.map((artist) => (
            <div key={artist.id} className="text-center">
              <div className="w-20 h-20 bg-gradient-to-br from-secondary-400 to-secondary-600 rounded-full mx-auto mb-3 flex items-center justify-center">
                {artist.avatar ? (
                  <img src={artist.avatar} alt={artist.name} className="w-full h-full object-cover rounded-full" />
                ) : (
                  <span className="text-white text-lg font-semibold">
                    {artist.name.charAt(0).toUpperCase()}
                  </span>
                )}
              </div>
              <h3 className="font-medium text-gray-900 text-sm truncate">{artist.name}</h3>
              {artist.location && (
                <p className="text-xs text-gray-500 truncate">{artist.location}</p>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Genres populaires */}
      <div>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Genres populaires</h2>
        </div>
        
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {genres.map((genre) => (
            <div
              key={genre.id}
              className="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-all cursor-pointer text-center"
              style={{ backgroundColor: `${genre.color}15` }}
            >
              <div
                className="w-12 h-12 rounded-lg mx-auto mb-3 flex items-center justify-center"
                style={{ backgroundColor: genre.color }}
              >
                <span className="text-white text-lg">🎵</span>
              </div>
              <h3 className="font-medium text-gray-900 text-sm">{genre.name}</h3>
              <p className="text-xs text-gray-500">{genre.tracks_count || 0} morceaux</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default HomePage;