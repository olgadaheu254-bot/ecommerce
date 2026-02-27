<?php
require_once '../config/database.php';
$page_title = 'Connexion - HairRoots';

if(isset($_SESSION['user_id'])) {
    header('Location: /ecommerce/index.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['first_name']= $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/ecommerce/index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = "Identifiants incorrects.";
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
.auth-left p { color: #e8d5b7; font-size: 0.95rem; line-height: 1.7; }
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
.auth-right { padding: 50px 45px; }
.auth-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.9rem;
    font-weight: 700;
    color: #3E1F0D;
    margin-bottom: 5px;
}
.auth-subtitle { color: #9a7c5c; font-size: 0.92rem; margin-bottom: 30px; }
.auth-input-group {
    position: relative;
    margin-bottom: 20px;
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
.auth-divider {
    text-align: center;
    position: relative;
    margin: 25px 0;
    color: #c4a882;
    font-size: 0.85rem;
}
.auth-divider::before, .auth-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 42%;
    height: 1px;
    background: #F5E6D3;
}
.auth-divider::before { left: 0; }
.auth-divider::after  { right: 0; }
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
</style>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="auth-card">
                    <div class="row g-0">

                        <!-- GAUCHE décorative -->
                        <div class="col-lg-5 d-none d-lg-block">
                            <div class="auth-left h-100">
                                <div style="font-size:5rem;margin-bottom:20px;">🌿</div>
                                <h2>Bon retour !</h2>
                                <p>Connectez-vous pour accéder à votre espace personnel HairRoots et profiter de toutes nos offres.</p>
                                <div class="mt-4">
                                    <span class="auth-badge">🛍️ Vos commandes</span>
                                    <span class="auth-badge">💇‍♀️ Vos RDV</span>
                                    <span class="auth-badge">❤️ Vos favoris</span>
                                    <span class="auth-badge">🎁 Vos offres</span>
                                </div>
                            </div>
                        </div>

                        <!-- DROITE formulaire -->
                        <div class="col-lg-7">
                            <div class="auth-right">
                                <h3 class="auth-title">🔐 Connexion</h3>
                                <p class="auth-subtitle">Connectez-vous à votre compte HairRoots</p>

                                <?php if($error): ?>
                                    <div class="alert-hairroots error">
                                        ⚠️ <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(isset($_GET['registered'])): ?>
                                    <div class="alert-hairroots success">
                                        ✅ Inscription réussie ! Vous pouvez maintenant vous connecter.
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="auth-input-group">
                                        <label>Nom d'utilisateur ou Email</label>
                                        <i class="bi bi-person input-icon"></i>
                                        <input type="text" class="auth-input" name="username"
                                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                               placeholder="Votre identifiant ou email"
                                               required autofocus>
                                    </div>

                                    <div class="auth-input-group">
                                        <label>Mot de passe</label>
                                        <i class="bi bi-lock input-icon"></i>
                                        <input type="password" class="auth-input" name="password"
                                               placeholder="Votre mot de passe"
                                               required>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me"
                                                   style="border-color:#C9A84C;">
                                            <label class="form-check-label small" for="remember_me" style="color:#6B3A2A;">
                                                Se souvenir de moi
                                            </label>
                                        </div>
                                        <a href="#" class="auth-link small">Mot de passe oublié ?</a>
                                    </div>

                                    <button type="submit" class="btn-auth">
                                        🔑 Se connecter
                                    </button>
                                </form>

                                <div class="auth-divider">ou</div>

                                <p class="text-center mb-0" style="font-size:0.92rem;color:#6B3A2A;">
                                    Pas encore de compte ?
                                    <a href="register.php" class="auth-link">S'inscrire gratuitement →</a>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>