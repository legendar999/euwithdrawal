<?php
/**
 * euwithdrawal - table removal. Loaded by Euwithdrawal::runSqlFile().
 *
 * @license AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'euwithdrawal`;';
