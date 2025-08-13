<?php

namespace PHPFrista;

class FacialRecognition
{
    private $username;
    private $password;
    private $baseUrl;
    private $version;
    private $token;

    public function __construct()
    {
        $this->baseUrl = "https://frista.bpjs-kesehatan.go.id";
        $this->version = "3.0.2";
    }

    /**
     * Set username and password for VClaim authentication
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function init($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->token = $this->auth();
        return $this;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = preg_replace('/\/+$/', '', $baseUrl);
        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Authenticate user biometrics
     *
     * @return string|null Returns token if authentication is successful, null otherwise
     */
    public function auth()
    {
        $data = array(
            'username' => $this->username,
            'password' => $this->password,
            'version'  => $this->version
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/frista-api/user/login/rs');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode == 200) {
            $response = json_decode($result, true);
            return isset($response['token']) ? $response['token'] : null;
        }

        return null;
    }

    /**
     * Verify facial data
     *
     * @param string $id Identity number (13 or 16 digits)
     * @param array $encoding Face encoding data (must be 128 decimal numbers)
     * @return array Verification result or error message
     */
    public function verify($id, $encoding)
    {
        if (!$this->token)
            return array(
                'status' => StatusCode::AUTH_FAILED,
                'message' => 'Gagal autentikasi ke server BPJS',
            );

        if (!isset($id) || !preg_match('/^\d{13}$|^\d{16}$/', $id)) {
            return array(
                'status' => StatusCode::INVALID_ID,
                'message' => 'Nomor identitas harus 13 atau 16 digit angka'
            );
        }
        if (
            !isset($encoding) ||
            !is_array($encoding) ||
            count($encoding) !== 128 ||
            array_filter($encoding, function ($v) {
                return !is_numeric($v) || !is_float($v + 0);
            })
        ) {
            return array(
                'status' => StatusCode::INVALID_ENCODING,
                'message' => 'Encoding harus berupa array berisi 128 angka desimal'
            );
        }

        $payload = array(
            'id'       => (string)$id,
            'encoding' => array_map('floatval', $encoding)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/frista-api/face/match2');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($result, true);

        if (isset($body['status'])) {
            $status = (bool)$body['status'];
            $code = isset($body['code']) ? (int)$body['code'] : null;
            $message = $body['message'] ?: 'Gagal terhubung ke server BPJS';

            if ($status) {
                return array(
                    'status' => StatusCode::OK,
                    'message' => 'Pengenalan wajah berhasil',
                );
            } else {
                if ($message && preg_match('/Peserta telah terdaftar hari ini/i', $message)) {
                    return array(
                        'status' => StatusCode::ALREADY_REGISTERED,
                        'message' => 'Peserta telah terdaftar hari ini',
                    );
                } else if ($code === 0) {
                    return array(
                        'status' => StatusCode::UNREGISTERED,
                        'message' => 'Fitur registerBiometrics belum diimplementasikan',
                    );
                } else {
                    return array(
                        'status' => StatusCode::INTEGRATION_ERROR,
                        'message' => $message,
                    );
                }
            }
        }

        return array(
            'status' => StatusCode::INTERNAL_SERVER_ERROR,
            'message' => 'Internal Server Error',
        );
    }


    /**
     * Register biometric data (face photo)
     *
     * @param string $id
     * @param string $file Base64 string or file path
     * @param bool $isFile True if $file is a file path, false if base64 string
     * @return array
     */
    public function register($id,  $file,  $isFile = true)
    {
        if (!$this->token)
            return array(
                'status' => StatusCode::AUTH_FAILED,
                'message' => 'Gagal autentikasi ke server BPJS',
            );

        if ($isFile) {
            if (!$this->isValidPath($file)) {
                return [
                    'status' => StatusCode::INVALID_ENCODING,
                    'message' => 'File path tidak valid atau bukan gambar JPEG'
                ];
            }
            $filePath = $file;
        } else {
            if (!$this->isBase64($file)) {
                return [
                    'status' => StatusCode::INVALID_ENCODING,
                    'message' => 'String base64 tidak valid atau bukan gambar JPEG'
                ];
            }
            $decodedData = base64_decode($file);
            $tempDir = sys_get_temp_dir();
            $filePath = tempnam($tempDir, 'face_') . '.jpg';
            file_put_contents($filePath, $decodedData);
        }

        // Prepare cURL for multipart/form-data
        $url = $this->baseUrl . '/frista-api/face/upload';
        $postFields = [
            'id' => $id,
            'file' => new \CURLFile($filePath, 'image/jpeg', 'photo.jpg')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$isFile && isset($filePath) && file_exists($filePath)) {
            @unlink($filePath);
        }

        $body = json_decode($result, true);

        if (isset($body['status'])) {
            $status = (bool)$body['status'];
            $code = isset($body['code']) ? (int)$body['code'] : null;
            $message = $body['message'] ?: 'Gagal terhubung ke server BPJS';

            if ($status) {
                return [
                    'status' => StatusCode::OK,
                    'message' => 'Pengenalan wajah berhasil'
                ];
            } else {
                return [
                    'status' => StatusCode::INTEGRATION_ERROR,
                    'message' => $message,
                ];
            }
        }

        return [
            'status' => StatusCode::INTERNAL_SERVER_ERROR,
            'message' => 'Gagal terhubung ke server BPJS'
        ];
    }


    /**
     * Check if a string is valid base64
     *
     * @param string $string
     * @return bool
     */
    private function isBase64($string)
    {
        $decoded = base64_decode($string, true);
        if ($decoded === false || base64_encode($decoded) !== $string) {
            return false;
        }
        // Check if decoded data is a JPEG image
        $finfo = finfo_open();
        $mime = finfo_buffer($finfo, $decoded, FILEINFO_MIME_TYPE);
        finfo_close($finfo);
        return in_array($mime, ['image/jpeg', 'image/jpg']);
    }

    /**
     * Check if a string is a valid file path
     *
     * @param string $path
     * @return bool
     */
    private function isValidPath($path)
    {
        if (!is_string($path) || !file_exists($path)) {
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return in_array($mime, ['image/jpeg', 'image/jpg']);
    }
}
