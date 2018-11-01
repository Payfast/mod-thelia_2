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

namespace PayFast\Form;

use PayFast\Model\PayFastConfigQuery;
use PayFast\PayFast;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

/**
 * Class ConfigurationForm
 * @package PayFast\Form
 */
class ConfigurationForm extends BaseForm
{
    protected function trans( $str, $params = [] )
    {
        return Translator::getInstance()->trans( $str, $params, PayFast::DOMAIN_NAME );
    }

    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'mode',
                'choice',
                [
                    'constraints' => [new NotBlank()],
                    'required' => true,
                    'choices' => [
                        'TEST' => $this->trans( 'Test' ),
                        'LIVE' => $this->trans( 'Live' ),
                    ],
                    'label' => $this->trans( 'Mode : ' ),
                    'data' => PayFastConfigQuery::read( 'mode', 'TEST' ),
                    'label_attr' => [
                        'for' => 'mode',
                        'help' => $this->trans( 'Select "Test" mode to test sandbox payments, and "Live" mode when you are ready to go live.' ),
                    ],
                ]
            )
            ->add(
                'merchant_id',
                'text',
                [
                    'constraints' => [],
                    'required' => false,
                    'label' => $this->trans( 'Merchant ID: ' ),
                    'data' => PayFastConfigQuery::read( 'merchant_id', '' ),
                    'label_attr' => [
                        'for' => 'merchant_key',
                        'help' => $this->trans( 'You can find your Merchant ID and Merchant Key on your payfast.co.za account under DASHBOARD.' ),
                    ],
                    'attr' => [
                        'rows' => 10,
                        'placeholder' => $this->trans( 'e.g. 1000010..' ),
                    ],
                ]
            )
            ->add(
                'merchant_key',
                'text',
                [
                    'constraints' => [],
                    'required' => false,
                    'label' => $this->trans( 'Merchant Key: ' ),
                    'data' => PayFastConfigQuery::read( 'merchant_key', '' ),
                    'label_attr' => [
                        'for' => 'merchant_key',
                        'help' => $this->trans( 'You can find your Merchant ID and Merchant Key on your payfast.co.za account under DASHBOARD.' ),
                    ],
                    'attr' => [
                        'rows' => 10,
                        'placeholder' => $this->trans( 'e.g. 46f0cd69458..' ),
                    ],
                ]
            )
            ->add(
                'passphrase',
                'text',
                [
                    'constraints' => [],
                    'required' => false,
                    'label' => $this->trans( ' Secure Passphrase: ' ),
                    'data' => PayFastConfigQuery::read( 'passphrase', '' ),
                    'label_attr' => [
                        'for' => 'passphrase',
                        'help' => $this->trans( 'The passphrase is an optional/ extra security feature that must be set on your PayFast.co.za account in order to be used. You can find your passphrase under SETTINGS > Integration SECURITY PASSPHRASE.' ),
                    ],
                    'attr' => [
                        'rows' => 10,
                        'placeholder' => $this->trans( 'Same as on your PayFast.co.za account' ),
                    ],
                ]
            )
            ->add(
                'debug',
                'checkbox',
                [
                    'value' => 0,
                    'constraints' => [],
                    'required' => false,
                    'label' => $this->trans( ' Debug ' ),
                    'data' => boolval( PayFastConfigQuery::read( 'debug', false ) ),
                    'label_attr' => [
                        'for' => 'debug',
                        'help' => $this->trans( 'Enable Debug to log the server-to-server communication. The log file for debugging can be found at /modules/payfast/payfast.log. If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.' ),
                    ],
                ]
            )
        ;
    }

    /**
     * @return string the name of your form. This name must be unique
     */
    public function getName()
    {
        return 'payfast_configuration_form';
    }
}