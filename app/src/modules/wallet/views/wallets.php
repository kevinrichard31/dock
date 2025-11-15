<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallets - Portefeuilles</title>
    <style>
        :root {
            --color-white: #ffffff;
            --color-black: #000000;
            --color-gray-light: #f0f0f0;
            --color-gray-medium: #999999;
            --color-gray-dark: #333333;
            --color-border: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--color-gray-light);
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
            background: var(--color-white);
            padding: 20px;
            border: 1px solid var(--color-border);
        }

        .header h1 {
            color: var(--color-black);
            font-size: 2em;
        }

        .back-link {
            text-decoration: none;
            color: var(--color-black);
            font-weight: bold;
            padding: 10px 20px;
            border: 1px solid var(--color-black);
            background: var(--color-white);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: var(--color-black);
            color: var(--color-white);
        }

        .wallets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .wallet-card {
            background: var(--color-white);
            padding: 25px;
            border: 1px solid var(--color-border);
            border-left: 3px solid var(--color-black);
        }

        .wallet-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--color-border);
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
            color: var(--color-black);
        }

        .wallet-user-id {
            color: var(--color-gray-medium);
            font-size: 0.85em;
        }

        .wallet-balance {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--color-black);
            margin-bottom: 15px;
        }

        .wallet-balance-label {
            color: var(--color-gray-medium);
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .address-box {
            background: var(--color-gray-light);
            padding: 12px;
            margin-bottom: 12px;
            border-left: 2px solid var(--color-black);
        }

        .address-label {
            color: var(--color-gray-dark);
            font-size: 0.75em;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .address-value {
            color: var(--color-black);
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            word-break: break-all;
        }

        .wallet-stats {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--color-border);
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--color-black);
        }

        .stat-label {
            color: var(--color-gray-medium);
            font-size: 0.8em;
            margin-top: 5px;
        }

        .no-wallets {
            text-align: center;
            padding: 40px;
            background: var(--color-white);
            border: 1px solid var(--color-border);
            color: var(--color-gray-medium);
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
            <h1>Wallets</h1>
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
                            <div class="wallet-icon"></div>
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
