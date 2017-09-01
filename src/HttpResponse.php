<?php

declare(strict_types=1);

/*
 * This file is part of Http.
 *
 * (c) Brian Faust <hello@brianfaust.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrianFaust\Http;

use SimpleXMLElement;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Traits\Macroable;

class HttpResponse
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The Guzzle Response.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    private $response;

    /**
     * Create a new Http response instance.
     *
     * @param \GuzzleHttp\Psr7\Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->response->{$method}(...$parameters);
    }

    /**
     * Gets the body of the message.
     *
     * @return string
     */
    public function body(): ?string
    {
        return $this->response->getBody()->getContents();
    }

    /**
     * Gets the body of the message as JSON.
     *
     * @return array
     */
    public function json(): ?array
    {
        return json_decode($this->response->getBody()->getContents(), true);
    }

    /**
     * Gets the body of the message as XML.
     *
     * @return \SimpleXMLElement
     */
    public function xml(): ?SimpleXMLElement
    {
        return simplexml_load_string(
            utf8_encode($this->response->getBody()->getContents()),
            'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS
        );
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $header
     *
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Retrieves all header values.
     *
     * @param string $header
     *
     * @return array
     */
    public function headers(): array
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    /**
     * Gets the response status code.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if the request returned a "success" status.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Alias of "isSuccess".
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->isSuccess();
    }

    /**
     * Check if the request returned a "redirect" status.
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Check if the request returned a "client error" status.
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Check if the request returned a "server error" status.
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Convert the response to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }
}
