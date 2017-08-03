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

use GuzzleHttp\Psr7\Request;

class HttpRequest
{
    /**
     * Create a new HttpRequest instance.
     *
     * @param \GuzzleHttp\Psr7\Request $guzzleRequest
     */
    public function __construct(Request $guzzleRequest)
    {
        $this->request = $guzzleRequest;
    }

    /**
     * Get the url of the request.
     *
     * @return string
     */
    public function url(): ?string
    {
        return (string) $this->request->getUri();
    }

    /**
     * Get the method of the request.
     *
     * @return string
     */
    public function method(): ?string
    {
        return $this->request->getMethod();
    }

    /**
     * Gets the body of the request.
     *
     * @return string
     */
    public function body(): ?string
    {
        return (string) $this->request->getBody();
    }

    /**
     * Gets all header values.
     *
     * @return array
     */
    public function headers(): array
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }
}
