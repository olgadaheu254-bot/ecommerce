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

// Adresses sauvegardees
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY est_principale DESC, created_at DESC");
$stmt->execute([$user_id]); $addresses = $stmt->fetchAll();

// Articles du panier
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
    $shipping_address = trim($_POST['shipping_address']);
    $city             = trim($_POST['city']);
    $postal_code      = trim($_POST['postal_code']);
    $phone            = trim($_POST['phone']);
    $payment_method   = $_POST['payment_method'];
    $notes            = trim($_POST['notes']);

    if(empty($shipping_address)||empty($city)||empty($postal_code)||empty($phone)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
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
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la creation de la commande. Veuillez reessayer.";
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
.co-section-title{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:#3E1F0D;margin:25px 0 15px;display:flex;align-items:center;gap:8px}
.co-section-title::after{content:'';flex:1;height:1px;background:#F5E6D3}

/* ADRESSES SAUVEGARDEES */
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
.success-icon{font-size:5rem;margin-bottom:20px;animation:bounceIn 0.6s ease}
@keyframes bounceIn{0%{transform:scale(0)}60%{transform:scale(1.1)}100%{transform:scale(1)}}
.alert-hr{border-radius:12px;padding:12px 18px;margin-bottom:20px;font-size:0.9rem;border:none}
.alert-hr.error{background:#fce4e4;color:#c62828;border-left:4px solid #c62828}
</style>

<div class="checkout-page"><div class="container">

<?php if($success): ?>
<!-- CONFIRMATION COMMANDE -->
<div class="success-box">
    <div class="success-icon"></div>
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
<?php 
header("refresh:3;url=/ecommerce/user/profile.php");
else: ?>

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
<form method="POST" action="">

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
                            <?= htmlspecialchars($a['adresse']) ?><?= !empty($a['complement'])?' - '.htmlspecialchars($a['complement']):'' ?><br>
                            <?= htmlspecialchars($a['code_postal'].' '.$a['ville']) ?>
                            <?php if(!empty($a['telephone'])): ?> • <?= htmlspecialchars($a['telephone']) ?><?php endif; ?>
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
                           value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+33 6 00 00 00 00" required>
                </div>
                <div class="col-12">
                    <label class="co-label">Adresse de livraison *</label>
                    <input type="text" class="co-input" name="shipping_address" id="input-address"
                           value="<?= htmlspecialchars($user['address']??'') ?>" placeholder="Numero et nom de rue" required>
                </div>
                <div class="col-md-4">
                    <label class="co-label">Code postal *</label>
                    <input type="text" class="co-input" name="postal_code" id="input-cp"
                           value="<?= htmlspecialchars($user['postal_code']??'') ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="co-label">Ville *</label>
                    <input type="text" class="co-input" name="city" id="input-city"
                           value="<?= htmlspecialchars($user['city']??'') ?>" required>
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
            <?php
            $pay_options = [
                ['value'=>'Carte bancaire',       'icon'=>'', 'label'=>'Carte bancaire',       'desc'=>'Visa, Mastercard, CB'],
                ['value'=>'PayPal',               'icon'=>'', 'label'=>'PayPal',               'desc'=>'Paiement securise via PayPal'],
                ['value'=>'Apple Pay',            'icon'=>'', 'label'=>'Apple Pay',            'desc'=>'Paiement rapide avec Apple Pay'],
                ['value'=>'Paiement a la livraison','icon'=>'','label'=>'Paiement a la livraison','desc'=>'Payez en especes a la reception'],
            ];
            foreach($pay_options as $i => $opt): ?>
            <label class="pay-option <?= $i===0?'selected':'' ?>" onclick="selectPay(this)">
                <input type="radio" name="payment_method" value="<?= $opt['value'] ?>" <?= $i===0?'checked':'' ?>>
                <div class="pay-icon"><?= $opt['icon'] ?></div>
                <div>
                    <div class="pay-label"><?= $opt['label'] ?></div>
                    <div class="pay-desc"><?= $opt['desc'] ?></div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- NOTES -->
    <div class="co-card">
        <div class="co-card-header">
            <i class="bi bi-chat-left-text" style="color:#C9A84C;font-size:1.2rem"></i>
            <h5>Notes / Instructions (optionnel)</h5>
        </div>
        <div class="co-card-body">
            <textarea class="co-input" name="notes" rows="3" placeholder="Instructions speciales pour la livraison, allergies..."></textarea>
        </div>
    </div>

    <!-- BOUTON VALIDER (mobile) -->
    <button type="submit" class="btn-validate d-lg-none mb-4">
        Valider ma commande →
    </button>

</form>
</div>

<!-- RESUME -->
<div class="col-lg-4">
    <div class="summary-card">
        <div class="summary-dark-header">
            <h5>🧾 Recapitulatif</h5>
        </div>
        <div class="co-card-body">
            <?php foreach($cart_items as $item): $p=$item['product']; ?>
            <div class="summary-item">
                <?php if(!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="summary-item-img">
                <?php else: ?>
                    <div class="summary-item-img-ph">🌿</div>
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

            <button type="submit" form="checkout-form" class="btn-validate mt-4 d-none d-lg-block" onclick="document.querySelector('form').submit()">
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
// Selectionner adresse sauvegardee
function selectAddress(el, adresse, ville, cp, tel) {
    document.querySelectorAll('.addr-select').forEach(a=>a.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('input-address').value = adresse;
    document.getElementById('input-city').value = ville;
    document.getElementById('input-cp').value = cp;
    document.getElementById('input-phone').value = tel;
}

// Afficher formulaire nouvelle adresse
function toggleNewAddress() {
    const f = document.getElementById('livraison-form');
    f.style.display = f.style.display==='none' ? 'block' : 'none';
    document.querySelectorAll('.addr-select input').forEach(r=>r.checked=false);
    document.querySelectorAll('.addr-select').forEach(a=>a.classList.remove('selected'));
}

// Selectionner mode paiement
function selectPay(el) {
    document.querySelectorAll('.pay-option').forEach(p=>p.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}

// Pre-remplir adresse principale
window.addEventListener('DOMContentLoaded', function() {
    const principal = document.querySelector('.addr-select.selected');
    if(principal) {
        principal.click();
        document.getElementById('livraison-form').style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>