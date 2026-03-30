<?php
require_once 'config.php';
require_once 'utils.php';
need_login();

$conn->set_charset('utf8mb4');

$uid = (int)$_SESSION['user_id'];

/* ลบถาวร */
if (isset($_GET['del'])) {
  $id = (int)$_GET['del'];
  $st = $conn->prepare("DELETE FROM expenses WHERE expense_id=? AND user_id=?");
  $st->bind_param('ii', $id, $uid);
  $st->execute();
  $st->close();
  header('Location: view_expenses.php');
  exit;
}

/* รับช่วงเดือน */
$from_month = $_GET['from_month'] ?? date('Y-m');
$to_month   = $_GET['to_month']   ?? date('Y-m');

$from_date = date('Y-m-01', strtotime($from_month . '-01'));
$to_date   = date('Y-m-t',  strtotime($to_month   . '-01'));

/* ดึงรายการ */
$st = $conn->prepare("
  SELECT e.expense_id, e.occurred_at, e.amount, e.note,
         c.name AS category_name, pm.method_name
  FROM expenses e
  LEFT JOIN categories c ON e.category_id = c.category_id
  LEFT JOIN paymentmethods pm ON e.method_id = pm.method_id
  WHERE e.user_id=? AND e.occurred_at BETWEEN ? AND ?
  ORDER BY e.occurred_at DESC, e.expense_id DESC
");
$st->bind_param('iss', $uid, $from_date, $to_date);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>รายการจ่าย</title>
<style>
  body{background:#f2f6f8;font-family:"Prompt",Tahoma,sans-serif;color:#333;margin:0;}
  .wrap{max-width:1000px;margin:90px auto 40px;background:#fff;padding:24px 30px;border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.08);}
  h2{color:#dc2626;margin-bottom:16px;font-weight:600;}
  .toolbar{display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap;}
  input[type=month]{padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;}
  .btn{background:#dc2626;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;transition:0.2s;}
  .btn:hover{background:#b91c1c;}
  table{width:100%;border-collapse:collapse;border-radius:12px;overflow:hidden;}
  th,td{padding:12px 10px;border-bottom:1px solid #e5e7eb;font-size:14px;}
  th{background:#f8fafc;text-align:left;color:#475569;font-weight:600;}
  tr:hover td{background:#fef2f2;}
  td:last-child{text-align:center;}
  .danger{color:#dc2626;text-decoration:none;font-weight:500;}
  .danger:hover{text-decoration:underline;}
  .amount{color:#dc2626;font-weight:500;text-align:right;}
  .no-data{text-align:center;color:#999;padding:30px 0;}
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">
  <h2>💸 รายการจ่าย</h2>

  <form class="toolbar" method="get">
    <label>ช่วงเดือน:</label>
    <input type="month" name="from_month" value="<?=esc($from_month)?>">
    <label>ถึง</label>
    <input type="month" name="to_month" value="<?=esc($to_month)?>">
    <button class="btn">กรอง</button>
  </form>

  <table>
    <tr>
      <th>วันที่</th>
      <th>หมวดหมู่</th>
      <th>วิธีชำระ</th>
      <th style="text-align:right">จำนวนเงิน (฿)</th>
      <th>หมายเหตุ</th>
      <th>ลบ</th>
    </tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?=esc($r['occurred_at'])?></td>
        <td><?=esc($r['category_name'] ?? '-')?></td>
        <td><?=esc($r['method_name'] ?? '-')?></td>
        <td class="amount"><?=number_format((float)$r['amount'],2)?></td>
        <td><?=esc($r['note'] ?? '')?></td>
        <td><a class="danger" href="?del=<?=$r['expense_id']?>" onclick="return confirm('ลบถาวรรายการนี้?')">ลบ</a></td>
      </tr>
    <?php endforeach; if(!$rows): ?>
      <tr><td colspan="6" class="no-data">ไม่มีข้อมูลในช่วงที่เลือก</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
