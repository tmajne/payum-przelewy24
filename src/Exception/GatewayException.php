<?php

declare(strict_types = 1);

namespace Nova\Payum\P24\Exception;

use Payum\Core\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;

class GatewayException extends RuntimeException
{
    public static function factory(string $content)
    {
        if (empty($content)) {
            $label = 'P24 gateway empty response';
            $statusCode = 0;
        } else {
            $content = explode('&', $content);

            $statusCode = (int) explode('=',array_shift($content))[1];
            $content = implode('&', $content);
            $content = explode('=', $content)[1];
            $content = explode('&', $content);
            $label = implode("\n", $content);
        }

        $message = implode(PHP_EOL, array(
            $label,
            '[status code] '.$statusCode
        ));

        $e = new static($message, $statusCode);

        return $e;
    }
}
