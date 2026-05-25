<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function analytics(Request $request)
    {
        // Get reservation period from query parameter (daily, weekly, monthly)
        $reservationPeriod = $request->query('reservationPeriod', 'daily');

        // Key Metrics
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $totalCustomers = Order::distinct('customer_name')->count();
        
        // Revenue by day (last 30 days)
        $revenueData = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as revenue'),
            DB::raw('COUNT(*) as orders')
        )
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Order Status Distribution
        $orderStatusData = Order::select('order_status as status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->get();

        // Payment Method Distribution
        $paymentMethodData = Order::select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        // Popular Products (from order items - using stored product data)
        $popularProducts = OrderItem::select(
                'name',
                'category',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(subtotal) as revenue')
            )
            ->groupBy('name', 'category')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        // Category Performance (from order items)
        $categoryData = OrderItem::select(
                'category',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(subtotal) as revenue')
            )
            ->whereNotNull('category')
            ->groupBy('category')
            ->get();

        // ============ RESERVATION ANALYTICS ============
        $reservationData = $this->getReservationAnalytics($reservationPeriod);

        // Total Reservations Count
        $totalReservations = Reservation::count();

        return response()->json([
            'success' => true,
            'data' => [
                'keyMetrics' => [
                    'totalRevenue' => (float) $totalRevenue,
                    'totalOrders' => $totalOrders,
                    'averageOrderValue' => round($averageOrderValue, 2),
                    'totalCustomers' => $totalCustomers,
                    'growthRate' => 0, // You can calculate this based on previous period
                ],
                'revenueData' => $revenueData,
                'orderStatusData' => $orderStatusData,
                'paymentMethodData' => $paymentMethodData,
                'popularProducts' => $popularProducts,
                'categoryData' => $categoryData,
                'totalReservations' => $totalReservations,
                'reservationData' => $reservationData,
            ]
        ]);
    }

    /**
     * Get reservation analytics - Walk-in vs Online
     * @param string $period 'daily', 'weekly', or 'monthly'
     */
    private function getReservationAnalytics($period = 'daily')
    {
        // Get total counts
        // is_walkin = 1 means walk-in customer
        // is_walkin = 0 means online reservation
        $totalWalkIns = Reservation::where('is_walkin', 1)->count();
        $totalOnline = Reservation::where('is_walkin', 0)
            ->orWhereNull('is_walkin') // Include old records without is_walkin field
            ->count();
        
        $totalReservations = $totalWalkIns + $totalOnline;

        // Calculate percentages
        $walkInPercentage = $totalReservations > 0 ? ($totalWalkIns / $totalReservations) * 100 : 0;
        $onlinePercentage = $totalReservations > 0 ? ($totalOnline / $totalReservations) * 100 : 0;

        // Get trends based on period
        $trends = $this->getReservationTrends($period);

        return [
            'totalWalkIns' => $totalWalkIns,
            'totalOnline' => $totalOnline,
            'walkInPercentage' => round($walkInPercentage, 2),
            'onlinePercentage' => round($onlinePercentage, 2),
            'trends' => $trends,
        ];
    }

    /**
     * Get reservation trends based on period
     * @param string $period 'daily', 'weekly', or 'monthly'
     */
    private function getReservationTrends($period = 'daily')
    {
        $trends = collect();

        switch ($period) {
            case 'daily':
                // Last 30 days - daily data
                $trends = Reservation::select(
                        DB::raw('DATE(date) as date'),
                        DB::raw('SUM(CASE WHEN is_walkin = 1 THEN 1 ELSE 0 END) as walkIn'),
                        DB::raw('SUM(CASE WHEN is_walkin = 0 OR is_walkin IS NULL THEN 1 ELSE 0 END) as online'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->where('date', '>=', Carbon::now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date', 'asc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'date' => Carbon::parse($item->date)->format('M d'),
                            'walkIn' => (int) $item->walkIn,
                            'online' => (int) $item->online,
                            'total' => (int) $item->total,
                        ];
                    });

                // Fill in missing days with zero data
                if ($trends->isEmpty()) {
                    for ($i = 29; $i >= 0; $i--) {
                        $date = Carbon::now()->subDays($i);
                        $trends->push([
                            'date' => $date->format('M d'),
                            'walkIn' => 0,
                            'online' => 0,
                            'total' => 0,
                        ]);
                    }
                }
                break;

            case 'weekly':
                // Last 12 weeks - weekly data
                $trends = Reservation::select(
                        DB::raw('YEARWEEK(date, 1) as week'),
                        DB::raw('MIN(DATE(date)) as week_start'),
                        DB::raw('SUM(CASE WHEN is_walkin = 1 THEN 1 ELSE 0 END) as walkIn'),
                        DB::raw('SUM(CASE WHEN is_walkin = 0 OR is_walkin IS NULL THEN 1 ELSE 0 END) as online'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->where('date', '>=', Carbon::now()->subWeeks(12))
                    ->groupBy('week')
                    ->orderBy('week', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $weekStart = Carbon::parse($item->week_start);
                        return [
                            'date' => 'Week of ' . $weekStart->format('M d'),
                            'walkIn' => (int) $item->walkIn,
                            'online' => (int) $item->online,
                            'total' => (int) $item->total,
                        ];
                    });

                // Fill in missing weeks with zero data
                if ($trends->isEmpty()) {
                    for ($i = 11; $i >= 0; $i--) {
                        $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                        $trends->push([
                            'date' => 'Week of ' . $weekStart->format('M d'),
                            'walkIn' => 0,
                            'online' => 0,
                            'total' => 0,
                        ]);
                    }
                }
                break;

            case 'monthly':
                // Last 12 months - monthly data
                $trends = Reservation::select(
                        DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                        DB::raw('SUM(CASE WHEN is_walkin = 1 THEN 1 ELSE 0 END) as walkIn'),
                        DB::raw('SUM(CASE WHEN is_walkin = 0 OR is_walkin IS NULL THEN 1 ELSE 0 END) as online'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->where('date', '>=', Carbon::now()->subMonths(12))
                    ->groupBy('month')
                    ->orderBy('month', 'asc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'date' => Carbon::parse($item->month . '-01')->format('M Y'),
                            'walkIn' => (int) $item->walkIn,
                            'online' => (int) $item->online,
                            'total' => (int) $item->total,
                        ];
                    });

                // Fill in missing months with zero data
                if ($trends->isEmpty()) {
                    for ($i = 11; $i >= 0; $i--) {
                        $date = Carbon::now()->subMonths($i);
                        $trends->push([
                            'date' => $date->format('M Y'),
                            'walkIn' => 0,
                            'online' => 0,
                            'total' => 0,
                        ]);
                    }
                }
                break;

            default:
                // Default to daily
                return $this->getReservationTrends('daily');
        }

        return $trends->values()->toArray();
    }
}