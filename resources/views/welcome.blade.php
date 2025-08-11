<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Connexion</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Nunito', sans-serif;
            height: 100vh;
            width: 100%;
        }

        /* Conteneur principal avec l'image de fond */
        .bg-container {
            width: 100%;
            height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-image: url('/images/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Styles pour le conteneur central */
        .center-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            /* Espace entre le bouton et le logo */
        }

        /* Styles pour le logo */
        .logo {
            /* Suppression des contraintes de taille pour utiliser la taille originale */
            max-width: 100%;
        }

        /* Styles pour le bouton de connexion */
        .login-button {
            display: inline-block;
            background-color: #8DC63E;
            color: white;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            font-size: 16px;
            font: bold;
            cursor: pointer;
            margin-bottom: 20px;
            /* Ajoute un espace supplémentaire sous le bouton */
        }

        .login-button:hover {
            background-color: white;
            color: #8DC63E;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Conteneur principal -->
    <div class="bg-container">
        <!-- Contenu centré -->
        <div class="center-content">
            <!-- Logo avec sa taille normale -->
            <img src="/images/logo.png" alt="Logo" class="logo">

            <!-- Bouton de connexion au-dessus du logo -->
            <a href="{{ route('login') }}" class="login-button">Se connecter</a>

        </div>
    </div>
</body>

</html>