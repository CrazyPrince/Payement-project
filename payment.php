<?php
// Etape 1 : Créer un compte marchand sur Orange Money et obtenir les clés d'authentification et de paiement
// Remplacer les valeurs par celles fournies par votre agrégateur Web Payment / M Payment
$authorization_header = "VotreAuthorizationHeader"; // Header d'authentification
$merchant_key = "VotreMerchantKey"; // Clé du marchand
$merchant_reference = "VotreMerchantReference"; // Référence du marchand
$merchant_name = "VotreMerchantName"; // Nom du marchand
$merchant_logo_url = "VotreMerchantLogoUrl"; // URL du logo du marchand
$merchant_website_url = "VotreMerchantWebsiteUrl"; // URL du site web du marchand
$merchant_callback_url = "VotreMerchantCallbackUrl"; // URL de retour du marchand
$merchant_cancel_url = "VotreMerchantCancelUrl"; // URL d'annulation du marchand
$merchant_notify_url = "VotreMerchantNotifyUrl"; // URL de notification du marchand

// Etape 2 : Intégrer l'API de Web Payment / M Payment d'Orange Money en utilisant les requêtes HTTP POST et GET
// Définir les URL de l'API
$token_url = "https://api.orange.com/oauth/v3/token"; // URL pour obtenir le jeton d'accès
$payment_url = "https://api.orange.com/orange-money-webpay/cm/v1/webpayment"; // URL pour initier le paiement
$check_payment_url = "https://api.orange.com/orange-money-webpay/cm/v1/transactionstatus"; // URL pour vérifier le statut du paiement

// Définir les paramètres du paiement
$amount = $_POST['amount']; // Montant à payer en XAF
$phone_number = $_POST['phone_number']; // Numéro de téléphone du client
$payment_service = $_POST['payment_service']; // Service de paiement choisi (Orange Money ou Mobile Money MTN)
$order_id = uniqid(); // Identifiant unique de la commande
$description = "Paiement de la commande $order_id"; // Description du paiement

// Obtenir le jeton d'accès
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $token_url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Authorization: Basic $authorization_header",
    "Content-Type: application/x-www-form-urlencoded"
));
curl_setopt($curl, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
$response = curl_exec($curl);
curl_close($curl);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        $access_token = $data['access_token']; // Jeton d'accès
    } else {
        die("Erreur : impossible d'obtenir le jeton d'accès");
    }
} else {
    die("Erreur : impossible de se connecter à l'API");
}

// Initier le paiement
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $payment_url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
));
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
    "merchant_key" => $merchant_key,
    "currency" => "XAF",
    "order_id" => $order_id,
    "amount" => $amount,
    "return_url" => $merchant_callback_url,
    "cancel_url" => $merchant_cancel_url,
    "notif_url" => $merchant_notify_url,
    "lang" => "fr",
    "reference" => $merchant_reference,
    "payment_mean" => $payment_service,
    "payer_phone" => $phone_number,
    "payee" => array(
        "name" => $merchant_name,
        "logo" => $merchant_logo_url,
        "website" => $merchant_website_url
    ),
    "order_info" => array(
        "description" => $description
    )
)));
$response = curl_exec($curl);
curl_close($curl);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['payment_url'])) {
        $payment_url = $data['payment_url']; // URL de redirection vers la page de validation du paiement
    } else {
        die("Erreur : impossible d'initier le paiement");
    }
} else {
    die("Erreur : impossible de se connecter à l'API");
}

// Rediriger le client vers la page de validation du paiement
header("Location: $payment_url");
exit();

// Etape 3 : Traitement de la notification
// Cette partie du code doit être placée dans le fichier correspondant à l'URL de notification du marchand
// Récupérer les données envoyées par l'API
$payment_data = file_get_contents("php://input");
if ($payment_data) {
    $data = json_decode($payment_data, true);
    if (isset($data['status']) && isset($data['order_id']) && isset($data['txnid'])) {
        $status = $data['status']; // Statut du paiement
        $order_id = $data['order_id']; // Identifiant de la commande
        $transaction_id = $data['txnid']; // Identifiant de la transaction
        // Traiter le résultat du paiement selon le statut
        if ($status == "SUCCESS") {
            // Le paiement a été effectué avec succès
            // Mettre à jour la base de données, envoyer un email de confirmation, etc.
            echo "OK"; // Répondre OK à l'API pour confirmer la réception de la notification
        } elseif ($status == "FAILURE") {
            // Le paiement a échoué
            // Afficher un message d'erreur, annuler la commande, etc.
            echo "OK"; // Répondre OK à l'API pour confirmer la réception de la notification
        } elseif ($status == "PENDING") {
            // Le paiement est en attente de validation
            // Afficher un message d'attente, vérifier le statut du paiement ultérieurement, etc.
            echo "OK"; // Répondre OK à l'API pour confirmer la réception de la notification
        } else {
            // Le statut du paiement est inconnu
            // Afficher un message d'erreur, contacter le support, etc.
            echo "KO"; // Répondre KO à l'API pour signaler un problème
        }
    } else {
        // Les données envoyées par l'API sont incomplètes ou incorrectes
        // Afficher un message d'erreur, contacter le support, etc.
        echo "KO"; // Répondre KO à l'API pour signaler un problème
    }
} else {
    // Aucune donnée n'a été envoyée par l'API
    // Afficher un message d'erreur, contacter le support, etc.
    echo "KO"; // Répondre KO à l'API pour signaler un problème
}
?>
