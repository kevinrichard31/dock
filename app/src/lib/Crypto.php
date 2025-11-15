<?php

namespace App\Lib;

class Crypto
{
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
}
