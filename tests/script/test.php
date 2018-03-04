<?php

declare(strict_types = 1);

//namespace Nova\Payum\P24\Tests\Script;

use Nova\Payum\P24\P24GatewayFactory;
use Payum\Core\PayumBuilder;
use Payum\Core\Payum;
use Payum\Core\Model\Payment;

require "../../vendor/autoload.php";

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 'port';

$defaultConfig = [];

$paymentClass = Payment::class;

$payumBuilder = new PayumBuilder();
/** @var \Payum\Core\Payum $payum */
$payum = $payumBuilder
    ->addDefaultStorages()
    ->addGatewayFactory('przelewy24', new P24GatewayFactory($defaultConfig))
    ->addGateway('przelewy24', [
        'factory' => 'przelewy24',
        'sandbox' => true,
        'tt' => 'aaaaaaaa',
    ])
    //->addGateway('aGateway', ['factory' => 'offline'])
    ->getPayum()
;

dump('======================================');
dump($payum->getGatewayFactories());
dump('======================================');
dump($payum->getGateways());

// prepare

$getwayName = 'p24';
$storage = $payum->getStorage($paymentClass);

dump('======================================');
dump($storage);