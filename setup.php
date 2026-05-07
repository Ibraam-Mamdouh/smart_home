<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartHome — Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#0f1117; color:#e2e8f0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .setup-card { background:#161b27; border:1px solid #1e2a3a; border-radius:16px; padding:40px; max-width:620px; width:100%; }
        .step { padding:10px 14px; border-radius:8px; margin-bottom:8px; font-size:.88rem; font-family:monospace; }
        .step.ok   { background:#00c89622; color:#00c896; border:1px solid #00c89644; }
        .step.err  { background:#ef444422; color:#ef4444; border:1px solid #ef444444; }
        .step.info { background:#1e2a3a; color:#94a3b8; border:1px solid #2d3f55; }
    </style>
</head>
<body>
<div class="setup-card">
    <div class="text-center mb-4">
        <div style="width:56px;height:56px;background:#00c896;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 16px">🏠</div>
        <h4 class="fw-bold">SmartHome Setup</h4>
        <p class="text-muted small">This will create the database, tables, and admin account.</p>
    </div>

    <?php

    // ── Config — edit if needed ──────────────────────────
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';          // XAMPP default is empty
    $DB_NAME = 'smart_home_db';
    $ADMIN_EMAIL    = 'admin@smarthome.local';
    $ADMIN_PASSWORD = 'Admin@1234';
    $ADMIN_NAME     = 'Admin Owner';
    // ────────────────────────────────────────────────────

    function step(string $msg, string $type = 'ok'): void {
        $icon = $type === 'ok' ? '✅' : ($type === 'err' ? '❌' : 'ℹ️');
        echo "<div class='step {$type}'>{$icon} {$msg}</div>";
    }

    $errors = false;

    // ── 1. Connect to MySQL (no DB selected yet) ─────────
    try {
        $pdo = new PDO("mysql:host={$DB_HOST};charset=utf8mb4", $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        step("Connected to MySQL on <strong>{$DB_HOST}</strong>");
    } catch (PDOException $e) {
        step("Cannot connect to MySQL: " . htmlspecialchars($e->getMessage()), 'err');
        step("Make sure XAMPP MySQL is running, and check DB_USER/DB_PASS above.", 'info');
        $errors = true;
    }

    if (!$errors):

    // ── 2. Create database ───────────────────────────────
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$DB_NAME}`");
    step("Database <strong>{$DB_NAME}</strong> ready");

    // ── 3. Create all tables ─────────────────────────────
    $tables = [

    "CREATE TABLE IF NOT EXISTS users (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(100)                        NOT NULL,
        email        VARCHAR(150)                        NOT NULL UNIQUE,
        password     VARCHAR(255)                        NOT NULL,
        role         ENUM('admin','resident','guest')    NOT NULL DEFAULT 'resident',
        avatar       VARCHAR(255)                        NULL,
        created_at   DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS rooms (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(80)  NOT NULL,
        floor      TINYINT      NOT NULL DEFAULT 1,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS appliances (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        room_id         INT UNSIGNED         NOT NULL,
        name            VARCHAR(100)         NOT NULL,
        type            VARCHAR(60)          NOT NULL,
        status          ENUM('ON','OFF','FAULT','SHUTDOWN') NOT NULL DEFAULT 'OFF',
        base_wattage    DECIMAL(8,2)         NOT NULL DEFAULT 0,
        age_hours       INT UNSIGNED         NOT NULL DEFAULT 0,
        health_score    TINYINT UNSIGNED     NOT NULL DEFAULT 100,
        resource_type   ENUM('electricity','water','gas') NOT NULL DEFAULT 'electricity',
        created_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_app_room FOREIGN KEY (room_id) REFERENCES rooms(id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS telemetry_logs (
        id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        device_id     INT UNSIGNED             NOT NULL,
        resource_type ENUM('electricity','water','gas') NOT NULL,
        value         DECIMAL(12,4)            NOT NULL,
        unit          VARCHAR(20)              NOT NULL,
        temperature   DECIMAL(6,2)            NULL,
        amperage      DECIMAL(8,4)            NULL,
        packet_id     VARCHAR(64)              NOT NULL,
        recorded_at   DATETIME(3)             NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
        CONSTRAINT fk_tel_device FOREIGN KEY (device_id) REFERENCES appliances(id),
        CONSTRAINT uq_packet UNIQUE (packet_id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS billing_history (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id        INT UNSIGNED            NOT NULL,
        resource_type  ENUM('electricity','water','gas') NOT NULL,
        billing_cycle  VARCHAR(7)              NOT NULL,
        total_kwh      DECIMAL(12,4)           NOT NULL DEFAULT 0,
        peak_cost      DECIMAL(12,4)           NOT NULL DEFAULT 0,
        offpeak_cost   DECIMAL(12,4)           NOT NULL DEFAULT 0,
        total_cost     DECIMAL(12,4)           NOT NULL DEFAULT 0,
        co2_kg         DECIMAL(10,4)           NOT NULL DEFAULT 0,
        created_at     DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_bill_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS budgets (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id        INT UNSIGNED            NOT NULL,
        resource_type  ENUM('electricity','water','gas') NOT NULL,
        billing_cycle  VARCHAR(7)              NOT NULL,
        monthly_limit  DECIMAL(10,2)           NOT NULL DEFAULT 0,
        current_spent  DECIMAL(10,4)           NOT NULL DEFAULT 0,
        updated_at     DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_bud_user FOREIGN KEY (user_id) REFERENCES users(id),
        CONSTRAINT uq_budget UNIQUE (user_id, resource_type, billing_cycle)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS tariff_rates (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        resource_type ENUM('electricity','water','gas') NOT NULL,
        tier_name    VARCHAR(30)             NOT NULL,
        rate_egp     DECIMAL(8,4)           NOT NULL,
        peak_start   TIME                   NULL,
        peak_end     TIME                   NULL,
        valid_from   DATE                   NOT NULL,
        valid_to     DATE                   NULL
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS automation_rules (
        id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id            INT UNSIGNED          NOT NULL,
        name               VARCHAR(120)          NOT NULL,
        condition_field    VARCHAR(60)           NOT NULL,
        condition_operator ENUM('>','<','>=','<=','==','!=') NOT NULL,
        condition_value    DECIMAL(12,4)         NOT NULL,
        action_type        ENUM('SHUTDOWN','ALERT','LOG','SCHEDULE') NOT NULL,
        action_value       VARCHAR(255)          NULL,
        priority           TINYINT UNSIGNED      NOT NULL DEFAULT 5,
        is_active          TINYINT(1)            NOT NULL DEFAULT 1,
        created_at         DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_rule_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS alerts (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED                        NOT NULL,
        device_id  INT UNSIGNED                        NULL,
        type       ENUM('SAFETY','BUDGET','ANOMALY','MAINTENANCE','SYSTEM') NOT NULL,
        message    TEXT                                NOT NULL,
        priority   ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
        is_read    TINYINT(1)                          NOT NULL DEFAULT 0,
        created_at DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS system_audit_trail (
        id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED  NULL,
        resource   VARCHAR(60)   NOT NULL,
        action     VARCHAR(60)   NOT NULL,
        old_value  TEXT          NULL,
        new_value  TEXT          NULL,
        ip_address VARCHAR(45)   NULL,
        created_at DATETIME(3)   NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS eco_challenges (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name             VARCHAR(120)    NOT NULL,
        description      TEXT            NULL,
        target_reduction DECIMAL(5,2)    NOT NULL,
        resource_type    ENUM('electricity','water','gas','all') NOT NULL DEFAULT 'all',
        points_reward    INT UNSIGNED    NOT NULL DEFAULT 0,
        start_date       DATE            NOT NULL,
        end_date         DATE            NOT NULL
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS user_challenges (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id      INT UNSIGNED NOT NULL,
        challenge_id INT UNSIGNED NOT NULL,
        joined_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status       ENUM('ACTIVE','PASSED','FAILED') NOT NULL DEFAULT 'ACTIVE',
        CONSTRAINT fk_uc_user  FOREIGN KEY (user_id)      REFERENCES users(id),
        CONSTRAINT fk_uc_chal  FOREIGN KEY (challenge_id) REFERENCES eco_challenges(id),
        CONSTRAINT uq_uc       UNIQUE (user_id, challenge_id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS reward_points (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED NOT NULL,
        points     INT          NOT NULL,
        action     VARCHAR(120) NOT NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_rp_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS uploaded_files (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED  NOT NULL,
        filename    VARCHAR(255)  NOT NULL,
        original    VARCHAR(255)  NOT NULL,
        mime_type   VARCHAR(80)   NOT NULL,
        size_bytes  INT UNSIGNED  NOT NULL,
        created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_uf_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB",

    ];

    $tableCount = 0;
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            $tableCount++;
        } catch (PDOException $e) {
            step("Table error: " . htmlspecialchars($e->getMessage()), 'err');
            $errors = true;
        }
    }
    if (!$errors) step("{$tableCount} tables created / verified");

    // ── 4. Seed rooms ────────────────────────────────────
    $roomCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    if ($roomCount == 0) {
        $pdo->exec("INSERT INTO rooms (name, floor) VALUES
            ('Living Room',1),('Kitchen',1),('Master Bedroom',2),
            ('Kids Bedroom',2),('Bathroom',1),('Garage',0)");
        step("6 rooms seeded");
    } else {
        step("{$roomCount} rooms already exist", 'info');
    }

    // ── 5. Create admin user with LIVE hash ──────────────
    $existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $existing->execute([$ADMIN_EMAIL]);

    if (!$existing->fetchColumn()) {
        $hash = password_hash($ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
        $stmt->execute([$ADMIN_NAME, $ADMIN_EMAIL, $hash, 'admin']);
        $adminId = (int)$pdo->lastInsertId();
        step("Admin user created — <strong>{$ADMIN_EMAIL}</strong> / <strong>{$ADMIN_PASSWORD}</strong>");
    } else {
        // Update password hash to be sure it's correct
        $hash = password_hash($ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("UPDATE users SET password=? WHERE email=?")->execute([$hash, $ADMIN_EMAIL]);
        $adminId = (int)$pdo->query("SELECT id FROM users WHERE email='".addslashes($ADMIN_EMAIL)."'")->fetchColumn();
        step("Admin password refreshed for <strong>{$ADMIN_EMAIL}</strong>", 'info');
    }

    // ── 6. Seed appliances ───────────────────────────────
    $appCount = $pdo->query("SELECT COUNT(*) FROM appliances")->fetchColumn();
    if ($appCount == 0) {
        $pdo->exec("INSERT INTO appliances (room_id,name,type,status,base_wattage,age_hours,health_score,resource_type) VALUES
            (1,'Living Room AC','Air Conditioner','OFF',1800,2400,85,'electricity'),
            (1,'Smart TV 55\"','Television','OFF',120,800,97,'electricity'),
            (2,'Refrigerator','Refrigerator','ON',150,5000,70,'electricity'),
            (2,'Dishwasher','Dishwasher','OFF',1400,600,95,'water'),
            (3,'Master Bedroom AC','Air Conditioner','OFF',1600,1800,90,'electricity'),
            (4,'Kids Room Fan','Fan','OFF',75,400,98,'electricity'),
            (5,'Water Heater','Water Heater','OFF',3000,3200,75,'electricity'),
            (6,'EV Charger','EV Charger','OFF',7200,200,99,'electricity')");
        step("8 appliances seeded");
    } else {
        step("{$appCount} appliances already exist", 'info');
    }

    // ── 7. Seed tariff rates ─────────────────────────────
    $tariffCount = $pdo->query("SELECT COUNT(*) FROM tariff_rates")->fetchColumn();
    if ($tariffCount == 0) {
        $pdo->exec("INSERT INTO tariff_rates (resource_type,tier_name,rate_egp,peak_start,peak_end,valid_from) VALUES
            ('electricity','Peak',1.85,'17:00:00','23:00:00','2024-01-01'),
            ('electricity','Off-Peak',0.95,'23:00:00','17:00:00','2024-01-01'),
            ('water','Standard',0.45,NULL,NULL,'2024-01-01'),
            ('gas','Standard',0.38,NULL,NULL,'2024-01-01')");
        step("Tariff rates seeded");
    }

    // ── 8. Seed budgets ──────────────────────────────────
    $cycle = date('Y-m');
    $budgetCount = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE user_id=?");
    $budgetCount->execute([$adminId]);
    if ($budgetCount->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO budgets (user_id,resource_type,billing_cycle,monthly_limit,current_spent) VALUES (?,?,?,?,?)");
        $stmt->execute([$adminId,'electricity',$cycle,500.00,187.40]);
        $stmt->execute([$adminId,'water',$cycle,80.00,32.10]);
        $stmt->execute([$adminId,'gas',$cycle,120.00,54.80]);
        step("Default budgets created");
    }

    // ── 9. Seed eco challenges ───────────────────────────
    $chalCount = $pdo->query("SELECT COUNT(*) FROM eco_challenges")->fetchColumn();
    if ($chalCount == 0) {
        $pdo->exec("INSERT INTO eco_challenges (name,description,target_reduction,resource_type,points_reward,start_date,end_date) VALUES
            ('May Energy Saver','Reduce electricity by 10% vs last month',10,'electricity',500,'".date('Y-m-01')."','".date('Y-m-t')."'),
            ('Water Warriors','Cut water usage by 15% this week',15,'water',300,'".date('Y-m-01')."','".date('Y-m-07')."'),
            ('Green Month','Reduce all resources by 5%',5,'all',800,'".date('Y-m-01')."','".date('Y-m-t')."')");
        step("Eco challenges seeded");
    }

    // ── 10. Seed automation rules ────────────────────────
    $ruleCount = $pdo->prepare("SELECT COUNT(*) FROM automation_rules WHERE user_id=?");
    $ruleCount->execute([$adminId]);
    if ($ruleCount->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO automation_rules (user_id,name,condition_field,condition_operator,condition_value,action_type,action_value,priority) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$adminId,'Emergency Overheat Shutdown','temperature','>',90,'SHUTDOWN','all',1]);
        $stmt->execute([$adminId,'High Amperage Alert','amperage','>',30,'ALERT','CRITICAL',2]);
        $stmt->execute([$adminId,'Budget 80% Warning','budget_pct','>',80,'ALERT','HIGH',3]);
        step("Default automation rules created");
    }

    // ── 11. Seed demo telemetry for charts ───────────────────────
    if (!$errors) {
        $tc = $pdo->query("SELECT COUNT(*) FROM telemetry_logs")->fetchColumn();
        if ($tc == 0) {
            $dApps = $pdo->query("SELECT id,base_wattage,resource_type FROM appliances WHERE id IN (1,2,3,4,5)")->fetchAll(PDO::FETCH_ASSOC);
            $now=time(); $intv=7*24*2; $ins=0;
            $stt=$pdo->prepare("INSERT IGNORE INTO telemetry_logs (device_id,resource_type,value,unit,temperature,amperage,packet_id,recorded_at) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($dApps as $ap) {
                for ($i=$intv;$i>=0;$i--) {
                    $ts=$now-($i*1800); $hr=(int)date("H",$ts);
                    $ml=(($hr>=7&&$hr<=9)||($hr>=18&&$hr<=22))?1.25:($hr<=5?0.3:0.8);
                    $u1=max(PHP_FLOAT_EPSILON,mt_rand(1,PHP_INT_MAX-1)/PHP_INT_MAX);
                    $z=sqrt(-2*log($u1))*cos(2*M_PI*(mt_rand(1,PHP_INT_MAX-1)/PHP_INT_MAX));
                    $v=max(0,round($ap["base_wattage"]*$ml+$z*$ap["base_wattage"]*0.05,4));
                    $stt->execute([$ap["id"],$ap["resource_type"],$v,"W",round(20+$v/$ap["base_wattage"]*8,2),round($v/220,4),bin2hex(random_bytes(10))."_demo",date("Y-m-d H:i:s",$ts)]);
                    $ins++;
                }
            }
            step("Demo sensor data seeded — $ins readings (7-day history for charts)");
        } else { step("$tc sensor readings already exist","info"); }
    }
    endif; // !$errors
    ?>

    <?php if (!$errors): ?>
    <div class="mt-4 p-3 rounded text-center" style="background:#00c89622;border:1px solid #00c89644">
        <div style="font-size:2rem">🎉</div>
        <div class="fw-bold" style="color:#00c896">Setup Complete!</div>
        <p class="text-muted small mt-1 mb-3">Your database is ready. You can now log in.</p>
        <a href="auth/login" class="btn px-5 py-2 fw-bold" style="background:#00c896;color:#0f1117">
            Go to Login →
        </a>
    </div>
    <div class="mt-3 p-3 rounded" style="background:#1e2a3a;font-size:.8rem">
        <strong>Login credentials:</strong><br>
        📧 Email: <code><?= htmlspecialchars($ADMIN_EMAIL) ?></code><br>
        🔑 Password: <code><?= htmlspecialchars($ADMIN_PASSWORD) ?></code>
    </div>
    <p class="text-muted text-center mt-3" style="font-size:.72rem">
        ⚠️ Delete <code>setup.php</code> after setup for security.
    </p>
    <?php else: ?>
    <div class="mt-3 alert alert-danger">Setup failed. Fix the errors above and refresh.</div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
// ── Extra: seed 7 days of demo telemetry for charts ──────────
// Only appended here — runs after main setup block
if (!$errors && isset($pdo)):
    $telCount = $pdo->query("SELECT COUNT(*) FROM telemetry_logs")->fetchColumn();
    if ($telCount == 0):
        $appRows = $pdo->query("SELECT id, base_wattage, resource_type FROM appliances WHERE status='OFF' LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        // For demo, flip them temporarily ON then insert readings
        $pdo->exec("UPDATE appliances SET status='ON' WHERE id IN (1,2,3)");

        $inserted = 0;
        $now      = time();
        // 7 days × 24 hours × 12 readings per hour = 2016 rows per device (trimmed to every 30min)
        $intervals = 7 * 24 * 2; // every 30min over 7 days
        $demoApps  = $pdo->query("SELECT id, base_wattage, resource_type FROM appliances WHERE id IN (1,2,3,4)")->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("INSERT IGNORE INTO telemetry_logs (device_id, resource_type, value, unit, temperature, amperage, packet_id, recorded_at) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($demoApps as $app) {
            for ($i = $intervals; $i >= 0; $i--) {
                $ts    = $now - ($i * 1800);
                $hour  = (int)date('H', $ts);
                // Realistic load curve — higher in morning & evening
                $mult  = ($hour>=7&&$hour<=9)||($hour>=18&&$hour<=22) ? 1.25 : (($hour>=0&&$hour<=5) ? 0.35 : 0.85);
                $sigma = $app['base_wattage'] * 0.05;
                $u1    = max(PHP_FLOAT_EPSILON, mt_rand(1,PHP_INT_MAX-1)/PHP_INT_MAX);
                $u2    = mt_rand(1,PHP_INT_MAX-1)/PHP_INT_MAX;
                $z     = sqrt(-2*log($u1)) * cos(2*M_PI*$u2);
                $val   = max(0, round(($app['base_wattage'] * $mult) + $z * $sigma, 4));
                $amp   = round($val / 220, 4);
                $temp  = round(20 + ($val / $app['base_wattage']) * 8, 2);

                $stmt->execute([
                    $app['id'],
                    $app['resource_type'],
                    $val,
                    'W',
                    $temp,
                    $amp,
                    bin2hex(random_bytes(12)) . '_seed',
                    date('Y-m-d H:i:s', $ts),
                ]);
                $inserted++;
            }
        }
        step("Demo telemetry seeded — {$inserted} sensor readings added (7-day history)");
    else:
        step("{$telCount} telemetry rows already exist", 'info');
    endif;
endif;
?>
