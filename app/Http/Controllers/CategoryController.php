<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoriesManagementResource;
use App\Http\Resources\CategoriesResource;

class CategoryController extends Controller
{

    public function __construct(){

        $this->middleware('role:seller')->only([
            'store', 
            'updateCategory', 
            'destroy', 
            'get_categories', 
            'search',
            'show'
        ]);

        $this->middleware('auth:api')->except([
            'index', 
            'ProductsCategory'
        ]);

    }

    // display data in market
    public function index()
    {
        //
        $data = Category::orderBy('created_at', 'desc')->get(['id', 'name', 'image']);

        return CategoriesManagementResource::collection($data);

    }

    /**
     * display data in dashboard.
     */
    public function get_categories($current_page)
    {
        //
        $validation = Validator::make([
            'current_page' => $current_page
        ], [
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $data = Category::orderBy('created_at', 'desc')->get(['id', 'name', 'image']);
        $itemsPerPage = 10;
        $categories = [];

        return response()->json([
            'categories' => CategoriesManagementResource::collection($data->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($data->count()/$itemsPerPage)
        ]);

    }

    
    public function ProductsCategory($id, $current_page)
    {
        //
        $validation = Validator::make([
            'current_page' => $current_page,
            'id' => $id
        ], [
            'current_page' => 'integer',
            'id' => 'required|exists:categories,id'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemPerPage = 2;
        $category = Category::find($id);
        $products = $category->products;

        return response()->json([
            'products' => ProductResource::collection($products->forPage($current_page, $itemPerPage)),
            'total_pages' => ceil($products->count()/$itemPerPage),
        ]);

    }
    
    public function Search($query, $current_page){
        
        $itemsPerPage = 10;
        $products = [];

        $validation = Validator::make([
            'query' => $query,
            'current_page' => $current_page
        ], [
            'query' => 'string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $categories = Category::orderBy('created_at', 'desc')
        ->where('name', 'like', '%' . $query . '%')
        ->get(['id', 'name']);

        return response()->json([
            'categories' => CategoriesManagementResource::collection($categories->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($categories->count()/$itemsPerPage)
        ]);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validation = Validator::make($request->all(), [
            'name' => 'required|max:200|unique:categories,name',
            'image' => 'required|mimes:png,jpg,webp,jpeg|max:1024'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        $category = Category::create([
            'name' => $request->name
        ]);

        $imageName = '';

        if ($request->file('image') !== null) {

            $file = $request->file('image');
            $defaultBucket = app('firebase.storage')->getBucket();
            if ($defaultBucket->object("categories/" . $file->getClientOriginalName())->exists()) {
                $imageName = uniqid() . '_' . $file->getClientOriginalName();
            }else{
                $imageName = $file->getClientOriginalName();
            }

            // Open the uploaded image
            $image = Image::make($file);

            // Compress the image with a desired quality (e.g., 60)
            $image->resize(180, 100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $image->encode('webp', 80);

            $defaultBucket->upload($image->stream()->detach(), [
                'name' => 'categories/' . $imageName,
            ]);

            $category->update([
                'image' => 'categories/' . $imageName
            ]);

        }

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'numbre_products' => $category->products->count()
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        $category = Category::find($id);

        return new CategoriesManagementResource($category);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $category = Category::find($id);

        if ($category->products()->count() > 0) {
            
            return response()->json([
                'message' => 'You can\'t delete this category because it has products'
            ], 422);

        }else{

            $defaultBucket = app('firebase.storage')->getBucket();

            if ($defaultBucket->object($category->image)->exists()) {
                $defaultBucket->object($category->image)->delete();
            }
    
            $category->delete();
            return response()->json([
                'message' => 'Category Deleted Succesfully'
            ]);

        }

    }

    // update category 
    /**
     * Update the specified resource in storage.
     */
    public function updateCategory(Request $request, $id)
    {
        //
        $validation = Validator::make($request->all(), [
            'name' => 'required|max:200|unique:categories,name,' . $id,
            'image' => 'mimes:png,jpg,webp,jpeg|max:1024'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        $category = Category::find($id);
        $imageName = $category->image;

        if ($request->file('image') !== null) {

            $defaultBucket = app('firebase.storage')->getBucket();
            $file = $request->file('image');

            if ($defaultBucket->object('categories/' . $file->getClientOriginalName())->exists()) {
                $imageName = 'categories/' . uniqid() . '_' . $file->getClientOriginalName();
            }else{
                $imageName = 'categories/' . $file->getClientOriginalName();
            }

            if (isset($category->image)) {
                
                if ($defaultBucket->object($category->image)->exists()) {
                    $defaultBucket->object($category->image)->delete();
                }

            }

            // Open the uploaded image
            $image = Image::make($file);

            $image->resize(250, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $image->encode('webp', 80);

            $defaultBucket->upload($image->stream()->detach(), [
                'name' => $imageName,
            ]);

        }

        $category->update([
            'name' => $request->name,
            'image' => $imageName
        ]);

        return response()->json([
            'message' => 'Category Updated Succesfully'
        ]);

    }
}
