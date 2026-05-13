<?php
/**
 * ============================================================
 *  HairRoots — Système de mailing centralisé
 *  Fichier  : includes/Mailer.php
 * ============================================================
 */

// ---- Chargement de PHPMailer via Composer ----
$phpmailerPath = $_SERVER['DOCUMENT_ROOT'] . '/ecommerce/vendor/autoload.php';
if (file_exists($phpmailerPath)) {
    require_once $phpmailerPath;
}

class Mailer
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            // ---- Expéditeur ----
            'from_email' => 'noreply@hairroots.fr',
            'from_name'  => 'HairRoots',
            'reply_to'   => 'contact@hairroots.fr',

            // ---- SMTP ----
            'smtp_host'     => 'smtp.gmail.com',
            'smtp_port'     => 587,
            'smtp_secure'   => 'tls',
            'smtp_user'     => 'olgadaheu254@gmail.com',
            'smtp_password' => 'jyhu fosw iapi hnao',

            // ---- Infos salon ----
            'salon_name'    => 'HairRoots',
            'salon_address' => '12 rue des Boucles, 75011 Paris',
            'salon_phone'   => '01 23 45 67 89',
            'salon_website' => 'http://localhost/ecommerce',
            'salon_logo'    => 'http://localhost/ecommerce/assets/img/logo.png',

            // ---- Debug ----
            'debug' => false,
        ], $config);
    }

    /* ==========================================================
       1. EMAIL DE BIENVENUE
       ========================================================== */
    public function sendWelcome(string $toEmail, string $firstName, string $lastName): bool
    {
        $subject = "Bienvenue chez HairRoots, {$firstName} !";

        $body = $this->loadTemplate('welcome', [
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => $toEmail,
            'login_url'   => $this->config['salon_website'] . '/pages/login.php',
            'profile_url' => $this->config['salon_website'] . '/pages/profile.php',
        ]);

        return $this->send($toEmail, "{$firstName} {$lastName}", $subject, $body);
    }

    /* ==========================================================
       2. CONFIRMATION DE COMMANDE
       ========================================================== */
    public function sendOrderConfirmation(
        string $toEmail,
        string $firstName,
        int    $orderId,
        array  $items,
        float  $total,
        string $deliveryAddress = ''
    ): bool {
        $subject = "Votre commande #{$orderId} est confirmée";

        $body = $this->loadTemplate('order_confirmation', [
            'first_name'       => $firstName,
            'order_id'         => $orderId,
            'items'            => $items,
            'total'            => number_format($total, 2, ',', ' '),
            'delivery_address' => $deliveryAddress,
            'order_url'        => $this->config['salon_website'] . "/pages/order.php?id={$orderId}",
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       3. CONFIRMATION DE RENDEZ-VOUS
       ========================================================== */
    public function sendAppointmentConfirmation(
        string $toEmail,
        string $firstName,
        string $service,
        string $date,
        string $time,
        string $stylist = '',
        int    $appointmentId = 0
    ): bool {
        $subject = "Votre rendez-vous du {$date} est confirme";

        $body = $this->loadTemplate('appointment_confirmation', [
            'first_name'     => $firstName,
            'service'        => $service,
            'date'           => $date,
            'time'           => $time,
            'stylist'        => $stylist ?: 'Votre coiffeur HairRoots',
            'appointment_id' => $appointmentId,
            'cancel_url'     => $this->config['salon_website'] . "/pages/cancel_appointment.php?id={$appointmentId}",
            'salon_address'  => $this->config['salon_address'],
            'salon_phone'    => $this->config['salon_phone'],
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       4. RAPPEL DE RENDEZ-VOUS
       ========================================================== */
    public function sendAppointmentReminder(
        string $toEmail,
        string $firstName,
        string $service,
        string $date,
        string $time,
        string $stylist = '',
        int    $appointmentId = 0
    ): bool {
        $subject = "Rappel : votre rendez-vous demain a {$time}";

        $body = $this->loadTemplate('appointment_reminder', [
            'first_name'     => $firstName,
            'service'        => $service,
            'date'           => $date,
            'time'           => $time,
            'stylist'        => $stylist ?: 'Votre coiffeur HairRoots',
            'appointment_id' => $appointmentId,
            'cancel_url'     => $this->config['salon_website'] . "/pages/cancel_appointment.php?id={$appointmentId}",
            'salon_address'  => $this->config['salon_address'],
            'salon_phone'    => $this->config['salon_phone'],
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       5. ANNULATION DE RENDEZ-VOUS
       ========================================================== */
    public function sendAppointmentCancellation(
        string $toEmail,
        string $firstName,
        string $service,
        string $date,
        string $time
    ): bool {
        $subject = "Annulation de votre rendez-vous du {$date}";

        $body = $this->loadTemplate('appointment_cancellation', [
            'first_name'  => $firstName,
            'service'     => $service,
            'date'        => $date,
            'time'        => $time,
            'booking_url' => $this->config['salon_website'] . '/pages/booking.php',
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       6. REINITIALISATION MOT DE PASSE
       ========================================================== */
    public function sendPasswordReset(
        string $toEmail,
        string $firstName,
        string $resetToken
    ): bool {
        $subject = "Reinitialisation de votre mot de passe HairRoots";

        $resetUrl = $this->config['salon_website'] . "/pages/reset_password.php?token={$resetToken}";

        $body = $this->loadTemplate('password_reset', [
            'first_name' => $firstName,
            'reset_url'  => $resetUrl,
            'expires_in' => '1 heure',
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       7. NOTIFICATION LIVRAISON
       ========================================================== */
    public function sendShippingUpdate(
        string $toEmail,
        string $firstName,
        int    $orderId,
        string $status,
        string $trackingNumber = '',
        string $trackingUrl    = ''
    ): bool {
        $statusLabels = [
            'processing' => 'en preparation',
            'shipped'    => 'expediee',
            'delivered'  => 'livree',
        ];
        $label   = $statusLabels[$status] ?? $status;
        $subject = "Votre commande #{$orderId} est {$label}";

        $body = $this->loadTemplate('shipping_update', [
            'first_name'      => $firstName,
            'order_id'        => $orderId,
            'status'          => $status,
            'status_label'    => $label,
            'tracking_number' => $trackingNumber,
            'tracking_url'    => $trackingUrl,
            'order_url'       => $this->config['salon_website'] . "/pages/order.php?id={$orderId}",
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       8. EMAIL PROMOTIONNEL / NEWSLETTER
       ========================================================== */
    public function sendPromo(
        string $toEmail,
        string $firstName,
        string $promoTitle,
        string $promoDescription,
        string $promoCode    = '',
        string $promoEndDate = '',
        string $ctaLabel     = 'Profiter de l\'offre',
        string $ctaUrl       = ''
    ): bool {
        $subject = "{$promoTitle} - Offre exclusive HairRoots";

        $body = $this->loadTemplate('promo', [
            'first_name'        => $firstName,
            'promo_title'       => $promoTitle,
            'promo_description' => $promoDescription,
            'promo_code'        => $promoCode,
            'promo_end_date'    => $promoEndDate,
            'cta_label'         => $ctaLabel,
            'cta_url'           => $ctaUrl ?: $this->config['salon_website'],
        ]);

        return $this->send($toEmail, $firstName, $subject, $body);
    }

    /* ==========================================================
       METHODE D'ENVOI CENTRALE
       ========================================================== */
    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody);
        }
        return $this->sendViaNativeMail($toEmail, $toName, $subject, $htmlBody);
    }

    private function sendViaPHPMailer(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        try {
            // Utilisation du namespace complet — pas besoin de "use"
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $this->config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp_user'];
            $mail->Password   = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_secure'];
            $mail->Port       = $this->config['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            if ($this->config['debug']) {
                $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addReplyTo($this->config['reply_to'], $this->config['salon_name']);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

            $mail->send();
            return true;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            if ($this->config['debug']) {
                error_log("[HairRoots Mailer] PHPMailer error: " . $e->getMessage());
            }
            return false;
        }
    }

    private function sendViaNativeMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";
        $headers .= "Reply-To: {$this->config['reply_to']}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $result = mail($toEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);

        if (!$result && $this->config['debug']) {
            error_log("[HairRoots Mailer] mail() failed for: {$toEmail}");
        }
        return $result;
    }

    /* ==========================================================
       CHARGEMENT ET RENDU DES TEMPLATES
       ========================================================== */
    private function loadTemplate(string $name, array $vars = []): string
    {
        $templateFile = __DIR__ . "/../emails/templates/{$name}.php";

        if (!file_exists($templateFile)) {
            if ($this->config['debug']) {
                error_log("[HairRoots Mailer] Template introuvable : {$templateFile}");
            }
            return "<p>Email {$name}</p>";
        }

        $vars = array_merge($vars, [
            'salon_name'    => $this->config['salon_name'],
            'salon_address' => $this->config['salon_address'],
            'salon_phone'   => $this->config['salon_phone'],
            'salon_website' => $this->config['salon_website'],
            'salon_logo'    => $this->config['salon_logo'],
        ]);

        extract($vars, EXTR_SKIP);
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
}