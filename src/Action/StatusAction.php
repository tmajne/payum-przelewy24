<?php

declare(strict_types = 1);

namespace Nova\Payum\P24\Action;

use Nova\Payum\P24\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        //dump('status');
        //dump($model);
        //dump($model['status']);
        //dump($request);
        if (null === $model['status'] || Api::STATUS_NEW == $model['status']) {
            $request->markNew();
            return;
        }
        /*elseif ($model['status'] == 'PENDING') {
            $request->markPending();
            return;
        } elseif ($model['status'] == 'COMPLETED') {
            $request->markCaptured();
            return;
        }elseif ($model['status'] == 'CANCELED') {
            $request->markCanceled();
            return;
        } elseif ($model['status'] == 'REJECTED') {
            $request->markFailed();
            return;
        }*/

        exit('unknown status');
        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
