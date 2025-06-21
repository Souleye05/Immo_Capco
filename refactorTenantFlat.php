<?php
// refactorTenantFlat.php

$directory = __DIR__ . '/app'; // dossier à scanner, adapte si besoin

function refactorFile(string $filePath)
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // 1. with('flat') => with('flatThroughContract')
    $content = preg_replace("/with\(\s*['\"]flat['\"]\s*\)/", "with('flatThroughContract')", $content);

    // 2. ->flat (en accés simple, éviter les conflits)
    // Attention: on remplace '->flat' par '->flatThroughContract?'
    $content = preg_replace("/->flat(\b)/", "->flatThroughContract?", $content);

    // 3. tenant_id dans requêtes Flat::where('tenant_id', ...) => Avertissement (manuel)
    // Ici c’est plus compliqué à automatiser car c’est une logique métier, on affiche un warning.
    if (preg_match("/Flat::where\(\s*['\"]tenant_id['\"]\s*,/", $content)) {
        echo "[AVERTISSEMENT] Fichier $filePath contient une requête sur tenant_id dans Flat::where(...).\n";
        echo "Manuellement, remplacer par une requête via Contract.\n\n";
    }

    // 4. tenant_id partout (pour info, pas de remplacement automatique ici)
    // Tu peux rajouter des patterns si tu veux

    // Sauvegarder si modifié
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fichier modifié : $filePath\n";
    }
}

function scanDirectory(string $dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            refactorFile($file->getPathname());
        }
    }
}

echo "Début du refactoring...\n";
scanDirectory($GLOBALS['directory']);
echo "Refactoring terminé.\n";
