<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain - Blocs</title>
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

        .blocks-list {
            display: grid;
            gap: 20px;
        }

        .block-card {
            background: var(--color-white);
            padding: 20px;
            border: 1px solid var(--color-border);
            border-left: 3px solid var(--color-black);
        }

        .block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--color-border);
        }

        .block-number {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--color-black);
        }

        .block-time {
            color: var(--color-gray-medium);
            font-size: 0.9em;
        }

        .block-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .detail-item {
            background: var(--color-gray-light);
            padding: 12px;
            border-left: 2px solid var(--color-black);
        }

        .detail-label {
            color: var(--color-gray-dark);
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            color: var(--color-black);
            font-size: 0.9em;
            word-break: break-all;
            font-family: 'Courier New', monospace;
        }

        .block-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--color-border);
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--color-black);
        }

        .stat-label {
            color: var(--color-gray-medium);
            font-size: 0.85em;
            margin-top: 5px;
        }

        .no-blocks {
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

            .block-details {
                grid-template-columns: 1fr;
            }

            .block-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Blockchain</h1>
            <a href="/" class="back-link">← Retour accueil</a>
        </div>

        <?php if (empty($data['blocks'])): ?>
            <div class="no-blocks">
                <p>Aucun bloc disponible. Initialisez la blockchain d'abord.</p>
            </div>
        <?php else: ?>
            <div class="blocks-list">
                <?php foreach ($data['blocks'] as $block): ?>
                    <div class="block-card">
                        <div class="block-header">
                            <div class="block-number">Bloc #<?php echo $block['index_num']; ?></div>
                            <div class="block-time"><?php echo date('d/m/Y H:i:s', $block['timestamp']); ?></div>
                        </div>

                        <div class="block-details">
                            <div class="detail-item">
                                <div class="detail-label">Hash</div>
                                <div class="detail-value"><?php echo $block['hash']; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Hash Précédent</div>
                                <div class="detail-value"><?php echo $block['previous_hash'] ?: 'N/A (Genesis)'; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Merkle Root</div>
                                <div class="detail-value"><?php echo $block['merkle_root'] ?: 'N/A'; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Validateur</div>
                                <div class="detail-value"><?php echo $block['validator_address'] ? substr($block['validator_address'], 0, 10) . '...' : 'Genesis'; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Transactions</div>
                                <div class="detail-value"><?php echo $block['transactions_count'] ?? 0; ?></div>
                            </div>
                        </div>

                        <div class="block-stats">
                            <div class="stat">
                                <div class="stat-value">Proof of Stake</div>
                                <div class="stat-label">Consensus</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo strlen($block['data'] ?? '') . ' bytes'; ?></div>
                                <div class="stat-label">Taille données</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>