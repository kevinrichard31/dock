<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain - Blocs</title>
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

        .blocks-list {
            display: grid;
            gap: 20px;
        }

        .block-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }

        .block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .block-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }

        .block-time {
            color: #999;
            font-size: 0.9em;
        }

        .block-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .detail-item {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 5px;
            border-left: 3px solid #764ba2;
        }

        .detail-label {
            color: #666;
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #333;
            font-size: 0.9em;
            word-break: break-all;
            font-family: 'Courier New', monospace;
        }

        .block-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #999;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .no-blocks {
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
            <h1>⛓️ Blockchain</h1>
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
                                <div class="detail-label">Nonce</div>
                                <div class="detail-value"><?php echo $block['nonce']; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Difficulté</div>
                                <div class="detail-value"><?php echo $block['difficulty']; ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Transactions</div>
                                <div class="detail-value"><?php echo $block['transactions_count'] ?? 0; ?></div>
                            </div>
                        </div>

                        <div class="block-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo $block['nonce']; ?></div>
                                <div class="stat-label">Iterations PoW</div>
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
