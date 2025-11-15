<?php

namespace App\Lib;

/**
 * Helper class for validator registration signature verification
 * Ensures validator registrations are properly signed by their private key
 */
class ValidatorSignatureHelper
{
    /**
     * Sign a validator registration
     * 
     * @param array $registrationData The registration data (without signature)
     * @param string $privateKey The validator's private key
     * @return array The registration data with signature added
     */
    public static function signRegistration(array $registrationData, string $privateKey): array
    {
        // Create canonical data to sign (exclude signature and description)
        $dataToSign = self::getCanonicalData($registrationData);
        
        // Sign the canonical data
        $signature = Crypto::sign($dataToSign, $privateKey);
        $registrationData['signature'] = $signature;
        
        return $registrationData;
    }

    /**
     * Verify a validator registration signature
     * 
     * @param array $registrationData The registration data (with signature)
     * @return bool True if signature is valid
     */
    public static function verifyRegistration(array $registrationData): bool
    {
        if (empty($registrationData['signature']) || empty($registrationData['public_key'])) {
            return false;
        }

        $signature = $registrationData['signature'];
        $publicKey = $registrationData['public_key'];

        // Create canonical data to verify (exclude signature and description)
        $dataToVerify = self::getCanonicalData($registrationData);

        // Verify the signature
        return Crypto::verifySignature($dataToVerify, $signature, $publicKey);
    }

    /**
     * Get canonical JSON representation of registration data for signing
     * Ensures consistent ordering and excludes non-essential fields
     */
    private static function getCanonicalData(array $registrationData): string
    {
        // Extract only essential fields in specific order
        $canonical = [
            'type' => $registrationData['type'] ?? '',
            'public_key' => $registrationData['public_key'] ?? '',
            'ip_address' => $registrationData['ip_address'] ?? '',
            'collateral' => $registrationData['collateral'] ?? 0
        ];

        // Return JSON with no spaces and sorted keys for deterministic output
        return json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Add validator IP and signature to registration data
     * Convenience method that combines IP retrieval and signing
     */
    public static function prepareRegistration(array $baseData, string $privateKey, string $ipAddress = null): array
    {
        $registrationData = $baseData;

        // Add IP if not present
        if (!isset($registrationData['ip_address'])) {
            $registrationData['ip_address'] = $ipAddress ?? self::myPublicIp();
        }

        // Sign the registration
        return self::signRegistration($registrationData, $privateKey);
    }

    /**
     * Get validator IP address
     * Returns the public IPv4 address visible on the network
     */
    private static function myPublicIp(): string
    {
        // Try to get public IP from ipify API
        try {
            $ip = @file_get_contents('https://api.ipify.org/');
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return trim($ip);
            }
        } catch (\Exception $e) {
            // API call failed, continue to fallback
        }

        // Fallback to localhost
        return '127.0.0.1';
    }
}
