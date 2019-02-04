<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webservice Messages Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during rendering application for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'default_error_msg' => 'Whoops! Something went wrong. Please try again later.',
    'default_success_msg' => 'Success',
    'image_upload_error' => 'Whoops! Something went wrong while uploading image. Please try again later.',
    'default_image_delete_error_msg' => 'Whoops! Something went wrong while deleting image. Please try again later.',
    'empty_data_msg' => 'No Data Found',
    'empty_data_msg_for_agent_cash_in_out' => 'Sorry! Customer not found.',
    'norecordsfound' => 'No Records Found',
    'parameter_missing' => 'Parameter Missing',
    'unauthorized_access' => 'You are not authorized for this action',
    'record_id_not_specified' => 'Record ID not specified',
    'error_registering_user' => 'We encountered an error while registering this user.',
    'can_not_delete_user_with_balance' => 'You can\'t delete user because they have balance.',
    'invalid_action_found' => 'Invalid resend OTP action found.',
    'inactive_account_found' => 'This account has been deactivated by the ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin, please contact our support team to get it reactivated.',
    'welcome_to_helapay_message' => 'Welcome to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet, where cashless money transfer is made easy!',
    'SOMETHING_WENT_WRONG_WHILE_SENDING_OTP' => 'Something went wrong while sending OTP. Please try again',
    'INVALID_EMAIL_ERROR' => 'You have entered an invalid e-mail address. Please try again.',
    'ERROR_UPDATING_PROFILE' => 'Whoops! Something went wrong while updating profile. Please try again later.',
    'UNAUTHORIZED_ACTION' => 'This is an unauthorized action.',
    'ACCOUNT_NOT_EXIST_WITH_THIS_MOBILE_NUMBER' => 'There is no account associated with this mobile number.',
    'INCORRECT_OR_MISSING_SLUG_PARAMETER_FOUND' => 'Incorrect or missing slug parameter found.',
    'INCORRECT_OR_MISSING_VALUE_PARAMETER_FOUND' => 'Incorrect or missing value parameter found.',
    'INCORRECT_DATA_FOUND' => 'Incorrect data found while performing operation.',
    'INVALID_INPUT_DATA_FOUND' => 'Input data is not valid',
    
    'OTP_SENT_SUCCESS_MESSAGE_FOR_PROD' => 'OTP successfully sent on <Mobile Number>.',
    'OTP_SENT_SUCCESS_MESSAGE_FOR_DEV' => 'OTP successfully sent on <Mobile Number>. OTP: <OTP>.',
    
    //login api
    'your_otp_for_signing_in_helapay_is' => 'Your OTP to Log In to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet is',
    'your_otp_for_login_in_helapay_is' => 'Your OTP to Log In to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet is',
    'logged_in' => 'You have logged in successfully.',
    'email_password' => 'Invalid email or password.',
    'invalid_otp' => 'OTP is invalid',
    'something_went_wrong' => 'Something went wrong. Please try again',
    'user_not_found' => 'User not found.',
    'user_not_verified' => 'User not verified. Please verify.',
    'user_not_registered' => 'User is not registered with ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet.',
    'user_already_rejected' => 'User already rejected.',
    // 'invalid_parameter' => 'Invalid request parameter.',
    'invalid_login' => 'Invalid login attempt.',
    // 'logout' => 'You have logged out successfully.',
    'error_to_get_login_history' => 'Error to get Login History',
    'login_history_get_successfully' => 'Login History Get Successfully',
    'ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_DEV' => 'We have sent an OTP on <Email>. OTP: <OTP>',
    'ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_PROD' => 'We have sent an OTP on <Email>.',

    'otp_list_get_successfully' => 'OTP List Get Successfully',
    'error_to_get_otp_list' => 'Error to get OTP List',

    //Registration API
    'user_type_not_valid' => 'Invalid user type',
    'type_not_valid' => 'Invalid type',
    'otp_send_on_this_number' => 'OTP successfully sent on',
    'your_otp_for_registering_in_helapay_is' => 'Hello!  Welcome to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet . Your sign up OTP is',
    'user_verified_suceessfully' => 'User verified successfully',
    'user_already_verified' => 'User already verified. Please sign in',
    'user_already_rejected' => 'To access your account, Please contact admin.',
    'error_registering_user' => 'We encountered an error while registering this user.',

    //Forgot Password
    'your_otp_to_reset_pasword_in_helapay_is' => 'Your OTP to reset PIN in ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet is',
    'otp_verified_successfully' => 'OTP verified successfully',
    'USER_NOT_FOUND_WITH_THIS_MOBILE_NUMBER' => 'You don\'t seems to have an account with us, please check your mobile number.',
    
    //Change Password
    'wrong_old_password' => 'The old PIN does not match.',
    'change_password_successfully' => 'Your PIN has been changed successfully.',
    'WRONG_OLD_PASSWORD' => 'The old password does not match.', // For admin
    'PASSWORD_CHANGE_SUCCESS_MESSAGE' => 'Password changed successfully.',
    'ERROR_WHILE_CHANGE_PASSWORD' => 'Whoops! Something went wrong while changing password. Please try again later.',

    //Registration API
    'user_added_suceessfully' => 'User added successfully.',
    'user_updated_suceessfully' => 'User updated successfully.',
    'error_updating_user' => 'We encountered an error while updating this user.',
    'error_adding_user' => 'We encountered an error while adding this user.',
    'user_not_found' => 'User not found.',
    'user_deleted_successfully' => 'User deleted successfully.',
    'user_role_changed_successfully' => 'User role changed successfully.',
    
    'error_admin_login' => 'Something went wrong while logging into admin panel',
    'user_is_not_verified' => 'This user is not verified',
    'mobile_is_not_verified' => 'This mobile number is not verified',

    // Admin
    'admin_reset_password_token_sent' => 'Mail sent, Please visit your mailbox.',
    'admin_password_reset_success' => 'Password reset successfully',
    'ALREADY_HAVE_SUPER_ADMIN_ROLE' => 'The user already have an admin access.',
    'admin_role_not_match' => 'This is Not a Super Admin',
    

    // Transaction API messages
    'insufficient_balance_msg' => 'You don\'t have sufficient balance. Please add money.',
    'transfer_request_already_proceed_or_not_possible' => 'Transfer money request already processed or not possible.',
    'transaction_already_done' => 'The transaction has already been processed.',
    'self_transaction_not_allowed' => 'You cannot do any action for the same user.',
    'add_withdraw_request_deleted_successfully' => 'Request deleted successfully.',
    'insufficient_balance' => ' don\'t have sufficient balance.',
    'transfer_money_transaction_msg_to_receiver' => 'You have received ZK <Value> from <Sender Name>/<Sender Mobile Number> in your wallet. Transaction ID:<Transaction ID>. Your current wallet balance is ZK <Receiver Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'transfer_money_transaction_msg_to_sender' => '<Sender Name> sent ZK <Value> to <Receiver Name> /<Receiver Mobile Number> from your wallet. Transaction ID:<Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'transfer_money_to_unregistered_user_mobile' => 'You are sending money to unregistered user as an E-Voucher. Please verify.',
    'message_to_unregistered_user_for_transfer' => '<User Name>/<User Mobile Number> sent you ZK <Value>. Your E-Voucher is <Authorization Code>. You can withdraw cash at agent or pay with your voucher wherever ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet is accepted.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'), // You have got E-Voucher with ZK <Value> from <User Name>/<User Mobile Number> through ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet. Visit your nearest ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Agent and provide this Authorisation code <Authorization Code> to receive this amount. Transaction ID: <Transaction ID>.
    'success_message_to_unregistered_user_for_transfer' => 'ZK <Value> have been transferred to this number <Mobile Number>, please ask the user to visit nearest ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Agent and provide him the authorization code to receive the amount.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'no_transfer_money_request_with_this_unregistered_number' => 'No request found with this mobile number.',    
    'no_transfer_money_request_with_this_unregistered_number_or_otp_mismatch' => 'No request found with this mobile number or invalid authorization code.',
    'amount_mismatch_of_cashout_to_unregistered_user' => 'You have entered the wrong amount. Please try with right one.',
    'OTP_MESSAGE_TO_SENDER_TO_TRANSFER_MONEY' => 'OTP <OTP> for transfer amount ZK <Value> to <Receiver Name>/<Receiver Mobile Number>.',
    'TRANSACTION_MESSAGE_TO_SENDER_AFTER_SEND_MONEY_TO_UNREGISTERED_USER' => '<Sender Name> sent ZK <Value> to <Receiver Mobile Number> from your wallet. Transaction ID: <Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'TRANSACTION_ALREADY_REJECTED_MESSAGE' => 'The transaction has been rejected.',

    // Add or Withdraw Money API messages
    'add_money_request_not_found' => 'OTP with this add money request is not found.',
    'withdraw_money_request_not_found' => 'OTP with this withdraw money request is not found.',
    'add_money_request_already_proceed_or_not_possible' => 'Add money request already processed or not possible.',
    'withdraw_money_request_already_proceed_or_not_possible' => 'Withdraw money request already processed or not possible.',
    'otp_expired' => 'Entered OTP has expired.',
    'invalid_input_parameter' => 'Invalid input parameter.',
    'add_money_request_agent_notification' => 'Your request to add ZK <Value> has been submitted successfully to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'add_money_request_admin_notification' => '<Agent Name> has requested to add ZK <value> to his wallet. Request Id is <request id>' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'withdraw_money_request_agent_notification' => 'Your request to withdraw ZK <Value> has been submitted successfully to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'withdraw_money_request_admin_notification' => '<Agent Name> has requested to withdraw ZK <value> from his wallet. Request Id is <request id>' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'withdraw_money_by_admin_request_agent_notification' => '' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin withdrawn ZK <Value> from your wallet. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'add_money_by_admin_request_agent_notification' => '' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin added ZK <Value> to your wallet. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_REQUEST_APPROVED_BY_ADMIN' => 'Your request to withdraw ZK <Value> has been approved by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_REQUEST_REJECTED_BY_ADMIN' => 'Your request to withdraw ZK <Value> has been rejected by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_COMMISSION_REQUEST_APPROVED_BY_ADMIN' => 'Your request to withdraw commission ZK <Value> has been approved by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_COMMISSION_REQUEST_REJECTED_BY_ADMIN' => 'Your request to withdraw commission ZK <Value> has been rejected by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_ADD_REQUEST_APPROVED_BY_ADMIN' => 'Your request to add ZK <Value> has been processed by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'NOTIFICATION_MESSAGE_TO_AGENT_AFTER_ADD_REQUEST_REJECTED_BY_ADMIN' => 'Your request to add ZK <Value> has been rejected by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'ADD_MONEY_OTP_MESSAGE_TO_AGENT' => 'OTP <OTP> for adding amount ZK <Value> to your wallet.',
    'WITHDRAW_MONEY_OTP_MESSAGE_TO_AGENT' => 'OTP <OTP> for withdraw amount ZK <Value> from your wallet.',
    'INSUFFICIENT_WALLET_BALANCE_MESSAGE_OF_AGENT' => '<Sender User> don\'t have sufficient balance to withdraw. Please ask agent to add money.',
    'INSUFFICIENT_COMMISSION_WALLET_BALANCE_MESSAGE_OF_AGENT' => '<Sender User> don\'t have sufficient commission balance to withdraw.',

    // Logout API messages
    'logout_success' => 'You have been logged out successfully.',
    'token_not_found' => 'Authorization token not found.',

    // Testimonial API messages
    'testimonial_updated_suceessfully' => 'Testimonial updated successfully.',
    'testimonial_added_suceessfully' => 'Testimonial added successfully.',
    'error_updating_testimonial' => 'We encountered an error while updating this testimonial.',
    'error_adding_testimonial' => 'We encountered an error while adding this testimonial.',
    'testimonial_not_found' => 'Testimonial not found.',
    'testimonial_deleted_successfully' => 'Testimonial deleted successfully.',

    // Blog API messages
    'blog_updated_suceessfully' => 'Blog updated successfully.',
    'blog_added_suceessfully' => 'Blog added successfully.',
    'error_updating_blog' => 'We encountered an error while updating this blog.',
    'error_adding_blog' => 'We encountered an error while adding this blog.',
    'blog_not_found' => 'Blog not found.',
    'blog_deleted_successfully' => 'Blog deleted successfully.',

    // Admin transafer Money messages
    'from_mobile_number_error_msg' => 'Invalid transfer or FROM Mobile is not verified.',
    'to_mobile_number_error_msg' => 'Invalid transfer or To Mobile is not verified.',
    'from_and_to_mobile_number_not_same' => 'From and To number should not be same.',
    'profile_updated_successfully' => 'Profile updated successfully.',

    'transfer_money_to_wallet_msg_to_receiver' => 'ZK <Value> has been added to your wallet by agent <Agent Name>/<Agent Mobile Number>. Transaction ID: <Transaction ID> and Helaypay Fee is ZK <Fee>. Your current wallet balance is ZK <Receiver Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'transfer_money_from_wallet_msg_to_receiver' => 'You have withdrawn ZK <Value> from your wallet through agent  <Agent Name>/<Agent Mobile Number>. Transaction ID: <Transaction ID> and Helaypay Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),

    // Contact Us messages
    'contact_us_success_msg' => 'Your inquiry has been sent successfully. Our Team will get in touch with you via the contact details you provided.',

    // Cash In / Out messages
    'SEND_MESSAGE_TO_SENDER_USER_AFTER_UNREGISTERED_USER_CASHOUT' => 'You E-Voucher sent to <Guest User Mobile Number> of amount ZK <Value> redeem through Agent <Agent Name>/<Agent Mobile Number>. Authorization Code: <Authorization Code>. Transaction ID: <Transaction ID>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'SEND_MESSAGE_TO_UNREGISTERED_USER_AFTER_SUCCESSFUL_CASHOUT' => 'You have redeem E-Voucher of ZK <Value> through Agent <Agent Name>/<Agent Mobile Number>. This E-Voucher had authorization code <Authorization Code> and was sent from <Sender Name>/<Sender Mobile Number> through ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet. Transaction ID: <Transaction ID>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'AGENT_MESSAGE_AFTER_CASHOUT_FROM_UNREGISTERED_USER' => 'You have redeem E-Voucher of ZK <Value> with Authorization code: <Authorization Code> to <Guest User Mobile Number>. Your current wallet balance is ZK <Agent Balance Amount>. Transaction ID: <Transaction ID>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'agent_message_after_cashout' => 'ZK <Value> has been added to your wallet for cash out made to <User Name>/<User Mobile Number>. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'agent_message_after_cashin' => 'You have added ZK <Value> to <Customer Name>/<Customer Mobile Number> from your wallet. Transaction ID: <Transaction ID>. Your current wallet balance is ZK <Agent Current Balance>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'CASHIN_OTP_MESSAGE_TO_AGENT' => 'OTP <OTP> for adding amount ZK <Value> to <Receiver Name>/<Receiver Mobile Number> wallet.',
    'CASHOUT_OTP_MESSAGE_TO_USER' => 'OTP <OTP> for withdraw amount ZK <Value> from your wallet through agent <Receiver Name>/<Receiver Mobile Number>.',
    'CASHIN_RESEND_OTP_MESSAGE_TO_AGENT' => 'OTP <OTP> for adding amount ZK <Value> to receiver wallet.',
    'CASHOUT_RESEND_OTP_MESSAGE_TO_USER' => 'OTP <OTP> for withdraw amount ZK <Value> from your wallet through agent.',
    'INVALID_AUTHORIZATION_CODE_OF_UNREGISTERED_USER' => 'This authorization code is not valid',

    // E-Voucher messages
    'e-voucher_to_unregistered_user_mobile' => 'You are sending E-Voucher to an unregistered user. Please verify.',
    'SOMETHING_WENT_WRONG_WHILE_SENDING_EVOUCHER' => 'Whoops! Something went wrong while sending E-Voucher. Please try again later.',
    'EVOUCHER_DOESNT_EXIST' => 'This E-Voucher does not exist.',
    'RECEIVER_USER_DOESNT_EXIST_OR_DELETED' => 'Receiver user doesn\'t exist or deleted.' ,
    'EVOUCHER_ALREADY_REDEEMED_BY_RECEIVER' => 'The E-Voucher has already been redeemed by receiver.',
    'EVOUCHER_ALREADY_REDEEMED' => 'You have already redeemed this E-Voucher.',
    'NO_VOUCHER_FOUND_WITH_THIS_AUTHORIZATION_CODE' => 'E-Voucher already redeemed or invalid code.',
    'INCORRECT_AMOUNT_WHILE_REDEEM_EVOUCHER' => 'You have entered the wrong amount. Please try with right one.',
    'USE_ADD_TO_WALLET_TO_REDEEM_VOUCHER' => 'Please use Add To Wallet from E-Voucher history to redeem this E-Voucher.',
    'EVOUCHER_OTP_MESSAGE_TO_SENDER' => 'OTP <OTP> for E-Voucher transaction of ZK <Value> to <Receiver Mobile Number>.',
    'EVOUCHER_REQUEST_ALREADY_PROCCED_NOT_POSSIBLE' => 'E-Voucher request already processed or not possible.',
    // 'E_VOUCHER_TEXT_MESSAGE_TO_RECEIVER_USER' => '<User Name>/<User Mobile Number> sent you ZK <Value>. Your E-Voucher authorization code is <Authorization Code>. Transaction ID is <Transaction ID>',
    'E_VOUCHER_TEXT_MESSAGE_TO_RECEIVER_USER' => '<User Name>/<User Mobile Number> sent you ZK <Value>. Your E-Voucher is <Authorization Code>. You can withdraw cash from agent or pay with your voucher wherever ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet is accepted.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_USER' => '<Sender Name> sent an E-Voucher of ZK <Value> to <Receiver Name> /<Receiver Mobile Number> from your wallet. Transaction ID: <Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_UNREGISTERED_USER' => '<Sender Name> sent an E-Voucher of ZK <Value> to <Receiver Mobile Number> from your wallet. Transaction ID: <Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_AGENT_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER' => 'You just cashed out an Evoucher for <Receiver Mobile Number>. Your Current wallet balance is ZK <Sender Balance Amount> & Transaction ID is <Transaction ID>' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_SENDER_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER' => 'You E-Voucher sent to <Receiver Name> /<Receiver Mobile Number> of amount ZK <Value> redeem through Agent <Agent Name> /<Agent Mobile Number>. Authorization Code:<Authorization Code>. Transaction Id:<Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_RECEIVER_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER' => 'You have redeem E-Voucher  of  ZK <Value> through Agent <Agent Name> /<Agent Mobile Number>. This E-Voucher had authorization code <Authorization Code> and was sent from <Sender Name> /<Sender Mobile Number> through ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet. Transaction Id:<Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. You receive ZK <Recieve Amount>' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'EVOUCHER_EXPIRED_MESSAGE_WHILE_REDEEM' => 'E-Voucher you trying to redeem is expired.',
    'EVOUCHER_EXPIRED_MESSAGE_WHILE_ADD_TO_WALLET' => 'E-Voucher you trying to add to wallet is expired.',
    'EVOUCHER_EXPIRED_MESSAGE_WHILE_RESEND' => 'E-Voucher you trying to resend code is expired.',

    // Commission messages
    'STATUS_UPDATED_SUCCESS_MESSAGE' => 'Status updated successfully.',
    'COMMISSION_DATA_DELETE_SUCCESS_MESSAGE' => 'Commission data deleted successfully.',
    'COMMISSION_DATA_ADDED_SUCCESS_MESSAGE' => 'Commision data added successfully.',
    'COMMISSION_DATA_UPDATED_SUCCESS_MESSAGE' => 'Commision data updated successfully.',
    'NULLABLE_END_RANGE_FOUND' => 'You have to update last nullable amount range first.',
    'START_AMOUNT_MUST_BE_ZERO' => 'Start range amount must be 0.00.',
    'START_AMOUNT_MUST_HAVE_GIVEN_VALUE' => 'Start range amount must be <Amount>.',
    'AGENT_COMMISSION_DATA_UPDATED_SUCCESS_MESSAGE' => 'Agent commission data updated successfully.',
    'AGENT_COMMISSION_DATA_REQUIRED' => 'Agent commission data is required.',
    'DO_NOT_ABLE_TO_FIND_USER_DETAIL' => 'We are not able to get your wallet detail. Please contact admin.',
    'YOU_DO_NOT_HAVE_COMMISSION_BALANCE_TO_ADD' => 'You don\'t have commission wallet balance to add into the wallet.',
    'YOU_DO_NOT_HAVE_COMMISSION_BALANCE_TO_WITHDRAW' => 'You don\'t have commission wallet balance to withdraw.',
    'YOU_DO_NOT_HAVE_SUFFICIENT_COMMISSION_BALANCE_TO_ADD' => 'You don\'t have requested amount in your commission wallet. Please try again.',
    'YOU_DO_NOT_HAVE_SUFFICIENT_COMMISSION_BALANCE_TO_WITHDRAW' => 'You don\'t have requested amount in your commission wallet. Please try again.',
    'ADD_COMMISSION_TO_WALLET_SUCCESS_MESSAGE' => 'ZK <Commission Wallet Balance> transferred successfully to your wallet.',
    'WITHDRAW_COMMISSION_FROM_WALLET_SUCCESS_MESSAGE' => 'Your request to withdraw commission has been generated. Our support team will get back to you shortly.',
    'GET_COMMISSION_AMOUNT_TO_ADD_INTO_WALLET_MESSAGE' => 'You are about to transfer ZK <Commission Wallet Balance> in your wallet. Do you want to proceed?',
    'GET_COMMISSION_AMOUNT_TO_WITHDTRAW_MESSAGE' => 'Your request will be sent to ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet Admin for withdrawing ZK <Commission Wallet Balance> from your commission wallet.',

    // Commission Calculation
    'AGENT_USER_DATA_NOT_FOUND' => 'Agent data not found.',
    'NOT_AUTHORIZE_TO_DO_THIS_ACTION' => 'You are not authorized to do this action.',
    'INSUFFICIENT_BALANCE_MESSAGE_FOR_CASHOUT' => '<Sender User> don\'t have sufficient balance. Please add money.',

    // Country Code management
    'COUNTRY_DATA_ADDED_SUCCESS_MESSAGE' => 'Country code added successfully.',
    'COUNTRY_DATA_DELETED_SUCCESS_MESSAGE' => 'Country code deleted successfully.',
    'COUNTRY_DATA_UPDATED_SUCCESS_MESSAGE' => 'Country code updated successfully.',

    // KYC Document management
    'KYC_DOCUMENT_ADDED_SUCCESS_MESSAGE' => 'Your document(s) are uploaded successfully. ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet team will verify and update you.',
    'KYC_DOCUMENT_APPROVED_SUCCESS_MESSAGE' => 'Congratulation! Your KYC is approved by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet team.',
    'KYC_DOCUMENT_REJECTED_MESSAGE' => "Your KYC is rejected, please check out " .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). " Wallet team's notes regarding rejection.",

    'KYC_DOCUMENT_TYPE_VALIDATION' => "Please select document type.",
    'KYC_DOCUMENT_ALLOWED_FILE_VALIDATION' => "Only pdf, png, jpeg, jpg, bmp and gif images are allowed",
    'KYC_DOCUMENT_MAX_SIZE_FILE_VALIDATION' => "Sorry! Maximum allowed size for an image is 5MB",
    'KYC_DOCUMENT_REQUIRED_FILE_VALIDATION' => "Please upload KYC Document(s).",
    'KYC_DOCUMENT_DELETED_SUCCESS_MESSAGE' => "The document is deleted successfully.",
    'KYC_DOCUMENT_NOT_FOUND' => "The document not found.",
    
    'document_image_upload_error' => 'Whoops! Something went wrong while uploading document. Please try again later.',

    'KYC_DOCUMENT_STATUS_SUCCESS_MESSAGE' => "KYC Document status updated successfully.",
    'KYC_DOCUMENT_APPROVED_SUCCESSFULLY_MESSAGE' => 'Congratulation! Your KYC is approved by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet team.',
    'KYC_DOCUMENT_APPROVED_SUCCESSFULLY_EMAIL_SUBJECT' => 'Congratulation! Your KYC is approved by ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Wallet team.',

    'KYC_DOCUMENT_REJECTED_SMS_MESSAGE' => "Your KYC is rejected, please check out " .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). " Wallet team's notes regarding rejection.",
    'KYC_DOCUMENT_REJECTED_EMAIL_SUBJECT' => "Your KYC is rejected, please check out " .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). " Wallet team's notes regarding rejection.",
    'KYC_DOCUMENT_ADDED_SUCCESS_MESSAGE_ADMIN' => "KYC documents are uploaded successfully.",

    // Permission API messages
    'permission_updated_suceessfully' => 'Permission updated successfully.',
    'permission_added_suceessfully' => 'Permission added successfully.',
    'error_updating_permission' => 'We encountered an error while updating this permission.',
    'error_adding_permission' => 'We encountered an error while adding this permission.',
    'permission_not_found' => 'Permission not found.',
    'permission_deleted_successfully' => 'Permission deleted successfully.',
    'assign_permission_suceessfully' => 'Permission assign to role successfully.',
    'error_assigning_permission' => 'We encountered an error while assigning permission(s) to role.',
    'ONLY_CHARACTER_WITH_UNDERSCORE_ALLOWED' => 'Slug value must have only charcater with underscore(_).',

    // Role API messages
    'role_updated_suceessfully' => 'Role updated successfully.',
    'role_added_suceessfully' => 'Role added successfully.',
    'error_updating_role' => 'We encountered an error while updating this role.',
    'error_adding_role' => 'We encountered an error while adding this role.',
    'role_not_found' => 'role not found.',
    'role_deleted_successfully' => 'Role deleted successfully.',

    // Settings API Messages
    'SEETING_DATA_NOT_FOUND_OR_DELETED' => 'Setting data not found or deleted.',
    'SETTINGS_DATA_UPDATED_SUCCESS_MESSAGE' => 'Setting data updated successfully.',

    // App version
    'ERROR_WHILE_CHECKING_APP_VERSION' => 'Whoops! Something went wrong while checking app version. Please try again later.',

    'insufficient_amount_for_commission' => 'Transaction not allowed, Please check transaction amount.',

    'nearby_agent_get_successfully' => 'Nearby Agents Get Successfully',
    // Beneficiary API messages
    'beneficiary_updated_suceessfully' => 'Beneficiary updated successfully.',
    'beneficiary_added_suceessfully' => 'Beneficiary added successfully.',
    'error_updating_beneficiary' => 'We encountered an error while updating this beneficiary.',
    'error_adding_beneficiary' => 'We encountered an error while adding this beneficiary.',
    'beneficiary_not_found' => 'Requested beneficiary not found.',
    'beneficiary_deleted_successfully' => 'Beneficiary deleted successfully.',
    'beneficiary_marked_primary_successfully' => "Beneficiary's bank account marked as primary successfully.",
    'beneficiary_otp_invalid' => 'Whoops! Invalid OTP.',
    'BENEFICIARY_OTP_MESSAGE_TO_USER' => 'OTP <OTP> for adding Beneficiary.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
    'MESSAGE_TO_SENDER_AFTER_SENT_MONEY_TO_BENEFICIARY' => 'You sent ZK <Value> to Beneficiary User <Receiver Name>/<Receiver Account Number> from your wallet. Transaction ID: <Transaction ID> and ' .Config::get('constant.APP_NAME_FOR_API_MESSAGE'). ' Fee is ZK <Fee>. Your current wallet balance is ZK <Sender Balance Amount>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),

    // Add money messages
    'ADD_WITHDRAW_MONEY_OTP_MESSAGE' => 'Your OTP for <Action> money is <OTP>.' . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'),
];
