<?php

/**
 * 01_5_simulation.php
 * ÉTAPE 1.5: Ajouter un bloc de simulation avec transactions de test
 * 
 * Cette étape s'exécute après le bloc Genesis et:
 * - Génère une nouvelle paire de clés pour le compte de simulation
 * - Crée 2 transactions du wallet genesis vers ce nouveau compte
 * - Sauvegarde les clés de simulation
 * - Crée un bloc avec ces transactions
 */

namespace App\Init;

use App\Config\Database;
use App\Modules\Block\Block;
use App\Modules\Block\BlockChain;
use App\Lib\Logger;
use App\Modules\Crypto\Crypto;
use App\Modules\Crypto\SignatureManager;
use PDO;

class InitSimulation
{
    public static function execute(): void
    {
        Logger::info('=== ÉTAPE 1.5: Initialisation de la Simulation ===');

        try {
            // Vérifier si le bloc de simulation existe déjà
            $db = Database::getInstance()->getConnection();
            $checkSql = "SELECT COUNT(*) as count FROM blocks WHERE index_num = 1";
            $blockCount = $db->query($checkSql)->fetch()['count'];

            if ($blockCount > 0) {
                Logger::warning('Simulation block already exists');
                return;
            }

            Logger::info('Creating simulation block with test transactions...');

            // Charger les clés du système (genesis wallet)
            $keysPath = __DIR__ . '/../../keys';
            $systemKeys = Crypto::loadKeys($keysPath);

            if (empty($systemKeys) || !isset($systemKeys['public'])) {
                throw new \Exception('System keys not found. Run blocks initialization first.');
            }

            $genesisPublicKey = $systemKeys['public'];
            $genesisPrivateKey = $systemKeys['private'];
            Logger::info('System (genesis) public key loaded', ['key' => substr($genesisPublicKey, 0, 20) . '...']);

            // Générer une nouvelle paire de clés pour le compte de simulation
            $simulationKeys = Crypto::generateKeys();
            $simulationPublicKey = $simulationKeys['public'];
            
            // Sauvegarder les clés de simulation
            $simulationKeysPath = __DIR__ . '/../../keys/simulation';
            Crypto::saveKeys($simulationKeys, $simulationKeysPath);
            
            Logger::success('Simulation keys generated and saved', [
                'public_key' => substr($simulationPublicKey, 0, 20) . '...',
                'path' => $simulationKeysPath
            ]);

            // Récupérer le bloc Genesis
            $genesisSql = "SELECT hash FROM blocks WHERE index_num = 0 LIMIT 1";
            $genesisStmt = $db->query($genesisSql);
            $genesisBlock = $genesisStmt->fetch(PDO::FETCH_ASSOC);

            if (!$genesisBlock) {
                throw new \Exception('Genesis block not found');
            }

            $genesisHash = $genesisBlock['hash'];

            // Créer les deux transactions de simulation
            // Transaction 1: Genesis envoie 100000 tokens à la simulation
            $transaction1 = [
                'type' => 'transaction',
                'from' => $genesisPublicKey,
                'to' => $simulationPublicKey,
                'amount' => 100000,
                'public_key' => $genesisPublicKey,
                'description' => 'First simulation transaction - Genesis sends to simulation account',
                'timestamp' => time()
            ];

            // Générer le hash de la transaction 1
            $transaction1['hash'] = self::hashTransaction(
                $transaction1['from'],
                $transaction1['to'],
                $transaction1['amount'],
                $transaction1['timestamp']
            );

            // Signer la transaction 1
            $signResult1 = Crypto::sign(
                json_encode([
                    'from' => $transaction1['from'],
                    'to' => $transaction1['to'],
                    'amount' => $transaction1['amount'],
                    'timestamp' => $transaction1['timestamp']
                ]),
                $genesisPrivateKey
            );
            $transaction1['signature'] = $signResult1;

            // Transaction 2: Genesis envoie 100000 tokens supplémentaires à la simulation
            $transaction2 = [
                'type' => 'transaction',
                'from' => $genesisPublicKey,
                'to' => $simulationPublicKey,
                'amount' => 100000,
                'public_key' => $genesisPublicKey,
                'description' => 'Second simulation transaction - Genesis sends more to simulation account',
                'timestamp' => time() + 1
            ];

            // Générer le hash de la transaction 2
            $transaction2['hash'] = self::hashTransaction(
                $transaction2['from'],
                $transaction2['to'],
                $transaction2['amount'],
                $transaction2['timestamp']
            );

            // Signer la transaction 2
            $signResult2 = Crypto::sign(
                json_encode([
                    'from' => $transaction2['from'],
                    'to' => $transaction2['to'],
                    'amount' => $transaction2['amount'],
                    'timestamp' => $transaction2['timestamp']
                ]),
                $genesisPrivateKey
            );
            $transaction2['signature'] = $signResult2;

            Logger::info('Simulation transactions created', [
                'transaction_1' => [
                    'from' => substr($transaction1['from'], 0, 20) . '...',
                    'to' => substr($transaction1['to'], 0, 20) . '...',
                    'amount' => $transaction1['amount'],
                    'hash' => substr($transaction1['hash'], 0, 20) . '...',
                    'signed' => !empty($transaction1['signature'])
                ],
                'transaction_2' => [
                    'from' => substr($transaction2['from'], 0, 20) . '...',
                    'to' => substr($transaction2['to'], 0, 20) . '...',
                    'amount' => $transaction2['amount'],
                    'hash' => substr($transaction2['hash'], 0, 20) . '...',
                    'signed' => !empty($transaction2['signature'])
                ]
            ]);

            // Créer le bloc de simulation avec les deux transactions
            $simulationBlock = new Block(
                1,  // Block index 1 (après Genesis)
                $genesisHash,
                [$transaction1, $transaction2],
                $genesisPublicKey  // Le genesis wallet crée ce bloc
            );

            // Sauvegarder le bloc
            $simulationBlock->save();

            Logger::success('Simulation block created successfully', [
                'index' => 1,
                'hash' => $simulationBlock->getHash(),
                'previous_hash' => $genesisHash,
                'transactions_count' => 2,
                'total_amount_transferred' => 200000
            ]);

            // Créer un wallet pour le compte de simulation
            $simulationUserName = 'Simulation_Account';
            $simulationUserEmail = substr($simulationPublicKey, 0, 20) . '@simulation.local';

            // Insérer l'utilisateur de simulation
            $userSql = "INSERT INTO users (name, email) VALUES (:name, :email)";
            $userStmt = $db->prepare($userSql);
            $userStmt->execute([
                ':name' => $simulationUserName,
                ':email' => $simulationUserEmail
            ]);
            $userId = $db->lastInsertId();

            // Insérer le wallet de simulation avec le solde initial de 200000
            $walletSql = "INSERT INTO wallets (user_id, public_key, private_key, balance) 
                          VALUES (:user_id, :public_key, :private_key, :balance)";
            $walletStmt = $db->prepare($walletSql);
            $walletStmt->execute([
                ':user_id' => $userId,
                ':public_key' => $simulationPublicKey,
                ':private_key' => $simulationKeys['private'],
                ':balance' => 200000
            ]);

            Logger::success('Simulation wallet created', [
                'user_id' => $userId,
                'public_key' => substr($simulationPublicKey, 0, 20) . '...',
                'balance' => 200000
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to initialize simulation', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Générer un hash pour une transaction
     */
    private static function hashTransaction(
        string $fromAddress,
        string $toAddress,
        float $amount,
        int $timestamp
    ): string {
        $data = $fromAddress . $toAddress . $amount . $timestamp;
        return hash('sha256', $data);
    }
}
