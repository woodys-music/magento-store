<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';


class Fisheye_Ajaxcartpreview_CartController extends Mage_Checkout_CartController
{

  
  public function ajaxUpdatePostAction() {
    try {
            $cartData = $this->getRequest()->getParam('cart');
            var_dump($cartData);
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter($data['qty']);
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
  }
  
  
  public function ajaxDeleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        
        if ($id) {
            
            try {
                $this->_getCart()->removeItem($id)->save();
            } catch (Exception $e) {
                    $output['status'] = "F";
                    $output['status_msg'] = $this->__('We cannot remove this item from your shopping cart at the moment, please try again.');
                    die(json_encode($output));
            }
        }
        
            $output['status'] = "S";
            $output['status_msg'] = "All Good";
            die(json_encode($output));
    }
    
    
    
    public function ajaxCartPreviewAction()
    {
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(array('locale' => Mage::app()->getLocale()->
                    getLocaleCode()));
                $params['qty'] = $filter->filter($params['qty']);
            }
            $output = array();
            $product = $this->_initProduct();

            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                $this->_goBack();
                return;
            }

            $cart->addProduct($product, $params);

            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            /**
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete', array('product' => $product, 'request' =>
                $this->getRequest(), 'response' => $this->getResponse()));

            if (!$this->_getSession()->getNoCartRedirect(true)) {

                if (!$cart->getQuote()->getHasError()) {
                    $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->htmlEscape($product->
                        getName()));
                    //  $this->_getSession()->addSuccess($message);
                    $output['status'] = "S";
                    $output['status_msg'] = $message;
                    die(json_encode($output));
                }

                $this->_goBack();
            }
        }

        catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                //   $this->_getSession()->addNotice($e->getMessage());
                      $output['status'] = "F";
                    $output['status_msg'] = json_encode($e->getMessage());
                     die(json_encode($output));
                     
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $error[] = "\n - ".$message;
                    //$this->_getSession()->addError($message);
                }

                if (isset($error)) {
                    $warn[] = $this->__('%s could not be added to your shopping cart.', Mage::helper('core')->htmlEscape($product->
                        getName()))."\n";
                    
                    $errormsg = array_merge($warn,$error);
                    
                    $output['status'] = "F";
                    $output['status_msg'] = json_encode($errormsg);
                    $error = null;
                    die(json_encode($output));
                }

            }

        }
        catch (exception $e) {

            $output['status'] = "F";
            $output['status_msg'] = json_encode($this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            die(json_encode($output));
        }
    }



}
