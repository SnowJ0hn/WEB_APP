<?php
require 'config.php';
require 'utils.php';
need_login();

$uid=(int)$_SESSION['user_id'];
$conn->set_charset('utf8mb4');

function esc($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }

/* บันทึกเงินออมเพิ่ม */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='deposit'){
  $gid=(int)$_POST['goal_id']; 
  $amt=(float)($_POST['amount']??0); 
  $d = $_POST['occurred_at'] ?? date('Y-m-d');
  if($gid>0 && $amt>0){
    $st=$conn->prepare("INSERT INTO goaltransactions(goal_id,amount,occurred_at) VALUES(?,?,?)");
    $st->bind_param('ids',$gid,$amt,$d);
    $st->execute(); 
    $st->close();
  }
  header('Location: goals.php'); exit;
}

/* ลบรายการออม */
if(isset($_GET['del_tx'])){
  $tx=(int)$_GET['del_tx'];
  $st=$conn->prepare("DELETE FROM goaltransactions WHERE goal_tx_id=?");
  $st->bind_param('i',$tx);
  $st->execute(); $st->close();
  header('Location: goals.php'); exit;
}

/* ลบเป้าหมาย */
if(isset($_GET['del_goal'])){
  $gid=(int)$_GET['del_goal'];
  $conn->query("DELETE FROM goaltransactions WHERE goal_id=$gid");
  $st=$conn->prepare("DELETE FROM goals WHERE goal_id=? AND user_id=?");
  $st->bind_param('ii',$gid,$uid);
  $st->execute(); $st->close();
  header('Location: goals.php'); exit;
}

/* ดึงเป้าหมาย + ยอดออม */
$goals=$conn->query("
  SELECT g.goal_id,g.name,g.target_amount,g.due_date,
         COALESCE(SUM(t.amount),0) AS saved
  FROM goals g
  LEFT JOIN goaltransactions t ON g.goal_id=t.goal_id
  WHERE g.user_id=$uid
  GROUP BY g.goal_id
  ORDER BY g.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

/* รายการออมล่าสุด 20 รายการ */
$last=$conn->query("
  SELECT t.goal_tx_id,t.goal_id,t.amount,t.occurred_at,g.name
  FROM goaltransactions t 
  JOIN goals g ON t.goal_id=g.goal_id
  WHERE g.user_id=$uid
  ORDER BY t.occurred_at DESC, t.goal_tx_id DESC 
  LIMIT 20
")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>เป้าหมายเก็บออม</title>
<style>
  body{
    background:#f2f6f8;
    font-family:"Prompt",Tahoma,sans-serif;
    color:#333;
    margin:0;
  }
  .wrap{
    max-width:1100px;
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
    margin-top:10px;
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
    color:#475569;
    text-align:left;
    font-weight:600;
  }
  tr:hover td{ background:#f9fafb; }
  .progress{
    width:220px;
    background:#e5e7eb;
    border-radius:10px;
    overflow:hidden;
    height:16px;
    position:relative;
  }
  .bar{
    height:100%;
    color:#fff;
    text-align:right;
    font-size:12px;
    padding-right:6px;
    line-height:16px;
  }
  .bar.green{background:#16a34a;}
  .bar.yellow{background:#eab308;}
  .bar.red{background:#dc2626;}
  button{
    background:#16a34a;
    color:#fff;
    border:none;
    border-radius:6px;
    padding:6px 10px;
    cursor:pointer;
    transition:.2s;
  }
  button:hover{background:#15803d;}
  a.del{
    color:#dc2626;
    text-decoration:none;
    font-weight:500;
  }
  a.del:hover{text-decoration:underline;}
  .no-data{
    text-align:center;
    color:#999;
    padding:20px 0;
  }
  h3{margin-top:28px;color:#0284c7;}
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="wrap">
  <h2>🎯 เป้าหมายการออมเงิน</h2>
  <p><a class="btn" href="add_goal.php">+ สร้างเป้าหมายใหม่</a></p>

  <table>
    <tr>
      <th>ชื่อเป้าหมาย</th>
      <th style="text-align:right">เป้าหมาย (บาท)</th>
      <th style="text-align:right">ออมแล้ว</th>
      <th>คืบหน้า</th>
      <th>กำหนดเสร็จ</th>
      <th>บันทึกเพิ่ม</th>
      <th>ลบเป้าหมาย</th>
    </tr>
    <?php foreach($goals as $g):
      $pct = $g['target_amount']>0 ? min(100, round($g['saved']*100/$g['target_amount'],1)) : 0;
      $barColor = $pct < 70 ? 'green' : ($pct < 100 ? 'yellow' : 'red');
    ?>
    <tr>
      <td><?=esc($g['name'])?></td>
      <td style="text-align:right"><?=number_format($g['target_amount'],2)?></td>
      <td style="text-align:right"><?=number_format($g['saved'],2)?></td>
      <td>
        <div class="progress">
          <div class="bar <?=$barColor?>" style="width:<?=$pct?>%"><?=$pct?>%</div>
        </div>
      </td>
      <td><?=esc($g['due_date']??'-')?></td>
      <td>
        <form method="post" style="display:flex;gap:6px;align-items:center;">
          <input type="hidden" name="action" value="deposit">
          <input type="hidden" name="goal_id" value="<?=$g['goal_id']?>">
          <input type="number" step="0.01" name="amount" placeholder="จำนวน" required style="width:90px;padding:5px;">
          <input type="date" name="occurred_at" value="<?=date('Y-m-d')?>" style="padding:5px;">
          <button>บันทึก</button>
        </form>
      </td>
      <td><a class="del" href="?del_goal=<?=$g['goal_id']?>" onclick="return confirm('ลบเป้าหมายและรายการออมทั้งหมด?')">ลบ</a></td>
    </tr>
    <?php endforeach; if(!$goals): ?>
      <tr><td colspan="7" class="no-data">ยังไม่มีเป้าหมายออมเงิน</td></tr>
    <?php endif; ?>
  </table>

  <h3>💰 รายการออมล่าสุด</h3>
  <table>
    <tr>
      <th>วันที่</th>
      <th>ชื่อเป้าหมาย</th>
      <th style="text-align:right">จำนวน (บาท)</th>
      <th>ลบ</th>
    </tr>
    <?php foreach($last as $t): ?>
    <tr>
      <td><?=esc($t['occurred_at'])?></td>
      <td><?=esc($t['name'])?></td>
      <td style="text-align:right;color:#16a34a;"><?=number_format($t['amount'],2)?></td>
      <td><a class="del" href="?del_tx=<?=$t['goal_tx_id']?>" onclick="return confirm('ลบรายการออมนี้?')">ลบ</a></td>
    </tr>
    <?php endforeach; if(!$last): ?>
      <tr><td colspan="4" class="no-data">ยังไม่มีรายการออม</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
