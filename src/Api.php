<?php

declare(strict_types = 1);

namespace Nova\Payum\P24;

use ArrayAccess;
use GuzzleHttp\Psr7\Response;
use Http\Message\MessageFactory;
use Nova\Payum\P24\Exception\GatewayException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;

class Api
{
    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REFUNDED = 'refunded';

    private const METHOD_REGISTER = 'trnRegister';
    private const METHOD_VERIFY = 'trnVerify';
    private const METHOD_TEST = 'testConnection';

    /** Api version */
    private const VERSION = '3.2';

    /** defaulut api url */
    private const DEFAULT_URL = 'https://secure.przelewy24.pl/';

    private const SANDBOX_URL = 'https://sandbox.przelewy24.pl/';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [
        'p24_merchant_id' => null,
        'p24_pos_id' => null,
        'CRC' => null,
        'redirect' => true, // Set true to redirect to Przelewy24 after transaction registration
        'sandbox' => true,
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'p24_merchant_id', 'p24_pos_id', 'CRC'
        ]);

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;

        //dump($this);
    }

    /*public function testConnection()
    {
        $fields = [
            'p24_merchant_id' => $this->options['p24_merchant_id'],
            'p24_pos_id' => $this->options['p24_pos_id'],
            'p24_sign' => md5(
                $this->options['p24_pos_id'].'|'.$this->options['CRC']
            ),
        ];

        $response = $this->doRequest('testConnection', $fields);

        dump($fields);
        $res = $response->getBody()->getContents();
        dump(explode('&', $res));

        exit;
    }*/

    /**
     * @return bool
     */
    public function isRedirect(): bool
    {
        return (bool) $this->options['redirect'];
    }

    /**
     * Prepare a transaction request
     */
    public function trnRegister(ArrayAccess $details): string
    {
        $details->validateNotEmpty([
            'p24_session_id', 'p24_amount', 'p24_currency', 'p24_email', 'p24_description', 'notify_url', 'done_url'
        ]);

        //dump((array)$details);
        $fields = [
            'p24_api_version' => self::VERSION,
            'p24_sign' => $this->generateCrcSum($details, static::METHOD_REGISTER),
            'p24_merchant_id' => $this->options['p24_merchant_id'],
            'p24_pos_id' => $this->options['p24_pos_id'],
            'p24_session_id' => $details['p24_session_id'],
            'p24_amount' => $details['p24_amount'],
            'p24_currency' => $details['p24_currency'],
            'p24_description' => $details['p24_description'] ?? '',
            'p24_email' => $details['p24_email'],

            //'p24_url_return' => $details['done_url'],
            'p24_url_status' => $details['notify_url'],

            'p24_url_return' => 'http://rtdev.pl/payment/done?status=ok&source=p24',
            //'p24_url_status' => 'http://rtdev.pl/payment/notify/unsafe/p24',
            //'p24_url_cancel' => 'http://www.rtdev.pl',

            //'p24_method' => 25,
            //'p24_channel' => 16,
        ];
        //dump($fields);
        /** @var Response $response */
        $response = $this->doRequest(static::METHOD_REGISTER, $fields);

        return $response['data']['token'];
    }

    public function trnRequest(string $token)
    {
        header("Location:" . $this->getApiEndpoint()."trnRequest/".$token);
        exit;

        /*if ($redirect) {
            header("Location:" . $this->getApiEndpoint()."trnRequest/".$token);
            return "";
        } else {
            return $this->hostLive."trnRequest/".$token;
        }*/
    }

    public function trnVerify(ArrayAccess $details)
    {
        $details->validateNotEmpty([
            'p24_session_id', 'p24_amount', 'p24_currency', 'p24_order_id'
        ]);

        //dump((array)$details);
        $fields = [
            'p24_merchant_id' => $this->options['p24_merchant_id'],
            'p24_pos_id' => $this->options['p24_pos_id'],
            'p24_session_id' => $details['p24_session_id'],
            'p24_amount' => $details['p24_amount'],
            'p24_currency' => $details['p24_currency'],
            'p24_order_id' => $details['p24_order_id'],
            'p24_sign' => $this->generateCrcSum($details, static::METHOD_VERIFY),
        ];

        //dump($fields);
        /** @var Response $response */
        $response = $this->doRequest(static::METHOD_VERIFY, $fields);
        //dump($response);
        return $response['data'];
    }

    /**
     * @param ArrayAccess $details
     *
     * @return string
     */
    protected function generateCrcSum(ArrayAccess $details, string $type): string
    {
        switch ($type) {
            case static::METHOD_REGISTER:
                $controlSum = md5(
                    $details['p24_session_id'] . "|"
                    . $this->options['p24_merchant_id'] . "|"
                    . $details['p24_amount'] . "|"
                    . $details['p24_currency'] . "|"
                    . $this->options['CRC']
                );
                break;
            case static::METHOD_VERIFY:
                $controlSum = md5(
                    $details['p24_session_id'] . "|"
                    . $details['p24_order_id'] . "|"
                    . $details['p24_amount'] . "|"
                    . $details['p24_currency'] . "|"
                    . $this->options['CRC']
                );
                break;
            case static::METHOD_TEST:
                $controlSum = md5(
                    $this->options['p24_pos_id'] . "|"
                    . $this->options['CRC']
                );
                break;
            default:
                throw GatewayException::factory('Bad method call');
                break;
        }

        return $controlSum;
    }

    /**
     * @param $method
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest($function, array $fields)
    {
        $method = 'POST';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
//dump($fields);
        $request = $this->messageFactory->createRequest(
            $method,
            $this->getApiEndpoint().$function,
            $headers,
            http_build_query($fields)
        );

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $content = $response->getBody()->getContents();

        if (empty($content) || 0 !== stripos($content, 'error=0')) {
            throw GatewayException::factory($content);
        }

        $content = explode('&', $content);
        $status = (int) explode('=', $content[0])[1];

        $data = !empty($content[1]) ? ['token' => explode('=', $content[1])[1]] : null;
        $response = [
            'status' => $status,
            'data' => $data
        ];

        return $response;
    }

    /**
     * @return string
     */
    protected function getApiEndpoint(): string
    {
        return !$this->options['sandbox'] ?
            self::DEFAULT_URL :
            self::SANDBOX_URL;
    }
}
