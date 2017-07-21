<?php

/*
 * This file is part of Http.
 *
 * (c) Brian Faust <hello@brianfaust.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrianFaust\Http;

class Http
{
    /**
     * Dynamically handle calls into the Http request.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return PendingHttpRequest::new()->{$method}(...$parameters);
    }
}
