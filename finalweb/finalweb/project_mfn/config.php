<?php

// ===== Database settings ) =====
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'expense_tracker_db';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Setup MySQLi connection =====
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($DB_NAME);
$conn->set_charset('utf8mb4');

$setup_done = false;
function ok($m){ echo "✅ ".htmlspecialchars($m)."<br>"; }
function info($m){ echo "• ".htmlspecialchars($m)."<br>"; }

/* ===========================================================
   CREATE TABLES
=========================================================== */
$conn->query("
CREATE TABLE IF NOT EXISTS users (
  user_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS paymentmethods (
  method_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  method_name  VARCHAR(120) NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS categories (
  category_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  name         VARCHAR(120) NOT NULL,
  type         ENUM('income','expense') NULL DEFAULT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS income (
  income_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  category_id  INT NULL,
  method_id    INT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  occurred_at  DATE NOT NULL,
  note         VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_date (user_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS expenses (
  expense_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  category_id  INT NULL,
  method_id    INT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  occurred_at  DATE NOT NULL,
  note         VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_date (user_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS budgets (
  budget_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  category_id   INT NOT NULL,
  limit_amount  DECIMAL(12,2) NOT NULL,
  period_start  DATE NOT NULL,
  period_end    DATE NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS goals (
  goal_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  name           VARCHAR(120) NOT NULL,
  target_amount  DECIMAL(12,2) NOT NULL,
  due_date       DATE NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS goaltransactions (
  goal_tx_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id      INT NOT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  occurred_at  DATE NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_goal (goal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ===========================================================
   SEED DATA (Global version)
=========================================================== */

//  สร้าง admin เริ่มต้น
$adminEmail = 'admin@example.com';
$exists = $conn->prepare("SELECT COUNT(*) FROM users WHERE email=?");
$exists->bind_param('s', $adminEmail);
$exists->execute();
$exists->bind_result($cnt);
$exists->fetch();
$exists->close();

if ((int)$cnt === 0) {
  $name = 'Admin';
  $hash = password_hash('123456', PASSWORD_BCRYPT);
  $ins = $conn->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'admin')");
  $ins->bind_param('sss', $name, $adminEmail, $hash);
  $ins->execute();
  $ins->close();
  ok("seed admin: admin@example.com / 123456");
  $setup_done = true;
}

//  seed global categories 
$catCount = $conn->query("SELECT COUNT(*) c FROM categories WHERE user_id=0")->fetch_assoc()['c'] ?? 0;
if ((int)$catCount === 0) {
  $conn->query("INSERT INTO categories(user_id,name,type) VALUES
    (0,'ทั่วไป','expense'),
    (0,'อาหาร','expense'),
    (0,'เงินเดือน','income'),
    (0,'อื่น ๆ','income')");
  ok("seed global categories (ทุกคนเห็น)");
  $setup_done = true;
}

// 3️⃣ seed global payment methods (user_id = 0 → ทุกคนเห็น)
$pmCount = $conn->query("SELECT COUNT(*) c FROM paymentmethods WHERE user_id=0")->fetch_assoc()['c'] ?? 0;
if ((int)$pmCount === 0) {
  $conn->query("INSERT INTO paymentmethods(user_id,method_name) VALUES
    (0,'เงินสด'),
    (0,'โอน/พร้อมเพย์'),
    (0,'บัตรเครดิต')");
  ok("seed global payment methods (ทุกคนเห็น)");
  $setup_done = true;
}

/* ===========================================================
   SESSION & LOGIN HELPERS
=========================================================== */
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function need_login() {
  if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
  }
}

/* ===========================================================
   SETUP RESULT
=========================================================== */
if ($setup_done) {
    echo "<hr><strong>✅ Database setup complete.</strong><br>";
}
?>
