<?php

namespace App\Modules\Crypto\API;

use App\Modules\Crypto\Crypto;
use App\Modules\Crypto\SignatureManager;

/**
 * API for cryptographic operations
 * Provides endpoints for key generation, hashing, signing, and verification
 */
class CryptoAPI
{
    /**
     * Generate new key pair
     */
    public static function generateKeyPair(): array
    {
        try {
            $keys = Crypto::generateKeys();
            return self::response('success', [
                'private_key' => $keys['private'],
                'public_key' => $keys['public']
            ], 'Key pair generated successfully');
        } catch (\Exception $e) {
            return self::error('Failed to generate key pair: ' . $e->getMessage());
        }
    }

    /**
     * Hash data using SHA-256
     */
    public static function hash(string $data): array
    {
        try {
            $hash = Crypto::hash($data);
            return self::response('success', [
                'data' => $data,
                'hash' => $hash,
                'algorithm' => 'SHA-256'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to hash data: ' . $e->getMessage());
        }
    }

    /**
     * Double hash data (Bitcoin-style)
     */
    public static function doubleHash(string $data): array
    {
        try {
            $hash = Crypto::doubleHash($data);
            return self::response('success', [
                'data' => $data,
                'hash' => $hash,
                'algorithm' => 'Double SHA-256'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to double hash data: ' . $e->getMessage());
        }
    }

    /**
     * Generate random hex string
     */
    public static function randomHex(int $length = 32): array
    {
        try {
            $hex = Crypto::randomHex($length);
            return self::response('success', [
                'value' => $hex,
                'length' => $length
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to generate random hex: ' . $e->getMessage());
        }
    }

    /**
     * Generate blockchain address
     */
    public static function generateAddress(string $data): array
    {
        try {
            $address = Crypto::generateAddress($data);
            return self::response('success', [
                'address' => $address,
                'prefix' => 'BCH_'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to generate address: ' . $e->getMessage());
        }
    }

    /**
     * Calculate merkle root from transactions
     */
    public static function calculateMerkleRoot(array $transactions): array
    {
        try {
            $merkleRoot = Crypto::calculateMerkleRoot($transactions);
            return self::response('success', [
                'merkle_root' => $merkleRoot,
                'transaction_count' => count($transactions)
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to calculate merkle root: ' . $e->getMessage());
        }
    }

    /**
     * Sign a message with private key
     */
    public static function signMessage(string $message, string $privateKey): array
    {
        try {
            $signature = Crypto::sign($message, $privateKey);
            return self::response('success', [
                'message' => $message,
                'signature' => $signature,
                'signature_type' => 'secp256k1'
            ], 'Message signed successfully');
        } catch (\Exception $e) {
            return self::error('Failed to sign message: ' . $e->getMessage());
        }
    }

    /**
     * Verify a signature
     */
    public static function verifySignature(string $message, string $signature, string $publicKey): array
    {
        try {
            $isValid = Crypto::verifySignature($message, $signature, $publicKey);
            return self::response('success', [
                'valid' => $isValid,
                'message' => $message,
                'signature_type' => 'secp256k1'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to verify signature: ' . $e->getMessage());
        }
    }

    /**
     * Verify validator registration signature
     */
    public static function verifyRegistration(array $registrationData): array
    {
        try {
            $isValid = SignatureManager::verifyRegistration($registrationData);
            return self::response('success', [
                'valid' => $isValid,
                'registration_type' => 'validator'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to verify registration: ' . $e->getMessage());
        }
    }

    /**
     * Verify transaction signature
     */
    public static function verifyTransaction(array $transactionData): array
    {
        try {
            $isValid = SignatureManager::verifyTransaction($transactionData);
            return self::response('success', [
                'valid' => $isValid,
                'signature_type' => 'transaction'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to verify transaction: ' . $e->getMessage());
        }
    }

    /**
     * Verify block signature
     */
    public static function verifyBlock(array $blockData): array
    {
        try {
            $isValid = SignatureManager::verifyBlock($blockData);
            return self::response('success', [
                'valid' => $isValid,
                'signature_type' => 'block'
            ]);
        } catch (\Exception $e) {
            return self::error('Failed to verify block: ' . $e->getMessage());
        }
    }

    /**
     * Format API response
     */
    public static function response(string $status, $data = null, string $message = null): array
    {
        $response = ['status' => $status];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }

    /**
     * Format error response
     */
    public static function error(string $message, int $code = 400): array
    {
        http_response_code($code);
        return self::response('error', null, $message);
    }
}
