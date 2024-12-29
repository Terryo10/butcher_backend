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
            return $this->jsonSuccess(200,'Request Successful', ['products' => $products], 'Products');
        }catch (\Exception $exception){
            return $this->jsonError(400, $exception->getMessage());
        }
    }
}
