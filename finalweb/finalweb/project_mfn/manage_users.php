<?php
session_start(); require 'config.php';
if(!isset($_SESSION['user_id'])){header('Location: login.php');exit;}
if($_SESSION['role']!=='admin'){ http_response_code(403); echo "Forbidden"; exit; }
function esc($s){return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8');}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['user_id']??0); $role=$_POST['role']??'user';
  $st=$conn->prepare("UPDATE users SET role=? WHERE user_id=?"); $st->bind_param('si',$role,$id); $st->execute(); $st->close();
}
if (isset($_GET['delete'])) {
  $uid = (int)$_GET['delete'];

  // ป้องกันไม่ให้ admin ลบตัวเอง
  if ($uid > 0 && $uid != $_SESSION['user_id']) {
    // ลบข้อมูลของผู้ใช้นี้ออกจากทุกตารางที่เกี่ยวข้อง
    $conn->query("DELETE FROM expenses WHERE user_id=$uid");
    $conn->query("DELETE FROM income WHERE user_id=$uid");
    $conn->query("DELETE FROM budgets WHERE user_id=$uid");
    $conn->query("DELETE FROM goals WHERE user_id=$uid");
    $conn->query("DELETE FROM categories WHERE user_id=$uid");
    $conn->query("DELETE FROM users WHERE user_id=$uid");

    echo "<script>alert('ลบผู้ใช้เรียบร้อยแล้ว');window.location='manage_users.php';</script>";
    exit;
  }
}
$users=$conn->query("SELECT user_id,name,email,role,created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>ผู้ใช้</title></head>
<script>
function confirmDelete(id) {
  if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้? การลบจะลบข้อมูลรายรับ/รายจ่ายทั้งหมดของผู้ใช้นี้ด้วย!')) {
    window.location.href = 'manage_users.php?delete=' + id;
  }
}
</script>
<body><?php include 'header.php'; ?>
<div class="wrap" style="max-width:900px;margin:0 auto;background:#fff;padding:16px;border-radius:12px">
<h2>จัดการผู้ใช้</h2>
<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-color:#eee">
<tr><th>ชื่อ</th><th>อีเมล</th><th>สิทธิ์</th><th>สมัครเมื่อ</th><th>ปรับ</th></tr>

<?php foreach($users as $u): ?>
<tr><td><?=esc($u['name'])?></td><td><?=esc($u['email'])?></td>
<td><?=esc($u['role'])?></td><td><?=esc($u['created_at'])?></td>
<td><form method="post" style="margin:0;display:inline"><input type="hidden" name="user_id" value="<?=$u['user_id']?>">
<select name="role"><option <?=$u['role']==='user'?'selected':''?>>user</option><option <?=$u['role']==='admin'?'selected':''?>>admin</option></select>
<button>บันทึก</button> <button type="Button" onclick="confirmDelete(<?=$u['user_id']?>)">ลบ</button></form></td></tr>

<?php endforeach; ?>
</table></div></body></html>
