// src/pages/ProfilePage.tsx
import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useAppSelector } from '../store';
import { usersAPI, musicAPI, playlistsAPI } from '../services/api';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import toast from 'react-hot-toast';

interface UserProfile {
  id: number;
  name: string;
  email: string;
  bio?: string;
  location?: string;
  avatar?: string;
  is_private: boolean;
}

interface UserStats {
  tracks_count: number;
  followers_count: number;
  following_count: number;
  playlists_count: number;
  total_plays: number;
}

interface Track {
  id: number;
  title: string;
  artist: string;
  genre: string;
  duration: number;
  play_count: number;
  cover_image?: string;
}

interface Playlist {
  id: number;
  name: string;
  description?: string;
  cover_image?: string;
  tracks_count: number;
  is_public: boolean;
}

const ProfilePage: React.FC = () => {
  const { userId } = useParams<{ userId: string }>();
  const { user: currentUser } = useAppSelector((state) => state.auth);
  
  const [profileUser, setProfileUser] = useState<UserProfile | null>(null);
  const [stats, setStats] = useState<UserStats | null>(null);
  const [tracks, setTracks] = useState<Track[]>([]);
  const [playlists, setPlaylists] = useState<Playlist[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'tracks' | 'playlists' | 'activity'>('tracks');
  const [isFollowing, setIsFollowing] = useState(false);
  
  const isOwnProfile = !userId || parseInt(userId) === currentUser?.id;
  const targetUserId = userId ? parseInt(userId) : currentUser?.id;

  useEffect(() => {
    const fetchProfileData = async () => {
      if (!targetUserId) return;
      
      try {
        setLoading(true);
        
        if (isOwnProfile) {
          // Profil personnel - utiliser les données du store et l'API /me
          setProfileUser(currentUser);
          
          const [tracksRes, playlistsRes] = await Promise.all([
            musicAPI.getTracks({ user_id: targetUserId }),
            playlistsAPI.getPlaylists({ user_id: targetUserId }),
          ]);

          setTracks(tracksRes.data.data.data);
          setPlaylists(playlistsRes.data.data.data);
          
          // Stats fictives pour le moment
          setStats({
            tracks_count: tracksRes.data.data.data.length,
            followers_count: 42,
            following_count: 18,
            playlists_count: playlistsRes.data.data.data.length,
            total_plays: tracksRes.data.data.data.reduce((sum, track) => sum + track.play_count, 0),
          });
          
        } else {
          // Profil d'un autre utilisateur
          const [userRes, tracksRes, playlistsRes] = await Promise.all([
            usersAPI.getUser(targetUserId),
            musicAPI.getTracks({ user_id: targetUserId }),
            playlistsAPI.getPlaylists({ user_id: targetUserId }),
          ]);

          setProfileUser(userRes.data.data.user);
          setStats(userRes.data.data.stats);
          setTracks(tracksRes.data.data.data);
          setPlaylists(playlistsRes.data.data.data.filter((p: any) => p.is_public));
          setIsFollowing(userRes.data.data.relations?.is_following || false);
        }
        
      } catch (error) {
        console.error('Erreur lors du chargement du profil:', error);
        toast.error('Erreur lors du chargement du profil');
      } finally {
        setLoading(false);
      }
    };

    fetchProfileData();
  }, [targetUserId, isOwnProfile, currentUser]);

  const handleFollow = async () => {
    if (!targetUserId || isOwnProfile) return;
    
    try {
      if (isFollowing) {
        await usersAPI.unfollowUser(targetUserId);
        setIsFollowing(false);
        toast.success('Utilisateur non suivi');
      } else {
        await usersAPI.followUser(targetUserId);
        setIsFollowing(true);
        toast.success('Utilisateur suivi !');
      }
    } catch (error) {
      toast.error('Erreur lors de l\'action');
    }
  };

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

  if (!profileUser) {
    return (
      <div className="text-center py-12">
        <h2 className="text-2xl font-bold text-gray-900 mb-4">Utilisateur non trouvé</h2>
        <p className="text-gray-600">Cet utilisateur n'existe pas ou son profil est privé.</p>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto space-y-8">
      {/* En-tête du profil */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Banner */}
        <div className="h-48 bg-gradient-to-r from-music-500 to-secondary-500 relative">
          <div className="absolute inset-0 bg-black bg-opacity-20"></div>
        </div>
        
        {/* Informations utilisateur */}
        <div className="relative px-6 pb-6">
          {/* Avatar */}
          <div className="flex items-end justify-between -mt-16 mb-4">
            <div className="flex items-end space-x-6">
              <div className="w-32 h-32 bg-white rounded-2xl p-2 shadow-lg">
                <div className="w-full h-full bg-gradient-to-br from-music-400 to-music-600 rounded-xl flex items-center justify-center">
                  {profileUser.avatar ? (
                    <img 
                      src={profileUser.avatar} 
                      alt={profileUser.name} 
                      className="w-full h-full object-cover rounded-xl" 
                    />
                  ) : (
                    <span className="text-4xl font-bold text-white">
                      {profileUser.name.charAt(0).toUpperCase()}
                    </span>
                  )}
                </div>
              </div>
              
              <div className="pb-4">
                <h1 className="text-3xl font-bold text-gray-900">{profileUser.name}</h1>
                {profileUser.location && (
                  <p className="text-gray-600 flex items-center mt-1">
                    <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {profileUser.location}
                  </p>
                )}
              </div>
            </div>
            
            {/* Boutons d'action */}
            <div className="flex items-center space-x-3 pb-4">
              {!isOwnProfile && (
                <button
                  onClick={handleFollow}
                  className={`px-6 py-2 rounded-lg font-medium transition-colors ${
                    isFollowing
                      ? 'bg-gray-200 text-gray-800 hover:bg-gray-300'
                      : 'bg-music-600 text-white hover:bg-music-700'
                  }`}
                >
                  {isFollowing ? 'Ne plus suivre' : 'Suivre'}
                </button>
              )}
              
              <button className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                </svg>
              </button>
              
              <button className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
            </div>
          </div>
          
          {/* Bio */}
          {profileUser.bio && (
            <p className="text-gray-700 mb-6 max-w-2xl">{profileUser.bio}</p>
          )}
          
          {/* Statistiques */}
          {stats && (
            <div className="grid grid-cols-2 md:grid-cols-5 gap-6">
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{stats.tracks_count}</div>
                <div className="text-sm text-gray-600">Morceaux</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{stats.playlists_count}</div>
                <div className="text-sm text-gray-600">Playlists</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{stats.followers_count}</div>
                <div className="text-sm text-gray-600">Abonnés</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{stats.following_count}</div>
                <div className="text-sm text-gray-600">Abonnements</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{stats.total_plays.toLocaleString()}</div>
                <div className="text-sm text-gray-600">Écoutes</div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Navigation des onglets */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="border-b border-gray-200">
          <nav className="flex space-x-8 px-6">
            {[
              { key: 'tracks', label: 'Morceaux', count: tracks.length },
              { key: 'playlists', label: 'Playlists', count: playlists.length },
              { key: 'activity', label: 'Activité', count: null },
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key as any)}
                className={`py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                  activeTab === tab.key
                    ? 'border-music-500 text-music-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {tab.label}
                {tab.count !== null && (
                  <span className="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">
                    {tab.count}
                  </span>
                )}
              </button>
            ))}
          </nav>
        </div>

        {/* Contenu des onglets */}
        <div className="p-6">
          {activeTab === 'tracks' && (
            <div className="space-y-4">
              {tracks.length === 0 ? (
                <div className="text-center py-12">
                  <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                  </svg>
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Aucun morceau</h3>
                  <p className="text-gray-600">
                    {isOwnProfile ? 'Vous n\'avez pas encore publié de morceaux.' : 'Cet utilisateur n\'a pas encore publié de morceaux.'}
                  </p>
                </div>
              ) : (
                tracks.map((track) => (
                  <div key={track.id} className="flex items-center space-x-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
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
                      <div className="flex items-center space-x-4 mt-1">
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-music-100 text-music-800">
                          {track.genre}
                        </span>
                        <span className="text-xs text-gray-500">{formatDuration(track.duration)}</span>
                        <span className="text-xs text-gray-500">{track.play_count} écoutes</span>
                      </div>
                    </div>
                    
                    <button className="p-2 text-music-600 hover:bg-music-50 rounded-lg transition-colors">
                      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                      </svg>
                    </button>
                  </div>
                ))
              )}
            </div>
          )}

          {activeTab === 'playlists' && (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {playlists.length === 0 ? (
                <div className="col-span-full text-center py-12">
                  <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Aucune playlist</h3>
                  <p className="text-gray-600">
                    {isOwnProfile ? 'Vous n\'avez pas encore créé de playlists.' : 'Cet utilisateur n\'a pas de playlists publiques.'}
                  </p>
                </div>
              ) : (
                playlists.map((playlist) => (
                  <div key={playlist.id} className="bg-gray-50 rounded-xl p-6 hover:shadow-md transition-shadow cursor-pointer">
                    <div className="w-full h-32 bg-gradient-to-br from-secondary-400 to-secondary-600 rounded-lg mb-4 flex items-center justify-center">
                      {playlist.cover_image ? (
                        <img src={playlist.cover_image} alt={playlist.name} className="w-full h-full object-cover rounded-lg" />
                      ) : (
                        <svg className="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                      )}
                    </div>
                    <h3 className="font-semibold text-gray-900 mb-2 truncate">{playlist.name}</h3>
                    {playlist.description && (
                      <p className="text-sm text-gray-600 mb-3 line-clamp-2">{playlist.description}</p>
                    )}
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-500">{playlist.tracks_count} morceaux</span>
                      <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                        playlist.is_public 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {playlist.is_public ? 'Public' : 'Privé'}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>
          )}

          {activeTab === 'activity' && (
            <div className="text-center py-12">
              <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <h3 className="text-lg font-medium text-gray-900 mb-2">Activité récente</h3>
              <p className="text-gray-600">Fonctionnalité en cours de développement</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ProfilePage;