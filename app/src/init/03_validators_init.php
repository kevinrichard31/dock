<?php

/**
 * 03_validators_init.php
 * ÉTAPE 3: Enregistrer le créateur comme validateur
 * 
 * Cette étape:
 * - Charge la clé publique du créateur (système)
 * - Crée un bloc de validation (validator_registration)
 * - Enregistre le créateur comme validateur avec collateral fixe (10000)
 * - Approuve automatiquement le validateur (auto-validation)
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Block\Block;
use App\Modules\Validator\ValidatorManager;
use App\Lib\Logger;
use App\Lib\Crypto;
use PDO;

class InitValidators
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 3: Initialisation du Validateur Créateur ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Vérifier si les validateurs ont déjà été initialisés
            $validatorsSql = "SELECT COUNT(*) as count FROM validators";
            $validatorsCount = $db->query($validatorsSql)->fetch()['count'];

            if ($validatorsCount && $validatorsCount > 0) {
                Logger::warning('Validators already initialized', ['count' => $validatorsCount]);
                return;
            }

            // Charger les clés du système
            $keysPath = __DIR__ . '/../../keys';
            $keys = Crypto::loadKeys($keysPath);

            if (empty($keys) || !isset($keys['public'])) {
                throw new \Exception('System keys not found. Run blocks initialization first.');
            }

            $publicKey = $keys['public'];
            Logger::info('Loading system public key', ['key' => substr($publicKey, 0, 20) . '...']);

            // Récupérer le dernier bloc pour avoir son hash
            $lastBlockSql = "SELECT * FROM blocks ORDER BY index_num DESC LIMIT 1";
            $lastBlockStmt = $db->query($lastBlockSql);
            $lastBlockRecord = $lastBlockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lastBlockRecord) {
                throw new \Exception('No blocks found. Initialize blocks first.');
            }

            $lastBlockHash = $lastBlockRecord['hash'];
            $nextBlockIndex = $lastBlockRecord['index_num'] + 1;

            // Get validator IP address
            $validatorIp = self::myPublicIp();

            // Créer le bloc de validation - enregistrement du validateur
            $validatorRegistrationData = [
                'type' => 'validator_registration',
                'description' => 'Creator registered as validator',
                'public_key' => $publicKey,
                'ip' => $validatorIp,
                'collateral' => 10000,
                'is_approved' => 1
            ];

            // Create signature for the registration data (excluding signature itself)
            $dataToSign = json_encode([
                'type' => $validatorRegistrationData['type'],
                'public_key' => $validatorRegistrationData['public_key'],
                'ip' => $validatorRegistrationData['ip'],
                'collateral' => $validatorRegistrationData['collateral']
            ]);
            
            $signature = Crypto::sign($dataToSign, $keys['private']);
            $validatorRegistrationData['signature'] = $signature;

            $validatorBlock = new Block(
                $nextBlockIndex,
                $lastBlockHash,
                [$validatorRegistrationData],
                $publicKey  // Le validateur crée son propre bloc d'enregistrement
            );

            // Sauvegarder le bloc
            $validatorBlock->save();

            Logger::info('Validator registration block created', [
                'index' => $nextBlockIndex,
                'hash' => $validatorBlock->getHash(),
                'publicKey' => substr($publicKey, 0, 20) . '...'
            ]);

            // Enregistrer le validateur dans la base de données
            $validatorSql = "INSERT INTO validators (public_key, ip, collateral, status, is_approved) 
                             VALUES (:public_key, :ip, :collateral, 'active', :is_approved)
                             ON DUPLICATE KEY UPDATE ip = VALUES(ip), collateral = VALUES(collateral), is_approved = VALUES(is_approved)";
            $validatorStmt = $db->prepare($validatorSql);
            $validatorStmt->execute([
                ':public_key' => $publicKey,
                ':ip' => $validatorIp,
                ':collateral' => 10000,
                ':is_approved' => 1
            ]);
            
            Logger::success('Creator registered as approved validator', [
                'publicKey' => substr($publicKey, 0, 20) . '...',
                'ip' => $validatorIp,
                'collateral' => 10000
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize validators', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
