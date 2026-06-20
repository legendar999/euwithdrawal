<?php

/**
 * EuWithdrawalRequest - ObjectModel for one withdrawal-from-contract request.
 *
 * Part of the open-source "euwithdrawal" PrestaShop 1.6 module.
 *
 * @author    Andriy Gryban
 * @copyright 2026 Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class EuWithdrawalRequest extends ObjectModel
{
    /** @var int */
    public $id_euwithdrawal;
    /** @var int */
    public $id_shop;
    /** @var int linked customer (0 for guests) */
    public $id_customer;
    /** @var int linked order */
    public $id_order;
    /** @var string customer-facing order reference (e.g. 000007626) */
    public $order_reference;
    /** @var string */
    public $firstname;
    /** @var string */
    public $lastname;
    /** @var string */
    public $email;
    /** @var string|null date the goods were received (optional) */
    public $date_received;
    /** @var string|null optional reason */
    public $reason;
    /** @var string 'order' (whole order) or 'items' (selected lines) */
    public $scope;
    /** @var string JSON list of selected line items */
    public $items;
    /** @var int 0 = received, 1 = processing, 2 = completed */
    public $status;
    /** @var string|null internal note added in the back office */
    public $staff_note;
    /** @var string client IP captured at submission (legal record) */
    public $ip;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public const STATUS_RECEIVED = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_COMPLETED = 2;

    public const SCOPE_ORDER = 'order';
    public const SCOPE_ITEMS = 'items';

    public static $definition = [
        'table' => 'euwithdrawal',
        'primary' => 'id_euwithdrawal',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'id_customer' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'id_order' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'order_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 255],
            'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 255],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail',       'required' => true, 'size' => 255],
            'date_received' => ['type' => self::TYPE_STRING, 'validate' => 'isDateFormat', 'size' => 10],
            'reason' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 2000],
            'scope' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'items' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 8000],
            'status' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'],
            'staff_note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 4000],
            'ip' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'date_add' => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE,   'validate' => 'isDate'],
        ],
    ];

    /**
     * Human labels for the status codes.
     *
     * @param Module $module used for $module->l() translation
     *
     * @return array status_id => label
     */
    public static function getStatuses($module = null)
    {
        $l = function ($s) use ($module) {
            return ($module instanceof Module) ? $module->l($s, 'euwithdrawalrequest') : $s;
        };

        return [
            self::STATUS_RECEIVED => $l('Received'),
            self::STATUS_PROCESSING => $l('Processing'),
            self::STATUS_COMPLETED => $l('Completed'),
        ];
    }

    /** Decode the stored items JSON into an array. */
    public function getItemsArray()
    {
        if (!$this->items) {
            return [];
        }
        $decoded = json_decode($this->items, true);

        return is_array($decoded) ? $decoded : [];
    }
}
