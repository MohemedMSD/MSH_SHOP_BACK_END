<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Visite;
use App\Models\Product_view;
use App\Models\CheckProductViews;
use App\Models\Discount;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Http\Resources\TrashedProductResource;
use App\Http\Resources\DiscountProductsResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductManagementResource;
use App\Models\OrderDetails;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Archive_order;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

// use Intervention\Image\src\ImageManager as Image;
class ProductController extends Controller
{  
    
    public function __construct(){
        $this->middleware('role:seller')->only([
            'store', 
            'updateProducts', 
            'softDelete',
            'restore', 
            'trashedProducts', 
            'destroy',
            'ProductsManagement',
            'DashboardSearch',
            'showProduct'
        ]);
        $this->middleware('auth:api')->except(['index', 'show', 'Search']);
        $this->middleware('visites')->only(['index', 'show', 'Search']);
    }

    /**
     * display product in landing page
     */
    public function index(Request $request, $current_page)
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


        $itemPerPage = 20;
        $limit = 40;
        $productsHasDiscount = [];

        // $productsWithSales = Product::has('orders')
        // ->withCount('orders')->orderByDesc('orders_count')
        // ->take($limit)
        // ->get();
        
        // if ($productsWithSales->count() < $limit) {
            
        //     $productWithoutSales = Product::doesntHave('orders')
        //     ->orderBy('created_at', 'asc')
        //     ->take($limit - $productsWithSales->count())
        //     ->get();

        //     $bestSellingProducts = $productsWithSales->merge($productWithoutSales);

        // }else{

        //     $bestSellingProducts = $productsWithSales;

        // }

        $bestSellingProducts = Product::withCount(['orders', 'reviews'])
        ->orderByDesc('orders_count')
        ->orderByDesc('reviews_count')
        ->take($limit)
        ->get(['orders_count', 'reviews_count']);

        $productsHasDiscount = Product::whereHas('Discount', function($query){
            $query->where('active', 1);
        })->get();
        
        if(count($productsHasDiscount) == 0){
            
            $productsHasDiscount = Category::whereHas('products')
            ->with('products')
            ->get()
            ->map(function ($category){
                return $category->products->random();
            });

        }

