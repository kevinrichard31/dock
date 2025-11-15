<?php
/**
 * validators.php
 * Template pour afficher la liste des validateurs et le formulaire pour en devenir un
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validators - Blockchain</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.9;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .validator-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        
        .validator-card:hover {
            transform: translateX(5px);
        }
        
        .validator-key {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            word-break: break-all;
            margin: 5px 0;
        }
        
        .validator-collateral {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin: 5px 0;
        }
        
        .validator-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-approved {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #664d03;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: transform 0.2s;
            width: 100%;
        }
        
        button:hover {
            transform: scale(1.02);
        }
        
        button:active {
            transform: scale(0.98);
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0c5aa0;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .validators-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔐 Validators Network</h1>
            <p>Proof of Stake - Manage your validator status</p>
        </header>
        
        <div class="stats">
            <div class="stat-card">
                <div class="value" id="active-count">0</div>
                <div class="label">Active Validators</div>
            </div>
            <div class="stat-card">
                <div class="value" id="approved-count">0</div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="value" id="pending-count">0</div>
                <div class="label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="value" id="total-collateral">0</div>
                <div class="label">Total Collateral</div>
            </div>
        </div>
        
        <div class="content">
            <!-- Current Validators -->
            <div class="section">
                <h2>👥 Active Validators</h2>
                <div class="info-box">
                    <strong>Current Collateral Required:</strong>
                    10,000 coins per validator
                </div>
                <div class="validators-list" id="validators-list">
                    <p>Loading validators...</p>
                </div>
            </div>
            
            <!-- Request Validator Status -->
            <div class="section">
                <h2>📝 Request Validator Status</h2>
                <div class="info-box">
                    <strong>To become a validator:</strong>
                    Submit your public key with the required 10,000 coin collateral. You'll need approval before validating transactions.
                </div>
                
                <form id="validator-form">
                    <div class="form-group">
                        <label for="public-key">Your Public Key:</label>
                        <textarea id="public-key" name="public_key" placeholder="Paste your public key here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Collateral Amount:</label>
                        <input type="text" value="10,000 coins" disabled style="background: #f5f5f5; cursor: not-allowed;">
                    </div>
                    
                    <button type="submit">Submit Validator Request</button>
                </form>
            </div>
        </div>
        
        <!-- Approved Validators List -->
        <div class="section" style="margin-top: 30px;">
            <h2>✅ Approved Validators</h2>
            <div class="validators-list" id="approved-validators-list">
                <p>Loading approved validators...</p>
            </div>
        </div>
    </div>
    
    <script>
        // Load validators data
        async function loadValidators() {
            try {
                // Load stats
                const statsResponse = await fetch('/api.php?module=validator&action=stats');
                const statsData = await statsResponse.json();
                
                if (statsData.success) {
                    document.getElementById('active-count').textContent = statsData.data.active;
                    document.getElementById('approved-count').textContent = statsData.data.approved;
                    document.getElementById('pending-count').textContent = statsData.data.pending;
                    document.getElementById('total-collateral').textContent = 
                        new Intl.NumberFormat().format(statsData.data.totalCollateral);
                }
                
                // Load all validators
                const validatorsResponse = await fetch('/api.php?module=validator&action=get_all');
                const validatorsData = await validatorsResponse.json();
                
                if (validatorsData.success) {
                    const list = document.getElementById('validators-list');
                    list.innerHTML = '';
                    
                    if (validatorsData.data.length === 0) {
                        list.innerHTML = '<p>No validators yet.</p>';
                    } else {
                        validatorsData.data.forEach(validator => {
                            const status = validator.is_approved ? 'approved' : 'pending';
                            const statusText = validator.is_approved ? '✅ Approved' : '⏳ Pending';
                            
                            const card = document.createElement('div');
                            card.className = 'validator-card';
                            card.innerHTML = `
                                <div class="validator-key">
                                    <strong>Key:</strong> ${validator.public_key}
                                </div>
                                <div class="validator-collateral">
                                    💰 ${new Intl.NumberFormat().format(validator.collateral)} coins
                                </div>
                                <span class="validator-status status-${status}">${statusText}</span>
                            `;
                            list.appendChild(card);
                        });
                    }
                }
                
                // Load approved validators
                const approvedResponse = await fetch('/api.php?module=validator&action=get_approved');
                const approvedData = await approvedResponse.json();
                
                if (approvedData.success) {
                    const list = document.getElementById('approved-validators-list');
                    list.innerHTML = '';
                    
                    if (approvedData.data.length === 0) {
                        list.innerHTML = '<p>No approved validators yet.</p>';
                    } else {
                        approvedData.data.forEach(validator => {
                            const card = document.createElement('div');
                            card.className = 'validator-card';
                            card.innerHTML = `
                                <div class="validator-key">
                                    <strong>Key:</strong> ${validator.public_key}
                                </div>
                                <div class="validator-collateral">
                                    💰 ${new Intl.NumberFormat().format(validator.collateral)} coins
                                </div>
                            `;
                            list.appendChild(card);
                        });
                    }
                }
            } catch (error) {
                console.error('Error loading validators:', error);
            }
        }
        
        // Handle form submission
        document.getElementById('validator-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const publicKey = document.getElementById('public-key').value.trim();
            
            if (!publicKey) {
                alert('Please enter your public key');
                return;
            }
            
            try {
                const response = await fetch('/api.php?module=validator&action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        public_key: publicKey
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Validator registration request submitted!\nWaiting for approval...');
                    document.getElementById('validator-form').reset();
                    loadValidators(); // Reload list
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (error) {
                alert('Error submitting request: ' + error.message);
            }
        });
        
        // Load validators on page load
        loadValidators();
        
        // Reload every 10 seconds
        setInterval(loadValidators, 10000);
    </script>
</body>
</html>
