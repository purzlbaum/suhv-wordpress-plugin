<?php

$apiKeySUHV = urlencode(get_option('api_key_suhv'));
$clubNameSaved = get_option('clubname');

define('CFG_SUHV_CLUB', $clubNameSaved);
define('CFG_SUHV_API_KEY', $apiKeySUHV);
