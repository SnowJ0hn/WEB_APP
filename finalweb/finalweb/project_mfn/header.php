<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// ดึงข้อมูลผู้ใช้
$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$user_email = $_SESSION['email'] ?? null;

if (!$user_name || !$user_role || !$user_email) {
    if (isset($conn)) {
        $res = $conn->query("SELECT name, role, email FROM users WHERE user_id = $user_id");
        if ($res && $res->num_rows > 0) {
            $u = $res->fetch_assoc();
            $user_name  = $user_name  ?: ($u['name'] ?? 'User');
            $user_role  = $user_role  ?: ($u['role'] ?? 'user');
            $user_email = $user_email ?: ($u['email'] ?? 'user@example.com');
        }
    }
}

// ตัวอักษรแรกจากอีเมล
$avatar_letter = strtoupper(substr($user_email, 0, 1));

// หน้าปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);
?>

<header style="background-color:#fff; color:#333; 
               box-shadow:0 2px 6px rgba(0,0,0,0.1);
               position:sticky; top:0; z-index:100;
               display:flex; align-items:center; justify-content:space-between;
               padding:10px 20px; flex-wrap:wrap;">

    <div class="logo" style="font-size:1.2em; font-weight:700; color:#0066ff;">
        💰 Finance Manager
    </div>

    <!-- เมนูหลัก -->
    <nav style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="dashboard.php" class="nav-item <?= $current_page=='dashboard.php'?'active':'' ?>">Dashboard</a>
        <a href="add_income.php" class="nav-item <?= $current_page=='add_income.php'?'active':'' ?>">เพิ่มรายรับ</a>
        <a href="add_expense.php" class="nav-item <?= $current_page=='add_expense.php'?'active':'' ?>">เพิ่มรายจ่าย</a>
        <a href="view_income.php" class="nav-item <?= $current_page=='view_income.php'?'active':'' ?>">รายการรับ</a>
        <a href="view_expenses.php" class="nav-item <?= $current_page=='view_expenses.php'?'active':'' ?>">รายการจ่าย</a>
        <a href="manage_categories.php" class="nav-item <?= $current_page=='manage_categories.php'?'active':'' ?>">เพิ่มหมวดหมู่</a>
        <a href="stats.php" class="nav-item <?= $current_page=='stats.php'?'active':'' ?>">สถิติ/กราฟ</a>
        <a href="add_budget.php" class="nav-item <?= $current_page=='add_budget.php'?'active':'' ?>">ตั้งงบ</a>
        <a href="budgets.php" class="nav-item <?= $current_page=='budgets.php'?'active':'' ?>">งบทั้งหมด</a>
        <a href="add_goal.php" class="nav-item <?= $current_page=='add_goal.php'?'active':'' ?>">สร้างเป้าหมาย</a>
        <a href="goals.php" class="nav-item <?= $current_page=='goals.php'?'active':'' ?>">เป้าหมาย</a>
        <?php if($user_role === 'admin') { ?>
            <a href="manage_users.php" class="nav-item <?= $current_page=='manage_users.php'?'active':'' ?>">ผู้ใช้</a>
        <?php } ?>
    </nav>

    <!-- โปรไฟล์ -->
    <div class="profile-dropdown" style="position: relative;">
        <div class="profile-info" onclick="toggleDropdown()" 
             style="display:flex; align-items:center; cursor:pointer;">
             
            <div style="width:36px; height:36px; border-radius:50%;
                        background-color:#007bff; color:#fff; display:flex;
                        align-items:center; justify-content:center;
                        font-weight:bold; margin-right:10px;">
                <?php echo htmlspecialchars($avatar_letter); ?>
            </div>

            <div style="display:flex; flex-direction:column; line-height:1.2;">
                <span style="font-weight:bold;"><?php echo htmlspecialchars($user_name); ?></span>
                <span style="font-size:0.85em; opacity:0.7;"><?php echo ucfirst($user_role); ?></span>
            </div>
        </div>

        <div id="dropdownMenu"
             style="display:none; position:absolute; right:0; top:45px;
                    background-color:#ffffff; color:#333;
                    border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2);
                    width:170px; overflow:hidden;">
            <a href="profile.php" style="display:block; padding:10px 15px; text-decoration:none; color:inherit;">👤 Profile</a>
            <hr style="margin:0;">
            <a href="logout.php" style="display:block; padding:10px 15px; text-decoration:none; color:inherit;">🚪 Logout</a>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const menu = document.getElementById("dropdownMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }
        document.addEventListener("click", function(e) {
            const menu = document.getElementById("dropdownMenu");
            const profile = document.querySelector(".profile-info");
            if (menu && !profile.contains(e.target)) menu.style.display = "none";
        });
    </script>

    <style>
        .nav-item {
            text-decoration:none;
            color:#5fbf7f;
            padding:6px 12px;
            border-radius:8px;
            border:1px solid transparent;
            transition:0.2s;
        }
        .nav-item:hover {
            background-color:#f0f5ff;
            border:1px solid #cce0ff;
        }
        .active {
            background-color:#e7f0ff;
            border:1px solid #99c2ff;
        }
        .profile-dropdown a:hover {
            background-color:rgba(0,0,0,0.05);
        }
    </style>
</header>
