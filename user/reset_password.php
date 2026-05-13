<?php
require_once '../config/database.php';
$page_title = 'Nouveau mot de passe - HairRoots';

$error   = '';
$success = '';
$token   = trim($_GET['token'] ?? '');
$user    = null;

// Verifier que le token est valide et non expire
if (empty($token)) {
    header('Location: forgot_password.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT pr.*, u.email, u.first_name, u.last_name
    FROM password_resets pr
    JOIN users u ON u.id = pr.user_id
    WHERE pr.token = ? AND pr.expires_at > NOW()
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Ce lien est invalide ou a expire. Veuillez faire une nouvelle demande.";
}

// TRAITEMENT DU NOUVEAU MOT DE PASSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une lettre majuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un caractere special.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Mettre a jour le mot de passe
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $reset['user_id']]);

        // Supprimer le token utilise
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        $success = true;
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
    padding: 50px;
    max-width: 480px;
    width: 100%;
    margin: 0 auto;
}
.auth-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.9rem;
    font-weight: 700;
    color: #3E1F0D;
    margin-bottom: 8px;
}
.auth-subtitle { color: #9a7c5c; font-size: 0.92rem; margin-bottom: 28px; }
.auth-input-group { margin-bottom: 18px; position: relative; }
.auth-input-group label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #3E1F0D;
    margin-bottom: 6px;
    display: block;
}
.auth-input {
    width: 100%;
    border: 2px solid #F5E6D3;
    border-radius: 12px;
    padding: 12px 44px 12px 15px;
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
.btn-toggle-pwd {
    position: absolute;
    right: 12px;
    bottom: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: #9a7c5c;
    font-size: 1.05rem;
}
.btn-toggle-pwd:hover { color: #C9A84C; }
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
}
.alert-box {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 0.88rem;
    margin-bottom: 20px;
}
.alert-box.error   { background: #fce4e4; color: #c62828; border-left: 4px solid #c62828; }
.alert-box.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
.auth-link { color: #C1622F; font-weight: 700; text-decoration: none; }
.strength-bar { height: 4px; border-radius: 4px; background: #F5E6D3; margin: 8px 0 4px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 4px; width: 0%; transition: all 0.4s; }
.strength-text { font-size: 0.75rem; font-weight: 600; }
.criteria-list { list-style: none; padding: 0; margin: 8px 0 0; }
.criteria-list li { font-size: 0.75rem; color: #c62828; margin-bottom: 2px; transition: color 0.3s; }
.criteria-list li.valid { color: #2e7d32; }
.criteria-list li::before { content: 'x '; }
.criteria-list li.valid::before { content: 'v '; }
.password-hint { font-size: 0.78rem; color: #9a7c5c; margin-top: 4px; }
</style>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">

            <?php if ($success): ?>
            <!-- SUCCES -->
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:4rem;margin-bottom:16px;">
                    <i class="bi bi-check-circle" style="color:#2e7d32;"></i>
                </div>
                <h3 class="auth-title" style="text-align:center;">Mot de passe modifie !</h3>
                <p style="color:#6B3A2A;margin:12px 0 28px;line-height:1.6;">
                    Votre mot de passe a bien ete reinitialise. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
                </p>
                <a href="login.php"
                   style="display:inline-block;background:linear-gradient(135deg,#C9A84C,#b8942e);
                          color:#3E1F0D;padding:14px 40px;border-radius:12px;font-weight:700;
                          text-decoration:none;font-size:1rem;">
                    Se connecter
                </a>
            </div>

            <?php elseif ($error && !$reset): ?>
            <!-- TOKEN INVALIDE -->
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:4rem;margin-bottom:16px;">
                    <i class="bi bi-x-circle" style="color:#c62828;"></i>
                </div>
                <h3 class="auth-title" style="text-align:center;">Lien invalide</h3>
                <p style="color:#6B3A2A;margin:12px 0 28px;">
                    <?= htmlspecialchars($error) ?>
                </p>
                <a href="forgot_password.php"
                   style="display:inline-block;background:linear-gradient(135deg,#C9A84C,#b8942e);
                          color:#3E1F0D;padding:14px 40px;border-radius:12px;font-weight:700;
                          text-decoration:none;">
                    Nouvelle demande
                </a>
            </div>

            <?php else: ?>
            <!-- FORMULAIRE -->
            <h3 class="auth-title">Nouveau mot de passe</h3>
            <p class="auth-subtitle">
                Bonjour <?= htmlspecialchars($reset['first_name']) ?>, choisissez un nouveau mot de passe pour votre compte.
            </p>

            <?php if ($error): ?>
                <div class="alert-box error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <!-- Nouveau mot de passe -->
                <div class="auth-input-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" class="auth-input" name="password" id="password"
                           placeholder="Min. 8 caracteres" required>
                    <button type="button" class="btn-toggle-pwd" id="togglePwd">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <span class="strength-text" id="strengthText"></span>
                    <ul class="criteria-list">
                        <li id="c-length">Au moins 8 caracteres</li>
                        <li id="c-upper">Une lettre majuscule</li>
                        <li id="c-number">Un chiffre</li>
                        <li id="c-special">Un caractere special</li>
                    </ul>
                </div>

                <!-- Confirmation -->
                <div class="auth-input-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" class="auth-input" name="confirm_password" id="confirm_password"
                           placeholder="Repetez le mot de passe" required>
                    <button type="button" class="btn-toggle-pwd" id="toggleConfirmPwd">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                    <p class="password-hint" id="matchHint"></p>
                </div>

                <button type="submit" class="btn-auth">Enregistrer le nouveau mot de passe</button>
            </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Afficher / masquer mot de passe
function setupToggle(btnId, inputId, iconId) {
    document.getElementById(btnId).addEventListener('click', function () {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
}
setupToggle('togglePwd',        'password',         'eyeIcon1');
setupToggle('toggleConfirmPwd', 'confirm_password', 'eyeIcon2');

// Indicateur de force
const pwd        = document.getElementById('password');
const confirmPwd = document.getElementById('confirm_password');
const fill       = document.getElementById('strengthFill');
const text       = document.getElementById('strengthText');
const matchHint  = document.getElementById('matchHint');

if (pwd) {
    pwd.addEventListener('input', function () {
        const val      = this.value;
        const cLength  = val.length >= 8;
        const cUpper   = /[A-Z]/.test(val);
        const cNumber  = /[0-9]/.test(val);
        const cSpecial = /[\W_]/.test(val);

        document.getElementById('c-length').classList.toggle('valid', cLength);
        document.getElementById('c-upper').classList.toggle('valid', cUpper);
        document.getElementById('c-number').classList.toggle('valid', cNumber);
        document.getElementById('c-special').classList.toggle('valid', cSpecial);

        const score  = [cLength, cUpper, cNumber, cSpecial].filter(Boolean).length;
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
}

if (confirmPwd) {
    confirmPwd.addEventListener('input', function () {
        if (this.value === pwd.value) {
            matchHint.textContent = 'Les mots de passe correspondent';
            matchHint.style.color = '#2e7d32';
        } else {
            matchHint.textContent = 'Les mots de passe ne correspondent pas';
            matchHint.style.color = '#c62828';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>