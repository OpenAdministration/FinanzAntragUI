<?php

$fname = $_GET['fname'];
$id = $_GET['id'];
$file = '/usr/share/FinanzAntragUI/storage/'.$id.'/'.$fname;
#echo $file;
header('Content-Disposition: attachment; filename="'. basename($file) . '"');
header("Content-type: application/pdf");
header('Content-Length: ' . filesize($file));
readfile($file);
?>
