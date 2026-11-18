<?php

namespace App\Providers;

use App\Models\PrintTemplate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer(['layouts.app', 'members.roster'], function (View $view): void {
            $category = match (true) {
                request()->routeIs('members.index', 'members.roster') => 'members',
                request()->routeIs('census') => 'census',
                request()->routeIs('events.show') => 'events',
                default => null,
            };
            $view->with('printTemplates', auth()->check()
                && $category !== null
                ? PrintTemplate::select('id', 'name', 'format', 'created_at')->where('category', $category)->orderBy('name')->get()
                : collect());
        });
    }
}
