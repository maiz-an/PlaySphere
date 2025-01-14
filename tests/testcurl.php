<?php
$ch = curl_init("https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    echo 'SSL verification successful!';
}
curl_close($ch);
?>
