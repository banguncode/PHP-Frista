# PHPFrista

**PHPFrista** is a PHP library to integrate with **BPJS Kesehatan FRISTA** (Facial Recognition and Biometric Registration API).  
It helps hospitals and clinics connect to BPJS FRISTA endpoints for **authentication**, **facial verification**, and **biometric registration**.

---

## Features

- **Authentication** using BPJS VClaim username & password.
- **Facial data verification** using a 128-number face encoding array.
- **Biometric registration** from either:
  - JPEG file path, or
  - JPEG image as Base64 string.
- Input validation for:
  - Identity number (13 or 16 digits only).
  - Encoding format (array of 128 decimal numbers).
  - Image format (JPEG only).
- cURL-based API requests with JSON and multipart/form-data support.

---

## Installation

You can install this library via Composer:

```bash
composer require banguncode/php-frista
```

## Requirements
- PHP >= 5.5
- PHP extensions:
  - curl
  - fileinfo
- Internet connection to access BPJS FRISTA endpoints.

## Directory Structure
```bash
project/
├── src/
│   ├── FacialRecognition.php
│   ├── StatusCode.php
├── tests/
│   └── FacialRecognitionTest.php
├── composer.json
├── .gitignore
└── README.md
```

## Usage
1. Authentication
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use PHPFrista\FacialRecognition;
use PHPFrista\StatusCode;

$frista = (new FacialRecognition())
    ->init('vclaim_username', 'vclaim_password');
```

- Automatically authenticates and stores the token.
- Uses default Base URL: https://frista.bpjs-kesehatan.go.id
- Uses default API version: 3.0.2

1. Verify Facial Data
```php
$id = '1234567890123456'; // Identity number
$encoding = array_fill(0, 128, 0.1234); // Example encoding

$match = $frista->verify($id, $encoding);

if ($match['status'] === StatusCode::OK || $match['status'] === StatusCode::ALREADY_REGISTERED) {
    echo "✅ Verification successful";
} else {
    echo "❌ Failed: " . $match['message'];
}
```

2. Register Biometric Data

**Register facial recognition only if the verify method returns ```UNREGISTERED```**
- From a JPEG File
```php
$upload = $frista->register($id, '/path/to/photo.jpg', true);
```
- From a Base64 String
```php
$base64Image = base64_encode(file_get_contents('/path/to/photo.jpg'));
$upload = $frista->register($id, $base64Image, false);
```

## Appendix
| Status Code             | Description                          |
| ----------------------- | ------------------------------------ |
| `OK`                    | Success                              |
| `AUTH_FAILED`           | Authentication to BPJS server failed |
| `INVALID_ID`            | Identity number format invalid       |
| `INVALID_ENCODING`      | Face encoding format invalid         |
| `INVALID_IMAGE`         | Image format invalid         |
| `ALREADY_REGISTERED`    | Participant already registered today |
| `UNREGISTERED`          | Biometric not yet registered         |
| `INTEGRATION_ERROR`     | Error from BPJS integration          |
| `INTERNAL_SERVER_ERROR` | Internal server error                |
