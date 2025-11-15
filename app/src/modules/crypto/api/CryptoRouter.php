<?php

namespace App\Modules\Crypto\API;

/**
 * Router for cryptographic operations
 * Routes requests to appropriate crypto API endpoints
 */
class CryptoRouter
{
    private string $method;
    private string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * Route crypto endpoints
     */
    public function route(): array
    {
        // POST /api/crypto/keygen
        if ($this->method === 'POST' && $this->path === 'crypto/keygen') {
            return CryptoAPI::generateKeyPair();
        }

        // POST /api/crypto/hash
        if ($this->method === 'POST' && $this->path === 'crypto/hash') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = $input['data'] ?? '';
            
            if (empty($data)) {
                return CryptoAPI::error('Missing data parameter');
            }
            
            return CryptoAPI::hash($data);
        }

        // POST /api/crypto/hash/double
        if ($this->method === 'POST' && $this->path === 'crypto/hash/double') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = $input['data'] ?? '';
            
            if (empty($data)) {
                return CryptoAPI::error('Missing data parameter');
            }
            
            return CryptoAPI::doubleHash($data);
        }

        // POST /api/crypto/random
        if ($this->method === 'POST' && $this->path === 'crypto/random') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $length = intval($input['length'] ?? 32);
            
            return CryptoAPI::randomHex($length);
        }

        // POST /api/crypto/address
        if ($this->method === 'POST' && $this->path === 'crypto/address') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = $input['data'] ?? '';
            
            if (empty($data)) {
                return CryptoAPI::error('Missing data parameter');
            }
            
            return CryptoAPI::generateAddress($data);
        }

        // POST /api/crypto/merkle
        if ($this->method === 'POST' && $this->path === 'crypto/merkle') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $transactions = $input['transactions'] ?? [];
            
            if (empty($transactions)) {
                return CryptoAPI::error('Missing transactions parameter');
            }
            
            return CryptoAPI::calculateMerkleRoot($transactions);
        }

        // POST /api/crypto/sign
        if ($this->method === 'POST' && $this->path === 'crypto/sign') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $message = $input['message'] ?? '';
            $privateKey = $input['private_key'] ?? '';
            
            if (empty($message) || empty($privateKey)) {
                return CryptoAPI::error('Missing message or private_key parameter');
            }
            
            return CryptoAPI::signMessage($message, $privateKey);
        }

        // POST /api/crypto/verify
        if ($this->method === 'POST' && $this->path === 'crypto/verify') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $message = $input['message'] ?? '';
            $signature = $input['signature'] ?? '';
            $publicKey = $input['public_key'] ?? '';
            
            if (empty($message) || empty($signature) || empty($publicKey)) {
                return CryptoAPI::error('Missing message, signature, or public_key parameter');
            }
            
            return CryptoAPI::verifySignature($message, $signature, $publicKey);
        }

        // POST /api/crypto/verify/registration
        if ($this->method === 'POST' && $this->path === 'crypto/verify/registration') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($input)) {
                return CryptoAPI::error('Missing registration data');
            }
            
            return CryptoAPI::verifyRegistration($input);
        }

        // POST /api/crypto/verify/transaction
        if ($this->method === 'POST' && $this->path === 'crypto/verify/transaction') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($input)) {
                return CryptoAPI::error('Missing transaction data');
            }
            
            return CryptoAPI::verifyTransaction($input);
        }

        // POST /api/crypto/verify/block
        if ($this->method === 'POST' && $this->path === 'crypto/verify/block') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($input)) {
                return CryptoAPI::error('Missing block data');
            }
            
            return CryptoAPI::verifyBlock($input);
        }

        http_response_code(404);
        return CryptoAPI::error('Crypto endpoint not found', 404);
    }

    /**
     * Check if path matches crypto routes
     */
    public static function matches(string $path): bool
    {
        return strpos($path, 'crypto') === 0;
    }
}
