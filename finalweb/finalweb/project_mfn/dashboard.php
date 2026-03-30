<?php
require_once 'config.php';
require_once 'utils.php';
need_login();

$conn->set_charset('utf8mb4');

$uid = (int)$_SESSION['user_id'];

$fromMonth = $_GET['from_month'] ?? date('Y-m');
$toMonth   = $_GET['to_month']   ?? date('Y-m');
$fromDate  = date('Y-m-01', strtotime($fromMonth . '-01'));
$toDate    = date('Y-m-t',  strtotime($toMonth   . '-01'));

$sumInc = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND occurred_at BETWEEN ? AND ?");
$sumInc->bind_param('iss', $uid, $fromDate, $toDate);
$sumInc->execute(); $sumInc->bind_result($totalIncome); $sumInc->fetch(); $sumInc->close();

$sumExp = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND occurred_at BETWEEN ? AND ?");
$sumExp->bind_param('iss', $uid, $fromDate, $toDate);
$sumExp->execute(); $sumExp->bind_result($totalExpense); $sumExp->fetch(); $sumExp->close();
$balance = $totalIncome - $totalExpense;

/* งบประมาณ */
$bsql = "
  SELECT b.budget_id, b.category_id, b.limit_amount, b.period_start, b.period_end,
         c.name AS category, COALESCE(SUM(e.amount),0) AS used_amount
  FROM budgets b
  LEFT JOIN categories c ON c.category_id = b.category_id
  LEFT JOIN expenses e
         ON e.user_id = b.user_id
        AND e.category_id = b.category_id
        AND e.occurred_at BETWEEN b.period_start AND b.period_end
        AND e.occurred_at BETWEEN ? AND ?
  WHERE b.user_id = ?
    AND b.period_end >= ?
    AND b.period_start <= ?
  GROUP BY b.budget_id, b.category_id, b.limit_amount, b.period_start, b.period_end, c.name
  ORDER BY b.period_start DESC, b.budget_id DESC";
$bst = $conn->prepare($bsql);
$bst->bind_param('issss', $fromDate, $toDate, $uid, $fromDate, $toDate);
$bst->execute();
$budgets = $bst->get_result()->fetch_all(MYSQLI_ASSOC);
$bst->close();

/* เป้าหมาย */
$gsql = "
  SELECT g.goal_id, g.name, g.target_amount, g.due_date,
         COALESCE(SUM(t.amount),0) AS saved_total
  FROM goals g
  LEFT JOIN goaltransactions t ON t.goal_id = g.goal_id
  WHERE g.user_id = ?
  GROUP BY g.goal_id, g.name, g.target_amount, g.due_date
  ORDER BY COALESCE(g.due_date,'9999-12-31'), g.goal_id";
$gst = $conn->prepare($gsql);
$gst->bind_param('i', $uid);
$gst->execute();
$goals = $gst->get_result()->fetch_all(MYSQLI_ASSOC);
$gst->close();

