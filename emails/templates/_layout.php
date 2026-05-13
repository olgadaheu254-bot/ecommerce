<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($salon_name ?? 'HairRoots') ?></title>
</head>
<body style="margin:0;padding:0;background-color:#F5EDE3;font-family:Arial,sans-serif;">

<!-- Texte de previsualisation invisible -->
<div style="display:none;max-height:0;overflow:hidden;">
  <?= htmlspecialchars($preview_text ?? '') ?>
</div>

<!-- Wrapper global -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5EDE3;padding:32px 0;">
  <tr>
    <td align="center">

      <!-- Carte email max 600px -->
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:20px;overflow:hidden;">

        <!-- EN-TETE -->
        <tr>
          <td style="background-color:#3E1F0D;padding:36px 40px 28px;text-align:center;">
            <div style="font-family:Georgia,serif;font-size:32px;font-weight:900;color:#C9A84C;letter-spacing:2px;margin-bottom:4px;">
              HairRoots
            </div>
            <div style="font-size:11px;color:#e8d5b7;letter-spacing:4px;">
              SALON CAPILLAIRE
            </div>
            <div style="width:50px;height:2px;background-color:#C9A84C;margin:16px auto 0;"></div>
          </td>
        </tr>

        <!-- CONTENU PRINCIPAL -->
        <tr>
          <td style="background-color:#ffffff;padding:44px 48px;">
            <?= $content ?? '' ?>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#3E1F0D;padding:28px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:13px;color:#C9A84C;font-weight:700;">
              <?= htmlspecialchars($salon_name ?? 'HairRoots') ?>
            </p>
            <p style="margin:0 0 4px;font-size:12px;color:#b89a7a;">
              <?= htmlspecialchars($salon_address ?? '') ?>
            </p>
            <p style="margin:0 0 16px;font-size:12px;color:#b89a7a;">
              <?= htmlspecialchars($salon_phone ?? '') ?>
              |
              <a href="<?= htmlspecialchars($salon_website ?? '#') ?>" style="color:#C9A84C;text-decoration:none;">
                <?= htmlspecialchars(str_replace('https://', '', $salon_website ?? '')) ?>
              </a>
            </p>
            <p style="margin:0;font-size:10px;color:#7a5c42;">
              Vous recevez cet email car vous avez un compte sur HairRoots.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>