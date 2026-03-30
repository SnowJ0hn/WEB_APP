<?php
if (!function_exists('esc')) {
  function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('need_login')) {
  function need_login(){
    if (session_status()===PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
  }
}
if (!function_exists('isAdmin')) {
  function isAdmin(){ return isset($_SESSION['role']) && $_SESSION['role']==='admin'; }
}
