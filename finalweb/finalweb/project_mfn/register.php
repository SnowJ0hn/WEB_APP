<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // ตรวจสอบอีเมลซ้ำ
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "อีเมลนี้มีอยู่ในระบบแล้ว";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $success = "สมัครสมาชิกสำเร็จ! <a href='login.php'>เข้าสู่ระบบ</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก | Finance</title>
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
  width: 380px;
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

input[type="text"],
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

input:focus {
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

.success {
  background: #e5f7eb;
  color: #2f8f4a;
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 10px;
  font-size: 14px;
}
</style>
</head>
<body>
  <div class="container">
    <h2>สมัครสมาชิก</h2>
    <p>สร้างบัญชีใหม่เพื่อเริ่มจัดการรายรับรายจ่าย</p>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="name" placeholder="ชื่อ" required>
      <input type="email" name="email" placeholder="อีเมล" required>
      <input type="password" name="password" placeholder="รหัสผ่าน" required>
      <button type="submit">สมัครสมาชิก</button>
    </form>

    <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
  </div>
</body>
</html>
