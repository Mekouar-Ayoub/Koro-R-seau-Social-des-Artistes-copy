#!/bin/bash

# Script de test automatisé pour l'API Kore
# Usage: ./test_kore_api.sh

BASE_URL="http://localhost:8000/api"
TOKEN=""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les résultats
print_result() {
    local test_name="$1"
    local status_code="$2"
    local expected_code="$3"
    
    if [ "$status_code" -eq "$expected_code" ]; then
        echo -e "${GREEN}✅ $test_name: OK (HTTP $status_code)${NC}"
    else
        echo -e "${RED}❌ $test_name: FAILED (HTTP $status_code, expected $expected_code)${NC}"
    fi
}

# Fonction pour extraire le token de la réponse JSON
extract_token() {
    echo "$1" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4
}

echo -e "${BLUE}🚀 Test de l'API Kore${NC}"
echo "=================================="

# Test 1: Vérification de base
echo -e "${YELLOW}1. Test de base de l'API...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/test")
print_result "API Base Test" "$response" "200"

# Test 2: Connexion admin
echo -e "${YELLOW}2. Connexion administrateur...${NC}"
login_response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@kore.com",
    "password": "password123"
  }')

status_code=$(echo "$login_response" | tail -n1)
response_body=$(echo "$login_response" | head -n -1)

print_result "Admin Login" "$status_code" "200"

if [ "$status_code" -eq "200" ]; then
    TOKEN=$(extract_token "$response_body")
    echo -e "${GREEN}Token récupéré: ${TOKEN:0:20}...${NC}"
fi

# Test 3: Profil utilisateur
echo -e "${YELLOW}3. Récupération du profil utilisateur...${NC}"
if [ ! -z "$TOKEN" ]; then
    response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/auth/me" \
      -H "Authorization: Bearer $TOKEN")
    print_result "User Profile" "$response" "200"
else
    echo -e "${RED}❌ Pas de token disponible${NC}"
fi

# Test 4: Liste des genres
echo -e "${YELLOW}4. Liste des genres musicaux...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/music/genres" \
  -H "Authorization: Bearer $TOKEN")
print_result "Music Genres" "$response" "200"

# Test 5: Liste des morceaux
echo -e "${YELLOW}5. Liste des morceaux...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/music/tracks" \
  -H "Authorization: Bearer $TOKEN")
print_result "Music Tracks" "$response" "200"

# Test 6: Liste des utilisateurs
echo -e "${YELLOW}6. Liste des utilisateurs...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users" \
  -H "Authorization: Bearer $TOKEN")
print_result "Users List" "$response" "200"

# Test 7: Posts musicaux
echo -e "${YELLOW}7. Posts musicaux...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/music/posts" \
  -H "Authorization: Bearer $TOKEN")
print_result "Music Posts" "$response" "200"

# Test 8: Feed personnel
echo -e "${YELLOW}8. Feed personnel...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/music/posts/feed" \
  -H "Authorization: Bearer $TOKEN")
print_result "Personal Feed" "$response" "200"

# Test 9: Playlists
echo -e "${YELLOW}9. Playlists...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/playlists" \
  -H "Authorization: Bearer $TOKEN")
print_result "Playlists" "$response" "200"

# Test 10: Notifications
echo -e "${YELLOW}10. Notifications...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/notifications" \
  -H "Authorization: Bearer $TOKEN")
print_result "Notifications" "$response" "200"

# Test 11: Préférences
echo -e "${YELLOW}11. Préférences utilisateur...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/preferences" \
  -H "Authorization: Bearer $TOKEN")
print_result "User Preferences" "$response" "200"

# Test 12: Statistiques d'écoute
echo -e "${YELLOW}12. Statistiques d'écoute...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/history/stats" \
  -H "Authorization: Bearer $TOKEN")
print_result "Listening Stats" "$response" "200"

# Test 13: Recherche
echo -e "${YELLOW}13. Recherche utilisateurs...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/search?q=Alice" \
  -H "Authorization: Bearer $TOKEN")
print_result "User Search" "$response" "200"

# Test 14: Géocodage
echo -e "${YELLOW}14. Géocodage...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/location/geocode?address=Casablanca" \
  -H "Authorization: Bearer $TOKEN")
print_result "Geocoding" "$response" "200"

# Test 15: Posts à proximité
echo -e "${YELLOW}15. Posts à proximité...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/location/nearby-posts?latitude=33.5731&longitude=-7.5898&radius=10" \
  -H "Authorization: Bearer $TOKEN")
print_result "Nearby Posts" "$response" "200"

# Test 16: Création d'un post (si on a un morceau)
echo -e "${YELLOW}16. Test de création de post...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/music/posts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "track_id": 1,
    "content": "Test post via script automatisé! 🎵",
    "latitude": 33.5731,
    "longitude": -7.5898,
    "location_name": "Casablanca, Maroc",
    "location_type": "manual"
  }')
print_result "Create Post" "$response" "201"

# Test 17: Like
echo -e "${YELLOW}17. Test de like...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/social/like" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "likeable_type": "track",
    "likeable_id": 1
  }')
print_result "Like Track" "$response" "200"

# Test 18: Commentaire
echo -e "${YELLOW}18. Test de commentaire...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/social/comments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "commentable_type": "track",
    "commentable_id": 1,
    "content": "Super morceau testé via script automatisé!"
  }')
print_result "Create Comment" "$response" "201"

# Test 19: Création playlist
echo -e "${YELLOW}19. Test de création de playlist...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/playlists" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Playlist Test Script",
    "description": "Playlist créée automatiquement par le script de test",
    "is_public": true,
    "is_collaborative": false
  }')
print_result "Create Playlist" "$response" "201"

# Test 20: Déconnexion
echo -e "${YELLOW}20. Déconnexion...${NC}"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $TOKEN")
print_result "Logout" "$response" "200"

echo ""
echo -e "${BLUE}=================================="
echo -e "🏁 Tests terminés!"
echo -e "=================================="${NC}