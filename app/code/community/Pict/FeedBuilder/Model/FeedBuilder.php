<?php

class Pict_FeedBuilder_Model_FeedBuilder extends Mage_Core_Model_Abstract {

     
    private $fileName = "";

    //http://www.edmondscommerce.co.uk/magento/encryping-data-with-magento/


    protected function _construct() {

        parent::_construct();
        $this->_init('feedbuilder/feedBuilder');
    }

    public function validateUser( $password) {
        

            if ($password == $this->getPassword())
                return true;
        

        return false;
    }

   

   
    
    public function getFeedFilePath() {
        try {
            $baseDir = Mage::getBaseDir();
            $varDir = $baseDir . DS . 'var';
            $pictfeedDir = $varDir . DS . 'pict_feed';
            $file = new Varien_Io_File();
            $file->checkAndCreateFolder($pictfeedDir);
            $csvFileNamePrefix = Mage::getStoreConfig("pict_extensions_section1/pict_extensions_group2/csv_filename_prefix");

            //$fileName = $csvFileNamePrefix . Mage::getSingleton('core/date')->date('d-m-Y_H-i-s') . '_csv.csv';
            $fileName = $csvFileNamePrefix . '_csv.csv';

            $this->setFileName($fileName);

            $fileNameWithPath = $pictfeedDir . DS . $fileName;
            $canWrite = $file->isWriteable($pictfeedDir);

            if (!$canWrite) {
                throw new Exception('Permission ISSUE please follow this link http://www.magentocommerce.com/wiki/groups/227/resetting_file_permissions');
            }

             


            return $fileNameWithPath;
        } catch (Exception $e) {
           // echo $e->getMessage();
            throw new Exception('Something went wrong during creating folder' . $e->getMessage());
        }
    }

    public function generateProductsFeed() {


        try {

            $error = false;
            ; //file generation 
            //Mage::log("generateProductsFeed:: STARTED", null, "generateProductsFeed.log");
            $feedFilePath =  $this->getFeedFilePath();
          $outPutHeaders = array(
                'id', //sku
                'name',
                'description',
                'price',
                'product_url', // product image url
                'qty',
                'meta_title', //categoreis
                'categories',
                'image_link', //short desc
                'additional_image_link',

            );
            $csvData[] = $outPutHeaders;
            //writing headers
            $file = fopen($feedFilePath, 'w');
            //write headers
            $this->writeData($file, $csvData);




            //paging
            $pageSize = 100;
            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToFilter('visibility', array('neq' => 1))
                    // Ensure the product is enabled
                    ->addAttributeToFilter('status', 1);
            $productsCount = $collection->count();
            $loopEnd = ceil($productsCount / $pageSize);
            //loop via products 
            for ($i = 1; $i <= $loopEnd; $i++) {

                $csvData = array();
                //writing headers
                //echo "I am in loop ";
                $collection = Mage::getModel('catalog/product')->getCollection()
                         
                        ->addAttributeToSelect('sku')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('price')
                        ->addAttributeToSelect('short_description')
                        ->addAttributeToSelect('meta_title')
                        // Ensure the product is visible
                        ->addAttributeToFilter('visibility', array('neq' => 1))
                        // Ensure the product is enabled
                        ->addAttributeToFilter('status', 1)
                        ->addAttributeToSort('entity_id', 'desc')
                        ->setPage($i, $pageSize);
                if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
                    $collection->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
                }

                //   echo "Going to Split tht";
                #$product_collection->getSelect()->limit(1, 1);   //where $limit will be the number of results we want, $starting_from will be the index of the result set to be considered as starting point (note 0 is the index of first row)
               // echo "<br>TOTAL NEW COUNT RESULTS : " . count($collection);
               // echo "<br>PAGE NO : " . $i;
                
               


                foreach ($collection->getItems() as $_product) {

                    $productRow = array();
                    $productRow['id'] = $_product->getSku(); //product's sku
                    $productRow['title'] = $_product->getName(); //product name
                    $productRow['description'] = $_product->getShortDescription(); //product's short description
                    //$productRow['abc']= $_product->getDescription(); // product's long description
                    $productRow['price'] = $_product->getPrice(); //product's regular Price
                    //$productRow['special_price']= $_product->getSpecialPrice(); //product's special Price
                    $productRow['link'] = $_product->getProductUrl(); //product url
                    //$productRow['image_link'] = $_product->getImageUrl(); //product's image url
                    $productRow['qty'] = $_product->getQty(); // product's QTY
                    $productRow['meta_title'] = $_product->getMetaTitle();

                    //render categories
                    $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');
                    $categoriesNames = "";
                    foreach ($categories as $category) {
                        $categoriesNames.=$category->getName() . ";";
                    }
                    $productRow['product_type'] = $categoriesNames;
                    $galleryImages = "";
                    
                    $product = Mage::getModel('catalog/product')->load($_product->getId());
                    
                    $baseImage=false;
                     $baseImageUrl="";
                    foreach ($product->getMediaGalleryImages() as $image) {
                         if($baseImage==false)
                         {
                            $baseImageUrl=   $image->getUrl();
                                 $baseImage=true;
                                 continue;
                         }
                        
                        $galleryImages.=$image->getUrl(). ";";
                    }
                    $productRow['image_link'] =$baseImageUrl;
                    // $productRow['gallery'] = $galleryImages;


                    // foreach ($product->getMediaGalleryImages() as $image) {
                        
                    //     $galleryImages.=$image->getUrl(). ";";
                    // }

                     $productRow['additional_image_link'] = $galleryImages;
                    //wring products to 
                    $csvData[] = $productRow;
                    $file = fopen($feedFilePath, 'a');
                    $this->writeData($file, $csvData);
                    unset($_product);
                    unset($categories);
                    unset($productRow);
                    unset($csvData);
                     
                }
                unset($collection);
            }
        } catch (Exception $e) {
            $error = true;
            throw new Exception(('Something went wrong during product read') . $e->getMessage());
        }

