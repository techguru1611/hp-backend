<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::post('register', 'AuthController@register');
Route::post('veryfyRegisterOtp', 'AuthController@verifyRegisterOTP');
Route::post('login', 'AuthController@login');
Route::post('verifyLoginOtp', 'AuthController@verifyLoginOtp');
Route::post('getToken', 'AuthController@getToken');
Route::post('requestOtp', 'AuthController@requestOtp');
Route::post('resendAdminLoginOTP', 'AuthController@resendAdminLoginOTP');
Route::post('verifyResetPasswordOtp', 'AuthController@verifyResetPasswordOtp');
Route::post('resetPassword', 'AuthController@resetPassword');
Route::post('changePassword', 'AuthController@changePassword');
// Country Code API
Route::any('getCountryCodeList', 'CountryController@view');
// Contact Us
Route::post('contactUs', 'Admin\ContactUsController@contactUs');

// Admin Login
Route::post('admin/login', 'Admin\UserController@login');
Route::post('admin/verifyLoginOtp', 'Admin\UserController@verifyLoginOtp');
Route::post('admin/forgotPassword', 'Admin\UserController@forgotPassword');
Route::post('admin/resetPassword/{resetToken}', 'Admin\UserController@resetPassword');

// Get testimonials
Route::get('testimonials', 'TestimonialController@view');
// Get blogs
Route::get('blogs', 'BlogController@view');

// Check App version
Route::get('checkAppVersion', 'CommonController@checkAppVersion');

//get setting list
Route::get('settings', 'CommonController@setting');

