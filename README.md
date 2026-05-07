# 🏠 Smart Home Resource Management System

A full-stack web application for monitoring and managing home energy, water, and gas consumption.

**Stack:** HTML5 + Bootstrap 5.3 · PHP 8.1+ · MySQL 8.0+

---

## 📁 Project Structure

```
smart_home/
├── index.php                  ← Front controller / router
├── .htaccess                  ← Apache URL rewriting
│
├── config/
│   ├── config.php             ← App constants (DB, tariffs, paths)
│   └── Database.php           ← PDO singleton
│
├── models/
│   ├── User.php               ← Auth + RBAC
│   ├── Appliance.php          ← CRUD + health degradation
│   ├── Telemetry.php          ← Mock generator, tariff engine, anomaly detection
│   ├── Budget.php             ← Budget CRUD + overrun escalation
│   └── Automation.php         ← Rule engine, audit trail, eco challenges
│
├── controllers/
│   ├── BaseController.php     ← Session, CSRF, RBAC, render, JSON helpers
│   ├── AuthController.php     ← Login / Register / Logout
│   ├── DashboardController.php
│   ├── ApplianceController.php
│   ├── BudgetController.php
│   ├── AutomationController.php
│   └── ReportsController.php
│
├── views/
│   ├── layout/
│   │   ├── main.php           ← Authenticated sidebar layout
│   │   └── auth.php           ← Auth-only layout
│   ├── auth/                  ← login.php, register.php
│   ├── dashboard/             ← index.php (KPIs, charts, alerts)
│   ├── appliances/            ← index.php, create.php, edit.php
│   ├── budget/                ← index.php
│   ├── automation/            ← index.php, audit.php
│   ├── reports/               ← index.php, pdf_template.php
│   └── errors/                ← 403.php
│
├── api/
│   └── telemetry.php          ← AJAX endpoint (live, weekly, forecast, mock_tick)
│
├── assets/
│   ├── css/app.css
│   └── js/app.js
│
└── database/
    └── schema.sql             ← Full MySQL schema + seed data
```

---

## ⚙️ Installation

### 1. Requirements
- **Apache** 2.4+ with `mod_rewrite` enabled
- **PHP** 8.1+ with extensions: `pdo_mysql`, `fileinfo`, `mbstring`
- **MySQL** 8.0+ or MariaDB 10.4+
- Place the project under your web root, e.g. `htdocs/smart_home/`

### 2. Database Setup
```sql
-- In MySQL client or phpMyAdmin:
SOURCE /path/to/smart_home/database/schema.sql;
```

### 3. Configuration
Edit `config/config.php`:
```php
define('BASE_URL',  'http://localhost/smart_home');  // no trailing slash
define('DB_HOST',   'localhost');
define('DB_NAME',   'smart_home_db');
define('DB_USER',   'root');
define('DB_PASS',   '');
```

### 4. Apache Virtual Host (optional)
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/smart_home"
    ServerName smarthome.local
    <Directory "/path/to/smart_home">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 5. Upload Directory
```bash
mkdir -p smart_home/uploads
chmod 755 smart_home/uploads
```

---

## 🔐 Default Login

| Email | Password | Role |
|---|---|---|
| `admin@smarthome.local` | `Admin@1234` | admin |

---

## 🚀 Key Features

| Feature | SRS Ref | Description |
|---|---|---|
| Tiered Tariff Engine | FR-01 | Peak (17–23h) vs off-peak EGP rates |
| Predictive Billing | FR-02 | 7-day rolling average forecast |
| Anomaly Detection | FR-03 | Statistical outlier (2.5σ) detection |
| Carbon Footprint | FR-05 | Per-resource CO₂ calculation |
| Resource Baseline | FR-06 | Hourly usage baseline over 14 days |
| Historical Reports | FR-08 | Daily / weekly aggregated consumption |
| Health Degradation | FR-10 | W_actual = W_base × (1+δ)^age |
| Maintenance Alerts | FR-12 | Triggered at <80% health score |
| Budget Management | FR-17 | Per-resource monthly limits |
| Overrun Escalation | FR-18 | Alerts at 80% / 90% / 100% |
| Reward Points | FR-20 | Points for under-budget months |
| Eco Challenges | FR-21 | Reduction targets with point rewards |
| Vacation Mode | FR-22 | Ultra-sensitive leak/usage detection |
| Automation Rules | FR-24 | IF–THEN rule engine with priorities |
| Mock Generator | FR-25 | Gaussian-distributed sensor simulation |
| RBAC | FR-26 | admin / resident / guest roles |
| Audit Trail | FR-29 | Immutable append-only event log |
| File Upload | FR-31 | JPEG/PNG/PDF upload (5MB limit) |

---

## 🎮 Live Simulation

Toggle the **"Live Sim"** switch in the top-right corner to run the mock sensor generator every 5 seconds. It will:
- Generate Gaussian-distributed readings for all `ON` appliances
- Evaluate automation rules and trigger shutdowns/alerts
- Update budget spending and escalation thresholds
- Refresh the weekly chart automatically

---

## 🛡️ Security

- Passwords hashed with `bcrypt` (cost 12)
- CSRF token on every state-changing form and AJAX call
- Session regeneration on login (prevents fixation)
- Prepared statements everywhere (no raw SQL concatenation)
- `htmlspecialchars()` on all output
- File type validated with `finfo` (not just extension)
- `.htaccess` blocks direct access to `config/`, `models/`, `controllers/`
