<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBOperations
Description : DBOperations for Codeigniter 3
Version : v0.23
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
            if(!defined('DELETE_OPERATIONS') || DELETE_OPERATIONS===true){
                $this->checkolddata();
            }
        } else {
            $query="CREATE TABLE `".TP."db_operations` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `operation` varchar(50) NOT NULL,
                     `table_name` varchar(100) NOT NULL,
                     `primary_key` varchar(255) NOT NULL,
                     `data` text NOT NULL,
                     `ref` varchar(100) NOT NULL,
                     `user_id` int(11) DEFAULT NULL,
                     `parent_id` int(11) DEFAULT NULL,
                     `added_on` datetime NOT NULL,
                     PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $this->CI->db->query($query);
        }
    }

    public function checkolddata() {
        $sql = "SELECT 
            table_name AS `Table`, 
            ROUND((data_length + index_length) / 1024 / 1024, 2) AS `Size_MB` 
                FROM information_schema.TABLES 
                WHERE table_schema = '".DB_NAME."' 
                AND table_name = '".TP."db_operations'";

        $query=$this->CI->db->query($sql);
        if($query->num_rows()>0){
            $data=$query->unbuffered_row('array');
            if($data['Size_MB']>5 || $this->CI->db->count_all('db_operations')>2000){
                $where=["date(added_on)<"=>date('Y-m-d',strtotime('-1 month'))];
                $array=$this->CI->db->get_where('db_operations',$where)->result_array();
                $data=json_encode($array);
                $filename='db_operation_data-'.date('y-m-d-h-i-s').'.json';
                $root='./application/logs/db_log/';
                if(!is_dir($root)){
                    mkdir($root);
                }
                $file=$root.$filename;
                if (!file_exists($file)) {
                    // echo "File does not exist.";
                } elseif (!is_readable($file)) {
                    // echo "File is not readable.";
                } elseif (!is_writable($file)) {
                    // echo "File is not writable.";
                } else {
                    $fh=fopen($root.$filename,'w');
                    fwrite($fh,$data);
                    fclose($fh);
                    $this->CI->db->delete('db_operations',$where);
                }
            }
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
        $columns[]=array('name'=>'user_id','attributes'=>array('type'=>'INT','null'=>true));
        $columns[]=array('name'=>'parent_id','attributes'=>array('type'=>'INT','null'=>true));
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
    
    public function log_update($table, $data, $where, $ref,$parent_id=NULL) {
        $updatedata=array();
        $array=$this->CI->db->get_where($table,$where)->result_array();
        if(!empty($array)){
            foreach($array as $single){
                $id=$single['id'];
                if(!empty($id)){
                    $intersect=array_intersect_assoc($single,$data);

                    $changes = array_diff_key($data, $intersect);
                    if(!empty($changes)){
                        foreach($changes as $column=>$value){
                            if(empty($single[$column])){
                                if(empty($data[$column])){
                                    continue;
                                }
                            }
                            if($single[$column]!=$data[$column]){
                                $updatedata['old'][$column]=$single[$column];
                                $updatedata['new'][$column]=$data[$column];
                            }
                        }
                    }
                    if(!empty($updatedata)){
                        $result=$this->log('update',$table,$id,$updatedata,$ref,$parent_id);
                        if($result['status']===true){
                            $parent_id=$parent_id===NULL?$result['parent_id']:$parent_id;
                        }
                    }
                }
            }
        }
        return $parent_id;
    }

    public function log_delete($table, $where, $ref,$parent_id=NULL) {
        $array=$this->CI->db->get_where($table,$where)->result_array();
        if(!empty($array)){
            foreach($array as $single){
                $id=$single['id'];
                $result=$this->log('delete',$table,$id,$single,$ref,$parent_id);
                if($result['status']===true){
                    $parent_id=$parent_id===NULL?$result['parent_id']:$parent_id;
                }
            }
        }
        return $parent_id;
    }

    public function log($operation, $table, $primaryKey, $updatedata, $ref,$parent_id=NULL) {
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
        if(!empty($parent_id)){
            $data['parent_id']=$parent_id;
        }
        //print_pre($data);
        $result=$this->CI->db->insert('db_operations', $data);
        if($result){
            return array('status'=>true,'parent_id'=>$this->CI->db->insert_id());
        }
        else{
            return array('status'=>false);
        }
    }
    
    public function getuser() {
        $user=array('id'=>0);
        if(function_exists('getuser')){
            $user=getuser(false);
        }
        else{
            $getuser=$this->CI->account->getuser(array("md5(id)"=>$this->CI->session->user));
            if($getuser['status']==true){
                $user=$getuser['user'];
            }
        }
        if(empty($user) && method_exists($this->CI, 'post') && !empty($this->CI->post('token'))){
            $token=$this->CI->post('token');
            $getuserid=$this->CI->db->get_where('tokens',['token'=>$token,'status'=>1]);
            if($getuserid->num_rows()>0){
                $user=array('id'=>$getuserid->unbuffered_row()->user_id);
            }
        }
        if(empty($user)){
            $user['id']=-1;
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
