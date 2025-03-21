<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        $addresses = $user->addresses;

        return response()->json([
            'addresses' => $addresses
        ]);
    }

    /**
     * Store a new address
     */
    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:50',
            'full_name' => 'required|string|max:100',
            'phone_number' => 'required|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // If this is the first address or is_default is true, update all other addresses
        $isDefault = $request->input('is_default', false);
        if ($isDefault || $user->addresses->count() === 0) {
            // Set all existing addresses to non-default
            Address::where('user_id', $user->id)
                ->update(['is_default' => false]);
            $isDefault = true;
        }

        $address = Address::create([
            'user_id' => $user->id,
            'label' => $request->label,
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Address created successfully',
            'address' => $address
        ], 201);
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'label' => 'sometimes|required|string|max:50',
            'full_name' => 'sometimes|required|string|max:100',
            'phone_number' => 'sometimes|required|string|max:20',
            'address_line1' => 'sometimes|required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If setting as default, update all other addresses
        if ($request->has('is_default') && $request->is_default) {
            Address::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check if this is the only address
        if ($user->addresses->count() <= 1) {
            throw ValidationException::withMessages([
                'address' => ['Cannot delete the only address']
            ]);
        }

        // If this was the default address, set another one as default
        if ($address->is_default) {
            $newDefault = Address::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }

    /**
     * Set an address as default
     */
    public function setDefault($id)
    {
        $user = Auth::user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Set all addresses to non-default
        Address::where('user_id', $user->id)
            ->update(['is_default' => false]);

        // Set selected address as default
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default address updated successfully',
            'address' => $address
        ]);
    }
}
