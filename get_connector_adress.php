<?php

function fetch_and_display_json()
{

    require('../../config.php');
    require "locallib.php";
    global $DB, $CFG, $USER;


    $host = $DB->get_record('config', ['name' => 'mod_tsbadge_domain_url'])->value;
    $url = $host . "/api/v2/Account/IdentityInfo";

    $xapikey = $DB->get_record('config', ['name' => 'mod_tsbadge_api_key'])->value;



    // Initialisiere cURL-Sitzung
    $ch = curl_init();

    // Setze cURL-Optionen
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $headers = array();
    $headers[] = 'X-API-Key: ' . $xapikey;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Führe die cURL-Anfrage aus und speichere die Antwort
    $response = curl_exec($ch);

    // Überprüfe auf cURL-Fehler
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        header('Content-Type: application/json');
        echo json_encode(['error' => $error_msg]);
        return;
    }

    // Schließe die cURL-Sitzung
    curl_close($ch);

    // Setze den Content-Type auf JSON
    header('Content-Type: application/json');

    // Gib die Antwort als JSON aus
    echo $response;
}

// Beispiel-Aufruf der Funktion mit einer URL
fetch_and_display_json();

