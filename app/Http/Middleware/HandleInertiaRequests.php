<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'menus' => $this->getMenus()?->map->toArray(),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error')
            ],
        ];
    }

    protected function getMenus()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user !== null) {
            return \Illuminate\Support\Facades\Cache::remember("menus.{$user->id}", now()->addHours(5), function () use ($user) {
                return \App\Models\Menu::query()
                    ->with(['children' => function ($query) use ($user) {
                        $query->whereHas('roles', fn($query) => $query->whereIn('role_id', $user->roles->pluck('id')));
                        $query->whereIsActive(true)->orderBy('sort_num', 'asc');
                    }])
                    ->whereNull('parent_id')
                    ->whereHas('roles', fn($query) => $query->whereIn('role_id', $user->roles->pluck('id')))
                    ->whereIsActive(true)
                    ->orderBy('sort_num', 'asc')
                    ->get();
            });
        }
    }
}
