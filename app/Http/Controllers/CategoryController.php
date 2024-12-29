<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        try {
            $categories = Category::paginate(20);
            return $this->jsonSuccess(200,'Request Successful', ['categories' => $categories], 'Categories');
        }catch (\Exception $exception){
            return $this->jsonError(400, $exception->getMessage());
        }
    }
}
