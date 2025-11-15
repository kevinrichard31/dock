<?php

namespace App\Modules\Crypto;

use Elliptic\EC;
use App\Lib\Logger;

class Crypto
{
    /**
     * Generate a new private/public key pair using secp256k1
     */
    public static function generateKeys(): array
    {
        $ec = new EC('secp256k1');
        $key = $ec->genKeyPair();
        
        $privateKey = $key->getPrivate('hex');
        $publicKeyUncompressed = $key->getPublic('hex');
        $publicKey = self::compressPublicKey($publicKeyUncompressed);

        return [
            'private' => $privateKey,
            'public' => $publicKey
        ];
    }

    /**
     * Compress a public key from uncompressed to compressed format
     * Uncompressed: 04 + X + Y (130 chars)
     * Compressed: 02/03 + X (66 chars)
     */
    public static function compressPublicKey(string $publicKeyUncompressed): string
    {
        if (strlen($publicKeyUncompressed) !== 130) {
            return $publicKeyUncompressed;
        }

        $x = substr($publicKeyUncompressed, 2, 64);
        $y = substr($publicKeyUncompressed, 66, 64);

        // Check if Y is even or odd
        $yLast = hexdec(substr($y, -1));
        $prefix = ($yLast % 2 === 0) ? '02' : '03';

        return $prefix . $x;
    }

    /**
     * Save keys to files
     */
    public static function saveKeys(array $keys, string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . '/private_key.hex', $keys['private']);
        file_put_contents($path . '/public_key.hex', $keys['public']);
    }

    /**
     * Load keys from files
     */
    public static function loadKeys(string $path): array
    {
        if (!is_dir($path) || !file_exists($path . '/private_key.hex') || !file_exists($path . '/public_key.hex')) {
            return [];
        }

        return [
            'private' => file_get_contents($path . '/private_key.hex'),
            'public' => file_get_contents($path . '/public_key.hex')
        ];
    }

    /**
     * Generate a SHA-256 hash
     */
    public static function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Generate a double SHA-256 hash (Bitcoin-style)
     */
    public static function doubleHash(string $data): string
    {
        return hash('sha256', hash('sha256', $data, true));
    }

    /**
     * Generate a random hex string
     */
    public static function randomHex(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a blockchain address from data
     * Format: BCH_ + first 40 characters of hash
     */
    public static function generateAddress(string $data): string
    {
        $hash = self::hash($data);
        return 'BCH_' . substr($hash, 0, 40);
    }

    /**
     * Verify a hash
     */
    public static function verifyHash(string $data, string $hash): bool
    {
        return hash_equals(self::hash($data), $hash);
    }

    /**
     * Generate merkle root from transactions
     */
    public static function calculateMerkleRoot(array $transactions): string
    {
        if (empty($transactions)) {
            return self::hash('');
        }

        $hashes = array_map(fn($tx) => self::hash(json_encode($tx)), $transactions);

        while (count($hashes) > 1) {
            $newHashes = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                $left = $hashes[$i];
                $right = $hashes[$i + 1] ?? $hashes[$i];
                $newHashes[] = self::hash($left . $right);
            }
            $hashes = $newHashes;
        }

        return $hashes[0];
    }

    /**
     * Sign a message with a private key using secp256k1
     * Returns signature as hex string (r and s concatenated)
     */
    public static function sign(string $message, string $privateKey): string
    {
        try {
            $ec = new EC('secp256k1');
            $key = $ec->keyFromPrivate($privateKey, 'hex');
            
            // Hash the message
            $hash = self::hash($message);
            
            // Sign the hash
            $signature = $key->sign($hash);
            
            // Get r and s components from signature object
            // The signature object has r and s properties (BN instances)
            $r = $signature->r->toString(16);
            $s = $signature->s->toString(16);
            
            // Pad to 64 chars each (256 bits)
            $r = str_pad($r, 64, '0', STR_PAD_LEFT);
            $s = str_pad($s, 64, '0', STR_PAD_LEFT);
            
            return $r . $s;
        } catch (\Exception $e) {
            throw new \Exception('Signature creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a signature with a public key
     * Signature should be concatenated r+s (128 hex chars)
     */
    public static function verifySignature(string $message, string $signature, string $publicKey): bool
    {
        try {
            // Split signature into r and s first
            if (strlen($signature) !== 128) {
                return false;
            }
            
            $r = substr($signature, 0, 64);
            $s = substr($signature, 64, 64);
            
            $ec = new EC('secp256k1');
            $key = $ec->keyFromPublic($publicKey, 'hex');
            
            // Hash the message
            $hash = self::hash($message);
            
            // Use the verify method with r and s as array - avoid using $ec->signature()
            // which appears to have a bug that causes infinite loop
            $result = $key->verify($hash, ['r' => $r, 's' => $s]);
            
            return $result;
        } catch (\Exception $e) {
            Logger::debug('verifySignature: exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
