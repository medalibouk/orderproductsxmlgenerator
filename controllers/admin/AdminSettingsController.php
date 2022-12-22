<?php



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
        // $pre_settings_content .= '<button type="submit" name="submitExportSettings" class="button btn btn-default"><i class="process-icon-export"></i>'.$this->l('Export settings').'</button>&nbsp;';
        // $pre_settings_content .= '<br /><br />';
    
   

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
        if(Tools::isSubmit('generatexml'))
        {
            $this->generateXML();
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
                    $this->importXMLProductsByUploading($_FILES['xmlproductfile']['tmp_name']);
                }
            }else{
                 $this->importXMLProductsFromInterne();
            }
    
            
            //  Tools::redirectAdmin(self::$currentIndex.'&token='.Tools::getValue('token'));
        }
        parent::initContent();
    }




    public function generateXML()
    {
       $id_order_state = (int) Configuration::get('ORDERPRODUCTSXMLGENERATOR_ORDER_STATE');
       $ordersIds = Order::getOrderIdsByStatus($id_order_state);
       $ordersInfo = array();

       foreach ($ordersIds as $orderId) {
           $order = new Order($orderId);
           $customer = new Customer($order->id_customer);
           $shippingAddress = new Address($order->id_address_delivery);
           $productsDetails = $order->getProducts();
          
                

                 $ordersInfo[$order->reference] = array(
                    'customer_name' => $customer->firstname. ' ' . $customer->lastname,
                    'total_amount_paid_with_tax' => $order->total_paid_tax_incl,
                    'total_amount_paid_without_tax' => $order->total_paid_tax_excl,
                    'shipping_address' => AddressFormat::generateAddress($shippingAddress, array(), '<br>'),
                    'rows' => array(),
                 );

                 foreach ($productsDetails as $productsDetail) {
                    $ordersInfo[$order->reference]['rows'][] = array(
                         'product_name' => $productsDetail['product_name'],
                         'product_reference' => $productsDetail['product_reference'],
                         'amount_ordered' => $productsDetail['product_quantity'],
                         'total_amount_paid_with_tax' => $productsDetail['total_price_tax_incl'],
                         'total_amount_paid_without_tax' => $productsDetail['total_price_tax_excl'],

                    );
                 }
                 
       }


      
    file_put_contents(_PS_MODULE_DIR_ . 'orderproductsxmlgenerator/orderxmlfiles/' . time() . 'orders.xml', $this->arrayToXml($ordersInfo));

    }


    public function arrayToXml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;
         
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<orders/>');
        }
         
        // Visit all key value pair
        foreach ($array as $k => $v) {
             
            // If there is nested array then
            if (is_array($v)) {
                 
                // Call function for nested array
                $this->arrayToXml($v, $k, $_xml->addChild($k));
                }
                 
            else {
                 
                // Simply add child element.
                $_xml->addChild($k, $v);
            }
        }
         
        return $_xml->asXML();
    }
    


    public function importXMLProducts($file = null)
    {
        try
        {
            
            $xml_data =  simplexml_load_file($file);
            $xml_data = json_decode(json_encode($xml_data), TRUE);
            foreach ($xml_data['PRODUCT'] as $value) 
           {
               
               $manufacturer = (bool) Manufacturer::getIdByName((string) $value['ART_MANUFACTURER']) ? new Manufacturer((int) Manufacturer::getIdByName((string) $value['ART_MANUFACTURER'])) : new Manufacturer();
               if(! (bool) Manufacturer::getIdByName($value['ART_MANUFACTURER']))
               {
                 $manufacturer->name = $value['ART_MANUFACTURER'];
                 $manufacturer->active = 1;
                 $manufacturer->save();
               }
               
               $productInfo = array(
               'name' => $value['ART_DESCRIPTION'],
               'link_rewrite' => self::slug($value['ART_DESCRIPTION']),
               'description' => $value['ART_DESCRIPTION'],
               'price' => $value['ART_PRICEEXCLTAX'],
               'state' => 1,
               'reference' => (string) $value['ART_NUMBER'],
               'active' => 1,
               'available_for_order' => 1,
               'show_price' => 1,
               'id_manufacturer' =>  $manufacturer->id,
               'id_category_default' => 2,
               );

               $idProduct = Product::getIdByReference($productInfo['reference']);
               $product = (bool) $idProduct ? new Product($idProduct) : new Product();
               if(!(bool) $idProduct)
               {
                foreach ($productInfo as $key => $value) 
                {
                  $product->{$key} = $value;
                }
                $product->save();
               }
              
   
               $id_stock_available = StockAvailable::getStockAvailableIdByProductId($product->id, 0);
               $stock = new StockAvailable($id_stock_available);
               if(Validate::isLoadedObject($stock))
               {
                   $stock->quantity = (int) $value['ART_STOCK'];
                   $stock->save();
                }
               
           }
           $this->confirmations[] = "Successful Importation";
        }
        catch (Exception $e)
        {
          $error_message = $e->getMessage().'\\n'. $e->getCode().'\\n'. $e->getFile().'\\n'. $e->getLine().'\\n'. $e->getTraceAsString() ;  
          $this->errors[] = $error_message;
        }

        

    }

    public function importXMLProductsByUploading($file = null)
    {
        $this->importXMLProducts($file);
    }

    public function importXMLProductsFromInterne()
    {
        $file = _PS_MODULE_DIR_ . 'orderproductsxmlgenerator/tmpxml/file.xml';
        $this->importXMLProducts($file);

    }

    public static function slug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }




   


  

   
  







}