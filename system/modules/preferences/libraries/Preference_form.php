<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
    /**
     * BackendPro
     *
     * A website backend system for developers for PHP 4.3.2 or newer
     *
     * @package         BackendPro
     * @author          Adam Price
     * @copyright       Copyright (c) 2008
     * @license         http://www.gnu.org/licenses/lgpl.html
     * @tutorial        BackendPro.pkg
     */

     // ---------------------------------------------------------------------------

    /**
     * Preference
     *
     * Allows the creation of a preference page for a controller
     *
     * @package         BackendPro
     * @subpackage      Libraries
     * @tutorial        Preference_form.cls
     */
    class Preference_form
    {
        var $form_name = 'Preferences';     // Default name of form
        var $form_link = '';                // ???????
        var $field = array();               // Array containing all field infomation
        var $group = array();               // Array containing all field groups
        var $defaults = array(              // Default array containing mappings from field types => default rule
            'text'     => 'trim',
            'textarea' => 'trim'
        );
        
        /**
         * Constructor
         */
        function Preference_form($config=array())
        {
            // Get CI Instance
            $this->CI = &get_instance();

            // Initalize Class
            if(count($config) != 0)
            {
                $this->initalize($config);
            }
            
            // Get form base url
            $this->_get_base_url();
            
            // Load language files
            $this->CI->lang->load('preferences');

            log_message('debug','Preference_form Class Initialized');
        }
        
        /**
         * Initalize Class
         * 
         * @access public
         * @param array $config Config array
         * @return void;
         */
        function initalize($config=array())
        {
            foreach($config as $key => $value)
            {
                $this->{$key} = $value;
            }
            return;
        }
        
        /**
         * Get Controller Base URL
         * 
         * @access private
         * @return void 
         */
        function _get_base_url()
        {
            $this->form_link .= $this->CI->uri->slash_segment(1);  // Folder
            $this->form_link .= $this->CI->uri->slash_segment(2);  // Controller  
            
            if($this->CI->uri->segment(3) != NULL){
                $this->form_link .= $this->CI->uri->segment(3);  // Method
            } else {
                $this->form_link .= 'index';
            }          
        }
        
        /**
         * Setup fields
         * 
         * Make sure that each field has a label, type, rule & param array
         * 
         * @access private
         * @return void 
         */
        function _setup_fields()
        {
            // Make sure every field in $group has a $field entry
            foreach($this->group as $key => $value)
            {
                foreach(explode(',',$value['fields']) as $name)
                {
                    if( !isset($this->field[$name]))
                        $this->field[$name] = array();
                }
            }
            
            foreach($this->field as $field => $data)
            {
                // Assign default label name
                if ( FALSE !== ($label = $this->CI->lang->line('preference_label_'.$field))) {
                    $this->field[$field]['label'] = $label;
                } else {
                    $this->field[$field]['label'] = ucwords(ereg_replace('_',' ',$field));
                }

                // Check a type is given, if not set it to 'text'
                if ( ! isset($this->field[$field]['type']))
                    $this->field[$field]['type'] = 'text';

                // Check a rule exists, if not set the default rule for that type
                if ( ! isset($this->field[$field]['rules']))
                    $this->field[$field]['rules'] = ( isset($this->defaults[$this->field[$field]['type']])) ? $this->defaults[$this->field[$field]['type']] : "";

                // Check a parms array exists
                if ( ! isset($this->field[$field]['params']))
                    $this->field[$field]['params'] = array();
            }
        }
        
        /**
         * Display Preference Form
         * 
         * Display either the field groupings menu
         * OR the actual preference form.
         * 
         * @access public
         * @param boolean $print Whether to return output or print it
         * @return mixed
         */
        function display($print=FALSE)
        {
            // Set breadcrumb
            //$this->CI->page->set_crumb($this->form_name,$this->form_link);
            
            // Setup fields
            $this->_setup_fields();
            
            $this->CI->page->set_crumb($this->form_name,$this->form_link);
            if(count($this->group) != 0)
            {
                $group_id = $this->CI->uri->segment(4);
                if($group_id)
                {
                    // Display group fields                    
                    $this->CI->page->set_crumb($this->group[$group_id]['name'],$this->form_link."/".$group_id);
                    return $this->_display_fields($print, $group_id);
                }
                else
                {                
                    // Display group listings
                    $data['group'] = $this->group; 
                    $data['form_link'] = $this->form_link;
                    $data['header'] = $this->form_name;      
                    return $this->CI->load->view("field_groups",$data, !$print);
                }
            }
            else
            {
                // Display fields
                return $this->_display_fields($print);
            }   
        }
        
        /**
         * Display fields
         * 
         * Display the form to edit the requested fields
         * 
         * @access private
         * @param boolean $print Whether to return output or to print it
         * @param string $group_id Group id
         * @return mixed
         */
        function _display_fields($print,$group_id=NULL)
        {
            if($group_id != NULL)
            {
                // Only show group fields
                foreach(explode(',',$this->group[$group_id]['fields']) as $key)
                {
                    $key = trim($key);
                    $fields_to_show[$key] = $this->field[$key];
                }
            }
            else
            {
                // Show all fields
                $fields_to_show = $this->field;
            }
            
            // Setup form validation
            $this->CI->load->library('validation');
            foreach($fields_to_show as $key => $value)
            {
                $form_fields[$key] = "'".$value['label']."'";
                $form_rules[$key] = $value['rules'];
            }
            $this->CI->validation->set_fields($form_fields);
            $this->CI->validation->set_rules($form_rules);

            // If this is the first load, get preference values from the DB
            if( ! $this->CI->input->post('submit'))
            {
                foreach($fields_to_show as $key => $value)
                {
                    $this->CI->validation->{$key} = $this->CI->preference->item($key);
                }
            }
            
            if ($this->CI->validation->run() === FALSE)
            {
                // SHOW FORM
                // Show validation errors
                $this->CI->validation->output_errors();

                // Create the fields input and pass the data into an array ready for viewing
                foreach($fields_to_show as $key => $value)
                {
                    // Call function for the given field type
                    $this->field[$key]['input'] = call_user_func(array(&$this,"_field_".$this->field[$key]['type']),$key);
                    // Pass field data over to view
                    $data['field'][$key] = $this->field[$key];
                }
                        
                // Display Page
                $data['header'] = ($group_id == NULL) ? $this->form_name : $this->group[$group_id]['name'];
                $data['form_link'] = $this->form_link . "/" . $group_id;
                return $this->CI->load->view("form_preference_fields",$data,!$print);
            }
            else
            {
                // SAVE FORM CONTENTS
                foreach($fields_to_show as $key=>$value)
                {
                    $this->CI->preference->set_item($key,$this->CI->input->post($key));
                }

                // Show success message and redirect
                flashMsg('success',sprintf($this->CI->lang->line('backendpro_saved'),$this->form_name));
                redirect($this->form_link);
            }
        }

        /**
         *        HTML FIELD CREATION FUNCTIONS
         *
         *     Below here are all functions used to create the inputs of the form
         *     there is one for each type of form field supported
         */

        function _field_text($key)
        {
            $params = $this->field[$key]['params'];
            $params['name'] = $key;
            $params['id'] = $key;
            $params['class'] = 'text';
            $params['value'] = $this->CI->validation->{$key};
            return form_input($params);
        }

        function _field_boolean($key)
        {
            $params = $this->field[$key]['params'];
            $params['name'] = $key;
            $params['id'] = $key;
            $params['value'] = 1;

            // Set checked status
            if ($this->CI->validation->{$key}){$params['checked'] = TRUE;}
            $field = "Yes " . form_radio($params);

            // Set checked status
            if ( ! $this->CI->validation->{$key}){$params['checked'] = TRUE;} else {$params['checked'] = FALSE;}
            $params['value'] = 0;
            $field .= " No " . form_radio($params);
            return $field;
        }

        function _field_dropdown($key)
        {
            $options = $this->field[$key]['params']['options'];
            unset($this->field[$key]['params']['options']);            
            return form_dropdown($key,$options,$this->CI->validation->{$key},$this->field[$key]['params']);
        }

        function _field_textarea($key)
        {
            $params = $this->field[$key]['params'];
            $params['name'] = $key;
            $params['id'] = $key;
            $params['value'] = $this->CI->validation->{$key};
            return form_textarea($params);
        }
    }
?>