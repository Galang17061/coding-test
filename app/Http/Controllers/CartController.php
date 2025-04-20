<?php

namespace App\Http\Controllers;

use App\Helpers\Cart;
use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        [$products, $cartItems] = Cart::getProductsAndCartItems();
        $total = 0;
        foreach ($products as $product) {
            $total += $product->price * $cartItems[$product->id]['quantity'];
        }

        return view('cart.index', compact('cartItems', 'products', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $quantity = $request->post('quantity', 1);
        $user = $request->user();

        $totalQuantity = 0;

        if ($user) {
            $cartItem = CartItem::where(['user_id' => $user->id, 'product_id' => $product->id])->first();
            if ($cartItem) {
                $totalQuantity = $cartItem->quantity + $quantity;
            } else {
                $totalQuantity = $quantity;
            }
        } else {
            $cartItems = json_decode($request->cookie('cart_items', '[]'), true);
            $productFound = false;
            foreach ($cartItems as &$item) {
                if ($item['product_id'] === $product->id) {
                    $totalQuantity = $item['quantity'] + $quantity;
                    $productFound = true;
                    break;
                }
            }
            if (!$productFound) {
                $totalQuantity = $quantity;
            }
        }

        if ($product->quantity !== null && $product->quantity < $totalQuantity) {
            return response([
                'message' => match ( $product->quantity ) {
                    0 => 'The product is out of stock',
                    1 => 'There is only one item left',
                    default => 'There are only ' . $product->quantity . ' items left'
                }
            ], 422);
        }

        if ($user) {

            $cartItem = CartItem::where(['user_id' => $user->id, 'product_id' => $product->id])->first();

            if ($cartItem) {
                $oldValues = $cartItem->toArray();
                $cartItem->quantity += $quantity;
                $cartItem->update();

                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'cart_items',
                    'record_id' => $cartItem->id,
                    'action' => 'updated',
                    'old_values' => json_encode($oldValues),
                    'new_values' => json_encode($cartItem->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            } else {
                $data = [
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ];
                $cartItem = CartItem::create($data);

                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'cart_items',
                    'record_id' => $cartItem->id,
                    'action' => 'created',
                    'new_values' => json_encode($cartItem->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }

            return response([
                'count' => Cart::getCartItemsCount()
            ]);
        } else {
            $cartItems = json_decode($request->cookie('cart_items', '[]'), true);
            $productFound = false;
            foreach ($cartItems as &$item) {
                if ($item['product_id'] === $product->id) {
                    $item['quantity'] += $quantity;
                    $productFound = true;
                    break;
                }
            }
            if (!$productFound) {
                $cartItems[] = [
                    'user_id' => null,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price
                ];
            }
            Cookie::queue('cart_items', json_encode($cartItems), 60 * 24 * 30);

            return response(['count' => Cart::getCountFromItems($cartItems)]);
        }
    }

    public function remove(Request $request, Product $product)
    {
        $user = $request->user();
        if ($user) {
            $cartItem = CartItem::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->first();
            if ($cartItem) {
                $oldValues = $cartItem->toArray();
                $cartItem->delete();

                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'cart_items',
                    'record_id' => $cartItem->id,
                    'action' => 'deleted',
                    'old_values' => json_encode($oldValues),
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }

            return response([
                'count' => Cart::getCartItemsCount(),
            ]);
        }

        $cartItems = json_decode($request->cookie('cart_items', '[]'), true);
        foreach ($cartItems as $i => &$item) {
            if ($item['product_id'] === $product->id) {
                array_splice($cartItems, $i, 1);
                break;
            }
        }
        Cookie::queue('cart_items', json_encode($cartItems), 60 * 24 * 30);

        return response(['count' => Cart::getCountFromItems($cartItems)]);
    }

    public function updateQuantity(Request $request, Product $product)
    {
        $quantity = (int)$request->post('quantity');
        $user = $request->user();

        if ($product->quantity !== null && $product->quantity < $quantity) {
            return response([
                'message' => match ( $product->quantity ) {
                    0 => 'The product is out of stock',
                    1 => 'There is only one item left',
                    default => 'There are only ' . $product->quantity . ' items left'
                }
            ], 422);
        }

        if ($user) {
            $cartItem = CartItem::where(['user_id' => $user->id, 'product_id' => $product->id])->first();
            if ($cartItem) {
                $oldValues = $cartItem->toArray();
                $cartItem->quantity = $quantity;
                $cartItem->save();

                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'cart_items',
                    'record_id' => $cartItem->id,
                    'action' => 'updated',
                    'old_values' => json_encode($oldValues),
                    'new_values' => json_encode($cartItem->toArray()),
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }

            return response([
                'count' => Cart::getCartItemsCount(),
            ]);
        }

        $cartItems = json_decode($request->cookie('cart_items', '[]'), true);
        foreach ($cartItems as &$item) {
            if ($item['product_id'] === $product->id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        Cookie::queue('cart_items', json_encode($cartItems), 60 * 24 * 30);

        return response(['count' => Cart::getCountFromItems($cartItems)]);
    }
}
