<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Contracts\Encryption\DecryptException;

class ReviewController extends Controller
{
    //

    public function index ($product_id){    

        $validation = Validator::make([
            'product_id' => $product_id,
        ],
        [
            'product_id' => 'required|exists:products,id'
        ]);

        if ($validation->fails()) {
            
            return response()->json('Invalid paramters', 422);

        }

        $product_details = [];
        $canUserMakeReview = false;
        $user_id = Auth('api')->check() ? Auth('api')->user()->id : null;
        $positiveAndNigative = [];
        $defaultBucket = app('firebase.storage')->getBucket();

        $product = Product::find($product_id);

        $positiveAndNigative = $product->reviews()->select('*')
        ->where('review_star', '>=', 3)
        ->orderBy('review_star', 'desc')
        ->orderBy('updated_at', 'desc')
        ->limit(1)
        ->union(
            $product->reviews()->select('*')
            ->where('review_star', '<', 3)
            ->orderBy('review_star', 'asc')
            ->orderBy('updated_at', 'desc')
            ->limit(1)
        )
        ->get();

        
        if( isset($user_id) ){
            
            try {

                $canUserMakeReview = User::find($user_id)->orders()->where('received', 1)->whereHas('products', function ($query) use ($product_id) {
                    $query->where('product_id', $product_id);
                })->exists();

            } catch (Throwable $th) {
                return response()->json('invalid infromation', 400);
            }

        }
        
        return response()->json([

            'product' => [
                'name' => $product->name,
                'image' => $defaultBucket->object($product->images[0])
                ->signedUrl(new \DateTime('15 min'))
            ],

            'NigativeAndPositive' => ReviewResource::collection($positiveAndNigative),
            
            'total_reviews' => $product->reviews->count(),

            'moyens' => $product->reviews()->selectRaw(
                'review_star, count(review_star)/' . $product->reviews->count() . ' as average'
            )->orderBy('review_star', 'desc')->groupBy('review_star')->get(),
            'permission_make_review' => $canUserMakeReview,

            'details' => Review::select(
                DB::raw('sum(review_star) / count(*) as moyen_reviews'),
                DB::raw('count(*) as reviews_count'),
            )->first()

        ]);

    }
    
