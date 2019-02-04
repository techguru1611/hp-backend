<?php

return [

    'HELAPAY_ADMIN_NAME' => env('HELAPAY_ADMIN_NAME'),
    'GUEST_NAME' => 'Unregistered User',
    'SELF' => 'You',
    'SELF_USER' => 'Self',

    'APP_NAME' => env('APP_NAME'),
    'APP_NAME_FOR_API_MESSAGE' => env('APP_NAME_FOR_API_MESSAGE'),
    'URL_TO_SEND_IN_SMS' => env('URL_TO_SEND_IN_SMS'),
    'DISPLAY_OTP' => env('DISPLAY_OTP', 0),
    'COMPANY_NAME' => env('COMPANY_NAME'),
    'COMPANY_EMAIL' => env('COMPANY_EMAIL'),
    'COMPANY_ADDRESS' => env('COMPANY_ADDRESS'),
    'COMPANY_FACEBOOK_URL' => env('COMPANY_FACEBOOK_URL'),
    'COMPANY_TWITTER_URL' => env('COMPANY_TWITTER_URL'),
    'COMPANY_URL' => env('COMPANY_URL'),
    'DEFAULT_AGENT_LATITUDE' => env('DEFAULT_AGENT_LATITUDE'),
    'DEFAULT_AGENT_LONGITUDE' => env('DEFAULT_AGENT_LONGITUDE'),
    'ADMIN_EMAIL_NO'=> '',
    'ADMIN_MOBILE_NO'=> '',

    'beneficiary_helapay_fees'  => 2.00,

    'SUPER_ADMIN_SLUG' => 'superadmin',
    'USER_SLUG' => 'user',
    'AGENT_SLUG' => 'agent',
    'COMPLIANCE_SLUG' => 'compliance',

    'SUPER_ADMIN_ROLE_NAME' => 'Super Admin',
    'USER_ROLE_NAME' => 'User',
    'AGENT_ROLE_NAME' => 'Agent',
    'COMPLIANCE_ROLE_NAME' => 'Compliance',
    'EXTERNAL_USER' => 'External User',

    'SUPER_ADMIN_ROLE_ID' => 1,
    'USER_ROLE_ID' => 2,
    'AGENT_ROLE_ID' => 3,
    'COMPLIANCE_ROLE_ID' => 4,

    'ACTIVE_FLAG' => 'active',
    'INACTIVE_FLAG' => 'inactive',
    'DELETED_FLAG' => 'deleted',

    // gender
    'MALE' => 'male',
    'FEMALE' => 'female',
    'NON_BINARY' => 'non-binary',

    /*OTP Expire Time in Seconds*/
    'OTP_EXPIRE_SECONDS' => 180,
    'RESEND_OTP_EXPIRE_SECONDS' => 600,

    /*INFOBIP Production Credentials*/
    'INFOBIP_USER_ID' => 'paullokende',
    'INFOBIP_PASSWORD' => 'Kompetens@2016',
    /*INFOBIP Production Credentials*/

    /*TWILIO Production Credentials*/
    'TWILIO_ACCOUNT_SID' => 'ACc01d14beac3a7b15636eef352148838f',
    'TWILIO_AUTH_TOKEN' => 'e45c044516cc643f3077040b934c1f9c',
    'TWILIO_PHONE_NUMBER' => '6194856767',
    /*TWILIO Production Credentials*/

    /*TWILIO Testing Credentials*/
    // 'TWILIO_ACCOUNT_SID'=>'ACe8fd6e6ecb1636e42843387062836d1b',
    // 'TWILIO_AUTH_TOKEN'=>'26f5f8b5c1b523615c5cb7a9b6ef24d6',
    // 'TWILIO_PHONE_NUMBER'=>'+15005550006',
    /*TWILIO Testing Credentials*/

    /* SMS Gateway Credentials*/
    'SMS_GATEWAY_URL' => 'https://gatewayapi.com/rest/mtsms',
    'SMS_GATEWAY_SENDER' => 'HelaPay',
    'SMS_GATEWAY_TOKEN' => 'QYmCJNHBQ0W2Lpaw5dXr7kZqyTS69J-SYISEms4Ou1wAmgaTw7QGlwOhR6PlBB-h',

    'USER_ORIGINAL_IMAGE_UPLOAD_PATH' => 'uploads/user/original/',
    'USER_THUMB_IMAGE_UPLOAD_PATH' => 'uploads/user/thumb/',
    'USER_THUMB_IMAGE_HEIGHT' => '300',
    'USER_THUMB_IMAGE_WIDTH' => '300',

    'TESTIMONIAL_USER_ORIGINAL_IMAGE_UPLOAD_PATH' => 'uploads/testimonial/original/',
    'TESTIMONIAL_USER_THUMB_IMAGE_UPLOAD_PATH' => 'uploads/testimonial/thumb/',
    'TESTIMONIAL_USER_THUMB_IMAGE_HEIGHT' => '300',
    'TESTIMONIAL_USER_THUMB_IMAGE_WIDTH' => '300',

    'BLOG_ORIGINAL_IMAGE_UPLOAD_PATH' => 'uploads/blog/original/',
    'BLOG_THUMB_IMAGE_UPLOAD_PATH' => 'uploads/blog/thumb/',
    'BLOG_THUMB_IMAGE_HEIGHT' => '300',
    'BLOG_THUMB_IMAGE_WIDTH' => '300',

    'DEFAULT_COUNTRY' => 'ZK',

    'PENDING_MOBILE_STATUS' => 0, // Not-verified
    'VERIFIED_MOBILE_STATUS' => 1, // Verified
    'REJECTED_MOBILE_STATUS' => 2, // Rejected
    'UNREGISTERED_USER_STATUS' => 3, // Un-registered

    'ADD_MONEY_TRANSACTION_TYPE' => 1,
    'WITHDRAW_MONEY_TRANSACTION_TYPE' => 2,
    'ONE_TO_ONE_TRANSACTION_TYPE' => 3,
    'CASH_IN_TRANSACTION_TYPE' => 4,
    'E_VOUCHER_TRANSACTION_TYPE' => 5,
    'REDEEMED_TRANSACTION_TYPE' => 6,
    'E_VOUCHER_CASHOUT_TRANSACTION_TYPE' => 7,
    'CASH_OUT_TRANSACTION_TYPE' => 8,
    'ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE' => 9,
    'WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE' => 10,
    'BENEFICIARY_TRANSFER_TYPE' => 21,

    'PENDING_TRANSACTION_STATUS' => 1,
    'SUCCESS_TRANSACTION_STATUS' => 2,
    'FAILED_TRANSACTION_STATUS' => 3,
    'REJECTED_TRANSACTION_STATUS' => 4,
    'EXPIRED_TRANSACTION_STATUS' => 5,

    'PENDING_TRANSACTION' => 'Pending',
    'SUCCESS_TRANSACTION' => 'Success',
    'FAILED_TRANSACTION' => 'Failed',
    'REJECTED_TRANSACTION' => 'Rejected',

    'TRANSACTION_HISTORY_PER_PAGE_LIMIT' => 10,
    'RECENT_TRANSACTION_LIMIT' => 6,
    'TRANSFER_MONEY_TRANSACTION_LIMIT' => 10,
    'ADD_WITHDRAW_MONEY_TRANSACTION_LIMIT' => 10,
    'COMMISSION_TRANSACTION_HISTORY_PER_PAGE_LIMIT' => 10,
    'NOTIFICATION_HISTORY_PER_PAGE_LIMIT' => 10,
    'LOGIN_HISTORY_PER_PAGE_LIMIT' => 10,

    'MONEY_SENT' => 'Money Sent',
    'MONEY_RECEIVED' => 'Money Received',
    'TRANSFER_MONEY' => 'Transfer Money',
    'ADDED_TO_WALLET' => 'Added to Wallet',
    'ADD_MONEY' => 'Add to wallet',
    'MONEY_WITHDRAW' => 'Withdraw money',
    'COMMISSION_MONEY_WITHDRAW' => 'Withdraw commission money',
    'CASE_IN' => 'Cash In',
    'CASE_OUT' => 'Cash Out',
    'E_VOUCHER_SENT' => 'Sent',
    'E_VOUCHER_SENT_STATUS' => 'E-Voucher Sent',
    'E_VOUCHER_RECEIVED_STATUS' => 'E-Voucher Received',
    'E_VOUCHER_RECEIVED' => 'Received',
    'E_VOUCHER_REDEEMED' => 'Redeemed',
    // 'E_VOUCHER_REDEEM' => 'Redeem',
    'E_VOUCHER_REDEEM' => 'Redeemed',
    'E_VOUCHER_REDEEM_STATUS' => 'E-Voucher Redeem',
    'E_VOUCHER_CASHOUT_STATUS' => 'E-Voucher Cash Out',
    'E_VOUCHER_CASHEDOUT_STATUS' => 'E-Voucher Cashed Out',
    'ADDED_COMMISSION_TO_WALLET' => 'Added Commission to Wallet',
    'WITHDRAW_COMMISSION_FROM_WALLET' => 'Withdraw Commission from Wallet',
    'BENEFICIARY_TRANSFER' => 'Beneficiary Transfer',

    'SELF_MOBILE_ERROR_CODE' => 'self',
    'UNVERIFIED_MOBILE_ERROR_CODE' => 'unverified',
    'UNREGISTERED_MOBILE_ERROR_CODE' => 'unregistered',
    'UNKNOWN_ERROR_CODE' => 'unknown',

    'ADD_MONEY_ACTION' => 'add',
    'WITHDRAW_MONEY_ACTION' => 'withdraw',
    'ADD_WITHDRAW_DATA' => 'add_withdraw',
    'TRANSFER_MONEY_ACTION' => 'transfer',
    'E-VOUCHER_ACTION' => 'e-voucher',
    'RESEND_EVOUCHER_CODE_ACTION' => 'resend_evoucher_code',
    'REDEEM_EVOUCHER_TO_WALLET' => 'redeem_evoucher_to_wallet',

    'CASH_IN_ACTION' => 'cashin',
    'CASH_OUT_ACTION' => 'cashout',

    'MAX_ALLOW_TIME_TO_IDLE_IN_SECONDS' => 60 * 30,

    // Mojaloop Credentials
    'MOJALOOP_DFSP2_USER_NAME' => 'dfsp2-test',
    'MOJALOOP_DFSP2_PASSWORD' => 'dfsp2-test',
    'MOJALOOP_DFSP2_IDENTIFER_TYPE_CODE' => 'tel',
    'MOJALOOP_ROLE_NAME' => 'customer',
    'MOJALOOP_DFSP2_DEFAULT_CURRENCY' => 'ZMW',

    // Mojaloop API request URL
    'CREATE_DFSP_ACCOUNT' => 'http://34.251.76.201:8010/wallet',

    // Commission Management
    'START' => 'start',
    'NOT_DEFAULT_COMMISSION_FLAG' => 0,
    'DEFAULT_COMMISSION_FLAG' => 1,
    'ADD_COMMISSION_TO_WALLET_ACTION' => 'add_commission',
    'WITHDARW_COMMISSION_FROM_WALLET_ACTION' => 'withdraw_commission',
    'DEFAULT_ADMIN_COMMISSION' => 1,
    'DEFAULT_TRANSFER_MONEY_HELAPAY_FEE' => 1,
    'DEFAULT_E_VOUCHER_ADD_TO_WALLET_HELAPAY_FEE' => 1,
    'DEFAULT_SEND_E_VOUCHER_FEE' => 1,

    // Transaction filter constant
    'MONEY_SENT_FILTER' => 'money_sent',
    'MONEY_RECEIVED_FILTER' => 'money_received',
    'ADDED_TO_WALLET_FILTER' => 'added_to_wallet',
    'WITHDRAW_MONEY_FILTER' => 'withdraw_money',
    'CASHIN_FILTER' => 'cash_in',
    'CASHOUT_FILTER' => 'cash_out',
    'E_VOUCHER_REDEEM_FILTER' => 'redeem',
    'ADDED_COMMISSION_TO_WALLET_FILTER' => 'added_commission_to_wallet',
    'WITHDRAWAL_OF_COMMISSION_FROM_WALLET_FILTER' => 'withdrawal_of_commission_from_wallet',
    'E_VOUCHER_SENT_FILTER' => 'sent',
    'E_VOUCHER_RECEIVED_FILTER' => 'received',
    'E_VOUCHER_REDEEMED_FILTER' => 'redeemed',
    'E_VOUCHER_ADDED_TO_WALLET_FILTER' => 'e_voucher_added_to_wallet',
    'E_VOUCHER_CASHED_OUT_FILTER' => 'e_voucher_cashed_wallet',

    // Response status
    'SUCCESS_RESPONSE_STATUS' => 1,
    'FAIL_RESPONSE_STATUS' => 1,

    // Login Platform
    'LOGIN' => 1,
    'LOGOUT' => 0,
    'LOGIN_SLUG' => 'login',
    'LOGOUT_SLUG' => 'logout',
    'WEB_PLATFORM' => 'WEB',
    'IOS_PLATFORM' => 'IOS',
    'ANDROID_PLATFORM' => 'Android',
    'ALL_USER_DETAIL_SLUG' => 'all',
    'AUDIT_TRANSACTION_LIMIT' => 10,

    /**
     * Following are the Audit transactions types.
     * We already have defined transaction for user transaction tables, to avoid confusion and conflict
     * we are going to use tranaction type id from 11 to 25 for audit transaction.
     *
     */
    'AUDIT_TRANSACTION_TYPE_ADD_MONEY' => 11,
    'AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY' => 12,
    'AUDIT_TRANSACTION_TYPE_ONE_TO_ONE' => 13,
    'AUDIT_TRANSACTION_TYPE_CASH_IN' => 14,
    'AUDIT_TRANSACTION_TYPE_E_VOUCHER' => 15,
    'AUDIT_TRANSACTION_TYPE_REDEEMED' => 16,
    'AUDIT_TRANSACTION_TYPE_E_VOUCHER_CASHOUT' => 17,
    'AUDIT_TRANSACTION_TYPE_CASH_OUT' => 18,
    'AUDIT_TRANSACTION_TYPE_ADD_COMMISSION_TO_WALLET' => 19,
    'AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY_FROM_COMMISSION' => 20,
    'AUDIT_BENEFICIARY_TRANSFER_TYPE' => 22,

    'USER_KYC_DOCUMENT_UPLOAD_PATH' => 'public/uploads/user/kyc',
    'USER_KYC_DOCUMENT_GET_UPLOAD_PATH' => 'storage/uploads/user/kyc/',

    'DOCUMENT_TYPE_ARRAY' => [
        ["id" => 1, "name" => "Bank Statement"],
        ["id" => 2, "name" => "Driving License"],
        ["id" => 3, "name" => "Passport"],
        ["id" => 4, "name" => "Identity Card"],
        ["id" => 5, "name" => "Electricity Bill"],
        ["id" => 6, "name" => "Water Bill"],
    ],

    /** 0 = Pending | 1 = Uploaded or Submitted | 2 = Rejected | 3 = Corrrection | 4 = Approved or completed  */
    'KYC_PENDING_STATUS' => 0,
    'KYC_SUBMITTED_STATUS' => 1,
    'KYC_REJECTED_STATUS' => 2,
    'KYC_CORRECTION_STATUS' => 3,
    'KYC_APPROVED_STATUS' => 4,

    'KYC_PENDING' => 'Pending',
    'KYC_SUBMITTED' => 'Submitted',
    'KYC_APPROVED' => 'Approved',
    'KYC_REJECTED' => 'Rejected',
    'KYC_CORRECTION' => 'Correction',

    'PENDING_KYC_COMMENT' => 'Get your KYC done.',
    'USER_KYC_PENDING_STATUS' => 0,
    'USER_KYC_SUBMITTED_STATUS' => 1,
    'USER_KYC_REJECTED_STATUS' => 2,
    'USER_KYC_CORRECTION_STATUS' => 3,
    'USER_KYC_APPROVED_STATUS' => 4,

    // Receipt Title
    'ADD_MONEY_RECEIPT_TITLE' => 'ADD MONEY RECEIPT',
    'WITHDRAW_MONEY_RECEIPT_TITLE' => 'WITHDRAW MONEY RECEIPT',
    'ADD_COMMISSION_RECEIPT_TITLE' => 'ADD COMMISSION RECEIPT',
    'WITHDRAW_COMMISSION_RECEIPT_TITLE' => 'WITHDRAW COMMISSION RECEIPT',
    'TRANSFER_MONEY_RECEIPT_TITLE' => 'TRANSFER MONEY RECEIPT',
    'CASH_IN_RECEIPT_TITLE' => 'CASH IN RECEIPT',
    'CASH_OUT_RECEIPT_TITLE' => 'CASH OUT RECEIPT',
    'E_VOUCHER_SENT_RECEIPT_TITLE' => 'E-VOUCHER SENT RECEIPT',
    'E_VOUCHER_RECEIVED_RECEIPT_TITLE' => 'E-VOUCHER RECEIVED RECEIPT',
    'E_VOUCHER_ADD_TO_WALLET_RECEIPT_TITLE' => 'E-VOUCHER ADD TO WALLET RECEIPT',
    'E_VOUCHER_CASHED_OUT_RECEIPT_TITLE' => 'E-VOUCHER CASHED OUT RECEIPT',
    'E_VOUCHER_CASH_OUT_RECEIPT_TITLE' => 'E-VOUCHER CASH OUT RECEIPT',
    'TRANSFER_MONEY_SENT_RECEIPT_TITLE' => 'TRANSFER MONEY SENT RECEIPT',
    'TRANSFER_MONEY_RECEIVED_RECEIPT_TITLE' => 'TRANSFER MONEY RECEIVED RECEIPT',

    // Receipt amount label
    'ADD_MONEY_AMOUNT_LABEL' => 'Added Amount',
    'WITHDRAW_MONEY_AMOUNT_LABEL' => 'Withdraw Amount',
    'MONEY_SENT_AMOUNT_LABEL' => 'Sent Amount',
    'MONEY_RECEIVED_AMOUNT_LABEL' => 'Received Amount',
    'CASH_IN_AMOUNT_LABEL' => 'Cashed In Amount',
    'CASH_OUT_AMOUNT_LABEL' => 'Cashed Out Amount',
    'E_VOUCHER_SENT_AMOUNT_LABEL' => 'E-Voucher Sent Amount',
    'E_VOUCHER_RECEIVED_AMOUNT_LABEL' => 'E-Voucher Received Amount',
    'E_VOUCHER_ADDED_TO_WALLET_AMOUNT_LABEL' => 'E-Voucher Added to Wallet Amount',
    'E_VOUCHER_REDEEMED_AMOUNT_LABEL' => 'E-Voucher Redeemed Amount',
    'E_VOUCHER_CASH_OUT_AMOUNT_LABEL' => 'E-Voucher Cash Out Amount',
    'E_VOUCHER_CASHED_OUT_AMOUNT_LABEL' => 'E-Voucher Cashed Out Amount',
    'ADDED_COMMISSION_AMOUNT_LABEL' => 'Added Commission Amount',
    'WITHDRAW_COMMISSION_AMOUNT_LABEL' => 'Withdraw Commission Amount',
    'TRANSFER_MONEY_AMOUNT_LABEL' => 'Transferred Amount',

    // Transaction History Status
    'TRANSACTION_MONEY_SENT_STATUS' => 'Money Sent',
    'TRANSACTION_MONEY_RECEIVED_STATUS' => 'Money Received',
    'TRANSACTION_E_VOUCHER_SENT_STATUS' => 'E-Voucher Sent',
    'TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_STATUS' => 'E-Voucher Added To Wallet',
    'TRANSACTION_E_VOUCHER_RECEIVED_STATUS' => 'E-Voucher Received',
    'TRANSACTION_E_VOUCHER_CASHED_OUT_STATUS' => 'E-Voucher Cashed Out',
    'TRANSACTION_CASH_IN_STATUS' => 'Cash In',
    'TRANSACTION_CASH_OUT_STATUS' => 'Cash Out',
    'TRANSACTION_ADDED_TO_WALLET_STATUS' => 'Money Added To Wallet',
    'TRANSACTION_WITHDRAW_FROM_WALLET_STATUS' => 'Money Withdraw From Wallet',
    'TRANSACTION_ADDED_COMMISSION_TO_WALLET_STATUS' => 'Commission Added To Wallet',
    'TRANSACTION_WITHDRAW_COMMISSION_FROM_WALLET_STATUS' => 'Commission Withdraw From Wallet',

    // Transaction History Images
    'MONEY_DEPOSIT_IMAGE_PATH' => 'images/plus.png',
    'MONEY_WITHDRAW_IMAGE_PATH' => 'images/minus.png',

    // Transaction History Button Text
    'E_VOUCHER_RESEND_BUTTON_TEXT' => 'Resend',
    'E_VOUCHER_ADD_TO_WALLET_BUTTON_TEXT' => 'Add To Wallet',
    'E_VOUCHER_ADDED_TO_WALLET_BUTTON_TEXT' => 'Added To Wallet',
    'E_VOUCHER_CASHED_OUT_BUTTON_TEXT' => 'Cashed Out',

    // Transaction History Button Status
    'BUTTON_DISABLE_STATUS' => 'disable',
    'BUTTON_ENABLE_STATUS' => 'enable',

    // Transaction Type
    'TRANSACTION_MONEY_SENT_TYPE' => 'Money Sent',
    'TRANSACTION_MONEY_RECEIVED_TYPE' => 'Money Received',
    'TRANSACTION_E_VOUCHER_SENT_TYPE' => 'E-Voucher Sent',
    'TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_TYPE' => 'E-Voucher Added To Wallet',
    'TRANSACTION_E_VOUCHER_RECEIVED_TYPE' => 'E-Voucher Received',
    'TRANSACTION_E_VOUCHER_CASHED_OUT_TYPE' => 'E-Voucher Cashed Out',
    'TRANSACTION_CASH_IN_TYPE' => 'Cash In',
    'TRANSACTION_CASH_OUT_TYPE' => 'Cash Out',
    'TRANSACTION_ADDED_TO_WALLET_TYPE' => 'Money Added To Wallet',
    'TRANSACTION_WITHDRAW_FROM_WALLET_TYPE' => 'Money Withdraw From Wallet',
    'TRANSACTION_ADDED_COMMISSION_TO_WALLET_TYPE' => 'Commission Added To Wallet',
    'TRANSACTION_WITHDRAW_COMMISSION_FROM_WALLET_TYPE' => 'Commission Withdraw From Wallet',

    // Transaction fee Type
    'TRANSFER_MONEY_FEE' => 'transfer',
    'E_VOUCHER_ADD_TO_WALLET_FEE' => 'add_evoucher_to_wallet',
    'SEND_E_VOUCHER_FEE' => 'send_evoucher',
    'BENEFICIARY_TRANSFER_FEE' => 'beneficiary',

    // Transaction filter
    'TRANSACTION_FILTER' => [
        [
            'filter_text' => 'Money Sent',
            'filter_slug' => 'money_sent',
        ],
        [
            'filter_text' => 'Money Received',
            'filter_slug' => 'money_received',
        ],
        [
            'filter_text' => 'Money Added To Wallet',
            'filter_slug' => 'added_to_wallet',
        ],
        [
            'filter_text' => 'Money Withdraw From Wallet',
            'filter_slug' => 'withdraw_money',
        ],
        [
            'filter_text' => 'Commission Added To Wallet',
            'filter_slug' => 'added_commission_to_wallet',
        ],
        [
            'filter_text' => 'Commission Withdraw From Wallet',
            'filter_slug' => 'withdrawal_of_commission_from_wallet',
        ],
        [
            'filter_text' => 'Cash In',
            'filter_slug' => 'cash_in',
        ],
        [
            'filter_text' => 'Cash Out',
            'filter_slug' => 'cash_out',
        ],
        [
            'filter_text' => 'E-Voucher Sent',
            'filter_slug' => 'sent',
        ],
        [
            'filter_text' => 'E-Voucher Received',
            'filter_slug' => 'received',
        ],
        [
            'filter_text' => 'E-Voucher Added To Wallet',
            'filter_slug' => 'e_voucher_added_to_wallet',
        ],
        [
            'filter_text' => 'E-Voucher Cashed Out',
            'filter_slug' => 'e_voucher_cashed_wallet',
        ],
        [
            'filter_text' => 'BENEFICIARY_TRANSFER',
            'filter_slug' => 'beneficiary_transfer',
        ]
    ],

    // E-Voucher filter
    'E_VOUCHER_FILTER' => [
        [
            'filter_text' => 'E-Voucher Sent',
            'filter_slug' => 'sent',
        ],
        [
            'filter_text' => 'E-Voucher Received',
            'filter_slug' => 'received',
        ],
        [
            'filter_text' => 'E-Voucher Added To Wallet',
            'filter_slug' => 'e_voucher_added_to_wallet',
        ],
        [
            'filter_text' => 'E-Voucher Cashed Out',
            'filter_slug' => 'e_voucher_cashed_wallet',
        ],
    ],

    // E-Voucher filter
    'COMMISSION_FILTER' => [
        [
            'filter_text' => 'Cash In',
            'filter_slug' => 'cash_in',
        ],
        [
            'filter_text' => 'Cash Out',
            'filter_slug' => 'cash_out',
        ],
        [
            'filter_text' => 'E-Voucher Cashed Out',
            'filter_slug' => 'e_voucher_cashed_wallet',
        ],
    ],

    // Settings
    'LOGO_SETTING_SLUG' => 'logo',
    'COMPANY_NAME_SETTING_SLUG' => 'company_name',
    'E_VOUCHER_VALIDITY_SETTING_SLUG' => 'e-voucher_validity',
    'COPY_RIGHT_STRING_SETTING_SLUG' => 'copy_right_string',
    'TRANSFER_FEE_SETTING_SLUG' => 'default_transfer_fee',
    'ADD_TO_WALLET_FEE_SETTING_SLUG' => 'default_add_to_wallet_fee',
    'SEND_E_VOUCHER_FEE_SETTING_SLUG' => 'default_send_evoucher_fee',
    'COMPANY_EMAIL_SETTING_SLUG' => 'company_email',
    'COMPANY_PHONE_NUMBER_SETTING_SLUG' => 'company_phone_number',
    'COMPANY_ADDRESS_SETTING_SLUG' => 'company_address',
    'COMPANY_FACEBOOK_URL_SLUG' => 'company_facebook_url',
    'COMPANY_TWITTER_URL_SLUG' => 'company_twitter_url',
    'COMPANY_URL_SLUG' => 'company_url',
    'DEFAULT_LATITUDE' => 'default_latitude',
    'DEFAULT_LONGITUDE' => 'default_longitude',

    'DEFAULT_EVOUCHER_EXPIRE_DAYS' => 90,

    'SETTING_LOGO_ORIGINAL_IMAGE_UPLOAD_PATH' => 'uploads/setting/original/',
    'SETTING_LOGO_THUMB_IMAGE_UPLOAD_PATH' => 'uploads/setting/thumb/',
    'SETTING_LOGO_THUMB_IMAGE_HEIGHT' => 300,
    'SETTING_LOGO_THUMB_IMAGE_WIDTH' => 300,

    // App version
    'IOS_APP_VERSION' => '1.0.0',
    'IOS_FORCE_UPDATE' => false,
    'ANDROID_APP_VERSION' => '1.0.0',
    'ANDROID_VERSION_CODE' => 1,
    'ANDROID_FORCE_UPDATE' => false,

    //Otp Management Operation
    'OTP_O_LOGIN' => 1,
    'OTP_O_REGISTER' => 2,
    'OTP_O_FORGOT_PASSWORD' => 3,
    'OTP_O_ADD_MONEY_VERIFICATION' => 4,
    'OTP_O_APPROVE_ADD_MONEY_VERIFICATION' => 5,
    'OTP_O_WITHDRAW_MONEY_VERIFICATION' => 6,
    'OTP_O_APPROVE_WITHDRAW_MONEY_VERIFICATION' => 7,
    'OTP_O_ADD_COMMISSION_TO_WALLET_VERIFICATION' => 8,
    'OTP_O_APPROVE_ADD_COMMISSION_TO_WALLET_VERIFICATION' => 9,
    'OTP_O_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION' => 10,
    'OTP_O_APPROVE_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION' => 11,
    'OTP_O_TRANSFER_MONEY_VERIFICATION' => 12,
    'OTP_O_CASH_IN_VERIFICATION' => 13,
    'OTP_O_CASH_OUT_VERIFICATION' => 14,
    'OTP_O_E_VOUCHER_SENT_VERIFICATION' => 15,
    'OTP_O_E_VOUCHER_AUTHORIZATION_CODE' => 16,
    'OTP_O_E_VOUCHER_CASH_OUT_VERIFICATION' => 17,
    'OTP_O_E_VOUCHER_ADD_TO_WALLET_VERIFICATION' => 18,

    //Otp Management Operation Name
    'OTP_N_LOGIN' => 'Login',
    'OTP_N_REGISTER' => 'Register',
    'OTP_N_FORGOT_PASSWORD' => 'Forgot Password',
    'OTP_N_ADD_MONEY_VERIFICATION' => 'Add Money Verification',
    'OTP_N_APPROVE_ADD_MONEY_VERIFICATION' => 'Approve Add Money',
    'OTP_N_WITHDRAW_MONEY_VERIFICATION' => 'Withdraw Money Verification',
    'OTP_N_APPROVE_WITHDRAW_MONEY_VERIFICATION' => 'Approve Withdraw Money',
    'OTP_N_ADD_COMMISSION_TO_WALLET_VERIFICATION' => 'Add Commission to Wallet',
    'OTP_N_APPROVE_ADD_COMMISSION_TO_WALLET_VERIFICATION' => 'Add Commission to Wallet',
    'OTP_N_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION' => 'Withdraw Commission From Wallet',
    'OTP_N_APPROVE_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION' => 'Withdraw Commission From Wallet',
    'OTP_N_TRANSFER_MONEY_VERIFICATION' => 'Transfer Money',
    'OTP_N_CASH_IN_VERIFICATION' => 'Cash In',
    'OTP_N_CASH_OUT_VERIFICATION' => 'Cash Out',
    'OTP_N_E_VOUCHER_SENT_VERIFICATION' => 'E-Voucher Sent',
    'OTP_N_E_VOUCHER_AUTHORIZATION_CODE' => 'E-Voucher Authorization Code',
    'OTP_N_E_VOUCHER_CASH_OUT_VERIFICATION' => 'E-Voucher Cash Out',
    'OTP_N_E_VOUCHER_ADD_TO_WALLET_VERIFICATION' => 'E-Voucher Add to Wallet',

    'OTP_OPERATION_FILTER' => [
        [
            'transaction_type' => 'Login',
            'transaction_id' => 1,
        ],
        [
            'transaction_type' => 'Register',
            'transaction_id' => 2,
        ],
        [
            'transaction_type' => 'Forgot Password',
            'transaction_id' => 3,
        ],
        [
            'transaction_type' => 'Add Money Verification',
            'transaction_id' => 4,
        ],
        [
            'transaction_type' => 'Approve Add Money',
            'transaction_id' => 5,
        ],
        [
            'transaction_type' => 'Withdraw Money Verification',
            'transaction_id' => 6,
        ],
        [
            'transaction_type' => 'Approve Withdraw Money',
            'transaction_id' => 7,
        ],
        [
            'transaction_type' => 'Add Commission to Wallet',
            'transaction_id' => 8,
        ],
        [
            'transaction_type' => 'Approve Add Commission to Wallet',
            'transaction_id' => 9,
        ],
        [
            'transaction_type' => 'Withdraw Commission From Wallet',
            'transaction_id' => 10,
        ],
        [
            'transaction_type' => 'Approve Withdraw Commission From Wallet',
            'transaction_id' => 11,
        ],
        [
            'transaction_type' => 'Transfer Money',
            'transaction_id' => 12,
        ],
        [
            'transaction_type' => 'Cash In',
            'transaction_id' => 13,
        ],
        [
            'transaction_type' => 'Cash Out',
            'transaction_id' => 14,
        ],
        [
            'transaction_type' => 'E-Voucher Sent',
            'transaction_id' => 15,
        ],
        [
            'transaction_type' => 'E-Voucher Authorization Code',
            'transaction_id' => 16,
        ],
        [
            'transaction_type' => 'E-Voucher Cash Out',
            'transaction_id' => 17,
        ],
        [
            'transaction_type' => 'E-Voucher Add to Wallet',
            'transaction_id' => 18,
        ],
    ],

    'TRANGLO_CODE_TYPES' => [
        /** FOR ID NAME  */        
        1 => "Purpose",
        2 => "Source of fund",
        3 => "Sender and Beneficiary Relationship",
        4 => "Account Type",
        5 => "Sender Identification Type",
        6 => "Beneficiary Identification Type",

        /** FOR ID REQUEST */        
        "Purpose" => 1,
        "Source_of_fund" => 2,
        "Relationship" => 3,
        "Account_Type" => 4,
        "Sender_Identification_Type" => 5,
        "Beneficiary_Identification_Type" => 6,
    ],

    'PENDING_BENEFICIARY_STATUS' => 0,
    'APPROVED_BENEFICIARY_STATUS' => 1,
    'REJECTED_BENEFICIARY_STATUS' => 2,
    'BENEFICIARY_PER_PAGE_LIMIT' => 10,
];
