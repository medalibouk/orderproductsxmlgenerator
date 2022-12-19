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

class Orderproductsxmlgenerator extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'orderproductsxmlgenerator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'medalibouk';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Product XML Generator');
        $this->description = $this->l('this is a test module');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('ORDERPRODUCTSXMLGENERATOR_ORDER_STATE', 5);

        return parent::install() &&
            $this->_installTab() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('ORDERPRODUCTSXMLGENERATOR_ORDER_STATE');

        return parent::uninstall() && $this->_uninstallTab();
    }


    public function _installTab()
    {
        $tabData = array(
            array('name' => 'Order P XML GENER', 'class_name' => 'Orderproductsxmlgenerator', 'id_parent' => 0, 'module' => $this->name),
            array('name' => 'Settings', 'class_name' => 'AdminSettings', 'id_parent' => 1, 'module' => $this->name),
           
        );
        $parentTabID = 0;
        foreach ($tabData as $data) {
          try {
            $id_tab = Tab::getIdFromClassName($data['class_name']);
            if((bool) $id_tab)
            {
                $parentTabID = $data['id_parent'] == 0 ? $id_tab : $parentTabID;
            }else{
                $tab = new Tab();   
                foreach ($data as $attribute => $value) {
                    if($attribute == 'id_parent')
                    {
                       $tab->id_parent = $parentTabID;
                    } elseif ($attribute == 'name') {
                       foreach (Language::getLanguages(true) as $lang)
                       {
                         $tab->name[$lang['id_lang']] = $value;
                       }
                    } else {
                        $tab->{$attribute} = $value;
                    }
                }
                $tab->save();
                $parentTabID = $data['id_parent'] == 0 ? $tab->id : $parentTabID;
            }
           
           } catch (Exception $e) {
            echo $e->getMessage();
            return false;
           }
        }
        return true;
    }



    protected function _uninstallTab()
    {
        $idsTab = array();
        $idsTab[] = (int)Tab::getIdFromClassName('AdminSettings');
        $idsTab[] = (int)Tab::getIdFromClassName('Orderproductsxmlgenerator');

        foreach ($idsTab as $idTab) {
            if ($idTab) {
                $tab = new Tab($idTab);
                try {
                    $tab->delete();
                } catch (Exception $e) {
                    echo $e->getMessage();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitOrderproductsxmlgeneratorModule')) == true) {
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
        $helper->submit_action = 'submitOrderproductsxmlgeneratorModule';
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
                        'type' => 'select',
                        'label' => $this->l('Order State'),
                        'name' => 'ORDERPRODUCTSXMLGENERATOR_ORDER_STATE',
                        'options' => array(
                            'query' => $this->getOrdersState(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $this->l('Live mode'),
                    //     'name' => 'ORDERPRODUCTSXMLGENERATOR_LIVE_MODE',
                    //     'is_bool' => true,
                    //     'desc' => $this->l('Use this module in live mode'),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => true,
                    //             'label' => $this->l('Enabled')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => false,
                    //             'label' => $this->l('Disabled')
                    //         )
                    //     ),
                    // ),
                    // array(
                    //     'col' => 3,
                    //     'type' => 'text',
                    //     'prefix' => '<i class="icon icon-envelope"></i>',
                    //     'desc' => $this->l('Enter a valid email address'),
                    //     'name' => 'ORDERPRODUCTSXMLGENERATOR_ACCOUNT_EMAIL',
                    //     'label' => $this->l('Email'),
                    // ),
                    // array(
                    //     'type' => 'password',
                    //     'name' => 'ORDERPRODUCTSXMLGENERATOR_ACCOUNT_PASSWORD',
                    //     'label' => $this->l('Password'),
                    // ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }



    protected function getOrdersState()
    {
       
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $newOrderStates = array();
        foreach ($orderStates as $orderState) {
            $newOrderStates[] = array(
                'id' => $orderState['id_order_state'],
                'name' => $orderState['name'],
            );
        }
       return $newOrderStates;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ORDERPRODUCTSXMLGENERATOR_ORDER_STATE' => Configuration::get('ORDERPRODUCTSXMLGENERATOR_ORDER_STATE', 5),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
}
