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
    $encoding = [0.123, 0.456];
    $file = __DIR__ . '/path/to/face.jpg';

    $matchResult = $frista->verify($id, $encoding);

    switch ($matchResult['status']) {
        case StatusCode::OK:
        case StatusCode::ALREADY_REGISTERED:
            echo $matchResult['message'] . "\n";
            break;

        case StatusCode::UNREGISTERED:
            $uploadResult = $frista->register($id, $file);
            echo "Register: " . $uploadResult['message'] . "\n";
            break;

        default:
            echo "Error: " . $matchResult['message'] . "\n";
            break;
    }
} catch (Exception $e) {
    echo "Test error: " . $e->getMessage() . "\n";
}
