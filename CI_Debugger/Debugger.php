<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Name : Debugger
Description : Debugger for Codeigniter
Version : v0.0003
*/
class Debugger {
    var $ci;
    var $debug=FALSE;

    function __construct() {
        $this->ci =& get_instance();
        $this->ci->benchmark->mark("default_start");
        if(defined('CI_DEBUGGER') && CI_DEBUGGER===TRUE){
            $this->debug=TRUE;
        }
    }


    function getelapsedtime($start="default_start",$end="default_end") {
        if($end=="default_end"){
            $this->ci->benchmark->mark("default_end");
        }
        return $this->ci->benchmark->elapsed_time($start, $end);
    }

    function getmemoryusage($precision=2) {
        $usage = memory_get_usage(true);
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $usage = max($usage, 0);
        $pow = floor(($usage ? log($usage) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $usage /= (1 << (10 * $pow));

        return round($usage, $precision) . ' ' . $units[$pow];
    }

    function printdata($array,$pre=true,$die=false) {
        if($pre===true){ echo "<pre>"; }
        print_r($array);
        if($pre===true){ echo "</pre>"; }
        if($die===true){ die; }
    }

    function getdebuggerbarstyle() {
        $style='<style>';
        $style.='body{';
        $style.='padding-bottom:50px;';
        $style.='}';
        $style.='#debugger-toggle-button{';
        $style.='position: fixed;';
        $style.='z-index: 100000;';
        $style.='display: block;';
        $style.='text-decoration: none;';
        $style.='left: 0;';
        $style.='bottom: 5px;';
        $style.='font-size: 20px;';
        $style.='padding: 2px 5px;';
        $style.='}';
        $style.='#debugger-bottom-bar{';
        $style.='position: fixed;';
        $style.='bottom: 0;';
        $style.='left: 0;';
        $style.='width: 100%;';
        $style.='background-color: #f0f0f0;';
        $style.='padding: 10px;';
        $style.='padding-left: 20px;';
        $style.='border-top: 1px solid #ccc;';
        $style.='z-index: 9999;';
        $style.='}';
        $style.='#debugger-bottom-bar *{';
        $style.='margin:0 10px;';
        $style.='}';
        $style.='</style>';
        echo $style;
    }

    function getdebuggerbar() {
        $this->getdebuggerbarstyle();
        //$variable_names = array_keys($GLOBALS);
        //print_pre($variable_names);
        //print_pre($GLOBALS,true);
        $bottombar='<a href="#" onClick="return toggleBottomBar()" id="debugger-toggle-button">&#11206</a>';
        $bottombar.='<div  id="debugger-bottom-bar">';
        $bottombar.='<span>Memory Usage: '.$this->getmemoryusage().'</span>';
        $bottombar.='<span>Execution Time: '.$this->getelapsedtime().' Seconds</span>';
        $bottombar.='<a href="#" onClick="window.location.reload(); return false;">&#11119 Reload Page</a>';
        $bottombar.='</div>';
        echo $bottombar;
        
        $this->getdebuggerbarscript();
    }

    function getdebuggerbarscript() {
        $script='<script>
            function toggleBottomBar() {
                var bottomBar = document.getElementById("debugger-bottom-bar");
                if (bottomBar.style.display === "none") {
                    bottomBar.style.display = "block";
                    document.getElementById("debugger-toggle-button").innerHTML="&#11206";
                } else {
                    bottomBar.style.display = "none";
                    document.getElementById("debugger-toggle-button").innerHTML="&#11205;";
                }
                return false;
            }';
        if($this->ci->input->get('debug')=='debugbar' && $this->debug==TRUE){
            $script.='var anchors=document.getElementsByTagName("a");';
            $script.='var queryParam="debug=debugbar";';
            $script.='for (var i = 0; i < anchors.length; i++) {
                        var href=anchors[i].href;
                        if(href.indexOf("'.base_url().'")!=-1){
                            console.log(anchors[i].innerText);
                            var separator = href.indexOf("?") === -1 ? "?" : "&";

                            // Update href attribute with the added query parameter
                            anchors[i].setAttribute("href", href + separator + queryParam);
                        }
                    }';
        }
        $script.='</script>';
        echo $script;
    }

    function checkDebugStatus() {
        $status=FALSE;
        if($this->ci->input->is_ajax_request()){
            $status=FALSE;
        }
        else{
            if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']=='localhost'){
                $status=TRUE;
            }
            if($this->ci->input->get('debug')=='debugbar' && $this->debug==TRUE){
                $status=TRUE;
            }
        }
        return $status;
    }

    function __destruct() {
        if($this->checkDebugStatus()){
            $this->getdebuggerbar();
        }
    }

}
