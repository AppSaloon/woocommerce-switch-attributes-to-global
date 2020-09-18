const elMessage = document.getElementById( 'message' );
const elProgress = document.getElementById( 'progress_bar' );
const elBtnProgress = document.getElementById( 'btn_start_process' );

function startProcess() {
    const action = ap_progress.action;
    const offset = elProgress.value;
    const max = elProgress.max;

    const opts = 'action=' + action + '&offset=' + offset + '&max=' + max;

    checkProduct( opts, elProgress, elMessage );
}

function checkProduct( opts, elProgress, elMessage ) {
    fetch( ajaxurl, {
        method: 'post',
        credentials: 'same-origin',
        body: opts,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        }
    } ).then( function ( response ) {
        return response.json();
    } ).then( function ( data ) {
        if ( data.error === true ) {
            elMessage.innerText = 'ERROR: ' + data.errorMessage;
            elBtnProgress.innerHTML = 'ERROR';
            elBtnProgress.disabled = true;
            return
        }

        elProgress.value = data.value;

        elProgress.setAttribute( 'data-label', data.procent + '%' );

        if ( data.complete === false ) {
            elMessage.innerHTML += data.message + '\n';
            elMessage.scrollTop = elMessage.scrollHeight;
            startProcess();
        } else {
            elMessage.innerText = 'The process is complete';
        }
    } );
}