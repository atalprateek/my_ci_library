<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Name : Debugger
Description : Debugger for Codeigniter 3
Version : v0.0020
*/
class Debugger {
    var $ci;
    var $debug=FALSE;
    var $debugbar=TRUE;
    var $default=TRUE;
    var $var_values=array();
    
    function __construct() {
        $this->ci =& get_instance();
        $this->ci->benchmark->mark("default_start");
        if(defined('CI_DEBUGGER') && CI_DEBUGGER===TRUE){
            $this->debug=TRUE;
        }
        if($this->ci->session->debugbar===FALSE){
            $debugbar=FALSE;
        }
    }


    function getelapsedtime($start="default_start",$end="default_end") {
        if($end=="default_end"){
            $this->ci->benchmark->mark("default_end");
        }
        $time=$this->ci->benchmark->elapsed_time($start, $end);
        $time=$time<1?($time*1000).' millseconds':$time.' seconds';
        return $time;
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

    function trackValue($name, $value) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $line_number = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 'Unknown';
        $this->var_values[] = array('name' => $name, 'value' => $value, 'line' => $line_number);
    }
    
    function viewvariables($array=array()) {
        $result=array();
        if(!empty($this->var_values)){
            if(empty($array)){
                $result=$this->var_values;
            }
            elseif(is_array($array)){
                foreach($this->var_values as $values){
                    if(in_array($values['name'],$array)){
                        $result[]=$values;
                    }
                }
            }
            elseif(!is_array($array)){
                foreach($this->var_values as $values){
                    if($values['name']==$array){
                        $result[]=$values;
                    }
                }
            }
        }
        return $result;
    }
    
    function printdata($array,$die=false,$pre=true) {
        if($pre===true){ echo "<pre>"; }
        print_r($array);
        if($pre===true){ echo "</pre>"; }
        if($die===true){ die; }
    }

    function getdebuggerbarstyle() {
        $style='<style>';
        $style.='body{';
        $style.='padding-bottom:60px;';
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
        $style.='#debugger-view-list-btn{';
        $style.='display: inline-block;';
        $style.='padding: 0px 5px;';
        $style.='font-size: 12px;';
        $style.='font-weight: 500;';
        $style.='text-align: center;';
        $style.='text-decoration: none;';
        $style.='color: #ffffff;';
        $style.='background-color: #007bff;';
        $style.='border: 2px solid #007bff;';
        $style.='border-radius: 5px;';
        $style.='cursor: pointer;';
        $style.='}';
        $style.='#debugger-view-list-btn:hover {';
        $style.='background-color: #0056b3;';
        $style.='border-color: #0056b3;';
        $style.='}';
        $style.='#debugger-list-container {';
        $style.='position: fixed;';
        $style.='bottom: 40px;';
        $style.='max-height: 150px;';
        $style.='overflow:auto;';
        $style.='left: 0;';
        $style.='width: 100%;';
        $style.='background-color: #e9e9e9;';
        $style.='border-top: 1px solid #ccc;';
        $style.='padding: 0 0 10px 0;';
        $style.='display: none;';
        $style.='z-index: 9998;';
        $style.='}';
        $style.='#debugger-list-container ul{';
        $style.='list-style:none;';
        $style.='padding:5px 0;';
        $style.='}';
        $style.='#debugger-list-container ul li{';
        $style.='padding: 5px 10px;';
        $style.='background-color: #e9e9e9;';
        $style.='border-bottom: 1px solid #cdcdcd;';
        $style.='}';
        $style.='</style>';
        $style.='<style type="text/css" media="print">';
        $style.='#debugger-bottom-bar,';
        $style.='#debugger-toggle-button{';
        $style.='display:none;';
        $style.='}';
        $style.='</style>';
        echo $style;
    }

    function getdebuggerbar() {
        $this->getdebuggerbarstyle();
        //$variable_names = array_keys($GLOBALS);
        //print_pre($variable_names);
        //print_pre($GLOBALS,true);
        $bottombar ='<a href="#" onClick="return toggleBottomBar()" id="debugger-toggle-button">';
        
        if($this->debugbar===FALSE){ $bottombar.='&#11205'; }
        else{ $bottombar.='&#11206'; }
        
        $bottombar.='</a>';
        $bottombar.='<div  id="debugger-bottom-bar" ';
        if($this->debugbar===FALSE){
            $bottombar.='style="display:none;"';
        }
        $bottombar.='>';
        $bottombar.='<span>Memory Usage: '.$this->getmemoryusage().'</span>';
        $bottombar.='<span>Execution Time: '.$this->getelapsedtime().'</span>';
        $bottombar.='<span id="debugger-page-load-time"></span>';
        $bottombar.='<span><button id="debugger-view-list-btn">View Resources</button></span>';
        $bottombar.='<a href="#" onClick="window.location.reload(); return false;">&#11119 Reload Page</a>';
        $bottombar.='<a href="'.base_url('pull.php').'" target="_blank">&#8681; Pull Files</a>';
        $bottombar.='<a href="#" onClick="window.print(); return false;">&#9113; Print Page</a>';
        //$bottombar.='<a href="#" onClick="clearCacheAndReload(); return false;">&#11119 Clear Cache &amp; Reload</a>';
        $bottombar.='</div>';
        
        $listdiv='<div id="debugger-list-container" style="display:none;"></div>';
        
        echo $listdiv;
        echo $bottombar;
        
        $this->getdebuggerbarscript();
    }

