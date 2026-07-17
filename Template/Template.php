<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : Template Manager
Description : Template Manager for Codeigniter 3
Version : v1.5
*/


class Template {

    protected $CI;
    protected $template;
	var $isAdmin=FALSE;
	var $sidebarPath=FALSE;
	protected $styles=array("link"=>array(),"file"=>array(),"theme"=>array());
	protected $top_script=array("link"=>array(),"file"=>array(),"theme"=>array());
	protected $content_script=array("link"=>array(),"file"=>array(),"theme"=>array());
	protected $bottom_script=array("link"=>array(),"file"=>array(),"theme"=>array());
    protected $includes=array('styles','top_script','content_script','bottom_script');

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->template = $this->CI->config->item('template');
    }

    public function config($data)
    {
        if(isset($data['isAdmin'])){
            $this->isAdmin=$data['isAdmin'];
        }
        if(isset($data['sidebar_path'])){
            $this->sidebarPath=$data['sidebar_path'];
        }
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
        if($type=='page'){
            $data['content'] = $this->CI->load->view($root."$folder/$view", $data, TRUE);
        }
        elseif($type=='auth-default'){
            $data['content'] = $this->CI->load->view("templates/{$this->template}/$folder/$view", $data, TRUE);
            $type='auth';
        }
        else{
            $data['content'] = $this->CI->load->view($root."$folder/$view", $data, TRUE);
        }
        $data['page_type'] = $type;

        if(!empty($this->includes)){
            foreach($this->includes as $include){
                if(!empty($data[$include])){ 
                    $includes=$data[$include];
                    foreach($includes as $key=>$single){
                        if(is_array($single)){
                            foreach($single as $single2){
                                if(array_search($single2,$this->{$include}[$key])===false)
                                    $this->{$include}[$key][]=$single2;
                            }
                        }
                        else{
                            if(array_search($single,$this->{$include}[$key])===false)
                                $this->{$include}[$key][]=$single;
                        }
                    }
                }
            }
        }
		
        if(method_exists($this, 'loadtoastr')){
            $this->loadtoastr();
		}
		if(isset($data['datatable']) && ($data['datatable']===true || $data['datatable']==='export')){
            if(method_exists($this, 'loaddatatable')){
                $this->loaddatatable();
            }
            if($data['datatable']==='export'){
                $data['datatableexport']=true;
            }
		}
		if(isset($data['datatableexport']) && $data['datatableexport']===true){
            if(method_exists($this, 'loaddatatableexport')){
                $this->loaddatatableexport();
            }
		}
		if(isset($data['alertify']) && $data['alertify']===true){
            if(method_exists($this, 'loadalertify')){
                $this->loadalertify();
            }
		}
		if(isset($data['switchery']) && $data['switchery']===true){
            if(method_exists($this, 'loadswitchery')){
                $this->loadswitchery();
            }
		}
        // Load layout
        $layout = "templates/{$this->template}/layout";

        $data['styles']=$this->styles;
		$data['top_script']=$this->top_script;
		$data['content_script']=$this->content_script;
		$data['bottom_script']=$this->bottom_script;
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
        if($name=='sidebar' && $this->sidebarPath!==false){
            $path=$this->sidebarPath;
        }
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