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

$app->get('/get', function () {
    return build_response(app('request'));
});

$app->post('/post', function () {
    return build_response(app('request'));
});

$app->put('/put', function () {
    return build_response(app('request'));
});

$app->patch('/patch', function () {
    return build_response(app('request'));
});

$app->delete('/delete', function () {
    return build_response(app('request'));
});

$app->get('/redirect', function () {
    return redirect('redirected');
});

$app->get('/redirected', function () {
    return 'Redirected!';
});

$app->get('/raw', function () {
    return 'A simple string response';
});

$app->get('/xml', function () {
    return "<?xml version='1.0' encoding='UTF-8'?><http><name>John</name></http>";
});

$app->get('/auth/basic', function () use ($app) {
    $request = $app['request'];

    $headers = [
        (bool) preg_match('/Basic\s[a-zA-Z0-9]+/', $request->header('Authorization')),
        $request->header('php-auth-user') === 'username',
        $request->header('php-auth-pw') === 'password',
    ];

    return (count(array_unique($headers)) === 1) ? response(null, 200) : response(null, 401);
});

$app->run();
