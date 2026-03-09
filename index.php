<?php 
require_one 'db.php'
header('Location: '.(isLoggedIn()?'dashboard.php':'login.php'
exit();
?>
