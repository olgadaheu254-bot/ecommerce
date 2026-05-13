<?php
/**
 * emails/templates/welcome.php
 * Email envoyé juste après l'inscription d'un nouveau client.
 * Variables disponibles : $first_name, $last_name, $email,
 *                         $login_url, $profile_url
 */

$preview_text = "Bienvenue chez HairRoots, {$first_name} ! Votre compte est prêt.";

ob_start(); ?>

<!-- Salutation -->
<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:28px;font-weight:900;
           color:#3E1F0D;margin:0 0 8px;">
  Bienvenue, <?= htmlspecialchars($first_name) ?> ! 
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:24px;"></div>

<!-- Message principal -->
<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 20px;">
  Votre compte <strong style="color:#3E1F0D;">HairRoots</strong> a bien été créé.
  Nous sommes ravis de vous accueillir dans notre communauté capillaire !
</p>

<!-- Encadré récap -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FDF0E8;border-radius:14px;margin-bottom:28px;">
  <tr>
    <td style="padding:22px 28px;">
      <p style="margin:0 0 10px;font-size:13px;font-weight:700;color:#6B3A2A;
                text-transform:uppercase;letter-spacing:1px;">
        Vos informations de connexion
      </p>
      <p style="margin:0 0 6px;font-size:15px;color:#3E1F0D;">
         <strong>Email :</strong> <?= htmlspecialchars($email) ?>
      </p>
      <p style="margin:0;font-size:13px;color:#9a7c5c;">
        Votre mot de passe est celui que vous avez défini lors de l'inscription.
      </p>
    </td>
  </tr>
</table>

<!-- Ce que vous pouvez faire -->
<p style="font-size:15px;font-weight:700;color:#3E1F0D;margin:0 0 14px;">
  Avec votre compte vous pouvez :
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin-bottom:32px;">
  <?php
  $features = [
    ['', 'Commander vos produits capillaires préférés'],
    ['', 'Prendre rendez-vous en ligne avec nos coiffeurs'],
    ['', 'Gérer votre profil et vos adresses de livraison'],
    ['', 'Suivre vos commandes en temps réel'],
  ];
  foreach ($features as $f): ?>
  <tr>
    <td style="padding:7px 0;font-size:15px;color:#4a3728;vertical-align:top;">
      <span style="font-size:18px;margin-right:10px;"><?= $f[0] ?></span>
      <?= htmlspecialchars($f[1]) ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
  <tr>
    <td style="border-radius:12px;background:linear-gradient(135deg,#C9A84C,#b8942e);">
      <a href="<?= htmlspecialchars($login_url) ?>"
         class="btn-main"
         style="display:inline-block;padding:16px 40px;font-size:16px;font-weight:700;
                color:#3E1F0D;text-decoration:none;font-family:'Lato',Arial,sans-serif;
                letter-spacing:0.5px;">
        Se connecter à mon compte →
      </a>
    </td>
  </tr>
</table>

<p style="font-size:13px;color:#9a7c5c;text-align:center;margin:0;">
  Une question ? Répondez directement à cet email ou appelez-nous au
  <strong><?= htmlspecialchars($salon_phone) ?></strong>.
</p>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';