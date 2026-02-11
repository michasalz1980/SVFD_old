<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depolox Pool Management Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            --dark-bg: #1a1a2e;
            --darker-bg: #16213e;
            --card-bg: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b8c6db;
            --border-color: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at top, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at bottom, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
            z-index: -1;
        }

        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo i {
            font-size: 2rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            font-size: 0.9rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse 2s infinite;
        }

        .status-dot.error {
            background: #f87171;
        }

        .status-dot.demo {
            background: #3b82f6;
        }

        .status-dot.loading {
            background: #fbbf24;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .nav-tabs {
            display: flex;
            gap: 1rem;
            margin: 2rem auto;
            max-width: 1400px;
            padding: 0 2rem;
        }

        .nav-tab {
            padding: 0.8rem 1.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tab:hover, .nav-tab.active {
            background: var(--primary-gradient);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            width: 100%;
        }

        .tab-content {
            display: none;
            overflow-x: auto;
        }

        .tab-content.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-grid {
            display: grid;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .grid-2 { 
            grid-template-columns: 1fr 1fr;
        }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: #4ade80;
        }

        .system-card {
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
            flex: 1;
            min-width: 0;
            max-width: none;
        }

        .system-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--success-gradient);
        }

        .system-card.warning::before {
            background: var(--warning-gradient);
        }

        .system-card.danger::before {
            background: var(--danger-gradient);
        }

        .system-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .system-name {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .system-status {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-excellent {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .status-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .status-critical {
            background: rgba(248, 113, 113, 0.2);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .parameters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .parameter {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .parameter:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.1);
        }

        /* USER STORY 1: Spezielle Styling für kritische Redox-Werte */
        .parameter.critical {
            border: 2px solid #f87171;
            background: rgba(248, 113, 113, 0.1);
            animation: redox-alert 2s ease-in-out infinite alternate;
        }

        .parameter.critical[data-type="redox"] {
            border: 3px solid #f87171;
            background: rgba(248, 113, 113, 0.15);
            box-shadow: 0 0 20px rgba(248, 113, 113, 0.3);
        }

        .parameter.critical[data-type="redox"]::before {
            content: '⚠️ UNTER 770 mV';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #f87171;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
            font-size: 0.6rem;
            font-weight: bold;
            z-index: 2;
        }

        @keyframes redox-alert {
            from { box-shadow: 0 0 10px rgba(248, 113, 113, 0.3); }
            to { box-shadow: 0 0 25px rgba(248, 113, 113, 0.6); }
        }

        .parameter-name {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .parameter-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .parameter-unit {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .parameter-setpoint {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 0.3rem;
        }

        .parameter-dosing {
            font-size: 0.7rem;
            color: #fbbf24;
            margin-top: 0.3rem;
        }

        /* USER STORY 2: System-Zeitstempel-Styling */
        .system-timestamp {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .system-timestamp i {
            color: #60a5fa;
        }

        .parameter.excellent .parameter-value {
            color: #4ade80;
        }

        .parameter.warning .parameter-value {
            color: #fbbf24;
        }

        .parameter.critical .parameter-value {
            color: #f87171;
            text-shadow: 0 0 10px rgba(248, 113, 113, 0.5);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alert-critical {
            background: rgba(248, 113, 113, 0.1);
            border-left-color: #f87171;
        }

        .alert-warning {
            background: rgba(251, 191, 36, 0.1);
            border-left-color: #fbbf24;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
        }

        .alert-icon {
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }

        .alert-content h4 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .alert-time {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .demo-notice {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            color: #3b82f6;
            text-align: center;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .error-notice {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #f87171;
            text-align: center;
        }

        .trends-container {
            width: 100%;
            overflow-x: auto;
        }

        .trends-scroll-container {
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            padding-bottom: 1rem;
        }

        .trends-scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .trends-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .trends-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .trends-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .trends-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            width: 100%;
            min-width: 1200px;
        }

        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            min-width: 280px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
            width: 100%;
        }

        .chart-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .chart-controls select, .chart-controls button {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .chart-controls select option {
            background: #2d2d4a;
            color: #ffffff;
            padding: 0.5rem;
            border: none;
        }

        .chart-controls select:hover,
        .chart-controls button:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .chart-controls select:focus,
        .chart-controls button:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }

        .chart-controls select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .trends-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            min-height: 400px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin-top: 1rem;
            width: 100%;
        }

        .trends-scroll-container {
            overflow-x: visible;
            overflow-y: visible;
            width: 100%;
            padding-bottom: 1rem;
        }

        .chart-card .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .system-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 1200px) {
            .trends-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .chart-card {
                min-height: 350px;
                padding: 1.5rem;
            }
            
            .chart-container {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-tabs {
                flex-wrap: wrap;
                padding: 0 1rem;
            }

            .container {
                padding: 0 1rem;
            }

            .card {
                padding: 1.5rem;
            }

            .card-grid.grid-2 {
                grid-template-columns: 1fr;
            }
            
            .trends-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                min-height: 300px;
                padding: 1rem;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .chart-controls {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }
            
            .chart-controls select,
            .chart-controls button {
                width: 100%;
            }

            .parameters-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            width: 100%;
        }

        .recommendation-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            min-height: 200px;
        }

        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .recommendation {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .recommendation-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .recommendation-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .icon-critical {
            background: rgba(248, 113, 113, 0.2);
            color: #f87171;
        }

        .icon-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .icon-success {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
        }

        .icon-info {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .recommendation-steps {
            list-style: none;
            margin-top: 1rem;
            counter-reset: step-counter;
        }

        .recommendation-steps li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            counter-increment: step-counter;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .recommendation-steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 20px;
            height: 20px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .layout-2-columns .trends-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .layout-1-column .trends-grid {
            grid-template-columns: 1fr;
            max-width: 1000px;
        }

        .layout-3-columns .trends-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .layout-toggle {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .layout-toggle button {
            width: 2rem;
            height: 2rem;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            color: var(--text-primary);
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .layout-toggle button:hover,
        .layout-toggle button.active {
            background: var(--primary-gradient);
            border-color: transparent;
        }

        .alarm-badge {
            background: #f87171;
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.5rem;
            min-width: 1.2rem;
            text-align: center;
            display: inline-block;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left: 4px solid #4ade80;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-swimming-pool"></i>
                <h1>Depolox Pool Management</h1>
            </div>
            <div class="header-actions">
                <div class="status-indicator">
                    <div class="status-dot loading" id="connection-dot"></div>
                    <span id="connection-status">Verbinde...</span>
                </div>
                <div class="status-indicator">
                    <i class="fas fa-clock"></i>
                    <span id="last-update">--:--</span>
                </div>
                <div class="status-indicator" style="cursor: pointer;" onclick="dashboard?.toggleDebug()">
                    <i class="fas fa-bug"></i>
                    <span>Debug</span>
                </div>
                <div class="status-indicator" style="cursor: pointer;" onclick="dashboard?.showDatabaseInfo()">
                    <i class="fas fa-database"></i>
                    <span>DB-Info</span>
                </div>
                <div class="status-indicator" style="cursor: pointer;" onclick="dashboard?.showDebugData()">
                    <i class="fas fa-search"></i>
                    <span>DB-Debug</span>
                </div>
            </div>
        </div>
    </header>

    <nav class="nav-tabs">
        <div class="nav-tab active" data-tab="overview">
            <i class="fas fa-tachometer-alt"></i>
            <span>Übersicht</span>
        </div>
        <div class="nav-tab" data-tab="values">
            <i class="fas fa-chart-line"></i>
            <span>Messwerte</span>
        </div>
        <div class="nav-tab" data-tab="trends">
            <i class="fas fa-chart-area"></i>
            <span>Trends</span>
        </div>
        <div class="nav-tab" data-tab="alarms">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Alarme</span>
            <span id="alarm-count" class="alarm-badge" style="display: none;"></span>
        </div>
        <div class="nav-tab" data-tab="recommendations">
            <i class="fas fa-lightbulb"></i>
            <span>Empfehlungen</span>
        </div>
    </nav>

    <main class="container">
        <!-- Debug Panel -->
        <div id="debug-panel" class="card" style="display: none; margin-bottom: 2rem; background: rgba(255, 255, 255, 0.05);">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-bug"></i>
                    Debug-Informationen
                </div>
                <button onclick="dashboard?.clearDebugLog()" style="padding: 0.5rem; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary); cursor: pointer;">
                    <i class="fas fa-trash"></i> Löschen
                </button>
            </div>
            <div id="debug-log" style="background: #000; color: #0f0; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.8rem; max-height: 300px; overflow-y: auto;">
                Debug-Log wird hier angezeigt...
            </div>
        </div>
        
        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="card-grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-swimming-pool"></i>
                            Pool-Systeme Status
                        </div>
                    </div>
                    <div id="systems-overview" class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-exclamation-circle"></i>
                            Aktive Alarme
                        </div>
                    </div>
                    <div id="alarms-overview" class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Values Tab -->
        <div id="values" class="tab-content">
            <div id="current-values" class="loading">
                <div class="spinner"></div>
            </div>
        </div>

        <!-- Trends Tab -->
        <div id="trends" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-area"></i>
                        Trend-Analyse
                    </div>
                    <div class="chart-controls">
                        <select id="trend-hours">
                            <option value="6">6 Stunden</option>
                            <option value="12">12 Stunden</option>
                            <option value="24" selected>24 Stunden</option>
                            <option value="48">48 Stunden</option>
                            <option value="168">7 Tage</option>
                        </select>
                        <button onclick="dashboard?.refreshTrends()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="layout-toggle">
                            <button onclick="setChartLayout('1')" title="1 Spalte">⚌</button>
                            <button onclick="setChartLayout('2')" title="2 Spalten" class="active">⚏</button>
                        </div>
                    </div>
                </div>
                <div id="trends-charts" class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>

        <!-- Alarms Tab -->
        <div id="alarms" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Aktive Alarme & Warnungen
                    </div>
                </div>
                <div id="alarms-list" class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>

        <!-- Recommendations Tab -->
        <div id="recommendations" class="tab-content">
            <div id="recommendations-list" class="loading">
                <div class="spinner"></div>
            </div>
        </div>
    </main>

    <script>
        class DepoloxDashboard {
            constructor() {
                this.apiBase = 'depolox_api.php';
                this.updateInterval = 30000; // 30 Sekunden
                this.charts = new Map();
                this.connectionStatus = 'loading';
                this.demoMode = false;
                this.debugMode = false;
                this.debugLog = [];
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.loadAllData();
                this.startAutoRefresh();
                this.log('Dashboard initialisiert');
            }

            log(message) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = `[${timestamp}] ${message}`;
                this.debugLog.push(logEntry);
                
                // Begrenze Log-Größe
                if (this.debugLog.length > 100) {
                    this.debugLog = this.debugLog.slice(-100);
                }
                
                // Update Debug-Panel wenn sichtbar
                if (this.debugMode) {
                    this.updateDebugPanel();
                }
                
                console.log(`DEPOLOX: ${message}`);
            }

            setChartLayout(columns) {
                const container = document.querySelector('.trends-scroll-container');
                const grid = container?.querySelector('.trends-grid');
                if (grid) {
                    grid.className = `trends-grid layout-${columns}-column${columns > 1 ? 's' : ''}`;
                }
            }

            toggleDebug() {
                this.debugMode = !this.debugMode;
                const panel = document.getElementById('debug-panel');
                if (panel) {
                    panel.style.display = this.debugMode ? 'block' : 'none';
                    if (this.debugMode) {
                        this.updateDebugPanel();
                    }
                }
                this.log(`Debug-Modus ${this.debugMode ? 'aktiviert' : 'deaktiviert'}`);
            }

            updateDebugPanel() {
                const logElement = document.getElementById('debug-log');
                if (logElement) {
                    logElement.innerHTML = this.debugLog.join('<br>');
                    logElement.scrollTop = logElement.scrollHeight;
                }
            }

            clearDebugLog() {
                this.debugLog = [];
                this.updateDebugPanel();
                this.log('Debug-Log geleert');
            }

            async showDatabaseInfo() {
                try {
                    this.log('Lade Datenbankinfo...');
                    const data = await this.apiCall('database_info');
                    
                    if (data?.status === 'success') {
                        this.displayDatabaseInfo(data.data);
                    } else {
                        this.log('Fehler beim Laden der Datenbankinfo');
                    }
                } catch (error) {
                    this.log(`Datenbankinfo-Fehler: ${error.message}`);
                }
            }

            async showDebugData() {
                try {
                    this.log('Lade Debug-Daten...');
                    const data = await this.apiCall('debug_data');
                    
                    if (data?.status === 'success') {
                        this.displayDebugData(data.data);
                    } else {
                        this.log('Fehler beim Laden der Debug-Daten');
                    }
                } catch (error) {
                    this.log(`Debug-Daten-Fehler: ${error.message}`);
                }
            }

            displayDatabaseInfo(dbInfo) {
                const infoWindow = window.open('', 'dbinfo', 'width=800,height=600,scrollbars=yes');
                const html = `
                    <html>
                    <head>
                        <title>Depolox Datenbankdiagnose</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a2e; color: white; }
                            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            th, td { padding: 10px; border: 1px solid #333; text-align: left; }
                            th { background: #2d2d4a; }
                            .exists { color: #4ade80; }
                            .missing { color: #f87171; }
                            .recommendation { background: #2d2d4a; padding: 15px; border-radius: 8px; margin: 20px 0; }
                        </style>
                    </head>
                    <body>
                        <h1>🔍 Depolox Datenbankdiagnose</h1>
                        
                        <div class="recommendation">
                            <h3>📊 Empfehlung:</h3>
                            <p>${dbInfo.recommendation}</p>
                        </div>
                        
                        <h2>📋 Tabellen-Status</h2>
                        <table>
                            <tr><th>Tabelle</th><th>Status</th><th>Datensätze</th><th>Beschreibung</th></tr>
                            ${Object.entries(dbInfo.tables).map(([table, info]) => `
                                <tr>
                                    <td>${table}</td>
                                    <td class="${info.exists ? 'exists' : 'missing'}">
                                        ${info.exists ? '✅ Vorhanden' : '❌ Fehlt'}
                                    </td>
                                    <td>${info.records}</td>
                                    <td>${info.description}</td>
                                </tr>
                            `).join('')}
                        </table>
                        
                        <h2>🏊 Verfügbare Systeme</h2>
                        ${dbInfo.systems.length > 0 ? `
                            <table>
                                <tr><th>System</th><th>Standort</th><th>Aktiv</th></tr>
                                ${dbInfo.systems.map(system => `
                                    <tr>
                                        <td>${system.system_name}</td>
                                        <td>${system.location_description || 'Unbekannt'}</td>
                                        <td>${system.is_active ? '✅' : '❌'}</td>
                                    </tr>
                                `).join('')}
                            </table>
                        ` : '<p>Keine Systeme in der Datenbank gefunden.</p>'}
                        
                        <button onclick="window.close()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;">
                            Schließen
                        </button>
                    </body>
                    </html>
                `;
                infoWindow.document.write(html);
                infoWindow.document.close();
            }

            displayDebugData(debugData) {
                const debugWindow = window.open('', 'debugdata', 'width=1000,height=700,scrollbars=yes');
                const html = `
                    <html>
                    <head>
                        <title>Depolox Debug-Daten</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a2e; color: white; }
                            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            th, td { padding: 8px; border: 1px solid #333; text-align: left; font-size: 12px; }
                            th { background: #2d2d4a; }
                            .section { background: #2d2d4a; padding: 15px; border-radius: 8px; margin: 20px 0; }
                            pre { background: #000; color: #0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
                        </style>
                    </head>
                    <body>
                        <h1>🔧 Debug-Daten</h1>
                        
                        <div class="section">
                            <h3>📊 Tabellen-Inhalte</h3>
                            ${Object.entries(debugData.tables || {}).map(([table, count]) => `
                                <p><strong>${table}:</strong> ${count} Datensätze</p>
                            `).join('')}
                        </div>
                        
                        ${debugData.recent_measurements ? `
                            <div class="section">
                                <h3>📈 Aktuelle Messungen (letzte 2h)</h3>
                                <table>
                                    <tr><th>System</th><th>Parameter</th><th>Wert</th><th>Sollwert</th><th>Zeit</th></tr>
                                    ${debugData.recent_measurements.map(m => `
                                        <tr>
                                            <td>${m.system_name}</td>
                                            <td>${m.type_name}</td>
                                            <td>${m.measured_value}</td>
                                            <td>${m.setpoint_value || '-'}</td>
                                            <td>${m.timestamp}</td>
                                        </tr>
                                    `).join('')}
                                </table>
                            </div>
                        ` : ''}
                        
                        ${debugData.samples ? `
                            <div class="section">
                                <h3>🔍 Beispiel-Daten</h3>
                                ${Object.entries(debugData.samples).map(([table, rows]) => `
                                    <h4>${table}:</h4>
                                    <pre>${JSON.stringify(rows, null, 2)}</pre>
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <button onclick="window.close()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;">
                            Schließen
                        </button>
                    </body>
                    </html>
                `;
                debugWindow.document.write(html);
                debugWindow.document.close();
            }

            setupEventListeners() {
                document.querySelectorAll('.nav-tab').forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        const tabName = e.currentTarget.dataset.tab;
                        this.switchTab(tabName);
                    });
                });

                document.getElementById('trend-hours')?.addEventListener('change', () => {
                    this.loadTrends();
                });
            }

            switchTab(tabName) {
                document.querySelectorAll('.nav-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');

                if (tabName === 'trends') {
                    this.loadTrends();
                } else if (tabName === 'recommendations') {
                    this.loadRecommendations();
                }
            }

            async apiCall(endpoint) {
                try {
                    this.log(`API-Aufruf gestartet: ${endpoint}`);
                    const url = `${this.apiBase}?endpoint=${endpoint}`;
                    
                    const response = await fetch(url);
                    this.log(`HTTP Status: ${response.status} ${response.statusText}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    this.log(`API-Antwort erhalten für ${endpoint}: ${data.status}`);
                    
                    // Prüfe ob Demo-Modus aktiviert wurde
                    if (data.demo_mode) {
                        this.setConnectionStatus('demo');
                        this.demoMode = true;
                        this.log(`Demo-Modus aktiviert: ${data.message || 'Keine Nachricht'}`);
                    } else {
                        this.setConnectionStatus('connected');
                        this.demoMode = false;
                        this.log(`Echte Daten erhalten für ${endpoint}`);
                    }
                    
                    return data;
                } catch (error) {
                    this.log(`API-Fehler für ${endpoint}: ${error.message}`);
                    this.setConnectionStatus('error');
                    this.demoMode = true;
                    
                    // Fallback auf lokale Demo-Daten
                    const fallbackData = {
                        status: 'success',
                        demo_mode: true,
                        message: `Verbindungsfehler: ${error.message}`,
                        data: this.getDemoData(endpoint),
                        total_count: endpoint === 'active_alarms' ? 2 : null
                    };
                    
                    this.log(`Fallback auf lokale Demo-Daten für ${endpoint}`);
                    return fallbackData;
                }
            }

            setConnectionStatus(status) {
                this.connectionStatus = status;
                const dot = document.getElementById('connection-dot');
                const statusText = document.getElementById('connection-status');
                
                switch(status) {
                    case 'connected':
                        dot.className = 'status-dot';
                        statusText.textContent = 'Verbunden';
                        this.log('Verbindung hergestellt');
                        break;
                    case 'error':
                        dot.className = 'status-dot error';
                        statusText.textContent = 'Verbindungsfehler';
                        this.log('Verbindungsfehler aufgetreten');
                        // Auto-Show Debug bei Fehlern
                        if (!this.debugMode) {
                            setTimeout(() => this.toggleDebug(), 2000);
                        }
                        break;
                    case 'demo':
                        dot.className = 'status-dot demo';
                        statusText.textContent = 'Demo-Modus';
                        this.log('Demo-Modus aktiviert');
                        break;
                    case 'loading':
                        dot.className = 'status-dot loading';
                        statusText.textContent = 'Wird geladen...';
                        this.log('Verbindung wird aufgebaut...');
                        break;
                }
            }

            getDemoData(endpoint) {
                const demoData = {
                    current_values: [
                        {
                            name: 'Schwimmer',
                            parameters: [
                                { name: 'Chlor (Cl2)', type: 'chlorine', value: 0.04, unit: 'mg/l', setpoint: 0.6, dosing: 45, status: 'critical', timestamp: new Date().toISOString(), formatted_time: new Date().toLocaleString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) },
                                { name: 'pH-Wert', type: 'ph', value: 7.8, unit: 'pH', setpoint: 7.2, dosing: 12, status: 'warning', timestamp: new Date().toISOString(), formatted_time: new Date().toLocaleString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) },
                                { name: 'Redox-Potential', type: 'redox', value: 685, unit: 'mV', setpoint: 770, dosing: null, status: 'critical', timestamp: new Date().toISOString(), formatted_time: new Date().toLocaleString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) },
                                { name: 'Temperatur', type: 'temperature', value: 24.5, unit: '°C', setpoint: null, dosing: null, status: 'excellent', timestamp: new Date().toISOString(), formatted_time: new Date().toLocaleString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }
                            ],
                            last_update: new Date().toISOString()
                        }
                    ],
                    system_health: [
                        {
                            system_name: 'Schwimmer',
                            location_description: 'Hauptbecken',
                            active_error_count: 2,
                            max_error_severity: 'WARNING',
                            connection_status: 'ONLINE',
                            health_status: 'warning',
                            minutes_since_update: 2,
                            last_status_update: new Date().toISOString()
                        }
                    ],
                    active_alarms: [
                        {
                            system_name: 'Schwimmer',
                            error_category: 'POOL_CHEMISTRY',
                            error_code: 'LOW_CHLORINE',
                            error_description: 'Kritisch niedrige Chlor-Konzentration',
                            severity: 'CRITICAL',
                            last_occurrence: new Date().toISOString(),
                            minutes_ago: 0,
                            value: 0.04,
                            unit: 'mg/l'
                        },
                        {
                            system_name: 'Schwimmer',
                            error_category: 'POOL_CHEMISTRY',
                            error_code: 'REDOX_LOW',
                            error_description: 'Redox-Potential unter Sollwert von 770 mV',
                            severity: 'CRITICAL',
                            last_occurrence: new Date().toISOString(),
                            minutes_ago: 2,
                            value: 685,
                            unit: 'mV'
                        }
                    ],
                    recommendations: [
                        {
                            system: 'Schwimmer',
                            actions: [
                                {
                                    type: 'critical',
                                    title: 'Sofortiger Chlor-Schock erforderlich',
                                    description: 'Chlor-Konzentration kritisch niedrig. Manuelle Chlorzugabe und Systemprüfung erforderlich.',
                                    steps: [
                                        'Sofort 0.5-1.0 mg/l Chlor manuell zugeben',
                                        'Wasserzirkulation prüfen',
                                        'Chlor-Dosieranlage überprüfen',
                                        'Nach 2 Stunden erneut messen'
                                    ]
                                },
                                {
                                    type: 'critical',
                                    title: 'Redox-Potential kritisch niedrig',
                                    description: 'Redox-Wert von 685 mV liegt unter dem Sollwert von 770 mV. Sofortige Maßnahmen erforderlich.',
                                    steps: [
                                        'Chlor-Konzentration erhöhen',
                                        'pH-Wert auf 7.0-7.2 einstellen',
                                        'Wasserzirkulation intensivieren',
                                        'Nach 1 Stunde Redox-Wert erneut messen'
                                    ]
                                }
                            ],
                            priority: 'critical',
                            overall_status: 'critical'
                        }
                    ],
                    trends: {
                        'Schwimmer': {
                            'chlorine': {
                                name: 'Chlor (Cl2)',
                                unit: 'mg/l',
                                data: this.generateTrendData(0.04, 24)
                            },
                            'ph': {
                                name: 'pH-Wert',
                                unit: 'pH',
                                data: this.generateTrendData(7.8, 24)
                            },
                            'redox': {
                                name: 'Redox-Potential',
                                unit: 'mV',
                                data: this.generateTrendData(685, 24)
                            },
                            'temperature': {
                                name: 'Temperatur',
                                unit: '°C',
                                data: this.generateTrendData(24.5, 24)
                            }
                        }
                    }
                };

                return demoData[endpoint] || [];
            }

            generateTrendData(baseValue, hours) {
                const data = [];
                const now = new Date();
                
                for (let i = hours; i >= 0; i--) {
                    const timestamp = new Date(now.getTime() - (i * 60 * 60 * 1000));
                    const variation = (Math.random() - 0.5) * 0.2 * baseValue;
                    data.push({
                        timestamp: timestamp.toISOString(),
                        time: timestamp.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'}),
                        value: Math.max(0, baseValue + variation),
                        setpoint: baseValue < 1 ? 0.6 : baseValue < 15 ? 7.2 : baseValue < 100 ? 25.0 : 770
                    });
                }
                
                return data;
            }

            async loadAllData() {
                await Promise.all([
                    this.loadCurrentValues(),
                    this.loadSystemHealth(),
                    this.loadAlarms(),
                    this.loadRecommendations()
                ]);
                this.updateLastUpdateTime();
            }

            async loadCurrentValues() {
                try {
                    this.log('Lade aktuelle Messwerte...');
                    const data = await this.apiCall('current_values');
                    
                    if (data?.status === 'success' && data.data) {
                        this.log(`${data.data.length} Systeme geladen`);
                        this.renderCurrentValues(data.data);
                        if (data.demo_mode) {
                            this.showDemoNotice('current-values');
                        }
                    } else {
                        throw new Error(data?.message || 'Keine Daten erhalten');
                    }
                } catch (error) {
                    this.log(`Fehler beim Laden der Messwerte: ${error.message}`);
                    this.showError('current-values', `Fehler beim Laden der Messwerte: ${error.message}`);
                }
            }

            async loadSystemHealth() {
                try {
                    this.log('Lade Systemstatus...');
                    const data = await this.apiCall('system_health');
                    
                    if (data?.status === 'success' && data.data) {
                        this.log(`${data.data.length} Systeme gefunden`);
                        this.renderSystemsOverview(data.data);
                        if (data.demo_mode) {
                            this.showDemoNotice('systems-overview');
                        }
                    } else {
                        throw new Error(data?.message || 'Keine Daten erhalten');
                    }
                } catch (error) {
                    this.log(`Fehler beim Laden des Systemstatus: ${error.message}`);
                    this.showError('systems-overview', `Fehler beim Laden des Systemstatus: ${error.message}`);
                }
            }

            async loadAlarms() {
                try {
                    this.log('Lade Alarme...');
                    const data = await this.apiCall('active_alarms');
                    
                    if (data?.status === 'success' && data.data) {
                        const count = data.total_count || data.data.length || 0;
                        this.log(`${count} Alarme gefunden`);
                        this.renderAlarms(data.data);
                        this.updateAlarmBadge(count);
                        if (data.demo_mode) {
                            this.showDemoNotice('alarms-overview');
                            this.showDemoNotice('alarms-list');
                        }
                    } else {
                        throw new Error(data?.message || 'Keine Daten erhalten');
                    }
                } catch (error) {
                    this.log(`Fehler beim Laden der Alarme: ${error.message}`);
                    this.showError('alarms-overview', `Fehler beim Laden der Alarme: ${error.message}`);
                    this.showError('alarms-list', `Fehler beim Laden der Alarme: ${error.message}`);
                }
            }

            async loadRecommendations() {
                try {
                    this.log('Lade Empfehlungen...');
                    const data = await this.apiCall('recommendations');
                    
                    if (data?.status === 'success' && data.data) {
                        this.log(`${data.data.length} Empfehlungen geladen`);
                        this.renderRecommendations(data.data);
                        if (data.demo_mode) {
                            this.showDemoNotice('recommendations-list');
                        }
                    } else {
                        throw new Error(data?.message || 'Keine Daten erhalten');
                    }
                } catch (error) {
                    this.log(`Fehler beim Laden der Empfehlungen: ${error.message}`);
                    this.showError('recommendations-list', `Fehler beim Laden der Empfehlungen: ${error.message}`);
                }
            }

            async loadTrends() {
                try {
                    const hours = document.getElementById('trend-hours')?.value || 24;
                    this.log(`Lade Trends für ${hours} Stunden...`);
                    const data = await this.apiCall(`trends&hours=${hours}`);
                    
                    if (data?.status === 'success' && data.data) {
                        const systemCount = Object.keys(data.data).length;
                        this.log(`Trends für ${systemCount} Systeme geladen`);
                        this.renderTrends(data.data);
                        if (data.demo_mode) {
                            this.showDemoNotice('trends-charts');
                        }
                    } else {
                        throw new Error(data?.message || 'Keine Daten erhalten');
                    }
                } catch (error) {
                    this.log(`Fehler beim Laden der Trend-Daten: ${error.message}`);
                    this.showError('trends-charts', `Fehler beim Laden der Trend-Daten: ${error.message}`);
                }
            }

            showError(containerId, message) {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = `
                        <div class="error-notice">
                            <i class="fas fa-exclamation-triangle"></i>
                            ${message}
                        </div>
                    `;
                }
            }

            showDemoNotice(containerId) {
                const container = document.getElementById(containerId);
                if (container) {
                    const notice = document.createElement('div');
                    notice.className = 'demo-notice';
                    notice.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        Demo-Daten werden angezeigt (API nicht verfügbar)
                    `;
                    container.insertBefore(notice, container.firstChild);
                }
            }

            renderCurrentValues(systems) {
                const container = document.getElementById('current-values');
                
                if (!systems || systems.length === 0) {
                    container.innerHTML = '<div class="error-notice">Keine Systemdaten verfügbar</div>';
                    return;
                }

                const html = systems.map(system => `
                    <div class="card system-card ${this.getSystemClass(system)}">
                        <div class="system-header">
                            <div class="system-name">${system.name}</div>
                            <div class="system-status ${this.getSystemStatusClass(system)}">
                                ${this.getSystemStatusText(system)}
                            </div>
                        </div>
                        <div class="parameters-grid">
                            ${system.parameters.map(param => `
                                <div class="parameter ${param.status}" data-type="${param.type}">
                                    <div class="parameter-name">${param.name}</div>
                                    <div class="parameter-value">${param.value}</div>
                                    <div class="parameter-unit">${param.unit}</div>
                                    ${param.setpoint !== null && param.setpoint !== undefined ? `<div class="parameter-setpoint">Soll: ${param.setpoint}</div>` : ''}
                                    ${param.dosing !== null && param.dosing !== undefined ? `<div class="parameter-dosing">${param.dosing}% Dosierung</div>` : ''}
                                </div>
                            `).join('')}
                        </div>
                        <div class="system-timestamp">
                            <i class="fas fa-clock"></i>
                            ${this.getSystemTimestamp(system)}
                        </div>
                    </div>
                `).join('');

                container.innerHTML = html;
            }

            renderSystemsOverview(systems) {
                const container = document.getElementById('systems-overview');
                
                if (!systems || systems.length === 0) {
                    container.innerHTML = '<div class="error-notice">Keine Systemdaten verfügbar</div>';
                    return;
                }

                const html = systems.map(system => `
                    <div class="system-overview">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3>${system.system_name}</h3>
                            <span class="system-status status-${system.health_status}">
                                ${system.connection_status}
                            </span>
                        </div>
                        <p><strong>Standort:</strong> ${system.location_description}</p>
                        <p><strong>Aktive Fehler:</strong> ${system.active_error_count}</p>
                        <p><strong>Letztes Update:</strong> ${this.formatTime(system.last_status_update)}</p>
                    </div>
                `).join('');

                container.innerHTML = html;
            }

            renderAlarms(alarms) {
                const overviewContainer = document.getElementById('alarms-overview');
                const listContainer = document.getElementById('alarms-list');
                
                if (!alarms || alarms.length === 0) {
                    const noAlarmsHtml = `
                        <div style="text-align: center; padding: 2rem; color: #4ade80;">
                            <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <h4>Keine aktiven Alarme</h4>
                            <p>Alle Systeme arbeiten ordnungsgemäß.</p>
                        </div>
                    `;
                    if (overviewContainer) overviewContainer.innerHTML = noAlarmsHtml;
                    if (listContainer) listContainer.innerHTML = noAlarmsHtml;
                    return;
                }

                const alarmHtml = alarms.map(alarm => this.renderAlarm(alarm)).join('');
                
                // Overview (nur die ersten 3 kritischsten)
                const criticalAlarms = alarms.filter(a => a.severity === 'CRITICAL');
                const overviewHtml = criticalAlarms.slice(0, 3).map(alarm => this.renderAlarm(alarm)).join('');
                
                if (overviewContainer) {
                    overviewContainer.innerHTML = overviewHtml || alarmHtml.slice(0, 3);
                }
                if (listContainer) {
                    listContainer.innerHTML = alarmHtml;
                }
            }

            renderAlarm(alarm) {
                const severityClass = alarm.severity.toLowerCase();
                const iconClass = this.getAlarmIcon(alarm.severity);
                
                return `
                    <div class="alert alert-${severityClass}">
                        <i class="${iconClass} alert-icon"></i>
                        <div class="alert-content">
                            <h4>${alarm.system_name} - ${alarm.error_description}</h4>
                            <p><strong>Kategorie:</strong> ${alarm.error_category}</p>
                            ${alarm.value ? `<p><strong>Aktueller Wert:</strong> ${alarm.value} ${alarm.unit}</p>` : ''}
                            ${alarm.occurrence_count > 1 ? `<p><strong>Aufgetreten:</strong> ${alarm.occurrence_count} mal</p>` : ''}
                            <div class="alert-time">${this.formatTimeAgo(alarm.minutes_ago)} - ${this.formatTime(alarm.last_occurrence)}</div>
                        </div>
                    </div>
                `;
            }

            renderRecommendations(recommendations) {
                const container = document.getElementById('recommendations-list');
                
                if (!recommendations || recommendations.length === 0) {
                    container.innerHTML = '<div class="error-notice">Keine Empfehlungen verfügbar</div>';
                    return;
                }

                const html = `
                    <div class="recommendations-grid">
                        ${recommendations.map(rec => `
                            <div class="recommendation-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <i class="fas fa-swimming-pool"></i>
                                        ${rec.system}
                                    </div>
                                    <div class="system-status status-${rec.overall_status}">
                                        ${rec.overall_status.toUpperCase()}
                                    </div>
                                </div>
                                <div class="recommendations">
                                    ${rec.actions.map(action => `
                                        <div class="recommendation">
                                            <div class="recommendation-header">
                                                <div class="recommendation-icon icon-${action.type}">
                                                    <i class="${this.getRecommendationIcon(action.type)}"></i>
                                                </div>
                                                <div>
                                                    <h4>${action.title}</h4>
                                                    <p>${action.description}</p>
                                                </div>
                                            </div>
                                            ${action.steps ? `
                                                <ol class="recommendation-steps">
                                                    ${action.steps.map(step => `<li>${step}</li>`).join('')}
                                                </ol>
                                            ` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;

                container.innerHTML = html;
            }

            renderTrends(trendsData) {
                const container = document.getElementById('trends-charts');
                
                if (!trendsData || Object.keys(trendsData).length === 0) {
                    container.innerHTML = '<div class="error-notice">Keine Trend-Daten verfügbar</div>';
                    return;
                }

                let html = '<div class="trends-scroll-container"><div class="trends-grid">';
                
                Object.entries(trendsData).forEach(([systemName, parameters]) => {
                    Object.entries(parameters).forEach(([paramType, paramData]) => {
                        const chartId = `chart-${systemName}-${paramType}`.replace(/[^a-zA-Z0-9]/g, '_');
                        html += `
                            <div class="chart-card">
                                <div class="card-header">
                                    <div class="card-title">${paramData.name} (${paramData.unit})</div>
                                    <div class="system-badge" style="font-size: 0.8rem; color: var(--text-secondary);">${systemName}</div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="${chartId}"></canvas>
                                </div>
                            </div>
                        `;
                    });
                });
                
                html += '</div></div>';
                container.innerHTML = html;

                setTimeout(() => {
                    Object.entries(trendsData).forEach(([systemName, parameters]) => {
                        Object.entries(parameters).forEach(([paramType, paramData]) => {
                            this.createChart(systemName, paramType, paramData);
                        });
                    });
                }, 100);
            }

            createChart(systemName, paramType, paramData) {
                const chartId = `chart-${systemName}-${paramType}`.replace(/[^a-zA-Z0-9]/g, '_');
                const ctx = document.getElementById(chartId)?.getContext('2d');
                
                if (!ctx) {
                    console.warn(`Canvas element ${chartId} not found`);
                    return;
                }

                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    ctx.canvas.parentElement.innerHTML = '<div class="error-notice">Chart.js konnte nicht geladen werden</div>';
                    return;
                }

                const existingChart = this.charts.get(chartId);
                if (existingChart) {
                    existingChart.destroy();
                }

                const data = paramData.data || [];
                const labels = data.map(d => d.time || new Date(d.timestamp).toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'}));
                const values = data.map(d => d.value);
                const setpoints = data.map(d => d.setpoint);

                const datasets = [{
                    label: paramData.name,
                    data: values,
                    borderColor: this.getParameterColor(paramType),
                    backgroundColor: this.getParameterColor(paramType, 0.1),
                    tension: 0.4,
                    fill: true
                }];

                if (setpoints.some(s => s !== null)) {
                    datasets.push({
                        label: 'Sollwert',
                        data: setpoints,
                        borderColor: '#fbbf24',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0
                    });
                }

                try {
                    const chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: {
                                        color: '#ffffff'
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { color: '#b8c6db' },
                                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                                },
                                y: {
                                    ticks: { color: '#b8c6db' },
                                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                                }
                            }
                        }
                    });

                    this.charts.set(chartId, chart);
                } catch (error) {
                    console.error('Error creating chart:', error);
                    ctx.canvas.parentElement.innerHTML = '<div class="error-notice">Fehler beim Erstellen des Charts</div>';
                }
            }

            // Utility Functions
            getSystemClass(system) {
                const hasErrors = system.parameters?.some(p => p.status === 'critical');
                const hasWarnings = system.parameters?.some(p => p.status === 'warning');
                
                if (hasErrors) return 'danger';
                if (hasWarnings) return 'warning';
                return '';
            }

            getSystemStatusClass(system) {
                const hasErrors = system.parameters?.some(p => p.status === 'critical');
                const hasWarnings = system.parameters?.some(p => p.status === 'warning');
                
                if (hasErrors) return 'status-critical';
                if (hasWarnings) return 'status-warning';
                return 'status-excellent';
            }

            getSystemStatusText(system) {
                const hasErrors = system.parameters?.some(p => p.status === 'critical');
                const hasWarnings = system.parameters?.some(p => p.status === 'warning');
                
                if (hasErrors) return 'Kritisch';
                if (hasWarnings) return 'Warnung';
                return 'Optimal';
            }

            getAlarmIcon(severity) {
                switch (severity) {
                    case 'CRITICAL': return 'fas fa-exclamation-circle';
                    case 'ERROR': return 'fas fa-exclamation-triangle';
                    case 'WARNING': return 'fas fa-exclamation';
                    default: return 'fas fa-info-circle';
                }
            }

            getRecommendationIcon(type) {
                switch (type) {
                    case 'critical': return 'fas fa-exclamation-circle';
                    case 'warning': return 'fas fa-exclamation-triangle';
                    case 'success': return 'fas fa-check-circle';
                    case 'info': return 'fas fa-info-circle';
                    default: return 'fas fa-info-circle';
                }
            }

            getParameterColor(paramType, alpha = 1) {
                const colors = {
                    chlorine: `rgba(74, 222, 128, ${alpha})`,
                    ph: `rgba(59, 130, 246, ${alpha})`,
                    redox: `rgba(168, 85, 247, ${alpha})`,
                    temperature: `rgba(248, 113, 113, ${alpha})`
                };
                return colors[paramType] || `rgba(156, 163, 175, ${alpha})`;
            }

            updateAlarmBadge(count) {
                const badge = document.getElementById('alarm-count');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            updateLastUpdateTime() {
                const now = new Date();
                const timeElement = document.getElementById('last-update');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'});
                }
            }

            getSystemTimestamp(system) {
                // Verwende system.last_update falls verfügbar, sonst den neuesten Parameter-Zeitstempel
                if (system.last_update) {
                    return new Date(system.last_update).toLocaleString('de-DE', {
                        day: '2-digit',
                        month: '2-digit', 
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
                // Fallback: Neuesten Zeitstempel aus Parametern finden
                if (system.parameters && system.parameters.length > 0) {
                    const timestamps = system.parameters
                        .map(p => p.timestamp)
                        .filter(t => t)
                        .sort()
                        .reverse();
                    
                    if (timestamps.length > 0) {
                        return new Date(timestamps[0]).toLocaleString('de-DE', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric', 
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                }
                
                return '--:--';
            }

            formatTime(dateString) {
                if (!dateString) return '--:--';
                return new Date(dateString).toLocaleString('de-DE');
            }

            formatTimeAgo(minutes) {
                if (minutes < 1) return 'Gerade eben';
                if (minutes < 60) return `vor ${Math.round(minutes)} Min.`;
                const hours = Math.floor(minutes / 60);
                if (hours < 24) return `vor ${hours} Std.`;
                const days = Math.floor(hours / 24);
                return `vor ${days} Tag(en)`;
            }

            startAutoRefresh() {
                setInterval(() => {
                    this.loadAllData();
                }, this.updateInterval);
            }

            refreshTrends() {
                this.loadTrends();
            }
        }

        // Globale Funktion für Layout-Toggle
        function setChartLayout(columns) {
            if (window.dashboard) {
                window.dashboard.setChartLayout(columns);
            }
            
            // Update active button
            document.querySelectorAll('.layout-toggle button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Dashboard initialisieren
        document.addEventListener('DOMContentLoaded', () => {
            window.dashboard = new DepoloxDashboard();
        });
    </script>
</body>
</html>