<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBOperations
Description : DBOperations for Codeigniter 3
Version : v0.01
*/

class DBOperations {
    protected $CI;

    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
    }

    public function checktable() {
        if ($this->db->table_exists('db_operations')) {
            echo "Table exists!";
        } else {
            echo "Table does not exist!";
            echo $query="CREATE TABLE `".TP."db_operations` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `operation` varchar(50) NOT NULL,
                     `table_name` varchar(100) NOT NULL,
                     `primary_key` varchar(255) NOT NULL,
                     `data` text NOT NULL,
                     `user_id` int(11) DEFAULT NULL,
                     `added_on` datetime NOT NULL,
                     PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        }
    }

    public function log($operation, $table, $primaryKey, $data) {
        $user=$this->getuser();
        $auditData = [
            'operation' => $operation,
            'table_name' => $table,
            'primary_key' => $primaryKey,
            'data' => json_encode($data),
            'user_id' =>$user['id'], 
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert('db_operations', $auditData);
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

}
