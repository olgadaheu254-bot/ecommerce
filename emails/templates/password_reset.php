<?php
/**
 * emails/templates/password_reset.php
 */

$preview_text = "Réinitialisez votre mot de passe HairRoots — lien valable {$expires_in}.";

ob_start(); ?>

<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:26px;font-weight:900;
           color:#3E1F0D;margin:0 0 6px;">
  Réinitialisation du mot de passe 
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:22px;"></div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 20px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FDF0E8;border-radius:14px;margin-bottom:28px;">
  <tr>
    <td style="padding:18px 24px;text-align:center;">
      <p style="margin:0;font-size:13px;color:#9a7c5c;">
         Ce lien est valable <strong><?= htmlspecialchars($expires_in) ?></strong> uniquement.
      </p>
    </td>
  </tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
  <tr>
    <td style="border-radius:12px;background:linear-gradient(135deg,#C9A84C,#b8942e);">
      <a href="<?= htmlspecialchars($reset_url) ?>"
         style="display:inline-block;padding:16px 40px;font-size:15px;font-weight:700;
                color:#3E1F0D;text-decoration:none;">
        Choisir un nouveau mot de passe →
      </a>
    </td>
  </tr>
</table>

<p style="font-size:13px;color:#9a7c5c;text-align:center;margin:0;">
  Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.
  Votre mot de passe actuel reste inchangé.
</p>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';