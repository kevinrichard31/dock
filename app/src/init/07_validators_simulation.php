<?php

/**
 * 07_validators_simulation.php
 * ÉTAPE 7: Ajouter un validateur de simulation
 * 
 * Cette étape:
 * - Charge les clés du compte simulation
 * - Crée une requête d'enregistrement de validateur signée
 * - Enregistre le validateur dans la base de données
 * - Ajoute un bloc pour enregistrer cette action
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Block\Block;
use App\Modules\Validator\ValidatorManager;
use App\Lib\Logger;
use App\Lib\Crypto;
use App\Lib\ValidatorSignatureHelper;
use PDO;

class InitValidatorsSimulation
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 7: Ajout d\'un Validateur de Simulation ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Vérifier si le validateur simulation existe déjà
            $simulationKeysPath = __DIR__ . '/../../keys/simulation';
            $simulationKeys = Crypto::loadKeys($simulationKeysPath);

            if (empty($simulationKeys) || !isset($simulationKeys['public'])) {
                Logger::warning('Simulation keys not found. Skipping simulation validator registration.');
                return;
            }

            $simulationPublicKey = $simulationKeys['public'];

            // Vérifier si ce validateur existe déjà
            if (ValidatorManager::validatorExists($simulationPublicKey)) {
                Logger::warning('Simulation validator already registered', [
                    'public_key' => substr($simulationPublicKey, 0, 20) . '...'
                ]);
                return;
            }

            Logger::info('Registering simulation validator', [
                'public_key' => substr($simulationPublicKey, 0, 20) . '...'
            ]);

            // Récupérer le dernier bloc
            $lastBlockSql = "SELECT * FROM blocks ORDER BY index_num DESC LIMIT 1";
            $lastBlockStmt = $db->query($lastBlockSql);
            $lastBlockRecord = $lastBlockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lastBlockRecord) {
                throw new \Exception('No blocks found. Initialize blocks first.');
            }

            $lastBlockHash = $lastBlockRecord['hash'];
            $nextBlockIndex = $lastBlockRecord['index_num'] + 1;

            // Obtenir l'IP du validateur simulation
            $simulationIp = self::myPublicIp();

            // Créer les données d'enregistrement du validateur
            $validatorRegistrationData = [
                'type' => 'validator_registration',
                'description' => 'Simulation account registered as validator',
                'public_key' => $simulationPublicKey,
                'ip_address' => $simulationIp,
                'collateral' => 10000,
                'is_approved' => 0  // En attente d'approbation
            ];

            // Signer les données d'enregistrement
            $dataToSign = json_encode([
                'type' => $validatorRegistrationData['type'],
                'public_key' => $validatorRegistrationData['public_key'],
                'ip_address' => $validatorRegistrationData['ip_address'],
                'collateral' => $validatorRegistrationData['collateral']
            ]);
            
            $signature = Crypto::sign($dataToSign, $simulationKeys['private']);
            $validatorRegistrationData['signature'] = $signature;

            Logger::info('Validator registration data signed', [
                'public_key' => substr($simulationPublicKey, 0, 20) . '...',
                'signature' => substr($signature, 0, 20) . '...'
            ]);

            // Vérifier la signature
            Logger::info('Verifying signature...');
            $isValid = false;
            
            try {
                $isValid = Crypto::verifySignature($dataToSign, $signature, $simulationPublicKey);
                Logger::info('Signature verification result', ['valid' => $isValid]);
                
                if (!$isValid) {
                    throw new \Exception('Signature verification failed for simulation validator');
                }
                
                Logger::success('Signature verified successfully');
            } catch (\Exception $e) {
                Logger::error('Signature verification exception', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            // Créer un bloc pour enregistrer ce validateur
            $validatorBlock = new Block(
                $nextBlockIndex,
                $lastBlockHash,
                [$validatorRegistrationData],
                $simulationPublicKey  // Le validateur crée son propre bloc d'enregistrement
            );

            // Sauvegarder le bloc
            $validatorBlock->save();

            Logger::info('Simulation validator registration block created', [
                'index' => $nextBlockIndex,
                'hash' => $validatorBlock->getHash(),
                'public_key' => substr($simulationPublicKey, 0, 20) . '...'
            ]);

            // Enregistrer le validateur dans la base de données
            $validatorSql = "INSERT INTO validators (public_key, ip_address, collateral, is_approved) 
                             VALUES (:public_key, :ip_address, :collateral, :is_approved)
                             ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), collateral = VALUES(collateral), is_approved = VALUES(is_approved)";
            $validatorStmt = $db->prepare($validatorSql);
            $validatorStmt->execute([
                ':public_key' => $simulationPublicKey,
                ':ip_address' => $simulationIp,
                ':collateral' => 10000,
                ':is_approved' => 0
            ]);

            Logger::success('Simulation validator registered (pending approval)', [
                'public_key' => substr($simulationPublicKey, 0, 20) . '...',
                'ip_address' => $simulationIp,
                'collateral' => 10000,
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to register simulation validator', [
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
