<?php

namespace BlackpigCreatif\Ephemeride;

use BlackpigCreatif\Ephemeride\Commands\MakeProviderCommand;
use BlackpigCreatif\Ephemeride\Livewire\Calendar;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EphemeridServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ephemeride')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasAssets()
            ->discoversMigrations()
            ->hasCommands([
                MakeProviderCommand::class,
            ]);
    }

    public function bootingPackage(): void
    {
        // 1. Livewire component tag: <livewire:ephemeride-calendar />
        Livewire::component('ephemeride-calendar', Calendar::class);

        // 2. Anonymous Blade component path: <x-ephemeride::event-chip />, etc.
        //    Anonymous components (view-only, no PHP class) are registered via
        //    anonymousComponentPath rather than componentNamespace.
        Blade::anonymousComponentPath(
            __DIR__.'/../resources/views/components',
            'ephemeride'
        );
    }
}
