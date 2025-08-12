<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPFrista\FacialRecognition;
use PHPFrista\StatusCode;

$frista = new FacialRecognition();

defined("BPJS_VCLAIM_USERNAME") or define("BPJS_VCLAIM_USERNAME", "your_username");
defined("BPJS_VCLAIM_PASSWORD") or define("BPJS_VCLAIM_PASSWORD", "your_password");

try {
    $frista->init(BPJS_VCLAIM_USERNAME, BPJS_VCLAIM_PASSWORD);

    /** NIK / BPJS no */
    $id = "1234567890000000";
    $encoding = [
        -0.16429905593395233,
        0.18262381851673126,
        0.07881429046392441,
        // 125 more float values as descriptors
    ];

    // For testing, you can use a placeholder array of 128 zeros:
    // $encoding = array_fill(0, 128, 0.0);

    $matchResult = $frista->verify($id, $encoding);

    switch ($matchResult['status']) {
        case StatusCode::OK:
        case StatusCode::ALREADY_REGISTERED:
            echo $matchResult['message'] . "\n";
            break;

        case StatusCode::UNREGISTERED:
            // $file = __DIR__ . '/path/to/face.jpg';
            // $uploadResult = $frista->register($id, $file);
            // echo "Register: " . $uploadResult['message'] . "\n";
            break;

        default:
            echo "Error: " . $matchResult['message'] . "\n";
            break;
    }
} catch (Exception $e) {
    echo "Test error: " . $e->getMessage() . "\n";
}
