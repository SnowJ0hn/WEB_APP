<?php
require_once 'config.php';
require_once 'utils.php';
need_login();

$uid = (int)$_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $amount = (float)($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $method_id = !empty($_POST['method_id']) ? (int)$_POST['method_id'] : NULL;
    $date = $_POST['occurred_at'] ?? date('Y-m-d');

    $st = $conn->prepare("INSERT INTO income(user_id,category_id,method_id,amount,note,occurred_at) VALUES(?,?,?,?,?,?)");
    $st->bind_param('iiidss', $uid, $category_id, $method_id, $amount, $note, $date);
    $st->execute();
    $st->close();

    $msg = '✅ บันทึกรายรับแล้ว';
}

$cats = $conn->query("SELECT category_id,name FROM categories WHERE user_id=$uid OR user_id=0");
$methods = $conn->query("SELECT method_id,method_name FROM paymentmethods WHERE user_id=$uid OR user_id=0");
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>เพิ่มรายรับ</title>
<style>
    body {
        font-family: "Segoe UI", "Prompt", sans-serif;
        background: #f6f9f7;
        margin: 0;
        padding: 0;
        color: #333;
    }
    .wrap {
        max-width: 680px;
        margin: 40px auto;
        background: #fff;
        padding: 24px 32px;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    h2 {
        text-align: center;
        color: #3da96d;
        margin-bottom: 24px;
    }
    form label {
        font-weight: 600;
        display: block;
        margin-bottom: 6px;
        color: #444;
    }
    input[type="number"],
    input[type="date"],
    input[type="text"],
    select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 15px;
        margin-bottom: 16px;
        transition: border 0.2s, box-shadow 0.2s;
    }
    input:focus, select:focus {
        outline: none;
        border-color: #66bb6a;
        box-shadow: 0 0 5px rgba(102,187,106,0.3);
    }
    button {
        background: #4caf50;
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        cursor: pointer;
        width: 100%;
        font-size: 16px;
        font-weight: 600;
        transition: background 0.3s, transform 0.2s;
    }
    button:hover {
        background: #43a047;
        transform: translateY(-2px);
    }
    .msg {
        text-align: center;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 8px;
        background: #e8f5e9;
        color: #2e7d32;
        font-weight: 500;
    }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">
    <h2>💰 เพิ่มรายรับ</h2>
    <?php if ($msg): ?>
        <div class="msg"><?= esc($msg) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>จำนวนเงิน:</label>
        <input type="number" step="0.01" name="amount" required>

        <label>หมวดหมู่:</label>
        <select name="category_id">
            <option value="">- ไม่ระบุ -</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['category_id'] ?>"><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>วิธีชำระ:</label>
        <select name="method_id">
            <option value="">- ไม่ระบุ -</option>
            <?php foreach ($methods as $m): ?>
                <option value="<?= $m['method_id'] ?>"><?= esc($m['method_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>วันที่:</label>
        <input type="date" name="occurred_at" value="<?= date('Y-m-d') ?>">

        <label>หมายเหตุ:</label>
        <input type="text" name="note">

        <button>บันทึก</button>
    </form>
</div>
</body>
</html>
