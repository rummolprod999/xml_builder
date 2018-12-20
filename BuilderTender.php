<?php
require_once 'AbstractBuilderTender.php';
require_once 'Utils.php';

/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.12.18
 * Time: 14:40
 */
class BuilderTender extends AbstractBuilderTender
{

    private $result_string;

    /**
     * BuilderTender constructor.
     */
    public function __construct($row, $pdo)
    {
        parent::__construct($row, $pdo);
        $this->result_string = '';
    }

    public function BuildTender(): void
    {
        $this->result_string = '
        <id_tender>' . $this->row['id_tender'] . '</id_tender>
        <name>' . $this->row['purchase_object_info'] . '</name>
        <number>' . $this->row['purchase_number'] . '</number>
        <url>' . $this->row['href'] . '</url>
        <id_region>' . $this->row['id_region'] . '</id_region>
        <pub_date1>' . $this->row['doc_publish_date'] . '</pub_date1>
        <end_date>' . $this->row['end_date'] . '</end_date>
        <id_etp>' . $this->row['id_etp'] . '</id_etp>
        <scoring_date>' . $this->row['scoring_date'] . '</scoring_date>
        <bidding_date>' . $this->row['bidding_date'] . '</bidding_date>
        <fz_type>' . $this->row['type_fz'] . '</fz_type>
        ' . $this->BuildVersionNotice()
            . $this->BuildPubDate()
            . $this->BuildMaxPrice()
            . $this->BuildFinSource()
            . $this->BuildPlacingWay()
            . $this->BuildOrganizator()
            . $this->BuildDocs()
            . $this->BuildLots();
    }

    public function ReturnTender(): string
    {
        $this->result_string = '<tender>' . $this->result_string . '
    </tender>
    ';
        return $this->result_string;
    }

}