/*
var pollinfo = document.getElementById('poll-info');

// TODO nach vorgegebener Zeit abbrechen
setInterval(async function () {
    result = await postData('dcconnectorpoll.php', {
        action: "poll"
    });
    console.log(result);
    if (result != '') {

        check = JSON.parse(result);
        if (check.status == 'polling') {
            //pollinfo.innerHTML = 'Polling';
        } else if (check.status == 'request_accepted') {
            //pollinfo.innerHTML = 'Request accepted';
            window.location.reload();
        } else if (check.status == 'bad_request') {
            pollinfo.style.display = 'block';
            pollinfo.innerHTML = 'Bad request';
        }
    }
}, 3000);

async function postData(url, data, jsonResponse = false) {
    const response = await fetch(url, {
        method: 'POST',
        body: new URLSearchParams(data)
    }).catch((error) => {
        console.error('Error:', error);
    });
    if (jsonResponse) {
        return response.json();
    }
    return response.text();
}
    */

var pollinfo = document.getElementById('poll-info');

// TODO nach vorgegebener Zeit abbrechen
setInterval(async function () {
    let result = await postData('dcconnectorpoll.php', {
        action: "poll"
    });

    //console.log("Server response:", result);  // Log für die Serverantwort

    // Falls die Antwort leer ist, Prozess fortsetzen
    if (result === '') {
        console.log("Leere Antwort erhalten, Prozess wird fortgesetzt.");
        // Du kannst hier andere Aktionen durchführen oder einfach den nächsten Durchlauf abwarten.
    } else {
        try {
            //let check = JSON.parse(result);
            let check = (result);
            if (check.status == 'polling') {
                //pollinfo.innerHTML = 'Polling';
            } else if (check.status == 'request_accepted') {
                //pollinfo.innerHTML = 'Request accepted';
                window.location.reload();
            } else if (check.status == 'bad_request') {
                pollinfo.style.display = 'block';
                pollinfo.innerHTML = 'Bad request';
            }
        } catch (error) {
            console.error("JSON-Parsing-Fehler:", error);
            pollinfo.style.display = 'block';
            pollinfo.innerHTML = 'Fehlerhafte Antwort vom Server: ' + result;
        }
    }
}, 3000);

async function postData(url, data, jsonResponse = false) {
    const response = await fetch(url, {
        method: 'POST',
        body: new URLSearchParams(data)
    }).catch((error) => {
        console.error('Fetch-Fehler:', error);
        return null;  // Abbruch bei Fehler
    });

    if (!response || response.status === 204) return '';  // Prüfe auf leere Antwort oder 204 No Content

    if (jsonResponse) {
        try {
            return await response.json();
        } catch (error) {
            console.error('Fehler beim Parsen der JSON-Antwort:', error);
            return '';
        }
    }
    return response.text();
}
