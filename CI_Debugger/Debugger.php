<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Name : Debugger
Description : Debugger for Codeigniter 3
Version : v0.0028
*/
class Debugger {
    var $ci;
    var $debug=FALSE;
    var $debugbar=TRUE;
    var $default=TRUE;
    var $current_url='';
    var $var_values=array();
    
    function __construct() {
        $this->ci =& get_instance();
        $this->ci->benchmark->mark("default_start");
        $this->getcurrenturl();
        if(defined('CI_DEBUGGER') && CI_DEBUGGER===TRUE){
            $this->debug=TRUE;
        }
        if($this->ci->session->debugbar===FALSE){
            $debugbar=FALSE;
        }
    }


    function getcurrenturl() {
        if(isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])){
            $this->current_url=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
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
        $style ='<style>';
        $style.= <<<CSS
            body{
                padding-bottom:60px;
            }
            #debugger-toggle-button{
                position: fixed;
                z-index: 100000;
                display: block;
                text-decoration: none;
                left: 0;
                bottom: 5px;
                font-size: 20px;
                padding: 2px 5px;
            }
            #debugger-bottom-bar{
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: #f0f0f0;
                padding: 10px;
                padding-left: 20px;
                border-top: 1px solid #ccc;
                z-index: 9999;
            }
            #debugger-bottom-bar *{
                margin:0 10px;
            }
            #debugger-view-list-btn{
                display: inline-block;
                padding: 0px 5px;
                font-size: 12px;
                font-weight: 500;
                text-align: center;
                text-decoration: none;
                color: #ffffff;
                background-color: #007bff;
                border: 2px solid #007bff;
                border-radius: 5px;
                cursor: pointer;
            }
            #debugger-view-list-btn:hover {
                background-color: #0056b3;
                border-color: #0056b3;
            }
            #debugger-list-container {
                position: fixed;
                bottom: 40px;
                max-height: 150px;
                overflow:auto;
                left: 0;
                width: 100%;
                background-color: #e9e9e9;
                border-top: 1px solid #ccc;
                padding: 0 0 10px 0;
                display: none;
                z-index: 9998;
            }
            #debugger-list-container ul{
                list-style:none;
                padding:5px 0;
            }
            #debugger-list-container ul li{
                padding: 5px 10px;
                background-color: #e9e9e9;
                border-bottom: 1px solid #cdcdcd;
                overflow-wrap: break-word;
            }
            /* Basic styling for the pop-up notification */
            .debugger-popup {
                 visibility: hidden;
                 min-width: 250px;
                 background-color: #333;
                 color: #fff;
                 text-align: center;
                 border-radius: 5px;
                 padding: 16px;
                 position: fixed;
                 z-index: 1;
                 left: 50%;
                 bottom: 30px;
                 transform: translateX(-50%);
                 box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            }
            .debugger-popup.popup-success {
                 background-color: #32bb30;
            }
            .debugger-popup.popup-danger {
                 background-color: #c52424;
            }
            /* Show the pop-up notification */
            .debugger-popup.show {
                 visibility: visible;
                 -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
                 animation: fadein 0.5s, fadeout 0.5s 2.5s;
            }
            /* Animation for fading in */
            @-webkit-keyframes fadein {
                 from {
                    bottom: 0;
                     opacity: 0;
                }
                 to {
                    bottom: 30px;
                     opacity: 1;
                }
            }
            @keyframes fadein {
                 from {
                    bottom: 0;
                     opacity: 0;
                }
                 to {
                    bottom: 30px;
                     opacity: 1;
                }
            }
            /* Animation for fading out */
            @-webkit-keyframes fadeout {
                 from {
                    bottom: 30px;
                     opacity: 1;
                }
                 to {
                    bottom: 0;
                     opacity: 0;
                }
            }
            @keyframes fadeout {
                 from {
                    bottom: 30px;
                     opacity: 1;
                }
                 to {
                    bottom: 0;
                     opacity: 0;
                }
            }
CSS;
        $style.='</style>';
        $style.='<style type="text/css" media="print">';
        $style.= <<<CSS
            @media print{
                #debugger-bottom-bar,
                #debugger-toggle-button{
                    display:none;
                }
            }
CSS;
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
        $bottombar.='<span id="stay-time"></span>';
        $bottombar.='<span><button id="debugger-view-list-btn">View Resources</button></span>';
        $bottombar.='<a href="#" onClick="window.location.reload(); return false;">&#11119; Reload Page</a>';
        $bottombar.='<a href="#" onClick="if (typeof reloadAjax === \'function\') { reloadAjax(); } else { console.log(\'Function does not exist.\');showPopup(\'Function does not exist.\',\'error\'); } return false;">&#11119; Reload Last Request</a>';
        $bottombar.='<a href="'.base_url('pull.php').'" target="_blank">&#8681; Pull Files</a>';
        $bottombar.='<a href="#" onClick="window.print(); return false;">&#9113; Print Page</a>';
        $bottombar.='<a href="view-source:'.$this->current_url.'">&#9113; View Page Source</a>';
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
                        if(href.indexOf("'.base_url().'")!=-1 && href.indexOf("#")==-1){
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
        
        $script.="let seconds = 0;
        
                    // Function to update the timer
                    function updateTimer() {
                        seconds++;
                        document.getElementById('stay-time').innerText = 'Time Elapsed: '+seconds+' seconds';
                    }

                    // Call the updateTimer function every 1 second (1000 milliseconds)
                    setInterval(updateTimer, 1000);";
        
        $script.="function countResources() {
                    var ul = listContainer.querySelector('ul');
                    var liCount=ul.getElementsByTagName('li').length;
                    document.getElementById('debugger-view-list-btn').innerText='View Resources ('+liCount+')';
                }";
        
        $script.="// Function to create and show the pop-up notification
                    function showPopup(message,status) {
                        // Create the pop-up element
                        var popup = document.createElement('div');
                        popup.className = 'debugger-popup';
                        popup.innerHTML = message;

                        // Append the pop-up to the body
                        document.body.appendChild(popup);

                        // Add the show class to make it visible
                        popup.classList.add('show');
                        
                        if(status=='error'){
                            popup.classList.add('popup-danger');
                        }
                        else{
                            popup.classList.add('popup-success');
                        }

                        // Remove the pop-up after the animation completes
                        setTimeout(function() {
                            popup.remove();
                        }, 3000); // 3 seconds
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
