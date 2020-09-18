<h1>Process bar</h1>

<h3>Transformer script</h3>
<p>This script will check every product. It will turn the product attributes into global WooCommerce attributes. 1 value in progress bar means 1 product handled.</p>

<div id=""></div>

<progress id="progress_bar" max="<?php echo $max; ?>" value="0" data-label="0% completed"></progress>

<button id="btn_start_process" onclick="startProcess()" class="button button-primary">Start script</button>

<p><strong>Note:</strong> The progress will be updated by ajax calls.</p>

<textarea id="message" rows="25" readonly="readonly"></textarea>

<div id="log"></div>
<?php