    function getdebuggerbarscript() {
        $script='<script>
            var listContainer = document.getElementById("debugger-list-container");
            function toggleBottomBar() {
                var bottomBar = document.getElementById("debugger-bottom-bar");
                var toggleButton = document.getElementById("debugger-toggle-button");

                // Check if bottomBar is currently visible
                var isBottomBarVisible = bottomBar.style.display === "block";

                // Toggle visibility of bottomBar
                bottomBar.style.display = isBottomBarVisible ? "none" : "block";

                // Toggle visibility of listContainer if needed
                if (isBottomBarVisible) {
                    listContainer.style.display = "none";
                }

                // Update toggleButton icon based on visibility
                toggleButton.innerHTML = isBottomBarVisible ? "&#11205;" : "&#11206;";

                // Save the state to localStorage
                localStorage.setItem("debugBarVisible", !isBottomBarVisible);

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
        
        $script.="window.addEventListener('load', function() {
                        var resources = window.performance.getEntriesByType('resource');
                        var ul = document.createElement('ul');

                        resources.forEach(function(resource) {
                            var li = document.createElement('li');
                            li.textContent = resource.name+' : '+resource.duration+' milliseconds';
                            ul.appendChild(li);
                        });
                        listContainer.appendChild(ul);
                        countResources();
                        var loadTime = window.performance.timing.domContentLoadedEventEnd - window.performance.timing.navigationStart;
                        if(loadTime<1000){
                            var plTime=loadTime+' milliseconds';
                        }
                        else{
                            var plTime=(loadTime/1000)+' seconds';
                        }
                        document.getElementById('debugger-page-load-time').innerText='Page Load Time: '+plTime;";
            $script.="
                        // Attach a function to the ajaxSend event handler
                        $(document).ajaxSend(function(event, jqXHR, ajaxOptions) {
                            var li = document.createElement('li');
                            li.textContent = 'AJAX request started:'+ajaxOptions.type+' : '+ajaxOptions.url;
                            var ul = listContainer.querySelector('ul');
                            ul.appendChild(li);
                            countResources();
                        });

                        // Attach a function to the ajaxComplete event handler
                        $(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {
                            var li = document.createElement('li');
                            li.textContent = 'AJAX request completed:'+ajaxOptions.type+' : '+ajaxOptions.url;
                            var ul = listContainer.querySelector('ul');
                            ul.appendChild(li);
                            countResources();
                        });

                        // Attach a function to the ajaxError event handler
                        $(document).ajaxError(function(event, jqXHR, ajaxOptions, thrownError) {
                            var li = document.createElement('li');
                            li.textContent = 'AJAX request failed:'+ajaxOptions.type+' : '+ajaxOptions.url+' : '+thrownError;
                            var ul = listContainer.querySelector('ul');
                            ul.appendChild(li);
                            countResources();
                        });";
        
        $script.="});";
        
        $script.="document.addEventListener('DOMContentLoaded', function() {
        
                    var isDebugBarVisible = localStorage.getItem('debugBarVisible') === 'true';
                    var bottomBar = document.getElementById('debugger-bottom-bar');
                    var toggleButton = document.getElementById('debugger-toggle-button');

                    if (isDebugBarVisible) {
                        bottomBar.style.display = 'block';
                        toggleButton.innerHTML = '&#11206;';
                    } else {
                        bottomBar.style.display = 'none';
                        toggleButton.innerHTML = '&#11205;';
                    }
                    var viewListBtn = document.getElementById('debugger-view-list-btn');

                    viewListBtn.addEventListener('click', function() {
                      if (listContainer.style.display === 'none') {
                        listContainer.style.display = 'block';
                      } else {
                        listContainer.style.display = 'none';
                      }
                    });
                });";
        
        $script.="function clearCacheAndReload() {
                    document.cookie = 'no-cache=' + Date.now() + '; path=/';
                    location.reload();
                }";
        
        $script.="function countResources() {
                    var ul = listContainer.querySelector('ul');
                    var liCount=ul.getElementsByTagName('li').length;
                    document.getElementById('debugger-view-list-btn').innerText='View Resources ('+liCount+')';
                }";
        
        $script.='</script>';
        echo $script;
    }

    function updateDefaultStatus($status=false) {
        $this->default=$status;
    }

    function checkDebugStatus() {
        $status=FALSE;
        if($this->ci->input->is_ajax_request() || 
           (isset($_SERVER['HTTP_API_HEADER']) && $_SERVER['HTTP_API_HEADER']=='API Tester')){
            $status=FALSE;
        }
        else{
            if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']=='localhost' && $this->debug === TRUE){
                $status=TRUE;
            }
            if($this->ci->input->get('debug')=='debugbar' && $this->debug===TRUE){
                $status=TRUE;
            }
        }
        return $status;
    }

    function __destruct() {
        if($this->checkDebugStatus() && $this->default===TRUE){
            $this->getdebuggerbar();
        }
    }

}
