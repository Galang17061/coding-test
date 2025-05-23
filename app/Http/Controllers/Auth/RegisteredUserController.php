<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Cart;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'uuid' => Str::uuid(),
            ]);

            event(new Registered($user));

            $customer = new Customer();
            $names = explode(" ", $user->name);
            $customer->user_id = $user->id;
            $customer->first_name = $names[0];
            $customer->last_name = $names[1] ?? '';
            $customer->uuid = Str::uuid();
            $customer->save();

            AuditLog::create([
                'id' => Str::uuid(),
                'table_name' => 'customer',
                'record_id' => $customer->user_id,
                'action' => 'created',
                'new_values' => json_encode($customer->toArray()),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            Auth::login($user);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Unable to register right now.');
        }

        DB::commit();

        Cart::moveCartItemsIntoDb();

        return redirect(RouteServiceProvider::HOME);
    }
}
