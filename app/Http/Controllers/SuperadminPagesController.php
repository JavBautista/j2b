<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SuperadminPagesController extends Controller
{
    public function index(){
        // Estadísticas de tiendas
        $totalShops = Shop::count();
        $activeShops = Shop::where('active', true)->count();
        $trialShops = Shop::where('is_trial', true)->where('active', true)->count();

        // Estadísticas de usuarios
        $totalUsers = User::count();
        $activeUsers = User::where('active', true)->count();

        // Estadísticas de suscripciones
        $activeSubscriptions = Shop::where('subscription_status', 'active')->count();

        // Suscripciones por vencer (próximos 7 días)
        $expiringSubscriptions = Shop::where('active', true)
            ->where(function($query) {
                $query->where(function($q) {
                    // Trial por vencer
                    $q->where('is_trial', true)
                      ->whereNotNull('trial_ends_at')
                      ->whereBetween('trial_ends_at', [now(), now()->addDays(7)]);
                })->orWhere(function($q) {
                    // Suscripción por vencer
                    $q->where('is_trial', false)
                      ->whereNotNull('subscription_ends_at')
                      ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)]);
                });
            })
            ->with(['plan', 'owner'])
            ->get();

        // Ingresos del mes actual
        $monthlyRevenue = Subscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'active')
            ->sum('total_amount');

        // Últimas tiendas registradas
        $recentShops = Shop::with(['plan', 'owner'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('superadmin.index', compact(
            'totalShops',
            'activeShops',
            'trialShops',
            'totalUsers',
            'activeUsers',
            'activeSubscriptions',
            'expiringSubscriptions',
            'monthlyRevenue',
            'recentShops'
        ));
    }

    public function shops(){
        return view('superadmin.shops');
    }

    public function plans(){
        return view('superadmin.plans');
    }

    public function users(){
        return view('superadmin.users');
    }

    public function preRegisters(){
        return view('superadmin.pre_registers');
    }

    public function cfdi(){
        return view('superadmin.cfdi');
    }

    public function cfdiFacturas(){
        return view('superadmin.cfdi_facturas');
    }
}
