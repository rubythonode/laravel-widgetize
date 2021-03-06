<?php

namespace Imanghafoori\Widgets;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\Widgets\Utils\Normalizer;
use Imanghafoori\Widgets\Utils\Normalizers\CacheNormalizer;
use Imanghafoori\Widgets\Utils\Normalizers\ControllerNormalizer;
use Imanghafoori\Widgets\Utils\Normalizers\PresenterNormalizer;
use Imanghafoori\Widgets\Utils\Normalizers\TemplateNormalizer;

class WidgetsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('widgetize.php'),
        ]);

        $this->_defineDirectives();
        $this->loadViewsFrom($this->app->basePath() . '/app/Widgets/', 'Widgets');
    }

    /**
     * Define Blade Directives
     */
    private function _defineDirectives()
    {
        $omitParenthesis = version_compare($this->app->version(), '5.3', '<');

        Blade::directive('widget', function ($expression) use ($omitParenthesis) {
            $expression = $omitParenthesis ? $expression : "($expression)";

            return "<?php echo app(\\Imanghafoori\\Widgets\\Utils\\WidgetRenderer::class)->renderWidget{$expression}; ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'widgetize');
        $this->commands('command.imanghafoori.widget');
        $this->_registerSingletons();
        $this->_registerMacros();
    }

    /**
     * Register classes as singletons
     */
    private function _registerSingletons()
    {
        $this->app->singleton('command.imanghafoori.widget', function ($app) {
            return $app['Imanghafoori\Widgets\WidgetGenerator'];
        });

        $this->app->singleton(Normalizer::class, function () {
            $cacheNormalizer = new CacheNormalizer();
            $tplNormalizer = new TemplateNormalizer();
            $presenterNormalizer = new PresenterNormalizer();
            $ctrlNormalizer = new ControllerNormalizer();

            return new Utils\Normalizer($tplNormalizer, $cacheNormalizer, $presenterNormalizer, $ctrlNormalizer);
        });

        $this->app->singleton(Utils\HtmlMinifier::class, function () {
            return new Utils\HtmlMinifier();
        });

        $this->app->singleton(Utils\DebugInfo::class, function () {
            return new Utils\DebugInfo();
        });

        $this->app->singleton(Utils\Policies::class, function () {
            return new Utils\Policies();
        });

        $this->app->singleton(Utils\Cache::class, function () {
            return new Utils\Cache();
        });

        $this->app->singleton(Utils\CacheTag::class, function () {
            return new Utils\CacheTag();
        });

        $this->app->singleton(Utils\WidgetRenderer::class, function () {
            return new Utils\WidgetRenderer();
        });
    }

    private function _registerMacros()
    {
        Route::macro('view', function ($url, $view, $name = null) {
            return Route::get($url, [
                'as' => $name,
                'uses' => function () use ($view) {
                    return view($view);
                }
            ]);
        });

        Route::macro('widget', function ($url, $widget, $name = null) {
            return Route::get($url, [
                'as' => $name,
                'uses' => function (...$args) use ($widget) {
                    return render_widget($widget, $args);
                }
            ]);
        });

        Route::macro('jsonWidget', function ($url, $widget, $name = null) {
            return Route::get($url, [
                'as' => $name,
                'uses' => function (...$args) use ($widget) {
                    return json_widget($widget, $args);
                }
            ]);
        });
    }
}
