
var pollinfo = document.getElementById('poll-info');
setInterval(async function () {
    let result = await postData('tsconnectorpoll.php', {
        action: "poll"
    });

    console.log("Raw response:", result); // Debug output

    if (result) {
        try {
            //let check = (result);
            console.log("result = " + result); 
            let check = JSON.parse(result);
            console.log("Parsed status:", check.status);

            switch(check.status) {
                case 'request_accepted':
                    console.log("Request accepted, reloading...");
                    window.location.reload();
                    break;
                case 'polling':
                    console.log("Still polling...");
                    break;
                case 'bad_request':
                    pollinfo.style.display = 'block';
                    pollinfo.innerHTML = 'Bad request';
                    break;
                default:
                    console.log("Unknown status:", check.status);
            }
        } catch (error) {
            console.error("JSON parsing error:", error, "Response was:", result);
        }
    }
}, 3000);

async function postData(url, data, jsonResponse = false) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.text();
    } catch (error) {
        console.error('Fetch error:', error);
        return null;
    }
}