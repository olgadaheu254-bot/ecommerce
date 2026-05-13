<?php
require_once '../config/database.php';
$page_title = 'Finaliser ma commande - HairRoots';

if(!isset($_SESSION['user_id'])) {
    header('Location: /ecommerce/user/login.php?redirect=/ecommerce/cart/checkout.php');
    exit;
}
if(!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: /ecommerce/cart/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]); $user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY est_principale DESC, created_at DESC");
$stmt->execute([$user_id]); $addresses = $stmt->fetchAll();

$cart_items = array(); $total = 0;
$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids)-1) . '?';
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids); $products = $stmt->fetchAll();
foreach($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $subtotal = $product['price'] * $quantity;
    $total += $subtotal;
    $cart_items[] = array('product'=>$product,'quantity'=>$quantity,'subtotal'=>$subtotal);
}

$livraison = $total >= 50 ? 0 : 4.99;
$total_final = $total + $livraison;

// TRAITEMENT COMMANDE
                    if($_SERVER['REQUEST_METHOD'] === 'POST') {
                $payment_method   = $_POST['payment_method'] ?? 'Carte bancaire';
                $notes            = trim($_POST['notes'] ?? '');
                $shipping_address = '';
                $city             = '';
                $postal_code      = '';
                $phone            = '';

                // Si adresse sauvegardee selectionnee
                $saved_address_id = isset($_POST['saved_address']) ? (int)$_POST['saved_address'] : 0;

                if($saved_address_id > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
                    $stmt->execute([$saved_address_id, $user_id]);
                    $saved_addr = $stmt->fetch();
                    if($saved_addr) {
                        $shipping_address = $saved_addr['adresse'];
                        $city             = $saved_addr['ville'];
                        $postal_code      = $saved_addr['code_postal'];
                        $phone            = $saved_addr['telephone'] ?? '';
                    }
                } else {
                    $shipping_address = trim($_POST['shipping_address'] ?? '');
                    $city             = trim($_POST['city'] ?? '');
                    $postal_code      = trim($_POST['postal_code'] ?? '');
                    $phone            = trim($_POST['phone'] ?? '');
                }

    // Validation adresse
    if(empty($shipping_address) || empty($city) || empty($postal_code)) {
        $error = "Veuillez remplir tous les champs d'adresse obligatoires.";
    } else {

        // Validation paiement selon le mode choisi
        $pay_error = '';

        if($payment_method === 'Carte bancaire') {
            $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
            $card_expiry = trim($_POST['card_expiry'] ?? '');
            $card_cvv    = trim($_POST['card_cvv'] ?? '');
            $card_name   = trim($_POST['card_name'] ?? '');

            if(empty($card_number) || strlen($card_number) < 16) {
                $pay_error = "Veuillez entrer un numero de carte valide (16 chiffres).";
            } elseif(!preg_match('/^\d{16}$/', $card_number)) {
                $pay_error = "Le numero de carte doit contenir uniquement des chiffres.";
            } elseif(empty($card_expiry) || !preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
                $pay_error = "Veuillez entrer une date d'expiration valide (MM/AA).";
            } elseif(empty($card_cvv) || !preg_match('/^\d{3,4}$/', $card_cvv)) {
                $pay_error = "Veuillez entrer un CVV valide (3 ou 4 chiffres).";
            } elseif(empty($card_name)) {
                $pay_error = "Veuillez entrer le nom du titulaire de la carte.";
            }

        } elseif($payment_method === 'PayPal') {
            $paypal_email = trim($_POST['paypal_email'] ?? '');
            if(empty($paypal_email) || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
                $pay_error = "Veuillez entrer une adresse email PayPal valide.";
            }

        } elseif($payment_method === 'Virement bancaire') {
            $iban = preg_replace('/\s+/', '', strtoupper($_POST['iban'] ?? ''));
            if(empty($iban) || strlen($iban) < 14) {
                $pay_error = "Veuillez entrer un IBAN valide.";
            } elseif(!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
                $pay_error = "Format IBAN invalide (ex: FR76 3000 6000 0112 3456 7890 189).";
            }
        }
        // Paiement a la livraison : aucune info supplementaire requise

        if(!empty($pay_error)) {
            $error = $pay_error;
        } else {
           try {
                    $pdo->beginTransaction();
                    $order_number = 'HR-' . date('Ymd') . '-' . rand(1000,9999);
                    $full_address = $shipping_address."\n".$postal_code." ".$city."\nTel: ".$phone;
                    $stmt = $pdo->prepare("INSERT INTO orders (user_id,order_number,total_amount,status,payment_method,payment_status,shipping_address,notes) VALUES (?,?,?,'pending',?,'pending',?,?)");
                    $stmt->execute([$user_id,$order_number,$total_final,$payment_method,$full_address,$notes]);
                    $order_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price,subtotal) VALUES (?,?,?,?,?)");
                    foreach($cart_items as $item) {
                        $p=$item['product'];
                        $stmt->execute([$order_id,$p['id'],$item['quantity'],$p['price'],$item['subtotal']]);
                        $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$item['quantity'],$p['id']]);
                    }

                    $pdo->commit();
                    $_SESSION['cart'] = array();
                    $success = $order_number;

                    // Envoi email confirmation de commande
                    require_once $_SERVER['DOCUMENT_ROOT'] . '/ecommerce/includes/Mailer.php';
                    $mailer = new Mailer();

                    $items_email = [];
                    foreach($cart_items as $item) {
                        $items_email[] = [
                            'name'  => $item['product']['name'],
                            'qty'   => $item['quantity'],
                            'price' => $item['product']['price'],
                        ];
                    }

                    $mailer->sendOrderConfirmation(
                        $user['email'],
                        $user['first_name'],
                        (int)$order_id,
                        $items_email,
                        (float)$total_final,
                        $full_address
                    );

                } catch(Exception $e) {
                    $pdo->rollBack();
                    $error = "Erreur lors de la creation de la commande. Veuillez reessayer.";
                }
        }
    }
}

