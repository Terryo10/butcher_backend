<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\DeliveryLocation;
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
        $addresses = Address::with('deliveryLocation')
            ->where('user_id', Auth::id())
            ->get();

        return response()->json([
            'addresses' => $addresses
        ]);
    }

    /**
     * Get all delivery locations
     */
    public function getDeliveryLocations()
    {
        $deliveryLocations = DeliveryLocation::all();

        return response()->json([
            'delivery_locations' => $deliveryLocations
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
            'delivery_location_id' => 'required|exists:delivery_locations,id',
            'is_default' => 'nullable|boolean',
        ]);

        // Check if delivery location exists
        $deliveryLocation = DeliveryLocation::find($request->delivery_location_id);
        if (!$deliveryLocation) {
            throw ValidationException::withMessages([
                'delivery_location_id' => ['Invalid delivery location']
            ]);
        }

        $user = Auth::user();

        // Create new address
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
            'delivery_location_id' => $request->delivery_location_id,
            'is_default' => $request->is_default ?? false,
        ]);

        // If this is set as default, update all other addresses
        if ($request->is_default) {
            Address::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        // Load the delivery location relationship
        $address->load('deliveryLocation');

        return response()->json([
            'message' => 'Address created successfully',
            'address' => $address,
        ], 201);
    }

    /**
     * Get a specific address
     */
    public function show($id)
    {
        $address = Address::with('deliveryLocation')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->json([
            'address' => $address
        ]);
    }

    /**
     * Update an address
     */
    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

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
            'delivery_location_id' => 'sometimes|required|exists:delivery_locations,id',
            'is_default' => 'nullable|boolean',
        ]);

        // Check if delivery location exists if provided
        if ($request->has('delivery_location_id')) {
            $deliveryLocation = DeliveryLocation::find($request->delivery_location_id);
            if (!$deliveryLocation) {
                throw ValidationException::withMessages([
                    'delivery_location_id' => ['Invalid delivery location']
                ]);
            }
        }

        // Update address details
        $address->update($request->only([
            'label',
            'full_name',
            'phone_number',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'country',
            'delivery_location_id',
            'is_default',
        ]));

        // If this is set as default, update all other addresses
        if ($request->is_default) {
            Address::where('user_id', Auth::id())
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        // Load the delivery location relationship
        $address->load('deliveryLocation');

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address,
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy($id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $wasDefault = $address->is_default;

        $address->delete();

        // If the deleted address was the default, set another address as default
        if ($wasDefault) {
            $newDefaultAddress = Address::where('user_id', Auth::id())->first();
            if ($newDefaultAddress) {
                $newDefaultAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }

    /**
     * Set an address as default
     */
    public function setDefault($id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Update all addresses to non-default
        Address::where('user_id', Auth::id())
            ->update(['is_default' => false]);

        // Set this address as default
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Address set as default successfully',
            'address' => $address
        ]);
    }
}
