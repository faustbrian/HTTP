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

use BrianFaust\Http\Http;
use BrianFaust\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class HttpTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        HttpServer::start();
    }

    public function url($url)
    {
        return vsprintf('%s/%s', [
            'http://localhost:'.getenv('TEST_SERVER_PORT'),
            ltrim($url, '/'),
        ]);
    }

    /** @test */
    public function query_parameters_can_be_passed_as_an_array()
    {
        $response = Http::get($this->url('/get'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertArraySubset([
            'query' => [
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
            'query' => [
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
            'query' => [
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
                'content-type' => ['application/json'],
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
                'content-type' => ['application/x-www-form-urlencoded'],
            ],
            'form_params' => [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
        ], $response->json());
    }

    /** @test */
    public function post_content_can_be_sent_as_multipart()
    {
        $response = Http::asMultipart()->post($this->url('/multi-part'), [
            [
                'name'     => 'foo',
                'contents' => 'bar',
            ], [
                'name'     => 'baz',
                'contents' => 'qux',
            ], [
                'name'     => 'test-file',
                'contents' => 'test contents',
                'filename' => 'test-file.txt',
            ],
        ])->json();

        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $response['body_content']);
        $this->assertTrue($response['has_file']);
        $this->assertEquals($response['file_content'], 'test contents');
        $this->assertStringStartsWith('multipart', $response['headers']['content-type'][0]);
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
                'content-type' => ['application/json'],
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
                'custom' => ['Header'],
            ],
        ], $response->json());
    }

    /** @test */
    public function post_with_additional_headers()
    {
        $response = Http::withHeaders(['Custom' => 'Header'])->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'custom' => ['Header'],
            ],
        ], $response->json());
    }

    /** @test */
    public function the_accept_header_can_be_set_via_shortcut()
    {
        $response = Http::accept('banana/sandwich')->post($this->url('/post'));

        $this->assertArraySubset([
            'headers' => [
                'accept' => ['banana/sandwich'],
            ],
        ], $response->json());
    }

    /** @test */
    public function exceptions_are_not_thrown_for_40x_responses()
    {
        $response = Http::withHeaders(['HTTP-Status' => 418])->get($this->url('/get'));

        $this->assertSame(418, $response->status());
    }

    /** @test */
    public function exceptions_are_not_thrown_for_50x_responses()
    {
        $response = Http::withHeaders(['HTTP-Status' => 508])->get($this->url('/get'));

        $this->assertSame(508, $response->status());
    }

    /** @test */
    public function redirects_are_followed_by_default()
    {
        $response = Http::get($this->url('/redirect'));

        $this->assertSame(200, $response->status());
    }

    /** @test */
    public function redirects_can_be_disabled()
    {
        $response = Http::withoutRedirecting()->get($this->url('/redirect'));

        $this->assertSame(302, $response->status());
        $this->assertSame($this->url('/redirected'), $response->header('Location'));
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
            'query' => [
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
            'query' => [
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
            'query' => [
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
            'query' => [
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
        $response = Http::get($this->url('/raw'));

        $this->assertSame('A simple string response', $response->body());
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
        $response = Http::withHeaders(['HTTP-Status' => 302])->get($this->url('/get'));

        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function can_check_if_a_response_is_client_error()
    {
        $response = Http::withHeaders(['HTTP-Status' => 404])->get($this->url('/get'));

        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isServerError());
    }

    /** @test */
    public function can_check_if_a_response_is_server_error()
    {
        $response = Http::withHeaders(['HTTP-Status' => 500])->get($this->url('/get'));

        $this->assertTrue($response->isServerError());
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isClientError());
    }

    /** @test */
    public function is_ok_is_an_alias_for_is_success()
    {
        $response = Http::withHeaders(['HTTP-Status' => 200])->get($this->url('/get'));

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

    /** @test */
    public function response_can_use_macros()
    {
        HttpResponse::macro('testMacro', function () {
            $body = $this->json()['json'];

            return sprintf('%s %s', $body['foo'], $body['baz']);
        });

        $response = Http::post($this->url('/post'), [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);

        $this->assertSame('bar qux', $response->testMacro());
    }

    /** @test */
    public function can_use_basic_auth()
    {
        $response = Http::withBasicAuth('username', 'password')->get($this->url('/auth/basic'));

        $this->assertTrue($response->isSuccess());
    }

    /** @test */
    public function can_use_digest_auth()
    {
        $response = Http::withDigestAuth('username', 'password')->get($this->url('/auth/digest'));

        $this->assertTrue($response->isSuccess());
    }

    /** @test */
    public function cookies_can_be_shared_between_requests()
    {
        $response = Http::withCookies()->get($this->url('/cookies'));
        $this->assertEmpty($response->body());

        Http::withCookies()->post($this->url('/cookies'));

        $response = Http::withCookies()->get($this->url('/cookies'));
        $this->assertEquals('bar', $response->body());

        $response = Http::get($this->url('/cookies'));
        $this->assertEmpty($response->body());
    }

    /**
     * @test
     * @expectedException \BrianFaust\Http\Exceptions\ConnectionException
     */
    public function client_will_force_timeout()
    {
        Http::timeout(1)->get($this->url('/timeout'));
    }
}

class HttpServer
{
    public static function start()
    {
        $pid = exec('php -S '.'localhost:'.getenv('TEST_SERVER_PORT').' -t ./tests/server/public > /dev/null 2>&1 & echo $!');

        while (@file_get_contents('http://localhost:'.getenv('TEST_SERVER_PORT').'/get') === false) {
            usleep(1000);
        }

        register_shutdown_function(function () use ($pid) {
            exec('kill '.$pid);
        });
    }
}
