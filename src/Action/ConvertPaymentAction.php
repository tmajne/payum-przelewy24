<?php

declare(strict_types = 1);

namespace Nova\Payum\P24\Action;

use Nova\Payum\P24\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details['p24_session_id'] = $payment->getNumber();
        $details['p24_currency'] = $payment->getCurrencyCode();
        $details['p24_amount'] = $payment->getTotalAmount();
        $details['p24_description'] = $payment->getDescription();
        $details['p24_email'] = $payment->getClientEmail();
        $details['status']  = Api::STATUS_NEW;

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