// Webservice
Route::post('userExists','WebserviceController@userExists');
Route::post('linkAccount','WebserviceController@linkAccount');

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::group(['middleware' => 'verify.user.status'], function () {
        //Admin API
        Route::group(['prefix' => 'admin', 'middleware' => 'role:superadmin'], function () {
            Route::post('getUserList', 'Admin\UserController@getUserList');
            Route::get('getAgentList', 'Admin\UserController@getAgentList');
            Route::post('addOrUpdateUser', 'Admin\UserController@addOrUpdateUser');
            Route::delete('users/{id}', 'Admin\UserController@destroyUser');
            Route::post('makeAgent', 'Admin\UserController@makeAgent');

            //
            Route::post('addOrWithdrawMoneyForAgentByAdmin', 'Admin\MoneyController@addOrWithdrawMoneyForAgentByAdmin');
            // Add / withdraw money request
            Route::post('addOrWithdrawMoney', 'AgentMoneyController@addOrWithdrawMoney');
            Route::post('verifyAddOrWithdrawMoneyOtp', 'Admin\MoneyController@verifyAddOrWithdrawMoneyOtp');

            Route::get('addWithdrawRequest', 'Admin\MoneyController@addWithdrawRequest');
            Route::post('approveAddOrWithdrawRequest', 'Admin\MoneyController@approveAddOrWithdrawRequest');

            Route::post('addWithdrawRequest/edit', 'Admin\AddWithdrawController@update');
            Route::delete('addWithdrawRequest/delete/{id}', 'Admin\AddWithdrawController@destroy');

            // Transfer Money
            Route::post('validateMobileNumber', 'Admin\MoneyController@validateMobileNumber');
            Route::post('transferMoneyRequest', 'Admin\MoneyController@transferMoneyRequest');
            Route::post('verifyTransferMoneyOtp', 'Admin\MoneyController@verifyTransferMoneyOtp');

            // Add or Withdraw from user balance
            Route::post('user/validateMobileNumber', 'Admin\AddWithdrawController@validateUserMobileNumber');
            Route::post('user/addWithdrawRequest', 'Admin\AddWithdrawController@addWithdrawMoneyRequestFromUserBalance');
            Route::post('user/verifyAddWithdrawOtp', 'Admin\AddWithdrawController@verifyAddWithdrawMoneyFromUserBalanceOtp');

            // Get contact us detail
            Route::get('contactUs', 'Admin\ContactUsController@view');

            // Change password
            Route::post('changePassword', 'Admin\UserController@changePassword');

            // Update profile
            Route::post('updateProfile', 'Admin\UserController@updateProfile');

            // Make admin
            Route::post('makeAdmin', 'Admin\UserController@makeAdmin');

            // Commission Management
            Route::get('commission/list', 'Admin\CommissionController@list');
            Route::post('commission/updateStatus', 'Admin\CommissionController@updateStatus');
            Route::post('commission/delete', 'Admin\CommissionController@delete');
            Route::post('commission/add', 'Admin\CommissionController@add');
            Route::post('commission/update', 'Admin\CommissionController@update');
            Route::get('commission/default', 'Admin\CommissionController@getDefaultCommission');

            Route::post('commission/agent/default', 'Admin\CommissionController@addOrUpdateDefaultAgentCommission');

            // Country Management
            Route::get('country/list', 'Admin\CountryController@list');
            Route::post('country/add', 'Admin\CountryController@add');
            Route::post('country/update', 'Admin\CountryController@update');
            Route::post('country/delete', 'Admin\CountryController@delete');

            // Login History API
            Route::get('loginHistory', 'Admin\LoginHistoryController@userLoginHistory');

            //OTP List
            Route::get('OTPManagementList', 'Admin\OTPManagementController@OTPManagementList');

            /**
             * @Added Date: 31st Jul, 2018.
             */
            // Permission API
            Route::post('permissionList', 'Admin\PermissionController@permissionList');
            Route::post('addOrUpdatePermission', 'Admin\PermissionController@addOrUpdatePermission');
            Route::post('getPermissionById', 'Admin\PermissionController@getPermissionById');
            Route::post('deletePermission/{id}', 'Admin\PermissionController@deletePermission');

            // Assing Permission to Role API
            Route::post('assignPermissoinToRole', 'Admin\PermissionController@assignPermissoinToRole');
            Route::post('getPermissionByRoleId', 'Admin\PermissionController@getPermissionByRoleId');

            /**
             * @Added Date: 31st Jul, 2018.
             */
            // Roles API
            Route::get('roleList', 'Admin\RoleController@roleList');
            Route::post('addOrUpdateRole', 'Admin\RoleController@addOrUpdateRole');
            Route::post('getRoleById', 'Admin\RoleController@getRoleById');
            Route::post('deleteRole/{id}', 'Admin\RoleController@deleteRole');

            /**
             * @Added Date: 07th Aug, 2018.
             * User Detail API
             */
            Route::post('getUserDetail', 'Admin\UserController@getUserDetail');

            // Update Setting value
            Route::get('settings', 'Admin\SettingController@list');
            // Route::post('setting/update', 'Admin\SettingController@updateSetting'); // Not in use as it is update single setting
            Route::post('settings/update', 'Admin\SettingController@updateSettings');
            Route::post('settings/image/update', 'Admin\SettingController@updateImageSettings');
            
            Route::group(['prefix' => 'beneficiary'], function () {
                Route::post('getAllList', 'UserBeneficiaryController@getAll');
            });
        });

        Route::group(['prefix' => 'admin', 'middleware' => 'role:superadmin-compliance'], function () {
            // Transaction History
            Route::get('transferHistory', 'Admin\TransactionController@transferMoneyHistory');
            Route::get('allTransactionHistory', 'Admin\MoneyController@allTransactionHistory');
            Route::get('addWithdrawHistory', 'Admin\TransactionController@addWithdrawMoneyHistory');
            Route::get('transactionHistory', 'Admin\TransactionController@adminTransactionHistory');
            // Audit Transaction History for Admin
            Route::get('allAuditTransactionHistory', 'Admin\TransactionController@allAuditTransactionHistory');

            /**
             * Added on 27th Julty, 2018
             * KYC Document Approve/Reject APIs
             */
            Route::post('getKYCDocumentByUserId', 'Admin\KycDocumentController@getKYCDocumentByUserId');
            Route::post('updateKYCStatus', 'Admin\KycDocumentController@updateKYCStatus');
            Route::post('addMultipleFile', 'Admin\KycDocumentController@addMultipleFile');
            Route::post('getPendingKYCDocumentList', 'Admin\KycDocumentController@getPendingKYCDocumentList');
            Route::post('deleteDocument', 'Admin\KycDocumentController@deleteDocument');
        });

        // Agent API
        Route::group(['middleware' => 'role:agent'], function () {
            Route::post('addOrWithdrawMoney', 'AgentMoneyController@addOrWithdrawMoney');
            Route::post('verifyAddOrWithdrawMoneyOtp', 'AgentMoneyController@verifyAddOrWithdrawMoneyOtp');
            // Check customer mobile exists
            Route::post('validateCustomerMobile', 'AgentMoneyController@validateCustomerMobileExist');
            Route::post('agent/cashInOut/commission', 'CashInCashOutController@agentCashInCashOutCommission');
            Route::post('agentCashInCashOut', 'AgentMoneyController@agentCashInCashOutMoney');
            Route::post('verifyCashInOrOutOtp', 'AgentMoneyController@verifyCashInOrOutOtp');

            // Cashout to unregistered user
            // Route::post('agent/cashout/validateUnregisteredUserOTP', 'CashInCashOutController@validateUnregisteredUserOTPForCashout');
            // Route::post('agent/cashout/unregisteredUser', 'CashInCashOutController@cashoutToUnregisteredUser');

            // Redeem voucher of user
            Route::post('agent/redeem/verifyEvoucherCode', 'EvoucherController@verifyEvoucherCode');
            Route::post('agent/redeem/evoucher', 'EvoucherController@redeemVoucher');

            // Add and withdraw commission
            Route::get('agent/commission/getBalance', 'CommissionController@getBalance');
            Route::post('agent/commission/addToWallet', 'CommissionController@addToWallet');
            Route::post('agent/commission/withdrawFromWallet', 'CommissionController@withdrawFromWallet');
            Route::get('agent/commission/history', 'CommissionController@history');
        });

        // Common API (Which is accesible to authorized user)
        Route::get('getUserDetail', 'AuthController@getUserDetail');
        Route::post('checkMobileExist', 'SendMoneyController@checkMobileExist');
        // Send Money
        Route::post('transfer/fee', 'SendMoneyController@transferFee'); // Tranfer fee calculation
        Route::post('sendMoney', 'SendMoneyController@sendMoney');
        Route::get('transactionHistory', 'SendMoneyController@transactionHistory');
        Route::get('recentTransaction', 'SendMoneyController@recentTransaction');
        // Testimonial
        Route::post('addOrUpdateTestimonials', 'TestimonialController@addOrUpdateTestimonials');
        Route::delete('deleteTestimonials/{id}', 'TestimonialController@deleteTestimonials');
        // Blog
        Route::post('addOrUpdateBlogs', 'BlogController@addOrUpdateBlog');
        Route::delete('deleteBlogs/{id}', 'BlogController@deleteBlog');
        // Logout API
        Route::post('logout', 'Admin\UserController@logout');
        // Request OTP
        Route::post('resendOTP', 'CommonController@resendOTP');
        // Upate Profile API
        Route::post('updateProfile', 'Admin\UserController@updateProfile');
        // E-voucher API

        // To send e-voucher with OTP verification
        // Route::post('evoucher/sendOTP', 'EvoucherController@sendOTP');
        // Route::post('evoucher/verifyOTP', 'EvoucherController@verifyOTP');

        // To send e-voucher without OTP\
        Route::post('evoucher/calculateFee', 'EvoucherController@sendEvoucherFee');
        Route::post('evoucher/send', 'EvoucherController@send');

        Route::post('evoucher/action', 'EvoucherController@action');
        Route::post('evoucher/addToWallet/fee', 'EvoucherController@evoucherAddToWalletFee');
        // Notification history
        Route::get('notification/history', 'NotificationController@history');

        /**
         * Added on 26th Julty, 2018
         * KYC document API
         */
        Route::get('common/getDocumentTypeList', 'CommonController@getDocumentTypeList');
        //Route::post('kycdocument/add', 'KycDocumentController@add');
        Route::post('kycdocument/add', 'KycDocumentController@addSingleFile');
        Route::post('kycdocument/getUserDocumentList', 'KycDocumentController@getUserDocumentList');
        Route::post('kycdocument/addMultipleFile', 'KycDocumentController@addMultipleFile');
        Route::delete('kycdocument/deleteDocument/{id}', 'KycDocumentController@deleteDocument');

        //Print Receipt API
        Route::post('printReceipt', 'Admin\TransactionController@printReceipt');

        // Get assigned permission to Role by role id
        Route::post('getPermissionByRoleId', 'Admin\PermissionController@getPermissionByRoleId');

        // get nearby agent
        Route::post('getNearByAgent', 'CommonController@getNearByAgent');

        // get role list
        Route::get('roles','Admin\RoleController@roles');
        /** 
         * getListOfTrangloCodes is used to retrive the list of Purpose, Source of fund, Sender & Beneficiary Relationship, Account Type, Sender Identification Type and Beneficiary Identification Type 
         */
        Route::post('getListOfTrangloCodes', 'CommonController@getListOfTrangloCodes');

        /**
         * @date: 07th Sep, 2018
         * 
         * Beneficiary API
         */
        Route::group(['prefix' => 'beneficiary'], function () {
            Route::post('addOrUpdate', 'UserBeneficiaryController@save');
            Route::get('getUserBeneficiary', 'UserBeneficiaryController@getUserBeneficiary');
            Route::get('getUserBeneficiaryList','UserBeneficiaryController@getUserBeneficiaryList');
            //Route::post('verifyOTP', 'UserBeneficiaryController@verifyOTP');
            //Route::post('getAll', 'UserBeneficiaryController@getAll');
            //Route::post('markAsPrimaryAccount', 'UserBeneficiaryController@markAsPrimary');
            Route::delete('remove/{id}', 'UserBeneficiaryController@destroy');
            Route::get('getDetails/{id}', 'UserBeneficiaryController@getDetails');
            //Route::post('getBankDetails', 'UserBeneficiaryController@getBankDetails');

            // transfer money
            //Route::post('convertAmount', 'BeneficiaryTransactionController@convertAmount');
            Route::post('transferFee','BeneficiaryTransactionController@transferFee');
            Route::post('sendMoney', 'BeneficiaryTransactionController@sendMoney');
        });

        // Webservice
        Route::post('extPayment','WebserviceController@extPayment');
    });
});


