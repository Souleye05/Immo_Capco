<style>
    body {
        background-image: url('/images/jules.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    .fi-simple-main {
        background-color: rgba(255, 255, 255, 0.25);
        /* Avant : 0.1 */
        padding: 2rem;
        border-radius: 12px;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #8DC63E;
    }


    .fi-simple-main input {
        background-color: rgba(255, 255, 255, 0.35);
        border: none;
        color: #000;
    }


    .fi-simple-main label {
        color: #8DC63E;
    }

    .fi-simple-main button {
        background-color: rgba(255, 255, 255, 0.4);
        border: none;
        color: #fff;
        transition: background-color 0.3s ease;
    }

    .fi-simple-main button:hover {
        background-color: rgba(255, 255, 255, 0.6);
    }


    /* Placeholder visible */
    .fi-simple-main input::placeholder {
        color: #ddd !important;
        opacity: 1;
    }

    /* Messages d'erreur en rouge clair */
    .fi-simple-main .fi-error,
    .fi-simple-main .text-danger,
    .fi-simple-main .text-red-500 {
        color: #ffb3b3 !important;
    }

    /* Liens dans le formulaire */
    .fi-simple-main a {
        color: #cce6ff !important;
        text-decoration: underline;
    }

    /* Textes de description ou titre */
    .fi-simple-main .fi-heading,
    .fi-simple-main .fi-description,
    .fi-simple-main .fi-text {
        color: #fff !important;
    }
</style>