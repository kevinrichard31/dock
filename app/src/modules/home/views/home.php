<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .module-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .module-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .module-icon {
            font-size: 3em;
            margin-bottom: 15px;
            text-align: center;
        }

        .module-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .module-description {
            color: #666;
            font-size: 0.95em;
            line-height: 1.5;
            flex-grow: 1;
        }

        .module-link {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            font-weight: bold;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .module-link:hover {
            color: #764ba2;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .footer p {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }

            .modules-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛓️ Blockchain Dashboard</h1>
            <p>Système blockchain décentralisé avec wallets et transactions</p>
        </div>

        <div class="modules-grid">
            <?php foreach ($data['modules'] as $module): ?>
                <a href="<?php echo $module['link']; ?>" class="module-card">
                    <div class="module-icon"><?php echo $module['icon']; ?></div>
                    <div class="module-name"><?php echo $module['name']; ?></div>
                    <div class="module-description"><?php echo $module['description']; ?></div>
                    <span class="module-link">Accéder →</span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <p>🚀 Système Blockchain v1.0 - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
