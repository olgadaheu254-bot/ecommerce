<?php
/**
 * emails/templates/order_confirmation.php
 * Confirmation d'achat avec récapitulatif des articles.
 * Variables : $first_name, $order_id, $items (array), $total,
 *             $delivery_address, $order_url
 */

$preview_text = "Votre commande #{$order_id} est confirmée — récapitulatif et suivi.";

ob_start(); ?>

<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:26px;font-weight:900;
           color:#3E1F0D;margin:0 0 6px;">
  Commande confirmée 
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:22px;"></div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 24px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  Merci pour votre commande ! Nous l'avons bien reçue et commençons sa préparation.
</p>

<!-- Numéro de commande -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:linear-gradient(135deg,#3E1F0D,#6B3A2A);border-radius:14px;
              margin-bottom:24px;">
  <tr>
    <td style="padding:18px 28px;text-align:center;">
      <p style="margin:0 0 2px;font-size:11px;color:#C9A84C;letter-spacing:3px;text-transform:uppercase;">
        Numéro de commande
      </p>
      <p style="margin:0;font-size:26px;font-weight:700;color:#fff;
                font-family:'Playfair Display',Georgia,serif;">
        #<?= htmlspecialchars($order_id) ?>
      </p>
    </td>
  </tr>
</table>

<!-- Récapitulatif des articles -->
<p style="font-size:14px;font-weight:700;color:#6B3A2A;text-transform:uppercase;
          letter-spacing:1px;margin:0 0 12px;">
  Votre panier
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;margin-bottom:8px;">
  <!-- En-tête -->
  <tr style="background:#F5EDE3;">
    <td style="padding:10px 14px;font-size:12px;font-weight:700;color:#6B3A2A;
               text-transform:uppercase;letter-spacing:0.8px;border-radius:8px 0 0 0;">
      Produit
    </td>
    <td style="padding:10px 14px;font-size:12px;font-weight:700;color:#6B3A2A;
               text-transform:uppercase;letter-spacing:0.8px;text-align:center;">
      Qté
    </td>
    <td style="padding:10px 14px;font-size:12px;font-weight:700;color:#6B3A2A;
               text-transform:uppercase;letter-spacing:0.8px;text-align:right;
               border-radius:0 8px 0 0;">
      Prix
    </td>
  </tr>
  <!-- Articles -->
  <?php foreach ($items as $i => $item):
    $bg = $i % 2 === 0 ? '#fff' : '#FDFAF7';
  ?>
  <tr class="order-item" style="border-bottom:1px solid #F5EDE3;">
    <td style="padding:12px 14px;font-size:14px;color:#3E1F0D;background:<?= $bg ?>;">
      <?= htmlspecialchars($item['name']) ?>
    </td>
    <td style="padding:12px 14px;font-size:14px;color:#6B3A2A;text-align:center;background:<?= $bg ?>;">
      ×<?= (int)$item['qty'] ?>
    </td>
    <td style="padding:12px 14px;font-size:14px;color:#3E1F0D;font-weight:700;
               text-align:right;background:<?= $bg ?>;">
      <?= number_format((float)$item['price'] * (int)$item['qty'], 2, ',', ' ') ?> €
    </td>
  </tr>
  <?php endforeach; ?>
  <!-- Total -->
  <tr>
    <td colspan="2"
        style="padding:14px 14px;font-size:15px;font-weight:700;color:#3E1F0D;
               background:#FDF0E8;border-radius:0 0 0 10px;">
      Total TTC
    </td>
    <td style="padding:14px 14px;font-size:18px;font-weight:700;color:#C1622F;
               text-align:right;background:#FDF0E8;border-radius:0 0 10px 0;">
      <?= htmlspecialchars($total) ?> €
    </td>
  </tr>
</table>

<?php if (!empty($delivery_address)): ?>
<!-- Adresse de livraison -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F5EDE3;border-radius:12px;margin:20px 0 28px;">
  <tr>
    <td style="padding:18px 24px;">
      <p style="margin:0 0 6px;font-size:12px;font-weight:700;color:#6B3A2A;
                text-transform:uppercase;letter-spacing:1px;">
         Livraison à
      </p>
      <p style="margin:0;font-size:14px;color:#3E1F0D;line-height:1.6;">
        <?= nl2br(htmlspecialchars($delivery_address)) ?>
      </p>
    </td>
  </tr>
</table>
<?php endif; ?>

<!-- CTA suivre la commande -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;">
  <tr>
    <td style="border-radius:12px;background:linear-gradient(135deg,#C9A84C,#b8942e);">
      <a href="<?= htmlspecialchars($order_url) ?>"
         style="display:inline-block;padding:15px 36px;font-size:15px;font-weight:700;
                color:#3E1F0D;text-decoration:none;font-family:'Lato',Arial,sans-serif;">
        Suivre ma commande →
      </a>
    </td>
  </tr>
</table>

<p style="font-size:13px;color:#9a7c5c;text-align:center;margin:0;">
  Vous recevrez un email dès que votre commande sera expédiée.
</p>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';