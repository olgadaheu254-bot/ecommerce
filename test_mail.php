<?php
require_once 'includes/Mailer.php';

$mailer = new Mailer();

$result = $mailer->sendWelcome(
    'olgadaheu254@gmail.com',   // ← mets ton adresse email ici
    'prenom',                    // ← mets ton prénom ici (ou un autre nom si tu préfères)
    'Bienvenue chez HairRoots !'
);

if ($result) {
    echo " Email envoyé avec succès !";
} else {
    echo " Erreur lors de l'envoi.";
}
?>