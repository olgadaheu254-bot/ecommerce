<?php
require_once '../config/database.php';
$page_title = 'Inscription - HairRoots';

$error   = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name       = trim($_POST['first_name']);
    $last_name        = trim($_POST['last_name']);
    $address          = trim($_POST['address']);
    $city             = trim($_POST['city']);
    $postal_code      = trim($_POST['postal_code']);

    if(empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($address) || empty($city) || empty($postal_code)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif(strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une lettre majuscule.";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif(!preg_match('/[\W_]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%...).";
    } elseif($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if($stmt->fetch()) {
            $error = "Ce nom d'utilisateur ou email est déjà utilisé.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $address, $city, $postal_code])) {
                $success = "Inscription réussie ! Redirection en cours...";
                header("refresh:2;url=login.php?registered=1");
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
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
.auth-left p { color: #e8d5b7; font-size: 0.92rem; line-height: 1.7; }
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
.auth-subtitle { color: #9a7c5c; font-size: 0.92rem; margin-bottom: 25px; }
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
.password-hint { font-size: 0.78rem; color: #9a7c5c; margin-top: 4px; }

/* Indicateur force mot de passe */
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
</style>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-11">
                <div class="auth-card">
                    <div class="row g-0">

                        <!-- GAUCHE décorative -->
                        <div class="col-lg-4 d-none d-lg-block">
                            <div class="auth-left h-100">
                                <div style="font-size:5rem;margin-bottom:20px;"></div>
                                <h2>Rejoignez HairRoots !</h2>
                                <p>Créez votre compte et profitez d'une expérience capillaire unique, adaptée à vos cheveux.</p>
                                <div class="mt-4">
                                    <span class="auth-badge"> Bouclés</span>
                                    <span class="auth-badge"> Crépus</span>
                                    <span class="auth-badge"> Lisses</span>
                                    <span class="auth-badge"> Ondulés</span>
                                    <span class="auth-badge"> Femmes</span>
                                    <span class="auth-badge"> Hommes</span>
                                    <span class="auth-badge"> Enfants</span>
                                </div>
                            </div>
                        </div>

                        <!-- DROITE formulaire -->
                        <div class="col-lg-8">
                            <div class="auth-right">
                                <h3 class="auth-title">🌿 Créer un compte</h3>
                                <p class="auth-subtitle">Rejoignez la communauté HairRoots dès aujourd'hui !</p>

                                <?php if($error): ?>
                                    <div class="alert-hairroots error">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if($success): ?>
                                    <div class="alert-hairroots success">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">

                                    <!-- Prénom & Nom -->
                                    <div class="row g-3 mb-0">
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Prénom</label>
                                                <input type="text" class="auth-input-no-icon" name="first_name"
                                                       value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>"
                                                       placeholder="Votre prénom" required>
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

                                    <!-- Username -->
                                    <div class="auth-input-group">
                                        <label>Nom d'utilisateur</label>
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" class="auth-input" name="username"
                                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                               placeholder="Choisissez un pseudo unique" required>
                                    </div>

                                    <!-- Adresse -->
                                    <div class="auth-input-group">
                                        <label>Adresse</label>
                                        <i class="bi bi-geo-alt input-icon"></i>
                                        <input type="text" class="auth-input" name="address"
                                               value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>"
                                               placeholder="Votre adresse complète" required>
                                    </div>

                                    <!-- Ville & Code postal -->
                                    <div class="row g-3 mb-0">
                                        <div class="col-md-8">
                                            <div class="auth-input-group">
                                                <label>Ville</label>
                                                <input type="text" class="auth-input-no-icon" name="city"
                                                       value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>"
                                                       placeholder="Votre ville" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="auth-input-group">
                                                <label>Code postal</label>
                                                <input type="text" class="auth-input-no-icon" name="postal_code"
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

                                    <!-- Mots de passe -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Mot de passe</label>
                                                <i class="bi bi-lock input-icon"></i>
                                                <input type="password" class="auth-input" name="password"
                                                       id="password"
                                                       placeholder="Min. 8 caractères" required>
                                                <!-- Indicateur visuel -->
                                                <div class="password-strength">
                                                    <div class="strength-bar">
                                                        <div class="strength-fill" id="strengthFill"></div>
                                                    </div>
                                                    <span class="strength-text" id="strengthText"></span>
                                                    <ul class="criteria-list" id="criteriaList">
                                                        <li id="c-length">Au moins 8 caractères</li>
                                                        <li id="c-upper">Une lettre majuscule</li>
                                                        <li id="c-number">Un chiffre</li>
                                                        <li id="c-special">Un caractère spécial (!@#$%...)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="auth-input-group">
                                                <label>Confirmer le mot de passe</label>
                                                <i class="bi bi-lock-fill input-icon"></i>
                                                <input type="password" class="auth-input" name="confirm_password"
                                                       id="confirm_password"
                                                       placeholder="Répétez le mot de passe" required>
                                                <p class="password-hint" id="matchHint"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CGU -->
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="cgu" required
                                               style="border-color:#C9A84C;">
                                        <label class="form-check-label small" for="cgu" style="color:#6B3A2A;">
                                            J'accepte les <a href="#" class="auth-link">conditions générales</a> et la <a href="#" class="auth-link">politique de confidentialité</a>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn-auth">
                                        Créer mon compte HairRoots
                                    </button>
                                </form>

                                <p class="text-center mt-4 mb-0" style="font-size:0.92rem;color:#6B3A2A;">
                                    Déjà un compte ?
                                    <a href="login.php" class="auth-link">Se connecter →</a>
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
const pwd = document.getElementById('password');
const confirmPwd = document.getElementById('confirm_password');
const fill = document.getElementById('strengthFill');
const text = document.getElementById('strengthText');
const matchHint = document.getElementById('matchHint');

pwd.addEventListener('input', function() {
    const val = this.value;
    let score = 0;

    const cLength  = val.length >= 8;
    const cUpper   = /[A-Z]/.test(val);
    const cNumber  = /[0-9]/.test(val);
    const cSpecial = /[\W_]/.test(val);

    document.getElementById('c-length').classList.toggle('valid', cLength);
    document.getElementById('c-upper').classList.toggle('valid', cUpper);
    document.getElementById('c-number').classList.toggle('valid', cNumber);
    document.getElementById('c-special').classList.toggle('valid', cSpecial);

    if (cLength)  score++;
    if (cUpper)   score++;
    if (cNumber)  score++;
    if (cSpecial) score++;

    const levels = [
        { w: '0%',   color: '#F5E6D3', label: '' },
        { w: '25%',  color: '#c62828', label: 'Très faible' },
        { w: '50%',  color: '#e65100', label: 'Faible' },
        { w: '75%',  color: '#C9A84C', label: 'Moyen' },
        { w: '100%', color: '#2e7d32', label: 'Fort' },
    ];

    fill.style.width = levels[score].w;
    fill.style.background = levels[score].color;
    text.textContent = levels[score].label;
    text.style.color = levels[score].color;
});

confirmPwd.addEventListener('input', function() {
    if (this.value === pwd.value) {
        matchHint.textContent = ' Les mots de passe correspondent';
        matchHint.style.color = '#2e7d32';
    } else {
        matchHint.textContent = ' Les mots de passe ne correspondent pas';
        matchHint.style.color = '#c62828';
    }
});
</script>

<?php include '../includes/footer.php'; ?>