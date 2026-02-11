<?php

// Konfiguration
$fritzboxUrl = 'http://iykjlt0jy435sqad.myfritz.net'; // IP oder Hostname der FritzBox
$username = 'michasalz';
$password = 'hoh8Xoi38!';

// Service-URL für WLAN-Geräte
$serviceControlURL = '/upnp/control/wlanconfig1'; 
$soapAction = 'urn:dslforum-org:service:WLANConfiguration:1#GetTotalAssociations';

// Funktion: SOAP Request senden
function sendSoapRequest($url, $service, $action, $username, $password)
{
    $xml = '<?xml version="1.0" encoding="utf-8"?>' .
           '<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
           'xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">' .
           '<s:Body>' .
           '<u:' . $action . ' xmlns:u="urn:dslforum-org:service:WLANConfiguration:1" />' .
           '</s:Body>' .
           '</s:Envelope>';

    $headers = [
        'Content-Type: text/xml; charset="utf-8"',
        'SoapAction: "urn:dslforum-org:service:WLANConfiguration:1#' . $action . '"'
    ];

    $ch = curl_init($url . $service);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die('cURL Error: ' . $error);
    }

    return $response;
}

// Anfrage senden
$response = sendSoapRequest($fritzboxUrl, $serviceControlURL, 'GetTotalAssociations', $username, $password);

// Ausgabe
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

?>
