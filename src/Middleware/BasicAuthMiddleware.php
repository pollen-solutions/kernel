<?php declare(strict_types=1);

namespace Pollen\Kernel\Middleware;

use Nyholm\Psr7\Response;
use Pollen\Routing\BaseMiddleware;
use Pollen\Routing\RouterInterface;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\RequestHandlerInterface;

class BasicAuthMiddleware extends BaseMiddleware
{
    /**
     * @var array
     */
    protected array $users = [];

    /**
     * Defines de users.
     *
     * @param array $users [username => password]
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * Execute the middleware.
     *
     * @param PsrRequest $request
     * @param RequestHandlerInterface $handler
     *
     * @return PsrResponse
     */
    public function process(PsrRequest $request, RequestHandlerInterface $handler): PsrResponse
    {
        $authorization = self::parseAuthorizationHeader($request->getHeaderLine('Authorization'));

        if ($authorization && $this->checkUserPassword($authorization['username'], $authorization['password'])) {
            return $handler->handle($request);
        }

        return new Response(401, ['WWW-Authenticate' => 'Basic realm="Login"']);
    }

    /**
     * Validate the user and password.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function checkUserPassword(string $username, string $password): bool
    {
        return !(!isset($this->users[$username]) || $this->users[$username] !== $password);
    }

    /**
     * Parses the authorization header for a basic authentication.
     *
     * @param string $header
     *
     * @return false|array
     */
    private static function parseAuthorizationHeader(string $header)
    {
        if (!str_starts_with($header, 'Basic')) {
            return false;
        }

        $credentials = explode(':', base64_decode(substr($header, 6)), 2);

        return [
            'username' => $credentials[0],
            'password' => $credentials[1] ?? null,
        ];
    }
}
