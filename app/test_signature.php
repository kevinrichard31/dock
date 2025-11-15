<?php

/**
 * Test script to debug signature verification issue
 */

require_once __DIR__ . '/vendor/autoload.php';

use Elliptic\EC;
use App\Lib\Logger;
use App\Lib\Crypto;

Logger::info('=== Testing Signature Verification ===');

// Generate test keys
Logger::info('Generating test keys...');
$keys = Crypto::generateKeys();
Logger::info('Keys generated', [
    'private_length' => strlen($keys['private']),
    'public_length' => strlen($keys['public'])
]);

// Test message
$message = 'test message';
Logger::info('Test message', ['message' => $message]);

// Sign the message
Logger::info('Signing message...');
$signature = Crypto::sign($message, $keys['private']);
Logger::info('Message signed', [
    'signature_length' => strlen($signature),
    'signature' => substr($signature, 0, 20) . '...'
]);

// Try to verify - this is where it blocks
Logger::info('About to verify signature...');
Logger::info('Creating EC instance...');
$ec = new EC('secp256k1');
Logger::info('EC instance created');

Logger::info('Creating key from public...');
try {
    $key = $ec->keyFromPublic($keys['public'], 'hex');
    Logger::info('Key created successfully');
} catch (\Exception $e) {
    Logger::error('Error creating key from public', ['error' => $e->getMessage()]);
    exit(1);
}

Logger::info('Hashing message...');
$hash = Crypto::hash($message);
Logger::info('Message hashed', ['hash' => substr($hash, 0, 20) . '...']);

Logger::info('Splitting signature...');
$r = substr($signature, 0, 64);
$s = substr($signature, 64, 64);
Logger::info('Signature split', ['r_length' => strlen($r), 's_length' => strlen($s)]);

Logger::info('About to call $key->verify() with array... TESTING FIX');
try {
    // Pass r and s as array instead of creating signature object
    $result = $key->verify($hash, ['r' => $r, 's' => $s]);
    Logger::info('Verification complete!', ['result' => $result]);
} catch (\Exception $e) {
    Logger::error('Error during verification', ['error' => $e->getMessage()]);
    exit(1);
}

Logger::success('Test completed successfully!');
