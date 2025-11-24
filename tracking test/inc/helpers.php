<?php
// inc/helpers.php
function gen_tracking_no() {
return 'MY' . strval(mt_rand(1000000000, 9999999999));
}


function esc($s) {
return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}