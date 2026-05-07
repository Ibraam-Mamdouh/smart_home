-- ============================================================
-- Smart Home Resource Management System — Database Schema
-- Engine: MySQL 8.0+ / MariaDB 10.4+ (InnoDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_home_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_home_db;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)                        NOT NULL,
    email        VARCHAR(150)                        NOT NULL UNIQUE,
    password     VARCHAR(255)                        NOT NULL,
    role         ENUM('admin','resident','guest')    NOT NULL DEFAULT 'resident',
    avatar       VARCHAR(255)                        NULL,
    created_at   DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── ROOMS ───────────────────────────────────────────────────
CREATE TABLE rooms (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(80)  NOT NULL,
    floor      TINYINT      NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── APPLIANCES ──────────────────────────────────────────────
CREATE TABLE appliances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id         INT UNSIGNED         NOT NULL,
    name            VARCHAR(100)         NOT NULL,
    type            VARCHAR(60)          NOT NULL,
    status          ENUM('ON','OFF','FAULT','SHUTDOWN') NOT NULL DEFAULT 'OFF',
    base_wattage    DECIMAL(8,2)         NOT NULL DEFAULT 0,
    age_hours       INT UNSIGNED         NOT NULL DEFAULT 0,
    health_score    TINYINT UNSIGNED     NOT NULL DEFAULT 100 COMMENT '0-100',
    resource_type   ENUM('electricity','water','gas') NOT NULL DEFAULT 'electricity',
    created_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_room FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;

CREATE INDEX idx_app_room_status ON appliances (room_id, status);

-- ─── TELEMETRY LOGS ──────────────────────────────────────────
-- INSERT-ONLY — no UPDATE/DELETE at application level
CREATE TABLE telemetry_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id     INT UNSIGNED             NOT NULL,
    resource_type ENUM('electricity','water','gas') NOT NULL,
    value         DECIMAL(12,4)            NOT NULL,
    unit          VARCHAR(20)              NOT NULL,
    temperature   DECIMAL(6,2)            NULL COMMENT 'Celsius',
    amperage      DECIMAL(8,4)            NULL COMMENT 'Amps',
    packet_id     VARCHAR(64)              NOT NULL,
    recorded_at   DATETIME(3)             NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_tel_device FOREIGN KEY (device_id) REFERENCES appliances(id),
    CONSTRAINT uq_packet UNIQUE (packet_id)     -- idempotency guard
) ENGINE=InnoDB;

CREATE INDEX idx_tel_device_time ON telemetry_logs (device_id, recorded_at);

-- ─── BILLING HISTORY ─────────────────────────────────────────
-- INSERT-ONLY
CREATE TABLE billing_history (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED            NOT NULL,
    resource_type  ENUM('electricity','water','gas') NOT NULL,
    billing_cycle  VARCHAR(7)              NOT NULL COMMENT 'YYYY-MM',
    total_kwh      DECIMAL(12,4)           NOT NULL DEFAULT 0,
    peak_cost      DECIMAL(12,4)           NOT NULL DEFAULT 0,
    offpeak_cost   DECIMAL(12,4)           NOT NULL DEFAULT 0,
    total_cost     DECIMAL(12,4)           NOT NULL DEFAULT 0,
    co2_kg         DECIMAL(10,4)           NOT NULL DEFAULT 0,
    created_at     DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bill_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE INDEX idx_bill_user_cycle ON billing_history (user_id, billing_cycle);

-- ─── BUDGETS ─────────────────────────────────────────────────
CREATE TABLE budgets (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED            NOT NULL,
    resource_type  ENUM('electricity','water','gas') NOT NULL,
    billing_cycle  VARCHAR(7)              NOT NULL,
    monthly_limit  DECIMAL(10,2)           NOT NULL DEFAULT 0,
    current_spent  DECIMAL(10,4)           NOT NULL DEFAULT 0,
    updated_at     DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bud_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT uq_budget UNIQUE (user_id, resource_type, billing_cycle)
) ENGINE=InnoDB;

-- ─── TARIFF RATES ────────────────────────────────────────────
CREATE TABLE tariff_rates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('electricity','water','gas') NOT NULL,
    tier_name    VARCHAR(30)             NOT NULL,
    rate_egp     DECIMAL(8,4)           NOT NULL,
    peak_start   TIME                   NULL,
    peak_end     TIME                   NULL,
    valid_from   DATE                   NOT NULL,
    valid_to     DATE                   NULL
) ENGINE=InnoDB;

