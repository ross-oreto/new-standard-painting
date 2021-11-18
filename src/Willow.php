<?php

namespace Alex\Nsp;

use Alex\Nsp\Routing\Router;
use Alex\Nsp\Routing\Routes;
use Base;
use JetBrains\PhpStorm\Pure;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Willow acts as a base controller for other concrete controllers.
 */
abstract class Willow {
    public static string $ASSETS_PATH = "/assets";
    public static Logger $logger;
    protected static Router $router;

    /**
     * Check the mode/environment of the application
     * Is the application running in dev,stage, or prod mode
     * @param Base $f3
     * @param string $mode The mode dev, stage, prod or any string
     * @return bool True if mode matches false otherwise
     */
    public static function isMode(Base $f3, string $mode): bool {
        return $f3->get("mode") === $mode;
    }
    public static function isDev(Base $f3): bool {
        return self::isMode($f3, "dev");
    }
    public static function isProd(Base $f3): bool {
        return self::isMode($f3, "prod");
    }
    public static function isStage(Base $f3): bool {
        return self::isMode($f3,"stage");
    }
    /**
     * @param Base $f3
     * @return bool True if the application is deployed to a host.
     */
    public static function isDeployed(Base $f3): bool {
        return self::isStage($f3) || self::isProd($f3);
    }

    /**
     * @param string $name Name of the asset
     * @param Base $f3
     * @param bool $dist If true, look for the minified distributed version of the asset
     * Always look for distributed js/css asset when deployed
     * @return string Path to the web asset
     */
     protected static function asset(Base $f3, string $name, bool $dist = false): string {
        return $dist || (self::isDeployed($f3) && (str_ends_with($name, "js") || str_ends_with($name, "css")))
            ? $f3->get("BASE").self::$ASSETS_PATH."/dist"."/$name"
            : $f3->get("BASE").self::$ASSETS_PATH."/$name";
    }

    /**
     * Lookup the i18n key in the dictionary
     * @param Base $f3
     * @param string $key The dictionary key
     * @param string|array|null $args Arguments passed for substitution in the message
     * @return string The resolved message
     */
    protected static function dict(Base $f3, string $key, string|array $args = NULL): string {
        return $f3->get($f3->get("PREFIX").$key, $args);
    }

    /**
     * Configure framework by reading config files from /config/
     * @param Base $f3
     * @param string $name Name of the config file
     * @return bool True if the file exists and is read, false otherwise
     */
    protected static function configure(Base $f3, string $name = "config.ini"): bool {
        $configPath = __DIR__."/../config/$name";
        if (file_exists($configPath)) {
           $f3->config($configPath, true);
           return true;
        }
        return false;
    }

    /**
     * Initialize Willow
     * Load config files, add extra functions, register error handlers etc.
     * Should be the first call in index.php after acquiring the Base::instance() $f3 object
     * @param Base $f3
     * @param Routes[] $routes
     */
    public static function run(Base $f3, array $routes): void {
        self::configure($f3);
        self::initLogger($f3);

        // mode functions in view templates
        $f3->set("isDev", function () use ($f3) { return self::isDev($f3); });
        $f3->set("isStage", function () use ($f3) { return self::isStage($f3); });
        $f3->set("isProd", function () use ($f3) { return self::isProd($f3); });
        $f3->set("isDeployed", function () use ($f3) { return self::isDeployed($f3); });

        // add the asset function for use in view templates
        $f3->set("asset", function (string $name) use ($f3) { return Willow::asset($f3, $name); });

        self::setErrorHandler($f3);
        self::initRouter($f3, $routes);

        $f3->run();
    }

    /**
     * @param Base $f3
     * @param Routes[] $routes
     */
    protected static function initRouter(Base $f3, array $routes): void {
        self::$router = Router::of($routes);
        foreach (self::$router->getRoutes() as $route) {
            $method = $route->getMethod();
            $name = $route->getName();
            $pattern = $route->getPattern();
            $type = $route->getType();
            $f3->route("$method @$name: $pattern$type"
                , $route->getHandler()
                , $route->ttl
                , $route->kbps);
        }
    }

    protected static function setErrorHandler(Base $f3) {
        $f3->set('ONERROR','Alex\Nsp\ErrorHandler::handle');
    }

    /**
     * Setup the app logger, which is the basis for other controller loggers.
     * @param Base $f3
     */
    protected static function initLogger(Base $f3): void {
        $logName = $f3->get("logName");
        $logName = $logName == null ? "app.log ": $logName;
        self::$logger = new Logger(Willow::class);
        $level = match ($f3->get("DEBUG")) {
            0 => Logger::ERROR,
            1 => Logger::NOTICE,
            2 => Logger::INFO,
            default => Logger::DEBUG
        };
        $stream = new StreamHandler($f3->get('LOGS').$logName, $level, true);
        $formatter = new LineFormatter(null, null, true, true);
        $stream->setFormatter($formatter);
        self::$logger->pushHandler($stream);
    }

    /**
     * Define routes for this Willow
     * @return Routes
     */
    protected abstract static function routes(): Routes;

    protected Logger $log;

    #[Pure] public function __construct() {
        $this->log = self::$logger->withName($this->logName());
    }

    /**
     * Provide easy override to define a separate logger.
     * @return string
     */
    protected function logName(): string {
       return get_class($this);
    }

    /**
     * the framework looks for a method in this class named beforeRoute().
     * If present, F3 runs the code contained in the beforeRoute() event handler
     * before transferring control to the method specified in the route
     * @param Base $f3
     */
    function beforeRoute(Base $f3) {
    }

    /**
     * @param string $view View to render inside the main template
     * @param Base $f3
     * @param bool $template If true render view inside a template, otherwise just the specified view
     * @return string The rendered template
     */
    protected function render(string $view, Base $f3, bool $template = true): string {
        $viewKey = str_replace('/', '.', $view);
        $f3->set('view', $viewKey);
        $f3->set('title', self::dict($f3, $viewKey.'.title'));
        $ext = $f3->get('ext');
        if ($template) {
            $f3->set('content',"$view$ext");
            return \Template::instance()->render("/template$ext");
        } else {
            return \Template::instance()->render("/$view$ext");
        }
    }

    /**
     * Default index page looks for home.htm
     * @param Base $f3
     */
    public function index(Base $f3) {
        echo $this->render("home", $f3);
    }
}