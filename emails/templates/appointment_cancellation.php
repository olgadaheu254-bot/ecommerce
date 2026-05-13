<?php
/**
 * emails/templates/appointment_reminder.php
 * Rappel envoyé 24h avant le rendez-vous (via cron job).
 */

$preview_text = "Rappel : votre {$service} est demain à {$time} chez HairRoots !";

ob_start(); ?>

<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:26px;font-weight:900;
           color:#3E1F0D;margin:0 0 6px;">
  Rappel de rendez-vous 
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:22px;"></div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 24px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  Petit rappel sympa  — vous avez un rendez-vous <strong>demain</strong> chez HairRoots !
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FDF0E8;border-radius:16px;margin-bottom:28px;border-left:4px solid #C9A84C;">
  <tr>
    <td style="padding:22px 28px;">
      <p style="margin:0 0 12px;font-size:18px;font-weight:700;color:#3E1F0D;
                font-family:'Playfair Display',Georgia,serif;">
        <?= htmlspecialchars($service) ?>
      </p>
      <p style="margin:0 0 6px;font-size:15px;color:#4a3728;">
         <strong><?= htmlspecialchars($date) ?></strong> à <strong><?= htmlspecialchars($time) ?></strong>
      </p>
      <?php if (!empty($stylist)): ?>
      <p style="margin:0 0 6px;font-size:14px;color:#6B3A2A;">
         Avec <?= htmlspecialchars($stylist) ?>
      </p>
      <?php endif; ?>
      <p style="margin:0;font-size:14px;color:#6B3A2A;">
         <?= htmlspecialchars($salon_address) ?>
      </p>
    </td>
  </tr>
</table>

<p style="font-size:13px;color:#9a7c5c;text-align:center;margin:0 0 10px;">
  Vous ne pouvez plus venir ?
  <a href="<?= htmlspecialchars($cancel_url) ?>"
     style="color:#c62828;font-weight:700;text-decoration:none;">
    Annuler le rendez-vous
  </a>
</p>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';