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

            // Créer le bloc de validation - enregistrement du validateur
            $validatorRegistrationData = [
                'type' => 'validator_registration',
                'description' => 'Creator registered as validator',
                'public_key' => $publicKey,
                'collateral' => 10000,
                'is_approved' => 1
            ];

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
            $validator = ValidatorManager::registerValidator($publicKey);
            if ($validator) {
                // Auto-approuver le validateur créateur
                $validator->approve();
                Logger::success('Creator registered as approved validator', [
                    'publicKey' => substr($publicKey, 0, 20) . '...',
                    'collateral' => 10000
                ]);
            } else {
                throw new \Exception('Failed to register validator');
            }

        } catch (\Exception $e) {
            Logger::error('Failed to initialize validators', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
