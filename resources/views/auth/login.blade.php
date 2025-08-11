<!DOCTYPE html>
<html lang="fr" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - Immo CapCo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background-image: url('/images/jules.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
      height: 100vh;
    }

    .login-container {
      background-color: rgba(255, 255, 255, 0.25);
      padding: 2rem;
      border-radius: 12px;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .login-container input {
      background-color: rgba(255, 255, 255, 0.35) !important;
      border: none !important;
      color: #000 !important;
      border-radius: 8px;
    }

    .login-container input::placeholder {
      color: #666 !important;
      opacity: 1;
    }

    .login-container label {
      color: #8DC63E !important;
      font-weight: 600;
    }

    .login-container button {
      background-color: #8DC63E !important;
      border: none !important;
      color: white !important;
      transition: all 0.3s ease;
      border-radius: 8px;
    }

    .login-container button:hover {
      background-color: rgba(141, 198, 62, 0.8) !important;
      transform: translateY(-1px);
    }

    .login-container .text-red-600 {
      color: #ffb3b3 !important;
    }

    .login-container a {
      color: #8DC63E !important;
      text-decoration: underline;
    }

    .login-container a:hover {
      color: rgba(141, 198, 62, 0.8) !important;
    }

    .login-container h2 {
      color: white !important;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .login-container p {
      color: rgba(255, 255, 255, 0.9) !important;
    }

    .access-info {
      background-color: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(4px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .access-info .font-semibold {
      color: white !important;
    }

    .access-info div:not(.font-semibold) {
      color: rgba(255, 255, 255, 0.8) !important;
    } 
  </style>
</head>

<body class="h-full">
  <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- Logo -->
      <div class="flex justify-center">
        <img class="h-20 w-auto" src="{{ asset('images/logo.png') }}" alt="Immo CapCo">
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        Connexion à Immo CapCo
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Accédez à votre espace personnalisé
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="login-container">
        <form class="space-y-6" action="{{ route('login.submit') }}" method="POST">
          @csrf

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Adresse email
            </label>
            <div class="mt-1">
              <input id="email" name="email" type="email" autocomplete="email" required
                value="{{ old('email') }}"
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <!-- Mot de passe -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Mot de passe
            </label>
            <div class="mt-1">
              <input id="password" name="password" type="password" autocomplete="current-password" required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            @error('password')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <!-- Sélection d'agence supprimée - redirection automatique vers le panel approprié -->

          <!-- Se souvenir de moi -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input id="remember" name="remember" type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="remember" class="ml-2 block text-sm text-gray-900">
                Se souvenir de moi
              </label>
            </div>

            <div class="text-sm">
              <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                Mot de passe oublié ?
              </a>
            </div>
          </div>

          <!-- Bouton de connexion -->
          <div>
            <button type="submit"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              Se connecter
            </button>
          </div>
        </form>

        <!-- Informations sur les types d'accès -->
        <div class="mt-8">
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="access-info p-3 rounded-lg text-center">
              <div class="font-semibold text-blue-600">Locataires</div>
              <div>Espace personnel</div>
            </div>
            <div class="access-info p-3 rounded-lg text-center">
              <div class="font-semibold text-green-600">Propriétaires</div>
              <div>Gestion des biens</div>
            </div>
            <div class="access-info p-3 rounded-lg text-center">
              <div class="font-semibold text-amber-600">Administrateurs</div>
              <div>Gestion complète</div>
            </div>
            <div class="access-info p-3 rounded-lg text-center">
              <div class="font-semibold text-red-600">Super Admin</div>
              <div>Accès système</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Auto-focus sur le champ email au chargement
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('email').focus();
    });
  </script>
</body>

</html>