        //var_dump($feed);
        
       
    }

    

    public function getPassword() {
        return Mage::getStoreConfig("pict_extensions_section1/pict_extensions_group1/password");
    }

     
    public function getFileName() {
        return $this->fileName;
    }

    public function setFileName($fname) {
        $this->fileName = $fname;
    }

    

    public function writeData($file, $data) {
        $_delimiter = Mage::getStoreConfig("pict_extensions_section1/pict_extensions_group2/feild_delim");

        if (ord($_delimiter) == 92)
            $_delimiter = "\t";

        $_enclosure = Mage::getStoreConfig("pict_extensions_section1/pict_extensions_group2/field_enclosure");
        $fh = $file; //fopen($file, 'a');
        foreach ($data as $dataRow) {
            $this->fputcsv($fh, $dataRow, $_delimiter, $_enclosure);
        }
        fclose($fh);
        return $this;
    }

    public function fputcsv(&$handle, $fields = array(), $delimiter = ',', $enclosure = '"') {
        $str = '';
        $escape_char = '\\';
        foreach ($fields as $value) {
            if (strpos($value, $delimiter) !== false ||
                    strpos($value, $enclosure) !== false ||
                    strpos($value, "\n") !== false ||
                    strpos($value, "\r") !== false ||
                    strpos($value, "\t") !== false ||
                    strpos($value, ' ') !== false) {
                $str2 = $enclosure;
                $escaped = 0;
                $len = strlen($value);
                for ($i = 0; $i < $len; $i++) {
                    if ($value[$i] == $escape_char) {
                        $escaped = 1;
                    } else if (!$escaped && $value[$i] == $enclosure) {
                        $str2 .= $enclosure;
                    } else {
                        $escaped = 0;
                    }
                    $str2 .= $value[$i];
                }
                $str2 .= $enclosure;
                $str .= $str2 . $delimiter;
            } else {
                $str .= $enclosure . $value . $enclosure . $delimiter;
            }
        }
        $str = substr($str, 0, -1);
        $str .= "\n";
        return fwrite($handle, $str);
    }

}


