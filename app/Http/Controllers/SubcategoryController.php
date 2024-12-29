<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $subcategories = Subcategory::paginate(20);
            return $this->jsonSuccess(200,'Request Successful', ['subcategories' => $subcategories], 'Subcategories');
        }catch (\Exception $exception){
            return $this->jsonError(400, $exception->getMessage());
        }
    }
}
