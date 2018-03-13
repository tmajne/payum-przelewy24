<?php

declare(strict_types = 1);

namespace Nova\Payum\P24\Action;

use Nova\Payum\P24\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\Payment;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Api $api */
        $api = $this->api;

        /** @var Payment $payment */
        $payment = $request->getFirstModel();

        /** @var ArrayObject $details */
        $details = ArrayObject::ensureArrayObject($request->getModel());

        $token = $api->trnRegister($details);

        if (
            true === $details->get('redirect')
            || (null === $details->get('redirect') && $api->isRedirect())
        ) {
            $api->trnRequest($token);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
