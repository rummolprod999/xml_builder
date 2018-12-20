<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.12.18
 * Time: 14:19
 */

abstract class AbstractBuilderTender
{
    protected $pdo;
    protected $row;

    public function __construct($row, $pdo)
    {
        $this->row = filter_row($row);
        $this->pdo = $pdo;
    }

    abstract public function BuildTender();

    abstract public function ReturnTender();

    protected function BuildVersionNotice(): string
    {
        $id_tender = (int)$this->row['id_tender'];
        $query = $this->pdo->prepare('SELECT date_version, num_version, notice_version FROM tender WHERE id_tender= :id_tender');
        $query->bindParam(':id_tender', $id_tender, PDO::PARAM_INT);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<date_version>' . $row['date_version'] . '</date_version>
        <num_version>' . $row['num_version'] . '</num_version>
        <notice_version>' . (trim($row['notice_version']) === '' ? 'Внесены изменения в документ закупки.' : $row['notice_version']) . '</notice_version>
        ';


    }

    protected function BuildPubDate(): string
    {
        $query = $this->pdo->prepare('SELECT date_version FROM tender WHERE purchase_number= :purchase_number ORDER BY UNIX_TIMESTAMP(doc_publish_date) ASC LIMIT 1');
        $query->bindParam(':purchase_number', $this->row['purchase_number'], PDO::PARAM_STR);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<pub_date>' . $row['date_version'] . '</pub_date>
        ';
    }

    protected function BuildMaxPrice(): string
    {
        $id_tender = (int)$this->row['id_tender'];
        $query = $this->pdo->prepare('SELECT SUM(max_price) AS max_price FROM lot WHERE id_tender= :id_tender');
        $query->bindParam(':id_tender', $id_tender, PDO::PARAM_INT);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<max_price>' . $row['max_price'] . '</max_price>
        ';
    }

    protected function BuildFinSource(): string
    {
        $id_tender = (int)$this->row['id_tender'];
        $query = $this->pdo->prepare('SELECT finance_source FROM lot WHERE id_tender= :id_tender AND lot_number = 1');
        $query->bindParam(':id_tender', $id_tender, PDO::PARAM_INT);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<ist_fin>' . $row['finance_source'] . '</ist_fin>
        ';
    }

    protected function BuildPlacingWay(): string
    {
        if ($this->row['id_placing_way'] === '0') {
            return '<id_placing_way>6</id_placing_way>
        ';
        }

        $id_placing_way = (int)$this->row['id_placing_way'];
        $query = $this->pdo->prepare('SELECT conformity FROM placing_way WHERE id_placing_way= :id_placing_way');
        $query->bindParam(':id_placing_way', $id_placing_way, PDO::PARAM_INT);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<id_placing_way>' . $row['conformity'] . '</id_placing_way>
        ';
    }

    protected function BuildOrganizator(): string
    {
        $id_organizer = (int)$this->row['id_organizer'];
        $query = $this->pdo->prepare('SELECT full_name, inn, kpp, contact_person, contact_email, contact_phone, contact_fax, fact_address FROM organizer WHERE id_organizer = :id_organizer');
        $query->bindParam(':id_organizer', $id_organizer, PDO::PARAM_INT);
        $query->execute();
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        return '<org_name>' . $row['full_name'] . '</org_name>
        ' . '<org_inn>' . $row['inn'] . '</org_inn>
        ' . '<org_kpp>' . $row['kpp'] . '</org_kpp>
        ' . '<org_cp>' . $row['contact_person'] . '</org_cp>
        ' . '<org_email>' . $row['contact_email'] . '</org_email>
        ' . '<org_phone>' . $row['contact_phone'] . '</org_phone>
        ' . '<org_fax>' . $row['contact_fax'] . '</org_fax>
        ' . '<org_addr>' . $row['fact_address'] . '</org_addr>
        ';
    }

    protected function BuildDocs(): string
    {
        $id_tender = (int)$this->row['id_tender'];
        $query = $this->pdo->prepare('SELECT file_name, url, description FROM attachment WHERE id_tender = :id_tender');
        $query->bindParam(':id_tender', $id_tender, PDO::PARAM_INT);
        $query->execute();
        $res = '<docs>
            ';
        if ($query->rowCount() === 0) {
            $res .= '<doc>
                <doc_name>' . 'Не прикреплено' . '</doc_name>
                ' . '<doc_url>' . '-' . '</doc_url>
                ' . '<doc_desc>' . '-' . '</doc_desc>
            </doc>
            ';
            return $res . '
        </docs>';
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= '<doc>
                <doc_name>' . $row['file_name'] . '</doc_name>
                ' . '<doc_url>' . $row['url'] . '</doc_url>
                ' . '<doc_desc>' . $row['description'] . '</doc_desc>
            </doc>
            ';
        }
        return $res . '
        </docs>';
    }

