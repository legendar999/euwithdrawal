<?php
/**
 * EU Withdrawal (euwithdrawal) - open-source PrestaShop 1.6 module.
 *
 * Adds an EU "right of withdrawal" button/link (footer, header and/or floating)
 * pointing to a simple 2-step withdrawal form. On confirmation it stores the
 * request in a back-office register and e-mails both the customer and the merchant.
 *
 * Implements the consumer "withdrawal button" required by Directive (EU) 2023/2673
 * (transposition deadline 19 June 2026 - SI ZVPot).
 *
 * @author    Andriy Gryban
 * @copyright 2026 Andriy Gryban
 * @license   AFL-3.0  http://opensource.org/licenses/afl-3.0.php
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/EuWithdrawalRequest.php';

class Euwithdrawal extends Module
{
    /** @var array cache of installed languages */
    protected $langs;

    public function __construct()
    {
        $this->name = 'euwithdrawal';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Andriy Gryban';
        $this->need_instance = 0;
        $this->bootstrap = true;

        // Front controllers shipped by this module.
        $this->controllers = array('withdrawal');

        parent::__construct();

        $this->displayName = $this->l('EU Withdrawal - Odstop od pogodbe');
        $this->description = $this->l('EU right-of-withdrawal button & form: a footer/header/floating link to a simple 2-step withdrawal page, automatic confirmation e-mails, and a back-office register of requests.');
        $this->confirmUninstall = $this->l('Are you sure? Uninstalling deletes the table of withdrawal requests. Export it first if you must keep the records.');

        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => '1.6.99.99');
    }

    /* ===================================================================== */
    /*  INSTALL / UNINSTALL                                                   */
    /* ===================================================================== */

    public function install()
    {
        return parent::install()
            && $this->installSql()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayFooter')
            && $this->registerHook('displayTop')
            && $this->registerHook('moduleRoutes')
            && $this->installDefaults()
            && $this->installTab();
    }

    public function uninstall()
    {
        // We intentionally drop the data table on uninstall (see confirmUninstall).
        return $this->uninstallTab()
            && $this->uninstallSql()
            && $this->uninstallConfig()
            && parent::uninstall();
    }

    protected function installSql()
    {
        return $this->runSqlFile(dirname(__FILE__).'/sql/install.php');
    }

    protected function uninstallSql()
    {
        return $this->runSqlFile(dirname(__FILE__).'/sql/uninstall.php');
    }

    protected function runSqlFile($file)
    {
        if (!file_exists($file)) {
            return true;
        }
        $sql = array();
        require $file; // populates $sql
        foreach ($sql as $query) {
            if (trim($query) === '') {
                continue;
            }
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    /** Seed default configuration (incl. per-language label & intro text). */
    protected function installDefaults()
    {
        Configuration::updateValue('EUWITHDRAWAL_SHOW_FOOTER', 1);
        Configuration::updateValue('EUWITHDRAWAL_SHOW_HEADER', 0);
        Configuration::updateValue('EUWITHDRAWAL_SHOW_FLOATING', 0);
        Configuration::updateValue('EUWITHDRAWAL_PREFILL', 1);
        Configuration::updateValue('EUWITHDRAWAL_ALLOW_ITEMS', 1);
        Configuration::updateValue('EUWITHDRAWAL_REQUIRE_LOGIN', 0);
        Configuration::updateValue('EUWITHDRAWAL_NOTIFY_CUSTOMER', 1);
        Configuration::updateValue('EUWITHDRAWAL_NOTIFY_MERCHANT', 1);
        Configuration::updateValue('EUWITHDRAWAL_MERCHANT_EMAIL', '');
        Configuration::updateValue('EUWITHDRAWAL_SLUG', 'odstop-od-pogodbe');

        $label = array();
        $intro = array();
        foreach ($this->getLangs() as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $label[$lang['id_lang']] = $this->defaultLabel($iso);
            $intro[$lang['id_lang']] = $this->defaultIntro($iso);
        }
        Configuration::updateValue('EUWITHDRAWAL_LINK_LABEL', $label);
        Configuration::updateValue('EUWITHDRAWAL_INTRO', $intro, true);

        return true;
    }

    protected function uninstallConfig()
    {
        $keys = array(
            'EUWITHDRAWAL_SHOW_FOOTER', 'EUWITHDRAWAL_SHOW_HEADER', 'EUWITHDRAWAL_SHOW_FLOATING',
            'EUWITHDRAWAL_PREFILL', 'EUWITHDRAWAL_ALLOW_ITEMS', 'EUWITHDRAWAL_REQUIRE_LOGIN',
            'EUWITHDRAWAL_NOTIFY_CUSTOMER', 'EUWITHDRAWAL_NOTIFY_MERCHANT', 'EUWITHDRAWAL_MERCHANT_EMAIL',
            'EUWITHDRAWAL_SLUG', 'EUWITHDRAWAL_LINK_LABEL', 'EUWITHDRAWAL_INTRO',
        );
        foreach ($keys as $k) {
            Configuration::deleteByName($k);
        }
        return true;
    }

    /** Create the back-office menu entry (under the Orders parent tab). */
    protected function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminEuWithdrawal';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
        if (!$tab->id_parent) {
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentCustomer');
        }
        if (!$tab->id_parent) {
            $tab->id_parent = 0; // top level fallback
        }
        $tab->name = array();
        foreach (Language::getLanguages(false) as $lang) {
            $iso = Tools::strtolower($lang['iso_code']);
            $tab->name[$lang['id_lang']] = ($iso === 'sl') ? 'Odstop od pogodbe' : 'Withdrawals';
        }
        return (bool)$tab->add();
    }

    protected function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminEuWithdrawal');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return (bool)$tab->delete();
        }
        return true;
    }

    /* ===================================================================== */
    /*  HOOKS                                                                 */
    /* ===================================================================== */

    public function hookDisplayHeader($params)
    {
        // Light CSS is needed wherever the link/floating button can appear.
        $this->context->controller->addCSS($this->_path.'views/css/front.css', 'all');
    }

    public function hookDisplayFooter($params)
    {
        $html = '';
        if (Configuration::get('EUWITHDRAWAL_SHOW_FOOTER')) {
            $this->smarty->assign(array(
                'euw_url'   => $this->getWithdrawalLink(),
                'euw_label' => $this->getLinkLabel(),
                'euw_place' => 'footer',
            ));
            $html .= $this->display(__FILE__, 'views/templates/hook/footer_link.tpl');
        }
        if (Configuration::get('EUWITHDRAWAL_SHOW_FLOATING')) {
            $this->smarty->assign(array(
                'euw_url'   => $this->getWithdrawalLink(),
                'euw_label' => $this->getLinkLabel(),
            ));
            $html .= $this->display(__FILE__, 'views/templates/hook/floating_button.tpl');
        }
        return $html;
    }

    public function hookDisplayTop($params)
    {
        if (!Configuration::get('EUWITHDRAWAL_SHOW_HEADER')) {
            return '';
        }
        $this->smarty->assign(array(
            'euw_url'   => $this->getWithdrawalLink(),
            'euw_label' => $this->getLinkLabel(),
            'euw_place' => 'header',
        ));
        return $this->display(__FILE__, 'views/templates/hook/footer_link.tpl');
    }

    /** Clean friendly URL: /<slug> (default /odstop-od-pogodbe). */
    public function hookModuleRoutes($params)
    {
        $slug = Configuration::get('EUWITHDRAWAL_SLUG');
        if (!$slug) {
            $slug = 'odstop-od-pogodbe';
        }
        return array(
            'module-euwithdrawal-withdrawal' => array(
                'controller' => 'withdrawal',
                'rule'       => $slug,
                'keywords'   => array(),
                'params'     => array(
                    'fc'         => 'module',
                    'module'     => 'euwithdrawal',
                    'controller' => 'withdrawal',
                ),
            ),
        );
    }

    /* ===================================================================== */
    /*  HELPERS                                                               */
    /* ===================================================================== */

    public function getWithdrawalLink($id_lang = null)
    {
        return $this->context->link->getModuleLink('euwithdrawal', 'withdrawal', array(), null, $id_lang);
    }

    public function getLinkLabel($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = (int)$this->context->language->id;
        }
        $label = Configuration::get('EUWITHDRAWAL_LINK_LABEL', $id_lang);
        if (!$label) {
            $label = $this->getLinkLabel((int)Configuration::get('PS_LANG_DEFAULT'));
        }
        if (!$label) {
            $label = 'Odstop od pogodbe';
        }
        return $label;
    }

    protected function getLangs()
    {
        if ($this->langs === null) {
            $this->langs = Language::getLanguages(false);
        }
        return $this->langs;
    }

    protected function defaultLabel($iso)
    {
        $map = array(
            'sl' => 'Odstop od pogodbe',
            'en' => 'Withdrawal from contract',
            'hr' => 'Odustanak od ugovora',
            'de' => 'Widerruf des Vertrags',
            'it' => 'Recesso dal contratto',
        );
        return isset($map[$iso]) ? $map[$iso] : $map['en'];
    }

    protected function defaultIntro($iso)
    {
        $sl = 'Tu lahko odstopite od pogodbe, sklenjene na daljavo. Vnesite svoje podatke in '
            .'številko naročila, na naslednjem koraku pa odstop potrdite. Po oddaji boste na svoj '
            .'e-naslov prejeli samodejno potrdilo o prejemu zahtevka.';
        $en = 'Here you can withdraw from a distance contract. Enter your details and order number; '
            .'on the next step you confirm the withdrawal. After submitting you will receive an '
            .'automatic acknowledgement of receipt at your e-mail address.';
        return ($iso === 'sl') ? $sl : $en;
    }

    /* ===================================================================== */
    /*  CONFIGURATION PAGE (getContent)                                       */
    /* ===================================================================== */

    public function getContent()
    {
        $html = '';
        if (Tools::isSubmit('submitEuwithdrawal')) {
            $html .= $this->postProcessConfig();
        }
        $html .= $this->renderConfigInfo();
        $html .= $this->renderForm();
        return $html;
    }

    protected function postProcessConfig()
    {
        $bools = array(
            'EUWITHDRAWAL_SHOW_FOOTER', 'EUWITHDRAWAL_SHOW_HEADER', 'EUWITHDRAWAL_SHOW_FLOATING',
            'EUWITHDRAWAL_PREFILL', 'EUWITHDRAWAL_ALLOW_ITEMS', 'EUWITHDRAWAL_REQUIRE_LOGIN',
            'EUWITHDRAWAL_NOTIFY_CUSTOMER', 'EUWITHDRAWAL_NOTIFY_MERCHANT',
        );
        foreach ($bools as $k) {
            Configuration::updateValue($k, (int)(bool)Tools::getValue($k));
        }

        $email = trim((string)Tools::getValue('EUWITHDRAWAL_MERCHANT_EMAIL'));
        if ($email !== '' && !Validate::isEmail($email)) {
            return $this->displayError($this->l('The merchant e-mail address is not valid.'));
        }
        Configuration::updateValue('EUWITHDRAWAL_MERCHANT_EMAIL', pSQL($email));

        $slug = Tools::link_rewrite(Tools::getValue('EUWITHDRAWAL_SLUG'));
        if (!$slug) {
            $slug = 'odstop-od-pogodbe';
        }
        Configuration::updateValue('EUWITHDRAWAL_SLUG', $slug);

        $label = array();
        $intro = array();
        foreach ($this->getLangs() as $lang) {
            $id = (int)$lang['id_lang'];
            $label[$id] = (string)Tools::getValue('EUWITHDRAWAL_LINK_LABEL_'.$id);
            $intro[$id] = (string)Tools::getValue('EUWITHDRAWAL_INTRO_'.$id);
        }
        Configuration::updateValue('EUWITHDRAWAL_LINK_LABEL', $label);
        Configuration::updateValue('EUWITHDRAWAL_INTRO', $intro, true);

        // Friendly URLs cache may need to forget old routes.
        Tools::clearSmartyCache();

        return $this->displayConfirmation($this->l('Settings updated.'));
    }

    /** Small info panel: public URL + quick stats. */
    protected function renderConfigInfo()
    {
        $count = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'euwithdrawal`'
        );
        $this->smarty->assign(array(
            'euw_public_url'  => $this->getWithdrawalLink(),
            'euw_admin_url'   => $this->context->link->getAdminLink('AdminEuWithdrawal'),
            'euw_total'       => $count,
            'euw_module_dir'  => $this->_path,
        ));
        return $this->display(__FILE__, 'views/templates/admin/config_info.tpl');
    }

    protected function renderForm()
    {
        $switch = function ($label, $name, $desc = '') {
            return array(
                'type'    => 'switch',
                'label'   => $label,
                'name'    => $name,
                'is_bool' => true,
                'desc'    => $desc,
                'values'  => array(
                    array('id' => $name.'_on',  'value' => 1, 'label' => $this->l('Yes')),
                    array('id' => $name.'_off', 'value' => 0, 'label' => $this->l('No')),
                ),
            );
        };

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Display & behaviour'),
                    'icon'  => 'icon-cogs',
                ),
                'input' => array(
                    $switch($this->l('Show link in footer'), 'EUWITHDRAWAL_SHOW_FOOTER', $this->l('Recommended. Renders on the displayFooter hook (clearly visible place, as required by law).')),
                    $switch($this->l('Show link in header (top)'), 'EUWITHDRAWAL_SHOW_HEADER'),
                    $switch($this->l('Show floating button (bottom-right)'), 'EUWITHDRAWAL_SHOW_FLOATING'),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('Link / page title'),
                        'name'  => 'EUWITHDRAWAL_LINK_LABEL',
                        'lang'  => true,
                        'desc'  => $this->l('Text of the link and the page heading.'),
                    ),
                    array(
                        'type'  => 'textarea',
                        'label' => $this->l('Intro text on the page'),
                        'name'  => 'EUWITHDRAWAL_INTRO',
                        'lang'  => true,
                        'autoload_rte' => false,
                        'rows'  => 4,
                        'cols'  => 60,
                    ),
                    array(
                        'type'   => 'text',
                        'label'  => $this->l('Friendly URL slug'),
                        'name'   => 'EUWITHDRAWAL_SLUG',
                        'desc'   => $this->l('Used when Friendly URLs are enabled. Default: odstop-od-pogodbe'),
                        'prefix' => '<i class="icon-link"></i>',
                    ),
                    $switch($this->l('Pre-fill data for logged-in customers'), 'EUWITHDRAWAL_PREFILL', $this->l('Pre-fills name, e-mail and offers a drop-down of the customer\'s orders.')),
                    $switch($this->l('Allow withdrawing specific items'), 'EUWITHDRAWAL_ALLOW_ITEMS', $this->l('If off, the customer can only withdraw from the whole order.')),
                    $switch($this->l('Require customer to be logged in'), 'EUWITHDRAWAL_REQUIRE_LOGIN', $this->l('Off by default - the law expects the form to be available without an account.')),
                    $switch($this->l('E-mail acknowledgement to the customer'), 'EUWITHDRAWAL_NOTIFY_CUSTOMER'),
                    $switch($this->l('E-mail notification to the merchant'), 'EUWITHDRAWAL_NOTIFY_MERCHANT'),
                    array(
                        'type'   => 'text',
                        'label'  => $this->l('Merchant e-mail (override)'),
                        'name'   => 'EUWITHDRAWAL_MERCHANT_EMAIL',
                        'desc'   => $this->l('Leave blank to use the shop e-mail (PS_SHOP_EMAIL).'),
                        'prefix' => '<i class="icon-envelope"></i>',
                    ),
                ),
                'submit' => array('title' => $this->l('Save')),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEuwithdrawal';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    protected function getConfigFieldsValues()
    {
        $values = array(
            'EUWITHDRAWAL_SHOW_FOOTER'      => Tools::getValue('EUWITHDRAWAL_SHOW_FOOTER', Configuration::get('EUWITHDRAWAL_SHOW_FOOTER')),
            'EUWITHDRAWAL_SHOW_HEADER'      => Tools::getValue('EUWITHDRAWAL_SHOW_HEADER', Configuration::get('EUWITHDRAWAL_SHOW_HEADER')),
            'EUWITHDRAWAL_SHOW_FLOATING'    => Tools::getValue('EUWITHDRAWAL_SHOW_FLOATING', Configuration::get('EUWITHDRAWAL_SHOW_FLOATING')),
            'EUWITHDRAWAL_PREFILL'          => Tools::getValue('EUWITHDRAWAL_PREFILL', Configuration::get('EUWITHDRAWAL_PREFILL')),
            'EUWITHDRAWAL_ALLOW_ITEMS'      => Tools::getValue('EUWITHDRAWAL_ALLOW_ITEMS', Configuration::get('EUWITHDRAWAL_ALLOW_ITEMS')),
            'EUWITHDRAWAL_REQUIRE_LOGIN'    => Tools::getValue('EUWITHDRAWAL_REQUIRE_LOGIN', Configuration::get('EUWITHDRAWAL_REQUIRE_LOGIN')),
            'EUWITHDRAWAL_NOTIFY_CUSTOMER'  => Tools::getValue('EUWITHDRAWAL_NOTIFY_CUSTOMER', Configuration::get('EUWITHDRAWAL_NOTIFY_CUSTOMER')),
            'EUWITHDRAWAL_NOTIFY_MERCHANT'  => Tools::getValue('EUWITHDRAWAL_NOTIFY_MERCHANT', Configuration::get('EUWITHDRAWAL_NOTIFY_MERCHANT')),
            'EUWITHDRAWAL_MERCHANT_EMAIL'   => Tools::getValue('EUWITHDRAWAL_MERCHANT_EMAIL', Configuration::get('EUWITHDRAWAL_MERCHANT_EMAIL')),
            'EUWITHDRAWAL_SLUG'             => Tools::getValue('EUWITHDRAWAL_SLUG', Configuration::get('EUWITHDRAWAL_SLUG')),
        );
        // Multilang values.
        foreach ($this->getLangs() as $lang) {
            $id = (int)$lang['id_lang'];
            $values['EUWITHDRAWAL_LINK_LABEL'][$id] = Tools::getValue('EUWITHDRAWAL_LINK_LABEL_'.$id, Configuration::get('EUWITHDRAWAL_LINK_LABEL', $id));
            $values['EUWITHDRAWAL_INTRO'][$id]      = Tools::getValue('EUWITHDRAWAL_INTRO_'.$id, Configuration::get('EUWITHDRAWAL_INTRO', $id));
        }
        return $values;
    }
}
