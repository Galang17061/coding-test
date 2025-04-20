<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductListResource;
use App\Mail\OrderUpdateEmail;
use App\Models\Api\Product;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $perPage = request('per_page', 10);
        $search = request('search', '');
        $sortField = request('sort_field', 'updated_at');
        $sortDirection = request('sort_direction', 'desc');

        $query = Order::query()
            ->withCount('items')
            ->with('user.customer')
            ->where('id', 'like', "%{$search}%")
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);

        return OrderListResource::collection($query);
    }

    public function view(Order $order)
    {
        $order->load('items.product');

        return new OrderResource($order);
    }

    public function getStatuses()
    {
        return OrderStatus::getStatuses();
    }

    public function changeStatus(Order $order, $status)
    {
        DB::beginTransaction();
        try {
            $oldOrder = $order;
            $order->status = $status;
            $order->save();

            AuditLog::create([
                'id' => Str::uuid(),
                'table_name' => 'orders',
                'record_id' => $order->id,
                'action' => 'updated',
                'old_values' => $oldOrder->toArray(),
                'new_values' => json_encode($order->toArray()),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            if ($status === OrderStatus::Cancelled->value) {
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product && $product->quantity !== null) {
                        $oldProduct = $product;
                        $product->quantity += $item->quantity;
                        $product->save();

                        AuditLog::create([
                            'id' => Str::uuid(),
                            'table_name' => 'products',
                            'record_id' => $product->id,
                            'action' => 'updated',
                            'old_values' => $oldProduct->toArray(),
                            'new_values' => json_encode($product->toArray()),
                            'user_id' => auth()->id(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->header('User-Agent'),
                        ]);
                    }
                }
            }
            Mail::to($order->user)->send(new OrderUpdateEmail($order));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::commit();

        return response('', 200);
    }
}
