<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            // Check if user is admin
            $isAdmin = $user->role === 'admin' || $user->is_admin || $request->header('X-Admin-Request') === 'true';

            $query = Order::with(['orderItems']);

            // If not admin, filter by user_id
            if (! $isAdmin) {
                $query->where('user_id', $user->id);
            }

            // Apply filters for admin
            if ($isAdmin) {
                if ($request->has('order_status') && $request->order_status !== 'all') {
                    $query->where('order_status', $request->order_status);
                }

                if ($request->has('payment_method') && $request->payment_method !== 'all') {
                    $query->where('payment_method', $request->payment_method);
                }

                if ($request->has('search')) {
                    $query->where(function ($q) use ($request) {
                        $q->where('order_number', 'like', '%' . $request->search . '%')
                            ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                            ->orWhere('customer_email', 'like', '%' . $request->search . '%');
                    });
                }
            }

            $perPage = $request->get('per_page', 10);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transform the data to match frontend expectations
            $transformedOrders = $orders->getCollection()->map(function ($order) {
                $totalAmount = is_numeric($order->total_amount) ? (float) $order->total_amount : 0.00;

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?? 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'customer_name' => $order->customer_name ?? '',
                    'customer_email' => $order->customer_email ?? '',
                    'customer_phone' => $order->customer_phone ?? '',
                    'delivery_address' => $order->delivery_address ?? '',
                    'delivery_city' => $order->delivery_city ?? '',
                    'delivery_zip_code' => $order->delivery_zip_code ?? '',
                    'delivery_fee' => is_numeric($order->delivery_fee) ? (float) $order->delivery_fee : 0.00,
                    'payment_method' => $order->payment_method ?? 'cash',
                    'payment_status' => $order->payment_status ?? 'pending',
                    'order_status' => $order->order_status ?? 'pending',
                    'total_amount' => $totalAmount,
                    'notes' => $order->notes,
                    'receipt_file' => $order->receipt_file,
                    'order_items' => $order->orderItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name ?? '',
                            'description' => $item->description ?? '',
                            'ingredients' => $item->ingredients ?? '',
                            'price' => is_numeric($item->price) ? (float) $item->price : 0.00,
                            'quantity' => is_numeric($item->quantity) ? (int) $item->quantity : 1,
                            'category' => $item->category ?? '',
                            'best_seller' => (bool) ($item->best_seller ?? false),
                            'set' => (bool) ($item->set ?? false),
                            'image_url' => $item->image_url ?? 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=100&h=100&fit=crop',
                            'subtotal' => is_numeric($item->subtotal) ? (float) $item->subtotal : ((float) $item->price * (int) $item->quantity),
                        ];
                    })->toArray(),
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $transformedOrders->toArray(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve orders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            // Check if user is admin
            $isAdmin = $user->role === 'admin' || $user->is_admin || $request->header('X-Admin-Request') === 'true';

            $query = Order::with(['orderItems']);

            // If not admin, filter by user_id
            if (! $isAdmin) {
                $query->where('user_id', $user->id);
            }

            $order = $query->where('id', $id)->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $totalAmount = is_numeric($order->total_amount) ? (float) $order->total_amount : 0.00;

            // Transform the data
            $transformedOrder = [
                'id' => $order->id,
                'order_number' => $order->order_number ?? 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'customer_name' => $order->customer_name ?? '',
                'customer_email' => $order->customer_email ?? '',
                'customer_phone' => $order->customer_phone ?? '',
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_city' => $order->delivery_city ?? '',
                'delivery_zip_code' => $order->delivery_zip_code ?? '',
                'payment_method' => $order->payment_method ?? 'cash',
                'payment_status' => $order->payment_status ?? 'pending',
                'order_status' => $order->order_status ?? 'pending',
                'total_amount' => $totalAmount,
                'notes' => $order->notes,
                'receipt_file' => $order->receipt_file,
                'order_items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name ?? '',
                        'description' => $item->description ?? '',
                        'ingredients' => $item->ingredients ?? '',
                        'price' => is_numeric($item->price) ? (float) $item->price : 0.00,
                        'quantity' => is_numeric($item->quantity) ? (int) $item->quantity : 1,
                        'category' => $item->category ?? '',
                        'best_seller' => (bool) ($item->best_seller ?? false),
                        'set' => (bool) ($item->set ?? false),
                        'image_url' => $item->image_url ?? 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=100&h=100&fit=crop',
                        'subtotal' => is_numeric($item->subtotal) ? (float) $item->subtotal : ((float) $item->price * (int) $item->quantity),
                    ];
                })->toArray(),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Order retrieved successfully',
                'data' => $transformedOrder,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            // Check if user is admin
            $isAdmin = $user->role === 'admin' || $user->is_admin || $request->header('X-Admin-Request') === 'true';

            if (! $isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'order_status' => 'required|in:pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $order = Order::find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Update status
            $order->order_status = $request->order_status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_status' => $order->order_status,
                        'updated_at' => $order->updated_at,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.category' => 'nullable|string|max:100',
                'items.*.description' => 'nullable|string',
                'items.*.ingredients' => 'nullable|string',
                'items.*.best_seller' => 'boolean',
                'items.*.set' => 'boolean',
                'items.*.image_url' => 'nullable|string',
                'payment_method' => 'required|in:cash,gcash,security_bank,bpi,maya',
                'delivery_address' => 'required|string|max:500',
                'delivery_city' => 'required|string|max:100',
                'delivery_zip_code' => 'required|string|max:20',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:20',
                'receipt_file' => 'nullable|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed: ' . json_encode($validator->errors()));

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $receiptUrl = null;
            if ($request->receipt_file) {
                try {

                    // Check if it's a base64 image
                    if (preg_match('/^data:image\/(\w+);base64,/', $request->receipt_file, $matches)) {

                        // Extract and decode base64
                        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $request->receipt_file);
                        $imageData = base64_decode($imageData, true);

                        if ($imageData === false) {
                            Log::error('[v0] Failed to decode base64 image');
                            throw new \Exception('Invalid base64 image data');
                        }

                        $filename = 'receipt_' . time() . '_' . $user->id . '.png';
                        $publicDir = public_path('uploads/order_receipts');

                        // Create directory if it doesn't exist
                        if (! file_exists($publicDir)) {
                            mkdir($publicDir, 0755, true);
                        }

                        // Save file directly to public folder
                        $filePath = $publicDir . '/' . $filename;
                        if (file_put_contents($filePath, $imageData)) {
                            $receiptUrl = '/uploads/order_receipts/' . $filename;
                        } else {
                            Log::error('[v0] Failed to write receipt file to public directory');
                            throw new \Exception('Failed to save receipt file');
                        }
                    } elseif (filter_var($request->receipt_file, FILTER_VALIDATE_URL)) {
                        // If it's already a URL, use it as-is
                        $receiptUrl = $request->receipt_file;
                    } else {
                        Log::warning('[v0] Receipt data is neither valid base64 nor URL');
                    }
                } catch (\Exception $e) {
                    Log::error('[v0] Receipt processing error: ' . $e->getMessage());
                    // Continue without receipt - don't fail the entire order
                    $receiptUrl = null;
                }
            }

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += (float) $item['price'] * (int) $item['quantity'];
            }

            $settings = Setting::first();

            // Auto-create settings if missing (optional)
            if (! $settings) {
                $settings = Setting::create([
                    'restaurant_name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                    'delivery_fee' => 0,
                    'maintenance_mode' => false,
                ]);
            }

            $deliveryFee = (float) ($settings->delivery_fee ?? 0);
            $totalAmount += $deliveryFee;

            $orderNumber = 'ORD-' . time() . rand(100, 999);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'delivery_fee' => $deliveryFee,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'delivery_address' => $request->delivery_address,
                'delivery_city' => $request->delivery_city,
                'delivery_zip_code' => $request->delivery_zip_code,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'receipt_file' => $receiptUrl,
                'notes' => $request->notes,
                'order_status' => 'pending',
            ]);

            // Handle items
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'price' => (float) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'category' => $item['category'] ?? '',
                    'ingredients' => $item['ingredients'] ?? null,
                    'best_seller' => (bool) ($item['best_seller'] ?? false),
                    'set' => (bool) ($item['set'] ?? false),
                    'image_url' => $item['image_url'] ?? null,
                    'subtotal' => (float) $item['price'] * (int) $item['quantity'],
                ]);
            }

            DB::commit();

            $order->load('orderItems');

            $transformedOrder = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'delivery_address' => $order->delivery_address,
                'delivery_city' => $order->delivery_city,
                'delivery_zip_code' => $order->delivery_zip_code,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'total_amount' => (float) $order->total_amount,
                'delivery_fee' => $order->delivery_fee,
                'notes' => $order->notes,
                'receipt_file' => $order->receipt_file,
                'order_items' => $order->orderItems->toArray(),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $transformedOrder,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Upload receipt file separately
     */
    public function uploadReceipt(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'receipt_file' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $base64Image = $request->receipt_file;

            // Validate base64 format
            if (! preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64Image)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image format',
                ], 422);
            }

            // Extract base64 data
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode image',
                ], 422);
            }

            // Generate unique filename
            $filename = 'receipt_' . time() . '_' . $user->id . '.png';

            // Create receipts directory if it doesn't exist
            $uploadsPath = public_path('uploads/receipts');
            if (! file_exists($uploadsPath)) {
                mkdir($uploadsPath, 0755, true);
            }

            // Full file path
            $filePath = $uploadsPath . '/' . $filename;

            // Save the file to public directory
            file_put_contents($filePath, $imageData);

            // Generate public URL
            $fileUrl = url('uploads/receipts/' . $filename);

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'data' => [
                    'file_path' => $fileUrl,
                    'filename' => $filename,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to upload receipt: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrderItems(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $orderItems = OrderItem::whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['order:id,order_number,created_at,order_status'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Order items retrieved successfully',
                'data' => [
                    'items' => $orderItems->items(),
                    'pagination' => [
                        'current_page' => $orderItems->currentPage(),
                        'last_page' => $orderItems->lastPage(),
                        'per_page' => $orderItems->perPage(),
                        'total' => $orderItems->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            // Check if user is admin (same logic as update method)
            $isAdmin = $user->role === 'admin' || $user->is_admin || $request->header('X-Admin-Request') === 'true';

            $order = Order::findOrFail($id);

            // Only admins or order owners can delete
            if (! $isAdmin && $order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this order',
                ], 403);
            }

            // Delete receipt file if exists
            if ($order->receipt_file && str_contains($order->receipt_file, 'uploads/receipts/')) {
                $filename = basename($order->receipt_file);
                $filePath = public_path('uploads/receipts/' . $filename);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Delete related order items first (if cascade delete is not set up in database)
            $order->orderItems()->delete();

            // Delete the order
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Order not found for deletion: ID {$id}");

            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting order ID {$id}: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelOrder(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $order = Order::find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if order belongs to the authenticated user
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only cancel your own orders',
                ], 403);
            }

            // Check if order can be cancelled
            $cancellableStatuses = ['pending', 'confirmed'];
            if (! in_array($order->order_status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage. Only pending or confirmed orders can be cancelled.',
                    'current_status' => $order->order_status,
                ], 400);
            }

            // Cancel the order
            $previousStatus = $order->order_status;
            $order->order_status = 'cancelled';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_status' => $order->order_status,
                        'previous_status' => $previousStatus,
                        'updated_at' => $order->updated_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to cancel order: ' . $e->getMessage(), [
                'order_id' => $id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
