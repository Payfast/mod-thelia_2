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

namespace PayFast\Controller;

use PayFast\Form\ConfigurationForm;
use PayFast\Model\PayFastConfigQuery;
use PayFast\PayFast;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

/**
 * PayFast payment module
 */
class ConfigurationController extends BaseAdminController
{

    /**
     * @return mixed an HTTP response, or
     */
    public function configure()
    {
        if ( null !== $response = $this->checkAuth( AdminResources::MODULE, 'PayFast', AccessManager::UPDATE ) )
        {
            return $response;
        }

        // Create the Form from the request
        $configurationForm = new ConfigurationForm( $this->getRequest() );

        try {
            // Check the form against constraints violations
            $form = $this->validateForm( $configurationForm, "POST" );

            // Get the form field values
            $data = $form->getData();

            foreach ( $data as $name => $value )
            {
                if ( is_array( $value ) )
                {
                    $value = implode( ';', $value );
                }

                PayFastConfigQuery::set( $name, $value );
            }

            // Log configuration modification
            $this->adminLogAppend(
                "payfast.configuration.message",
                AccessManager::UPDATE,
                sprintf( "PayFast configuration updated" )
            );

            // Redirect to the success URL,
            if ( $this->getRequest()->get( 'save_mode' ) == 'stay' )
            {
                // If we have to stay on the same page, redisplay the configuration page/
                $route = '/admin/module/PayFast';
            }
            else
            {
                // If we have to close the page, go back to the module back-office page.
                $route = '/admin/modules';
            }

            return $this->generateRedirect( URL::getInstance()->absoluteUrl( $route ) );

            // An exit is performed after redirect.
        }
        catch ( FormValidationException $ex )
        {
            // Form cannot be validated. Create the error message using
            // the BaseAdminController helper method.
            $error_msg = $this->createStandardFormValidationErrorMessage( $ex );
        }
        catch ( \Exception $ex )
        {
            // Any other error
            $error_msg = $ex->getMessage();
        }

        // At this point, the form has errors, and should be redisplayed. We do not redirect,
        // just redisplay the same template.
        // Setup the Form error context, to make error information available in the template.
        $this->setupFormErrorContext(
            $this->getTranslator()->trans( "PayFast configuration", [], PayFast::DOMAIN_NAME ),
            $error_msg,
            $configurationForm,
            $ex
        );

        // Do not redirect at this point, or the error context will be lost.
        // Just redisplay the current template.
        return $this->render( 'module-configure', array( 'module_code' => 'PayFast' ) );
    }
}