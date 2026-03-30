<?php
require 'config.php';
require 'utils.php';
need_login();

$conn->set_charset('utf8mb4');
$uid = (int)$_SESSION['user_id'];

/* ===== ลบงบ ===== */
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $st = $conn->prepare("DELETE FROM budgets WHERE budget_id=? AND user_id=?");
  $st->bind_param('ii', $id, $uid);
  $st->execute(); $st->close();
  header('Location: budgets.php'); exit;
}

/* ===== ดึงข้อมูลงบ + ยอดใช้จริง ===== */
$sql = "
  SELECT
    b.budget_id, b.category_id, b.limit_amount, b.period_start, b.period_end,
    c.name AS category,
    COALESCE(SUM(e.amount), 0) AS used_amount
  FROM budgets b
  LEFT JOIN categories c
         ON c.category_id = b.category_id
  LEFT JOIN expenses e
         ON e.user_id = b.user_id
        AND e.category_id = b.category_id
        AND e.occurred_at BETWEEN b.period_start AND b.period_end
  WHERE b.user_id = ?
  GROUP BY b.budget_id, b.category_id, b.limit_amount, b.period_start, b.period_end, c.name
  ORDER BY b.period_start DESC, b.budget_id DESC
";
$st = $conn->prepare($sql);
$st->bind_param('i', $uid);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>งบประมาณ</title>
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
    color:#16a34a;
    font-weight:600;
    margin-bottom:16px;
  }
  a.btn{
    display:inline-block;
    background:#16a34a;
    color:#fff;
    padding:8px 16px;
    border-radius:8px;
    text-decoration:none;
    transition:0.2s;
  }
  a.btn:hover{ background:#15803d; }

  table{
    width:100%;
    border-collapse:collapse;
    margin-top:12px;
    border-radius:10px;
    overflow:hidden;
  }
  th,td{
    padding:12px 10px;
    border-bottom:1px solid #e5e7eb;
    font-size:14px;
  }
  th{
    background:#f8fafc;
    text-align:left;
    color:#475569;
    font-weight:600;
  }
  tr:hover td{ background:#f9fafb; }
  .danger{
    color:#dc2626;
    text-decoration:none;
    font-weight:500;
  }
  .danger:hover{text-decoration:underline;}
  .no-data{
    text-align:center;
    color:#999;
    padding:30px 0;
  }
  .progress{
    width:100%;
    height:8px;
    border-radius:4px;
    background:#e5e7eb;
    overflow:hidden;
    margin-top:4px;
  }
  .bar{
    height:8px;
    border-radius:4px;
  }
  .bar.green{background:#16a34a;}
  .bar.yellow{background:#eab308;}
  .bar.red{background:#dc2626;}
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">
  <h2>💚 งบประมาณรายจ่าย</h2>
  <p><a class="btn" href="add_budget.php">+ ตั้งงบใหม่</a></p>

  <table>
    <tr>
      <th>ช่วงเวลา</th>
      <th>หมวดหมู่</th>
      <th style="text-align:right">วงเงิน (บาท)</th>
      <th style="text-align:right">ใช้ไป (บาท)</th>
      <th style="text-align:right">คงเหลือ</th>
      <th style="width:90px;">ลบ</th>
    </tr>
    <?php foreach($rows as $r):
      $limit = (float)$r['limit_amount'];
      $used  = (float)$r['used_amount'];
      $remain = $limit - $used;
      $percent = $limit > 0 ? min(100, ($used / $limit) * 100) : 0;
      $barColor = $percent < 70 ? 'green' : ($percent < 100 ? 'yellow' : 'red');
    ?>
      <tr>
        <td><?=esc($r['period_start'])?> – <?=esc($r['period_end'])?></td>
        <td><?=esc($r['category'] ?? '-')?></td>
        <td style="text-align:right"><?=number_format($limit,2)?></td>
        <td style="text-align:right"><?=number_format($used,2)?></td>
        <td style="text-align:right; color:<?=$remain < 0 ? '#dc2626' : '#16a34a'?>">
          <?=number_format($remain,2)?><br>
          <div class="progress"><div class="bar <?=$barColor?>" style="width:<?=$percent?>%"></div></div>
        </td>
        <td><a class="danger" href="?del=<?=$r['budget_id']?>" onclick="return confirm('ลบงบนี้?')">ลบ</a></td>
      </tr>
    <?php endforeach; if(!$rows): ?>
      <tr><td colspan="6" class="no-data">ยังไม่มีงบประมาณที่ตั้งไว้</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
