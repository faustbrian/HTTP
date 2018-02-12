<?php

require_once __DIR__.'/../../../vendor/autoload.php';

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

function build_response($request)
{
    return response()->json([
        'headers'     => $request->header(),
        'query'       => $request->query(),
        'json'        => $request->json()->all(),
        'form_params' => $request->request->all(),
    ], $request->header('HTTP-Status', 200));
}

$app->router->get('/get', function () {
    return build_response(app('request'));
});

$app->router->post('/post', function () {
    return build_response(app('request'));
});

$app->router->put('/put', function () {
    return build_response(app('request'));
});

$app->router->patch('/patch', function () {
    return build_response(app('request'));
});

$app->router->delete('/delete', function () {
    return build_response(app('request'));
});

$app->router->get('/redirect', function () {
    return redirect('redirected');
});

$app->router->get('/redirected', function () {
    return 'Redirected!';
});

$app->router->get('/raw', function () {
    return 'A simple string response';
});

$app->router->get('/xml', function () {
    return "<?xml version='1.0' encoding='UTF-8'?><http><name>John</name></http>";
});

$app->router->get('/timeout', function () {
    sleep(2);
});

$app->router->get('/auth/basic', function () use ($app) {
    $request = $app['request'];

    $headers = [
        (bool) preg_match('/Basic\s[a-zA-Z0-9]+/', $request->header('Authorization')),
        $request->header('php-auth-user') === 'username',
        $request->header('php-auth-pw') === 'password',
    ];

    return (count(array_unique($headers)) === 1) ? response(null, 200) : response(null, 401);
});

/*
 * Made by @bastien-phi.
 */
$app->router->get('/auth/digest', function () {
    $realm = 'Restricted area';

    $authorization = app('request')->server->get('PHP_AUTH_DIGEST');
    if (!$authorization) {
        return response(null, 401)->header(
            'WWW-Authenticate',
            'Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"'
        );
    }

    $data = ['nonce' => null, 'nc' => null, 'cnonce' => null, 'qop' => null, 'username' => null, 'uri' => null, 'response' => null];
    foreach (array_keys($data) as $key) {
        if (!preg_match("@$key=(?:\"(.*)\"|'(.*)'|(.*),)@U", $authorization, $matches)) {
            return response(null, 401);
        }
        $data[$key] = array_values(array_filter($matches))[1];
    }

    if ($data['username'] != 'username') {
        return response(null, 401);
    }

    $a = md5('username:'.$realm.':password');
    $b = md5(app('request')->server->get('REQUEST_METHOD').':'.$data['uri']);
    $validResponse = md5($a.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$b);

    if ($data['response'] != $validResponse) {
        return response(null, 401);
    }

    return response(200);
});

$app->router->post('/multi-part', function () {
    return response()->json([
        'body_content' => app('request')->only(['foo', 'baz']),
        'has_file'     => app('request')->hasFile('test-file'),
        'file_content' => file_get_contents($_FILES['test-file']['tmp_name']),
        'headers'      => app('request')->header(),
    ], 200);
});

$app->router->post('/cookies', function () {
    return response(null, 200)->withCookie(
       new \Symfony\Component\HttpFoundation\Cookie('foo', 'bar')
   );
});

$app->router->get('/cookies', function () {
    return response(app('request')->cookies->get('foo'), 200);
});

$app->run();
