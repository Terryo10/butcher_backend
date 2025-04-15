<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Tax;

class TaxService
{
    /**
     * Calculate tax for a cart
     *
     * @param Cart $cart
     * @param Address|null $address
     * @return float
     */
    public function calculateTax(Cart $cart, ?Address $address = null)
    {
        // Get applicable tax rate based on address if available
        $tax = $this->getTaxRate($address);

        // Calculate taxable amount (subtotal minus discounts)
        $taxableAmount = $cart->items->sum('total_price') - ($cart->discount_amount ?? 0);

        // Calculate tax amount
        return round($taxableAmount * $tax->rate, 2);
    }

    /**
     * Get the appropriate tax rate based on address
     *
     * @param Address|null $address
     * @return Tax
     */
    public function getTaxRate(?Address $address = null)
    {
        // If address is provided, try to find a tax rate specific to that region
        if ($address) {
            // First try to find a tax for the exact state and country
            $tax = Tax::where('country', $address->country)
                ->where('state', $address->state)
                ->where('is_active', true)
                ->first();

            if ($tax) {
                return $tax;
            }

            // If no state-specific tax is found, try country-wide tax
            $tax = Tax::where('country', $address->country)
                ->whereNull('state')
                ->where('is_active', true)
                ->first();

            if ($tax) {
                return $tax;
            }
        }

        // If no specific tax is found, use the default tax rate
        $defaultTax = Tax::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultTax) {
            return $defaultTax;
        }

        // As a fallback, create a default tax object (this should not happen in production)
        return new Tax([
            'name' => 'Default Tax',
            'rate' => 0.1, // 10%
            'is_active' => true,
            'is_default' => true
        ]);
    }
}
