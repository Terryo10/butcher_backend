<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{
    protected $taxService;

    /**
     * Create a new controller instance.
     *
     * @param TaxService $taxService
     * @return void
     */
    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Get all active tax rates
     */
    public function index()
    {
        $taxes = Tax::where('is_active', true)->get();

        return response()->json([
            'taxes' => $taxes
        ]);
    }

    /**
     * Get tax rates for a specific country/state
     */
    public function getTaxForRegion(Request $request)
    {
        $request->validate([
            'country' => 'required|string',
            'state' => 'nullable|string',
        ]);

        // Find tax by country and state
        $tax = Tax::where('country', $request->country)
            ->where(function ($query) use ($request) {
                if ($request->state) {
                    $query->where('state', $request->state)
                        ->orWhereNull('state');
                } else {
                    $query->whereNull('state');
                }
            })
            ->where('is_active', true)
            ->first();

        if (!$tax) {
            // If no specific tax found, return default tax
            $tax = Tax::where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        return response()->json([
            'tax' => $tax
        ]);
    }

    /**
     * Get default tax rate
     */
    public function getDefaultTax()
    {
        $tax = Tax::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$tax) {
            return response()->json([
                'message' => 'No default tax rate found'
            ], 404);
        }

        return response()->json([
            'tax' => $tax
        ]);
    }

    /**
     * Calculate tax for a cart (for preview purposes)
     */

    public function calculateTax(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'subtotal' => 'nullable|numeric',
        ]);

        // Get the current user's cart using the same method from CartController
        $cart = $this->getOrCreateCart();

        // Get the address
        $address = \App\Models\Address::findOrFail($request->address_id);

        // If subtotal is provided, use it instead of cart total
        $subtotal = $request->subtotal ?? $cart->items->sum('total_price');

        // Calculate tax
        $taxAmount = $this->taxService->calculateTax($cart, $address, $subtotal);
        $tax = $this->taxService->getTaxRate($address);

        return response()->json([
            'tax_rate' => $tax->rate,
            'tax_name' => $tax->name,
            'tax_amount' => $taxAmount,
        ]);
    }

// Copy the getOrCreateCart method from CartController
    private function getOrCreateCart()
    {
        if (Auth::check()) {
            // User is logged in, get or create cart by user_id
            $cart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['session_id' => session()->getId()]
            );
        } else {
            // User is not logged in, get or create cart by session_id
            $sessionId = session()->getId();
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }

        return $cart;
    }
}
