<?php
namespace Core2\Mod\Invoices\Index;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/class.list.php";


/**
 *
 */
class View extends \Common {


    /**
     * @param $app
     * @return false|string
     * @throws \Exception
     */
    public function getList($app) {

        $list = new \listTable($this->resId);

        $list->filterColumn = true;
        $list->showTemplates();

        $list->addSearch("Название", 'i.title', 'TEXT');

        $list->SQL = "
            SELECT i.id,
                   i.type,
                   i.title,
                   i.target,
                   i.date_invoice,
                   i.date_created
            FROM mod_invoices AS i
            WHERE 1=1 /*ADD_SEARCH*/
            ORDER BY date_invoice DESC
        ";

        $list->addColumn("Вид счета",        "110", "TEXT");
        $list->addColumn("Название",         "150", "TEXT");
        $list->addColumn("Цель",             "",    "TEXT");
        $list->addColumn("Дата выставления", "140", "DATE");
        $list->addColumn("Дата создания",    "140", "DATETIME");

        $list->addURL    = "{$app}&edit=0";
        $list->editURL   = "{$app}&edit=TCOL_00";
        $list->deleteKey = "mod_invoices.id";

        $types = [
            'house_invoice'  => 'Жировка',
            'membership_fee' => 'Членский взнос',
            'other'          => 'Другое',
        ];

        $list->getData();
        foreach ($list->data as $key => $row) {

            // Вид счета
            if (isset($types[$row[1]])) {
                $list->data[$key][1] = $types[$row[1]];
            }
        }

        return $list->render();
    }


    /**
     * @param $app
     * @param $invoice_id
     * @return false|string
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Exception
     */
    public function getEdit($app, $invoice_id) {

        $edit = new \editTable($this->resId);

        $edit->SQL = $this->db->quoteInto("
            SELECT id,
                   type,
                   'invoice',
                   title,
                   target,
                   date_invoice,
                   invoice_data
            FROM mod_invoices
            WHERE id = ?
        ", $invoice_id);

        $invoice      = $this->db->fetchRow($edit->SQL);
        $date_invoice = isset($invoice['date_invoice']) ? date('d.m.Y', strtotime($invoice['date_invoice'])) : '';
        $json_text    = isset($invoice['invoice_data']) ? print_r(json_decode($invoice['invoice_data'], true), true) : '';

        $types = [
            'house_invoice'  => 'Жировка',
            'membership_fee' => 'Членский взнос',
            'other'          => 'Другое',
        ];

        $edit->addControl('Вид счета',     "LIST",       'style="width:200px;"', '', '', true); $edit->selectSQL[] = $types;
        $edit->addControl('Исходный файл', "XFILE_AUTO", '', '', '', true);

        if ($invoice_id) {
            $edit->addControl('Название',            "PROTECTED");
            $edit->addControl('Цель',                "PROTECTED");
            $edit->addControl('Дата выставления',    "CUSTOM", $date_invoice);
            $edit->addControl('Обработанные данные', "CUSTOM", "<pre style=\"max-height: 600px;max-width: 900px;\">{$json_text}</pre>");

            $edit->addParams("is_send_influx");
            $edit->addParams("is_send_mysql");
            $edit->addButtonCustom("<button type=\"button\" class=\"btn btn-sm btn-warning\" onclick=\"$('[name=is_send_influx]').val(1);this.form.onsubmit();\">Отправить в Influx</button> ");
            $edit->addButtonCustom("<button type=\"button\" class=\"btn btn-sm btn-warning\" onclick=\"$('[name=is_send_mysql]').val(1);this.form.onsubmit();\">Отправить в Mysql</button>");
        }

        $edit->firstColWidth     = "150px";
        $edit->back              = $app;
        $edit->save("xajax_saveInvoice(xajax.getFormValues(this.id))");

        return $edit->render();
    }
}
