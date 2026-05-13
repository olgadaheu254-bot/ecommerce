<?php
/**
 * emails/templates/promo.php
 * Email marketing / promotion / newsletter.
 */

$preview_text = "Offre exclusive HairRoots : {$promo_title}";

ob_start(); ?>

<!-- Hero promo -->
<div style="background:linear-gradient(135deg,#3E1F0D 0%,#C1622F 60%,#C9A84C 100%);
            border-radius:16px;padding:36px 32px;text-align:center;margin-bottom:28px;">
  <p style="margin:0 0 8px;font-size:11px;color:rgba(255,255,255,0.7);
             letter-spacing:4px;text-transform:uppercase;">
    Offre exclusive
  </p>
  <h1 style="font-family:'Playfair Display',Georgia,serif;font-size:30px;font-weight:900;
             color:#fff;margin:0 0 12px;line-height:1.2;">
    <?= htmlspecialchars($promo_title) ?>
  </h1>
  <?php if (!empty($promo_end_date)): ?>
  <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.75);">
    ⏳ Jusqu'au <?= htmlspecialchars($promo_end_date) ?>
  </p>
  <?php endif; ?>
</div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 24px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  <?= htmlspecialchars($promo_description) ?>
</p>

<?php if (!empty($promo_code)): ?>
<!-- Code promo -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin-bottom:28px;">
  <tr>
    <td align="center">
      <p style="font-size:13px;color:#6B3A2A;margin:0 0 10px;font-weight:700;
                text-transform:uppercase;letter-spacing:1px;">
        Votre code promotionnel
      </p>
      <div style="display:inline-block;background:#FDF0E8;border:2px dashed #C9A84C;
                  border-radius:14px;padding:16px 40px;">
        <span style="font-size:28px;font-weight:900;color:#3E1F0D;letter-spacing:6px;
                     font-family:'Playfair Display',Georgia,serif;">
          <?= htmlspecialchars($promo_code) ?>
        </span>
      </div>
      <p style="font-size:12px;color:#9a7c5c;margin:10px 0 0;">
        Entrez ce code lors de votre prochaine commande ou réservation.
      </p>
    </td>
  </tr>
</table>
<?php endif; ?>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
  <tr>
    <td style="border-radius:12px;background:linear-gradient(135deg,#C9A84C,#b8942e);">
      <a href="<?= htmlspecialchars($cta_url) ?>"
         style="display:inline-block;padding:16px 44px;font-size:16px;font-weight:700;
                color:#3E1F0D;text-decoration:none;">
        <?= htmlspecialchars($cta_label) ?> →
      </a>
    </td>
  </tr>
</table>

<?php if (!empty($promo_end_date)): ?>
<p style="font-size:12px;color:#9a7c5c;text-align:center;margin:0;">
  Offre valable jusqu'au <?= htmlspecialchars($promo_end_date) ?>, dans la limite des stocks disponibles.
</p>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';