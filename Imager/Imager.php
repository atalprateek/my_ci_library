<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Name : Imager
Description : Image Manipulation Using Image Intervention
Version : v1.1
*/
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
//use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Format;
use Intervention\Image\MediaType;
use Intervention\Image\FileExtension;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\BmpEncoder;
use Intervention\Image\Encoders\WebpEncoder;

class Imager {
    var $ci;
    private $manager;
    private $driver;
    
    public function __construct(){
        $this->ci =& get_instance();
        // create new manager instance with desired driver
        $this->manager = new ImageManager(new Driver());
    }
    
    public function initiateDriver(){
        $this->driver = $this->manager->driver();
    }
    
    public function checkSupportedFormat($format){
        $result = $this->manager->driver()->supports($format);
        return $result;
    }
    
    public function readImage($path){
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if($this->checkSupportedFormat($extension)){
            if(filter_var($path, FILTER_VALIDATE_URL) !== false){
                $image = $this->manager->read(file_get_contents($path));
            }
            else{
                $image = $this->manager->read($path);
            }
            return $image;
        }
    }
    
    public function getImageDimensions($path){
        $image=$this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        $width = $image->width();
        $height = $image->height();
        $size = $image->size();
        $ratio = $size->aspectRatio();
        $is_portrait = $size->isPortrait(); // true
        $is_landscape = $size->isLandscape(); // false
        $resolution = $image->resolution();
        // convert resolution to dpcm
        $resolutioncm = $resolution->perCm();
        // read resolution for each axis
        $x = $resolution->x();
        $y = $resolution->y();
        //print_pre($width);
        //print_pre($height);
        //print_pre($size);
        //print_pre($ratio);
        //var_dump($is_portrait);
        //var_dump($is_landscape);
        //print_pre($width);
        //print_pre($resolution);
        //print_pre($resolutioncm);
        //print_pre($x);
        //print_pre($y);
    }
    
