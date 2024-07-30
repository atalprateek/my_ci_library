<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBMigration
Description : DB Migration for Codeigniter 3
Version : v0.01
*/

class DBMigration {
    protected $CI;
    protected $db;
    protected $dbforge;
    private $tables;
    var $root='migrations';
    var $migrationfile='migrations.json';
    
    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
        $this->db=$this->CI->db;
        $this->CI->load->dbforge();
        $this->dbforge=$this->CI->dbforge;
        
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
        $this->checkMigrationFile();
        $result=$this->createTable();
        echo '<h3>'.$result['message'].'</h3>';
    }

    public function getTables() {
        $tablearray=$this->db->query("show tables;")->result_array();
        $this->tables=array_column($tablearray,'Tables_in_'.DB_NAME);
    }

    public function getColumns() {
        $data=array();
        foreach($this->tables as $table){
            $data[$table]=array();
            //if($table!='ns_users'){ continue; }
            //echo '<br>----------';
            //echo '<br>'.$table;
            $columns=$this->db->query("DESC ".$table)->result_array();
            $fields = $this->db->field_data($table);
            if(!empty($columns)){
                foreach($columns as $key=>$column){
                    if($columns[$key]['Field']==$fields[$key]->name){
                        $field=$fields[$key];
                    //print_pre($column);
                    //print_pre($fields[$key]);
                        /*Array
(
    [Field] => id
    [Type] => int(11)
    [Null] => NO
    [Key] => PRI
    [Default] => 
    [Extra] => auto_increment
)
stdClass Object
(
    [name] => id
    [type] => int
    [max_length] => 11
    [default] => 
    [primary_key] => 1
)
unsigned/true : to generate “UNSIGNED” in the field definition.
default/value : to generate a default value in the field definition.
null/true : to generate “NULL” in the field definition. Without this, the field will default to “NOT NULL”.
auto_increment/true : generates an auto_increment flag on the field. Note that the field type must be a type that supports this, such as integer.
unique/true :

                "null" => false,
                "auto_increment" => false,
                "primary" => false,
                "unique" => false
                        
                    */
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
                    $data[$table][$column['Field']]=$attributes;
                }
            }
        }
        return $data;
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
    }

    public function createTable() {
        $filepath=$this->root.'/'.$this->migrationfile;
        $data=file_get_contents($filepath);
        $tables=json_decode($data,true);
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

    public function dropTable() {
        $this->dbforge->drop_table('table_name',TRUE);
    }

    public function renameTable() {
        $this->dbforge->rename_table('old_table_name', 'new_table_name');
    }

    public function addColumn() {
        $fields = array(
                'preferences' => array('type' => 'TEXT', 'after' => 'another_field')
        );

        // Will place the new column at the start of the table definition:
        $fields = array(
                'preferences' => array('type' => 'TEXT', 'first' => TRUE)
        );
        $this->dbforge->add_column('table_name', $fields);
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

    public function getColumnAttributes($table, $column) {
        $fields = $this->CI->db->field_data($table);
        
        foreach ($fields as $field) {
            if ($field->name == $column) {
                $attributes = [
                    'type' => strtoupper($field->type),
                    'constraint' => $field->max_length,
                    'unsigned' => isset($field->unsigned) ? $field->unsigned : FALSE,
                    'null' => false,
                    'default' => $field->default,
                    'primary_key' => $field->primary_key
                ];
                return $attributes;
            }
        }
        
        return null;
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