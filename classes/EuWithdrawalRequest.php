<?php
/**
 * EuWithdrawalRequest - ObjectModel for one withdrawal-from-contract request.
 *
 * Part of the open-source "euwithdrawal" PrestaShop 1.6 module.
 *
 * @author    Andriy Gryban
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

    const STATUS_RECEIVED   = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED  = 2;

    const SCOPE_ORDER = 'order';
    const SCOPE_ITEMS = 'items';

    public static $definition = array(
        'table'   => 'euwithdrawal',
        'primary' => 'id_euwithdrawal',
        'fields'  => array(
            'id_shop'         => array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'),
            'id_customer'     => array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'),
            'id_order'        => array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'),
            'order_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'firstname'       => array('type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 255),
            'lastname'        => array('type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 255),
            'email'           => array('type' => self::TYPE_STRING, 'validate' => 'isEmail',       'required' => true, 'size' => 255),
            'date_received'   => array('type' => self::TYPE_STRING, 'validate' => 'isDateFormat', 'size' => 10),
            'reason'          => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 2000),
            'scope'           => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16),
            'items'           => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 8000),
            'status'          => array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'),
            'staff_note'      => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 4000),
            'ip'              => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'date_add'        => array('type' => self::TYPE_DATE,   'validate' => 'isDate'),
            'date_upd'        => array('type' => self::TYPE_DATE,   'validate' => 'isDate'),
        ),
    );

    /**
     * Human labels for the status codes.
     *
     * @param Module $module used for $module->l() translation
     * @return array status_id => label
     */
    public static function getStatuses($module = null)
    {
        $l = function ($s) use ($module) {
            return ($module instanceof Module) ? $module->l($s, 'euwithdrawalrequest') : $s;
        };
        return array(
            self::STATUS_RECEIVED   => $l('Received'),
            self::STATUS_PROCESSING => $l('Processing'),
            self::STATUS_COMPLETED  => $l('Completed'),
        );
    }

    /** Decode the stored items JSON into an array. */
    public function getItemsArray()
    {
        if (!$this->items) {
            return array();
        }
        $decoded = json_decode($this->items, true);
        return is_array($decoded) ? $decoded : array();
    }
}
