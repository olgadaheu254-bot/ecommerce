<?php
/**
 * emails/templates/appointment_confirmation.php
 * Confirmation de prise de rendez-vous au salon.
 * Variables : $first_name, $service, $date, $time, $stylist,
 *             $appointment_id, $cancel_url, $salon_address, $salon_phone
 */

$preview_text = "Votre rendez-vous {$service} du {$date} à {$time} est confirmé !";

ob_start(); ?>

<h1 style="font-family:'Playfair Display',Georgia,serif;font-size:26px;font-weight:900;
           color:#3E1F0D;margin:0 0 6px;">
  Rendez-vous confirmé 
</h1>
<div style="width:40px;height:3px;background:linear-gradient(90deg,#C9A84C,#C1622F);
            border-radius:2px;margin-bottom:22px;"></div>

<p style="font-size:16px;color:#4a3728;line-height:1.75;margin:0 0 28px;">
  Bonjour <strong><?= htmlspecialchars($first_name) ?></strong>,<br>
  Votre rendez-vous est bien enregistré. Nous avons hâte de vous accueillir !
</p>

<!-- Carte récap du RDV -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:linear-gradient(135deg,#3E1F0D 0%,#5a2c1a 100%);
              border-radius:18px;margin-bottom:28px;overflow:hidden;">
  <tr>
    <td style="padding:30px 32px;">

      <!-- Service -->
      <p style="margin:0 0 20px;font-size:11px;color:#C9A84C;letter-spacing:3px;
                text-transform:uppercase;">
        Votre prestation
      </p>
      <p style="margin:0 0 24px;font-size:22px;font-weight:700;color:#fff;
                font-family:'Playfair Display',Georgia,serif;">
        <?= htmlspecialchars($service) ?>
      </p>

      <!-- Grille date / heure / coiffeur -->
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="width:50%;padding-right:12px;vertical-align:top;">
            <div style="background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.35);
                        border-radius:12px;padding:14px 16px;">
              <p style="margin:0 0 4px;font-size:10px;color:#C9A84C;letter-spacing:2px;
                         text-transform:uppercase;"> Date</p>
              <p style="margin:0;font-size:15px;font-weight:700;color:#fff;">
                <?= htmlspecialchars($date) ?>
              </p>
            </div>
          </td>
          <td style="width:50%;padding-left:12px;vertical-align:top;">
            <div style="background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.35);
                        border-radius:12px;padding:14px 16px;">
              <p style="margin:0 0 4px;font-size:10px;color:#C9A84C;letter-spacing:2px;
                         text-transform:uppercase;"> Heure</p>
              <p style="margin:0;font-size:15px;font-weight:700;color:#fff;">
                <?= htmlspecialchars($time) ?>
              </p>
            </div>
          </td>
        </tr>
      </table>

      <!-- Coiffeur -->
      <?php if (!empty($stylist)): ?>
      <div style="margin-top:14px;background:rgba(255,255,255,0.07);border-radius:12px;
                  padding:14px 16px;">
        <p style="margin:0 0 3px;font-size:10px;color:#C9A84C;letter-spacing:2px;
                   text-transform:uppercase;"> Votre coiffeur</p>
        <p style="margin:0;font-size:15px;font-weight:700;color:#fff;">
          <?= htmlspecialchars($stylist) ?>
        </p>
      </div>
      <?php endif; ?>

    </td>
  </tr>
</table>

<!-- Adresse du salon -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FDF0E8;border-radius:14px;margin-bottom:28px;">
  <tr>
    <td style="padding:20px 24px;">
      <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#6B3A2A;
                text-transform:uppercase;letter-spacing:1px;">
         Où nous trouver
      </p>
      <p style="margin:0 0 4px;font-size:15px;color:#3E1F0D;">
        <?= htmlspecialchars($salon_address) ?>
      </p>
      <p style="margin:0;font-size:14px;color:#9a7c5c;">
         <?= htmlspecialchars($salon_phone) ?>
      </p>
    </td>
  </tr>
</table>

<!-- Lien d'annulation discret -->
<p style="font-size:13px;color:#9a7c5c;text-align:center;margin:0 0 8px;">
  Vous ne pouvez plus venir ?
</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr>
    <td style="border-radius:10px;background:#fff;border:2px solid #F5EDE3;">
      <a href="<?= htmlspecialchars($cancel_url) ?>"
         style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:700;
                color:#c62828;text-decoration:none;">
        Annuler mon rendez-vous
      </a>
    </td>
  </tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/_layout.php';