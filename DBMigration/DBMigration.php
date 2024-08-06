<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBMigration
Description : DB Migration for Codeigniter 3
Version : v0.03
*/

class DBMigration {
    protected $CI;
    protected $db;
    protected $dbforge;
    private $tables;
    private $oldData;
    var $root='migrations';
    var $migrationfile='migrations.json';
    var $statusfile='migration-status.json';
    
    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
        $this->db=$this->CI->db;
        $this->CI->load->dbforge();
        $this->dbforge=$this->CI->dbforge;
        defined('TP') OR define('TP',"");
    }

    public function generateMigration() {
        $this->checkMigrationFile();
        $this->getTables();
        $columnData=$this->getColumns();
        
        if(!empty($columnData)){
            //print_pre($data);
            $filepath=$this->root.'/'.$this->migrationfile;
            $jsondata=json_encode($columnData,JSON_PRETTY_PRINT);
            file_put_contents($filepath,$jsondata);
            echo '<h3>Migration File Created Successfully!</h3>';
        }
        else{
            echo '<h3>No tables found in Database!</h3>';
        }
    }

    public function runMigration() {
        $this->getTables();
        if(empty($this->tables)){
            $this->checkMigrationFile();
            $result=$this->createTables();
            echo '<h3>'.$result['message'].'</h3>';
        }
        else{
            echo '<h3>Tables Already Added!</h3>';
            $statusfilepath=$this->root.'/'.$this->statusfile;
            $statusdata=file_get_contents($statusfilepath);
            if(empty($statusdata)){
                $statusdata=array(0);
                $jsondata=json_encode($statusdata,JSON_PRETTY_PRINT);
                file_put_contents($statusfilepath,$jsondata);
            }
            else{
                $statusdata=json_decode($statusdata,true);
            }
            $last=max($statusdata);
            $last++;
            
            $filepath=$this->root.'/'.$this->migrationfile;
            $data=file_get_contents($filepath);
            $data=json_decode($data,true);
            while(isset($data[$last])){
                $tables=$data[$last];
                if(!empty($tables)){
                    foreach($tables as $table=>$attributes){
                        $table=substr($table,3,strlen($table)-3);
                        //print_pre($table);
                        //print_pre($attributes);
                        if(isset($attributes['remove'])){
                            foreach($attributes['remove'] as $column=>$value){
                                $this->dropColumn($table, $column);
                            }
                        }
                        if(isset($attributes['update'])){
                            $this->modifyColumn($table, $attributes['update']);
                        }
                        if(isset($attributes['add'])){
                            $this->addColumn($table, $attributes['add']);
                        }
                    }
                }
                $last++;
            }
            $last--;
            if(!in_array($last,$statusdata)){
                echo '<h3>Database Updated!</h3>';
                $statusdata[]=$last;
                $jsondata=json_encode($statusdata,JSON_PRETTY_PRINT);
                file_put_contents($statusfilepath,$jsondata);
            }
        }
    }

    public function getTables() {
        $tablearray=$this->db->query("show tables;")->result_array();
        $this->tables=array_column($tablearray,'Tables_in_'.DB_NAME);
    }

    public function getColumns() {
        $data=array();
        foreach($this->tables as $table){
            $tablename=strpos($table,TP)===0?str_replace(TP,TP,$table):$table;
            $data[$tablename]=array();
            //if($table!='ns_users'){ continue; }
            //echo '<br>----------';
            //echo '<br>'.$table;
            $columns=$this->db->query("DESC ".$table)->result_array();
            $fields = $this->db->field_data($table);
            if(!empty($columns)){
                foreach($columns as $key=>$column){
                    if($columns[$key]['Field']==$fields[$key]->name){
                        $field=$fields[$key];
                        $attributes=$this->getDatatypeAttributes($field->type);
                        foreach($attributes as $attribute=>$value){
                            if($attribute=='constraint'){
                                if(empty($field->max_length)){
                                    unset($attributes[$attribute]);
                                }
                                else{
                                    $attributes[$attribute]=$field->max_length;
                                }
                            }
                            if($attribute=='unsigned'){
                                if (strpos($column['Type'], 'unsigned') !== false) {
                                    $attributes[$attribute]=true;
                                } else {
                                    unset($attributes[$attribute]);
                                }
                                
                            }
                            if($attribute=='default'){
                                if($column['Default']!==NULL){
                                    $attributes[$attribute]=$column['Default'];
                                }
                                else{
                                    if (strpos($column['Null'], 'YES') !== false) {
                                        $attributes[$attribute]=NULL;
                                    } else {
                                        unset($attributes[$attribute]);
                                    }
                                }
                            }
                            if($attribute=='null'){
                                if (strpos($column['Null'], 'YES') !== false) {
                                    $attributes[$attribute]=TRUE;
                                } 
                                else {
                                    unset($attributes[$attribute]);
                                }
                            }
                            if($attribute=='auto_increment'){
                                if(!empty($column['Extra']) && $column['Extra']=='auto_increment'){
                                    $attributes[$attribute]=true;
                                } 
                                else {
                                    unset($attributes[$attribute]);
                                }
                            }
                            if($attribute=='primary'){
                                if(!empty($column['Key']) && $column['Key']=='PRI'){
                                    $attributes[$attribute]=true;
                                } 
                                else {
                                    unset($attributes[$attribute]);
                                }
                            }
                            if($attribute=='unique'){
                                if(!empty($column['Key']) && $column['Key']=='UNI'){
                                    $attributes[$attribute]=true;
                                } 
                                else {
                                    unset($attributes[$attribute]);
                                }
                            }
                        }
                        //echo '--------------xxxxxxxxxxxxx-----------';
                    }
                    $data[$tablename][$column['Field']]=$attributes;
                }
            }
        }
        $data=array($data);
        
        $json = file_get_contents($this->root.'/'.$this->migrationfile);
        if(!empty($json)){
            $array=json_decode($json,true);
            $this->oldData=$array;
            $diff=$this->compareArrays($array[0],$data[0]);
            $diffData=$this->createDiffData($diff);
            //$positions=$this->getColumnPositions('ns_notes');
            //print_pre($positions);
            //print_pre($diffData,true);
            $array[0]=$data[0];
            if(!empty($diffData)){
                $array[]=$diffData;
            }
        }
        //print_pre($data,true);
        return $array;
    }

    public function createDiffData($diff) {
        $result=array();
        if(!empty($diff)){
            //print_pre($diff,true);
            foreach($diff as $table=>$columns){
                $result[$table]=array();
                if(isset($columns['status']) && $columns['status']=='added'){
                    $columns=$columns['value'];
                    foreach($columns as $column=>$attributes){
                        $result[$table]['update'][$column]=$attributes;
                    }
                    continue;
                }
                foreach($columns as $column=>$attributes){
                    if(isset($attributes['status'])){
                        if($attributes['status']=='added'){
                            $result[$table]['add'][$column]=$attributes['value'];
                        }
                        if($attributes['status']=='removed'){
                            $result[$table]['remove'][$column]=$attributes['value'];
                        }
                    }
                    else{
                        $oldAttributes=!empty($this->oldData[0][$table])?$this->oldData[0][$table][$column]:array();
                        $result[$table]['update'][$column]=array();
                        foreach($oldAttributes as $attribute=>$value){
                            if(isset($attributes[$attribute])){
                                if($attributes[$attribute]['status']=='updated'){
                                    if($value==$attributes[$attribute]['old_value']){
                                        $result[$table]['update'][$column][$attribute]=$attributes[$attribute]['new_value'];
                                    }
                                }
                                /*elseif($attributes[$attribute]['status']=='removed'){
                                    $result[$table]['remove'][$column][$attribute]=$attributes[$attribute]['value'];
                                }*/
                            }
                            else{
                                $result[$table]['update'][$column][$attribute]=$value;
                            }
                        }
                        foreach($attributes as $attribute=>$value){
                            if(!isset($result[$table]['update'][$column][$attribute])){
                                if(isset($value['status']) && $value['status']=='added'){
                                    $result[$table]['update'][$column][$attribute]=$value['value'];
                                }
                            }
                        }
                    }
                    if(isset($result[$table]['update'][$column]['null'])){
                        if(!isset($result[$table]['update'][$column]['default'])){
                            $result[$table]['update'][$column]['default']=NULL;
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function checkMigrationFile() {
        if(!is_dir($this->root)){
            mkdir($this->root);
        }
        $filepath=$this->root.'/'.$this->migrationfile;
        if(!file_exists($filepath)){
            $fh=fopen($filepath,'w');
            fclose($fh);
        }
        
        $filepath=$this->root.'/'.$this->statusfile;
        if(!file_exists($filepath)){
            $fh=fopen($filepath,'w');
            fclose($fh);
            
            $ignorefilepath='./.gitignore';
            if(file_exists($ignorefilepath)){
                $array=array();
                $status=1;
                $fh=fopen($ignorefilepath,'a+');
                while (($line = fgets($fh)) !== false) {
                    $array[]=$line;
                    if(strpos($line,'/'.$this->root.'/'.$this->statusfile)!==false){
                        $status=0;
                        break;
                    }
                }
                if($status==1){
                    fwrite($fh,"\n");
                    fwrite($fh,'/'.$this->root.'/'.$this->statusfile);
                }
                fclose($fh);
            }
        }
    }

    public function createTables() {
        $filepath=$this->root.'/'.$this->migrationfile;
        $data=file_get_contents($filepath);
        $tables=json_decode($data,true);
        $tables=$tables[0];
        if(!empty($tables)){
            foreach($tables as $table=>$attributes){
                //if($table!='ns_users'){ continue; }
                $s=array_column($attributes,'unique');
                //print_pre($s);
                //print_pre($attributes,true);
                $this->dbforge->add_field($attributes);

                foreach ($attributes as $key => $attribute) {
                    if (isset($attribute['primary']) && $attribute['primary'] == 1) {
                        $this->dbforge->add_key($key, TRUE);
                        break;
                    }
                    elseif (isset($attribute['unique']) && $attribute['unique'] == 1) {
                        //$this->dbforge->add_key($key, FALSE, TRUE);
                        // Unique Set from attributes
                    }
                }
                $tableattributes = array('ENGINE' => 'InnoDB');
                $table=substr($table,3,strlen($table)-3);
                $this->dbforge->create_table($table, TRUE, $tableattributes);
                //print_pre($this->db->error());
            }
            return array('status'=>true,'message'=>'Migration Successful!');
        }
        else{
            return array('status'=>false,'message'=>'Migration data not found!');
        }
    }
    
    function compareArrays($array1, $array2) {
        $diff = [];
        // Check for differences in $array1 that are not in $array2
        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->compareArrays($value, $array2[$key]);
                    if (!empty($recursiveDiff)) {
                        $diff[$key] = $recursiveDiff;
                    }
                } elseif ($value != $array2[$key]) {
                    // Key exists in both, but the value has been updated
                    $diff[$key] = [
                        'status' => 'updated',
                        'old_value' => $value,
                        'new_value' => $array2[$key]
                    ];
                }
            } else {
                // Key is missing in the second array
                $diff[$key] = [
                    'status' => 'removed',
                    'value' => $value
                ];
            }
        }

        // Check for keys that exist in $array2 but not in $array1
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                // Key is newly added in the second array
                $diff[$key] = [
                    'status' => 'added',
                    'value' => $value
                ];
            }
        }
        
        return $diff;
    }
    
    public function getColumnPositions($table_name) {
        $database_name = $this->db->database;  // Get the current database name
        
        $sql = "SELECT COLUMN_NAME, ORDINAL_POSITION
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '$database_name'
                AND TABLE_NAME = '$table_name'
                ORDER BY ORDINAL_POSITION ASC";

        $query = $this->db->query($sql);

        return $query->result_array();
    }
    
    public function dropTable() {
        $this->dbforge->drop_table('table_name',TRUE);
    }

    public function renameTable() {
        $this->dbforge->rename_table('old_table_name', 'new_table_name');
    }

    public function addColumn($table,$fields) {
        /*$fields = array(
                'preferences' => array('type' => 'TEXT', 'after' => 'another_field')
        );

        // Will place the new column at the start of the table definition:
        $fields = array(
                'preferences' => array('type' => 'TEXT', 'first' => TRUE)
        );*/
        $this->dbforge->add_column($table, $fields);
    }

    public function modifyColumn($table,$fields) {
        $this->dbforge->modify_column($table,$fields);
    }

    public function dropColumn($table,$column) {
        $this->dbforge->drop_column($table,$column);
    }

    public function checkcolumns() {
        
        $columns=array();
        $columns[]=array('name'=>'id','attributes'=>array('type'=>'INT','null'=>false,'auto_increment'=>true,
                                                          'key'=>'primary'));
        $columns[]=array('name'=>'operation','attributes'=>array('type'=>'varchar','null'=>false,'constraint'=>50));
        $columns[]=array('name'=>'table_name','attributes'=>array('type'=>'varchar','null'=>false,'constraint'=>100));
        $columns[]=array('name'=>'table_name','attributes'=>array('type'=>'varchar','null'=>false,'constraint'=>100));
        $columns[]=array('name'=>'primary_key','attributes'=>array('type'=>'varchar','null'=>false,'constraint'=>255));
        $columns[]=array('name'=>'data','attributes'=>array('type'=>'text','null'=>false));
        $columns[]=array('name'=>'ref','attributes'=>array('type'=>'varchar','null'=>false,'constraint'=>100));
        $columns[]=array('name'=>'user_id','attributes'=>array('type'=>'INT','null'=>false));
        $columns[]=array('name'=>'added_on','attributes'=>array('type'=>'datetime','null'=>false));
        foreach($columns as $column){
            $old_attributes=$this->get_column_attributes('db_operations',$column['name']);
            //print_pre($old_attributes);
            $attributes=$column['attributes'];
            
            $changes_needed = false;
            foreach ($attributes as $key => $value) {
                if (!isset($old_attributes[$key]) || strtoupper($old_attributes[$key]) != strtoupper($value)) {
                    if($key=='auto_increment'){
                        continue;
                    }
                    elseif($key=='key' && $value=='primary' && $old_attributes['primary_key']==1){
                        continue;
                    }
                    $changes_needed = true;
                    break;
                }
            }
            
            if($changes_needed){
                $fields = [
                    $column['name'] => $attributes
                ];

                // Modify the column
                $this->CI->dbforge->modify_column('db_operations', $fields);
                //echo "Column attributes modified successfully.";
            } else {
                //echo "No changes needed.";
            }
        }
    }

    public function getDatatypeAttributes($type){
//unsigned/true : to generate “UNSIGNED” in the field definition.
//default/value : to generate a default value in the field definition.
//null/true : to generate “NULL” in the field definition. Without this, the field will default to “NOT NULL”.
//auto_increment/true : generates an auto_increment flag on the field. Note that the field type must be a type that supports //this, such as integer.
//unique/true :
        $allattributes=array(
                "type"=>"",
                "constraint" => 11,
                "unsigned" => false,
                "default" => "",
                "null" => false,
                "auto_increment" => false,
                "primary" => false,
                "unique" => false
                );
        
        $dataTypes=array();
        $attributes=$allattributes;
        $attributes['type']="INT";
        $dataTypes['INT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="TINYINT";
        $attributes['constraint']=4;
        $dataTypes['TINYINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="SMALLINT";
        $attributes['constraint']=6;
        $dataTypes['SMALLINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="MEDIUMINT";
        $attributes['constraint']=9;
        $dataTypes['MEDIUMINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="BIGINT";
        $attributes['constraint']=20;
        $dataTypes['BIGINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="FLOAT";
        $attributes['constraint']="9,2";
        $dataTypes['FLOAT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="DOUBLE";
        $attributes['constraint']="16,8";
        $dataTypes['DOUBLE']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="DECIMAL";
        $attributes['constraint']="10,2";
        $dataTypes['DECIMAL']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="CHAR";
        $attributes['constraint']="10";
        $dataTypes['CHAR']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="VARCHAR";
        $attributes['constraint']="255";
        $dataTypes['VARCHAR']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="TEXT";
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $dataTypes['TEXT']=$attributes;
        $attributes['type']="MEDIUMTEXT";
        $dataTypes['MEDIUMTEXT']=$attributes;
        $attributes['type']="LONGTEXT";
        $dataTypes['LONGTEXT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="";
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $attributes['type']="DATE";
        $dataTypes['DATE']=$attributes;
        $attributes['type']="DATETIME";
        $dataTypes['DATETIME']=$attributes;
        
        $attributes["default"] = "CURRENT_TIMESTAMP";
        $attributes['type']="TIMESTAMP";
        $dataTypes['TIMESTAMP']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="";
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['type']="TIME";
        $dataTypes['TIME']=$attributes;
        $attributes['type']="YEAR";
        $dataTypes['YEAR']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="ENUM";
        $attributes['constraint']=["value1", "value2", "value3"];
        unset($attributes['auto_increment'],$attributes['default'],$attributes['unique']);
        $dataTypes['ENUM']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="SET";
        $attributes['constraint']=["value1", "value2", "value3"];
        unset($attributes['auto_increment'],$attributes['default'],$attributes['unique']);
        $dataTypes['SET']=$attributes;
        
        $attributes=$allattributes;
        $attributes['type']="JSON";
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $dataTypes['JSON']=$attributes;
        
        $type=strtoupper($type);
        
        if(isset($dataTypes[$type])){
            $attribute=$dataTypes[$type];
        }
        else{
            $attribute=array('type'=>$type,'null'=>true);
        }
        return $attribute;
    }

}