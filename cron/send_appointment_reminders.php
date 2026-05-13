<?php
/**
 * ============================================================
 *  HairRoots — Cron : rappels automatiques de rendez-vous
 *  Fichier  : cron/send_appointment_reminders.php
 *
 *  À planifier via crontab (exécution quotidienne à 10h) :
 *    0 10 * * * php /var/www/hairroots/cron/send_appointment_reminders.php
 *
 *  Ce script interroge la BDD pour trouver tous les rendez-vous
 *  prévus DEMAIN et envoie un rappel par email.
 * ============================================================
 */

// ---- Sécurité : ce script ne doit être appelé qu'en CLI ----
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    exit('Accès interdit');
}

require_once 'C:/xamp1/htdocs/ecommerce/config/database.php';
require_once 'C:/xamp1/htdocs/ecommerce/vendor/autoload.php';
require_once 'C:/xamp1/htdocs/ecommerce/includes/Mailer.php';
$mailer = new Mailer();

/* ----------------------------------------------------------
   Sélectionne les rendez-vous prévus demain (J+1)
   Table attendue : appointments
   Colonnes       : id, user_id, service, appointment_date,
                    appointment_time, stylist, reminder_sent
   ---------------------------------------------------------- */
$tomorrow = (new DateTime('+1 day'))->format('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.type_prestation,
        a.date_rdv,
        a.heure_rdv,
        a.statut,
        a.reminder_sent,
        u.email,
        u.first_name,
        u.last_name,
        CONCAT(c.prenom, ' ', c.nom) AS stylist
    FROM appointments a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN coiffeuses c ON c.id = a.coiffeuse_id
    WHERE a.date_rdv = ?
      AND a.statut != 'annule'
      AND a.reminder_sent = 0
");
$stmt->execute([$tomorrow]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sent  = 0;
$error = 0;

foreach ($appointments as $appt) {

    /* ---- Formatage de la date en français ---- */
   $mois = [
    1=>'janvier',2=>'fevrier',3=>'mars',4=>'avril',
    5=>'mai',6=>'juin',7=>'juillet',8=>'aout',
    9=>'septembre',10=>'octobre',11=>'novembre',12=>'decembre'
    ];
        $dateObj = new DateTime($appt['date_rdv']);
        $dateFr  = $dateObj->format('d') . ' ' . $mois[(int)$dateObj->format('m')] . ' ' . $dateObj->format('Y');
            /* ---- Formatage de l'heure (ex : "14:30" → "14h30") ---- */
            $timeFr = str_replace(':', 'h', substr($appt['heure_rdv'], 0, 5));

    /* ---- Envoi du rappel ---- */
    $ok = $mailer->sendAppointmentReminder(
        $appt['email'],
        $appt['first_name'],
        $appt['type_prestation'],
        $dateFr,
        $timeFr,
        $appt['stylist'] ?? '',
        (int) $appt['id']
    );

    if ($ok) {
        /* ---- Marque le rappel comme envoyé pour ne pas doubler ---- */
        $upd = $pdo->prepare("UPDATE appointments SET reminder_sent = 1 WHERE id = ?");
        $upd->execute([$appt['id']]);
        $sent++;
        echo "[OK] Rappel envoyé à {$appt['email']} (RDV #{$appt['id']})\n";
    } else {
        $error++;
        echo "[ERR] Échec envoi à {$appt['email']} (RDV #{$appt['id']})\n";
    }
}

echo "\n=== Résultat : {$sent} rappel(s) envoyé(s), {$error} erreur(s) ===\n";