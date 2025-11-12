<?php
echo "✅ Test file works!";
echo "<br>Current directory: " . __DIR__;
echo "<br>File exists: " . (file_exists('login.php') ? 'YES' : 'NO');
?>
