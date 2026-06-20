<?php

/**
 * euwithdrawal - table removal. Loaded by Euwithdrawal::runSqlFile().
 *
 * @author    Andriy Gryban
 * @copyright 2026 Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'euwithdrawal`;';
