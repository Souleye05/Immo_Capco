#!/bin/bash

echo "🚀 Déploiement de l'application..."

# 1. Mettre l'application en mode maintenance
echo "📝 Activation du mode maintenance..."
php artisan down

# 2. Vider les caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 3. Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# 4. Exécuter les seeders (seulement pour les rôles et permissions)
echo "🌱 Exécution des seeders de base..."
php artisan db:seed --class=RolePermissionSeeder --force

# 5. Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Désactiver le mode maintenance
echo "✅ Désactivation du mode maintenance..."
php artisan up

echo "🎉 Déploiement terminé avec succès !"