<?php
header('Content-Type: text/plain');
$raw = file_get_contents('php://input');
echo "RAW: [" . $raw . "]\n";
$headers = getallheaders();
echo "HEADERS:\n";
foreach ($headers as $k => $v) {
    echo "$k: $v\n";
}
