<?php
/**
 * Page d'inscription - HairRoots
 * Crée un compte utilisateur et enregistre l'adresse comme adresse principale
 */

require_once '../config/database.php';
$page_title = 'Inscription - HairRoots';

$error   = '';
$success = '';

/* =========================================================
   TRAITEMENT DU FORMULAIRE D'INSCRIPTION
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- Récupération et nettoyage des champs --- */
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name       = trim($_POST['first_name']);
    $last_name        = trim($_POST['last_name']);
    $address          = trim($_POST['address']);
    $city             = trim($_POST['city']);
    $postal_code      = trim($_POST['postal_code']);

    /* --- Validation des champs obligatoires --- */
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($address) || empty($city) || empty($postal_code)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une lettre majuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un caractere special (!@#$%...).";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {

        /* --- Vérification que l'username et l'email ne sont pas déjà pris --- */
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur ou email est deja utilise.";
        } else {

            /* --- Hashage du mot de passe --- */
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            /* --- Insertion dans la table users --- */
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, address, city, postal_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $address, $city, $postal_code])) {

                /* --- Récupération de l'id du nouvel utilisateur --- */
                $new_user_id = $pdo->lastInsertId();
                require_once '../includes/Mailer.php';
                        $mailer = new Mailer();
                        $mailer->sendWelcome($email, $first_name, $last_name);

                /* -------------------------------------------------------
                   Enregistrement de l'adresse d'inscription dans la table
                   user_addresses en tant qu'adresse principale.
                   ------------------------------------------------------- */
                $stmt_addr = $pdo->prepare("
                    INSERT INTO user_addresses
                        (user_id, nom, prenom, adresse, complement, code_postal, ville, pays, telephone, est_principale)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_addr->execute([
                    $new_user_id,
                    $last_name,
                    $first_name,
                    $address,
                    '',
                    $postal_code,
                    $city,
                    'France',
                    '',
                    1
                ]);

                /* --- Redirection vers la page de connexion après succès --- */
                $success = "Inscription reussie ! Redirection en cours...";
                header("refresh:2;url=login.php?registered=1");

            } else {
                $error = "Erreur lors de l'inscription. Veuillez reessayer.";
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
/* =========================================================
   STYLES DE LA PAGE D'INSCRIPTION
   ========================================================= */

.auth-page {
    min-height: 80vh;
    background: linear-gradient(135deg, #FDF0E8 0%, #FDEBD0 50%, #F5E6D3 100%);
    display: flex;
    align-items: center;
    padding: 60px 0;
}

.auth-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(62,31,13,0.12);
    overflow: hidden;
    border: none;
}

.auth-left {
    background: linear-gradient(135deg, #3E1F0D, #6B3A2A);
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.auth-left h2 {
    font-family: 'Playfair Display', serif;
    color: #C9A84C;
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 15px;
}
.auth-left p {
    color: #e8d5b7;
    font-size: 0.92rem;
    line-height: 1.7;
}

.auth-badge {
    background: rgba(201,168,76,0.15);
    border: 1px solid rgba(201,168,76,0.4);
    color: #C9A84C;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    margin: 4px;
    display: inline-block;
}

.auth-right { padding: 45px; }

.auth-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.9rem;
    font-weight: 700;
    color: #3E1F0D;
    margin-bottom: 5px;
}
.auth-subtitle {
    color: #9a7c5c;
    font-size: 0.92rem;
    margin-bottom: 25px;
}

.auth-input-group {
    position: relative;
    margin-bottom: 18px;
}
.auth-input-group label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #3E1F0D;
    margin-bottom: 6px;
    display: block;
}
.auth-input-group .input-icon {
    position: absolute;
    left: 14px;
    bottom: 12px;
    color: #C9A84C;
    font-size: 1rem;
    pointer-events: none;
}

.auth-input {
    width: 100%;
    border: 2px solid #F5E6D3;
    border-radius: 12px;
    padding: 12px 15px 12px 42px;
    font-size: 0.92rem;
    transition: all 0.3s;
    background: #FDFAF7;
    color: #3E1F0D;
}
.auth-input:focus {
    border-color: #C9A84C;
    box-shadow: 0 0 0 3px rgba(201,168,76,0.15);
    outline: none;
    background: #fff;
}

.auth-input-no-icon {
    width: 100%;
    border: 2px solid #F5E6D3;
    border-radius: 12px;
    padding: 12px 15px;
    font-size: 0.92rem;
    transition: all 0.3s;
    background: #FDFAF7;
    color: #3E1F0D;
}
.auth-input-no-icon:focus {
    border-color: #C9A84C;
    box-shadow: 0 0 0 3px rgba(201,168,76,0.15);
    outline: none;
    background: #fff;
}

/* ---- AMÉLIORATION 1 : Champ mot de passe avec icône œil ---- */
.password-wrapper {
    position: relative;
}
/* Le champ a une icône à gauche (cadenas) ET un bouton à droite (œil) */
.password-wrapper .auth-input {
    padding-right: 44px; /* espace pour le bouton œil */
}
.btn-toggle-pwd {
    position: absolute;
    right: 12px;
    bottom: 10px;
    background: none;
    border: none;
    padding: 2px 4px;
    cursor: pointer;
    color: #9a7c5c;
    font-size: 1.05rem;
    line-height: 1;
    transition: color 0.2s;
}
.btn-toggle-pwd:hover { color: #C9A84C; }

/* ---- AMÉLIORATION 2 : Suggestions d'autocomplétion adresse ---- */
.autocomplete-wrapper {
    position: relative;
}
.autocomplete-suggestions {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid #C9A84C;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(62,31,13,0.14);
    z-index: 9999;
    max-height: 220px;
    overflow-y: auto;
    display: none; /* caché par défaut */
}
.autocomplete-suggestions.open { display: block; }
.autocomplete-item {
    padding: 10px 14px;
    font-size: 0.86rem;
    color: #3E1F0D;
    cursor: pointer;
    border-bottom: 1px solid #F5E6D3;
    line-height: 1.4;
    transition: background 0.15s;
}
.autocomplete-item:last-child { border-bottom: none; }
.autocomplete-item:hover,
.autocomplete-item.active { background: #FDF0E8; }
.autocomplete-item strong { color: #C1622F; }
.autocomplete-spinner {
    padding: 10px 14px;
    font-size: 0.84rem;
    color: #9a7c5c;
    text-align: center;
}

.btn-auth {
    background: linear-gradient(135deg, #C9A84C, #b8942e);
    color: #3E1F0D;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-size: 1rem;
    font-weight: 700;
    width: 100%;
    transition: all 0.3s;
    cursor: pointer;
}
.btn-auth:hover {
    background: linear-gradient(135deg, #C1622F, #a0491f);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(193,98,47,0.3);
}

.auth-link {
    color: #C1622F;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.3s;
}
.auth-link:hover { color: #3E1F0D; }

.alert-hairroots {
    border-radius: 12px;
    border: none;
    padding: 12px 16px;
    font-size: 0.88rem;
    margin-bottom: 20px;
}
.alert-hairroots.error {
    background: #fce4e4;
    color: #c62828;
    border-left: 4px solid #c62828;
}
.alert-hairroots.success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #2e7d32;
}

.password-strength { margin-top: 8px; }
.strength-bar {
    height: 4px;
    border-radius: 4px;
    background: #F5E6D3;
    margin-bottom: 6px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    border-radius: 4px;
    width: 0%;
    transition: all 0.4s;
}
.strength-text { font-size: 0.75rem; font-weight: 600; }

.criteria-list { list-style: none; padding: 0; margin: 8px 0 0; }
.criteria-list li {
    font-size: 0.75rem;
    color: #c62828;
    margin-bottom: 2px;
    transition: color 0.3s;
}
.criteria-list li.valid { color: #2e7d32; }
.criteria-list li::before { content: '✗ '; }
.criteria-list li.valid::before { content: '✓ '; }

.password-hint { font-size: 0.78rem; color: #9a7c5c; margin-top: 4px; }
</style>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-11">
                <div class="auth-card">
                    <div class="row g-0">

                        <!-- Panneau gauche décoratif -->
                        <div class="col-lg-4 d-none d-lg-block">
                            <div class="auth-left h-100">
                                <h2>Rejoignez HairRoots !</h2>
                                <p>Creez votre compte et profitez d'une experience capillaire unique, adaptee a vos cheveux.</p>
                                <div class="mt-4">
                                    <span class="auth-badge">Boucles</span>
                                    <span class="auth-badge">Crepus</span>
                                    <span class="auth-badge">Lisses</span>
                                    <span class="auth-badge">Ondules</span>
                                    <span class="auth-badge">Femmes</span>
                                    <span class="auth-badge">Hommes</span>
                                    <span class="auth-badge">Enfants</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panneau droit : formulaire d'inscription -->
                        <div class="col-lg-8">
                            <div class="auth-right">

                                <h3 class="auth-title">Creer un compte</h3>
                                <p class="auth-subtitle">Rejoignez la communaute HairRoots des aujourd'hui !</p>

                                <?php if ($error): ?>
                                    <div class="alert-hairroots error">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert-hairroots success">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">

                                    <!-- Prénom et Nom -->
                                    <div class="row g-3 mb-0">
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Prenom</label>
                                                <input type="text" class="auth-input-no-icon" name="first_name"
                                                       value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>"
                                                       placeholder="Votre prenom" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Nom</label>
                                                <input type="text" class="auth-input-no-icon" name="last_name"
                                                       value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>"
                                                       placeholder="Votre nom" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nom d'utilisateur -->
                                    <div class="auth-input-group">
                                        <label>Nom d'utilisateur</label>
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" class="auth-input" name="username"
                                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                               placeholder="Choisissez un pseudo unique" required>
                                    </div>

                                    <!-- =====================================================
                                         AMÉLIORATION 2 : Adresse avec autocomplétion OSM
                                         L'API Nominatim (OpenStreetMap) est gratuite et ne
                                         nécessite aucune clé. On envoie une requête après
                                         300 ms de pause de frappe (debounce) pour ne pas
                                         surcharger le service.
                                         ===================================================== -->
                                    <div class="auth-input-group">
                                        <label>Adresse de livraison</label>
                                        <i class="bi bi-geo-alt input-icon"></i>
                                        <div class="autocomplete-wrapper">
                                            <input type="text"
                                                   class="auth-input"
                                                   name="address"
                                                   id="address"
                                                   value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>"
                                                   placeholder="Commencez a taper votre adresse…"
                                                   autocomplete="off"
                                                   required>
                                            <!-- Dropdown des suggestions -->
                                            <div class="autocomplete-suggestions" id="addressSuggestions"></div>
                                        </div>
                                    </div>

                                    <!-- Ville et Code postal (remplis automatiquement par la suggestion) -->
                                    <div class="row g-3 mb-0">
                                        <div class="col-md-8">
                                            <div class="auth-input-group">
                                                <label>Ville</label>
                                                <input type="text" class="auth-input-no-icon" name="city"
                                                       id="city"
                                                       value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>"
                                                       placeholder="Votre ville" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="auth-input-group">
                                                <label>Code postal</label>
                                                <input type="text" class="auth-input-no-icon" name="postal_code"
                                                       id="postal_code"
                                                       value="<?= isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : '' ?>"
                                                       placeholder="75000" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="auth-input-group">
                                        <label>Adresse email</label>
                                        <i class="bi bi-envelope input-icon"></i>
                                        <input type="email" class="auth-input" name="email"
                                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                               placeholder="votre@email.com" required>
                                    </div>

                                    <!-- =====================================================
                                         AMÉLIORATION 1 : Mot de passe avec bouton œil
                                         Un <button type="button"> positionné en absolu à
                                         droite du champ. Au clic il bascule le type entre
                                         "password" et "text" et change l'icône Bootstrap.
                                         ===================================================== -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Mot de passe</label>
                                                <i class="bi bi-lock input-icon"></i>
                                                <div class="password-wrapper">
                                                    <input type="password"
                                                           class="auth-input"
                                                           name="password"
                                                           id="password"
                                                           placeholder="Min. 8 caracteres"
                                                           required>
                                                    <button type="button"
                                                            class="btn-toggle-pwd"
                                                            id="togglePwd"
                                                            aria-label="Afficher / masquer le mot de passe"
                                                            title="Afficher / masquer">
                                                        <i class="bi bi-eye" id="eyeIcon1"></i>
                                                    </button>
                                                </div>
                                                <div class="password-strength">
                                                    <div class="strength-bar">
                                                        <div class="strength-fill" id="strengthFill"></div>
                                                    </div>
                                                    <span class="strength-text" id="strengthText"></span>
                                                    <ul class="criteria-list" id="criteriaList">
                                                        <li id="c-length">Au moins 8 caracteres</li>
                                                        <li id="c-upper">Une lettre majuscule</li>
                                                        <li id="c-number">Un chiffre</li>
                                                        <li id="c-special">Un caractere special (!@#$%...)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Confirmer le mot de passe</label>
                                                <i class="bi bi-lock-fill input-icon"></i>
                                                <div class="password-wrapper">
                                                    <input type="password"
                                                           class="auth-input"
                                                           name="confirm_password"
                                                           id="confirm_password"
                                                           placeholder="Repetez le mot de passe"
                                                           required>
                                                    <button type="button"
                                                            class="btn-toggle-pwd"
                                                            id="toggleConfirmPwd"
                                                            aria-label="Afficher / masquer la confirmation"
                                                            title="Afficher / masquer">
                                                        <i class="bi bi-eye" id="eyeIcon2"></i>
                                                    </button>
                                                </div>
                                                <p class="password-hint" id="matchHint"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Acceptation des CGU -->
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="cgu" required
                                               style="border-color:#C9A84C;">
                                        <label class="form-check-label small" for="cgu" style="color:#6B3A2A;">
                                            J'accepte les <a href="#" class="auth-link">conditions generales</a>
                                            et la <a href="#" class="auth-link">politique de confidentialite</a>
                                        </label>
                                    </div>

                                    <!-- Bouton de soumission -->
                                    <button type="submit" class="btn-auth">
                                        Creer mon compte HairRoots
                                    </button>

                                </form>

                                <p class="text-center mt-4 mb-0" style="font-size:0.92rem;color:#6B3A2A;">
                                    Deja un compte ?
                                    <a href="login.php" class="auth-link">Se connecter</a>
                                </p>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ==========================================================
   AMÉLIORATION 1 — Afficher / Masquer le mot de passe
   Bascule le type du champ entre "password" et "text"
   et swap l'icône Bootstrap Icons eye / eye-slash.
   ========================================================== */
function setupTogglePassword(btnId, inputId, iconId) {
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);

    btn.addEventListener('click', function () {
        const isHidden = input.type === 'password';
        input.type     = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}

setupTogglePassword('togglePwd',        'password',         'eyeIcon1');
setupTogglePassword('toggleConfirmPwd', 'confirm_password', 'eyeIcon2');


/* ==========================================================
   INDICATEUR DE FORCE DU MOT DE PASSE
   ========================================================== */
const pwd        = document.getElementById('password');
const confirmPwd = document.getElementById('confirm_password');
const fill       = document.getElementById('strengthFill');
const text       = document.getElementById('strengthText');
const matchHint  = document.getElementById('matchHint');

pwd.addEventListener('input', function () {
    const val = this.value;

    const cLength  = val.length >= 8;
    const cUpper   = /[A-Z]/.test(val);
    const cNumber  = /[0-9]/.test(val);
    const cSpecial = /[\W_]/.test(val);

    document.getElementById('c-length').classList.toggle('valid', cLength);
    document.getElementById('c-upper').classList.toggle('valid', cUpper);
    document.getElementById('c-number').classList.toggle('valid', cNumber);
    document.getElementById('c-special').classList.toggle('valid', cSpecial);

    let score = [cLength, cUpper, cNumber, cSpecial].filter(Boolean).length;

    const levels = [
        { w: '0%',   color: '#F5E6D3', label: '' },
        { w: '25%',  color: '#c62828', label: 'Tres faible' },
        { w: '50%',  color: '#e65100', label: 'Faible' },
        { w: '75%',  color: '#C9A84C', label: 'Moyen' },
        { w: '100%', color: '#2e7d32', label: 'Fort' },
    ];

    fill.style.width      = levels[score].w;
    fill.style.background = levels[score].color;
    text.textContent      = levels[score].label;
    text.style.color      = levels[score].color;
});

confirmPwd.addEventListener('input', function () {
    if (this.value === pwd.value) {
        matchHint.textContent = '✓ Les mots de passe correspondent';
        matchHint.style.color = '#2e7d32';
    } else {
        matchHint.textContent = '✗ Les mots de passe ne correspondent pas';
        matchHint.style.color = '#c62828';
    }
});


/* ==========================================================
   AMÉLIORATION 2 — Autocomplétion d'adresse via OpenStreetMap
   API utilisée : Nominatim (gratuite, sans clé)
   Endpoint    : https://nominatim.openstreetmap.org/search
   Paramètres  : q (requête), format=json, addressdetails=1,
                 countrycodes=fr (limité à la France),
                 limit=6 (max 6 suggestions)

   Bonnes pratiques Nominatim :
     - User-Agent obligatoire (passé via fetch headers)
     - Debounce de 300 ms pour ne pas sur-solliciter le service
     - Minimum 3 caractères avant de lancer la recherche
   ========================================================== */
(function () {
    const addressInput  = document.getElementById('address');
    const cityInput     = document.getElementById('city');
    const postalInput   = document.getElementById('postal_code');
    const dropdown      = document.getElementById('addressSuggestions');

    let debounceTimer   = null;   // timer pour le debounce
    let activeIndex     = -1;     // indice de la suggestion active (navigation clavier)
    let currentResults  = [];     // résultats de la dernière requête

    /* ---- Lance la recherche après 300 ms de pause de frappe ---- */
    addressInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 3) {
            closeDropdown();
            return;
        }

        debounceTimer = setTimeout(() => searchAddress(query), 300);
    });

    /* ---- Ferme le dropdown si on clique ailleurs ---- */
    document.addEventListener('click', function (e) {
        if (!addressInput.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    /* ---- Navigation clavier dans les suggestions ---- */
    addressInput.addEventListener('keydown', function (e) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActiveItem(items);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            selectResult(currentResults[activeIndex]);
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    /* ---- Requête vers l'API Nominatim ---- */
    async function searchAddress(query) {
        showSpinner();

        const url = 'https://nominatim.openstreetmap.org/search?'
            + new URLSearchParams({
                q             : query,
                format        : 'json',
                addressdetails : '1',
                countrycodes  : 'fr',   // restreint à la France
                limit         : '6',
            });

        try {
            const res  = await fetch(url, {
                headers: {
                    /* Nominatim demande un User-Agent identifiable */
                    'Accept-Language': 'fr',
                }
            });
            const data = await res.json();
            currentResults = data;
            renderSuggestions(data);
        } catch (err) {
            closeDropdown();
            console.warn('Nominatim error:', err);
        }
    }

    /* ---- Affiche les suggestions dans le dropdown ---- */
    function renderSuggestions(results) {
        dropdown.innerHTML = '';
        activeIndex = -1;

        if (!results.length) {
            dropdown.innerHTML = '<div class="autocomplete-spinner">Aucun résultat trouvé</div>';
            dropdown.classList.add('open');
            return;
        }

        results.forEach(function (place, i) {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';

            /* Mise en avant du numéro + rue si disponible */
            const addr   = place.address || {};
            const road   = addr.road || addr.pedestrian || addr.footway || '';
            const house  = addr.house_number || '';
            const city   = addr.city || addr.town || addr.village || addr.municipality || '';
            const postal = addr.postcode || '';

            const mainLine = [house, road].filter(Boolean).join(' ') || place.display_name.split(',')[0];
            const subLine  = [postal, city].filter(Boolean).join(' ');

            item.innerHTML = '<strong>' + escapeHtml(mainLine) + '</strong>'
                + (subLine ? '<br><span style="color:#9a7c5c;font-size:0.8rem;">' + escapeHtml(subLine) + '</span>' : '');

            item.addEventListener('mouseenter', function () {
                activeIndex = i;
                updateActiveItem(dropdown.querySelectorAll('.autocomplete-item'));
            });

            item.addEventListener('click', function () {
                selectResult(place);
            });

            dropdown.appendChild(item);
        });

        dropdown.classList.add('open');
    }

    /* ---- Remplit les champs quand l'utilisateur choisit une suggestion ---- */
    function selectResult(place) {
        if (!place) return;
        const addr = place.address || {};

        /* Reconstruit la ligne d'adresse (n° + rue) */
        const house  = addr.house_number || '';
        const road   = addr.road || addr.pedestrian || addr.footway || '';
        const street = [house, road].filter(Boolean).join(' ');

        addressInput.value = street || place.display_name.split(',')[0];
        cityInput.value    = addr.city || addr.town || addr.village || addr.municipality || '';
        postalInput.value  = addr.postcode || '';

        closeDropdown();
        /* Met le focus sur le champ suivant (email) pour fluidifier la saisie */
        document.querySelector('input[name="email"]').focus();
    }

    /* ---- Helpers ---- */
    function showSpinner() {
        dropdown.innerHTML = '<div class="autocomplete-spinner">Recherche en cours…</div>';
        dropdown.classList.add('open');
    }

    function closeDropdown() {
        dropdown.classList.remove('open');
        dropdown.innerHTML = '';
        activeIndex = -1;
    }

    function updateActiveItem(items) {
        items.forEach(function (el, i) {
            el.classList.toggle('active', i === activeIndex);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
</script>

<?php include '../includes/footer.php'; ?>