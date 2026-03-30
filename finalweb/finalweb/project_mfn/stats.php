<?php
require_once 'config.php';
require_once 'utils.php';
need_login();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$uid = (int)$_SESSION['user_id'];

/* ===== รับช่วงเดือนและแปลงเป็นช่วงวันจริง ===== */
$from_month = $_GET['from_month'] ?? date('Y-m');
$to_month   = $_GET['to_month']   ?? date('Y-m');

$from_date = date('Y-m-01', strtotime($from_month . '-01'));
$to_date   = date('Y-m-t',  strtotime($to_month   . '-01'));

/* ====== ดึงยอดรายวันแบบ group by ====== */
$st = $conn->prepare("
  SELECT occurred_at, COALESCE(SUM(amount),0) AS total
  FROM income
  WHERE user_id=? AND occurred_at BETWEEN ? AND ?
  GROUP BY occurred_at ORDER BY occurred_at
");
$st->bind_param('iss', $uid, $from_date, $to_date);
$st->execute();
$res = $st->get_result();
$incByDay = [];
while ($r = $res->fetch_assoc()) $incByDay[$r['occurred_at']] = (float)$r['total'];
$st->close();

$st = $conn->prepare("
  SELECT occurred_at, COALESCE(SUM(amount),0) AS total
  FROM expenses
  WHERE user_id=? AND occurred_at BETWEEN ? AND ?
  GROUP BY occurred_at ORDER BY occurred_at
");
$st->bind_param('iss', $uid, $from_date, $to_date);
$st->execute();
$res = $st->get_result();
$expByDay = [];
while ($r = $res->fetch_assoc()) $expByDay[$r['occurred_at']] = (float)$r['total'];
$st->close();

/* ====== สร้างลิสต์วันในช่วงที่เลือก ====== */
$labels = [];
$incSeries = [];
$expSeries = [];
$cur = new DateTime($from_date);
$end = new DateTime($to_date);
while ($cur <= $end) {
  $d = $cur->format('Y-m-d');
  $labels[]    = $d;
  $incSeries[] = $incByDay[$d] ?? 0.0;
  $expSeries[] = $expByDay[$d] ?? 0.0;
  $cur->modify('+1 day');
}

/* ====== พาย/โดนัท: สัดส่วนรายจ่ายตามหมวด ====== */
$st = $conn->prepare("
  SELECT COALESCE(c.name,'(ไม่ระบุ)') AS label, SUM(e.amount) AS total
  FROM expenses e
  LEFT JOIN categories c ON e.category_id=c.category_id
  WHERE e.user_id=? AND e.occurred_at BETWEEN ? AND ?
  GROUP BY c.name HAVING total > 0 ORDER BY total DESC
");
$st->bind_param('iss', $uid, $from_date, $to_date);
$st->execute();
$res = $st->get_result();
$pieLabels = []; $pieValues = [];
while ($r = $res->fetch_assoc()) {
  $pieLabels[] = $r['label'];
  $pieValues[] = (float)$r['total'];
}
$st->close();

/* ====== การ์ดสรุป ====== */
$totalIncome  = array_sum($incSeries);
$totalExpense = array_sum($expSeries);
$balance      = $totalIncome - $totalExpense;

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สถิติ / กราฟ</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
  background:#f2f6f8;
  font-family:"Prompt",Tahoma,sans-serif;
  color:#333;
  margin:0;
}
.wrap {
  max-width:1100px;
  margin:90px auto 40px;
  background:#fff;
  padding:24px 30px;
  border-radius:16px;
  box-shadow:0 6px 20px rgba(0,0,0,0.08);
}
h2 { color:#0284c7; margin-bottom:16px; font-weight:600; }
.toolbar {
  display:flex; gap:10px; align-items:center; flex-wrap:wrap;
  margin-bottom:20px;
}
input[type=month] {
  padding:8px 10px;
  border:1px solid #d1d5db;
  border-radius:8px;
  font-size:14px;
}
.btn {
  background:#0284c7;
  color:#fff;
  border:none;
  padding:8px 16px;
  border-radius:8px;
  cursor:pointer;
  transition:0.2s;
}
.btn:hover { background:#0369a1; }
.btn.secondary {
  background:#e5e7eb; color:#111;
}
.cards {
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:12px;
  margin-bottom:24px;
}
.card {
  background:#f9fafb;
  border:1px solid #eee;
  border-radius:12px;
  padding:16px;
  text-align:center;
}
.card h4 { margin:0 0 6px 0; font-weight:600; }
.card .v { font-size:22px; font-weight:700; }
.card.income .v { color:#16a34a; }
.card.expense .v { color:#dc2626; }
.card.balance .v { color:#0284c7; }
canvas {
  max-width:100%;
  height:380px;
}
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">
  <h2>📊 สถิติ / กราฟ</h2>

  <form class="toolbar" method="get">
    <span>ช่วงเดือน:</span>
    <input type="month" name="from_month" value="<?=esc($from_month)?>">
    <span>ถึง</span>
    <input type="month" name="to_month" value="<?=esc($to_month)?>">
    <button class="btn">แสดง</button>
    <a class="btn secondary" href="stats.php?from_month=<?=date('Y-m')?>&to_month=<?=date('Y-m')?>">เดือนนี้</a>
  </form>

  <div class="cards">
    <div class="card income">
      <h4>รายรับช่วงที่เลือก</h4>
      <div class="v"><?=number_format($totalIncome,2)?></div>
    </div>
    <div class="card expense">
      <h4>รายจ่ายช่วงที่เลือก</h4>
      <div class="v"><?=number_format($totalExpense,2)?></div>
    </div>
    <div class="card balance">
      <h4>คงเหลือ</h4>
      <div class="v"><?=number_format($balance,2)?></div>
    </div>
  </div>

  <h3>📈 กราฟรายวัน</h3>
  <canvas id="line"></canvas>

  <h3 style="margin-top:24px">💰 สัดส่วนรายจ่ายตามหมวด</h3>
  <canvas id="pie"></canvas>
</div>

<script>
const labels   = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const incData  = <?= json_encode($incSeries, JSON_UNESCAPED_UNICODE) ?>;
const expData  = <?= json_encode($expSeries, JSON_UNESCAPED_UNICODE) ?>;
const pieLab   = <?= json_encode($pieLabels, JSON_UNESCAPED_UNICODE) ?>;
const pieVal   = <?= json_encode($pieValues, JSON_UNESCAPED_UNICODE) ?>;

/* ===== เส้นรายวัน ===== */
new Chart(document.getElementById('line'), {
  type:'line',
  data:{
    labels: labels,
    datasets:[
      {
        label:'รายรับ',
        data: incData,
        borderColor:'#16a34a',
        backgroundColor:'rgba(22,163,74,0.2)',
        fill:true,
        tension:.3,
        pointRadius:3
      },
      {
        label:'รายจ่าย',
        data: expData,
        borderColor:'#dc2626',
        backgroundColor:'rgba(220,38,38,0.2)',
        fill:true,
        tension:.3,
        pointRadius:3
      }
    ]
  },
  options:{
    responsive:true,
    plugins:{ legend:{ position:'top' } },
    scales:{ y:{ beginAtZero:true } }
  }
});

/* ===== พายโดนัทรายจ่าย ===== */
const pieColors = [
  '#ef4444','#f97316','#eab308','#22c55e','#06b6d4',
  '#3b82f6','#6366f1','#8b5cf6','#d946ef','#f43f5e'
];

new Chart(document.getElementById('pie'), {
  type:'doughnut',
  data:{
    labels: pieLab,
    datasets:[{ data: pieVal, backgroundColor: pieColors }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{ position:'right' }
    }
  }
});
</script>
</body>
</html>
