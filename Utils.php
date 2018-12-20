<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.12.18
 * Time: 14:53
 * @param $value
 * @return string
 */
function check_null( $value ){
    if (($value === '') || ($value === '  ')) {
        $result = '   ';
    }
    else {
        $result = $value;
    }
    return $result;
}

/**
 * @param $str
 * @return string
 */
function ecr($str ){
    $res = quotemeta ( $str );
    $res =  stripslashes($res);
    return htmlspecialchars($res);
}

/**
 * @param $row
 * @return array
 */
function filter_row($row){
    $new_row = array();
    foreach ($row as $k => $v){
        $new_row[$k] = ecr(check_null($v));
    }
    return $new_row;
}