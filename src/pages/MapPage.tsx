import React, { useState, useEffect } from 'react';
import LoadingSpinner from '../components/ui/LoadingSpinner';

const MapPage: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [userLocation, setUserLocation] = useState<{ lat: number; lng: number } | null>(null);

  useEffect(() => {
    // Simuler le chargement de la carte
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);

    // Demander la géolocalisation
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setUserLocation({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          });
        },
        (error) => {
          console.log('Géolocalisation non autorisée:', error);
          // Utiliser la position par défaut (Casablanca)
          setUserLocation({
            lat: parseFloat(import.meta.env.VITE_DEFAULT_LATITUDE || '33.5731'),
            lng: parseFloat(import.meta.env.VITE_DEFAULT_LONGITUDE || '-7.5898'),
          });
        }
      );
    }

    return () => clearTimeout(timer);
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-96">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* En-tête */}
      <div className="text-center">
        <h1 className="text-4xl font-bold text-gray-900 mb-4">
          Explorez la musique autour de vous 🗺️
        </h1>
        <p className="text-xl text-gray-600 max-w-2xl mx-auto">
          Découvrez les morceaux partagés près de chez vous et explorez les tendances musicales par région
        </p>
      </div>

      {/* Contrôles de la carte */}
      <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <button className="px-4 py-2 bg-music-600 text-white rounded-lg hover:bg-music-700 transition-colors">
              Ma position
            </button>
            <select className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-music-500">
              <option>Tous les genres</option>
              <option>Pop</option>
              <option>Rock</option>
              <option>Hip-Hop</option>
              <option>Electronic</option>
            </select>
            <select className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-music-500">
              <option>Rayon : 10 km</option>
              <option>Rayon : 5 km</option>
              <option>Rayon : 25 km</option>
              <option>Rayon : 50 km</option>
            </select>
          </div>
          <div className="text-sm text-gray-600">
            {userLocation 
              ? `Position: ${userLocation.lat.toFixed(4)}, ${userLocation.lng.toFixed(4)}`
              : 'Localisation en cours...'
            }
          </div>
        </div>
      </div>

      {/* Zone de la carte */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="h-96 bg-gradient-to-br from-music-100 to-secondary-100 flex items-center justify-center">
          <div className="text-center">
            <div className="text-6xl mb-4">🗺️</div>
            <h3 className="text-xl font-semibold text-gray-700 mb-2">Carte interactive</h3>
            <p className="text-gray-600 mb-4">
              La carte sera intégrée ici avec Leaflet/OpenStreetMap
            </p>
            <div className="text-sm text-gray-500">
              Fonctionnalités prévues :
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>Marqueurs des posts musicaux géolocalisés</li>
                <li>Filtrage par genre et distance</li>
                <li>Clusters pour les zones denses</li>
                <li>Popup avec aperçu des morceaux</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Posts récents dans la région */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Morceaux récents dans votre région</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {Array.from({ length: 6 }, (_, i) => (
            <div key={i} className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
              <div className="flex items-start space-x-4">
                <div className="w-16 h-16 bg-gradient-to-br from-music-400 to-music-600 rounded-lg flex items-center justify-center">
                  <span className="text-white text-lg">🎵</span>
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-gray-900">Morceau {i + 1}</h3>
                  <p className="text-sm text-gray-600">Artiste {i + 1}</p>
                  <div className="flex items-center mt-2">
                    <svg className="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    <span className="text-xs text-gray-500">{Math.random() * 10 + 1} km</span>
                  </div>
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
    </div>
  );
};

export default MapPage;