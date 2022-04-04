<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Clv_tagmanager extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'clv_tagmanager';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'Carlos Loyola V';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Insert tag manager');
        $this->description = $this->l('Insert tag manager on your website');
        $this->google_tag = 'CLV_TAGMANAGER_GOOGLETAG';

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CLV_TAGMANAGER_LIVE_MODE', false);
        Configuration::updateValue('CLV_TAGMANAGER_GOOGLETAG', '');

        return parent::install() &&
            $this->registerHook('displayAfterBodyOpeningTag') &&
            $this->registerHook('displayAfterTitleTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CLV_TAGMANAGER_LIVE_MODE');
        Configuration::deleteByName('CLV_TAGMANAGER_GOOGLETAG');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitClv_tagmanagerModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitClv_tagmanagerModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate script'),
                        'name' => 'CLV_TAGMANAGER_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Activate block into header and body'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i> GTM-',
                        'desc' => $this->l('Enter your tag code from Google Tag Manager'),
                        'name' => 'CLV_TAGMANAGER_GOOGLETAG',
                        'label' => $this->l('Google Tag'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CLV_TAGMANAGER_LIVE_MODE' => Configuration::get('CLV_TAGMANAGER_LIVE_MODE', true),
            'CLV_TAGMANAGER_GOOGLETAG' => Configuration::get('CLV_TAGMANAGER_GOOGLETAG', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess() {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }
    private function getValues() {
        $google_tag = 'GTM-' . Configuration::get('CLV_TAGMANAGER_GOOGLETAG');
        $live_mode = Configuration::get('CLV_TAGMANAGER_LIVE_MODE');
        $this->context->smarty->assign(array(
            'google_tag' => $google_tag,
            'live_mode' =>  $live_mode
            ));
        
    }

    public function hookdisplayAfterTitleTag() {
        $this->getValues();
        return $this->display(__FILE__, 'views/templates/hook/head.tpl');
    }
  
    public function hookdisplayAfterBodyOpeningTag() {
        $this->getValues();
        return $this->display(__FILE__, 'views/templates/hook/body.tpl');
    }
  
}
