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

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;

class PendingHttpRequest
{
    /**
     * The pre-request callback.
     *
     * @var \Illuminate\Support\Collection
     */
    private $beforeSendingCallbacks;

    /**
     * The Guzzle body format.
     *
     * @var stirng
     */
    private $bodyFormat = 'json';

    /**
     * The Guzzle options.
     *
     * @var array
     */
    private $options = ['http_errors' => false];

    /**
     * The Guzzle HandlerStack.
     *
     * @var \GuzzleHttp\HandlerStack
     */
    private $handler;

    /**
     * The Guzzle CookieJar.
     *
     * @var \GuzzleHttp\Cookie\CookieJar
     */
    private static $cookieJar;

    /**
     * Create a new PendingHttpRequest instance.
     */
    public function __construct()
    {
        $this->beforeSendingCallbacks = collect();
    }

    /**
     * Create a new PendingHttpRequest instance.
     *
     * @param mixed $args
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public static function new(...$args): self
    {
        return new self(...$args);
    }

    /**
     * Set the request body fomrat.
     *
     * @param string $format
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function bodyFormat(string $format): self
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * Set the "Content-Type" header.
     *
     * @param string $contentType
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function contentType(string $contentType): self
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    /**
     * Set the "Accept" header.
     *
     * @param string $header
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function accept(string $header): self
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    /**
     * Send the request body as "json payload".
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function asJson(): self
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * Send the request body as "form parameters".
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function asFormParams(): self
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Send the request body as "multipart".
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function asMultipart(): self
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * Send the request body as "body".
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function asBody(): self
    {
        return $this->bodyFormat('body');
    }

    /**
     * Set the base uri for all requests.
     *
     * @param string $uri
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withBaseUri(string $uri): self
    {
        return tap($this, function ($request) use ($uri) {
            return $this->options = array_merge_recursive($this->options, [
                'base_uri' => $uri,
            ]);
        });
    }

    /**
     * Use authentication for the request.
     *
     * @param string $username
     * @param string $password
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withBasicAuth(string $username, string $password): self
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    /**
     * Use authentication for the request.
     *
     * @param string $username
     * @param string $password
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withDigestAuth(string $username, string $password): self
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password, 'digest'],
            ]);
        });
    }

    public function withParams($params)
    {
        return tap($this, function ($request) use ($params) {
            return $this->options = array_merge_recursive($this->options, [
                'query' => $params,
            ]);
        });
    }

    public function withBase($baseUri)
    {
        return tap($this, function ($request) use ($baseUri) {
            return $this->options = array_merge_recursive($this->options, [
                'base_uri' => $baseUri,
            ]);
        });
    }

    public function withOptions($options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    /**
     * Send headers with the request.
     *
     * @param array $headers
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withHeaders(array $headers): self
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Disable Http redirects (301).
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withoutRedirecting(): self
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'allow_redirects' => false,
            ]);
        });
    }

    /**
     * Disable SSL certificate verification.
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withoutVerifying(): self
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'verify' => false,
            ]);
        });
    }

    /**
     * Store any cookies in a cookie jar.
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withCookies(): self
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => static::getCookieJar(),
            ]);
        });
    }

    /**
     * Get an instance of a Guzzle cookie jar.
     *
     * @return \GuzzleHttp\Cookie\CookieJar
     */
    public static function getCookieJar(): CookieJar
    {
        return static::$cookieJar ?: static::$cookieJar = new CookieJar();
    }

    /**
     * Handle requests with the given class.
     *
     * @param mixed $handler
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function withHandler($handler): self
    {
        return tap($this, function ($request) use ($handler) {
            return $this->handler = $handler;
        });
    }

    /**
     * Timeout of the request in seconds.
     *
     * @param int $seconds
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function timeout(int $seconds): self
    {
        return tap($this, function () use ($seconds) {
            return $this->options = array_merge_recursive($this->options, [
                'timeout' => $seconds,
            ]);
        });
    }

    /**
     * Set the pre-request Callback.
     *
     * @param \Closure $callback
     *
     * @return \BrianFaust\Http\PendingHttpRequest
     */
    public function beforeSending(Closure $callback): self
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    /**
     * Create and send an Http "GET" request.
     *
     * @param string $url
     * @param array  $queryParams
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function get(string $url, array $queryParams = []): HttpResponse
    {
        return $this->send('GET', $url, [
            'query' => $queryParams,
        ]);
    }

    /**
     * Create and send an Http "POST" request.
     *
     * @param string            $url
     * @param null|string|array $params
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function post(string $url, $params = null): HttpResponse
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Create and send an Http "PATCH" request.
     *
     * @param string $url
     * @param array  $params
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function patch(string $url, array $params = []): HttpResponse
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Create and send an Http "PUT" request.
     *
     * @param string $url
     * @param array  $params
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function put(string $url, array $params = []): HttpResponse
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Create and send an Http "DELETE" request.
     *
     * @param string $url
     * @param array  $params
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function delete(string $url, array $params = []): HttpResponse
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    /**
     * Create and send an Http request.
     *
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return \BrianFaust\Http\HttpResponse
     */
    public function send(string $method, string $url, array $options): HttpResponse
    {
        try {
            return new HttpResponse($this->buildClient()->request($method, $url, $this->mergeOptions([
                'query' => $this->parseQueryParams($url),
            ], $options)));
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new Exceptions\ConnectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new Guzzle Client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function buildClient(): Client
    {
        return new Client(['handler' => $this->buildHandlerStack()]);
    }

    /**
     * Create a new Guzzle HandlerStack instance.
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public function buildHandlerStack(): HandlerStack
    {
        static $handler;

        if (!$handler) {
            $handler = $this->handler ?? \GuzzleHttp\choose_handler();
        }

        if ($handler instanceof HandlerStack) {
            $stack = $handler;
        }

        return tap($stack ?? HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());
        });
    }

    /**
     * Build the pre-request callback.
     *
     * @return \Closure
     */
    public function buildBeforeSendingHandler(): Closure
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request), $options);
            };
        };
    }

    /**
     * Run the pre-request callback.
     *
     * @param \GuzzleHttp\Psr7\Request $request
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    public function runBeforeSendingCallbacks(Request $request): Request
    {
        return tap($request, function ($request) {
            $this->beforeSendingCallbacks->each->__invoke(new HttpRequest($request));
        });
    }

    /**
     * Merge the existing and given options.
     *
     * @param array $options
     *
     * @return array
     */
    public function mergeOptions(...$options): array
    {
        return array_merge_recursive($this->options, ...$options);
    }

    /**
     * Parse the given URL and return its query.
     *
     * @param string $url
     *
     * @return array
     */
    public function parseQueryParams(string $url): array
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        });
    }
}
