<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : Template Manager
Description : Template Manager for Codeigniter 3
Version : v1.0
*/


class Template {

    protected $CI;
    protected $template;
	var $isAdmin=FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->template = $this->CI->config->item('template');
    }

    /**
     * Load full page with layout
     */
    public function load($folder, $view, $data = [],$type="page", $return = FALSE){
        $root='';
        if($this->isAdmin){
            $root='admin/';
        }
        // Page content
        $data['content'] = $this->CI->load->view($root."$folder/$view", $data, TRUE);
        $data['page_type'] = $type;

        // Load layout
        $layout = "templates/{$this->template}/layout";

        if ($return) {
            return $this->CI->load->view($layout, $data, TRUE);
        } else {
            $this->CI->load->view($layout, $data);
        }
    }

    /**
     * Load partials (header, footer, navbar, etc.)
     */
    public function partial($name, $data = [], $return = FALSE)
    {
        $path = "templates/{$this->template}/partials/$name";

        // Fallback to default if not found
        if (!file_exists(APPPATH . "views/$path.php")) {
            $path = "templates/default/partials/$name";
        }

        if ($return) {
            return $this->CI->load->view($path, $data, TRUE);
        } else {
            $this->CI->load->view($path, $data);
        }
    }

    /**
     * Get current template name
     */
    public function getTemplate()
    {
        return $this->template;
    }
}