-- ─── AUTOMATION RULES ────────────────────────────────────────
CREATE TABLE automation_rules (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id            INT UNSIGNED          NOT NULL,
    name               VARCHAR(120)          NOT NULL,
    condition_field    VARCHAR(60)           NOT NULL,
    condition_operator ENUM('>','<','>=','<=','==','!=') NOT NULL,
    condition_value    DECIMAL(12,4)         NOT NULL,
    action_type        ENUM('SHUTDOWN','ALERT','LOG','SCHEDULE') NOT NULL,
    action_value       VARCHAR(255)          NULL,
    priority           TINYINT UNSIGNED      NOT NULL DEFAULT 5 COMMENT '1=highest',
    is_active          TINYINT(1)            NOT NULL DEFAULT 1,
    created_at         DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rule_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ─── ALERTS ──────────────────────────────────────────────────
CREATE TABLE alerts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED                        NOT NULL,
    device_id  INT UNSIGNED                        NULL,
    type       ENUM('SAFETY','BUDGET','ANOMALY','MAINTENANCE','SYSTEM') NOT NULL,
    message    TEXT                                NOT NULL,
    priority   ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    is_read    TINYINT(1)                          NOT NULL DEFAULT 0,
    created_at DATETIME                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ─── SYSTEM AUDIT TRAIL ──────────────────────────────────────
-- INSERT-ONLY immutable log
CREATE TABLE system_audit_trail (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED  NULL,
    resource   VARCHAR(60)   NOT NULL,
    action     VARCHAR(60)   NOT NULL,
    old_value  TEXT          NULL,
    new_value  TEXT          NULL,
    ip_address VARCHAR(45)   NULL,
    created_at DATETIME(3)   NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB;

-- ─── ECO CHALLENGES ──────────────────────────────────────────
CREATE TABLE eco_challenges (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120)    NOT NULL,
    description      TEXT            NULL,
    target_reduction DECIMAL(5,2)    NOT NULL COMMENT 'percentage 0-100',
    resource_type    ENUM('electricity','water','gas','all') NOT NULL DEFAULT 'all',
    points_reward    INT UNSIGNED    NOT NULL DEFAULT 0,
    start_date       DATE            NOT NULL,
    end_date         DATE            NOT NULL
) ENGINE=InnoDB;

CREATE TABLE user_challenges (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    challenge_id INT UNSIGNED NOT NULL,
    joined_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status       ENUM('ACTIVE','PASSED','FAILED') NOT NULL DEFAULT 'ACTIVE',
    CONSTRAINT fk_uc_user  FOREIGN KEY (user_id)      REFERENCES users(id),
    CONSTRAINT fk_uc_chal  FOREIGN KEY (challenge_id) REFERENCES eco_challenges(id),
    CONSTRAINT uq_uc       UNIQUE (user_id, challenge_id)
) ENGINE=InnoDB;

-- ─── REWARD POINTS ───────────────────────────────────────────
CREATE TABLE reward_points (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    points     INT          NOT NULL,
    action     VARCHAR(120) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rp_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ─── UPLOADED FILES ──────────────────────────────────────────
CREATE TABLE uploaded_files (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NOT NULL,
    filename    VARCHAR(255)  NOT NULL,
    original    VARCHAR(255)  NOT NULL,
    mime_type   VARCHAR(80)   NOT NULL,
    size_bytes  INT UNSIGNED  NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uf_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default rooms
INSERT INTO rooms (name, floor) VALUES
  ('Living Room', 1), ('Kitchen', 1), ('Master Bedroom', 2),
  ('Kids Bedroom', 2), ('Bathroom', 1), ('Garage', 0);

-- Default admin (password: Admin@1234)
INSERT INTO users (name, email, password, role) VALUES
  ('Admin Owner', 'admin@smarthome.local',
   '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiockrZB.Tz5rjGcSAqH7Y9KGT.i', 'admin');

-- Sample appliances
INSERT INTO appliances (room_id, name, type, status, base_wattage, age_hours, health_score, resource_type) VALUES
  (1, 'Living Room AC',        'Air Conditioner', 'OFF', 1800, 2400, 85, 'electricity'),
  (1, 'Smart TV 55"',          'Television',      'OFF',  120,  800, 97, 'electricity'),
  (2, 'Refrigerator',          'Refrigerator',    'ON',   150, 5000, 70, 'electricity'),
  (2, 'Dishwasher',            'Dishwasher',      'OFF', 1400,  600, 95, 'water'),
  (3, 'Master Bedroom AC',     'Air Conditioner', 'OFF', 1600, 1800, 90, 'electricity'),
  (4, 'Kids Room Fan',         'Fan',             'OFF',   75,  400, 98, 'electricity'),
  (5, 'Water Heater',          'Water Heater',    'OFF', 3000, 3200, 75, 'electricity'),
  (6, 'EV Charger',            'EV Charger',      'OFF', 7200,  200, 99, 'electricity');

-- Egyptian electricity tariff rates (simplified)
INSERT INTO tariff_rates (resource_type, tier_name, rate_egp, peak_start, peak_end, valid_from) VALUES
  ('electricity', 'Peak',     1.85, '17:00:00', '23:00:00', '2024-01-01'),
  ('electricity', 'Off-Peak', 0.95, '23:00:00', '17:00:00', '2024-01-01'),
  ('water',       'Standard', 0.45, NULL, NULL, '2024-01-01'),
  ('gas',         'Standard', 0.38, NULL, NULL, '2024-01-01');

-- Default budget for admin
INSERT INTO budgets (user_id, resource_type, billing_cycle, monthly_limit, current_spent) VALUES
  (1, 'electricity', DATE_FORMAT(NOW(), '%Y-%m'), 500.00, 187.40),
  (1, 'water',       DATE_FORMAT(NOW(), '%Y-%m'),  80.00,  32.10),
  (1, 'gas',         DATE_FORMAT(NOW(), '%Y-%m'), 120.00,  54.80);

-- Sample eco challenges
INSERT INTO eco_challenges (name, description, target_reduction, resource_type, points_reward, start_date, end_date) VALUES
  ('May Energy Saver',   'Reduce electricity by 10% vs last month', 10, 'electricity', 500, '2025-05-01', '2025-05-31'),
  ('Water Warriors',     'Cut water usage by 15% this week',        15, 'water',       300, '2025-05-01', '2025-05-07'),
  ('Green Ramadan',      'Reduce all resources by 5%',               5, 'all',         800, '2025-03-01', '2025-03-31');

-- Sample automation rules
INSERT INTO automation_rules (user_id, name, condition_field, condition_operator, condition_value, action_type, action_value, priority) VALUES
  (1, 'Emergency Overheat Shutdown', 'temperature', '>', 90, 'SHUTDOWN', 'all',     1),
  (1, 'High Amperage Alert',         'amperage',    '>', 30, 'ALERT',    'CRITICAL',2),
  (1, 'Budget 80% Warning',          'budget_pct',  '>', 80, 'ALERT',    'HIGH',    3),
  (1, 'Vacation Mode Leak Detect',   'water_flow',  '>', 0,  'ALERT',    'HIGH',    2);
