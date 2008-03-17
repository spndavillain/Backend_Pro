<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
    /**
     * Userlib Language Array
     *
     * Contains all language strings used by the Userlib class.
     *
     * @package			BackendPro
     * @subpackage		Languages
     * @author		    Adam Price
     * @copyright		Copyright (c) 2008
     * @license			http://www.gnu.org/licenses/lgpl.html
     */
     
     /* Actions & Titles & Form labels */
     $lang['userlib_logout'] = 'Logout';
     $lang['userlib_login'] = 'Login';
     $lang['userlib_reset'] = 'Reset';
     $lang['userlib_remember_me'] = 'Remember Me';
     $lang['userlib_forgotten_password'] = 'Forgotten Password';
     $lang['userlib_reset_password'] = 'Reset Password';
     $lang['userlib_email'] = 'Email';
     $lang['userlib_password'] = 'Password'; 
     $lang['userlib_username'] = 'Username';
     $lang['userlib_confirm_password'] = 'Confirm Password'; 
     $lang['userlib_captcha'] = 'Captcha';
     $lang['userlib_register'] = 'Register New Account';
     
     /* Email activation messages */
     $lang['userlib_no_activation'] = 'You may login to your new account immediatly.';
     $lang['userlib_email_activation'] = 'Before you can login to your new account you must first verify that you created it. To do this please follow this link:
     %s
     
     You must do so within the next %s day(s) or your account will be deleted.';
     $lang['userlib_admin_activation'] = 'Before you can login to your new account an administrator must verify it, this may take a few days. Please be patient. You will recive an email informing you when your account has been activated. Untill this time you will not be able to login to the system.';
     
     /* Email subject titles */
     $lang['userlib_email_forgotten_password'] = 'Requested new login infomation';
     $lang['userlib_email_register'] = 'Your new Account has been created';
     
     /* Auth validation messages */
     $lang['userlib_validation_captcha'] = 'The %s is incorrect.';
     $lang['userlib_validation_username'] = 'The %s is aleady in use.';
     $lang['userlib_validation_email'] = 'The %s is already in use.';
     
     /* Status messages */
     $lang['userlib_status_restricted_access'] = "You do not have permission to view the page you requested.";
     $lang['userlib_status_require_login'] = "The page you tried to access requires you to be logged in.";
     $lang['userlib_account_unactivated'] = 'We cannot log you in since your account has not been activated. Please check your email for details how to activate your account.';
     $lang['userlib_login_successfull'] = 'You have been logged in successfully.';  
     $lang['userlib_logout_successfull'] = 'You have been logged out successfully.'; 
     $lang['userlib_login_failed'] = 'We could not log you in, please try again. Check your email and password are correct.';   
     $lang['userlib_logout_failed'] = 'We could not log you out, please try again.';   
     $lang['userlib_email_not_found'] = 'The email provided is not in our database.';
     $lang['userlib_new_password_sent'] = 'A new password has been emailed to you.';
     $lang['userlib_registration_denied'] = 'Registration is not permited with this website.';
     $lang['userlib_registration_failed'] = 'We could not create your user account, please try again.';
     $lang['userlib_registration_success'] = 'Your new account has been created, please check your email for further details';
     $lang['userlib_activation_success'] = 'Your account has been activated, you may now login.';
     $lang['userlib_activation_failed'] = 'We could not activate your account. Please check the url is exactly like in the email and has no spaces in it.'
?>