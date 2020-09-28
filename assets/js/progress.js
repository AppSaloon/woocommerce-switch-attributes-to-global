const elMessage = document.getElementById( 'message' );
const elProgress = document.getElementById( 'progress_bar' );
const elBtnProgress = document.getElementById( 'btn_start_process' );
const elPercentageCompleted = document.getElementById( 'percentage_completed' );
const elAmountCompleted = document.getElementById( 'amount_completed' );
const maxProducts = elProgress.max;

function startProcess() {
    const action = ap_progress.action;
    const promises = [];
    for ( let i = 0; i < maxProducts; i++ ) {
        const promise = new Promise( function ( resolve ) {
            const opts = 'action=' + action + '&offset=' + i + '&max=' + maxProducts;
            checkProduct( opts ).then( resolve );
        } )
        promises.push( promise )
    }

    elBtnProgress.innerHTML = 'IN PROGRESS';
    elBtnProgress.disabled = true;

    Promise.all( promises ).then(
        function () {
            console.log( 'David' )
            elMessage.innerText += 'The process is complete';
            elBtnProgress.innerHTML = 'Start script';
            elBtnProgress.disabled = false;
        }
    );
}

async function checkProduct( opts ) {
    const response = await fetch( ajaxurl, {
        method: 'post',
        credentials: 'same-origin',
        body: opts,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        }
    } );
    const data = await response.json();

    elProgress.value += 1;
    elAmountCompleted.innerHTML = elProgress.value;
    elPercentageCompleted.innerHTML =  Math.floor( ( elProgress.value / maxProducts ) * 100 );

    if ( data.error === true ) {
        elMessage.innerHTML += ' ERROR: ' + data.errorMessage + '<br>';
    } else {
        elMessage.innerHTML += data.message + '<br>';
        elMessage.scrollTop = elMessage.scrollHeight;
    }
}