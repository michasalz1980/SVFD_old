<?php
/**
 * logout.php - expliziter Logout-Endpunkt
 */
require_once 'auth_freibad.php';

$auth = new FreibadDabringhausenAuth();
$auth->logout();

header('Location: /userManagement/login.php?logout=1');
exit;
