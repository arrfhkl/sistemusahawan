<?php
/**
 * ToyyibPay Configuration File
 * Dapatkan credentials dari https://toyyibpay.com/
 */

// ToyyibPay API Settings
define('TOYYIBPAY_SECRET_KEY', 'b7phs19p-gnh0-hodl-byz1-q9ig8h2no7td'); // Dapatkan dari ToyyibPay Dashboard
define('TOYYIBPAY_CATEGORY_CODE', 'd3dip0r6'); // Dapatkan dari ToyyibPay Dashboard
define('TOYYIBPAY_API_URL', 'https://toyyibpay.com/index.php/api/'); // Production URL
// define('TOYYIBPAY_API_URL', 'https://dev.toyyibpay.com/index.php/api/'); // Sandbox URL untuk testing

// Website Settings
define('SITE_URL', 'http://localhost/sups'); // Tukar kepada URL website anda
define('RETURN_URL', SITE_URL . '/payment_return.php'); // Return URL selepas payment
define('CALLBACK_URL', SITE_URL . '/payment_callback.php'); // Callback URL untuk notification

/**
 * Function to create ToyyibPay bill
 * @param array $billData - Data untuk create bill
 * @return array - Response dari ToyyibPay API
 */
function createToyyibPayBill($billData) {
    $url = TOYYIBPAY_API_URL . 'createBill';
    
    $data = array(
        'userSecretKey' => TOYYIBPAY_SECRET_KEY,
        'categoryCode' => TOYYIBPAY_CATEGORY_CODE,
        'billName' => $billData['billName'],
        'billDescription' => $billData['billDescription'],
        'billPriceSetting' => 1, // 1 = Fixed price
        'billPayorInfo' => 1, // 1 = Required customer info
        'billAmount' => $billData['billAmount'] * 100, // Convert to cents
        'billReturnUrl' => RETURN_URL,
        'billCallbackUrl' => CALLBACK_URL,
        'billExternalReferenceNo' => $billData['orderNo'],
        'billTo' => $billData['customerName'],
        'billEmail' => $billData['customerEmail'],
        'billPhone' => $billData['customerPhone'],
        'billSplitPayment' => 0,
        'billSplitPaymentArgs' => '',
        'billPaymentChannel' => '0', // 0 = FPX, 1 = Credit Card, 2 = Both
        'billContentEmail' => 'Terima kasih atas pembelian anda di Sistem Usahawan Pahang.',
        'billChargeToCustomer' => 1 // 1 = Charge to customer, 2 = Charge to merchant
    );
    
    $response = callToyyibPayAPI($url, $data);
    return $response;
}

/**
 * Function to get bill transactions
 * @param string $billCode - Bill code from ToyyibPay
 * @return array - Bill transactions
 */
function getToyyibPayBillTransactions($billCode) {
    $url = TOYYIBPAY_API_URL . 'getBillTransactions';
    
    $data = array(
        'billCode' => $billCode
    );
    
    $response = callToyyibPayAPI($url, $data);
    return $response;
}

/**
 * Function to call ToyyibPay API
 * @param string $url - API endpoint URL
 * @param array $data - Data to send
 * @return array - API response
 */
function callToyyibPayAPI($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    
    if ($result === false) {
        return array(
            'success' => false,
            'error' => 'Failed to connect to payment gateway'
        );
    }
    
    $response = json_decode($result, true);
    
    // Check if response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array(
            'success' => false,
            'error' => 'Invalid response from payment gateway'
        );
    }
    
    return array(
        'success' => true,
        'data' => $response
    );
}

/**
 * Generate unique order number
 * @return string
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Verify ToyyibPay callback signature (if implemented)
 * @param array $data - Callback data
 * @param string $signature - Signature from ToyyibPay
 * @return bool
 */
function verifyToyyibPaySignature($data, $signature) {
    // ToyyibPay currently doesn't use signature verification
    // This function is for future implementation
    return true;
}
?>