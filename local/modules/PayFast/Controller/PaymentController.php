<?php
/**
 * PaymentController.php
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 * @author     Jonathan Page
 * @version    1.0.2
 *
 * @author     Ron Darby <ron.darby@payfast.co.za>
 * @version    1.0.2
 * @date       12/12/2013
 * 
 * @copyright  2013 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace PayFast\Controller;

use PayFast\Model\PayFastConfigQuery;
use PayFast\PayFast;
use Thelia\Module\BasePaymentModuleController;

include dirname( __FILE__ ) . '/../payfast_common.inc.php';

class PaymentController extends BasePaymentModuleController
{
    protected function getModuleCode()
    {
        return "PayFast";
    }

    /**
     * Process a PayFast payment request
     */
    public function processPayFastRequest()
    {
        // Check if this is an ITN request
        // Has to be done like this (as opposed to "exit" as processing needs
        // to continue after this check.
        if ( $_GET['itn_request'] == 'true' )
        {
            // Variable Initialization
            $pfError = false;
            $pfErrMsg = '';
            $pfDone = false;
            $pfData = array();
            $pfHost = (  ( PayFastConfigQuery::read( 'mode' ) == 'LIVE' ) ? 'www' : 'sandbox' ) . '.payfast.co.za';
            $pfOrderId = '';
            $pfParamString = '';

            $payfast = new PayFast();

            pflog( 'PayFast ITN call received' );

            //// Notify PayFast that information has been received
            if ( !$pfError && !$pfDone )
            {
                header( 'HTTP/1.0 200 OK' );
                flush();
            }

            //// Get data sent by PayFast
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Get posted data' );

                // Posted variables from ITN
                $pfData = pfGetData();

                pflog( 'PayFast Data: ' . print_r( $pfData, true ) );

                if ( $pfData === false )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_ACCESS;
                }
            }

            //// Verify security signature
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Verify security signature' );

                $passPhrase = PayFastConfigQuery::read( 'passphrase' );
                $pfPassPhrase = empty( $passPhrase ) ? null : $passPhrase;

                // If signature different, log for debugging
                if ( !pfValidSignature( $pfData, $pfParamString, $pfPassPhrase ) )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
                }
            }

            //// Verify source IP (If not in debug mode)
            if ( !$pfError && !$pfDone && !PF_DEBUG )
            {
                pflog( 'Verify source IP' );

                if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
                }
            }

            // Get internal cart
            if ( !$pfError && !$pfDone )
            {
                $order_id = $pfData['m_payment_id'];
                // Get order data
                $order = $this->getOrder( $order_id );

                pflog( "Purchase:\n" . print_r( $order->getId(), true ) );
            }

            //// Verify data received
            if ( !$pfError )
            {
                pflog( 'Verify data received' );

                $pfValid = pfValidData( $pfHost, $pfParamString );

                if ( !$pfValid )
                {
                    $pfError = true;
                    $pfErrMsg = PF_ERR_BAD_ACCESS;
                }
            }

            //// Check Amounts
            $dbAmount = $order->getTotalAmount();

            $amountCheck = pfAmountsEqual( $dbAmount, $pfData['amount_gross'] );

            if ( !$amountCheck )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
            }

            //// Check status and update order
            if ( !$pfError && !$pfDone )
            {
                pflog( 'Check status and update order' );

                switch ( $pfData['payment_status'] )
                {
                    case 'COMPLETE':
                        pflog( '- Complete' );

                        // Payment OK !
                        $this->confirmPayment( $order_id );

                        break;

                    case 'FAILED':
                        pflog( '- Failed' );

                        break;

                    case 'PENDING':
                        pflog( '- Pending' );

                        // Need to wait for "Completed" before processing
                        break;

                    default:
                        // If unknown status, do nothing (safest course of action)
                        break;
                }
            }

            // If an error occurred
            if ( $pfError )
            {
                pflog( 'Error occurred: ' . $pfErrMsg );
            }

            // Close log
            pflog( '', true );
            exit();
        }
    }
}
