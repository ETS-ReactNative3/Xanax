<?php
$_GET['func'] = "phpinfo";
$func = $_GET['func'];
$some_array = array(0, 1, 2, 3);
$new_array = array_map($func, $some_array);
?>
