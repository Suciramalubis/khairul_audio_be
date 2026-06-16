<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// --- CONTROLLER USER (PUBLIC & AUTH) ---
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\NotificationController;   
use App\Http\Controllers\Api\UserProfileController;   
use App\Http\Controllers\Api\UserOrderController;   
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\Api\LocationController;

// --- CONTROLLER WEBHOOK ---
use App\Http\Controllers\PaymentController;

// --- CONTROLLER ADMIN (BACKOFFICE) ---
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\CourierController as AdminCourierController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. OTENTIKASI (Public)
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

Route::post('/forgot-password/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'forgotPasswordVerifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// ==========================================
// 2. WEBHOOK PAYMENT GATEWAY (TANPA LOGIN)
// ==========================================
Route::post('/payment/komerce/callback', [PaymentController::class, 'komerceWebhook']);

// ==========================================
// 3. PUBLIC ROUTES (Belanja Tanpa Login & Data Master)
// ==========================================
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/shipping/search', [ShippingController::class, 'searchLocation']);
Route::get('/locations/search', [LocationController::class, 'searchLocation']); 
Route::get('/locations/provinces', [LocationController::class, 'getProvinces']);
Route::get('/locations/cities/{province_id}', [LocationController::class, 'getCities']);
Route::get('/locations/subdistricts/{city_id}', [LocationController::class, 'getSubdistricts']);

Route::get('/cek-koneksi', function() {
    return response()->json(['message' => 'API Khairul Audio Berhasil Diakses!']);
});

// ==========================================
// 4. PROTECTED ROUTES (Hanya untuk User Login)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth User ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) { return $request->user(); });

    // --- User Profile & Orders ---
    Route::put('/user/profile', [UserProfileController::class, 'update']);
    Route::get('/user/orders', [UserOrderController::class, 'index']);
    Route::post('/user/orders/checkout', [UserOrderController::class, 'checkout']);
    Route::get('/user/orders/{id}', [UserOrderController::class, 'show']);
    Route::put('/user/orders/{id}/payment-method', [UserOrderController::class, 'updatePaymentMethod']);
    Route::put('/user/orders/{id}/cancel', [UserOrderController::class, 'cancelOrder']);
    Route::post('/user/reviews', [App\Http\Controllers\Api\ReviewController::class, 'store']);

    // --- RajaOngkir ---
    Route::post('/shipping/cost', [ShippingController::class, 'checkCost']); 

    // --- Notifikasi User ---
    Route::get('/notifications', [UserOrderController::class, 'getUserNotifications']);
    Route::post('/notifications/read-all', [UserOrderController::class, 'markAllUserNotifAsRead']);
    Route::post('/notifications/{id}/read', [UserOrderController::class, 'markUserNotifAsRead']);
    Route::delete('/notifications/{id}', [UserOrderController::class, 'deleteUserNotification']);

    // --- User Addresses ---
    Route::get('/user/addresses', [UserAddressController::class, 'index']);
    Route::post('/user/addresses', [UserAddressController::class, 'store']);
    Route::put('/user/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/user/addresses/{id}', [UserAddressController::class, 'destroy']);
    Route::put('/user/addresses/{id}/default', [UserAddressController::class, 'setDefault']);

    // --- Wishlist Routes ---
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add/{productId}', [WishlistController::class, 'add']);
    Route::delete('/wishlist/remove/{productId}', [WishlistController::class, 'remove']);
    Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check']);

});

// ==========================================
// 5. ADMIN ROUTES (Backoffice)
// ==========================================
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    
    Route::get('/chart', [AdminDashboardController::class, 'getChartData']);
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::get('/products/{id}', [AdminProductController::class, 'show']);
    Route::put('/products/{id}', [AdminProductController::class, 'update']); 
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
    Route::delete('/products/gallery/{id}', [AdminProductController::class, 'destroyGallery']);
    Route::get('/couriers', [AdminCourierController::class, 'index']);
    Route::put('/couriers/{id}', [AdminCourierController::class, 'update']);
    Route::post('/couriers/weights', [AdminCourierController::class, 'updateWeights']); 
    Route::post('/couriers/test-api', [AdminCourierController::class, 'checkCost']); 
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{id}', [AdminOrderController::class, 'update']);
    Route::get('/notifications', [UserOrderController::class, 'getAdminNotifications']);
    Route::put('/notifications/{id}/read', [UserOrderController::class, 'markNotificationAsRead']);
    Route::put('/notifications/read-all', [UserOrderController::class, 'markAllNotificationsAsRead']);
    Route::delete('/notifications/{id}', [UserOrderController::class, 'deleteNotification']);
    Route::put('/profile/password', [AdminProfileController::class, 'updatePassword']);
    Route::put('/profile/info', [AdminProfileController::class, 'updateProfile']);
    Route::put('/orders/{id}/tracking', [AdminOrderController::class, 'updateTracking']);
});