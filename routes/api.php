<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DiscountsController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\ArchiveOrdersController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UpdateProfileInformation;
use Illuminate\Support\Facades\Crypt;
use Kreait\Laravel\Firebase\FirebaseProject;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    
    $user = $request->user();
    
    return response()->json([
        'name' => $user->name,
        'email' => $user->email,
        'profile' => app('firebase.storage')->getBucket()->object($user->profile)
        ->signedUrl(new \DateTime('15 minutes')),
        'adress' => $user->adress,
    ]);

});

Route::POST('/login', [AuthenticationController::class, 'login'])->name('login');
Route::POST('/register', [AuthenticationController::class, 'register'])->name('register');

Route::GET('/products-category/{id}/{current_page}', [CategoryController::class, 'ProductsCategory']);

Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);

Route::GET('get-products/{id}', [ProductController::class, 'index']);

Route::get('reviews/{product_id}', [ReviewController::class, 'index']);
Route::get('reviews/{product_id}/{typeSort}/{current_page}/{numberStar}', [ReviewController::class, 'getReviews']);

Route::GET('/products-search/{query}/{current_page}', [ProductController::class, 'Search']);

Route::post('/forget-password', [AuthenticationController::class, 'forgetPassword']);
Route::post('/check-is-link-valide/{token}', [AuthenticationController::class, 'checkLinkIsValide']);
Route::post('/reset-password/{token}', [AuthenticationController::class, 'resetPassword']);

Route::middleware(['auth:api'])->group(function(){

    Route::get('/user-role', function (Request $request){
        $user = $request->user();
        return response()->json([
            'id' => Crypt::encrypt($user->id),
            'role' => $user->role->name,
            'verified_at' => $user->email_verified_at
        ]);
    });

    Route::middleware('verified')->group(function(){

        Route::GET('/products-management/{current_page}', [ProductController::class, 'ProductsManagement']);
        Route::PUT('/products-management/{id}', [ProductController::class, 'updateProducts']);
        Route::GET('/products-management/show/{id}', [ProductController::class, 'showProduct']);

        Route::GET('/products-management/search/{query}/{current_page}', [ProductController::class, 'DashboardSearch']);

        Route::put('/categories-management/{id}', [CategoryController::class, 'updateCategory']);
        Route::get('/categories-management/{current_page}', [CategoryController::class, 'get_categories']);
        Route::get('/categories-management/search/{query}/{current_page}', [CategoryController::class, 'Search']);

        Route::GET('/trashed-products/{current_page}', [ProductController::class, 'trashedProducts']);
        Route::GET('/trashed-products/search/{query}/{current_page}', [ProductController::class, 'trashedProductsSearch']);
        Route::POST('/trashed-products/restore/{id}', [ProductController::class, 'restore']);
        Route::DELETE('/trashed-products/delete/{id}', [ProductController::class, 'softDelete']);

        Route::post('/update-information', [UpdateProfileInformation::class, 'update']);
        Route::post('/update-password', [UpdateProfileInformation::class, 'updateUserPassword']);

        Route::get('/status', [OrderController::class, 'status']);

        Route::post('/success/{session_id}',[ProductController::class, 'success'])->name('success');

        Route::get('/cancel/{session_id}', [ProductController::class, 'cancel'])->name('cancel');

        Route::POST('/checkout', [ProductController::class, 'session']);

        Route::get('/user-orders', [OrderController::class, 'getUserOrders']);

        Route::get('/archive-orders/{current_pages}', [ArchiveOrdersController::class, 'index']);
        Route::get('/archive-orders/show/{id}', [ArchiveOrdersController::class, 'show']);
        Route::get('/archive-orders/search/{query?}/{start_date?}/{end_date?}/{status?}/{current_pages}', [ArchiveOrdersController::class, 'search']);

        Route::get('/order-confirmation', [NotificationController::class, 'index']);
        Route::post('/order-confirmation/remove/{id}/{delete_from}', [NotificationController::class, 'delete']);
        Route::put('/order-confirmation/{id}', [NotificationController::class, 'confirmatedOrNot']);
        Route::put('/order-confirmation/resend/{id}', [NotificationController::class, 'resend']);
        
        Route::post('discount', [DiscountsController::class, 'store']);
        Route::get('discount/{current_pages}', [DiscountsController::class, 'index']);
        Route::get('products-discount/{id}/{current_page}', [DiscountsController::class, 'discount_products']);
        Route::get('discount-select/{product_id}', [DiscountsController::class, 'Discount_and_categories']);
        Route::get('discount/show/{id}', [DiscountsController::class, 'show']);
        Route::put('update-discount/{id}', [DiscountsController::class, 'update']);
        Route::delete('discount/{id}', [DiscountsController::class, 'destroy']);
        Route::get('/discount/search/{query?}/{date?}/{status?}/{current_pages}', [DiscountsController::class, 'search']);

        Route::post('reviews', [ReviewController::class, 'store']);
        Route::put('reviews/{id}', [ReviewController::class, 'update']);
        Route::get('reviews/show/{id}', [ReviewController::class, 'show']);
        Route::delete('reviews/{id}', [ReviewController::class, 'destroy']);

        Route::get('/details', [StatisticController::class, 'home']);
        Route::get('statistic/orders-count/{current_year}', [StatisticController::class, 'orders']);
        Route::get('statistic/orders-amount/{current_year}', [StatisticController::class, 'orders_amount']);
        Route::get('statistic/visites/{current_year}', [StatisticController::class, 'visites']);
        Route::get('statistic/product-views/{current_year}', [StatisticController::class, 'product_views']);
        Route::get('statistic/categories-views/{current_date}/{type}', [StatisticController::class, 'categories_views']);
        Route::get('statistic/orders-profit/{current_date}', [StatisticController::class, 'Profits']);

        /* 
            I make other route for update with post method 
            because the put method not work in host I deployed at it
        */
        
        Route::apiResource('orders', OrderController::class);
        Route::put('update-orders/{id}', [OrderController::class, 'update']);

        Route::apiResource('card', CardController::class);
        Route::put('update-card/{id}', [CardController::class, 'update']);

        Route::post('notifications-test', [NotificationController::class, 'test']);
        
    });
    
    Route::post('/send-verification-code', [AuthenticationController::class, 'sendVerificationCode']);
    Route::post('/email-verification/{token}', [AuthenticationController::class, 'verify']);

    Route::POST('/logout', [AuthenticationController::class, 'logout']);



    
    

});
