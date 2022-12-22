<?php


use OrderProductsXmlGenerator\Repositories\Operations;


class AdminSettingsController extends ModuleAdminController
{

    public function __construct()
    {
         
        parent::__construct();
        $this->bootstrap = true; 
        $this->initOptions();
    }

  
    public function initOptions()
    {
        $this->optionTitle = $this->l('Generate XML');
     
        $pre_settings_content = '<button type="submit" name="generatexml" class="button btn btn-default"><i class="process-icon-cogs"></i>'.$this->l('Generate XML File').'</button>&nbsp;';
        $pre_settings_content .= '<button type="submit" name="generatexmlwithdownloading" class="button btn btn-default"><i class="process-icon-cogs"></i>'.$this->l('Generate XML File And Download').'</button>&nbsp;';

    
   

        $pre_settings_content2 = '<br><br>';
        $pre_settings_content2 .= '<input type="file" name="xmlproductfile" />';
        $pre_settings_content2 .= '<br /><br />';
        $pre_settings_content2 .= '<button type="submit" name="importxmlproductsbyuploading" class="button btn btn-default"><i class="process-icon-save"></i>'.$this->l('Import').'</button>&nbsp;';
        $pre_settings_content .= '<br /><br />';
      

        $pre_settings_content3 = '<br><br>';
        $pre_settings_content3 .= '<button type="submit" name="importxmlproductsfrominterne" class="button btn btn-default"><i class="process-icon-save"></i>'.$this->l('Import from Server').'</button>&nbsp;';
        $pre_settings_content3 .= '<br /><br />';

        $standard_options = array(
            'general' => array(
                'title' =>  $this->l('ORDERS PRODUCTS - generate XML'),
                'image' =>   '../img/t/AdminOrderPreferences.gif',
                'info' => $pre_settings_content,

                // 'submit' => array('title' => $this->l('Update'), 'class' => 'button'),
            ),

            

            'xmlproductfile' => array(
                'title' =>  $this->l('PRODUCT - Importing Product from XML'),
                'image' =>   '../img/t/AdminOrderPreferences.gif',
                 'info' => $pre_settings_content2,
            ),

            'xmlproductfilefromserver' => array(
                'title' =>  $this->l('PRODUCT - Importing Product from XML On Server'),
                'image' =>   '../img/t/AdminOrderPreferences.gif',
                 'info' => $pre_settings_content3,
            ),
        );



       
        $this->fields_options = $standard_options;

        if (empty($this->display)) {
            $this->page_header_toolbar_title = $this->l('ORDER P XML Settings');
            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => Context::getContext()->link->getAdminLink('AdminModules').'&configure=orderproductsxmlgenerator',
                'desc' => $this->l('Go to Configuration', null, null, false),
                'icon' => 'process-icon-back'
            );
        }

        return parent::renderOptions();
    }




    public function initContent()
    {

        try
        {

                if(Tools::isSubmit('generatexml'))
                {
                    Operations::generateXML();
                    $this->confirmations[] = "Succesful Generation";
                    //  Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token'));
                }

                if(Tools::isSubmit('generatexmlwithdownloading'))
                {
                    Operations::generateXMLANdDownload();
                    $this->confirmations[] = "Succesful Generation";
                    //  Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token'));
                }


                if(Tools::isSubmit('importxmlproductsbyuploading') || Tools::isSubmit('importxmlproductsfrominterne'))
                {
                
                    if(Tools::isSubmit('importxmlproductsbyuploading'))
                    {
                        $accept_ext = array('xml');
                        $file_data = explode('.', $_FILES['xmlproductfile']['name']);
                        $file_ext = end($file_data);

                        if(isset($_FILES['xmlproductfile']) && in_array($file_ext, $accept_ext) && $_FILES['xmlproductfile']['size'] > 0)
                        {
                            Operations::importXMLProductsByUploading($_FILES['xmlproductfile']['tmp_name']);
                        }
                    }else{
                        Operations::importXMLProductsFromInterne();
                    }
            
                    $this->confirmations[] = "Successful Importation";
                    //  Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token'));
                }

            }
            catch (Exception $e)
            {
              $error_message = $e->getMessage().'\\n'. $e->getCode().'\\n'. $e->getFile().'\\n'. $e->getLine().'\\n'. $e->getTraceAsString() ;  
              $this->errors[] = $error_message;
            }

        parent::initContent();
    }







    






   


  

   
  







}