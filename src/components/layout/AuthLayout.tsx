// src/components/layout/AuthLayout.tsx
import React from 'react';

interface AuthLayoutProps {
  children: React.ReactNode;
}

const AuthLayout: React.FC<AuthLayoutProps> = ({ children }) => {
  return (
    <div className="min-h-screen flex">
      {/* Section gauche - Illustration/Brand */}
      <div className="flex-1 hidden lg:flex items-center justify-center bg-gradient-to-br from-music-500 to-secondary-500 relative overflow-hidden">
        <div className="absolute inset-0 bg-black/20"></div>
        <div className="relative z-10 text-center text-white px-8">
          <div className="mb-8">
            <h1 className="text-6xl font-bold font-display mb-4">🎵 Kore</h1>
            <p className="text-xl opacity-90 max-w-md mx-auto">
              Découvrez, partagez et explorez la musique autour de vous
            </p>
          </div>
          
          {/* Animation musicale */}
          <div className="flex justify-center space-x-2 mb-8">
            {[...Array(5)].map((_, i) => (
              <div
                key={i}
                className="w-1 bg-white/60 rounded-full animate-pulse"
                style={{
                  height: `${Math.random() * 40 + 20}px`,
                  animationDelay: `${i * 0.1}s`,
                  animationDuration: '1.5s'
                }}
              ></div>
            ))}
          </div>
          
          <div className="text-sm opacity-75">
            Rejoignez une communauté passionnée de musique
          </div>
        </div>
        
        {/* Motif de fond */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-10 left-10 w-20 h-20 border border-white rounded-full"></div>
          <div className="absolute bottom-20 right-20 w-32 h-32 border border-white rounded-full"></div>
          <div className="absolute top-1/2 left-1/4 w-16 h-16 border border-white rounded-full"></div>
        </div>
      </div>

      {/* Section droite - Formulaire */}
      <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div className="max-w-md w-full">
          {children}
        </div>
      </div>
    </div>
  );
};

export default AuthLayout;