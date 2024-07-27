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
    private $tables;
    var $root='migrations';
    var $migrationfile='migrations.json';
    
    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
        $this->db=$this->CI->db;
        $this->CI->load->dbforge();
        $this->checkMigrationFile();
        $this->getTables();
        $this->getColumns();
    }

    public function getTables() {
        $tablearray=$this->db->query("show tables;")->result_array();
        $this->tables=array_column($tablearray,'Tables_in_'.DB_NAME);
    }

    public function getColumns() {
        foreach($this->tables as $table){
            echo '<br>----------';
            echo '<br>'.$table;
            $columns=$this->db->query("DESC ".$table)->result_array();
            $fields = $this->db->field_data($table);
            if(!empty($columns)){
                foreach($columns as $key=>$column){
                    if($columns[$key]['Field']==$fields[$key]->name){
                        $field=$fields[$key];
                    print_pre($column);
                    print_pre($fields[$key],true);
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
                        */
                        $attributes=array(
                            'type' => strtoupper($field->type),
                            'constraint' => $field->max_length,
                            'unsigned' => isset($field->unsigned) ? $field->unsigned : FALSE,
                            'null' => false,
                            'default' => $field->default,
                            'primary_key' => $field->primary_key
                        );
                    }
                }
            }
        }
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
                $this->dbforge->add_field(array(
                        'blog_id' => array(
                                'type' => 'INT',
                                'constraint' => 5,
                                'unsigned' => TRUE,
                                'auto_increment' => TRUE
                        ),
                        'blog_title' => array(
                                'type' => 'VARCHAR',
                                'constraint' => '100',
                        ),
                        'blog_description' => array(
                                'type' => 'TEXT',
                                'null' => TRUE,
                        ),
                ));
                $this->dbforge->add_key('blog_id', TRUE);
                $this->dbforge->create_table('blog');
        $attributes = array('ENGINE' => 'InnoDB');
        $this->dbforge->create_table('table_name', TRUE, $attributes);
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
                "constraint" => 11,
                "unsigned" => false,
                "default" => "",
                "null" => false,
                "auto_increment" => false,
                "unique" => false
                );
        
        $dataTypes=array();
        $attributes=$allattributes;
        $dataTypes['INT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=4;
        $dataTypes['TINYINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=6;
        $dataTypes['SMALLINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=9;
        $dataTypes['MEDIUMINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=20;
        $dataTypes['BIGINT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']="9,2";
        $dataTypes['FLOAT']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']="16,8";
        $dataTypes['DOUBLE']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']="10,2";
        $dataTypes['DECIMAL']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']="10";
        $dataTypes['CHAR']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']="255";
        $dataTypes['VARCHAR']=$attributes;
        
        $attributes==$allattributes;
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $dataTypes['TEXT']=$attributes;
        $dataTypes['MEDIUMTEXT']=$attributes;
        $dataTypes['LONGTEXT']=$attributes;
        
        $attributes=$allattributes;
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $dataTypes['DATE']=$attributes;
        $dataTypes['DATETIME']=$attributes;
        
        $attributes["default"] = "CURRENT_TIMESTAMP";
        $dataTypes['TIMESTAMP']=$attributes;
        
        $attributes=$allattributes;
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $dataTypes['TIME']=$attributes;
        $dataTypes['YEAR']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=["value1", "value2", "value3"];
        unset($attributes['auto_increment'],$attributes['default'],$attributes['unique']);
        $dataTypes['ENUM']=$attributes;
        
        $attributes=$allattributes;
        $attributes['null']=true;
        $attributes['constraint']=["value1", "value2", "value3"];
        unset($attributes['auto_increment'],$attributes['default'],$attributes['unique']);
        $dataTypes['ENUM']=$attributes;
        
        $attributes=$allattributes;
        $attributes['constraint']=["value1", "value2", "value3"];
        unset($attributes['auto_increment'],$attributes['default'],$attributes['unique']);
        $dataTypes['SET']=$attributes;
        
        $attributes=$allattributes;
        unset($attributes['constraint'],$attributes['unsigned'],$attributes['auto_increment'],$attributes['default'],
              $attributes['unique']);
        $attributes['null']=true;
        $dataTypes['JSON']=$attributes;
        $attributes=json_decode($json,true);
        
        $type=strtoupper($type);
        
        if(isset($attributes[$type])){
            $attribute=$attributes[$type];
        }
        else{
            $attribute=array('type'=>$type,'null'=>true);
        }
        return $attribute;
    }

}