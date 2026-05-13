<?php
/**
 * emails/templates/shipping_update.php
 * Mise à jour du statut d'expédition d'une commande.
 */

$preview_text = "Votre commande #{$order_id} est {$status_label} — suivez-la en temps réel.";

$statusConfig = [
    'processing' => ['icon' => '', 'color' => '#e65100', 'bg' => '#fff3e0', 'label' => 'En préparation'],
    'shipped'    => ['icon' => '', 'color' => '#1565c0', 'bg' => '#e3f2fd', 'label' => 'Expédiée'],
    'delivered'  => ['icon' => '', 'color' => '#2e7d32', 'bg' => '#e8f5e9', 'label' => 'Livrée'],
];
$cfg = $statusConfig[$status] ?? ['icon' => '', 'color' => '#6B3A2A', 'bg' => '#FDF0E8', 'label' => $status_label];

ob_start(); ?>

<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:26px;font-weight:900;
           color:#3E1F0D;margin:0 0 6px;">
  <?= $cfg['icon'] ?> Mise à jour de votre commande
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:22px;"></div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 24px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  Votre commande <strong>#<?= htmlspecialchars($order_id) ?></strong> vient de changer de statut.
</p>

<!-- Badge statut -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:<?= $cfg['bg'] ?>;border-radius:16px;margin-bottom:24px;
              border-left:5px solid <?= $cfg['color'] ?>;">
  <tr>
    <td style="padding:22px 28px;">
      <p style="margin:0 0 4px;font-size:11px;color:#9a7c5c;text-transform:uppercase;letter-spacing:2px;">
        Nouveau statut
      </p>
      <p style="margin:0;font-size:22px;font-weight:700;color:<?= $cfg['color'] ?>;
                font-family:'Playfair Display',Georgia,serif;">
        <?= $cfg['icon'] ?> <?= htmlspecialchars($cfg['label']) ?>
      </p>
    </td>
  </tr>
</table>

<?php if (!empty($tracking_number)): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F5EDE3;border-radius:14px;margin-bottom:24px;">
  <tr>
    <td style="padding:18px 24px;">
      <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#6B3A2A;
                text-transform:uppercase;letter-spacing:1px;">
        Numéro de suivi
      </p>
      <p style="margin:0;font-size:16px;font-weight:700;color:#3E1F0D;letter-spacing:1px;">
        <?= htmlspecialchars($tracking_number) ?>
      </p>
      <?php if (!empty($tracking_url)): ?>
      <p style="margin:8px 0 0;">
        <a href="<?= htmlspecialchars($tracking_url) ?>"
           style="color:#C1622F;font-weight:700;font-size:14px;text-decoration:none;">
          Suivre sur le site du transporteur →
        </a>
      </p>
      <?php endif; ?>
    </td>
  </tr>
</table>
<?php endif; ?>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;">
  <tr>
    <td style="border-radius:12px;background:linear-gradient(135deg,#C9A84C,#b8942e);">
      <a href="<?= htmlspecialchars($order_url) ?>"
         style="display:inline-block;padding:15px 36px;font-size:15px;font-weight:700;
                color:#3E1F0D;text-decoration:none;">
        Voir ma commande →
      </a>
    </td>
  </tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';