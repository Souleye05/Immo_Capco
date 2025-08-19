# Instructions de Déploiement - Correction des Rôles et Permissions

## Problème rencontré
Erreur lors de la connexion : `Table 'roles' doesn't exist`

## Solution

### Étape 1 : Se connecter au serveur de production
```bash
ssh user@dev.capco.sn
cd /path/to/application
```

### Étape 2 : Exécuter les migrations
```bash
php artisan migrate --force
```

### Étape 3 : Créer les rôles et permissions
```bash
php artisan db:seed --class=RolePermissionSeeder --force
```

### Étape 4 : Vérifier que l'utilisateur dev existe
```bash
php artisan user:check dev@capco.sn
```

### Étape 5 : Si l'utilisateur n'existe pas, le créer
```bash
php artisan user:create-dev
```

### Étape 6 : Nettoyer les caches
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Alternative : Commande tout-en-un
Au lieu des étapes 2-5, vous pouvez utiliser cette commande unique :
```bash
php artisan fix:roles --force
```

## Vérification
Après ces étapes, l'utilisateur devrait pouvoir se connecter avec :
- Email: dev@capco.sn  
- Mot de passe: dev@1234

## En cas de problème
Si les erreurs persistent, vérifier :
1. Les permissions des fichiers
2. La configuration de la base de données (.env)
3. Les logs Laravel : `tail -f storage/logs/laravel.log`