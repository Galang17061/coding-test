<?php

namespace App\Http\Controllers;

use App\Enums\AddressType;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileRequest;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function view(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Customer $customer */
        $customer = $user->customer;
        $shippingAddress = $customer->shippingAddress ?: new CustomerAddress(['type' => AddressType::Shipping]);
        $billingAddress = $customer->billingAddress ?: new CustomerAddress(['type' => AddressType::Billing]);
        $countries = Country::query()->orderBy('name')->get();

        return view('profile.view', compact('customer', 'user', 'shippingAddress', 'billingAddress', 'countries'));
    }

    public function store(ProfileRequest $request)
    {
        $customerData = $request->validated();
        $shippingData = $customerData['shipping'];
        $billingData = $customerData['billing'];

        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Customer $customer */
        $customer = $user->customer;

        DB::beginTransaction();
        try {
            $oldCustomer = $customer;
            $customer->update($customerData);
            AuditLog::create([
                'id' => Str::uuid(),
                'table_name' => 'customers',
                'record_id' => $customer->user_id,
                'action' => 'updated',
                'old_values' => $oldCustomer->toArray(),
                'new_values' => json_encode($customer->toArray()),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            if ($customer->shippingAddress) {
                $oldCustomer = $customer;
                $customer->shippingAddress->update($shippingData);
                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'customers',
                    'record_id' => $customer->user_id,
                    'action' => 'updated',
                    'old_values' => $oldCustomer->toArray(),
                    'new_values' => json_encode($customer->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            } else {
                $shippingData['customer_id'] = $customer->user_id;
                $shippingData['type'] = AddressType::Shipping->value;
                $customerAddress = CustomerAddress::create($shippingData);
                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'customer_addresses',
                    'record_id' => $customerAddress->id,
                    'action' => 'created',
                    'new_values' => json_encode($customerAddress->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }
            if ($customer->billingAddress) {
                $oldCustomer = $customer;
                $customer->billingAddress->update($billingData);
                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'customers',
                    'record_id' => $customer->user_id,
                    'action' => 'updated',
                    'old_values' => $oldCustomer->toArray(),
                    'new_values' => json_encode($customer->billingAddress->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            } else {
                $billingData['customer_id'] = $customer->user_id;
                $billingData['type'] = AddressType::Billing->value;
                $customerAddress = CustomerAddress::create($billingData);
                AuditLog::create([
                    'id' => Str::uuid(),
                    'table_name' => 'customer_addresses',
                    'record_id' => $customerAddress->id,
                    'action' => 'created',
                    'new_values' => json_encode($customerAddress->toArray()),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::critical(__METHOD__ . ' method does not work. '. $e->getMessage());
            throw $e;
        }

        DB::commit();

        $request->session()->flash('flash_message', 'Profile was successfully updated.');

        return redirect()->route('profile');

    }

    public function passwordUpdate(PasswordUpdateRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $passwordData = $request->validated();

        $user->password = Hash::make($passwordData['new_password']);
        $user->save();

        $request->session()->flash('flash_message', 'Your password was successfully updated.');

        return redirect()->route('profile');
    }
}
