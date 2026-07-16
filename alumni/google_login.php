<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php'; // Google API Client Library


require __DIR__ . '/vendor/autoload.php';

// ... kode Anda untuk menggunakan Google API Client, misalnya:
use Google\Client;
use Google\Service\Oauth2;

$client = new Client();
// ... sisa kode Anda
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
    $google_oauth = new Google_Service_Oauth2($client);
    $user_info = $google_oauth->userinfo->get();
    $email = $user_info->email;

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user_id = $conn->insert_id;
    } else {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: ' . $client->createAuthUrl());
    exit;
}
?>