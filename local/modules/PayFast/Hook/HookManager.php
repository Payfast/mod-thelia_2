<?php
/**
 * PaymentController.php
 *
 * Copyright (c) 2018 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 * @author     Rheinhardt Snyman
 * @copyright  2018 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * 
 */

namespace PayFast\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

/**
 * Class HookManager
 *
 * @package PayFast\Hook
 */
class HookManager extends BaseHook
{

    public function onModuleConfigure( HookRenderEvent $event )
    { /*
        $event->add(
        $this->render('payfast/module-configuration.html')
        ); */
    }
}