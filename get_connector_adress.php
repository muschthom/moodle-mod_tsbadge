//Call this page to get the current address of the wallet
//Settingspage: https://dev-isy.th-luebeck.de/moodle3/mod/ilddigitalcert/dcconnectorsettings.php

<?php
//$url = "https://wallet.trainspot.dbis.rwth-aachen.de/api/v2/Account/IdentityInfo";
$url = "https://dev-isy.th-luebeck.de/trainspot/api/v2/Account/IdentityInfo";
//$xapikey = "7H5ijx7eVP8uz058KFRQ";
$xapikey = "an-api-key";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$headers = array();
$headers[] = 'X-API-Key: ' . $xapikey;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);
echo $result; 
// $result enthält die Antwort des Servers
