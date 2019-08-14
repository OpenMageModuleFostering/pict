<?php

class Pict_FeedBuilder_IndexController extends Mage_Core_Controller_Front_Action {

    public function IndexAction() {

        //$username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('secret');
        $requested_file = $this->getRequest()->getParam('requested_file');
        $isUserValidated = Mage::getModel('feedbuilder/feedBuilder')->validateUser($password);
        //$isUserValidated=true;
        if ($isUserValidated) {
            ///build product file

            $feedBuilder = Mage::getModel('feedbuilder/feedBuilder');
            $filePath = $feedBuilder->getFeedFilePath();
            $feedBuilder->generateProductsFeed();
            echo file_get_contents($filePath);
            //$this->_prepareDownloadResponse($feedBuilder->getFileName(), file_get_contents($filePath));
            return;
        }
    }

}