<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activation de votre compte locataire</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    .activation-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 2rem;
      text-align: center;
    }

    .card-body {
      padding: 2rem;
    }

    .form-control {
      border-radius: 10px;
      border: 2px solid #e9ecef;
      padding: 12px 15px;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-activate {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px 30px;
      font-weight: bold;
      color: white;
      width: 100%;
    }

    .btn-activate:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      color: white;
    }

    .password-requirements {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-top: 10px;
      font-size: 0.9em;
    }

    .requirement {
      color: #6c757d;
      margin-bottom: 5px;
    }

    .requirement.valid {
      color: #28a745;
    }

    .agency-info {
      background-color: #e9ecef;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="activation-card">
          <div class="card-header">
            <h2 class="mb-0">🏠 Activation de votre compte</h2>
            <p class="mb-0 mt-2">Définissez votre mot de passe pour accéder à votre espace locataire</p>
          </div>

          <div class="card-body">
            <div class="agency-info">
              <strong>🏢 Agence :</strong> {{ $agency->name }}<br>
              <strong>📧 Votre email :</strong> {{ $email }}
            </div>

            @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('tenant.activate.submit') }}" id="activationForm">
              @csrf
              <input type="hidden" name="email" value="{{ $email }}">
              <input type="hidden" name="token" value="{{ $token }}">

              <div class="mb-3">
                <label for="password" class="form-label">
                  <strong>🔐 Nouveau mot de passe</strong>
                </label>
                <input type="password"
                  class="form-control @error('password') is-invalid @enderror"
                  id="password"
                  name="password"
                  required
                  placeholder="Entrez votre mot de passe">
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="password_confirmation" class="form-label">
                  <strong>🔐 Confirmer le mot de passe</strong>
                </label>
                <input type="password"
                  class="form-control"
                  id="password_confirmation"
                  name="password_confirmation"
                  required
                  placeholder="Confirmez votre mot de passe">
              </div>

              <div class="password-requirements">
                <strong>📋 Exigences du mot de passe :</strong>
                <div class="requirement" id="req-length">• Au moins 8 caractères</div>
                <div class="requirement" id="req-letter">• Au moins une lettre</div>
                <div class="requirement" id="req-case">• Au moins une majuscule et une minuscule</div>
                <div class="requirement" id="req-number">• Au moins un chiffre</div>
                <div class="requirement" id="req-symbol">• Au moins un symbole (!@#$%^&*)</div>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-activate">
                  ✅ Activer mon compte
                </button>
              </div>
            </form>

            <div class="text-center mt-4">
              <small class="text-muted">
                Une fois votre compte activé, vous pourrez vous connecter à votre espace locataire.<br>
                <strong>Besoin d'aide ?</strong> Contactez {{ $agency->name }}
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Validation en temps réel du mot de passe
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;

      // Vérifier la longueur
      const lengthReq = document.getElementById('req-length');
      if (password.length >= 8) {
        lengthReq.classList.add('valid');
      } else {
        lengthReq.classList.remove('valid');
      }

      // Vérifier les lettres
      const letterReq = document.getElementById('req-letter');
      if (/[a-zA-Z]/.test(password)) {
        letterReq.classList.add('valid');
      } else {
        letterReq.classList.remove('valid');
      }

      // Vérifier majuscules et minuscules
      const caseReq = document.getElementById('req-case');
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
        caseReq.classList.add('valid');
      } else {
        caseReq.classList.remove('valid');
      }

      // Vérifier les chiffres
      const numberReq = document.getElementById('req-number');
      if (/\d/.test(password)) {
        numberReq.classList.add('valid');
      } else {
        numberReq.classList.remove('valid');
      }

      // Vérifier les symboles
      const symbolReq = document.getElementById('req-symbol');
      if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        symbolReq.classList.add('valid');
      } else {
        symbolReq.classList.remove('valid');
      }
    });
  </script>
</body>

</html>