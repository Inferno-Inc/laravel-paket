<?php

/*
 * This file is part of Laravel Paket.
 *
 * (c) Anton Komarev <anton@komarev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cog\Laravel\Paket;

use Cog\Contracts\Paket\Job\Repositories\JobRepository as JobRepositoryContract;
use Cog\Laravel\Paket\Console\Commands\Setup;
use Cog\Laravel\Paket\Job\Repositories\JobFileRepository;
use Cog\Laravel\Paket\Requirement\Events\RequirementInstalling;
use Cog\Laravel\Paket\Requirement\Events\RequirementUninstalling;
use Cog\Laravel\Paket\Requirement\Listeners\RequirementInstallingListener;
use Cog\Laravel\Paket\Requirement\Listeners\RequirementUninstallingListener;
use Cog\Laravel\Paket\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class PaketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConsoleCommands();
    }

    public function boot(): void
    {
        $this->registerPublishes();
        $this->registerResources();
        $this->registerRoutes();
        $this->registerBindings();
        $this->registerListeners();
    }

    private function getRouteConfiguration(): array
    {
        return [
            'namespace' => 'Cog\Laravel\Paket\Http\Controllers',
            'prefix' => 'paket',
            'middleware' => 'web',
        ];
    }

    private function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'paket');
    }

    private function registerPublishes(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/paket'),
            ], 'paket-assets');
        }
    }

    private function registerRoutes(): void
    {
        Route::group($this->getRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    private function registerConsoleCommands(): void
    {
        $this->commands([
            Setup::class,
        ]);
    }

    private function registerBindings(): void
    {
        $this->app->singleton(Composer::class, function () {
            return new Composer(
                $this->app->make(Filesystem::class),
                base_path(),
                storage_path('paket/jobs')
            );
        });

        $this->app->singleton(JobRepositoryContract::class, function () {
            return new JobFileRepository(
                $this->app->make(Filesystem::class),
                storage_path('paket')
            );
        });
    }

    private function registerListeners(): void
    {
        Event::listen(RequirementInstalling::class, RequirementInstallingListener::class);
        Event::listen(RequirementUninstalling::class, RequirementUninstallingListener::class);
    }
}
