<?php
require 'config.php';
require 'utils.php';
need_login();

$uid = (int)$_SESSION['user_id'];
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$cats = $conn->query("SELECT category_id,name FROM categories WHERE user_id=$uid OR user_id=0");

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $category_id = (int)($_POST['category_id'] ?? 0);
  $limit_amount = (float)($_POST['limit_amount'] ?? 0);
  $start = $_POST['period_start'] ?? date('Y-m-01');
  $end   = $_POST['period_end'] ?? date('Y-m-t');

  if($category_id<=0 || $limit_amount<=0){
    $err = '⚠️ กรุณากรอกข้อมูลให้ครบถ้วน';
  } else {
    $st=$conn->prepare("
      INSERT INTO budgets(user_id,category_id,limit_amount,period_start,period_end)
      VALUES(?,?,?,?,?)
    ");
    $st->bind_param('iidss',$uid,$category_id,$limit_amount,$start,$end);
    $st->execute();
    $st->close();
    $msg='✅ บันทึกงบประมาณเรียบร้อยแล้ว';
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตั้งงบประมาณ</title>
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
    color:#dc2626;
    font-weight:600;
    margin-bottom:16px;
  }
  form{
    display:flex;
    flex-direction:column;
    gap:14px;
  }
  select,input[type=number],input[type=date]{
    padding:10px 12px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:14px;
    width:100%;
  }
  label{ font-weight:500; }
  button{
    background:#dc2626;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    transition:0.2s;
  }
  button:hover{ background:#b91c1c; }
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
  <h2>📉 ตั้งงบประมาณรายจ่าย</h2>

  <?php if($msg): ?>
    <div class="msg success"><?=esc($msg)?></div>
  <?php elseif($err): ?>
    <div class="msg error"><?=esc($err)?></div>
  <?php endif; ?>

  <form method="post">
    <label>หมวดรายจ่าย:</label>
    <select name="category_id" required>
      <option value="">-- เลือกหมวด --</option>
      <?php foreach($cats as $c): ?>
        <option value="<?=$c['category_id']?>"><?=esc($c['name'])?></option>
      <?php endforeach; ?>
    </select>

    <label>วงเงินงบ (บาท):</label>
    <input type="number" step="0.01" name="limit_amount" required>

    <label>ช่วงวันที่:</label>
    <div style="display:flex;gap:10px;">
      <input type="date" name="period_start" value="<?=esc(date('Y-m-01'))?>">
      <span style="align-self:center;">ถึง</span>
      <input type="date" name="period_end" value="<?=esc(date('Y-m-t'))?>">
    </div>

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button>บันทึกงบประมาณ</button>
      <a href="budgets.php" class="btn-link">ดูรายการงบ</a>
    </div>
  </form>
</div>
</body>
</html>
