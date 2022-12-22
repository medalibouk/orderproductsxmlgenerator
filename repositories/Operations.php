<?php
namespace OrderProductsXmlGenerator\Repositories;

use Manufacturer;
use Product;
use StockAvailable;
use Exception;
use Validate;
use SimpleXMLElement;
use Configuration;
use Order;
use Customer;
use Address;
use AddressFormat;

class Operations 
{
  

    
    public static function generateXML()
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


    $fileName = time().'_orders.xml';
    file_put_contents(_PS_MODULE_DIR_ . 'orderproductsxmlgenerator/orderxmlfiles/' . $fileName , Operations::arrayToXml($ordersInfo));
    return $fileName;
    }



    public static function generateXMLANdDownload()
    {

      $fileName = self::generateXML();
      $path = _PS_MODULE_DIR_ . 'orderproductsxmlgenerator/orderxmlfiles/' . $fileName;
      if(file_exists($path)) {

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($path).'"');
            header('Content-Length: ' . filesize($path));
            header('Pragma: public');
            
            flush();
            
            readfile($path,true);
            
            die();
        }
        else{
            echo "File path does not exist.";
        }

    }

    
    public static function importXMLProducts($file = null)
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
         

    }

    public static function importXMLProductsByUploading($file = null)
    {
        self::importXMLProducts($file);
    }

    public static function importXMLProductsFromInterne()
    {
        $file = _PS_MODULE_DIR_ . 'orderproductsxmlgenerator/tmpxml/file.xml';
        self::importXMLProducts($file);

    }

    public static function slug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }




    public static function arrayToXml($array, $rootElement = null, $xml = null) {
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
                self::arrayToXml($v, $k, $_xml->addChild($k));
                }
                 
            else {
                 
                // Simply add child element.
                $_xml->addChild($k, $v);
            }
        }
         
        return $_xml->asXML();
    }



}
