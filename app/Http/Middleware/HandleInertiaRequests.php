<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
// use Tightenco\Ziggy\Ziggy;
use App\Helper\Cart;
// use App\Http\Resources\CartResource;
use Illuminate\Foundation\Application;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    // public function share(Request $request): array
    // {
    //     return [
    //         ...parent::share($request),
    //         'auth' => [
    //             'user' => $request->user(),
    //         ],
    //     ];
    // }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(), // opcional pero útil
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'), // aquí está la magia
                ] : null,
            ],
            // 'ziggy' => fn () => [
            //     ...(new Ziggy)->toArray(),
            //     'location' => $request->url(),
            // ],
            // 'cart' => new CartResource(Cart::getProductsAndCartItems()),

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
                'idColonia' => fn() => $request->session()->get('idColonia'),
                'idLocalidad' => fn() => $request->session()->get('idLocalidad'),
                'activeTab' => fn() => $request->session()->get('activeTab'),
                'solicitud' => fn() => $request->session()->get('solicitud'),
                'idConstancia' => fn() => $request->session()->get('idConstancia'),
                'necesitaConfirmacion' => fn() => $request->session()->get('necesitaConfirmacion'),
            ],
            'canLogin' => app('router')->has('login'),
            'canRegister' => app('router')->has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'currentYear' => intval(date('Y')),

        ];
    }
}
