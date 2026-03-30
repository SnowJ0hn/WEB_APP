<?php
require_once 'config.php';
require_once 'utils.php';
need_login();

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_cat'])) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'expense';
    if ($name !== '') {
      $st = $conn->prepare("INSERT IGNORE INTO categories(user_id,name,type) VALUES(?,?,?)");
      $st->bind_param('iss', $uid, $name, $type);
      $st->execute(); $st->close();
    }
  }
  if (isset($_POST['add_method'])) {
    $name = trim($_POST['method_name'] ?? '');
    if ($name !== '') {
      $st = $conn->prepare("INSERT IGNORE INTO paymentmethods(user_id,method_name) VALUES(?,?)");
      $st->bind_param('is', $uid, $name);
      $st->execute(); $st->close();
    }
  }
}

if (isset($_GET['del_cat'])) {
  $id = (int)$_GET['del_cat'];
  $st = $conn->prepare("DELETE FROM categories WHERE category_id=? AND user_id=?");
  $st->bind_param('ii', $id, $uid);
  $st->execute(); $st->close();
  header('Location: manage_categories.php'); exit;
}
if (isset($_GET['del_method'])) {
  $id = (int)$_GET['del_method'];
  $st = $conn->prepare("DELETE FROM paymentmethods WHERE method_id=? AND user_id=?");
  $st->bind_param('ii', $id, $uid);
  $st->execute(); $st->close();
  header('Location: manage_categories.php'); exit;
}
//gatagory
$cats = $conn->query("
  SELECT category_id,name,type 
  FROM categories 
  WHERE user_id=$uid OR user_id=0 
  ORDER BY type,name
")->fetch_all(MYSQLI_ASSOC);
//payment
$methods = $conn->query("
  SELECT method_id,method_name 
  FROM paymentmethods 
  WHERE user_id=$uid OR user_id=0 
  ORDER BY method_name
")->fetch_all(MYSQLI_ASSOC);

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>หมวดหมู่และวิธีชำระ</title>
<style>
  body{
    background:#f2f6f8;
    font-family:"Prompt",Tahoma,sans-serif;
    color:#333;
    margin:0;
  }
  .wrap{
    max-width:1000px;
    margin:90px auto 40px;
    background:#fff;
    padding:24px 30px;
    border-radius:16px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
  }
  h2{
    font-weight:600;
    margin-bottom:10px;
    display:flex;
    align-items:center;
    gap:6px;
  }
  h2.cat{ color:#16a34a; }       /* เขียว */
  h2.method{ color:#0284c7; }    /* น้ำเงิน */

  form{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:8px;
    margin-bottom:14px;
  }
  input, select{
    padding:8px 10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:14px;
  }
  button{
    padding:8px 16px;
    border:none;
    border-radius:8px;
    color:#fff;
    cursor:pointer;
    font-size:14px;
    transition:.2s;
  }
  .btn-cat{ background:#16a34a; }
  .btn-cat:hover{ background:#15803d; }
  .btn-method{ background:#0284c7; }
  .btn-method:hover{ background:#0369a1; }

  table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:28px;
    border-radius:12px;
    overflow:hidden;
  }
  th, td{
    padding:10px 12px;
    border-bottom:1px solid #e5e7eb;
    font-size:14px;
  }
  th{
    background:#f8fafc;
    text-align:left;
    color:#475569;
    font-weight:600;
  }
  tr:hover td{
    background:#f9fafb;
  }
  a.del{
    color:#dc2626;
    text-decoration:none;
    font-weight:500;
  }
  a.del:hover{
    text-decoration:underline;
  }
  .no-data{
    text-align:center;
    color:#999;
    padding:20px 0;
  }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">

  <h2 class="cat">💚 หมวดหมู่</h2>
  <form method="post">
    <input type="hidden" name="add_cat" value="1">
    ชื่อ: <input name="name" required>
    ประเภท:
    <select name="type">
      <option value="expense">รายจ่าย</option>
      <option value="income">รายรับ</option>
    </select>
    <button class="btn-cat">เพิ่ม</button>
  </form>

  <table>
    <tr><th>ชื่อ</th><th>ประเภท</th><th>ลบ</th></tr>
    <?php foreach($cats as $c): ?>
      <tr>
        <td><?=esc($c['name'])?></td>
        <td><?=($c['type']=='income'?'รายรับ':'รายจ่าย')?></td>
        <td><a class="del" href="?del_cat=<?=$c['category_id']?>" onclick="return confirm('ลบหมวดหมู่นี้?')">ลบ</a></td>
      </tr>
    <?php endforeach; if(!$cats): ?>
      <tr><td colspan="3" class="no-data">ยังไม่มีหมวดหมู่</td></tr>
    <?php endif; ?>
  </table>

  <h2 class="method">💳 วิธีชำระ</h2>
  <form method="post">
    <input type="hidden" name="add_method" value="1">
    ชื่อวิธี: <input name="method_name" required>
    <button class="btn-method">เพิ่ม</button>
  </form>

  <table>
    <tr><th>ชื่อวิธีชำระ</th><th>ลบ</th></tr>
    <?php foreach($methods as $m): ?>
      <tr>
        <td><?=esc($m['method_name'])?></td>
        <td><a class="del" href="?del_method=<?=$m['method_id']?>" onclick="return confirm('ลบวิธีชำระนี้?')">ลบ</a></td>
      </tr>
    <?php endforeach; if(!$methods): ?>
      <tr><td colspan="2" class="no-data">ยังไม่มีวิธีชำระ</td></tr>
    <?php endif; ?>
  </table>

</div>
</body>
</html>
