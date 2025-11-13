<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirect Manager - Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px 40px;
            max-width: 1000px;
            width: 100%;
            text-align: center;
        }

        .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        h1 {
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .description {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f9fafb;
            padding: 18px 15px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 6px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            color: #9ca3af;
            font-size: 0.85rem;
        }

        .footer-links {
            margin-top: 15px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #764ba2;
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔀</div>
        
        <h1>Redirect Manager</h1>
        
        <div class="status-badge">
            ✓ System Active
        </div>

        <p class="description">
            This service manages URL and domain redirects with built-in analytics.
            All configuration is handled through console commands.
        </p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['active_redirects']) }}</div>
                <div class="stat-label">Active Redirects</div>
            </div>

            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['total_redirects']) }}</div>
                <div class="stat-label">Total Redirects</div>
            </div>

            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['domain_redirects']) }}</div>
                <div class="stat-label">Domain Redirects</div>
            </div>

            <div class="stat-card">
                <div class="stat-number">{{ number_format($stats['url_redirects']) }}</div>
                <div class="stat-label">URL Redirects</div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="/up">Health Check</a>
            </div>
        </div>
    </div>
</body>
</html>
