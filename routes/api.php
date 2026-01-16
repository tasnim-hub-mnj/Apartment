<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OwnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('test',function(){
    return response()->json(['message'=>'ok']);
});

//تسجيل الدخول و الخروج و انشاء حساب
Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');
//******************************************************************************
Route::middleware('auth:sanctum')->group(function(){
//=============================================================================///////////////////
//***********User Profile Management*************
Route::post('profile',[ProfileController::class,'UpdateProfile']);
Route::get('profile',[ProfileController::class,'getUserProfile']);
Route::post('password',[UserController::class,'updatePassword']);


//=============================================================================///////////////////
Route::middleware('CheckRenter')->group(function()
{
//**********Reservations CRUD*************
Route::post('reservation/{apartmentId}',[ReservationController::class,'store'])->middleware('CheckApprovedUser');
Route::post('reservationUpdate/{reservationId}',[ReservationController::class,'update']);
Route::put('reservation/cancellation/{reservationId}',[ReservationController::class,'cancellation'])->middleware('CheckApprovedUser');

//**********Get Reservations*************
Route::get('reservations/confirmed',[ReservationController::class,'getConfirmedReservations']);
Route::get('reservations/cancelled',[ReservationController::class,'getCancelledReservations']);
Route::get('reservations/finished',[ReservationController::class,'getFinishedReservations']);

//************Favorite****************
Route::post('favorite/{apartmentId}',[FavoriteController::class,'addToFavorites'])->middleware('CheckApprovedUser');
Route::delete('favorite/{apartmentId}',[FavoriteController::class,'removeFromFavorites']);
// Route::get('countFavorites',[FavoriteController::class,'countFavorites']);
Route::get('favorite/AllICAR',[FavoriteController::class,'getAllFavoritesICAR']);
Route::get('favorite/AllDetailed/{apartmentId}',[FavoriteController::class,'getApartmentWithAllDetailed']);
//*********get all apartment************
Route::get('apartmentAll',[ApartmentController::class,'getAllApartmentsICAR']);
Route::get('apartment/AllDetailed/{apartmentId}',[ApartmentController::class,'getApartmentWithAllDetailed']);
//**********Filter Apartments***********
Route::post('FilterApartment',[ApartmentController::class,'getFilterApartments']);
//**********Ratings for Apartments***********
Route::post('Rating/{apartmentId}',[ApartmentController::class,'addRating']);

});
//=============================================================================///////////////////
Route::middleware('CheckOwner')->group(function()
{

//***********Apartments CRUD*******************
Route::post('apartment',[ApartmentController::class,'store'])->middleware('CheckApprovedUser');
Route::post('apartment/{apartmentId}',[ApartmentController::class,'update']);
Route::delete('apartment/{apartmentId}',[ApartmentController::class,'destroy']);

//***********Changed Approve_Status Reservation*************
Route::put('approved/reservation/{reservationId}',[OwnerController::class,'approved'])->middleware('CheckApprovedUser');
Route::put('rejected/reservation/{reservationId}',[OwnerController::class,'rejected'])->middleware('CheckApprovedUser');

//***********Update Payment Status*************
Route::put('updateStatusPay/reservation/{reservationId}',[OwnerController::class,'updateStatus_pay']);

//***********Get Reservation for my apartments****************
Route::get('pending/reservation',[OwnerController::class,'pendingReservation']);
Route::get('approved/reservation',[OwnerController::class,'approvedReservation']);

//***********Get my Apartments*****************
Route::get('getAllApartments',[OwnerController::class,'getAllApartmentsICAR']);
Route::get('getApartmentWithAllDetailed/{apartmentId}',[OwnerController::class,'getApartmentWithAllDetailed']);

Route::get('rating/apartment/{apartmentId}',[ApartmentController::class,'showRatingsForApartment']);
Route::get('countApartment',[OwnerController::class,'countApartmentOwner']);

});
//=============================================================================///////////////////
Route::middleware('CheckAdmin')->group(function()
{

//***********Admin Dashboard & Users Management*************
Route::get('dashboard',[AdminController::class,'dashboard']);
Route::get('users/approved',[AdminController::class,'approvedUsers']);
Route::get('users/pending',[AdminController::class,'pendingUsers']);

//**********Change User Approve Status*************
Route::put('users/{id}/approve',[AdminController::class,'approveUser']);
Route::put('users/{id}/rejecte',[AdminController::class,'rejecteUser']);
Route::delete('users/{id}',[AdminController::class,'deleteUser']);
});
//=============================================================================///////////////////
//***********Conversations*************
Route::post('conversations/apartment/{apartmentId}',[ConversationController::class,'store'])->middleware(['CheckApprovedUser','CheckRenter']);
Route::get('conversations',[ConversationController::class,'getConversations']);
Route::get('conversations/{conversationId}',[ConversationController::class,'getConversation']);
// Route::delete('conversations/{conversationId}',[ConversationController::class,'destroy']);

//=============================================================================///////////////////
//***********Messages*************
Route::post('conversations/{conversationId}/message',[MessageController::class,'store'])->middleware('CheckApprovedUser');
Route::get('conversations/{conversationId}/messages',[MessageController::class,'getMessagesInConvers']);
// Route::get('message/{messageId}',[MessageController::class,'getMessage']);
// Route::delete('message/{messageId}',[MessageController::class,'destroy']);
//=============================================================================///////////////////
Route::put('autoFinishReservations',[ReservationController::class,'autoFinishReservations']);
//=============================================================================///////////////////
//***********Notifications*************
Route::get('notifications',[NotificationController::class,'getMynotifications']);
Route::delete('notification/{notificationId}',[NotificationController::class,'destroy']);


});
