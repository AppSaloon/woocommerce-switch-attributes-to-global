<?php /* @var $max int */ ?>

<h1>Progress bar</h1>

<h3>Transformer script</h3>
<p>This script will check every product. It will turn the product attributes into global WooCommerce attributes.
    1 value in progress bar means 1 product handled.</p>

<div id=""></div>

<div><span id="percentage_completed">0</span>% completed (<span
            id="amount_completed">0</span>/<?php echo $max; ?>)
</div>
<progress id="progress_bar" max="<?php echo $max; ?>" value="0" data-label=""></progress>

<button id="btn_start_process" class="button button-primary">Start script</button>

<p><strong>Note:</strong> The progress will be updated by ajax calls.</p>
<div style="display: flex; max-height: 500px;">
    <div id="message" style="width: 100%; white-space: pre; overflow-y: auto"></div>

    <div id="failed_products" style="width: 100%; white-space: pre; overflow-y: auto"></div>
</div>
<?php
