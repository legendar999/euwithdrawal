<?php
/**
 * Back-office register of withdrawal requests.
 *
 * Part of the open-source "euwithdrawal" PrestaShop 1.6 module.
 *
 * @author    Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'euwithdrawal/classes/EuWithdrawalRequest.php';

class AdminEuWithdrawalController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'euwithdrawal';
        $this->className = 'EuWithdrawalRequest';
        $this->identifier = 'id_euwithdrawal';
        $this->lang = false;
        $this->explicitSelect = true;

        parent::__construct();

        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->_select = 'CONCAT(a.`firstname`, \' \', a.`lastname`) AS `customer_name`';

        $statuses = EuWithdrawalRequest::getStatuses($this->module);

        $this->fields_list = array(
            'id_euwithdrawal' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'date_add' => array(
                'title' => $this->l('Received on'),
                'type'  => 'datetime',
                'align' => 'left',
            ),
            'order_reference' => array(
                'title' => $this->l('Order'),
                'align' => 'left',
            ),
            'customer_name' => array(
                'title'      => $this->l('Customer'),
                'align'      => 'left',
                'filter_key' => 'a!lastname',
                'havingFilter' => true,
            ),
            'email' => array(
                'title' => $this->l('E-mail'),
                'align' => 'left',
            ),
            'scope' => array(
                'title'    => $this->l('Scope'),
                'align'    => 'center',
                'callback' => 'renderScope',
                'search'   => false,
            ),
            'status' => array(
                'title'      => $this->l('Status'),
                'align'      => 'center',
                'type'       => 'select',
                'list'       => $statuses,
                'filter_key' => 'a!status',
                'callback'   => 'renderStatusBadge',
                'class'      => 'fixed-width-sm',
            ),
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete the selected requests?'),
                'icon'    => 'icon-trash',
            ),
        );
    }

    /** Hide the "Add new" button - requests are only created from the front office. */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['new']);
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        return parent::renderList();
    }

    /* ----- list callbacks (called on the controller, HelperList.php:320) ---- */

    public function renderStatusBadge($value, $row)
    {
        $statuses = EuWithdrawalRequest::getStatuses($this->module);
        $classes = array(
            EuWithdrawalRequest::STATUS_RECEIVED   => 'label-default',
            EuWithdrawalRequest::STATUS_PROCESSING => 'label-warning',
            EuWithdrawalRequest::STATUS_COMPLETED  => 'label-success',
        );
        $v = (int)$value;
        $cls = isset($classes[$v]) ? $classes[$v] : 'label-default';
        $txt = isset($statuses[$v]) ? $statuses[$v] : $value;
        return '<span class="label '.$cls.'">'.htmlspecialchars($txt, ENT_QUOTES, 'UTF-8').'</span>';
    }

    public function renderScope($value, $row)
    {
        if ($value === EuWithdrawalRequest::SCOPE_ITEMS) {
            return '<span class="label label-info">'.$this->l('Items').'</span>';
        }
        return '<span class="label label-default">'.$this->l('Whole order').'</span>';
    }

    /* ----- edit form: read-only details + editable status & note ----------- */

    public function renderForm()
    {
        $obj = $this->loadObject(true);
        if (!Validate::isLoadedObject($obj)) {
            return parent::renderForm();
        }

        $statuses = EuWithdrawalRequest::getStatuses($this->module);
        $options = array();
        foreach ($statuses as $id => $name) {
            $options[] = array('id_option' => (int)$id, 'name' => $name);
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Withdrawal request').' #'.(int)$obj->id,
                'icon'  => 'icon-reply',
            ),
            'input' => array(
                array(
                    'type'         => 'free',
                    'label'        => $this->l('Details'),
                    'name'         => 'euw_details',
                ),
                array(
                    'type'    => 'select',
                    'label'   => $this->l('Status'),
                    'name'    => 'status',
                    'options' => array(
                        'query' => $options,
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ),
                ),
                array(
                    'type'  => 'textarea',
                    'label' => $this->l('Internal note'),
                    'name'  => 'staff_note',
                    'rows'  => 4,
                    'cols'  => 60,
                    'desc'  => $this->l('Not visible to the customer.'),
                ),
            ),
            'submit' => array('title' => $this->l('Save')),
        );

        // Inject the read-only details into the "free" field via a template var.
        $this->context->smarty->assign('euw_details', $this->buildDetailsHtml($obj));
        $this->fields_value['euw_details'] = $this->buildDetailsHtml($obj);
        $this->fields_value['status'] = (int)$obj->status;
        $this->fields_value['staff_note'] = $obj->staff_note;

        return parent::renderForm();
    }

    protected function buildDetailsHtml($obj)
    {
        $rows = array(
            $this->l('Order')            => htmlspecialchars($obj->order_reference, ENT_QUOTES, 'UTF-8').' (ID '.(int)$obj->id_order.')',
            $this->l('Customer')         => htmlspecialchars(trim($obj->firstname.' '.$obj->lastname), ENT_QUOTES, 'UTF-8'),
            $this->l('E-mail')           => htmlspecialchars($obj->email, ENT_QUOTES, 'UTF-8'),
            $this->l('Date goods received') => $obj->date_received ? htmlspecialchars($obj->date_received, ENT_QUOTES, 'UTF-8') : '-',
            $this->l('Scope')            => ($obj->scope === EuWithdrawalRequest::SCOPE_ITEMS) ? $this->l('Selected items') : $this->l('Whole order'),
            $this->l('Reason')           => $obj->reason ? nl2br(htmlspecialchars($obj->reason, ENT_QUOTES, 'UTF-8')) : '-',
            $this->l('Submitted')        => htmlspecialchars($obj->date_add, ENT_QUOTES, 'UTF-8'),
            $this->l('Client IP')        => htmlspecialchars($obj->ip, ENT_QUOTES, 'UTF-8'),
        );

        $html = '<div class="table-responsive"><table class="table">';
        foreach ($rows as $label => $value) {
            $html .= '<tr><th style="width:200px;">'.$label.'</th><td>'.$value.'</td></tr>';
        }

        $items = $obj->getItemsArray();
        if (!empty($items)) {
            $li = '';
            foreach ($items as $it) {
                $name = isset($it['name']) ? htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') : '';
                $qty = isset($it['qty']) ? (int)$it['qty'] : 0;
                $li .= '<li>'.$name.' &times;'.$qty.'</li>';
            }
            $html .= '<tr><th>'.$this->l('Items').'</th><td><ul style="margin:0;padding-left:18px;">'.$li.'</ul></td></tr>';
        }

        // A link to the order in the back office.
        $order_link = $this->context->link->getAdminLink('AdminOrders').'&id_order='.(int)$obj->id_order.'&vieworder';
        $html .= '<tr><th></th><td><a class="btn btn-default btn-sm" href="'.$order_link.'"><i class="icon-search"></i> '.$this->l('Open the order').'</a></td></tr>';

        $html .= '</table></div>';
        return $html;
    }
}
