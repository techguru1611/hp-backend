<?php
/**
 * Created by PhpStorm.
 * User: pcsaini
 * Date: 14-08-2018
 * Time: 11:13 AM
 */

return [
    'default_error_msg' => 'Error : <Message>',
    'login' => 'Login Success : <User> Logged In with email/mobile_no : <Email>.',
    'role_not_match' => 'Login Error : Not a Super Admin or Compliance with Email : <Email>',
    'invalid_login' => 'Login Error : Invalid Email or Password with Email : <Email>',

    'register_success' => 'Register Success: <User> Register Successfully',
    'register_error' => 'Register Error: Otp not match on This Mobile Number <User>',
    'login_success' => 'Login Success: <User> Login Successfully',
    'login_error' => 'Login Error: Otp not match on This Mobile Number <User>',
    'otp_error' => 'OTP Error : Otp not sent on this Number <Mobile Number>',
    'otp_success' => 'OTP Success : Otp <Otp> Sent Successfully on This Number <Mobile Number>',

    'logout_success' => 'Logout Success : <User Email> Logout Successfully',
    'logout_error' => 'Logout Error: Jwt Not Disable',

    'reset_token_sent' => 'Success: Reset Password Token Sent Successfully on Email : <Email>',
    'password_reset_success' => 'Success: Password Reset Successfully of User : <Email>',
    'password_change_success' => 'Success: Password Change Successfully of User : <Email>',
    'old_password_wrong' => 'Error : Old Password is Wrong of User Email : <Email>',

    'update_profile_success' => 'Success: Profile Update Successfully of User <User Email>',
    'INVALID_EMAIL_ERROR' => 'Error: Email <User Email> Invalid',

    'user_list_success' => 'Success: get User List by <User Email>',
    'agent_list_success' => 'Success: get Agent List by <User Email>',

    'user_add_success' => 'Success: <User> Add Successfully by <Admin>',
    'user_update_success' => 'Success: <User> Update Successfully by <Admin>',
    'add_agent_commission' => 'Success: Agent <User> Commission Add Successfully by <Admin>',
    'update_agent_commission' => 'Success: Agent <User> Commission Update Successfully by <Admin>',
    'error_update_user' => 'Error: <User> Not update or Add by <Admin>',

    'user_delete_success' => 'Success: <User> Delete Successfully by <Admin>',
    'make_agent_success' => 'Success: <User> Make Agent Successfully by <Admin>',
    'make_admin_success' => 'Success: <User> Make Admin Successfully by <Admin>',

    'get_user_detail' => 'Success: <User> Details get By <Admin>',

    'contact_us' => 'Success: Contact us by <User>',
    'get_testimonial' => 'Success: Get Testimonial List',
    'add_testimonial' => 'Success: Testimonial Add or Update Successfully by <User>',
    'add_testimonial_error' => 'Error: error in Add or Update Testimonial by <User>',
    'testimonial_delete' => 'Success: Testimonial Delete Successfully by <User>',

    'get_blog_list' => 'Success: Get Blog List',
    'blog_add_success' => 'Success: Blog Add or Update Successfully by <User>',
    'blog_add_error' => 'Error: error in add or Update Blog by <User>',
    'delete_blog' => 'Success: Blog Delete Successfully by <User>',

    'check_mobile_exists' => 'Success: Get User Details of Mobile No. <Mobile Number>',

    'validate_user_mobile' => 'Success: Validate User Mobile Number <Mobile Number> for <Action> Money',

    'otp_success_action' => 'Success: Otp Sent Successfully on <Mobile Number> for <Action> Money',
    'otp_error_action' => 'Error: error to Send Otp on <Mobile Number> for <Action> Money',

    'insufficient_balance' => 'Error : Insufficient Balance on Account <Mobile Number>',
    'add_or_withdraw_money' => 'Success: <Amount>, <Action> Money Form <From Mobile Number> to <To Mobile Number>',

    'reject_transaction' => 'Success: <Transaction Type> Transaction Id: <Transaction Id> Rejected By <User>',

    'add_or_withdraw' => 'Success: Transaction: <Transaction Id> of Amount <Amount> <Action> By <User>',

    'update_default_commission' => 'Success: Agent Default Commission Update to <Commission>',

    'get_default_commission' => 'Success: Agent Default Commission is <Commission>',

    'message_error' => 'Error: Message not Sent to <Mobile Number>',

    'KYC_document_add' => 'Success: <User>\'s KYC Document Add Successfully',

    'updateKYC' =>  'Success: <User>\'s KYC document status updated successfully.',

    'approveAddOrWithdrawRequest' => 'Success: <Action> request Approved By <User>',

    'transfer_money' => 'Success: ZK <Amount> Transfer Successfully From <From User> to <To User>',

    'cashInOrOut' => 'Success: ZK <Amount> <Action> to <To User> By <By User>',

    'sendMoney' => 'Success: <From User> Send ZK <Amount> to <TO User>',

    'e_voucher' => 'Success: <Form User> Send a E-Voucher of ZK <Amount> to <To User>',

    'redeem_e_voucher' => 'Success: Transaction Id : <Transaction> Redeem E-Voucher <Action> of ZK <Amount>',

    'commission' => 'Success: <User> ZK <Amount> Commission <Action>',

    'cash' => 'Success: <From User> <Action> ZK <Amount> by <By User>',

    'beneficiary_transfer' => 'Success: Amount <Amount> ZK Transfer to  Beneficiary User <To User> by <By User>'





];