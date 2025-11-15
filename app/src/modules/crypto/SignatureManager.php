<?php

namespace App\Modules\Crypto;

use App\Lib\Logger;

/**
 * Manager for signature verification and creation
 * Handles validator registration signatures and general signature operations
 * Supports different signature types for future extensibility
 */
class SignatureManager
{
    public const SIGNATURE_TYPE_REGISTRATION = 'registration';
    public const SIGNATURE_TYPE_TRANSACTION = 'transaction';
    public const SIGNATURE_TYPE_BLOCK = 'block';

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
        $dataToSign = self::getCanonicalRegistrationData($registrationData);
        
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
            Logger::debug('verifyRegistration: missing signature or public_key');
            return false;
        }

        try {
            Logger::debug('verifyRegistration: starting verification');
            $signature = $registrationData['signature'];
            $publicKey = $registrationData['public_key'];

            Logger::debug('verifyRegistration: getting canonical data');
            // Create canonical data to verify (exclude signature and description)
            $dataToVerify = self::getCanonicalRegistrationData($registrationData);
            Logger::debug('verifyRegistration: canonical data obtained');

            Logger::debug('verifyRegistration: calling Crypto::verifySignature');
            // Verify the signature
            $isValid = Crypto::verifySignature($dataToVerify, $signature, $publicKey);
            Logger::debug('verifyRegistration: verification result', ['valid' => $isValid]);
            return $isValid;
        } catch (\Exception $e) {
            Logger::debug('verifyRegistration: exception caught', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Sign a transaction
     * 
     * @param array $transactionData The transaction data (without signature)
     * @param string $privateKey The signer's private key
     * @return array The transaction data with signature added
     */
    public static function signTransaction(array $transactionData, string $privateKey): array
    {
        $dataToSign = self::getCanonicalTransactionData($transactionData);
        $signature = Crypto::sign($dataToSign, $privateKey);
        $transactionData['signature'] = $signature;
        
        return $transactionData;
    }

    /**
     * Verify a transaction signature
     * 
     * @param array $transactionData The transaction data (with signature)
     * @return bool True if signature is valid
     */
    public static function verifyTransaction(array $transactionData): bool
    {
        if (empty($transactionData['signature']) || empty($transactionData['public_key'])) {
            Logger::debug('verifyTransaction: missing signature or public_key');
            return false;
        }

        try {
            $signature = $transactionData['signature'];
            $publicKey = $transactionData['public_key'];
            $dataToVerify = self::getCanonicalTransactionData($transactionData);
            $isValid = Crypto::verifySignature($dataToVerify, $signature, $publicKey);
            
            return $isValid;
        } catch (\Exception $e) {
            Logger::debug('verifyTransaction: exception caught', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Sign a block
     * 
     * @param array $blockData The block data (without signature)
     * @param string $privateKey The signer's private key
     * @return array The block data with signature added
     */
    public static function signBlock(array $blockData, string $privateKey): array
    {
        $dataToSign = self::getCanonicalBlockData($blockData);
        $signature = Crypto::sign($dataToSign, $privateKey);
        $blockData['signature'] = $signature;
        
        return $blockData;
    }

    /**
     * Verify a block signature
     * 
     * @param array $blockData The block data (with signature)
     * @return bool True if signature is valid
     */
    public static function verifyBlock(array $blockData): bool
    {
        if (empty($blockData['signature']) || empty($blockData['validator_public_key'])) {
            Logger::debug('verifyBlock: missing signature or validator_public_key');
            return false;
        }

        try {
            $signature = $blockData['signature'];
            $publicKey = $blockData['validator_public_key'];
            $dataToVerify = self::getCanonicalBlockData($blockData);
            $isValid = Crypto::verifySignature($dataToVerify, $signature, $publicKey);
            
            return $isValid;
        } catch (\Exception $e) {
            Logger::debug('verifyBlock: exception caught', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get canonical JSON representation of registration data for signing
     * Ensures consistent ordering and excludes non-essential fields
     */
    private static function getCanonicalRegistrationData(array $registrationData): string
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
     * Get canonical JSON representation of transaction data for signing
     */
    private static function getCanonicalTransactionData(array $transactionData): string
    {
        $canonical = [
            'from' => $transactionData['from'] ?? '',
            'to' => $transactionData['to'] ?? '',
            'amount' => $transactionData['amount'] ?? 0,
            'nonce' => $transactionData['nonce'] ?? 0,
            'timestamp' => $transactionData['timestamp'] ?? 0
        ];

        return json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get canonical JSON representation of block data for signing
     */
    private static function getCanonicalBlockData(array $blockData): string
    {
        $canonical = [
            'index' => $blockData['index'] ?? 0,
            'timestamp' => $blockData['timestamp'] ?? 0,
            'previous_hash' => $blockData['previous_hash'] ?? '',
            'merkle_root' => $blockData['merkle_root'] ?? '',
            'nonce' => $blockData['nonce'] ?? 0
        ];

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
            $registrationData['ip_address'] = $ipAddress ?? self::getPublicIp();
        }

        // Sign the registration
        return self::signRegistration($registrationData, $privateKey);
    }

    /**
     * Get validator IP address
     * Returns the public IPv4 address visible on the network
     */
    private static function getPublicIp(): string
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
