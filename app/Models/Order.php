<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'total_price', 'created_by', 'updated_by'];

    public function isPaid()
    {
        return $this->status === OrderStatus::Paid->value;
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function deleteUnpaidOrders($hours)
    {
        $order = Order::query()->where('status', OrderStatus::Unpaid->value)
            ->where('created_at', '<', Carbon::now()->subHours($hours))
            ->first();

        AuditLog::create([
            'id' => Str::uuid(),
            'table_name' => 'orders',
            'record_id' => $order->id,
            'action' => 'deleted',
            'new_values' => json_encode($order->toArray()),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);

        return $order->delete();
    }
}
