<?php

/**
 * 04_validators_sync.php
 * ÉTAPE 4: Synchroniser les validateurs avec la blockchain
 * 
 * Cette étape:
 * - Parcourt tous les blocs de la blockchain
 * - Extrait les blocs validator_registration et genesis_allocation
 * - Crée/met à jour les validateurs
 * - Met à jour les collaterals depuis les allocations genesis
 * - Approuve les validateurs enregistrés
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Validator\ValidatorManager;
use App\Lib\Logger;
use PDO;

class InitValidatorsSync
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 4: Synchronisation des Validateurs avec la Blockchain ===');

        try {
            $db = Database::getInstance()->getConnection();

            // Récupérer tous les blocs
            $blocksSql = "SELECT * FROM blocks ORDER BY index_num ASC";
            $blocksStmt = $db->query($blocksSql);
            $blocks = $blocksStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($blocks)) {
                Logger::warning('No blocks found in blockchain');
                return;
            }

            Logger::info('Processing blocks for validator registrations', ['count' => count($blocks)]);

            $validatorCount = 0;
            $validatorsData = [];
            $creatorPublicKey = null;

            // Récupérer la clé publique du créateur depuis le bloc genesis
            foreach ($blocks as $block) {
                if ($block['index_num'] === 0) {
                    $genesisData = json_decode($block['data'], true);
                    if (is_array($genesisData) && isset($genesisData[0]['public_key'])) {
                        $creatorPublicKey = $genesisData[0]['public_key'];
                        Logger::info('Creator public key loaded from genesis block', [
                            'creator' => substr($creatorPublicKey, 0, 20) . '...'
                        ]);
                    }
                    break;
                }
            }

            // Traiter chaque bloc
            foreach ($blocks as $block) {
                $blockData = json_decode($block['data'], true);

                if (is_array($blockData)) {
                    foreach ($blockData as $transaction) {
                        // Traiter les enregistrements de validateurs
                        if ($transaction['type'] === 'validator_registration' && isset($transaction['public_key'])) {
                            $publicKey = $transaction['public_key'];
                            $ip = $transaction['ip'] ?? '127.0.0.1';
                            $collateral = $transaction['collateral'] ?? 10000;
                            $isApproved = $transaction['is_approved'] ?? 0;

                            if (!isset($validatorsData[$publicKey])) {
                                $validatorsData[$publicKey] = [
                                    'public_key' => $publicKey,
                                    'ip' => $ip,
                                    'collateral' => $collateral,
                                    'is_approved' => $isApproved
                                ];
                                $validatorCount++;

                                Logger::info('Validator registration found', [
                                    'public_key' => substr($publicKey, 0, 20) . '...',
                                    'ip' => $ip,
                                    'collateral' => $collateral
                                ]);
                            }
                        }

                        // Mettre à jour les collaterals depuis les blocs genesis_allocation
                        if ($transaction['type'] === 'genesis_allocation' && isset($transaction['allocations'])) {
                            // Vérifier que c'est le créateur qui fait cette allocation
                            if ($transaction['public_key'] === $creatorPublicKey) {
                                foreach ($transaction['allocations'] as $allocation) {
                                    $recipient = $allocation['recipient'];
                                    
                                    // Si le recipient est un validateur, mettre à jour le collateral
                                    if (ValidatorManager::validatorExists($recipient)) {
                                        $collateralAmount = $allocation['amount'] ?? 10000;
                                        
                                        if (isset($validatorsData[$recipient])) {
                                            $validatorsData[$recipient]['collateral'] = $collateralAmount;
                                            Logger::info('Validator collateral updated from creator genesis block', [
                                                'public_key' => substr($recipient, 0, 20) . '...',
                                                'collateral' => $collateralAmount,
                                                'creator' => substr($creatorPublicKey, 0, 20) . '...'
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Créer/mettre à jour les validateurs
            Logger::info('Syncing validators to database', ['count' => count($validatorsData)]);

            foreach ($validatorsData as $validatorData) {
                $publicKey = $validatorData['public_key'];
                $ip = $validatorData['ip'] ?? '127.0.0.1';
                $collateral = $validatorData['collateral'];
                $isApproved = $validatorData['is_approved'];

                // Vérifier si le validateur existe
                $checkSql = "SELECT id FROM validators WHERE public_key = :public_key LIMIT 1";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([':public_key' => $publicKey]);
                $existingValidator = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingValidator) {
                    // Mettre à jour le validateur
                    $updateSql = "UPDATE validators SET ip = :ip, collateral = :collateral, is_approved = :is_approved WHERE public_key = :public_key";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->execute([
                        ':ip' => $ip,
                        ':collateral' => $collateral,
                        ':is_approved' => $isApproved,
                        ':public_key' => $publicKey
                    ]);
                    Logger::info('Validator updated', [
                        'public_key' => substr($publicKey, 0, 20) . '...',
                        'ip' => $ip,
                        'collateral' => $collateral,
                        'approved' => $isApproved === 1
                    ]);
                } else {
                    // Créer un nouveau validateur
                    $insertSql = "INSERT INTO validators (public_key, ip, collateral, status, is_approved) 
                                  VALUES (:public_key, :ip, :collateral, 'active', :is_approved)";
                    $insertStmt = $db->prepare($insertSql);
                    $insertStmt->execute([
                        ':public_key' => $publicKey,
                        ':ip' => $ip,
                        ':collateral' => $collateral,
                        ':is_approved' => $isApproved
                    ]);

                    Logger::info('Validator created', [
                        'public_key' => substr($publicKey, 0, 20) . '...',
                        'ip' => $ip,
                        'collateral' => $collateral,
                        'approved' => $isApproved === 1
                    ]);
                }
            }

            Logger::success('Validators synchronized successfully', [
                'total_validators' => count($validatorsData),
                'registrations_processed' => $validatorCount
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to synchronize validators', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
