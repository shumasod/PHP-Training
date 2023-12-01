<?php
$title='手数をおかけしますが';

ob_start();
require 'header.php';
$contests = ob_get_clean();
echo $contests;

require 'rss.php';
require 'main.php';
require 'sub1.php';
require 'sub2.php';
require 'footer.php';
?>