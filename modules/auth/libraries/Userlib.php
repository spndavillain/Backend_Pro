<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
    /**
     * BackendPro
     *
     * A website backend system for developers for PHP 4.3.2 or newer
     *
     * @package			BackendPro
     * @author			Adam Price
     * @copyright		Copyright (c) 2008
     * @license			http://www.gnu.org/licenses/lgpl.html
     */

     // ---------------------------------------------------------------------------

    /**
     * Userlib
     *
     * User authentication library used by BackendPro. Permits
     * protecting controllers/methods from un-authorized access.
     *
     * @package			BackendPro
     * @subpackage		Libraries
     */
	class Userlib
	{
		function Userlib()
		{
			// Get CI Instance
			$this->CI = &get_instance();

			// Load needed files
			$this->CI->load->config('userlib');
			$this->CI->lang->load('userlib');
			$this->CI->load->helper('cookie');
			$this->CI->load->model('user_model');
			$this->CI->load->helper('userlib');
            $this->CI->load->library('validation');  
            $this->CI->load->library('Useremail');
            //$this->CI->load->library('khacl');
            $this->CI->load->helper('string');
            $this->CI->load->helper('Khacl');

			// Initialise the class
			$this->_init();

			log_message('debug','Userlib Class Initialized');
		}

		/**
		 * Initialise User Library
		 *
		 * Several jobs to perform
		 * > Check for autologin
         * > Delete un activated user accounts
		 *
		 * @access private
		 * @return void
		 */
		function _init()
		{
			// Log the user in if autologin details are correct
			if( !$this->is_user())
			{
				if (FALSE !== ($autologin = get_cookie('autologin')))
				{
					// Autologin data exists
					$autologin = unserialize($autologin);

					// Check its valid
					$query = $this->CI->user_model->validateLogin($autologin['email'],$autologin['password']);
					if($query->num_rows() == 1)
					{
						// Log user in
						$this->_set_userlogin($autologin['id']);
                        log_message('debug','Logged user in using autologin cookie');
					}
				}
			}
            
            // Remove any user accounts which have not been activated
            // within the specified deadline
            $query = $this->CI->user_model->delete('Users','DATE_ADD(created,INTERVAL '.$this->CI->preference->item('account_activation_time').' DAY) <= NOW() AND active=0');

			return;
		}
        
        /**
         * Check a user is logged in
         * 
         * @access public
         * @return boolean
         */
        function is_user()
        {
            $CI = & get_instance();
         
             if($CI->session)
             {
                $email = $CI->session->userdata('email');
                $group = $CI->session->userdata('group');
                
                if ($email !== FALSE && $group !== FALSE)
                {
                    // Logged in
                    log_message('debug','User is logged in');
                    return TRUE;
                }
             }
             
             // Not logged in
             log_message('debug','User is not logged in');
             return FALSE;
        }
        
		/**
		 * Check User Permissions
		 *
		 * Check the user has the correct permissions to access the resource
         * If $redirect is TRUE then redirect to login page, otherwise return boolean
		 *
		 * @access public
		 * @return boolean
		 */
		function check($resource,$action = NULL,$redirect = TRUE)
		{
            log_message('debug','Checking if user has access to "'.$resource.'"');
            if ( $this->CI->session ) {
                // Get details from user
                $email = $this->CI->session->userdata('email');
                $group = $this->CI->session->userdata('group');
                
                if ( $email !== FALSE && $group !== FALSE)
                {
                    // There user has a session with values
                    // Lets check there valid                    
                    if (kh_acl_check($group,$resource,$action))
                    {
                        // They can access this resource
                        log_message('debug','Yes they do have access');
                        return TRUE; 
                    }
                }
            }
            
            // DENIED ACCESS  
            log_message('debug','Access is denied for user');          
            if ($redirect)
            {
                if( is_user())
                {
                    // They just don't have access
                    flashMsg('warning',$this->CI->lang->line('userlib_status_restricted_access'));
                    redirect('auth/login','location'); 
                }
                else
                {
                    // They arn't logged in
                    flashMsg('warning',$this->CI->lang->line('userlib_status_require_login'));
                     
                    // Save requested page
                    $this->CI->session->set_flashdata('requested_page',$this->CI->uri->uri_string());
                    redirect('auth/login','location'); 
                }
            }
            return FALSE;
		}
        
        /**
        * Login Form
        * 
        * Display a login form for the user
        * 
        * @access public
        * @param string $container View file container
        * @return void
        */
        function login_form($container)
        {
            // First lets see if they are logged in, if so run action for that user
            if ( $this->is_user() ) {
                // If they have access to the control panel panel send them there
                if( check('Control Panel',NULL,FALSE)) {
                    redirect($this->CI->config->item('userlib_action_admin_login'),'location');
                }
                // Otherwise run user action
                redirect($this->CI->config->item('userlib_action_login'),'location');
            }

            // Setup fields
            $fields['email'] = $this->CI->lang->line('userlib_email');
            $fields['password'] = $this->CI->lang->line('userlib_password');
            $fields['recaptcha_response_field'] = $this->CI->lang->line('userlib_captcha');
            $this->CI->validation->set_fields($fields);
            
            // Set Rules
            // Only run captcha check if needed
            $rules['email'] = 'trim|required|valid_email';
            $rules['password'] = 'trim|required';
            if($this->CI->preference->item('use_login_captcha')){ $rules['recaptcha_response_field'] = 'trim|required|valid_captcha';}
            $this->CI->validation->set_rules($rules);

            if ( $this->CI->validation->run() === FALSE ) {
                // Output any errors
                $this->CI->validation->output_errors();
                
                // Display page
                $data['header'] = $this->CI->lang->line('userlib_login');
                $data['captcha'] = ($this->CI->preference->item('use_login_captcha')?$this->_generate_captcha():'');
                $data['page'] = $this->CI->config->item('backendpro_template_public') . 'form_login';
                $data['module'] = 'auth'; 
                $this->CI->load->view($container,$data);
                
                $this->CI->session->keep_flashdata('requested_page');
            }
            else {
                // Submit form
                $this->_login();
            }
        }
        
        /**
         * Log User In
         *
         * Log the user into the system
         *
         * @access pubic
         * @return void
         */
        function _login()
        {
            $this->CI->load->model('user_model');
                                     
            // Fetch what they entered in the login
            $values['email'] = $this->CI->input->post('email');
            $values['password'] = $this->encode_password($this->CI->input->post('password'));

            // See if a user exists with the given credentials
            $query = $this->CI->user_model->validateLogin($values['email'],$values['password']);
            if ( $query->num_rows() == 1 ) {
                // We we have a valid user
                $user = $query->row();
                
                // Check if the users account hasn't been activated yet
                if ( $user->active == 0 ) {
                    // NEEDS ACTIVATION
                    flashMsg('warning',$this->CI->lang->line('userlib_account_unactivated'));
                    redirect('auth/login','location');
                }
                
                // Everything is OK
                // Save details to session
                $this->_set_userlogin($user->id);

                // If they asked to remember login, store details
                if ( $this->CI->input->post('remember') ) {
                    set_cookie('autologin',
                                       serialize(array('id'=>$user->id, 'email'=>$values['email'], 'password'=>$values['password'])),
                                       $this->CI->preference->item('autologin_period')*86400);
                }

                flashMsg('success',$this->CI->lang->line('userlib_login_successfull'));

                // Redirect to requested page
                if(FALSE !== ($page = $this->CI->session->flashdata('requested_page')))
                {
                    redirect($page,'location');
                }

                // If user has access to control panel
                if ( $this->check('Control Panel',NULL,FALSE)) {
                    redirect($this->CI->config->item('userlib_action_admin_login'),'location');
                }
                redirect($this->CI->config->item('userlib_action_login'),'location');
            }
            else {
                // Login details not valid
                flashMsg('error',$this->CI->lang->line('userlib_login_failed'));
            }
            redirect('auth/login','location');
        }
        
        /**
        * Logout User
        * 
        * Log the user out from the system
        * 
        * @access public
        * @return void
        */
        function logout()
        {
            $this->CI->session->sess_destroy();
            $this->CI->session->sess_create();

            if ( is_user() ) {
                // Failed to log user out
                flashMsg('error',$this->CI->lang->line('userlib_logout_failed'));
                redirect($this->CI->config->item('userlib_action_logout'),'location');
            }

            // Unset autologin variable
            delete_cookie('autologin');

            flashMsg('success',$this->CI->lang->line('userlib_logout_successfull'));
            redirect($this->CI->config->item('userlib_action_logout'),'location');
        }
        
        /**
        * Forgotten Password Form
        * 
        * Display the form for the forgotten password page
        * 
        * @access public
        * @param string $container View file container    
        * @return void
        */
        function forgotten_password_form($container)
        {
            // Setup fields
            $fields['email'] = $this->CI->lang->line('userlib_email');
            $this->CI->validation->set_fields($fields);
            
            // Set Rules
            $rules['email'] = 'trim|required|valid_email';
            $this->CI->validation->set_rules($rules);

            if ( $this->CI->validation->run() === FALSE ) {
                // Output any errors
                $this->CI->validation->output_errors();
                
                // Display page
                $data['header'] = $this->CI->lang->line('userlib_forgotten_password');
                $data['page'] = $this->CI->config->item('backendpro_template_public') . 'form_forgotten_password';
                $data['module'] = 'auth';
                $this->CI->load->view($container,$data);
                
                $this->CI->session->keep_flashdata('requested_page');
            }
            else {
                // Submit form
                $this->_forgotten_password();
            }
        }
        
        /**
        * Forgotten Password
        * 
        * Set a new password for the user
        * 
        * @access public
        * @return void
        */
        function _forgotten_password()
        {
            $email = $this->CI->input->post('email');
            
            if ($this->CI->user_model->validEmail($email))
            {
                // Valid Email
                
                // Generate a new password
                $password = random_string('alnum',$this->CI->preference->item('min_password_length'));
                $encoded_password = $this->encode_password($password);
                
                // Email the new password to the user
                $query = $this->CI->user_model->fetch('Users','username',NULL,array('email'=>$email));
                $user = $query->row();
                $data = array(
                    'username'=>$user->username,
                    'email'=>$email,
                    'password'=>$password,
                    'site_name'=>$this->CI->preference->item('site_name'),
                    'site_url'=>base_url()                
                );
                $this->CI->useremail->send($email,$this->CI->lang->line('userlib_email_forgotten_password'),'public/email_forgotten_password',$data);                
                
                // Update password in database
                $this->CI->user_model->update('Users',array('password'=>$encoded_password),array('email'=>$email));              
                
                flashMsg('success',$this->CI->lang->line('userlib_new_password_sent'));
            }
            else
            {
                // Email not found
                flashMsg('error',$this->CI->lang->line('userlib_email_not_found'));
            } 
            redirect($this->CI->config->item('userlib_action_forgotten_password','location'));   
        }
           
        /**
        * Process registration
        * 
        * Creat the new user accounts for the registered user. When this
        * is called all the data should be valid and no more checks should
        * be needed   
        * 
        * @access private
        * @return void    
        */
        function _register()
        {
            // Build
            $data['users']['username'] = $this->CI->input->post('username');  
            $data['users']['email'] = $this->CI->input->post('email');  
            $data['users']['password'] = $this->encode_password($this->CI->input->post('password'));  
            $data['users']['group'] = $this->CI->preference->item('default_user_group');
            $data['users']['created'] = date("Y-m-d H:i:s",time());               
            
            // Check how the account should be activated
            switch($this->CI->preference->item('activation_method'))
            {
                case 'none':
                    // Send welcome email, account already activated
                    $data['users']['active'] = 1;
                    $activation_message = $this->CI->lang->line('userlib_no_activation');
                break;
                
                case 'admin':
                    // Admin must activate, do nothing
                    $activation_message = $this->CI->lang->line('userlib_admin_activation'); 
                break;
                
                default:
                    // Send email with activation link
                    $data['users']['activation_key'] = random_string('alnum',32);
                    $activation_message = sprintf($this->CI->lang->line('userlib_email_activation'), site_url('auth/activate/'.$data['users']['activation_key']), $this->CI->preference->item('account_activation_time'));
                break;
            } 
              
            $this->CI->db->trans_start();   
            // Add user details to DB
            $this->CI->user_model->insert('Users',$data['users']);
            
            // Get the auto insert ID
            $data['user_profiles']['user_id'] = $this->CI->db->insert_id();
            
            // Add user_profile details to DB
            $this->CI->user_model->insert('UserProfiles',$data['user_profiles']);   
            
            if ($this->CI->db->trans_status() === FALSE)
            {
                // Registration failed
                $this->CI->db->trans_rollback();
                
                flashMsg('error',$this->CI->lang->line('userlib_registration_failed'));
                redirect('auth/register','location'); 
            }
            else
            {
                // User registered
                $this->CI->db->trans_commit();
                
                // Send email to user
                $edata = array(
                    'username'=> $data['users']['username'],
                    'email'=> $data['users']['email'],
                    'password'=> $this->CI->input->post('password'),
                    'activation_message' => $activation_message,
                    'site_name'=>$this->CI->preference->item('site_name'),
                    'site_url'=>base_url()                
                );
                $this->CI->useremail->send($data['users']['email'],$this->CI->lang->line('userlib_email_register'),'public/email_register',$edata);                
            
                flashMsg('success',$this->CI->lang->line('userlib_registration_success')); 
                redirect($this->CI->config->item('userlib_action_register'),'location');
            }
        }
        
        /**
        * Register form
        * 
        * Display the register form to the user
        * 
        * @access public
        * @param string $container View file container    
        * @return void
        */
        function register_form($container)
        {
            if( !$this->CI->preference->item('allow_user_registration'))
            {
                // If registration is not allowed
                flashMsg('info',$this->CI->lang->line('userlib_registration_denied'));
                redirect('auth/login','location');
            }
            
            // Setup fields
            $fields['username'] = $this->CI->lang->line('userlib_username'); 
            $fields['password'] = $this->CI->lang->line('userlib_password');
            $fields['confirm_password'] = $this->CI->lang->line('userlib_confirm_password');
            $fields['email'] = $this->CI->lang->line('userlib_email'); 
            $fields['recaptcha_response_field'] = $this->CI->lang->line('userlib_captcha');
            $this->CI->validation->set_fields($fields);
            
            // Set Rules
            $rules['username'] = 'trim|required|max_length[32]|spare_username'; 
            $rules['password'] = 'trim|required|min_length['.$this->CI->preference->item('min_password_length').']|matches[confirm_password]'; 
            $rules['email'] = 'trim|required|max_length[254]|valid_email|spare_email'; 
            if($this->CI->preference->item('use_registration_captcha')){ $rules['recaptcha_response_field'] = 'trim|required|valid_captcha';}
            $this->CI->validation->set_rules($rules);

            if ( $this->CI->validation->run() === FALSE ) {
                // Output any errors
                $this->CI->validation->output_errors();
                
                // Display page
                $data['header'] = $this->CI->lang->line('userlib_register');
                $data['captcha'] = ($this->CI->preference->item('use_registration_captcha')?$this->_generate_captcha():'');   
                $data['page'] = $this->CI->config->item('backendpro_template_public') . 'form_register';
                $data['module'] = 'auth';
                $this->CI->load->view($container,$data);
            }
            else {
                // Submit form
                $this->_register();
            }
        }
            
        /**
        * Activate User Account
        * 
        * @access public      
        * @return void
        */
        function activate()
        {
            // Fetch code from url
            $key = $this->CI->uri->segment(3);
            
            if( $this->CI->user_model->activateUser($key) )
            {
                // Activation successful             
                flashMsg('success',$this->CI->lang->line('userlib_activation_success'));
                redirect($this->CI->config->item('userlib_action_activation'),'location');
            }
            else
            {
                // Activation failed
                flashMsg('error',$this->CI->lang->line('userlib_activation_failed'));
                redirect('auth/login','location');
            }
        }
                     
        /**
        * Encode Password
        * 
        * Encode the users password using a set method.
        * Use SHA-1 and a salt appended to password
        * 
        * @access public
        * @return string
        */
        function encode_password($string=NULL)
        {
            if($string == NULL)
            {
                return NULL;
            }
            
            // Append the salt to the password
            $string .= $this->CI->config->item('encryption_key');
            
            // Return the SHA-1 encryption
            return sha1($string);
        }
        
        /**
        * Generate Captcha Image
        * 
        * @access private
        * @return string
        */
        function _generate_captcha()
        {
            $this->CI->page->set_asset('shared','css','recaptcha.css');
            $this->CI->load->module_library('recaptcha','Recaptcha');             
            return $this->CI->recaptcha->recaptcha_get_html();
        }
        
        /**
         * Set User Login data
         *
         * When given a user ID it will fetch the required data
         * we need to save and save it to their session
         * 
         * @access private
         * @param integer $user_id User ID of user
         * @return void
         */
        function _set_userlogin($id)
        {
            // Create Users session data
            $user = $this->CI->user_model->getUsers(array('users.id'=>$id));
            $user = $user->row_array();            
            $this->CI->session->set_userdata($user);

            if( !$this->CI->session ) {
                // Could not log user in, something went wrong
                flashMsg('error',$this->CI->lang->line('userlib_login_failed'));

                // Remove autologin value to stop an infinite loop
                delete_cookie('autologin');

                redirect('auth/login','location');
            }

            // Update users last login time
            $this->CI->user_model->updateUserLogin($id);
            return;
        }
	}
?>