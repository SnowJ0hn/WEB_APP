<?php
require 'config.php';
require 'utils.php';
need_login();

$uid = (int)$_SESSION['user_id'];

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = trim($_POST['name'] ?? '');
  $target = (float)($_POST['target_amount'] ?? 0);
  $due = $_POST['due_date'] ?? null;

  if($name==='' || $target<=0){
    $err = '⚠️ กรุณากรอกชื่อและจำนวนเป้าหมายให้ครบ';
  } else {
    $st=$conn->prepare("INSERT INTO goals(user_id,name,target_amount,due_date) VALUES(?,?,?,?)");
    $st->bind_param('isds',$uid,$name,$target,$due);
    $st->execute();
    $st->close();
    $msg = '✅ สร้างเป้าหมายออมเงินเรียบร้อยแล้ว';
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สร้างเป้าหมายออมเงิน</title>
<style>
  body{
    background:#f2f6f8;
    font-family:"Prompt",Tahoma,sans-serif;
    color:#333;
    margin:0;
  }
  .wrap{
    max-width:720px;
    margin:90px auto 40px;
    background:#fff;
    padding:24px 30px;
    border-radius:16px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
  }
  h2{
    color:#16a34a;
    font-weight:600;
    margin-bottom:16px;
  }
  form{
    display:flex;
    flex-direction:column;
    gap:14px;
  }
  input[type=text], input[type=number], input[type=date]{
    padding:10px 12px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:14px;
    width:100%;
  }
  button{
    background:#16a34a;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    transition:0.2s;
  }
  button:hover{ background:#15803d; }
  .btn-link{
    background:#e5e7eb;
    color:#111;
    text-decoration:none;
    padding:10px 16px;
    border-radius:8px;
    font-size:15px;
  }
  .msg{
    padding:10px 14px;
    border-radius:8px;
    margin-bottom:12px;
    font-weight:500;
  }
  .success{ background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
  .error{ background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">
  <h2>🎯 สร้างเป้าหมายการออมเงิน</h2>

  <?php if($msg): ?>
    <div class="msg success"><?=esc($msg)?></div>
  <?php elseif($err): ?>
    <div class="msg error"><?=esc($err)?></div>
  <?php endif; ?>

  <form method="post">
    <label>ชื่อเป้าหมาย:</label>
    <input type="text" name="name" required>

    <label>จำนวนเป้าหมาย (บาท):</label>
    <input type="number" step="0.01" name="target_amount" required>

    <label>กำหนดวันสำเร็จ:</label>
    <input type="date" name="due_date">

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button>บันทึกเป้าหมาย</button>
      <a href="goals.php" class="btn-link">ดูเป้าหมาย</a>
    </div>
  </form>
</div>
</body>
</html>