    public function getReviews($product_id, $typeSort, $current_page, $numberStar = 'all'){

        $validation = Validator::make([
            'product_id' => $product_id,
            'typeSort' => $typeSort,
            'current_page' => $current_page,
            'numberStar' => $numberStar
        ],
        [
            'product_id' => 'required|exists:products,id',
            'typeSort' => 'required|in:mostRecent,topReviews',
            'current_page' => 'required|integer',
            'numberStar' => 'required|in:1,2,3,4,5,all'
        ]);

        if ($validation->fails()) {
            
            return response()->json('Invalid paramters', 422);

        }

        $product = Product::find($product_id);
        $query = $product->reviews();
        $itemsPerPage = 10;

        if ($typeSort == 'topReviews') {
            
            $query->orderBy('review_star', 'desc');
            
        }

        if ($typeSort == 'mostRecent') {
            
            $query->orderBy('updated_at', 'desc');
            
        }

        if ($numberStar != 'all') {
            
            $query->where('review_star', $numberStar);

        }

        $reviews = $query->get();

        return response()->json([
            'reviews' => ReviewResource::collection($reviews->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil( $reviews->count() / $itemsPerPage ),
            'all_reviews_count' => $product->reviews->count(),
            'reviews_count' => $reviews->count()
        ]);

    }

    public function store(Request $request){
        
        $validation = Validator::make($request->all(), [
            'review' => 'required',
            'review_star' => 'required|integer',
            'product_id' => 'required|exists:products,id',
            'images' => 'between:0,4',
            'images.*' => ['mimes:jpg,jpeg,png,webp', 'max:1024']
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        $user = Auth('api')->user();
        
        $checkIfReceivedOrder = false;

        
        $canUserMakeReview = User::find($user->id)->orders()->where('received', 1)
        ->whereHas('products', function ($query) use ($request) {
            $query->where('product_id', $request->product_id);
        })->exists();

        if ($canUserMakeReview) {

            $uploadedImages = [];

            if ($request->has('images')) {
                
                $defaultBucket = app('firebase.storage')->getBucket();
                
                foreach ($request->file('images') as $key => $file) {
                    
                    if ($defaultBucket->object("reviews/" . $file->getClientOriginalName())->exists()) {
                        $imageName = uniqid() . '_' . $file->getClientOriginalName();
                    }else{
                        $imageName = $file->getClientOriginalName();
                    }
    
                    // Open the uploaded image
                    $image = Image::make($file);
    
                    // Compress the image with a desired quality (e.g., 60)
                    $image->resize(350, 350, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
    
                    $image->encode('webp', 80);
    
                    $defaultBucket->upload($image->stream()->detach(), [
                        'name' => 'reviews/' . $imageName,
                    ]);

                    // Add the compressed image name to the array
                    $uploadedImages[] = 'reviews/' . $imageName;
    
                }
    
        
            }
            
            $review = Review::create([
                'review' => $request->review,
                'review_star' => $request->review_star,
                'product_id' => $request->product_id,
                'user_id' => $user->id,
                'images' => $uploadedImages
            ]);

            return new ReviewResource($review);

        }else {

            return response()->json('You dont have permission for make review on this product', 422);

        }

        


    }

    public function show($id){
        
        try {

            $id = Crypt::decrypt($id);
            $review = Review::find($id);
            $defaultBucket = app('firebase.storage')->getBucket();
            $imagesUrl = [];
    
            if (isset($review->images)) {
                
                foreach($review->images as $image){
                    $imagesUrl[] = $defaultBucket->object($image)
                    ->signedUrl(new \DateTime('1 hour'));
                }
    
            }
            
            return response()->json([
                'review' => $review->review,
                'star_number' => $review->review_star,
                'images_path' => $review->images,
                'images' => $imagesUrl
            ]);
        } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json($e, 422);
        }

    }

    public function update(Request $request, $id){

        $validation = Validator::make($request->all(), [
            'review' => 'required',
            'review_star' => 'integer',
            'images' => 'between:0,4',
            'images.*' => ['max:1024']
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        try {

            $id = Crypt::decrypt($id);
            $review = Review::find($id);
    
            if ($review->user_id == Auth('api')->user()->id) {

                $defaultBucket = app('firebase.storage')->getBucket();
                $uploadedImages = [];
        
                if (isset($request->images)) {
                    
                    if ($request->file('images') != null) {
        
                        $old_images = array_diff($request->old_images, $request->images);
                        
                        $new_images = array_diff($request->file('images'), $request->old_images);
        
                    }else {
        
                        $old_images = array_diff($request->old_images, $request->images);
                        $new_images = [];
        
                    }
                    
                    if (count($old_images) > 0) {
        
                        foreach ($old_images as $key => $file) {
        
                            if ($defaultBucket->object($file)->exists()) {
                                $defaultBucket->object($file)->delete();
                            }
                
                        }
        
                        $old_image_not_delete = array_diff($review->images, $old_images);
                        
                        foreach($old_image_not_delete as $image){
                            $uploadedImages[] = $image;
                        }
        
                    }else if (count($old_images) == 0) {
        
                        foreach($review->images as $image){
                            $uploadedImages[] = $image;
                        }
        
                    }
        
                    if (count($new_images) > 0) {
                        
                        foreach ($request->file('images') as $key => $file) {
        
                            if ($defaultBucket->object('reviews/' . $file->getClientOriginalName())->exists()) {
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
                                'name' => 'reviews/' . $imageName,
                            ]);
                            
                            // Add the compressed image name to the array
                            $uploadedImages[] = 'reviews/' . $imageName;
        
                        }
                        
                    }
        
                }
        
                $review->update([
                    'review' => $request->review,
                    'review_star' => $request->review_star,
                    'images' => $uploadedImages
                ]);
        
                return new ReviewResource($review);
                
            }else{

                return response()->json('This review isen\'t belong to you', 422);

            }

        } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json($e, 422);
        }
        
    }

    public function destroy($id){

        try {

            $id = Crypt::decrypt($id);
            $review = Review::find($id);

            if ($review->user_id == Auth('api')->user()->id) {
                
                if (isset($review->images)) {
                    
                    $defaultBucket = app('firebase.storage')->getBucket();
                    foreach ($review->images as $key => $image) {
                        if ($defaultBucket->object($image)->exists()) {
                            $defaultBucket->object($image)->delete();
                        }
                    }
                    
                }

                $review->delete();

            }else{

                return response()->json('This review isen\'t belong to you', 422);

            }
    
            return response()->json(200);
        } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json($e, 422);
        }


    }

}
