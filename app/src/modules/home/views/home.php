<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?></title>
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
            background: var(--color-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-top: 3px solid var(--color-black);
        }

        .container {
            max-width: 1200px;
            width: 100%;
        }

        .header {
            text-align: center;
            color: var(--color-black);
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2em;
            color: var(--color-gray-medium);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .module-card {
            background: var(--color-white);
            padding: 30px;
            border: 1px solid var(--color-border);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border-left: 3px solid var(--color-black);
        }

        .module-card:hover {
            border-left: 3px solid var(--color-black);
            background: var(--color-gray-light);
        }

        .module-icon {
            font-size: 3em;
            margin-bottom: 15px;
            text-align: center;
        }

        .module-name {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--color-black);
            margin-bottom: 10px;
        }

        .module-description {
            color: var(--color-gray-medium);
            font-size: 0.95em;
            line-height: 1.5;
            flex-grow: 1;
        }

        .module-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--color-black);
            font-weight: bold;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .module-link:hover {
            color: var(--color-gray-medium);
        }

        .footer {
            text-align: center;
            color: var(--color-gray-dark);
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--color-border);
        }

        .footer p {
            color: var(--color-gray-medium);
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
            <h1>Blockchain Dashboard</h1>
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
            <p>Système Blockchain v1.0 - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>