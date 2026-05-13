<?php
require_once '../config/database.php';
$page_title = 'Mot de passe oublie - HairRoots';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } else {
        // Verifier si l'email existe dans la BDD
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generer un token unique
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Supprimer les anciens tokens de cet utilisateur
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

            // Enregistrer le nouveau token
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expiry]);

            // Envoyer l'email
            require_once $_SERVER['DOCUMENT_ROOT'] . '/ecommerce/includes/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendPasswordReset($email, $user['first_name'], $token);
        }

        // Message generique pour ne pas reveler si l'email existe ou non
        $success = "Si cette adresse email est associee a un compte, vous recevrez un lien de reinitialisation dans quelques minutes. Pensez a verifier vos spams.";
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
.auth-subtitle {
    color: #9a7c5c;
    font-size: 0.92rem;
    margin-bottom: 28px;
    line-height: 1.6;
}
.auth-input-group { margin-bottom: 18px; }
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
    padding: 12px 15px;
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
.auth-link:hover { color: #3E1F0D; }
</style>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">

            <h3 class="auth-title">Mot de passe oublie</h3>
            <p class="auth-subtitle">
                Entrez votre adresse email et nous vous enverrons un lien pour reinitialiser votre mot de passe.
            </p>

            <?php if ($error): ?>
                <div class="alert-box error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-box success"><?= htmlspecialchars($success) ?></div>
                <p class="text-center mt-3">
                    <a href="login.php" class="auth-link">Retour a la connexion</a>
                </p>
            <?php else: ?>

            <form method="POST" action="">
                <div class="auth-input-group">
                    <label>Adresse email</label>
                    <input type="email" class="auth-input" name="email"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           placeholder="votre@email.com" required>
                </div>

                <button type="submit" class="btn-auth">
                    Envoyer le lien de reinitialisation
                </button>
            </form>

            <p class="text-center mt-4 mb-0" style="font-size:0.92rem;color:#6B3A2A;">
                Vous vous souvenez de votre mot de passe ?
                <a href="login.php" class="auth-link">Se connecter</a>
            </p>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>