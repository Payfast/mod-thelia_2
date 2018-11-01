<?php
/**
 * PaymentController.php
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 * @author     Rheinhardt Snyman
 * @copyright  2018 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 *
 */

namespace PayFast;

use PayFast\Model\Map\PayFastConfigTableMap;
use PayFast\Model\PayFastConfigQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Propel;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Model\ConfigQuery;
use Thelia\Model\MessageQuery;
use Thelia\Model\ModuleImageQuery;
use Thelia\Model\Order;
use Thelia\Module\AbstractPaymentModule;

class PayFast extends AbstractPaymentModule
{
    /** @var string */
    const DOMAIN_NAME = 'payfast';

    const SANDBOX_MERCHANT_ID = '10000100';
    const SANDBOX_MERCHANT_KEY = '46f0cd694581a';

    /**
     * Payment gateway invocation
     *
     * @param  Order $order processed order
     * @return Response the payment form
     */
    public function pay( Order $order )
    {

        include 'payfast_common.inc.php';

        // Convert files into standard var => value array
        $data = array();

        // Use appropriate merchant identifiers
        // Live
        if ( PayFastConfigQuery::read( 'mode' ) == 'LIVE' )
        {
            $data['merchant_id'] = PayFastConfigQuery::read( 'merchant_id' );
            $data['merchant_key'] = PayFastConfigQuery::read( 'merchant_key' );
            $payfast_url = 'https://www.payfast.co.za/eng/process';
        }
        // Sandbox
        else
        {
            $data['merchant_id'] = self::SANDBOX_MERCHANT_ID;
            $data['merchant_key'] = self::SANDBOX_MERCHANT_KEY;
            $payfast_url = 'https://sandbox.payfast.co.za/eng/process';
        }
        // Create URLs
        $data['return_url'] = $this->getPaymentSuccessPageUrl( $order->getId() );
        $data['cancel_url'] = $this->getPaymentFailurePageUrl(
            $order->getId(),
            Translator::getInstance()->trans( "You canceled the payment", [], PayFast::DOMAIN_NAME )
        );

        $data['notify_url'] = ConfigQuery::read( "url_site", '' ) . "/web/payfast/callback";

        //Buyer details
        $customer = $order->getCustomer();
        $address = $customer->getDefaultAddress();
        // Customer phone (first non empty)
        $phone = $address->getPhone();
        if ( empty( $phone ) )
        {
            $phone = $address->getCellphone();
        }

        $data['name_first'] = $customer->getFirstname();
        $data['name_last'] = $customer->getLastname();
        $data['email_address'] = $customer->getEmail();
        $data['m_payment_id'] = $order->getId();
        $data['amount'] = number_format( sprintf( "%01.2f", $this->getCurrentOrderTotalAmount() ), 2, '.', '' );

        $data['item_name'] = ConfigQuery::getStoreName() . ' purchase, Order #' . $order->getId();
        $data['custom_str1'] = 'PF_THELIA_2.3.4_' . constant( 'PF_MODULE_VER' );

        $pfOutput = '';
        // Create output string
        foreach ( $data as $key => $val )
        {
            $pfOutput .= $key . '=' . urlencode( trim( $val ) ) . '&';
        }

        $passPhrase = PayFastConfigQuery::read( 'passphrase' );
        if ( empty( $passPhrase ) || PayFastConfigQuery::read( 'mode' ) != 'LIVE' )
        {
            $pfOutput = substr( $pfOutput, 0, -1 );
        }
        else
        {
            $pfOutput = $pfOutput . "passphrase=" . urlencode( $passPhrase );
        }

        $data['signature'] = md5( $pfOutput );

        // Generate the form

        return $this->generateGatewayFormResponse( $order, $payfast_url, $data );

    }

    /**
     * @param ConnectionInterface|null $con
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function postActivation( ConnectionInterface $con = null )
    {
        // Once activated, create the module schema in the Thelia database.
        $database = new Database( $con );

        try {
            PayFastConfigQuery::create()->findOne();
        }
        catch ( \Exception $e )
        {
            $database->insertSql( null, array(
                __DIR__ . DS . 'Config' . DS . 'thelia.sql', // The module schema
            ) );
        }

        // Deploy the module's image
        $module = $this->getModuleModel();

        if ( ModuleImageQuery::create()->filterByModule( $module )->count() == 0 )
        {
            $this->deployImageFolder( $module, sprintf( '%s/images', __DIR__ ), $con );
        }

    }

    /**
     * @param ConnectionInterface|null $con
     * @param bool $deleteModuleData
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function destroy( ConnectionInterface $con = null, $deleteModuleData = false )
    {
        // Delete config table and messages if required
        if ( $deleteModuleData )
        {
            $database = new Database( $con );

            $database->execute( "DROP TABLE ?", PayFastConfigTableMap::TABLE_NAME );

            MessageQuery::create()->findOneByName( self::CONFIRMATION_MESSAGE_NAME )->delete();
        }
    }

    /* This method is called by the Payment loop.
     *
     * If you return true, the payment method will be displayed
     * If you return false, the payment method will not be displayed
     *
     * @return boolean
     */

    //Todo subscription check

    public function isValidPayment()
    {
        // At this point, the order does not exists yet in the database. We have to get
        // item count from the customer cart.

        /** @var Session $session */
        $session = $this->getRequest()->getSession();

        /** @var Cart $cart */
        $cart = $session->getSessionCart();

        $cartContentCount = $cart->countCartItems();

        // BaseModule::getCurrentOrderTotalAmount() is a convenient methods
        // to get order total from the current customer cart.

        $orderTotal = $this->getCurrentOrderTotalAmount();

        return $cartContentCount <= 10 && $orderTotal < 8000;
    }
}