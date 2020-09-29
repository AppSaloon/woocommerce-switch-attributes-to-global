const elMessage = document.getElementById( 'message' );
const elProgress = document.getElementById( 'progress_bar' );
const elBtnProgress = document.getElementById( 'btn_start_process' );
const elPercentageCompleted = document.getElementById( 'percentage_completed' );
const elAmountCompleted = document.getElementById( 'amount_completed' );
const elFailedProducts = document.getElementById( 'failed_products' );
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
            elMessage.innerHTML += 'The process is complete <br>';
            elBtnProgress.innerHTML = 'Start script';
            elBtnProgress.disabled = false;
            elProgress.value = 0;
            elAmountCompleted.innerHTML = '0';
            elPercentageCompleted.innerHTML = '0';
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
        elMessage.innerHTML += ' ERROR: ' + data.errorMessage + ', productId: ' + data.productId + '<br>';
        elFailedProducts.innerHTML += data.productId + '<br>';
    } else {
        elMessage.innerHTML += data.message + '<br>';
        elMessage.scrollTop = elMessage.scrollHeight;
    }
}