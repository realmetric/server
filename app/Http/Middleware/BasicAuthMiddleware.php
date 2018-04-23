<?php


namespace App\Http\Middleware;

use App\Injectable;
use GuzzleHttp\Psr7\Response;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicAuthMiddleware implements MiddlewareInterface
{
    private $options = [
        'user' => null,
        'password' => null,
    ];

    public function __construct(array $options = [])
    {
        $this->hydrate($options);
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (!$this->authenticate($request)){
            return new Response(401, [], 'Access denied');
        }
        return $delegate->process($request);
    }

    public function setUser(string $user)
    {
        $this->options['user'] = $user;
        return $this;
    }

    public function setPassword(string $password)
    {
        $this->options['password'] = $password;
        return $this;
    }

    private function authenticate(ServerRequestInterface $request):bool
    {
        $serverParams = $request->getServerParams();
        $user = $serverParams['PHP_AUTH_USER'] ?? null;
        $password = $serverParams['PHP_AUTH_PW'] ?? null;

        if ($user !== $this->options['user'] ||
            ($password !== $this->options['password'] && $password !== sha1($this->options['password']))){
            return false;
        }

        return true;
    }

    private function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }
}