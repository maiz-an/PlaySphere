<?php
session_start();
require 'vendor/autoload.php';
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

// Validate session
if (!isset($_SESSION['username']) || !isset($_SESSION['customer_id'])) {
    header("Location: index.php#login-section");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$username = htmlspecialchars($_SESSION['username']);
$name = htmlspecialchars($_SESSION['name']);

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: booking.php");
    exit();
}

// Required fields
$required_fields = ['futsal_id', 'start_time', 'end_time', 'total_cost'];
$missing_fields = array_diff($required_fields, array_keys($_POST));

if (!empty($missing_fields)) {
    echo "Missing fields: " . implode(', ', $missing_fields) . ". Redirecting...";
    header("Refresh: 3; url=booking.php");
    exit();
}

// Convert LKR to USD
$total_cost_lkr = number_format((float)$_POST['total_cost'], 2, '.', '');
$lkr_to_usd_rate = 0.003; // Example: 1 LKR = 0.003 USD
$total_cost_usd = number_format($total_cost_lkr * $lkr_to_usd_rate, 2, '.', '');

try {
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');

    $amount = new Amount();
    $amount->setTotal($total_cost_usd);
    $amount->setCurrency('USD');

    $transaction = new Transaction();
    $transaction->setAmount($amount);
    $transaction->setDescription('Futsal Booking Payment');

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl("http://localhost/PlaySphere/payment_success.php")
                 ->setCancelUrl("http://localhost/PlaySphere/payment_cancel.php?reason=User+Cancelled+the+Payment");

    $payment = new Payment();
    $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

    $apiContext = new \PayPal\Rest\ApiContext(
        new \PayPal\Auth\OAuthTokenCredential(
            'AWB-uJNAy7dIng1crd9LOsjNiMB3fAfZi91-O1NYy323rHGrjLFFmdByjhNdZ30ldfRLyXsRKU3dLXT4',
            'EJ8nufZKYZXbj9EUwVAcROOFTNnA3f-8YmbzcJSJElEXuk3Nop-KsEyWEwZL5-92ir4Als4y8QtzmhBU'
        )
    );

    $apiContext->setConfig(['mode' => 'sandbox']);

    $payment->create($apiContext);

    // Save booking details in the session
    $_SESSION['booking_details'] = [
        'futsal_id' => $_POST['futsal_id'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time'],
        'total_cost' => $_POST['total_cost'],
    ];

    session_write_close(); // Ensure session data is saved
    header("Location: " . $payment->getApprovalLink());
    exit();
} catch (Exception $e) {
    error_log("Error in PayPal Payment Creation: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>

