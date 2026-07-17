<?php
//echo PHP_SAPI ;
$root='mylib';
$libfile='mylibraries.json';
$ignorefilepath='./.gitignore';
$filepath=$root.'/'.$libfile;
$librarypath='./application/libraries/';
$pre_msgs=array();
if(!is_dir($root)){
    $pre_msgs[]="Root Directory not found!\nCreating Root Directory...";
    mkdir($root);
    $pre_msgs[]="Root Directory Created!";
    copy('./application/.htaccess',$root.'/.htaccess');
    copy('./application/index.html',$root.'/index.html');
    $pre_msgs[]="index and htaccess files added to Root Directory.";
    if(file_exists($ignorefilepath)){
        $pre_msgs[]="Updating Git Ignore file...";
        $array=array();
        $status=0;
        $fh=fopen($ignorefilepath,'a+');
        while (($line = fgets($fh)) !== false) {
            $array[]=$line;
            if(strpos($line,'/lib_updater.php')===false){
                $status=$status==0?1:2;
            }
            if(strpos($line,'/mylib/')===false){
                $status=$status==0?3:2;
            }
        }
        if($status!=0){
            $pre_msgs[]="Updating Git Ignore file...";
        }
        if($status==1 || $status==2){
            fwrite($fh,"\n");
            fwrite($fh,'/lib_updater.php');
        }
        if($status==3 || $status==2){
            fwrite($fh,"\n");
            fwrite($fh,'/mylib/');
        }
        if($status!=0){
            $pre_msgs[]="Git Ignore file Updated!";
        }
        fclose($fh);
    }
    
}
if(!file_exists($filepath)){
    $fh=fopen($filepath,'w');
    fclose($fh);
}
if($argc>1 && $argv[1]=='install' && !empty($argv[2])){
    $libraries=array();
}
else{
    $libraries=file_get_contents($filepath);
    $libraries=json_decode($libraries,true);
}

$githubpath="https://raw.githubusercontent.com/atalprateek/my_ci_library/main/libraries.json";
$verbose=false;
if($argc>1){
    if($argv[1]=='install' && !empty($argv[2])){
        $gitlibraries=file_get_contents($githubpath);
        $gitlibraries=json_decode($gitlibraries,true);
        $gitlibraries=!empty($gitlibraries)?$gitlibraries:array();
        $names=!empty($gitlibraries)?array_column($gitlibraries,'name'):array();
        $toinstall=$argv[2];
        $index=array_search($toinstall,$names);
        if($index!==false){
            $pre_msgs[]="Installing ".$toinstall.'...';
            $libraries=array_merge($libraries,[$gitlibraries[$index]]);
        }
    }
    if($argv[1]=='-verbose' || in_array('-verbose',$argv)){
        $verbose=true;
    }
}
if($verbose && !empty($pre_msgs)){
    $pre_msgs=implode("\n",$pre_msgs);
    echo $pre_msgs."\n";
}
//print_r($libraries);die;
$messages=array();
if(!empty($libraries)){
    foreach($libraries as $key=>$library){
        $update=false;
        $path=$librarypath.$library['path'];
        $libfilepath=$path.'/'.$library['name'].'.php';
        if(is_dir($path)){
            if(file_exists($libfilepath)){
                $oldcontent=file_get_contents($libfilepath);
                preg_match("/v\d+\.\d+/", $oldcontent, $oldmatches);
            }
            else{
                $update=true;
            }
            if($verbose){ echo "\nLibrary : ".$library['name']."\n"; }
        }
        else{
            mkdir($path);
            $update=true;
        }
        $content=file_get_contents($library['url']);
        preg_match("/v\d+\.\d+/", $content, $matches);
        
        if(!empty($matches)){
            $version=$matches[0];
            if($verbose){ echo 'New Version : '.$version."\n"; }
            $version=str_replace('v','',$version);
            if(empty($oldmatches)){
                $update=true;
            }
            else{
                $oldversion=$oldmatches[0];
                if($verbose){ echo 'Old Version : '.$oldversion."\n"; }
                $oldversion=str_replace('v','',$oldversion);
                //echo $oldversion.":".$version."\n";
                //var_dump($oldversion<$version);
                if($oldversion<$version){
                    if($verbose){ echo 'Updating '.$library['name']."...\n"; }
                    $update=true;
                }
            }
        }
        if($update){
            if(!empty($version)){
                $libraries[$key]['version']="v".$version;
            }
            $messages[]=$library['name'];
            file_put_contents($libfilepath,$content);
        }
    }
    if($argc>1 && $argv[1]=='install' && !empty($argv[2])){
        $oldlibraries=file_get_contents($filepath);
        $oldlibraries=json_decode($oldlibraries,true);
        $oldlibraries=!empty($oldlibraries)?$oldlibraries:array();
        $oldnames=!empty($oldlibraries)?array_column($oldlibraries,'name'):array();
        $newlibrary=$argv[2];
        $index=array_search($newlibrary,$oldnames);
        if($index===false){
            $libraries=array_merge($oldlibraries,$libraries);
        }
        else{
            $oldlibraries[$index]=$libraries[0];
            $libraries=$oldlibraries;
        }
    }
    $libraries=json_encode($libraries,JSON_PRETTY_PRINT);
    file_put_contents($filepath,$libraries);
}
if(!empty($messages)){
    echo "\n".implode(',',$messages).' Updated!';
}
else{
    echo "\nAll Libraries are Up to Date!";
}

function createZip($sourceDir,$zipFileName){
    $sourceDir=realpath($sourceDir);
    // Create a new ZipArchive object
    $zip = new ZipArchive();

    // Create and open the zip file
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        // Add files from the source directory to the zip archive
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Close the zip file
        $zip->close();

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));
        var_dump($files);
        echo "Zip archive created successfully.";
    } else {
        echo "Failed to create the zip archive.";
    }
}