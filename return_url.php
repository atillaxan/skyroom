<?php
/**
 * This exists, for synchronizing moodle and laravel
 */

global $skyroom_base_url;
$config = get_config('skyroom');
return $skyroom_base_url . '/' . $config->apikey;