include '../includes/header.php';
?>
<style>
.checkout-page{background:#FDF8F2;min-height:80vh;padding:40px 0}
.co-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;margin-bottom:20px}
.co-card-header{background:linear-gradient(135deg,#F5E6D3,#FDEBD0);padding:18px 25px;border-bottom:1px solid #F0D9C0;display:flex;align-items:center;gap:10px}
.co-card-header h5{font-family:'Playfair Display',serif;color:#3E1F0D;font-weight:700;margin:0;font-size:1.1rem}
.co-card-body{padding:25px}
.co-input{border:2px solid #F5E6D3;border-radius:10px;padding:11px 15px;font-size:0.9rem;transition:all 0.3s;background:#FDFAF7;width:100%}
.co-input:focus{border-color:#C9A84C;box-shadow:0 0 0 3px rgba(201,168,76,0.12);outline:none;background:#fff}
.co-input:disabled,.co-input[readonly]{background:#F5E6D3;color:#9a7c5c}
.co-label{font-weight:600;font-size:0.82rem;color:#3E1F0D;margin-bottom:5px;display:block}

/* ADRESSES */
.addr-select{border:2px solid #F5E6D3;border-radius:12px;padding:14px;cursor:pointer;transition:all 0.3s;margin-bottom:10px;background:#FDFAF7}
.addr-select:hover{border-color:#C9A84C;background:#FFFDF5}
.addr-select.selected{border-color:#C9A84C;background:linear-gradient(135deg,#FFFDF5,#FFF8E8)}
.addr-select input[type=radio]{accent-color:#C9A84C}

/* PAIEMENT */
.pay-option{border:2px solid #F5E6D3;border-radius:12px;padding:15px 20px;cursor:pointer;transition:all 0.3s;margin-bottom:10px;display:flex;align-items:center;gap:15px;background:#FDFAF7}
.pay-option:hover{border-color:#C9A84C;background:#FFFDF5}
.pay-option.selected{border-color:#C9A84C;background:linear-gradient(135deg,#FFFDF5,#FFF8E8)}
.pay-option input[type=radio]{accent-color:#C9A84C;width:18px;height:18px}
.pay-icon{font-size:1.8rem;width:45px;text-align:center}
.pay-label{font-weight:600;color:#3E1F0D;font-size:0.92rem}
.pay-desc{font-size:0.78rem;color:#9a7c5c}

/* FORMULAIRES DE PAIEMENT */
.pay-form{display:none;background:#FDFAF7;border-radius:14px;padding:20px;margin-top:15px;border:1px solid #F5E6D3}
.pay-form.active{display:block}
.card-row{display:flex;gap:10px}
.card-preview{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:14px;padding:20px;margin-bottom:15px;color:#fff;position:relative;overflow:hidden}
.card-preview::before{content:'';position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:rgba(201,168,76,0.15);border-radius:50%}
.card-chip{width:35px;height:28px;background:linear-gradient(135deg,#C9A84C,#b8942e);border-radius:5px;margin-bottom:15px}
.card-number-preview{font-size:1.1rem;letter-spacing:3px;font-weight:600;color:#C9A84C;margin-bottom:10px;font-family:monospace}
.card-info-row{display:flex;justify-content:space-between;font-size:0.75rem;color:rgba(255,255,255,0.7)}
.iban-info{background:#E3F2FD;border-radius:10px;padding:12px 15px;font-size:0.82rem;color:#1565C0;margin-bottom:15px;border:1px solid #BBDEFB}
.paypal-info{background:#fff8e1;border-radius:10px;padding:12px 15px;font-size:0.82rem;color:#F57F17;margin-bottom:15px;border:1px solid #FFE082}

/* SUMMARY */
.summary-card{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3;overflow:hidden;position:sticky;top:80px}
.summary-dark-header{background:linear-gradient(135deg,#3E1F0D,#6B3A2A);padding:18px 25px}
.summary-dark-header h5{font-family:'Playfair Display',serif;color:#C9A84C;font-weight:700;margin:0}
.summary-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #F5E6D3}
.summary-item:last-child{border-bottom:none}
.summary-item-img{width:55px;height:55px;border-radius:10px;object-fit:cover;border:2px solid #F5E6D3;flex-shrink:0}
.summary-item-img-ph{width:55px;height:55px;border-radius:10px;background:#F5E6D3;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.summary-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:0.9rem;color:#6B3A2A}
.summary-total-row{display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:2px solid #F5E6D3;margin-top:5px}
.btn-validate{background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;border:none;border-radius:12px;padding:16px;font-size:1.05rem;font-weight:800;width:100%;transition:all 0.3s;cursor:pointer;letter-spacing:0.3px}
.btn-validate:hover{background:linear-gradient(135deg,#C1622F,#a0491f);color:#fff;transform:translateY(-2px);box-shadow:0 10px 25px rgba(193,98,47,0.3)}

/* ETAPES */
.steps{display:flex;align-items:center;margin-bottom:35px}
.step{display:flex;align-items:center;gap:8px;font-size:0.85rem;font-weight:600}
.step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700}
.step.done .step-num{background:#C9A84C;color:#3E1F0D}
.step.active .step-num{background:#C1622F;color:#fff}
.step.pending .step-num{background:#F5E6D3;color:#9a7c5c}
.step.done span,.step.active span{color:#3E1F0D}
.step.pending span{color:#9a7c5c}
.step-line{flex:1;height:2px;background:#F5E6D3;margin:0 10px}
.step-line.done{background:#C9A84C}

/* SUCCES */
.success-box{text-align:center;padding:50px 30px;background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(62,31,13,0.06);border:1px solid #F5E6D3}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}
</style>

<div class="checkout-page"><div class="container">

<?php if($success): ?>
<div class="success-box">
    <div style="font-size:5rem;margin-bottom:20px">✅</div>
    <h2 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-size:2rem">Commande confirmee !</h2>
    <p style="color:#6B3A2A;font-size:1rem;margin:10px 0">Merci pour votre commande. Vous allez recevoir un email de confirmation.</p>
    <div style="background:linear-gradient(135deg,#F5E6D3,#FDEBD0);border-radius:14px;padding:20px;display:inline-block;margin:20px 0">
        <p style="color:#9a7c5c;font-size:0.85rem;margin:0 0 5px">Numero de commande</p>
        <p style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;color:#C1622F;margin:0"><?= htmlspecialchars($success) ?></p>
    </div>
    <p style="color:#9a7c5c;font-size:0.85rem">Redirection vers votre profil dans 3 secondes...</p>
    <div class="d-flex gap-3 justify-content-center mt-3 flex-wrap">
        <a href="/ecommerce/user/profile.php" style="background:linear-gradient(135deg,#C9A84C,#b8942e);color:#3E1F0D;padding:12px 30px;border-radius:12px;font-weight:700;text-decoration:none">Voir mes commandes</a>
        <a href="/ecommerce/products/index.php" style="background:#F5E6D3;color:#3E1F0D;padding:12px 30px;border-radius:12px;font-weight:700;text-decoration:none">Continuer mes achats</a>
    </div>
</div>
<?php header("refresh:3;url=/ecommerce/user/profile.php"); else: ?>

<!-- ETAPES -->
<div class="steps mb-4">
    <div class="step done"><div class="step-num">✓</div><span>Panier</span></div>
    <div class="step-line done"></div>
    <div class="step active"><div class="step-num">2</div><span>Livraison & Paiement</span></div>
    <div class="step-line"></div>
    <div class="step pending"><div class="step-num">3</div><span>Confirmation</span></div>
</div>

<h1 style="font-family:'Playfair Display',serif;color:#3E1F0D;font-size:2rem;font-weight:900;margin-bottom:25px">
    Finaliser ma commande
</h1>

<?php if($error): ?><div class="alert-hr error mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-8">

<form method="POST" action="" id="checkout-form">

    <!-- ADRESSES SAUVEGARDEES -->
    <?php if(count($addresses) > 0): ?>
    <div class="co-card">
        <div class="co-card-header">
            <i class="bi bi-geo-alt" style="color:#C9A84C;font-size:1.2rem"></i>
            <h5>Choisir une adresse enregistree</h5>
        </div>
        <div class="co-card-body">
            <?php foreach($addresses as $a): ?>
            <label class="addr-select d-block <?= $a['est_principale']?'selected':'' ?>" onclick="selectAddress(this, '<?= htmlspecialchars(addslashes($a['adresse'])) ?>', '<?= htmlspecialchars(addslashes($a['ville'])) ?>', '<?= htmlspecialchars(addslashes($a['code_postal'])) ?>', '<?= htmlspecialchars(addslashes($a['telephone']??'')) ?>')">
                <div class="d-flex align-items-start gap-3">
                    <input type="radio" name="saved_address" value="<?= $a['id'] ?>" <?= $a['est_principale']?'checked':'' ?> style="margin-top:3px">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <strong style="color:#3E1F0D"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong>
                            <?php if($a['est_principale']): ?><span style="background:#C9A84C;color:#3E1F0D;padding:2px 10px;border-radius:10px;font-size:0.72rem;font-weight:700">Principale</span><?php endif; ?>
                        </div>
                        <div style="color:#6B3A2A;font-size:0.85rem;margin-top:3px">
                            <?= htmlspecialchars($a['adresse']) ?><br>
                            <?= htmlspecialchars($a['code_postal'].' '.$a['ville']) ?>
                            <?php if(!empty($a['telephone'])): ?> - <?= htmlspecialchars($a['telephone']) ?><?php endif; ?>
                        </div>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
            <button type="button" style="background:none;border:none;color:#C1622F;font-weight:600;font-size:0.85rem;padding:5px 0;cursor:pointer" onclick="toggleNewAddress()">
                + Utiliser une autre adresse
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- INFOS LIVRAISON -->
    <div class="co-card" id="livraison-form" <?= count($addresses)>0?'style="display:none"':'' ?>>
        <div class="co-card-header">
            <i class="bi bi-truck" style="color:#C9A84C;font-size:1.2rem"></i>
            <h5>Informations de livraison</h5>
        </div>
        <div class="co-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="co-label">Prenom</label>
                    <input type="text" class="co-input" value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="co-label">Nom</label>
                    <input type="text" class="co-input" value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="co-label">Telephone *</label>
                    <input type="tel" class="co-input" name="phone" id="input-phone"
                           value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+33 6 00 00 00 00">
                </div>
                <div class="col-12">
                    <label class="co-label">Adresse de livraison *</label>
                    <input type="text" class="co-input" name="shipping_address" id="input-address"
                           value="<?= htmlspecialchars($user['address']??'') ?>" placeholder="Numero et nom de rue">
                </div>
                <div class="col-md-4">
                    <label class="co-label">Code postal *</label>
                    <input type="text" class="co-input" name="postal_code" id="input-cp"
                           value="<?= htmlspecialchars($user['postal_code']??'') ?>">
                </div>
                <div class="col-md-8">
                    <label class="co-label">Ville *</label>
                    <input type="text" class="co-input" name="city" id="input-city"
                           value="<?= htmlspecialchars($user['city']??'') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- PAIEMENT -->
    <div class="co-card">
        <div class="co-card-header">
            <i class="bi bi-credit-card" style="color:#C9A84C;font-size:1.2rem"></i>
            <h5>Mode de paiement</h5>
        </div>
        <div class="co-card-body">

            <!-- OPTIONS -->
            <label class="pay-option selected" onclick="selectPay(this, 'carte')">
                <input type="radio" name="payment_method" value="Carte bancaire" checked>
                <div class="pay-icon"><i class="bi bi-credit-card-fill"></i></div>
                <div>
                    <div class="pay-label">Carte bancaire</div>
                    <div class="pay-desc">Visa, Mastercard, CB</div>
                </div>
            </label>

            <label class="pay-option" onclick="selectPay(this, 'paypal')">
                <input type="radio" name="payment_method" value="PayPal">
                <div class="pay-icon"><i class="bi bi-paypal"></i></div>
                <div>
                    <div class="pay-label">PayPal</div>
                    <div class="pay-desc">Paiement securise via PayPal</div>
                </div>
            </label>

            <label class="pay-option" onclick="selectPay(this, 'virement')">
                <input type="radio" name="payment_method" value="Virement bancaire">
                <div class="pay-icon"><i class="bi bi-bank"></i></div>
                <div>
                    <div class="pay-label">Virement bancaire</div>
                    <div class="pay-desc">Payez par virement IBAN</div>
                </div>
            </label>

            <label class="pay-option" onclick="selectPay(this, 'livraison')">
                <input type="radio" name="payment_method" value="Paiement a la livraison">
                <div class="pay-icon"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="pay-label">Paiement a la livraison</div>
                    <div class="pay-desc">Payez en especes a la reception</div>
                </div>
            </label>

            <!-- FORMULAIRE CARTE BANCAIRE -->
            <div class="pay-form active" id="form-carte">
                <!-- Apercu carte -->
                <div class="card-preview">
                    <div class="card-chip"></div>
                    <div class="card-number-preview" id="preview-number">**** **** **** ****</div>
                    <div class="card-info-row">
                        <div>
                            <div style="font-size:0.65rem;margin-bottom:2px">TITULAIRE</div>
                            <div id="preview-name" style="font-size:0.82rem;color:#fff;font-weight:600">VOTRE NOM</div>
                        </div>
                        <div>
                            <div style="font-size:0.65rem;margin-bottom:2px">EXPIRE</div>
                            <div id="preview-expiry" style="font-size:0.82rem;color:#fff;font-weight:600">MM/AA</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="co-label">Numero de carte *</label>
                        <input type="text" class="co-input" name="card_number" id="card-number"
                               maxlength="19" placeholder="1234 5678 9012 3456"
                               oninput="formatCard(this)" autocomplete="cc-number">
                    </div>
                    <div class="col-12">
                        <label class="co-label">Nom du titulaire *</label>
                        <input type="text" class="co-input" name="card_name" id="card-name"
                               placeholder="JEAN DUPONT" style="text-transform:uppercase"
                               oninput="document.getElementById('preview-name').textContent = this.value.toUpperCase() || 'VOTRE NOM'"
                               autocomplete="cc-name">
                    </div>
                    <div class="col-md-6">
                        <label class="co-label">Date d'expiration *</label>
                        <input type="text" class="co-input" name="card_expiry" id="card-expiry"
                               maxlength="5" placeholder="MM/AA"
                               oninput="formatExpiry(this)" autocomplete="cc-exp">
                    </div>
                    <div class="col-md-6">
                        <label class="co-label">CVV *</label>
                        <input type="text" class="co-input" name="card_cvv"
                               maxlength="4" placeholder="123" autocomplete="cc-csc"
                               style="letter-spacing:4px">
                        <small style="color:#9a7c5c;font-size:0.75rem">3 ou 4 chiffres au dos de votre carte</small>
                    </div>
                </div>
            </div>

            <!-- FORMULAIRE PAYPAL -->
            <div class="pay-form" id="form-paypal">
                <div class="paypal-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Vous serez redirige vers PayPal pour finaliser le paiement.
                </div>
                <div>
                    <label class="co-label">Adresse email PayPal *</label>
                    <input type="email" class="co-input" name="paypal_email"
                           placeholder="votre@email.com" autocomplete="email">
                </div>
            </div>

            <!-- FORMULAIRE VIREMENT -->
            <div class="pay-form" id="form-virement">
                <div class="iban-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Votre commande sera traitee apres reception du virement. Delai : 2-3 jours ouvrables.
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="co-label">Votre IBAN *</label>
                        <input type="text" class="co-input" name="iban"
                               placeholder="FR76 3000 6000 0112 3456 7890 189"
                               oninput="formatIban(this)" maxlength="34" style="letter-spacing:1px">
                        <small style="color:#9a7c5c;font-size:0.75rem">Format : FR76 XXXX XXXX XXXX XXXX XXXX XXX</small>
                    </div>
                    <div class="col-12">
                        <label class="co-label">Nom du titulaire du compte *</label>
                        <input type="text" class="co-input" name="account_name"
                               placeholder="Jean Dupont" style="text-transform:uppercase">
                    </div>
                </div>
                <div style="background:#F5E6D3;border-radius:10px;padding:14px;margin-top:15px">
                    <p style="font-weight:700;color:#3E1F0D;font-size:0.85rem;margin-bottom:8px">Coordonnees bancaires HairRoots :</p>
                    <p style="font-size:0.82rem;color:#6B3A2A;margin:3px 0"><strong>IBAN :</strong> FR76 3000 6000 0112 3456 7890 189</p>
                    <p style="font-size:0.82rem;color:#6B3A2A;margin:3px 0"><strong>BIC :</strong> AGRIFRPP882</p>
                    <p style="font-size:0.82rem;color:#6B3A2A;margin:3px 0"><strong>Reference :</strong> Votre numero de commande</p>
                </div>
            </div>

            <!-- PAIEMENT A LA LIVRAISON -->
            <div class="pay-form" id="form-livraison">
                <div style="background:#E8F5E9;border-radius:10px;padding:14px;border:1px solid #C8E6C9">
                    <p style="color:#2E7D32;font-weight:600;font-size:0.88rem;margin:0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Vous payerez en especes lors de la reception de votre commande. Aucune information bancaire requise.
                    </p>
                </div>
            </div>

        </div>
    </div>

    <!-- NOTES -->
    <div class="co-card">
        <div class="co-card-header">
            <i class="bi bi-chat-left-text" style="color:#C9A84C;font-size:1.2rem"></i>
            <h5>Notes / Instructions (optionnel)</h5>
        </div>
        <div class="co-card-body">
            <textarea class="co-input" name="notes" rows="3" placeholder="Instructions speciales pour la livraison..."></textarea>
        </div>
    </div>

    <button type="submit" class="btn-validate d-lg-none mb-4">
        Valider ma commande →
    </button>

</form>
</div>

<!-- RESUME -->
<div class="col-lg-4">
    <div class="summary-card">
        <div class="summary-dark-header">
            <h5>Recapitulatif</h5>
        </div>
        <div class="co-card-body">
            <?php foreach($cart_items as $item): $p=$item['product']; ?>
            <div class="summary-item">
                <?php if(!empty($p['image'])): ?>
                   <img src="/ecommerce/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="summary-item-img">
                <?php else: ?>
                    <div class="summary-item-img-ph"></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <div style="font-weight:600;color:#3E1F0D;font-size:0.88rem"><?= htmlspecialchars($p['name']) ?></div>
                    <div style="color:#9a7c5c;font-size:0.78rem">Qte: <?= $item['quantity'] ?> x <?= number_format($p['price'],2) ?>€</div>
                </div>
                <div style="font-weight:800;color:#C1622F;font-size:0.92rem;white-space:nowrap"><?= number_format($item['subtotal'],2) ?>€</div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:15px">
                <div class="summary-row"><span>Sous-total</span><strong><?= number_format($total,2) ?>€</strong></div>
                <div class="summary-row">
                    <span>Livraison</span>
                    <?php if($livraison==0): ?><strong style="color:#2e7d32">Gratuite</strong>
                    <?php else: ?><strong><?= number_format($livraison,2) ?>€</strong><?php endif; ?>
                </div>
                <div class="summary-total-row">
                    <span style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:#3E1F0D">Total</span>
                    <span style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:900;color:#C1622F"><?= number_format($total_final,2) ?>€</span>
                </div>
            </div>

            <button type="submit" form="checkout-form" class="btn-validate mt-4 d-none d-lg-block">
                Valider ma commande →
            </button>

            <div class="text-center mt-3">
                <p style="font-size:0.75rem;color:#9a7c5c;margin-bottom:8px">Paiement securise</p>
                <div class="d-flex justify-content-center gap-2">
                    <span style="background:#F5E6D3;color:#3E1F0D;padding:3px 10px;border-radius:6px;font-size:0.72rem;font-weight:600">SSL</span>
                    <span style="background:#F5E6D3;color:#3E1F0D;padding:3px 10px;border-radius:6px;font-size:0.72rem;font-weight:600">3D Secure</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

</div></div>

<script>
// Selectionner mode paiement et afficher le bon formulaire
function selectPay(el, type) {
    document.querySelectorAll('.pay-option').forEach(p => p.classList.remove('selected'));
    document.querySelectorAll('.pay-form').forEach(f => f.classList.remove('active'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
    const form = document.getElementById('form-' + type);
    if(form) form.classList.add('active');
}

// Formater numero de carte
function formatCard(input) {
    let val = input.value.replace(/\D/g, '').substring(0, 16);
    let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
    input.value = formatted;
    // Apercu
    let preview = val.padEnd(16, '*');
    document.getElementById('preview-number').textContent =
        preview.match(/.{1,4}/g)?.join(' ') || '**** **** **** ****';
}

// Formater expiration
function formatExpiry(input) {
    let val = input.value.replace(/\D/g, '').substring(0, 4);
    if(val.length >= 2) val = val.substring(0,2) + '/' + val.substring(2);
    input.value = val;
    document.getElementById('preview-expiry').textContent = val || 'MM/AA';
}

// Formater IBAN
function formatIban(input) {
    let val = input.value.replace(/\s/g, '').toUpperCase().substring(0, 34);
    input.value = val.match(/.{1,4}/g)?.join(' ') || val;
}

// Selectionner adresse sauvegardee
function selectAddress(el, adresse, ville, cp, tel) {
    document.querySelectorAll('.addr-select').forEach(a => a.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('input-address').value = adresse;
    document.getElementById('input-city').value = ville;
    document.getElementById('input-cp').value = cp;
    document.getElementById('input-phone').value = tel;
}

// Afficher formulaire nouvelle adresse
function toggleNewAddress() {
    const f = document.getElementById('livraison-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
    document.querySelectorAll('.addr-select input').forEach(r => r.checked = false);
    document.querySelectorAll('.addr-select').forEach(a => a.classList.remove('selected'));
}

// Pre-remplir adresse principale au chargement
window.addEventListener('DOMContentLoaded', function() {
    const principal = document.querySelector('.addr-select.selected');
    if(principal) {
        principal.click();
        document.getElementById('livraison-form').style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>