<?php

namespace App\Http\Controllers;

use App\Http\Resources\DiscountResource;
use App\Http\Resources\DiscountSelectResource;
use App\Http\Resources\DiscountProductsResource_1;
use Illuminate\Support\Facades\Validator;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class DiscountsController extends Controller
{
    //

    public function __construct(){

        $this->middleware('role:seller')->only([
            'Discount_and_categories', 
            'index', 
            'show', 
            'store', 
            'update',
            'destroy', 
            'search'
        ]);

    }

    // function for get discount poducts
    public function discount_products($id, $current_page){


        try {

            $id = Crypt::decrypt($id);


        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

        $validation = Validator::make([
            'id' => $id,
            'current_page' => $current_page
        ], [
            'id' => 'required|exists:discounts,id',
            'current_page' => 'required|integer'
        ]);

        if ($validation->fails()) {
            return response($validation->errors(), 422);
        }

        $itemsPerPage = 10;

        $discount = Discount::find($id);

        $products = $discount->products()->get();
        
        return response()->json([
            'products' => DiscountProductsResource_1::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage),
            'TotalProducts' => count($products)
        ]);

    }

    // function for get discounts and categories in selection discount and category for product
    public function Discount_and_categories($product_id){
        
            $discounts = Discount::where('active', 1)->orderBy('start_date', 'desc')
            ->get(['id', 'title', 'discount']);
            $categories = Category::get(['id', 'name']);

            if ($product_id != 'null') {

                $product = Product::find($product_id);
    
                $updatedDiscounts = $discounts->map(function($discount) use ($product){
    
                    $discount->selected = $product->discount_id == $discount->id;
    
                    return $discount;
    
                });

            }else{
                $updatedDiscounts = $discounts;
            }

            return response()->json([
                'discounts' => DiscountSelectResource::collection($updatedDiscounts),
                'categories' => $categories
            ]);

    }

    // function for get discounts in management discount
    public function index($current_page){

        $validation = Validator::make([
            'current_page' => $current_page
        ], [
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;
        $discounts = Discount::orderBy('start_date', 'desc')->get();

        return response()->json([
            'discounts' => DiscountResource::collection($discounts->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($discounts->count()/$itemsPerPage)
        ]);

    }

    public function show($id){
        
        try {

            $id = Crypt::decrypt($id);
            $validation = Validator::make([
                'id' => $id
            ], [
                'id' => 'required|exists:discounts,id'
            ]);
    
            if ($validation->fails()) {
                return response($validation->errors(), 422);
            }

            $discount = Discount::find($id);

            return response()->json([
                'discount' => [
                    "id" => Crypt::encrypt($discount->id),
                    "title" => $discount->title,
                    "desc" => $discount->desc,
                    "discount" => $discount->discount,
                    "color" => $discount->color,
                    "start_date" => $discount->start_date,
                    "end_date" => $discount->end_date,
                    "active" => $discount->active,
                    "products" => $discount->products()->get(['id', 'price', 'name'])
                ],
            ]);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

    public function store(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'title' => 'required|unique:discounts,title',
            'desc' => 'required',
            'discount' => 'required|between:0,100',
            'color' => 'required',
            'start_date' => 'required|after_or_equal:today',
            'end_date' => 'required|after_or_equal:start_date|after_or_equal:today'
        ]);

        if ($validation->fails()) {
            return response($validation->errors(), 422);
        }

        $today = Carbon::today();
        $active = $request->active ? $request->active : 0;

        if ($today->eq(Carbon::parse($request->start_date))) {
            $active = $request->active ? $request->active : 1;
        }
        
        $discount = Discount::create([
            'title' => $request->title,
            'desc' => $request->desc,
            'discount' => $request->discount,
            'color' => $request->color,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'active' => $active
        ]);

        return new DiscountResource($discount);

    }

    public function update(Request $request, $id)
    {

        try {

            $id = Crypt::decrypt($id);

            $validation = Validator::make($request->all(), [
                'title' => 'required|unique:discounts,title,' . $id,
                'desc' => 'required',
                'discount' => 'required|between:0,100',
                'color' => 'required',
                'start_date' => 'required', 
                'end_date' => 'required|after_or_equal:start_date',
                'active' => 'boolean'
            ]);
    
            if ($validation->fails()) {
                return response($validation->errors(), 422);
            }
    
            $today = Carbon::today();
            $active = isset($request->active) ? $request->active : 0;
    
            if ($today->eq(Carbon::parse($request->start_date))) {
                $active = isset($request->active) ? $request->active : 1;
            }

            $discount = Discount::find($id);

            $discount->update([
                'title' => $request->title,
                'desc' => $request->desc,
                'discount' => $request->discount,
                'color' => $request->color,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'active' => $active
            ]);

            return new DiscountResource($discount);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

    public function destroy($id){

        try {

            $id = Crypt::decrypt($id);

            $validation = Validator::make([
                'id' => $id
            ], [
                'id' => 'required|exists:discounts,id'
            ]);
    
            if ($validation->fails()) {
                return response($validation->errors(), 422);
            }

            $discount = Discount::find($id);

            $discount->products()->update([
                'discount_id' => 0
            ]);

            $discount->delete();

            return response()->json(200);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

    public function search(Request $request, $query, $date, $status, $current_page){

        $validation = Validator::make([
            'current_page' => $current_page,
            'query' => $query != 'null' ? $query : null,
            'date' => $date != 'null' ? $date : null,
            'status' => $status != 'null' ? $status : null,
        ], [
            'current_page' => 'integer',
            'query' => 'nullable|string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'date' => 'nullable|date_format:Y-m-d',
            'status' => 'numeric|nullable|between:0,1'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;

        $discountQuery = Discount::query();

        if ($query != 'null') {
            
            $discountQuery->where('title', 'LIKE', '%' . $query .'%');

        }

        if ($status != 'null') {
            
            $discountQuery->where('active', $status);

        }

        if ($date != 'null') {
            
            $discountQuery->whereDate('start_date', $date)
            ->orWhereDate('end_date', $date);
            
        }

        $discounts = $discountQuery->get(['id', 'title', 'start_date', 'end_date', 'active']);

        return response()->json([
            'discounts' => DiscountResource::collection($discounts->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($discounts->count()/$itemsPerPage)
        ]);

    }

}
