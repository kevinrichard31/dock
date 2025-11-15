<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallets - Portefeuilles</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            font-size: 2em;
        }

        .back-link {
            text-decoration: none;
            color: #667eea;
            font-weight: bold;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #667eea;
            color: white;
        }

        .wallets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .wallet-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-top: 5px solid #667eea;
            transition: transform 0.3s ease;
        }

        .wallet-card:hover {
            transform: translateY(-5px);
        }

        .wallet-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .wallet-icon {
            font-size: 2.5em;
            margin-right: 15px;
        }

        .wallet-title {
            flex: 1;
        }

        .wallet-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
        }

        .wallet-user-id {
            color: #999;
            font-size: 0.85em;
        }

        .wallet-balance {
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .wallet-balance-label {
            color: #999;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .address-box {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 12px;
            border-left: 3px solid #764ba2;
        }

        .address-label {
            color: #666;
            font-size: 0.75em;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .address-value {
            color: #333;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            word-break: break-all;
        }

        .wallet-stats {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #999;
            font-size: 0.8em;
            margin-top: 5px;
        }

        .no-wallets {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #999;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .wallets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Wallets</h1>
            <a href="/" class="back-link">← Retour accueil</a>
        </div>

        <?php if (empty($data['wallets'])): ?>
            <div class="no-wallets">
                <p>Aucun portefeuille disponible.</p>
            </div>
        <?php else: ?>
            <div class="wallets-grid">
                <?php foreach ($data['wallets'] as $wallet): ?>
                    <div class="wallet-card">
                        <div class="wallet-header">
                            <div class="wallet-icon">💳</div>
                            <div class="wallet-title">
                                <div class="wallet-name">Wallet #<?php echo $wallet['user_id']; ?></div>
                                <div class="wallet-user-id">User ID: <?php echo $wallet['user_id']; ?></div>
                            </div>
                        </div>

                        <div class="wallet-balance-label">Solde</div>
                        <div class="wallet-balance"><?php echo number_format($wallet['balance'], 2); ?></div>

                        <div class="address-box">
                            <div class="address-label">Clé Publique</div>
                            <div class="address-value"><?php echo $wallet['public_key']; ?></div>
                        </div>

                        <div class="wallet-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo strlen($wallet['public_key']); ?></div>
                                <div class="stat-label">Caractères</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">secp256k1</div>
                                <div class="stat-label">Algo</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
