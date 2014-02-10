<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Criteo
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Inovarti_Criteo_Block_Block extends Mage_Core_Block_Abstract {

    public function __construct() {
        parent::__construct();
        $this->setCriteoConversionId(Mage::getStoreConfig('criteo/criteo/criteo_conversion_id'));
    }

    protected function _toHtml() {
        $_helper = Mage::helper('criteo');
        $html = "";

        if (Mage::helper('criteo')->isTrackingAllowed()) {
            $_conversionId = $this->getCriteoConversionId();
            $_pagetype = $_helper->getPageType();
            $tagHome = '';
            $tagCart = '';
            $tagProduct = '';
            $tagPurchase = '';
            $tagCategory = '';

            if ($_pagetype == 'home') {
                $tagHome = '{ event: "viewHome"}';
            }

            if ($_pagetype == 'category') {
                $count = 0;
                $tagCategory = '{ event: "viewList",item: [';

                $cat_id = Mage::registry('current_category')->getId();
                $products = Mage::getModel('catalog/category')->load($cat_id)
                        ->getProductCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToFilter('status', 1)
                        ->setOrder('entity_id', 'DESC');
                foreach ($products as $_product):
                    $count++;
                    if ($count == count($products)):
                        $tagCategory .= '"' . $_product->getSku() . '"';
                    else:
                        $tagCategory .= '"' . $_product->getSku() . '",';
                    endif;
                endforeach;
                $tagCategory .='],keywords: "' . $this->htmlEscape(Mage::registry('current_category')->getName()) . '"}';
            }

            if ($_pagetype == 'product') {
                $tagProduct = '{ event: "viewItem", item: "' . Mage::registry('current_product')->getSku() . '" }';
            }

            if ($_pagetype == 'cart') {
                $tagCart = '{event: "viewBasket", item: [';

                $count = 0;
                $cartLines = Mage::helper('checkout/cart')->getCart()->getItems();
                $PriceString = 0;
                $quantityString = 0;
                $price = 0;
                $IdString = 0;
                foreach ($cartLines as $cartLine):
                    $count++;
                    $product = Mage::getModel('catalog/product')->load($cartLine->getProductId());
                    if ($product->getSpecialPrice()) {
                        $price = $product->getSpecialPrice();
                    } else {
                        $price = $product->getPrice();
                    }

                    /* Get Configurable Sku from Simple product SKU/ID.
                     * If there is no configurable/simple product set up, then just use the standard Sku display
                     * */
                    $parentId = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($cartLine->getProductId());
                    $configurableProductSku = Mage::getModel('catalog/product')->load($parentId)->getSku();
                    if ($configurableProductSku) {
                        $IdString .= $configurableProductSku;
                    } else {
                        $IdString .= $cartLine->getSku();
                    }
                    $PriceString .= $price;
                    $quantityString .= (int) $cartLine->getQty();
                    if ($count == count($cartLines)):
                        $tagCart .='{ id: "' . $IdString . '", price: "' . number_format($PriceString, 2, '.', ' ') . '", quantity: "' . $quantityString . '"} ';
                    else:
                        $tagCart .='{ id: "' . $IdString . '", price: "' . number_format($PriceString, 2, '.', ' ') . '", quantity: "' . $quantityString . '"}, ';
                    endif;

                    $IdString = '';
                    $PriceString = '';
                    $quantityString = '';
                endforeach;
                $tagCart .=']}';
            }


            if ($_pagetype == 'purchase') {
                $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
                $order = Mage::getModel('sales/order')->load($orderId);
                $items = $order->getAllItems();
                
                $tagPurchase = '{event: "trackTransaction" , id: "' . $order->getIncrementId() . '", item: [';
                $count = 0;
                $PriceString = 0;
                $quantityString = 0;
                $price = 0;
                $IdString = 0;

                foreach ($items as $item):
                    $count++;
                    /* Get Configurable Sku from Simple product SKU/ID.
                     * If there is no configurable/simple product set up, then just use the standard Sku display
                     * */
                    $parentId = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
                    $configurableProductSku = Mage::getModel('catalog/product')->load($parentId)->getSku();
                    if ($configurableProductSku) {
                        $IdString .= $configurableProductSku;
                    } else {
                        $IdString .= $item->getSku();
                    }
                    $PriceString .= $item->getPrice();
                    $quantityString .= (int) $item->getQtyOrdered();
                    if ($count == count($items)):
                        $tagPurchase .='{ id: "' . $IdString . '", price:"' . number_format($PriceString, 2, '.', ' ') . '", quantity:"' . $quantityString . '" } ';
                    else:
                        $tagPurchase .='{ id: "' . $IdString . '", price:"' . number_format($PriceString, 2, '.', ' ') . '", quantity:"' . $quantityString . '" },';
                    endif;

                    $IdString = '';
                    $PriceString = '';
                    $quantityString = '';
                endforeach;

                $tagPurchase .=']}';
            }
            if ($_pagetype) {

                $html .= '<script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>';

                $html .='<script type="text/javascript"> 
                        window.criteo_q = window.criteo_q || []; window.criteo_q.push( 
                        { event: "setAccount", account: ' . $_conversionId . '},';
                if (Mage::getSingleton('customer/session')->isLoggedIn()) :
                    $customerData = Mage::getSingleton('customer/session')->getCustomer();
                    $html .= '{ event: "setCustomerId", id: "' . $customerData->getId() . '"},';
                endif;
                $html .='{ event: "setSiteType", type: "d"}, 
                        ' . $tagHome . '
                        ' . $tagCategory . '
                        ' . $tagProduct . '
                        ' . $tagCart . '
                        ' . $tagPurchase . '
                        ); 
                        </script>';
            }
        }
        return $html;
    }

}
