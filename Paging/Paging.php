<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : Paging
Description : Custom Pagination
Version : v1.11
*/


class Paging {
	protected $CI;
	
	protected $url;
	
	protected $num_links=2;
	
	protected $count=10;
	
	protected $pages;
	
	protected $page;
    
	protected $page_type='uri';
    
	protected $total_data;
	
	protected $display_links=array('pages','prevnext');
	
	protected $display_type="individual";
	
	protected $prevnext=array("prev"=>"&lt; Prev","next"=>"Next &gt;");
	
	protected $prevnextstatus="hide";
	
	protected $skip=array("num"=>5,"skip_prev"=>"&lt; Skip 5","skip_next"=>"Skip 5 &gt;");
	
	protected $firstlast=array("first"=>"&lsaquo; First","last"=>"Last &rsaquo;");
	
	protected $pagefilters=array();
	
	protected $ul_class=array("pagination");
	
	protected $num_class=array();
	
	protected $li_class=array();
	
	protected $link_class=array();
	
	protected $page_size=false;

	protected $style=false;
    
    protected $sizes=array(10=>10,25=>25,50=>50,100=>100);
	
	protected $config=false;

	// We'll use a constructor, as you can't directly call a function
	// from a property definition.
	public function __construct()
	{
			// Assign the CodeIgniter super-object
			$this->CI =& get_instance();
	}
	
	public function initialize($config=array()){
		$this->config=false;
		if(!empty($config)){
			if(!isset($config['url'])){
				echo "Please add Page URL!";
				return false;
			}
			if(!isset($config['page'])){
				echo "Please add Current Page!";
				return false;
			}
			if(isset($config['count']) && $config['count']==0){
                unset($config['count']);
			}
			foreach ($config as $key => $val){
				if (property_exists($this, $key)){
					if(is_array($val) && $key!='sizes'){
						foreach($val as $key2=>$val2){
							$this->{$key}[$key2]= $val2;
						}
					}
					else{
						$this->$key = $val;
					}
				}
			}
			$this->config=true;
            if($this->page_size===true){
                $this->count=($this->CI->input->get('page_size')!==NULL)?$this->CI->input->get('page_size'):$this->count;
                if(!empty($this->CI->input->get('page_size')) && !isset($this->pagefilters['page_size'])){
                    $this->pagefilters['page_size']=$this->CI->input->get('page_size');
                }
            }
		}
		else{
			echo "Please add Paging Configuration!";
			return false;
		}
	}
	
	public function paginationstyle(){
		$style=<<<HTML
				<style>
					/* ----------------pagination start----------------------- */

					.pagination-div .pagination {
						justify-content: end;
					}
					.pagination-div .active > .page-link,
					.pagination-div .page-link.active {
						background-color: #3c46cf;
						border-color: transparent;
						border-radius: 0;
						color: #fff;
					}
					.pagination-div .page-item:last-child .page-link,
					.pagination-div .page-item:first-child .page-link {
						border-radius: 0 !important;
					}
					.pagination-div .page-link {
						color: #000;
						font-size: 0.8rem;
					}
					.pagination-div .page-link:focus {
						box-shadow: none;
					}
					.pagination-div .page-link {
						border: 0 !important;
					}
					.pagination-div {
						/* position: relative; */
					}
					.pagination-div .pagination-info {
						/* top: 28px;
						left: 105px;
						position: absolute; */
						font-size: 0.8rem;
					}
					.pagination-div .form-control {
						appearance: auto !important;
					}
					.pagination-div {
						background: #f6f6f8;
						padding: 20px;
						display: flex;
						min-height: 60px;
						justify-content: space-between;
					}
					.pagination-div .pagination {
						margin-bottom: 0;
						--bs-pagination-bg: #f6f7f8 !important;
					}
					.pagination-div .showpage {
						display: flex;
					}
					.showpage select{
						padding: 0!important;
					}
					.pagination-div .pagination-info {
						margin-left: 4px;
						padding: 8px;
					}
					.pagination-div .pagination li {
						margin-right: 4px;
					}
					.pagination-div .showpage .form-control {
						border-radius: 0;
					}
					/* ------------pagintaion end----------------------------- */
				</style>
HTML;
					return $style;
	}
	
	public function pagedata($array){
		$offset=($this->page-1);
        $pagedata=array();
        $this->total_data=!empty($array)?count($array):0;
        if(is_numeric($this->count)){
            if(!empty($array)){
                $data = array_chunk($array,$this->count);
                $this->pages=ceil($this->total_data/$this->count);
                if(isset($data[$offset])){
                    $pagedata=$data[$offset];
                }
            }
        }
        elseif($this->count=='All'){
            $pagedata=$array;
        }
        $result=['pagedata'=>$pagedata];
		$pagination=$this->pagination();
		if($this->style){
			$pagination=$this->paginationstyle().$pagination;
		}
        $result['pagination']=$pagination;
        //echo PRE;print_r($array);die;
        return $result;
	}
	