    public function encodeImage($path,$quality=80){
        $image = $this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        // Get the image extension/type
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Determine encoding type and quality
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Encode JPEG with $quality% quality
                $image=$image->toJpeg($quality);
                break;
            case 'png':
                // Encode PNG (lossless, no quality parameter needed)
                $image=$image->toPng();
                break;
            case 'webp':
                // Encode WebP with $quality% quality
                $image=$image->toWebp($quality);
                break;
            case 'gif':
                // Encode GIF (no quality parameter for GIF)
                $image=$image->toGif();
                break;
            default:
                // If unknown type, skip encoding
                
        }
        return $image;
        //$newImagePath = './encoded_' . basename($path);
        //$this->saveImage($image,$newImagePath);
    }
    
    public function resizeImage($path,$size,$quality=80){
        $image = $this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        // reading jpeg image
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if($size['original']===true){
            $image->resizeDown($size['width'], $size['height']);
        }
        else{
            $image->resize($size['width'], $size['height']);
        }
        
        // Determine encoding type and quality
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Encode JPEG with $quality% quality
                $image=$image->toJpeg($quality);
                break;
            case 'png':
                // Encode PNG (lossless, no quality parameter needed)
                $image=$image->toPng();
                break;
            case 'webp':
                // Encode WebP with $quality% quality
                $image=$image->toWebp($quality);
                break;
            case 'gif':
                // Encode GIF (no quality parameter for GIF)
                $image=$image->toGif();
                break;
            default:
                // If unknown type, skip encoding
                
        }
        return $image;
        //$newImagePath = './encoded_' . basename($path);
        //$this->saveImage($image,$newImagePath);
    }
    
    public function scaleImage($path,$size,$quality=80){
        $image = $this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        // reading jpeg image
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if($size['original']===true){
            $image->scaleDown($size['width'], $size['height']);
        }
        else{
            $image->scale($size['width'], $size['height']);
        }
        
        // Determine encoding type and quality
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Encode JPEG with $quality% quality
                $image=$image->toJpeg($quality);
                break;
            case 'png':
                // Encode PNG (lossless, no quality parameter needed)
                $image=$image->toPng();
                break;
            case 'webp':
                // Encode WebP with $quality% quality
                $image=$image->toWebp($quality);
                break;
            case 'gif':
                // Encode GIF (no quality parameter for GIF)
                $image=$image->toGif();
                break;
            default:
                // If unknown type, skip encoding
                
        }
        return $image;
        //$newImagePath = './encoded_' . basename($path);
        //$this->saveImage($image,$newImagePath);
    }
    
    public function cropImage($path,$size,$quality=80){
        $image = $this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        // reading image
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $width = $image->width();
        $height = $image->height();

        // Calculate the center 
        $cropWidth = $size['width'];
        $cropHeight = $size['height'];
        $x = ($width / 2) - ($cropWidth / 2);
        $y = ($height / 2) - ($cropHeight / 2);

        // Crop the image from the center
        $image->crop($cropWidth, $cropHeight, $x, $y);
        // Determine encoding type and quality
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Encode JPEG with $quality% quality
                $image=$image->toJpeg($quality);
                break;
            case 'png':
                // Encode PNG (lossless, no quality parameter needed)
                $image=$image->toPng();
                break;
            case 'webp':
                // Encode WebP with $quality% quality
                $image=$image->toWebp($quality);
                break;
            case 'gif':
                // Encode GIF (no quality parameter for GIF)
                $image=$image->toGif();
                break;
            default:
                // If unknown type, skip encoding
                
        }
        return $image;
        //$newImagePath = './encoded_' . basename($path);
        //$this->saveImage($image,$newImagePath);
    }
    
    public function cropScaleImage($path,$size,$quality=80){
        $image = $this->readImage($path);
        if(empty($image)){
            return array('status'=>false,'message'=>'Image type not supported!');
        }
        $imagesize = $image->size();
        $ratio = $imagesize->aspectRatio();
        if($ratio==1 && !isset($size['original'])){
            $size['original']=false;
        }
        elseif(!isset($size['original'])){
            $size['original']=true;
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if($size['original']===true){
            $image->scaleDown($size['width'], $size['height']);
        }
        else{
            $image->scale($size['width'], $size['height']);
        }
        $width = $image->width();
        $height = $image->height();

        // Calculate the center 
        $cropWidth = $size['width'];
        $cropHeight = $size['height'];
        $x = round(($width / 2) - ($cropWidth / 2));
        $y = round(($height / 2) - ($cropHeight / 2));

        $cropWidth = round($cropWidth);
        $cropHeight = round($cropHeight);
        $x = round($x);
        $y = round($y);
        
        // Crop the image from the center
        $image->crop($cropWidth, $cropHeight, $x, $y);
        // Determine encoding type and quality
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                // Encode JPEG with $quality% quality
                $image=$image->toJpeg($quality);
                break;
            case 'png':
                // Encode PNG (lossless, no quality parameter needed)
                $image=$image->toPng();
                break;
            case 'webp':
                // Encode WebP with $quality% quality
                $image=$image->toWebp($quality);
                break;
            case 'gif':
                // Encode GIF (no quality parameter for GIF)
                $image=$image->toGif();
                break;
            default:
                // If unknown type, skip encoding
                
        }
        return $image;
    }
    
    public function saveImage($image,$path,$destination='',$thumb="thumb"){
        if(empty($destination)){
            $destination = $path;
        }
        else{
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
			$dirs=explode('/',$destination);
			$destination='';
			foreach($dirs as $dir){
				if($dir==''){ break; }
				$destination.=$dir.'/';
				if(!is_dir($destination)){
					mkdir($destination);
				}
			}
            $destination = $destination. basename($path);
            $destination = str_replace(".$extension","_$thumb.$extension",$destination);
        }
        //echo $destination;die;
        if(is_array($image) && $image['status']===false){
            if($path!=$destination){
                copy($path,$destination);
            }
        }
        else{
            $image->save($destination);
        }
        $filepath=trim($destination,'./');
        return $filepath;
    }
    
    public function processimage($path,$type="encode",$quality=80,$size=array(),$destination='',$thumb='thumb'){
        switch($type){
            case 'encode' : $image=$this->encodeImage($path,$quality);
                break;
            case 'resize' : $image=$this->resizeImage($path,$size,$quality);
                break;
            case 'scale' : $image=$this->scaleImage($path,$size,$quality);
                break;
            case 'crop' : $image=$this->cropImage($path,$size,$quality);
                break;
            case 'cropscale' : $image=$this->cropScaleImage($path,$size,$quality);
                break;
            default:
        }
        
        $newpath=$this->saveImage($image,$path,$destination,$thumb);
        return $newpath;
    }
    
}