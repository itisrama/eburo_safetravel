<?php
$filePath	= base64_decode(urldecode($_GET['code']));
$fileName 	= basename($filePath);
$fileSize	= sprintf("%u", filesize($filePath));

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('Content-Transfer-Encoding: binary');
header('Connection: Keep-Alive');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $fileSize);

readfile($filePath);

exit();