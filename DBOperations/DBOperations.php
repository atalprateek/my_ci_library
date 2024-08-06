<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBOperations
Description : DBOperations for Codeigniter 3
Version : v0.06
*/

class DBOperations {
    protected $CI;

    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
        $this->CI->load->dbforge();
        $this->checktable();
    }

    public function checktable() {
        if ($this->CI->db->table_exists('db_operations')) {
            //echo "Table exists!";
            $this->checkcolumns();
        } else {
            $query="CREATE TABLE `".TP."db_operations` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `operation` varchar(50) NOT NULL,
                     `table_name` varchar(100) NOT NULL,
                     `primary_key` varchar(255) NOT NULL,
                     `data` text NOT NULL,
                     `ref` varchar(100) NOT NULL,
                     `user_id` int(11) DEFAULT NULL,
                     `added_on` datetime NOT NULL,
                     PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $this->CI->db->query($query);
        }
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

    public function get_column_attributes($table, $column) {
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
    public function log_update($table, $data, $where, $ref) {
        $updatedata=array();
        $array=$this->CI->db->get_where($table,$where)->unbuffered_row('array');
        $id=$array['id'];
        if(!empty($id)){
            $intersect=array_intersect_assoc($array,$data);

            $changes = array_diff_key($data, $intersect);
            if(!empty($changes)){
                foreach($changes as $column=>$value){
                    if(empty($array[$column])){
                        if(empty($data[$column])){
                            continue;
                        }
                    }
                    if($array[$column]!=$data[$column]){
                        $updatedata['old'][$column]=$array[$column];
                        $updatedata['new'][$column]=$data[$column];
                    }
                }
            }
            if(!empty($updatedata)){
                $this->log('update',$table,$id,$updatedata,$ref);
            }
        }
    }

    public function log($operation, $table, $primaryKey, $updatedata, $ref) {
        $user=$this->getuser();
        $data = [
            'operation' => $operation,
            'table_name' => $table,
            'primary_key' => $primaryKey,
            'data' => json_encode($updatedata),
            'ref' => json_encode($ref),
            'user_id' =>$user['id'], 
            'added_on' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert('db_operations', $data);
    }
    
    public function getuser() {
        $user=array('id'=>0);
        if(function_exists('getuser')){
            $user=getuser();
        }
        else{
            $this->CI->account->account->getuser(array("md5(id)"=>$CI->session->user));
            if($getuser['status']==true){
                $user=$geuser['user'];
            }
        }
        return $user;
    }
    
    public function getcolumnattributes($type){
        $json='{"INT":{"type":"INT","constraint":11,"unsigned":true,"null":false,"auto_increment":true},"TINYINT":{"type":"TINYINT","constraint":4,"unsigned":true,"null":false,"default":0},"SMALLINT":{"type":"SMALLINT","constraint":6,"unsigned":true,"null":false,"default":0},"MEDIUMINT":{"type":"MEDIUMINT","constraint":9,"unsigned":true,"null":false,"default":0},"BIGINT":{"type":"BIGINT","constraint":20,"unsigned":true,"null":false,"default":0},"FLOAT":{"type":"FLOAT","constraint":"5,2","unsigned":true,"null":false,"default":0},"DOUBLE":{"type":"DOUBLE","constraint":"16,8","unsigned":true,"null":false,"default":0},"DECIMAL":{"type":"DECIMAL","constraint":"10,2","null":false,"default":0},"CHAR":{"type":"CHAR","constraint":10,"null":false,"default":""},"VARCHAR":{"type":"VARCHAR","constraint":255,"null":true,"default":""},"TEXT":{"type":"TEXT","null":true},"MEDIUMTEXT":{"type":"MEDIUMTEXT","null":true},"LONGTEXT":{"type":"LONGTEXT","null":true},"DATE":{"type":"DATE","null":true},"DATETIME":{"type":"DATETIME","null":true},"TIMESTAMP":{"type":"TIMESTAMP","null":true,"default":"CURRENT_TIMESTAMP"},"TIME":{"type":"TIME","null":true},"YEAR":{"type":"YEAR","null":true},"ENUM":{"type":"ENUM","constraint":["value1","value2","value3"],"null":true},"SET":{"type":"SET","constraint":["value1","value2","value3"],"null":true},"JSON":{"type":"JSON","null":true}}';
        
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
