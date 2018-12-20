<?php
require_once 'BuilderTender.php';
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.12.18
 * Time: 14:14
 */

class ConstructorXmlTenders
{

    /**
     * ConstructorXmlTenders constructor.
     */
    private $pdo;
    private $query_string;
    private $result_string;

    public function __construct($pdo, $query_string)
    {
        $this->pdo = $pdo;
        $this->query_string = $query_string;
    }

    /**
     *
     */
    public function ConstructorXml() : void
    {
        $query = $this->pdo->prepare('SET SQL_BIG_SELECTS=1');
        $query->execute();
        $query = $this->pdo->prepare($this->query_string);
        $query->execute();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $builder = new BuilderTender($row, $this->pdo);
            $builder->BuildTender();
            $this->result_string .= $builder->ReturnTender();
        }
    }

    public function ReturnResult()  : array
    {
        $this->result_string = '<?xml version="1.0" encoding="utf-8"?>
<tenders>
    ' . $this->result_string . '
</tenders>';
        $file_name = 'Result_GetTendersByFilter_'.random_int (100,50000).'.xml';
        file_put_contents ('..'.$file_name , $this->result_string);
        $result = array(
            'FileResult' 		=> $file_name,
            'FileResult_size'	=> '0'
        );
        return $result;
    }

}