        return response()->json([
            'products' => ProductResource::collection($bestSellingProducts->forPage($current_page, $itemPerPage)),
            'total_pages' => ceil($bestSellingProducts->count()/$itemPerPage),
            'Discounts' => DiscountProductsResource::collection($productsHasDiscount)
        ]);

    }

    public function Search($query, $current_page){
        
        $itemsPerPage = 20;
        $products = [];

        $validation = Validator::make([
            'query' => $query,
            'current_page' => $current_page
        ], [
            'query' => 'required|string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $ProductQuery = Product::withCount(['orders', 'reviews'])
        ->join('categories', 'categories.id', 'products.category_id')
        ->where('products.deleted_at', null)
        ->orderBy('orders_count', 'desc')
        ->orderBy('reviews_count', 'desc')
        ->whereAny(
            ['products.name', 'products.description', 'categories.name'],
            'REGEXP',
            '.*' . $query . '.*'
        );

        $products = $ProductQuery->get(['products.id', 'products.name', 'products.price', 'products.quantity', 'products.images', 'products.discount_id', 'products.category_id']);

        return response()->json([
            'products' => ProductResource::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage),
            'ProductFounded' => $products->count()
        ]);    

    }

    public function DashboardSearch($query, $current_page){
        
        $itemsPerPage = 10;
        $products = [];

        $validation = Validator::make([
            'query' => $query,
            'current_page' => $current_page
        ], [
            'query' => 'required|string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $ProductQuery = Product::join('categories', 'categories.id', 'products.category_id')
        ->where('products.deleted_at', null)
        ->orderBy('products.created_at', 'desc')
        ->whereAny(
            ['products.name','categories.name'],
            'LIKE',
            '%' . $query .'%'
        );


        $products = $ProductQuery->get(['products.*']);
            
        return response()->json([
            'products' => ProductManagementResource::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage)
        ]);

    }

    // show product in dashboard
    public function ProductsManagement($current_page){

        $validation = Validator::make([
            'current_page' => $current_page
        ], [
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;

        $products = Product::where('products.deleted_at', null)
        ->orderBy('created_at', 'desc')
        ->get();
        
        return response()->json([
            'products' => ProductManagementResource::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage)
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validation = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => 'required|min:10',
            'price' => 'required|gte:price',
            'purchase_price' => 'required|gt:0',
            'quantity' => 'required|integer|gt:0',
            'category' => 'required|exists:categories,id',
            'images' => 'required|between:1,4',
            'images.*' => ['mimes:jpg,jpeg,png,webp', 'max:1024']
        ], [
            'price.gte' => 'You will lose if you sell this product at this price'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $uploadedImages = [];
        $discount_id = null;

        if ($request->has('images')) {

            $defaultBucket = app('firebase.storage')->getBucket();
            
            foreach ($request->file('images') as $key => $file) {

                if ($defaultBucket->object("products/" . $file->getClientOriginalName())->exists()) {
                    $imageName = uniqid() . '_' . $file->getClientOriginalName();
                }else{
                    $imageName = $file->getClientOriginalName();
                }

                // Open the uploaded image
                $image = Image::make($file);

                // Compress the image with a desired quality (e.g., 60)
                $image->resize(450, 450, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $image->encode('webp', 80);

                $defaultBucket->upload($image->stream()->detach(), [
                    'name' => 'products/' . $imageName,
                ]);
                
                // Add the compressed image name to the array
                $uploadedImages[] = 'products/' . $imageName;

            }

    
        }

        if($request->discount_id != 'null'){

            try {
                $discount_id = Crypt::decrypt($request->discount_id);
            } catch (\Throwable $e) {
            
                return response()->json('Something not correct', 422);
    
            }

        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'purchase_price' => $request->purchase_price,
            'quantity' => $request->quantity,
            'category_id' => $request->category,
            'images' => $uploadedImages,
            'discount_id' => $discount_id
        ]);

        if($request->hero || $request->footer){

            $product->banner()->create([
                'color' => $request->color,
                'hero' => $request->hero,
                'footer' => $request->footer,
            ]);

        }


        return response()->json([
            'message' => 'Product created succesfully',
        ]);

    }

    /**
     * Display the specified resource to costumer.
     */
    public function show(Request $request, $id)
    {
        //

            $product = Product::find($id);

            if(isset($product)){

                $canUserMakeReview = false;
    
                $semalaireProducts = $product->category->products;
                $defaultBucket = app('firebase.storage')->getBucket();
                $imagesUrl = [];
    
    
                foreach($product->images as $image){
                    $imagesUrl[] = $defaultBucket->object($image)
                    ->signedUrl(new \DateTime('1 hour'));
                }
    
                // start code for check if can user make review
                if( Auth('api')->check() ){
                    
                    $user_id = Auth('api')->user()->id;

                    $currentMonth = Carbon::today();
                    $MonthViews = Product_view::whereMonth('duration', $currentMonth->month)
                    ->where('product_id', $id)
                    ->first();
        
                    // start code for make views
        
                    $checkUserViews = CheckProductViews::where('user_agent', $request->header('User-Agent'))
                    ->where('ip_adress', $request->ip())
                    ->where('product_id', $id)
                    ->orderBy('visited_at', 'desc')
                    ->first();
        
                    if(isset($checkUserViews)){
                        
                        $now = Carbon::now();
                        
                        if($now->diffinMinutes($checkUserViews->visited_at) >= 20){
        
                            if (isset($MonthViews)) {
                            
                                $MonthViews->increment('count');
                                
                            }else{
            
                                Product_view::create([
                                    'duration' => $currentMonth,
                                    'product_id' => $id,
                                    'count' => 1
                                ]);
            
                            }
        
                        }
                        
                        $checkUserViews->update([
                            'visited_at' => now()
                        ]);
        
                    }else{
                        
                        CheckProductViews::create([
                            'ip_adress' => $request->ip(),
                            'user_agent' => $request->header('User-Agent'),
                            'product_id' => $id,
                            'visited_at' => now()
                        ]);
        
                        if (isset($MonthViews)) {
                            
                            $MonthViews->increment('count');
                            
                        }else{
        
                            Product_view::create([
                                'duration' => $currentMonth,
                                'product_id' => $id,
                                'count' => 1
                            ]);
        
                        }
        
        
                    }
        
                    // end code for make views

                    $canUserMakeReview = User::find($user_id)->orders()->where('received', 1)->whereHas('products', function ($query) use ($id) {
                        $query->where('product_id', $id);
                    })->exists();
        
                }
                // end code for check if can user make review
    
                return response()->json([
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'quantity' => $product->quantity,
                        'images' => $imagesUrl,
                        'discount' => isset($product->Discount) && $product->Discount->active ? $product->Discount->discount : null,
                        'banner' => $product->banner,
                        'category_id' => $product->category_id,
                        'products' => ProductResource::collection($product->category->products()->get(['id', 'name', 'price', 'quantity', 'category_id', 'images'])->take(10))
                    ],
                    'reviews' => [
                        'reviews' => ReviewResource::collection($product->reviews()->orderBy('updated_at', 'desc')->limit(3)->get()),
                        'details' => Review::select(
                            DB::raw('sum(review_star) / count(*) as moyen_reviews'),
                            DB::raw('count(*) as reviews_count'),
                        )
                        ->where('product_id', $product->id)
                        ->first()
                    ],
                    'canUserMakeReview' => $canUserMakeReview
                ]);    

            }else {
                return response()->json('Product not found', 404);
            }

    }

    /**
     * Show the specified resource to admin in dashboard.
     */
    public function showProduct($id)
    {
        //
            $product = Product::find($id);
            $defaultBucket = app('firebase.storage')->getBucket();
            $imagesUrl = [];

            foreach($product->images as $image){
                $imagesUrl[] = $defaultBucket->object($image)
                ->signedUrl(new \DateTime('1 hour'));
            }
            
            return response()->json([
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'purchase_price' => $product->purchase_price,
                    'quantity' => $product->quantity,
                    'images' => $imagesUrl,
                    'images_path' => $product->images,
                    'category_id' => $product->category_id,
                    'discount' => isset($product->Discount) ? $product->Discount->discount : null
                ]
            ]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateProducts(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => 'required|min:10',
            'price' => 'required|gte:purchase_price',
            'purchase_price' => 'required',
            'quantity' => 'required|integer|gt:0',
            'category' => 'required|exists:categories,id',
            'images' => 'required|between:1,4',
            'images.*' => ['max:1024']
        ], [
            'price.gte' => 'You will lose if you sell this product at this price',
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        } 

            $product = Product::find($id);
            $defaultBucket = app('firebase.storage')->getBucket();
            $uploadedImages = [];
            $discount_id = null;

            if($request->discount_id != 'null'){
                
                try {
                    $discount_id = Crypt::decrypt($request->discount_id);
                } catch (\Throwable $e) {
                
                    return response()->json('Something not correct', 422);
        
                }

            }
    
            if ($request->file('images') != null) {
    
                $old_images = array_diff($request->old_images, $request->images);
                $new_images = array_diff($request->file('images'), $request->old_images);
    
            }else{
    
                $old_images = array_diff($request->old_images, $request->images);
                $new_images = [];
    
            }
            
            if (count($old_images) > 0) {
    
                foreach ($old_images as $key => $file) {
    
                    if ($defaultBucket->object($file)->exists()) {
                        $defaultBucket->object($file)->delete();
                    }
        
                }
    
                $old_image_not_delete = array_diff($product->images, $old_images);
                
                foreach($old_image_not_delete as $image){
                    $uploadedImages[] = $image;
                }
    
            }else if (count($old_images) == 0) {
    
                foreach($product->images as $image){
                    $uploadedImages[] = $image;
                }
    
            }
    
            if (count($new_images) > 0) {
                
                foreach ($request->file('images') as $key => $file) {
    
                    if ($defaultBucket->object("products/" . $file->getClientOriginalName())->exists()) {
                        $imageName = uniqid() . '_' . $file->getClientOriginalName();
                    }else{
                        $imageName = $file->getClientOriginalName();
                    }
    
                    // Open the uploaded image
                    $image =  Image::make($file);
    
                    // Compress the image with a desired quality (e.g., 60)
                    $image->resize(450, 450, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
    
                    $image->encode('webp', 80);
    
                    $defaultBucket->upload($image->stream()->detach(), [
                        'name' => 'products/' . $imageName,
                    ]);
                    
                    // Add the compressed image name to the array
                    $uploadedImages[] = 'products/' . $imageName;
    
                }
                
            }
    
            // get orders have this product 
            $check = Order::whereHas('products', function ($query) use ($product){
                $query->where('products.id', $product->id);
            });
    
            $Orders = $check->get();
            
            foreach($Orders as $order){
    
                $Archive_order = $order->archive()->first();
                
                $Archive_order->update([
                    'products' => str_replace('"id":' . $product->id . ',"name":' . '"' . $product->name . '"', '"id":' . $product->id . ',"name":' . '"' . $request->name . '"', $Archive_order->products)
                ]);
    
            }
    
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'purchase_price' => $request->purchase_price,
                'quantity' => $request->quantity,
                'category_id' => $request->category,
                'images' => $uploadedImages,
                'discount_id' => $discount_id
            ]);
    
            return response()->json([
                'message' => 'Product updated succesfully'
            ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //

            $product = Product::find($id);

            if (isset($product)) {

                // get product into order 
                $check = Order::whereHas('products', function ($query) use ($product){
                    $query->where('products.id', $product->id);
                });

                $Orders = $check->get();

                // check if exists Product belong to order not received
                if ($check->where('received', 0)->exists()) {

                    return response()->json([
                        'message' => "You Can't Delete This Products It Has A Not Received Order"
                    ], 422);

                }else{
                    
                    foreach($Orders as $order){

                        $Archive_order = $order->archive()->first();
                        $Archive_order->update([
                            'products' => str_replace('"id":' . $id, '"id":"null"', $Archive_order->products)
                        ]);

                    }
                    
                    $product->delete();

                }

            }


            return response()->json(200);

    }

    public function trashedProducts($current_page){

        $validation = Validator::make([
            'current_page' => $current_page
        ], [
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;
        $products = Product::onlyTrashed()->get();

        return response()->json([
            'products' => ProductManagementResource::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage)
        ]);

    }

    public function trashedProductsSearch($query, $current_page){
        
        $itemsPerPage = 10;

        $validation = Validator::make([
            'query' => $query,
            'current_page' => $current_page
        ], [
            'query' => 'required|string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $products = Product::join('categories', 'categories.id', 'products.category_id')
        ->orderBy('products.created_at', 'desc')
        ->whereAny(
            ['products.name', 'products.description', 'categories.name'],
            'LIKE',
            '%' . $query .'%'
        )->onlyTrashed()->get();

        return response()->json([
            'products' => ProductManagementResource::collection($products->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($products->count()/$itemsPerPage),
        ]);    
    }

    public function restore($id){

            $product =  Product::onlyTrashed()->where('id', $id)->first();  

            // get product into order 
            $orders = Order::whereHas('products', function ($query) use ($product){
                $query->onlyTrashed()->where('products.id', $product->id);
            })->get();

            if (isset($orders)) {
                
                foreach($orders as $order){

                    $Archive_order = $order->archive()->first();
                    $Archive_order->update([
                        'products' => str_replace('"id":"null"', '"id":' . $product->id , $Archive_order->products)
                    ]);
                    
                }

            }

            $product->restore();
            return response()->json($product);
    
    }

    public function softDelete($id){

        if(Auth('api')->check()){

            $product =  Product::onlyTrashed()->where('id', $id)->first(); 
            $defaultBucket = app('firebase.storage')->getBucket();
    
            if ($product->images != null) {
                foreach ($product->images as $key => $image) {
                    if ($defaultBucket->object($image)->exists()) {
                        $defaultBucket->object($image)->delete();
                    }
                }
            }

            Auth('api')->user()->card()->where('product_id', $product->id)
            ->delete();

            $product->forceDelete();
            return response()->json([
                'message' => 'Product deleted succesfully'
            ]);

        }
        
    }

    public function session(Request $request)
    {
    
        $validation = Validator::make($request->all(), [
            'products_checkout' => 'required',
            'success' => 'required',
            'cancel' => 'required',
            'products' => [
                'required', 
                function ($attribute, $value, $fail) use ($request){

                    foreach($request->products as $productBuy){

                            $product = Product::find($productBuy['id']);

                            if ($product->quantity < $productBuy['quantity']) {
                                $fail('we have just ' . $product->quantity . ' from ' . $product->name . ' in our stock and you want ' . $productBuy['quantity']);
                            }

                    }

                } 
            ],
            'adress' => 'required',
            'adress.city' => 'required|string|max:100',
            'adress.line' => 'required|string|max:255',
            'adress.state' => 'required|string|max:100',
            'adress.postal_code' => 'required|string|max:10',
            'adress.country' => 'required|string|max:255',
        ]
        );

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
 
        $session = \Stripe\Checkout\Session::create([
            'line_items'  => $request->products_checkout,
            'mode'        => 'payment',
            'success_url' => $request->success . '/{CHECKOUT_SESSION_ID}',
            'cancel_url'  => $request->cancel . '/{CHECKOUT_SESSION_ID}',
        ]);
    
        $orderController = new OrderController();
        $order = $orderController->store(new Request([
            'products' => $request->products, 
            'session_id' => $session->id,
            'adress' => $request->adress
        ]));
        
        return response()->json($session->url);
    }

    public function success($session_id){

        $validation = Validator::make([
            'session_id' => $session_id
        ], [
            'session_id' => 'required|exists:orders,session_id',

        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $order = Order::where('session_id', $session_id)
        ->where('paid', 0)
        ->first();
        
        if(isset($order)){

            
            $order->update([
                'paid' => 1
            ]);

            $order->notifications()->where('response', '')->delete();


            $OrderRef = Str::random(5);

            while(Archive_order::where('ref', $OrderRef)->exists()){

                $OrderRef = Str::random(5);

            }

            Archive_order::create([
                'user_id' => Auth()->user()->id,
                'ref' => $OrderRef,
                'status' => OrderStatus::where('part', 1)->first()->statut,
                'quantity' => $order->quantity,
                'adress' => $order->adress,
                'total_price' => $order->total_price,
                'email' => Auth()->user()->email,
                'products' => $order->products()->get()->map(function($product){

                    $product->update([
                        'quantity' => $product->quantity - $product->pivot->quantity
                    ]);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $product->pivot->quantity,
                        'price' => $product->pivot->price,
                        'profit' => $product->pivot->price - ($product->purchase_price * $product->pivot->quantity),
                    ];

                }),
                'session_id' => $session_id,
                'part' => 1,
                'order_id'=> $order->id
            ]);

            Auth()->user()->card()->delete();

            return response()->json(200);

        }else {
            return response()->json('this order areally paid', 422);
        }
    
    }
    
    public function cancel($session_id){

        $validation = Validator::make([
            'session_id' => $session_id
        ], [
            'session_id' => 'required|exists:orders,session_id'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $order = Order::where('session_id', $session_id)->first();
        $OrderProducts = $order->products;

        $order->notifications()->where('response', '')->delete();

        $order->delete();

        // foreach($OrderProducts as $p){
            
        //     $product = Product::onlyTrashed()->where('id', $p->id)->first();
            
        //     if(isset($product)){
                
        //         $product->restore();

        //     }

        // }
        
        return response()->json(200);

    }

}
