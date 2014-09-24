<?php
if (!defined('WP_UNINSTALL_PLUGIN'))
exit();

global $wpdb;

$table_name = $wpdb->prefix . "onepointmail";

$sql = "DROP TABLE IF EXISTS $table_name;";
$wpdb->query($sql);
