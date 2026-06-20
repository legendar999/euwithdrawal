<?php
/**
 * Front controller for the EU withdrawal-from-contract form.
 *
 * Flow (one controller, three rendered states):
 *   1. form     - the customer enters their details + order number
 *   2. review   - we found & verified the order; show a summary + "Confirm" button
 *   3. done     - request stored, acknowledgement e-mails sent
 *
 * Class name is resolved case-insensitively by Dispatcher.php:295
 * ($module_name.$controller.'ModuleFrontController').
 *
 * @author    Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class EuwithdrawalWithdrawalModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $display_column_left = false;
    /** @var bool */
    public $display_column_right = false;

    /** @var array collected field values (to repopulate the form) */
    protected $data = array();
    /** @var array validation error messages */
    protected $errors_list = array();

    public function init()
    {
        // Optionally force login (off by default - the law expects open access).
        $this->auth = (bool)Configuration::get('EUWITHDRAWAL_REQUIRE_LOGIN');
        if ($this->auth) {
            $this->authRedirection = $this->module->getWithdrawalLink();
        }
        parent::init();
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri().'views/css/front.css');
        $this->addJS($this->module->getPathUri().'views/js/front.js');
    }

    public function initContent()
    {
        parent::initContent();

        $step = Tools::getValue('euw_step');
        $rendered = false;

        if (Tools::isSubmit('euw_back')) {
            // "Back" from the review screen: repopulate the form with what was typed.
            $this->collectInput();
        } elseif ($step === 'confirm' && Tools::isSubmit('euw_submit')) {
            $rendered = $this->processConfirm();
        } elseif ($step === 'review' && Tools::isSubmit('euw_continue')) {
            $rendered = $this->processReview();
        }

        if (!$rendered) {
            $this->renderForm();
        }
    }

    /* ===================================================================== */
    /*  STEP 1 -> 2 : validate input, find the order, show the review        */
    /* ===================================================================== */

    protected function processReview()
    {
        $this->collectInput();

        if (!$this->validateInput()) {
            $this->renderForm();
            return true;
        }

        $order = $this->findOrder($this->data['order_number'], $this->data['email']);
        if (!$order) {
            $this->errors_list[] = $this->module->l('We could not find an order with that number and e-mail. Please check both and try again.', 'withdrawal');
            $this->renderForm();
            return true;
        }

        $products = $this->getOrderItems((int)$order['id_order']);

        $this->context->smarty->assign(array(
            'euw_title'       => $this->module->getLinkLabel(),
            'euw_action'      => $this->module->getWithdrawalLink(),
            'euw_data'        => $this->data,
            'euw_order'       => $order,
            'euw_products'    => $products,
            'euw_allow_items' => (bool)Configuration::get('EUWITHDRAWAL_ALLOW_ITEMS'),
            'euw_token'       => $this->makeToken($order['id_order'], $this->data['email']),
        ));
        $this->setTemplate('confirm.tpl');
        return true;
    }

    /* ===================================================================== */
    /*  STEP 2 -> 3 : re-verify, store the request, send e-mails, show done  */
    /* ===================================================================== */

    protected function processConfirm()
    {
        $this->collectInput();

        if (!$this->validateInput()) {
            $this->renderForm();
            return true;
        }

        // Re-verify the order against the posted e-mail (never trust hidden ids).
        $order = $this->findOrder($this->data['order_number'], $this->data['email']);
        if (!$order) {
            $this->errors_list[] = $this->module->l('We could not verify the order. Please start again.', 'withdrawal');
            $this->renderForm();
            return true;
        }

        // CSRF-ish guard: token must match the verified order + e-mail.
        if (Tools::getValue('euw_token') !== $this->makeToken($order['id_order'], $this->data['email'])) {
            $this->errors_list[] = $this->module->l('Your session has expired. Please start again.', 'withdrawal');
            $this->renderForm();
            return true;
        }

        list($scope, $items) = $this->resolveScope((int)$order['id_order']);

        $request = $this->storeRequest($order, $scope, $items);
        if (!Validate::isLoadedObject($request)) {
            $this->errors_list[] = $this->module->l('Sorry, we could not save your request. Please try again later.', 'withdrawal');
            $this->renderForm();
            return true;
        }

        $this->notify($request, $order, $items);

        $this->context->smarty->assign(array(
            'euw_title'      => $this->module->getLinkLabel(),
            'euw_reference'  => $order['reference'],
            'euw_email'      => $this->data['email'],
            'euw_request_id' => (int)$request->id,
            'euw_home'       => $this->context->link->getPageLink('index'),
        ));
        $this->setTemplate('done.tpl');
        return true;
    }

    /* ===================================================================== */
    /*  RENDER STEP 1 (form)                                                  */
    /* ===================================================================== */

    protected function renderForm()
    {
        $prefill = array(
            'firstname'    => '',
            'lastname'     => '',
            'email'        => '',
            'order_number' => '',
            'date_received' => '',
            'reason'       => '',
        );
        // Repopulate from a failed submit, otherwise prefill from the account.
        if (!empty($this->data)) {
            $prefill = array_merge($prefill, $this->data);
        } elseif (Configuration::get('EUWITHDRAWAL_PREFILL') && $this->context->customer->isLogged()) {
            $prefill['firstname'] = $this->context->customer->firstname;
            $prefill['lastname']  = $this->context->customer->lastname;
            $prefill['email']     = $this->context->customer->email;
        }

        $customer_orders = array();
        if (Configuration::get('EUWITHDRAWAL_PREFILL') && $this->context->customer->isLogged()) {
            $customer_orders = $this->getCustomerOrderList((int)$this->context->customer->id);
        }

        $this->context->smarty->assign(array(
            'euw_title'    => $this->module->getLinkLabel(),
            'euw_intro'    => Configuration::get('EUWITHDRAWAL_INTRO', (int)$this->context->language->id),
            'euw_action'   => $this->module->getWithdrawalLink(),
            'euw_data'     => $prefill,
            'euw_orders'   => $customer_orders,
            'euw_errors'   => $this->errors_list,
        ));
        $this->setTemplate('form.tpl');
    }

    /* ===================================================================== */
    /*  INPUT / VALIDATION                                                    */
    /* ===================================================================== */

    protected function collectInput()
    {
        $this->data = array(
            'firstname'     => trim((string)Tools::getValue('euw_firstname')),
            'lastname'      => trim((string)Tools::getValue('euw_lastname')),
            'email'         => trim((string)Tools::getValue('euw_email')),
            'order_number'  => trim((string)Tools::getValue('euw_order_number')),
            'date_received' => trim((string)Tools::getValue('euw_date_received')),
            'reason'        => trim((string)Tools::getValue('euw_reason')),
        );
    }

    protected function validateInput()
    {
        // Honeypot - bots fill hidden fields; humans never see it.
        if (trim((string)Tools::getValue('euw_website')) !== '') {
            $this->errors_list[] = $this->module->l('Spam detected.', 'withdrawal');
            return false;
        }

        $ok = true;
        if ($this->data['firstname'] === '' || !Validate::isName($this->data['firstname'])) {
            $this->errors_list[] = $this->module->l('Please enter a valid first name.', 'withdrawal');
            $ok = false;
        }
        if ($this->data['lastname'] === '' || !Validate::isName($this->data['lastname'])) {
            $this->errors_list[] = $this->module->l('Please enter a valid last name.', 'withdrawal');
            $ok = false;
        }
        if ($this->data['email'] === '' || !Validate::isEmail($this->data['email'])) {
            $this->errors_list[] = $this->module->l('Please enter a valid e-mail address.', 'withdrawal');
            $ok = false;
        }
        if ($this->data['order_number'] === '') {
            $this->errors_list[] = $this->module->l('Please enter your order number.', 'withdrawal');
            $ok = false;
        }
        if ($this->data['date_received'] !== '' && !Validate::isDateFormat($this->data['date_received'])) {
            // Accept it but normalise; a wrong format is non-blocking (optional field).
            $this->data['date_received'] = '';
        }
        if ($this->data['reason'] !== '' && !Validate::isCleanHtml($this->data['reason'])) {
            $this->data['reason'] = strip_tags($this->data['reason']);
        }
        return $ok;
    }

    /* ===================================================================== */
    /*  ORDER LOOKUP                                                          */
    /* ===================================================================== */

    /**
     * Find an order by its customer-facing number (reference OR numeric id)
     * and verify the customer e-mail. akvavent.si uses a zero-padded reference
     * equal to the order id (000007626), so we match both forms.
     */
    protected function findOrder($order_number, $email)
    {
        $ref = ltrim($order_number, "#\t\n\r\0\x0B ");
        $id  = (int)$ref;

        $sql = 'SELECT o.`id_order`, o.`reference`, o.`id_customer`, o.`id_lang`, o.`id_shop`,
                       o.`total_paid_tax_incl`, o.`date_add`,
                       c.`email`, c.`firstname` AS c_firstname, c.`lastname` AS c_lastname
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = o.`id_customer`)
                WHERE (o.`reference` = \''.pSQL($ref).'\'';
        if ($id > 0) {
            $sql .= ' OR o.`id_order` = '.$id;
        }
        $sql .= ') AND c.`email` = \''.pSQL($email).'\'
                ORDER BY o.`id_order` DESC';

        // Note: Db::getRow() appends its own "LIMIT 1" - do NOT add one here.
        $row = Db::getInstance()->getRow($sql);
        return $row ? $row : false;
    }

    /** Line items of an order, normalised for display/selection. */
    protected function getOrderItems($id_order)
    {
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return array();
        }
        $list = array();
        foreach ($order->getProducts() as $id_detail => $p) {
            $list[] = array(
                'id_order_detail' => (int)$id_detail,
                'name'            => $p['product_name'],
                'qty'             => (int)$p['product_quantity'],
                'price'           => Tools::displayPrice($p['total_price_tax_incl']),
            );
        }
        return $list;
    }

    /** Read the posted scope/items selection and validate it against the order. */
    protected function resolveScope($id_order)
    {
        $allow = (bool)Configuration::get('EUWITHDRAWAL_ALLOW_ITEMS');
        $scope = Tools::getValue('euw_scope');
        if (!$allow || $scope !== EuWithdrawalRequest::SCOPE_ITEMS) {
            return array(EuWithdrawalRequest::SCOPE_ORDER, array());
        }

        $valid = $this->getOrderItems($id_order);
        $valid_ids = array();
        foreach ($valid as $v) {
            $valid_ids[$v['id_order_detail']] = $v;
        }

        $posted = Tools::getValue('euw_items');
        $selected = array();
        if (is_array($posted)) {
            foreach ($posted as $id_detail) {
                $id_detail = (int)$id_detail;
                if (isset($valid_ids[$id_detail])) {
                    $selected[] = $valid_ids[$id_detail];
                }
            }
        }

        if (empty($selected)) {
            // Nothing valid selected -> treat as whole order.
            return array(EuWithdrawalRequest::SCOPE_ORDER, array());
        }
        return array(EuWithdrawalRequest::SCOPE_ITEMS, $selected);
    }

    /* ===================================================================== */
    /*  PERSIST + NOTIFY                                                      */
    /* ===================================================================== */

    protected function storeRequest($order, $scope, $items)
    {
        $request = new EuWithdrawalRequest();
        $request->id_shop         = (int)$order['id_shop'];
        $request->id_customer     = (int)$order['id_customer'];
        $request->id_order        = (int)$order['id_order'];
        $request->order_reference = pSQL($order['reference']);
        $request->firstname       = $this->data['firstname'];
        $request->lastname        = $this->data['lastname'];
        $request->email           = $this->data['email'];
        $request->date_received   = $this->data['date_received'];
        $request->reason          = $this->data['reason'];
        $request->scope           = $scope;
        $request->items           = $items ? (json_encode($items) ?: '') : '';
        $request->status          = EuWithdrawalRequest::STATUS_RECEIVED;
        $request->ip              = pSQL(Tools::getRemoteAddr());
        try {
            $request->add();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('euwithdrawal: save failed - '.$e->getMessage(), 3);
        }
        return $request;
    }

    protected function notify($request, $order, $items)
    {
        $items_text = $this->itemsToText($request->scope, $items);
        $request_date = Tools::displayDate(date('Y-m-d H:i:s'));

        $merchant_email = Configuration::get('EUWITHDRAWAL_MERCHANT_EMAIL');
        if (!$merchant_email || !Validate::isEmail($merchant_email)) {
            $merchant_email = Configuration::get('PS_SHOP_EMAIL');
        }

        $base_vars = array(
            '{firstname}'       => $this->data['firstname'],
            '{lastname}'        => $this->data['lastname'],
            '{email}'           => $this->data['email'],
            '{order_reference}' => $order['reference'],
            '{order_id}'        => (int)$order['id_order'],
            '{request_id}'      => (int)$request->id,
            '{request_date}'    => $request_date,
            '{date_received}'   => $this->data['date_received'] ? $this->data['date_received'] : '-',
            '{scope}'           => ($request->scope === EuWithdrawalRequest::SCOPE_ITEMS)
                ? $this->module->l('Selected items', 'withdrawal')
                : $this->module->l('Whole order', 'withdrawal'),
            '{items}'           => $items_text,
            '{reason}'          => $this->data['reason'] ? $this->data['reason'] : '-',
            '{shop_name}'       => Configuration::get('PS_SHOP_NAME'),
        );

        // Customer acknowledgement (in the order's language when we have a template).
        if (Configuration::get('EUWITHDRAWAL_NOTIFY_CUSTOMER')) {
            $id_lang = $this->mailLang((int)$order['id_lang'], 'withdrawal_customer');
            $this->sendMail(
                $id_lang,
                'withdrawal_customer',
                $this->module->l('We received your withdrawal from the contract', 'withdrawal'),
                $base_vars,
                $this->data['email'],
                trim($this->data['firstname'].' '.$this->data['lastname'])
            );
        }

        // Merchant notification.
        if (Configuration::get('EUWITHDRAWAL_NOTIFY_MERCHANT') && $merchant_email) {
            $id_lang = $this->mailLang((int)Configuration::get('PS_LANG_DEFAULT'), 'withdrawal_merchant');
            $merchant_vars = array_merge($base_vars, array(
                '{customer_link}' => $this->context->link->getAdminLink('AdminEuWithdrawal'),
            ));
            $this->sendMail(
                $id_lang,
                'withdrawal_merchant',
                $this->module->l('New withdrawal request', 'withdrawal').' #'.(int)$request->id,
                $merchant_vars,
                $merchant_email,
                Configuration::get('PS_SHOP_NAME'),
                $this->data['email'] // reply-to the customer
            );
        }
    }

    protected function sendMail($id_lang, $template, $subject, $vars, $to, $to_name, $reply_to = null)
    {
        try {
            Mail::Send(
                (int)$id_lang,
                $template,
                $subject,
                $vars,
                $to,
                $to_name,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_.'euwithdrawal/mails/',
                false,
                (int)$this->context->shop->id,
                null,
                $reply_to
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog('euwithdrawal: mail "'.$template.'" failed - '.$e->getMessage(), 2);
        }
    }

    /** Pick a language id whose iso has our mail template; fall back to en. */
    protected function mailLang($id_lang, $template)
    {
        $iso = Language::getIsoById((int)$id_lang);
        if ($iso && file_exists(_PS_MODULE_DIR_.'euwithdrawal/mails/'.Tools::strtolower($iso).'/'.$template.'.html')) {
            return (int)$id_lang;
        }
        $en = (int)Language::getIdByIso('en');
        if ($en && file_exists(_PS_MODULE_DIR_.'euwithdrawal/mails/en/'.$template.'.html')) {
            return $en;
        }
        return (int)$id_lang;
    }

    protected function itemsToText($scope, $items)
    {
        if ($scope !== EuWithdrawalRequest::SCOPE_ITEMS || empty($items)) {
            return $this->module->l('Whole order', 'withdrawal');
        }
        $lines = array();
        foreach ($items as $it) {
            $lines[] = $it['name'].' x'.(int)$it['qty'];
        }
        return implode('; ', $lines);
    }

    /** Lightweight token tying the two steps together (server secret + order + email). */
    protected function makeToken($id_order, $email)
    {
        return hash('sha256', _COOKIE_KEY_.'|euwithdrawal|'.(int)$id_order.'|'.Tools::strtolower($email));
    }

    /** Orders for the logged-in customer (drop-down on step 1). */
    protected function getCustomerOrderList($id_customer)
    {
        $orders = Order::getCustomerOrders($id_customer);
        $list = array();
        if (is_array($orders)) {
            foreach ($orders as $o) {
                $list[] = array(
                    'reference' => $o['reference'],
                    'label'     => $o['reference'].' - '.Tools::displayDate($o['date_add']),
                );
            }
        }
        return $list;
    }
}
