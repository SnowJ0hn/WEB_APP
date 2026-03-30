<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เข้าสู่ระบบ | Finance</title>
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #e3f7ec, #f5f7fa);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
}

.container {
  background: #fff;
  padding: 40px 50px;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  width: 350px;
  text-align: center;
}

h2 {
  color: #5fbf7f;
  margin-bottom: 10px;
}

p {
  color: #555;
  font-size: 14px;
}

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 12px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 10px;
  outline: none;
  transition: border-color 0.3s;
}

input[type="email"]:focus,
input[type="password"]:focus {
  border-color: #5fbf7f;
}

button {
  width: 100%;
  background: #5fbf7f;
  border: none;
  color: #fff;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s;
}

button:hover {
  background: #4ca56d;
}

a {
  color: #5fbf7f;
  text-decoration: none;
  font-size: 14px;
}

a:hover {
  text-decoration: underline;
}

.error {
  background: #ffe6e6;
  color: #d93025;
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 10px;
  font-size: 14px;
}
</style>
</head>
<body>
  <div class="container">
    <h2>เข้าสู่ระบบ</h2>
    <p>ระบบจัดการรายรับรายจ่ายส่วนบุคคล</p>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="email" name="email" placeholder="อีเมล" required>
      <input type="password" name="password" placeholder="รหัสผ่าน" required>
      <button type="submit">เข้าสู่ระบบ</button>
    </form>
    <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
  </div>
</body>
</html>
