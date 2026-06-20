<?php

/**
 * euwithdrawal - table creation. Loaded by Euwithdrawal::runSqlFile().
 *
 * @author    Andriy Gryban
 * @copyright 2026 Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'euwithdrawal` (
    `id_euwithdrawal` int(10) unsigned NOT NULL auto_increment,
    `id_shop` int(10) unsigned NOT NULL DEFAULT 1,
    `id_customer` int(10) unsigned NOT NULL DEFAULT 0,
    `id_order` int(10) unsigned NOT NULL DEFAULT 0,
    `order_reference` varchar(64) NOT NULL DEFAULT \'\',
    `firstname` varchar(255) NOT NULL DEFAULT \'\',
    `lastname` varchar(255) NOT NULL DEFAULT \'\',
    `email` varchar(255) NOT NULL DEFAULT \'\',
    `date_received` varchar(10) NOT NULL DEFAULT \'\',
    `reason` text NULL,
    `scope` varchar(16) NOT NULL DEFAULT \'order\',
    `items` text NULL,
    `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `staff_note` text NULL,
    `ip` varchar(64) NOT NULL DEFAULT \'\',
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_euwithdrawal`),
    KEY `id_order` (`id_order`),
    KEY `email` (`email`),
    KEY `status` (`status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
