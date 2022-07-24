<?php
namespace Core2\Mod\Invoices\Index;

/**
 *
 */
class Model extends \Common {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    private static $db_ext;


    /**
     * @var \HomeInvoice\Influx
     */
    private static $influx;


    /**
     * @param array $invoice_data
     * @return void
     * @throws \Zend_Config_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Exception
     */
    public function sendMysql(array $invoice_data) {

        $transform = new \HomeInvoice\Transform($invoice_data);
        $data      = $transform->getData();

        $date_invoice = $data['simple']['date_invoice']['value']
            ? date('Y-m-d H:i:s', strtotime($data['simple']['date_invoice']['value']))
            : null;


        $date_invoice_created = $data['simple']['date_created']['value']
            ? date('Y-m-d H:i:s', strtotime($data['simple']['date_created']['value']))
            : null;

        $db_ext = $this->getDbExt();

        $where   = [];
        $where[] = $this->db->quoteInto('date_invoice = ?', $date_invoice);
        $where[] = $this->db->quoteInto('address = ?', $data['simple']['address']['value'] ?: '');
        $db_ext->delete('invoices', $where);

        $db_ext->insert('invoices', [
            'date_invoice'           => $date_invoice,
            'date_invoice_created'   => $date_invoice_created,

            'address'                => $data['simple']['address']['value'] ?: '',
            'payer_name'             => $data['simple']['payer_name']['value'] ?: '',
            'personal_account'       => $data['simple']['personal_account']['value'] ?: '',
            'total_accrued'          => $data['simple']['total_accrued']['value'] ?: 0,
            'total_price'            => $data['simple']['total_price']['value'] ?: 0,
            'cold_water_count'       => $data['simple']['cold_water_count']['value'] ?: 0,
            'cold_water_diff'        => $data['simple']['cold_water_diff']['value'] ?: 0,
            'hot_water_count'        => $data['simple']['hot_water_count']['value'] ?: 0,
            'hot_water_diff'         => $data['simple']['hot_water_diff']['value'] ?: 0,
            'house_square'           => $data['simple']['house_square']['value'] ?: 0,
            'house_sub_square'       => $data['simple']['house_sub_square']['value'] ?: 0,
            'house_people'           => $data['simple']['house_people']['value'] ?: 0,
            'house_people_energy'    => $data['simple']['house_people_energy']['value'] ?: 0,
            'house_people_other'     => $data['simple']['house_people_other']['value'] ?: 0,
            'house_hot_water_count'  => $data['simple']['house_hot_water_count']['value'] ?: 0,
            'house_hot_water_cal'    => $data['simple']['house_hot_water_cal']['value'] ?: 0,
            'house_cold_water_count' => $data['simple']['house_cold_water_count']['value'] ?: 0,
            'house_energy'           => $data['simple']['house_energy']['value'] ?: 0,
            'house_energy_lift'      => $data['simple']['house_energy_lift']['value'] ?: 0,
            'invoice_data'           => json_encode($invoice_data),
        ]);

        $invoice_id = $db_ext->lastInsertId();

        if ( ! empty($data['services'])) {
            foreach ($data['services'] as $service) {
                $db_ext->insert('invoices_services', [
                    'invoice_id'    => $invoice_id,

                    'title'         => $service['title'],
                    'unit'          => $service['unit'],
                    'volume'        => $service['volume'] ?: 0,
                    'rate'          => $service['rate'] ?: 0,
                    'accrued'       => $service['accrued'] ?: 0,
                    'privileges'    => $service['privileges'] ?: 0,
                    'recalculation' => $service['recalculation'] ?: 0,
                    'total'         => $service['total'] ?: 0,
                ]);
            }
        }

        if ( ! empty($data['services_extra'])) {
            foreach ($data['services_extra'] as $service) {
                $db_ext->insert('invoices_services_extra', [
                    'invoice_id' => $invoice_id,

                    'title' => $service['title'],
                    'value' => $service['value'] ?: 0,
                ]);
            }
        }
    }


    /**
     * @param array $invoice_data
     * @return void
     * @throws \Zend_Config_Exception
     */
    public function sendInfluxDb(array $invoice_data) {

        if (empty(self::$influx)) {
            $config = $this->getModuleConfig('invoices');
            self::$influx = new \HomeInvoice\Influx([
                "url"    => $config->influx->url,
                "token"  => $config->influx->token,
                "bucket" => $config->influx->bucket,
                "org"    => $config->influx->org,
            ]);
        }

        self::$influx->sendInfluxDB2($invoice_data);
    }


    /**
     * @return \Zend_Db_Adapter_Abstract
     * @throws \Zend_Config_Exception
     * @throws \Zend_Db_Exception
     */
    private function getDbExt(): \Zend_Db_Adapter_Abstract {

        if (empty(self::$db_ext)) {
            $config       = $this->getModuleConfig('invoices');
            self::$db_ext = $this->getConnection(new \Zend_Config([
                "adapter" => 'Pdo_Mysql',
                "params" => [
                    "host"     => $config->mysql->host,
                    "dbname"   => $config->mysql->dbname,
                    "username" => $config->mysql->username,
                    "password" => $config->mysql->password,
                ]
            ]));
        }

        return self::$db_ext;
    }
}
