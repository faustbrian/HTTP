<?php

/*
 * This file is part of Http.
 *
 * (c) Brian Faust <hello@brianfaust.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use BrianFaust\Http\Http;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class HttpTest extends TestCase
{
    public function url($url)
    {
        return sprintf('http://httpbin.org/%s', ltrim($url, '/'));
    }

    /** @test */
    public function query_parameters_can_be_passed_as_an_array()
    {
        $response = Http::get($this->url('/get'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_in_urls_are_respected()
    {
        $response = Http::get($this->url('/get?foo=bar&baz=qux'));

        $this->assertArraySubset([
            'args' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_in_urls_can_be_combined_with_array_parameters()
    {
        $response = Http::get($this->url('/get?foo=bar'), [
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function post_content_is_json_by_default()
    {
        $response = Http::post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_form_params()
    {
        $response = Http::asFormParams()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_json_explicitly()
    {
        $response = Http::asJson()->post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function get_with_additional_headers()
    {
        $response = Http::withHeaders(['Custom' => 'Header'])->get($this->url('/get'));

        $this->assertArraySubset([
            'headers' => [
                'Custom' => 'Header',
            ],
        ], $response->json());
    }

    /** @test */
    public function post_with_additional_headers()
    {
        $response = Http::withHeaders(['Custom' => 'Header'])->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'Custom' => 'Header',
            ],
        ], $response->json());
    }

    /** @test */
    public function the_accept_header_can_be_set_via_shortcut()
    {
        $response = Http::accept('banana/sandwich')->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'Accept' => 'banana/sandwich',
            ],
        ], $response->json());
    }

    /** @test */
    public function exceptions_are_not_thrown_for_40x_responses()
    {
        $response = Http::get($this->url('/status/418'));

        $this->assertSame(418, $response->status());
    }

    /** @test */
    public function exceptions_are_not_thrown_for_50x_responses()
    {
        $response = Http::get($this->url('/status/508'));

        $this->assertSame(508, $response->status());
    }

    /** @test */
    public function redirects_are_followed_by_default()
    {
        $response = Http::get($this->url('/redirect/1'));

        $this->assertSame(200, $response->status());
    }

    /** @test */
    public function redirects_can_be_disabled()
    {
        $response = Http::withoutRedirecting()->get($this->url('/redirect/1'));

        $this->assertSame(302, $response->status());
        $this->assertSame('/get', $response->header('Location'));
    }

    /** @test */
    public function patch_requests_are_supported()
    {
        $response = Http::patch($this->url('/patch'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function put_requests_are_supported()
    {
        $response = Http::put($this->url('/put'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function delete_requests_are_supported()
    {
        $response = Http::delete($this->url('/delete'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_are_respected_in_post_requests()
    {
        $response = Http::post($this->url('/post?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_are_respected_in_put_requests()
    {
        $response = Http::put($this->url('/put?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_are_respected_in_patch_requests()
    {
        $response = Http::patch($this->url('/patch?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function query_parameters_are_respected_in_delete_requests()
    {
        $response = Http::delete($this->url('/delete?banana=sandwich'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'args' => [
                'banana' => 'sandwich',
            ],
            'json' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function can_retrieve_the_raw_response_body()
    {
        $response = Http::get($this->url('/links/1/0'));

        $this->assertSame('<html><head><title>Links</title></head><body>0 </body></html>', $response->body());
    }

    /** @test */
    public function can_retrieve_the_xml_response_body()
    {
        $response = Http::get($this->url('/xml'));

        $this->assertInstanceOf(SimpleXMLElement::class, $response->xml());
    }

    /** @test */
    public function can_retrieve_response_header_values()
    {
        $response = Http::get($this->url('/get'));

        $this->assertSame('application/json', $response->header('Content-Type'));
        $this->assertSame('application/json', $response->headers()['Content-Type']);
    }

    /** @test */
    public function can_check_if_a_response_is_success()
    {
        $response = Http::withHeaders(['Z-Status' => 200])->get($this->url('/get'));

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function can_check_if_a_response_is_redirect()
    {
        $response = Http::withoutRedirecting()->get($this->url('/status/302'));

        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function can_check_if_a_response_is_client_error()
    {
        $response = Http::get($this->url('/status/404'));

        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function can_check_if_a_response_is_server_error()
    {
        $response = Http::get($this->url('/status/500'));

        $this->assertTrue($response->isServerError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
    }

    /** @test */
    public function is_ok_is_an_alias_for_is_success()
    {
        $response = Http::get($this->url('/status/200'));

        $this->assertTrue($response->isOk());
        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function multiple_callbacks_can_be_run_before_sending_the_request()
    {
        $state = [];

        $response = Http::beforeSending(function ($request) use (&$state) {
            return tap($request, function ($request) use (&$state) {
                $state['url'] = $request->url();
                $state['method'] = $request->method();
            });
        })->beforeSending(function ($request) use (&$state) {
            return tap($request, function ($request) use (&$state) {
                $state['headers'] = $request->headers();
                $state['body'] = $request->body();
            });
        })->withHeaders(['Http-Status' => 200])->post($this->url('/post'), ['foo' => 'bar']);

        $this->assertSame($this->url('/post'), $state['url']);
        $this->assertSame('POST', $state['method']);
        $this->assertArrayHasKey('User-Agent', $state['headers']);
        $this->assertSame(200, (int) $state['headers']['Http-Status']);
        $this->assertSame(json_encode(['foo' => 'bar']), $state['body']);
    }
}
