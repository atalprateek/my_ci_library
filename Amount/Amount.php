<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : Amount
Description : Amount Operations
Version : v1.14
*/
class Amount {
	private $ones=array("","One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten",
						"Eleven","Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen",
						"Eighteen","Nineteen");
	private $tens=array(2=>"Twenty",3=>"Thirty",4=>"Forty",5=>"Fifty",6=>"Sixty",7=>"Seventy",
					8=>"Eighty",9=>"Ninety");
	private $words="";
	private $toreturn="";
	private $inwords="";
	private $inhundred="";
    
    function get_units($number){
        $units='';
        if($number>9){
            $number=substr($number,-1);
        }
        $number=intval($number,10);
        $units=$this->ones[$number];
        return $units;
    }
    
    function get_tens($number){
        $tens='';
        if($number>99){
            $number=substr($number,-2);
        }
        $number=intval($number,10);
        if($number<20){
            $tens=$this->ones[$number];
        }
        else{
            $units=$this->get_units($number);
            $number=floor($number/10);
            $tens=$this->tens[$number];
            $tens.=' '.$units;
        }
        return $tens;
    }
    
	function get_hundreds($number){
        $hundreds='';
        if($number>999){
            $number=substr($number,-3);
        }
        $number=intval($number,10);
        if($number>99){
            $number=floor($number/100);
            $hundreds=$this->ones[$number];
            $hundreds.=' Hundred';
        }
        return $hundreds;
    }
    
	function get_thousands($number){
        $thousands='';
        if($number>99999){
            $number=substr($number,-5);
        }
        $number=intval($number,10);
        if($number>999){
            $number=floor($number/1000);
            $thousands=$this->get_tens($number);
            $thousands.=' Thousand';
        }
        return $thousands;
    }
    
	function get_lakhs($number){
        $lakhs='';
        if($number>9999999){
            $number=substr($number,-7);
        }
        $number=intval($number,10);
        if($number>99999){
            $number=floor($number/100000);
            $lakhs=$this->get_tens($number);
            $lakhs.=' Lakh';
        }
        return $lakhs;
    }
    
	function get_crores($number){
        $crores='';
        if($number>999999999){
            $number=substr($number,-9);
        }
        $number=intval($number,10);
        if($number>9999999){
            $number=floor($number/10000000);
            $crores=$this->get_tens($number);
            $crores.=' Crore';
        }
        return $crores;
    }
    
	function to_words($number,$round=false){
        $number=abs($number);
        if($round===false){
            $num_array=explode('.',$number);
            $number=$num_array[0];
        }
        else{
            $number=round($number);
        }
        $words='';
		if($number<1000000000){
			$words.= $this->get_crores($number)." ".$this->get_lakhs($number)." ";
			$words.= $this->get_thousands($number)." ".$this->get_hundreds($number)." ".$this->get_tens($number);
		}
		return $words;
	}
	
	function decimal_to_words($number,$suffix=array()){
        $number=abs($number);
        $number=round($number,2);
        $array=explode('.',$number);
        $words=$this->to_words($array[0]);
        if(empty($suffix) && !empty($array[1])){
            $words.=' point ';
        }
        elseif(!empty($suffix[0])){
            $words.=' '.$suffix[0].' ';
        }
        if(!empty($array[1])){
            if($array[1]>0){
                if(strlen($array[1])<2){
                    $array[1].='0';
                }
                $words.=$this->get_tens($array[1]);
                if(!empty($suffix[1])){
                    $words.=' '.$suffix[1].' ';
                }
            }
        }
		return $words;
	}
	
	function toDecimal($number,$decimal=true,$decimalDigits=2){
        if($number==0){ return 0; }
		$sign="";
		if($number<0){
			$number=0-$number;
			$sign="-";
		}
		$amount=number_format((float)$number,$decimalDigits,'.','');
		$array=explode('.',$amount);
		$arr=str_split($array[0],1);
		$length=sizeof($arr);
		$amt="";
		if($length>3){
			if($length%2==0){
				for($i=0;$i<$length;$i++){
					$amt.=$arr[$i];
					if($i%2==0){
						if($length-$i==2){continue;}
						$amt.=",";
					}
				}
			}
			else{
				for($i=0;$i<$length;$i++){
					$amt.=$arr[$i];
					if($i%2!=0){
						if($length-$i==2){continue;}
						$amt.=",";
					}
				}			
			}
		}
		else{
			$amt=$array[0];
		}
		$result=$sign.$amt;
		if($decimal || $array[1]>0){ $result.='.'.$array[1]; }
		return $result;
	}
	
	function twoDigits($number){
		return number_format((float)$number,2,'.','');
	}
    
    function formatNumber($num,$numberSystem='Indian') {
        if($numberSystem=='Indian'){
            if ($num >= 10000000) {
                // 1 crore and above
                return round($num / 10000000, 1) . 'C';
            } elseif ($num >= 100000) {
                // 1 lakh and above
                return round($num / 100000, 1) . 'L';
            } elseif ($num >= 1000) {
                // 1 thousand and above
                return round($num / 1000, 1) . 'k';
            } else {
                // Less than 1 thousand
                return $num;
            }
        }
        else{
            if ($num >= 1000000000000) {
                // 1 trillion and above
                return round($num / 1000000000000, 1) . 'T';
            } elseif ($num >= 1000000000) {
                // 1 billion and above
                return round($num / 1000000000, 1) . 'B';
            } elseif ($num >= 1000000) {
                // 1 million and above
                return round($num / 1000000, 1) . 'M';
            } elseif ($num >= 1000) {
                // 1 thousand and above
                return round($num / 1000, 1) . 'k';
            } else {
                // Less than 1 thousand
                return $num;
            }
        }
    }
    
}