    protected function BuildLots(): string
    {
        $id_tender = (int)$this->row['id_tender'];
        $query = $this->pdo->prepare('SELECT id_lot FROM lot WHERE id_tender = :id_tender');
        $query->bindParam(':id_tender', $id_tender, PDO::PARAM_INT);
        $query->execute();
        $res = '
        <tender_lots>';
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $res .= '
            <tender_lot>';
            $res .= $this->BuildPreferences((int)$row['id_lot']);
            $res .= $this->BuildGuarantee((int)$row['id_lot']);
            $res .= $this->BuildRestricts((int)$row['id_lot']);
            $res .= $this->BuildRequirements((int)$row['id_lot']);
            $res .= $this->BuildPurObjects((int)$row['id_lot']);
            $res .= $this->BuildCustomers((int)$row['id_lot']);
            $res .= '
           </tender_lot>
        ';
        }
        return $res . '
        </tender_lots>
    ';
    }

    protected function BuildPreferences($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT name FROM preferense WHERE id_lot = :id_lot');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '';
        if ($query->rowCount() === 0) {
            $res .= '
                <preferense>' . 'Предпочтения к исполнителю по данной закупке отсутствуют.' . '</preferense>';
            return $res;
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= '
                <preferense>' . $row['name'] . '</preferense>';
        }
        return $res;
    }

    protected function BuildGuarantee($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT SUM(application_guarantee_amount) AS app_guar, SUM(contract_guarantee_amount) AS contr_guar FROM customer_requirement WHERE id_lot = :id_lot');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '';
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $res .= '
                <app_guar>' . (double)$row['app_guar'] . '</app_guar>';
        $res .= '
                <con_guar>' . (double)$row['contr_guar'] . '</con_guar>';

        return $res;
    }

    protected function BuildRestricts($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT foreign_info, info FROM restricts WHERE id_lot = :id_lot');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '
                <restrict>';
        if ($query->rowCount() === 0) {
            $res .= 'Ограничения к исполнителю по данной закупке отсутствуют.' . '</restrict>
                ';
            return $res;
        }
        $rest = '';
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $rest .= ' ' . $row['foreign_info'] . ' ' . $row['info'];
        }
        $rest = trim($rest);
        if ($rest === '') {
            $res .= 'Ограничения к исполнителю по данной закупке отсутствуют.' . '</restrict>
                ';
            return $res;
        }
        return $res . $rest . '</restrict>
                ';
    }

    protected function BuildRequirements($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT name, content FROM requirement WHERE id_lot = :id_lot');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '<requirements>';
        if ($query->rowCount() === 0) {
            $res .= '
                    <requirement>
                        <name>' . 'Нет' . '</name>
                        ' . '<content>' . 'Нет ограничений' . '</content>
                    </requirement>';
            return $res . '
                </requirements>
                ';
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= '
                    <requirement>
                        <name>' . $row['name'] . '</name>
                        ' . '<content>' . $row['content'] . '</content>
                    </requirement>';
        }
        return $res . '
                </requirements>
                ';
    }

    protected function BuildPurObjects($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT okpd2_code, name, SUM(customer_quantity_value) AS sum_quant, price, okei, SUM(sum) AS sum_po, okpd_name  FROM purchase_object WHERE id_lot = :id_lot GROUP BY okpd2_code, name, price, okei');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '<lots>';
        if ($query->rowCount() === 0) {
            $res .= '
                    <lot>
                        <code>' . '-' . '</code>
                        ' . '<okpd_name>' . 'Подробнее в документации' . '</okpd_name>
                        ' . '<name>' . 'Подробнее в документации' . '</name>
                        ' . '<quantity>' . 0 . '</quantity>
                        ' . '<price>' . 0 . '</price>
                        ' . '<okei>' . '-' . '</okei>
                        ' . '<sum>' . 0 . '</sum>
                    </lot>';
            return $res . '
                </lots>
                ';
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= '
                    <lot>
                        <code>' . $row['okpd2_code'] . '</code>
                        ' . '<okpd_name>' . $row['okpd_name'] . '</okpd_name>
                        ' . '<name>' . $row['name'] . '</name>
                        ' . '<quantity>' . $row['sum_quant'] . '</quantity>
                        ' . '<price>' . $row['price'] . '</price>
                        ' . '<okei>' . $row['okei'] . '</okei>
                        ' . '<sum>' . $row['sum_po'] . '</sum>
                    </lot>';
        }
        return $res . '
                </lots>
                ';
    }

    protected function BuildCustomers($id_lot): string
    {
        $query = $this->pdo->prepare('SELECT c.id_customer, c.reg_num AS reg_num, c.full_name AS full_name, c.inn AS inn FROM customer AS c  LEFT JOIN purchase_object AS po on po.id_customer = c.id_customer WHERE po.id_lot = :id_lot GROUP BY c.id_customer');
        $query->bindParam(':id_lot', $id_lot, PDO::PARAM_INT);
        $query->execute();
        $res = '<customers>';
        if ($query->rowCount() === 0) {
            $res .= '
                    <customer>
                        <cus_name>' . 'Не определен' . '</cus_name>
                        ' . '<cus_inn>' . ' ' . '</cus_inn>
                        ' . '<cus_kpp>' . ' ' . '</cus_kpp>
                        ' . '<cus_cp>' . ' ' . '</cus_cp>
                        ' . '<cus_email>' . ' ' . '</cus_email>
                        ' . '<cus_phone>' . ' ' . '</cus_phone>
                        ' . '<cus_fax>' . ' ' . '</cus_fax>
                        ' . '<cus_addr>' . ' ' . '</cus_addr>
                    </customer>';
            return $res . '
                </customers>
                ';
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= $this->BuildOdCustomers($row['reg_num'], $row['inn'], $row['full_name']);
        }
        return $res . '
                </customers>
                ';
    }

    protected function BuildOdCustomers($reg_num, $inn, $full_name): string
    {
        $query = $this->pdo->prepare('SELECT full_name, short_name, inn, kpp, contact_name, email, phone, fax, postal_address FROM od_customer WHERE regNumber = :regNumber');
        $query->bindParam(':regNumber', $reg_num, PDO::PARAM_STR);
        $query->execute();
        $res = '';
        if ($query->rowCount() === 0) {
            return $res . $this->BuildOdCustomers223($reg_num, $inn, $full_name);
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $name_cus = ($row['short_name'] === '') ? $row['full_name'] : $row['short_name'];
            $res .= '
                    <customer>
                        <cus_name>' . $name_cus . '</cus_name>
                        ' . '<cus_inn>' . $row['inn'] . '</cus_inn>
                        ' . '<cus_kpp>' . $row['kpp'] . '</cus_kpp>
                        ' . '<cus_cp>' . $row['contact_name'] . '</cus_cp>
                        ' . '<cus_email>' . $row['email'] . '</cus_email>
                        ' . '<cus_phone>' . $row['phone'] . '</cus_phone>
                        ' . '<cus_fax>' . $row['fax'] . '</cus_fax>
                        ' . '<cus_addr>' . $row['postal_address'] . '</cus_addr>
                    </customer>';
        }
        return $res;

    }

    protected function BuildOdCustomers223($reg_num, $inn, $full_name): string
    {
        $query = $this->pdo->prepare('SELECT full_name, short_name, inn, kpp, contact_name, email, phone, fax, postal_address  FROM od_customer_from_ftp223 WHERE inn = :inn LIMIT 1');
        $query->bindParam(':inn', $inn, PDO::PARAM_STR);
        $query->execute();
        $res = '';
        if ($query->rowCount() === 0) {
            return $res . $this->BuildCustomers223($reg_num, $inn, $full_name);
        }
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        $name_cus = ($row['short_name'] === '') ? $row['full_name'] : $row['short_name'];
        $res .= '
                    <customer>
                        <cus_name>' . $name_cus . '</cus_name>
                        ' . '<cus_inn>' . $row['inn'] . '</cus_inn>
                        ' . '<cus_kpp>' . $row['kpp'] . '</cus_kpp>
                        ' . '<cus_cp>' . $row['contact_name'] . '</cus_cp>
                        ' . '<cus_email>' . $row['email'] . '</cus_email>
                        ' . '<cus_phone>' . $row['phone'] . '</cus_phone>
                        ' . '<cus_fax>' . $row['fax'] . '</cus_fax>
                        ' . '<cus_addr>' . $row['postal_address'] . '</cus_addr>
                    </customer>';

        return $res;

    }

    protected function BuildCustomers223($reg_num, $inn, $full_name): string
    {
        $query = $this->pdo->prepare('SELECT full_name, inn, kpp, contact, email, phone, fax, post_address  FROM customer223 WHERE inn = :inn LIMIT 1');
        $query->bindParam(':inn', $inn, PDO::PARAM_STR);
        $query->execute();
        $res = '';
        if ($query->rowCount() === 0) {
            return $res . $this->BuildCustomer($reg_num, $inn, $full_name);
        }
        $row = filter_row($query->fetch(PDO::FETCH_ASSOC));
        $res .= '
                    <customer>
                        <cus_name>' . $row['full_name'] . '</cus_name>
                        ' . '<cus_inn>' . $row['inn'] . '</cus_inn>
                        ' . '<cus_kpp>' . $row['kpp'] . '</cus_kpp>
                        ' . '<cus_cp>' . $row['contact'] . '</cus_cp>
                        ' . '<cus_email>' . $row['email'] . '</cus_email>
                        ' . '<cus_phone>' . $row['phone'] . '</cus_phone>
                        ' . '<cus_fax>' . $row['fax'] . '</cus_fax>
                        ' . '<cus_addr>' . $row['post_address'] . '</cus_addr>
                    </customer>';

        return $res;

    }

    protected function BuildCustomer($reg_num, $inn, $full_name): string
    {
        if ($full_name !== '') {
            $res = '';
            $res .= '
                    <customer>
                        <cus_name>' . $full_name . '</cus_name>
                        ' . '<cus_inn>' . $inn . '</cus_inn>
                        ' . '<cus_kpp>' . ' ' . '</cus_kpp>
                        ' . '<cus_cp>' . ' ' . '</cus_cp>
                        ' . '<cus_email>' . ' ' . '</cus_email>
                        ' . '<cus_phone>' . ' ' . '</cus_phone>
                        ' . '<cus_fax>' . ' ' . '</cus_fax>
                        ' . '<cus_addr>' . ' ' . '</cus_addr>
                    </customer>';
            return $res;
        }

        return $this->BuildOrganizerAsCustomer($reg_num);
    }

    protected function BuildOrganizerAsCustomer($reg_num): string
    {
        $query = $this->pdo->prepare('SELECT full_name, inn, kpp, contact_person, contact_email, contact_phone, contact_fax, post_address  FROM organizer WHERE reg_num = :reg_num');
        $query->bindParam(':reg_num', $reg_num, PDO::PARAM_STR);
        $query->execute();
        $res = '';
        if ($query->rowCount() === 0) {
            $res .= '
                    <customer>
                        <cus_name>' . 'Не определен' . '</cus_name>
                        ' . '<cus_inn>' . ' ' . '</cus_inn>
                        ' . '<cus_kpp>' . ' ' . '</cus_kpp>
                        ' . '<cus_cp>' . ' ' . '</cus_cp>
                        ' . '<cus_email>' . ' ' . '</cus_email>
                        ' . '<cus_phone>' . ' ' . '</cus_phone>
                        ' . '<cus_fax>' . ' ' . '</cus_fax>
                        ' . '<cus_addr>' . ' ' . '</cus_addr>
                    </customer>';
            return $res;
        }
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $row = filter_row($row);
            $res .= '
                    <customer>
                        <cus_name>' . $row['full_name'] . '</cus_name>
                        ' . '<cus_inn>' . $row['inn'] . '</cus_inn>
                        ' . '<cus_kpp>' . $row['kpp'] . '</cus_kpp>
                        ' . '<cus_cp>' . $row['contact_person'] . '</cus_cp>
                        ' . '<cus_email>' . $row['contact_email'] . '</cus_email>
                        ' . '<cus_phone>' . $row['contact_phone'] . '</cus_phone>
                        ' . '<cus_fax>' . $row['contact_fax'] . '</cus_fax>
                        ' . '<cus_addr>' . $row['postal_address'] . '</cus_addr>
                    </customer>';
        }
        return $res;
    }
}