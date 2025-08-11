<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Owner;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Création des enregistrements Owner pour les utilisateurs...\n\n";

// Récupérer tous les utilisateurs avec le rôle 'owner'
$ownerUsers = User::whereHas('roles', function ($q) {
  $q->where('name', 'owner');
})->get();

echo "Utilisateurs avec le rôle 'owner' trouvés : " . $ownerUsers->count() . "\n\n";

foreach ($ownerUsers as $user) {
  echo "Traitement de l'utilisateur : {$user->email} ({$user->name})\n";

  // Vérifier si l'utilisateur a déjà un enregistrement Owner
  $existingOwner = Owner::where('user_id', $user->id)->first();

  if (!$existingOwner) {
    // Créer un enregistrement Owner pour cet utilisateur
    $owner = Owner::create([
      'name' => $user->name,
      'phone' => '77 123 45 67', // Numéro fictif
      'user_id' => $user->id,
    ]);

    echo "  ✅ Enregistrement Owner créé (ID: {$owner->id})\n";
  } else {
    echo "  ℹ️  Enregistrement Owner existe déjà (ID: {$existingOwner->id})\n";
  }
  echo "\n";
}

echo "🎉 Traitement terminé !\n";