	public function pagination(){
		if($this->config){
			$pagination="";
			if($this->pages>1){
				if($this->display_type=="individual"){
					$pagination="";
				}
				else{
					$pagination="<ul ";
					if(is_array($this->ul_class))
						$pagination.="class='".implode(" ",$this->ul_class)."'";
					$pagination.=">";
				}
				$current=false;
			
				if(array_search("firstlast",$this->display_links)!==false && $this->page!=1){
					$pagination.=$this->createpagelinks(1,$this->firstlast['first'],false,$this->li_class,$this->link_class);
				}
				if(array_search("skip",$this->display_links)!==false && $this->page-$this->skip['num']>0){
					$pagination.=$this->createpagelinks($this->page-$this->skip['num'],$this->skip['skip_prev'],false,$this->li_class,$this->link_class);
				}
				if(array_search("prevnext",$this->display_links)!==false){
                    if($this->page==1 && $this->prevnextstatus=='hide'){
                    }
                    elseif($this->page==1 && $this->prevnextstatus=='disabled'){
                        $prev_class=$this->link_class;
                        $prev_class[]="disabled";
                        $pagination.=$this->createpagelinks($this->page-1,$this->prevnext['prev'],false,$this->li_class,$prev_class);
                    }
                    else{
                        $pagination.=$this->createpagelinks($this->page-1,$this->prevnext['prev'],false,$this->li_class,$this->link_class);
                    }
				}
				if(array_search("pages",$this->display_links)!==false){
					for($i=1;$i<=$this->pages;$i++){
						if($i<=$this->num_links || $i>$this->pages-$this->num_links || ($i>$this->page-$this->num_links && $i<$this->page+$this->num_links)){
							if($i==$this->page){ $current=true; }else{ $current=false; }
							$pagination.=$this->createpagelinks($i,$i,$current,$this->li_class,$this->num_class);
						}
						elseif($i==$this->num_links+1 || $i==$this->pages-$this->num_links){
							$pagination.=$this->createpagelinks("","...",$current,$this->li_class,$this->num_class);
						}
					}
				}
				if(array_search("prevnext",$this->display_links)!==false){
                    if($this->page==$this->pages && $this->prevnextstatus=='hide'){
                    }
                    elseif($this->page==$this->pages && $this->prevnextstatus=='disabled'){
                        $next_class=$this->link_class;
                        $next_class[]="disabled";
                        $pagination.=$this->createpagelinks(NULL,$this->prevnext['next'],false,$this->li_class,$next_class);
                    }
                    else{
                        $pagination.=$this->createpagelinks($this->page+1,$this->prevnext['next'],false,$this->li_class,$this->link_class);
                    }
				}
				if(array_search("skip",$this->display_links)!==false && $this->pages-$this->page>=$this->skip['num']){
					$pagination.=$this->createpagelinks($this->page+$this->skip['num'],$this->skip['skip_next'],false,$this->li_class,$this->link_class);
				}
				if(array_search("firstlast",$this->display_links)!==false && $this->page!=$this->pages){
					$pagination.=$this->createpagelinks($this->pages,$this->firstlast['last'],false,$this->li_class,$this->link_class);
				}
				if($this->display_type!="individual"){
					$pagination.="</ul>";
				}
			}
            $showing="Showing ";
            if(is_numeric($this->count)){
                $offset=($this->page-1)*$this->count;
                $from=!empty($this->total_data)?($offset+1):0;
                $to=(($offset+$this->count)>$this->total_data)?$this->total_data:($offset+$this->count);
                $showing.=$from.' to '.$to.' of '.$this->total_data;
            }
            elseif($this->count=='All'){
                $showing.='All';
            }
            $showing.=($this->total_data==1)?' Entry':' Entries';
            if($this->page>$this->pages){
                $showing="";
            }
            $page_size='';
            if($this->page_size && $this->total_data>10){
                $page_size=form_dropdown('page_size',$this->sizes,$this->count,array('class'=>'form-control radius-0 page_size','style'=>"width:auto;"));
            }
            $pagination='<div class="pagination-div" style="min-height:60px;"><div class="showpage">'.$page_size.'<div class="pagination-info">'.$showing.'</div></div>'.$pagination.'</div>';
			return $pagination;
		}
		else{
			echo "Please add Paging Configuration!";
			return false;
		}
	}
		
	public function createpagelinks($page,$link,$current,$li_class,$class){
		if(is_array($this->pagefilters)){
            $pagefilters=$this->pagefilters;
            if($this->page_type=='parameter'){
                $pagefilters['page']=$page;
            }
            if(!empty($pagefilters)){
                $pagefilters=http_build_query($pagefilters);
                $pagefilters="?".$pagefilters;
            }
            else{
                $pagefilters='';
            }
		}
		if($this->display_type=="individual"){
			$pagelink="<ul ";
			if(is_array($this->ul_class))
				$pagelink.="class='".implode(" ",$this->ul_class)."'";
			$pagelink.=">";
		}
		else{
			$pagelink="";
		}
		$pagelink.="<li class='".implode(" ",$li_class);
		if($current===true){$pagelink.=" active";}
        $pagelink.="'>";
		if(!empty($page)){
            if($this->page_type=='uri'){
                $href=$this->url."page/".$page."/".$pagefilters;
            }
            else{
                $href=$this->url.$pagefilters;
            }
		}
		else{
			$href="#";
		}
		$pagelink.="<a href='$href' class='".implode(" ",$class);
		if($current===true){$pagelink.=" active";}
		$pagelink.="'>".$link."</a></li>";
		
		if($this->display_type=="individual"){
			$pagelink.="</ul> ";
		}
		return $pagelink;
	}
	
}