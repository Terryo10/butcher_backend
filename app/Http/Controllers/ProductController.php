<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::paginate(20);
            return $this->jsonSuccess(200, 'Request Successful', ['products' => $products], 'Products');
        } catch (\Exception $exception) {
            return $this->jsonError(400, $exception->getMessage());
        }
    }

    /**
     * Search products by name
     */
    public function search(Request $request)
    {
        try {
            // Get request parameters
            $name = $request->input('name');
            $order = $request->input('order');
            $category = $request->input('category');
            $minPrice = $request->input('min_price');
            $maxPrice = $request->input('max_price');

            // Start with a base query
            $query = Product::query();

            // Add name search if provided
            if ($name) {
                $query->where('name', 'LIKE', '%'.$name.'%');
            }

            // Add filters if provided
            if ($category) {
                $query->whereHas('subcategory', function($q) use ($category) {
                    $q->where('category_id', $category);
                });
            }

            if ($minPrice) {
                $query->where('price', '>=', $minPrice);
            }

            if ($maxPrice) {
                $query->where('price', '<=', $maxPrice);
            }

            // Add ordering if provided
            if ($order === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($order === 'price_desc') {
                $query->orderBy('price', 'desc');
            } elseif ($order === 'name_asc') {
                $query->orderBy('name', 'asc');
            } elseif ($order === 'name_desc') {
                $query->orderBy('name', 'desc');
            }

            // Paginate the results
            $products = $query->paginate(20);

            // Return in the same format as the index method
            return $this->jsonSuccess(200, 'Request Successful', ['products' => $products], 'Products');
        } catch (\Exception $exception) {
            return $this->jsonError(400, $exception->getMessage());
        }
    }
}
