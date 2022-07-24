<?php
use Core2\Mod\Invoices;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';

require_once "classes/Index/Model.php";


/**
 *
 */
class ModInvoicesCli extends Common {

    /**
     * Получение данных из всех счетов
     * @throws Exception
     */
    public function parseAll() {

        $invoices = $this->db->fetchAll("
            SELECT i.id,
                   mif.content
            FROM mod_invoices AS i
                JOIN mod_invoices_files AS mif ON i.id = mif.refid
            GROUP BY i.id
        ");

        if ( ! empty($invoices)) {
            foreach ($invoices as $invoice_row) {

                $parser = new HomeInvoice\Parser($invoice_row['content']);
                $invoice_text = $parser->getText();
                $invoice_data = $parser->getData($invoice_text);

                $where = $this->db->quoteInto('id = ?', $invoice_row['id']);
                $this->db->update('mod_invoices', [
                    'title'        => $invoice_data ? $invoice_data['month_name'] . ' ' . $invoice_data['year'] : '',
                    'target'       => $invoice_data ? $invoice_data['address'] : '',
                    'date_invoice' => $invoice_data ? "{$invoice_data['year']}-{$invoice_data['month']}-01" : '',
                    'invoice_data' => $invoice_data ? json_encode($invoice_data) : '',
                    'invoice_text' => $invoice_text,
                ], $where);
            }
        }
    }


    /**
     * Отправка данных всех счетов
     * @throws Zend_Config_Exception
     */
    public function sendAll() {

        $invoices_data = $this->db->fetchCol("
            SELECT invoice_data
            FROM mod_invoices
        ");

        if ( ! empty($invoices_data)) {
            $model = new Invoices\Index\Model();

            foreach ($invoices_data as $invoice_data) {
                $model->sendInfluxDB(json_decode($invoice_data, true));
            }
        }
    }


    /**
     * Отправка данных всех счетов в Mysql
     * @throws Zend_Config_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Exception
     */
    public function sendAllMysql() {

        $invoices_data = $this->db->fetchCol("
            SELECT invoice_data
            FROM mod_invoices
        ");

        if ( ! empty($invoices_data)) {
            $model = new Invoices\Index\Model();

            foreach ($invoices_data as $invoice_data) {
                $model->sendMysql(json_decode($invoice_data, true));
            }
        }
    }
}