/* สรุปรายเดือน */
$incStmt = $conn->prepare("
  SELECT DATE_FORMAT(occurred_at,'%Y-%m') ym, COALESCE(SUM(amount),0) total
  FROM income WHERE user_id=? AND occurred_at BETWEEN ? AND ? GROUP BY ym ORDER BY ym");
$incStmt->bind_param('iss', $uid, $fromDate, $toDate);
$incStmt->execute();
$incRes = $incStmt->get_result();
$incByMonth = []; while ($r = $incRes->fetch_assoc()) $incByMonth[$r['ym']] = (float)$r['total'];
$incStmt->close();

$expStmt = $conn->prepare("
  SELECT DATE_FORMAT(occurred_at,'%Y-%m') ym, COALESCE(SUM(amount),0) total
  FROM expenses WHERE user_id=? AND occurred_at BETWEEN ? AND ? GROUP BY ym ORDER BY ym");
$expStmt->bind_param('iss', $uid, $fromDate, $toDate);
$expStmt->execute();
$expRes = $expStmt->get_result();
$expByMonth = []; while ($r = $expRes->fetch_assoc()) $expByMonth[$r['ym']] = (float)$r['total'];
$expStmt->close();

$ymList = [];
$cur = new DateTime($fromDate);
$end = new DateTime($toDate); $end->modify('first day of next month');
while ($cur < $end) { $ymList[] = $cur->format('Y-m'); $cur->modify('+1 month'); }

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>แดชบอร์ด | Finance</title>
<style>
body {
  background: #f5f7fa;
  font-family: 'Poppins', sans-serif;
  margin: 0;
  color: #333;
}

/* container */
.wrap {
  max-width: 1150px;
  margin: 30px auto;
  background: #fff;
  padding: 28px 40px;
  border-radius: 18px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* title */
h2 {
  color: #4ca56d;
  font-size: 22px;
  margin: 10px 0 18px;
}

/* toolbar */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin: 10px 0 25px;
}
input[type="month"] {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-family: inherit;
}
.btn {
  background: #5fbf7f;
  color: #fff;
  border: none;
  padding: 8px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.3s;
}
.btn:hover { background: #4ca56d; }
.btn.secondary {
  background: #e5e7eb;
  color: #333;
}

/* cards summary */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 18px;
  margin-bottom: 30px;
}
.card {
  background: #f9fdf9;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border: 1px solid #eaeaea;
}
.card h4 { margin: 0 0 8px; color: #666; }
.card .v {
  font-size: 26px;
  font-weight: 700;
  color: #222;
}

/* section */
h3 {
  color: #4ca56d;
  margin-top: 40px;
  font-size: 18px;
}

/* box area */
.box {
  background: #f8fdf9;
  border-radius: 14px;
  border: 1px solid #e8f0e8;
  padding: 15px;
  margin-top: 8px;
}

/* tables */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 8px;
}
th, td {
  padding: 10px 8px;
  border-bottom: 1px solid #eaeaea;
}
th {
  text-align: left;
  background: #eaf7ef;
  color: #333;
  font-weight: 600;
}
tr:hover { background: #f9f9f9; }

.pos { color: #22c55e; font-weight: 600; }
.neg { color: #dc2626; font-weight: 600; }

@media (max-width: 768px) {
  .wrap { padding: 20px; }
  .cards { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="wrap">
  <h2>สรุปภาพรวม</h2>

  <form class="toolbar" method="get">
    <label>เลือกช่วงเดือน:</label>
    <input type="month" name="from_month" value="<?=esc($fromMonth)?>">
    <span>ถึง</span>
    <input type="month" name="to_month" value="<?=esc($toMonth)?>">
    <button class="btn">แสดงผล</button>
    <a class="btn secondary" href="dashboard.php?from_month=<?=date('Y-m')?>&to_month=<?=date('Y-m')?>">เดือนนี้</a>
  </form>

  <!-- summary cards -->
  <div class="cards">
    <div class="card"><h4>รายรับทั้งหมด</h4><div class="v"><?=number_format($totalIncome,2)?></div></div>
    <div class="card"><h4>รายจ่ายทั้งหมด</h4><div class="v"><?=number_format($totalExpense,2)?></div></div>
    <div class="card"><h4>คงเหลือ</h4><div class="v"><?=number_format($balance,2)?></div></div>
  </div>

  <!-- budgets -->
  <h3>งบที่ตั้ง </h3>
  <div class="box">
    <table>
      <tr><th>ช่วงงบ</th><th>หมวด</th><th style="text-align:right">วงเงิน</th><th style="text-align:right">ใช้ไป</th><th style="text-align:right">คงเหลือ</th></tr>
      <?php foreach($budgets as $b):
        $remain = (float)$b['limit_amount'] - (float)$b['used_amount'];
        $over = $remain < 0;
      ?>
      <tr>
        <td><?=esc($b['period_start'])?> – <?=esc($b['period_end'])?></td>
        <td><?=esc($b['category'] ?? '-')?></td>
        <td style="text-align:right"><?=number_format((float)$b['limit_amount'],2)?></td>
        <td style="text-align:right"><?=number_format((float)$b['used_amount'],2)?></td>
        <td style="text-align:right" class="<?= $over?'neg':'pos' ?>"><?=number_format($remain,2)?></td>
      </tr>
      <?php endforeach; if(!$budgets): ?>
      <tr><td colspan="5">ไม่มีงบที่ทับซ้อนกับช่วงที่เลือก</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- goals -->
  <h3>เป้าหมายที่สร้างไว้</h3>
  <div class="box">
    <table>
      <tr><th>เป้าหมาย</th><th>กำหนด</th><th style="text-align:right">เป้าหมาย</th><th style="text-align:right">ออมแล้ว</th><th style="text-align:right">คงเหลือ</th><th style="text-align:right">ความคืบหน้า</th></tr>
      <?php foreach($goals as $g):
        $saved = (float)$g['saved_total'];
        $target = (float)$g['target_amount'];
        $remainG = $target - $saved;
        $pct = $target > 0 ? max(0, min(100, ($saved/$target)*100)) : 0;
      ?>
      <tr>
        <td><?=esc($g['name'])?></td>
        <td><?=esc($g['due_date'] ?? '-')?></td>
        <td style="text-align:right"><?=number_format($target,2)?></td>
        <td style="text-align:right"><?=number_format($saved,2)?></td>
        <td style="text-align:right" class="<?=($remainG<=0)?'pos':''?>"><?=number_format($remainG,2)?></td>
        <td style="text-align:right"><?=number_format($pct,1)?>%</td>
      </tr>
      <?php endforeach; if(!$goals): ?>
      <tr><td colspan="6">ยังไม่มีเป้าหมาย</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- monthly summary -->
  <h3>สรุปรายเดือน (<?=esc($fromMonth)?> ถึง <?=esc($toMonth)?>)</h3>
  <div class="box">
    <table>
      <tr><th>เดือน</th><th style="text-align:right">รับ</th><th style="text-align:right">จ่าย</th><th style="text-align:right">คงเหลือ</th></tr>
      <?php $grandInc=0; $grandExp=0;
      foreach ($ymList as $ym):
        $inc = $incByMonth[$ym] ?? 0;
        $exp = $expByMonth[$ym] ?? 0;
        $bal = $inc - $exp;
        $grandInc += $inc; $grandExp += $exp;
      ?>
      <tr>
        <td><?=esc(date('M Y', strtotime($ym.'-01')))?></td>
        <td style="text-align:right"><?=number_format($inc,2)?></td>
        <td style="text-align:right"><?=number_format($exp,2)?></td>
        <td style="text-align:right"><?=number_format($bal,2)?></td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <th style="text-align:right">รวม</th>
        <th style="text-align:right"><?=number_format($grandInc,2)?></th>
        <th style="text-align:right"><?=number_format($grandExp,2)?></th>
        <th style="text-align:right"><?=number_format($grandInc-$grandExp,2)?></th>
      </tr>
    </table>
  </div>
</div>
</body>
</html>
