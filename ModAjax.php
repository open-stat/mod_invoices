<?php
use Core2\Mod\Invoices;

require_once DOC_ROOT . "core2/inc/ajax.func.php";

require_once "classes/Index/Model.php";


/**
 * Class ModAjax
 */
class ModAjax extends ajaxFunc {


    /**
     * @param array $data
     * @return xajaxResponse
     * @throws Zend_Db_Adapter_Exception
     */
    public function axSaveInvoice(array $data): xajaxResponse {

        $fields = [
            'type' => 'req',
        ];

        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }

        $refid = $this->getSessFormField($data['class_id'], 'refid');

        $this->db->beginTransaction();
        try {
            if (isset($data['is_send_influx']) && $data['is_send_influx'] == 1) {
                $invoice_data = $this->db->fetchOne("
                    SELECT invoice_data
                    FROM mod_invoices
                    WHERE id = ?
                ", $refid);

                $model = new Invoices\Index\Model();
                $model->sendInfluxDb(json_decode($invoice_data, true));

                $this->response->script("Snarl.addNotification({title: \"Сохранено\", icon: '<i class=\"fa fa-check\"></i>', text: \"Данные отправлены!\" });");

            } elseif (isset($data['is_send_mysql']) && $data['is_send_mysql'] == 1) {
                $invoice_data = $this->db->fetchOne("
                    SELECT invoice_data
                    FROM mod_invoices
                    WHERE id = ?
                ", $refid);

                $model = new Invoices\Index\Model();
                $model->sendMysql(json_decode($invoice_data, true));

                $this->response->script("Snarl.addNotification({title: \"Сохранено\", icon: '<i class=\"fa fa-check\"></i>', text: \"Данные отправлены!\" });");

            } else {
                if (empty($refid)) {
                    $data['control']['date_created'] = new Zend_Db_Expr('NOW()');
                }

                $invoice_id  = $this->saveData($data);
                $invoice_row = $this->db->fetchRow("
                    SELECT content
                    FROM mod_invoices_files AS f
                    WHERE refid = ?
                ", $invoice_id);

                if ( ! empty($invoice_row)) {
                    $parser = new \HomeInvoice\Parser($invoice_row['content']);
                    $invoice_text = $parser->getText();
                    $invoice_data = $parser->getData($invoice_text);
                }

                $where = $this->db->quoteInto('id = ?', $invoice_id);
                $this->db->update('mod_invoices', [
                    'title'        => ! empty($invoice_data) ? $invoice_data['month_name'] . ' ' . $invoice_data['year'] : '',
                    'target'       => ! empty($invoice_data) ? $invoice_data['address'] : '',
                    'date_invoice' => ! empty($invoice_data) ? "{$invoice_data['year']}-{$invoice_data['month']}-01" : '',
                    'invoice_data' => ! empty($invoice_data) ? json_encode($invoice_data) : '',
                    'invoice_text' => ! empty($invoice_text) ? $invoice_text : '',
                ], $where);

                $this->response->script("Snarl.addNotification({title: \"Сохранено\", icon: '<i class=\"fa fa-check\"></i>' });");
            }


            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            $this->error[] = $e->getMessage();
        }


        $this->done($data);
        return $this->response;
    }
}
