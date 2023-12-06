<?php
$title='お勉強サイト';

ob_start();
require 'header.php';
$contests = ob_get_clean();
echo $contests;

//Contestsの表示

require 'rss.php';
require 'main.php';
require 'sub1.php';
require 'sub2.php';
require 'footer.php';
?>
