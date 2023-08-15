/**
 * Send API requests using Fetch API to PHP backend.
 * 1. Poll PHP backend to check if proof request has been verified.
 * 2. When the proof request has been verified, redirect to
 *    authentication page.
 * 
 * Polling uses some code from https://blog.openreplay.com/forever-functional-three-ways-of-polling/
*/

/** Waits time (used to space out API poll requests) */
const timeout = (time) =>
    new Promise((resolve) => setTimeout(resolve, time));


/** Get the state of the proof request by polling Flask backend */
const getRequestState = async (pres_ex_id) => {

    response = await fetch('/wp-json/backend/verified', {
        method: 'POST',
        body: pres_ex_id
    })
    //console.log(response);
    response = await response.json();
    response = JSON.parse(response);
    //console.log(response);
    return response["state"] === 'verified';
}

/** Call getRequestState every time seconds for a certain pres_ex_id.
 * Keep polling until the state is verified.
 * Then, call sendRedirectRequest to go to verified page.
 * Times out after max_time elapsed.
*/ 
function startPolling(time, max_time, pres_ex_id) {
    let polling = true;
    let timed_out = false;
    let verified;
    const start_time = Date.now();                
    //console.log('In startPolling');        
  
    (async function doPolling() {
        while (polling) {                                        
            try {
                let result;
                if (polling) {                        
                    await timeout(time);
                }
                if (polling) {                             
                    result = await getRequestState(pres_ex_id);
                    //console.log(result);
                }
                if (polling && (result || start_time + max_time < Date.now())) {     

                    if (start_time + max_time < Date.now()) timed_out = true;
                    verified = result;
        
                    stopPolling();
                }
            } catch (e) {                                 
                console.log(e);
                stopPolling();
                throw new Error("Polling cancelled due to API error");
            }
        }
    })();                                            
  
    async function stopPolling() {                                  
        if (polling) {
            console.log("Stopping polling...");
            polling = false;

            if (timed_out) {
                //console.log('time_out');
                window.location = '/wp-login.php?timeout=true';
            } else if (verified) {
                //console.log('verified');
                window.location = '/wp-login.php?pres_ex_id=' + pres_ex_id;
            }
        
        } else {
            console.log("Polling was already stopped...");
        }
    }
  
    return stopPolling;          
}

increment = 200;
max_time = 60000;
pres_ex_id = document.getElementById('pres_ex_id').value;
startPolling(increment, max_time, pres_ex_id); // poll every two seconds for up to one minute before timing out