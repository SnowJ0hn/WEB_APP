<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['user_id'];

// ✅ ตรวจสอบชื่อคอลัมน์ของตาราง users
$check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'user_id'");
$user_col = ($check_col && $check_col->num_rows > 0) ? "user_id" : "id";

// ✅ ดึงข้อมูลผู้ใช้
$user_query = $conn->query("SELECT name, email, role FROM users WHERE $user_col = $user_id");
if (!$user_query) { die("<b>SQL Error:</b> " . $conn->error); }
$user = $user_query->fetch_assoc();

// ✅ ฟังก์ชันรวมข้อมูลสั้น ๆ
function get_value($conn, $sql) {
    $q = $conn->query($sql);
    if ($q && $q->num_rows > 0) {
        $row = $q->fetch_assoc();
        return array_values($row)[0] ?? 0;
    }
    return 0;
}

// ✅ ดึงข้อมูลสรุป
$total_income = get_value($conn, "SELECT SUM(amount) FROM income WHERE user_id=$uid");
$total_expense = get_value($conn, "SELECT SUM(amount) FROM expenses WHERE user_id=$uid");
$total_budget = get_value($conn, "SELECT SUM(limit_amount) FROM budgets WHERE user_id=$uid");
$total_goal = get_value($conn, "SELECT SUM(target_amount) FROM goals WHERE user_id=$uid");

// Avatar ตัวแรกของ email
$avatar = strtoupper(substr($user['email'], 0, 1));
$total_sum = ($total_income ?? 0) + ($total_expense ?? 0);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์ผู้ใช้</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 20px;
    }
    .profile-container {
        max-width: 850px;
        margin: 30px auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        padding: 25px 40px;
    }
    .profile-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }
    .avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: #007bff;
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
        font-weight: bold;
    }
    h2 {
        margin: 0;
        color: #333;
    }
    .info {
        margin-top: 10px;
        line-height: 1.6;
    }
    .stats {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 30px;
    }
    .card {
        flex: 1;
        min-width: 180px;
        background: #f1f5ff;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .card h3 {
        margin: 0;
        color: #0066ff;
        font-size: 1.2em;
    }
    .card p {
        margin: 5px 0 0;
        color: #333;
        font-weight: 600;
    }
</style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <div class="avatar"><?php echo $avatar; ?></div>
        <div>
            <h2><?php echo htmlspecialchars($user['name']); ?></h2>
            <div class="info">
                <strong>ยศ:</strong> <?php echo ucfirst($user['role']); ?><br>
                <strong>อีเมล:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </div>
        </div>
    </div>

    <hr>

    <h3 style="color:#0066ff;" >📊 ภาพรวม</h3>
    <div class="stats">
        <div class="card">
            <h3>💰 งบที่ตั้ง</h3>
            <p><?php echo number_format($total_budget, 2); ?> บาท</p>
        </div>
        <div class="card">
            <h3>🎯 เป้าหมาย คืบหน้า</h3>
            <p><?php echo $total_goal; ?> บาท</p>
        </div>
        <div class="card">
            <h3>📈 รายรับรวม</h3>
            <p style="color:green;"><?php echo number_format($total_income, 2); ?> บาท</p>
        </div>
        <div class="card">
            <h3>📉 รายจ่ายรวม</h3>
            <p style="color:red;"><?php echo number_format($total_expense, 2); ?> บาท</p>
        </div>
    </div>

    <!-- ✅ กราฟโดนัท -->
    <div class="chart-container" style="margin-top:40px; text-align:center; position:relative;">
        <h3 style="margin-top:30px; color:#0066ff;">📈 สัดส่วนรายรับ-รายจ่าย</h3>

        <div style="width:220px; height:220px; margin:0 auto; position:relative;">
            <canvas id="financeChart"></canvas>
            <div id="chartCenterText" 
                 style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);
                        font-size:1.1em; color:#333; font-weight:bold;">
                <?php echo number_format($total_sum, 2); ?> ฿
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('financeChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['รายรับ', 'รายจ่าย'],
        datasets: [{
            data: [<?php echo $total_income; ?>, <?php echo $total_expense; ?>],
            backgroundColor: ['#4CAF50', '#FF5252'],
            hoverOffset: 6,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        cutout: '70%',
        aspectRatio: 1,
        layout: { padding: 5 },
        responsive: true,
        plugins: {
            legend: { 
                position: 'bottom',
                labels: { boxWidth: 15, font: { size: 13 } }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.raw || 0;
                        return label + ': ' + value.toLocaleString() + ' บาท';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
