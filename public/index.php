<?php

declare(strict_types=1);

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\ServiceManager\ServiceManager;
use Asgrim\MiniMezzio\AppFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';
$dependencies                       = $config['dependencies'];
$dependencies['services']['config'] = $config;
$container = new ServiceManager($dependencies);
$router = new FastRouteRouter();

$app = AppFactory::create($container, $router);
$app->pipe(new RouteMiddleware($router));
$app->pipe(new DispatchMiddleware());
$app->get('/', new readonly class($container->get(TemplateRendererInterface::class)) implements RequestHandlerInterface {
    private Memcached $mc;

    public function __construct(private TemplateRendererInterface $renderer)
    {
        $this->mc = new Memcached('mc');
        $this->mc->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        if (!count($this->mc->getServerList())) {
            $this->mc->addServers([
                ['cache',11211],
            ]);
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'memcached_version' => $this->mc->getVersion(),
            'extension_loaded' => extension_loaded('memcached'),
            'cleared' => $this->mc->flush(),
            'set' => $this->mc->set('item', 'item', 3600),
            'get' => $this->mc->get('item'),
        ];
        return new HtmlResponse($this->renderer->render('app::home', $data));
    }
});
$app->run();
