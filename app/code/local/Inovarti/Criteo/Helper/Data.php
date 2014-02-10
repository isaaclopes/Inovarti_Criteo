<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Criteo
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Inovarti_Criteo_Helper_Data extends Mage_Core_Helper_Abstract {

    public function isTrackingAllowed() {
        return Mage::getStoreConfigFlag('criteo/criteo/enabled');
    }

    public function isHomepage() {
        return (Mage::getSingleton('cms/page')->getIdentifier() == 'home' ? true : false);
    }

    public function getPageType() {
        $_pagetype = '';
        $_controllerName = Mage::app()->getRequest()->getControllerName();
        $_frontcontroller = Mage::app()->getFrontController()->getRequest()->getRouteName();
        $_actionName = Mage::app()->getRequest()->getActionName();
        switch ($_controllerName) {
            case 'index':
                if ($this->isHomepage())
                    $_pagetype = 'home';
                break;
            case 'category':
                $_pagetype = 'category';
                break;
            case 'product':
                $_pagetype = 'product';
                break;
            case 'cart':
                $_pagetype = 'cart';
                break;
            case 'onepagecheckout':
                if ($_actionName == 'success') {
                    $_pagetype = 'purchase';
                }
                break;
            case 'onestepcheckout':
                if ($_actionName == 'success') {
                    $_pagetype = 'purchase';
                }
                break;
            case 'onepage':
                if ($_actionName == 'success') {
                    $_pagetype = 'purchase';
                }
                break;
            default:
                break;
        }
        return $_pagetype;
    }

}
