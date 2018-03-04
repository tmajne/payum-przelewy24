<?php

declare(strict_types = 1);

namespace Nova\Payum\P24;

use Nova\Payum\P24\Action\AuthorizeAction;
use Nova\Payum\P24\Action\CancelAction;
use Nova\Payum\P24\Action\ConvertPaymentAction;
use Nova\Payum\P24\Action\CaptureAction;
use Nova\Payum\P24\Action\NotifyAction;
use Nova\Payum\P24\Action\RefundAction;
use Nova\Payum\P24\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class P24GatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'p24',
            'payum.factory_title' => 'Przelewy24',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    (array) $config,
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };
        }
    }
}
