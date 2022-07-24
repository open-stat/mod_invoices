<?php
use \Core2\Mod\Invoices;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/Index/View.php';


/**
 * Class ModInvoicesController
 */
class ModInvoicesController extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_index() {

        $app     = 'index.php?module=invoices';
        $panel   = new Panel('tab');
        $content = [];

        $view = new Invoices\Index\View();


        if (isset($_GET['edit'])) {
            $panel->setTitle( ! $_GET['edit'] ? "Добавление счета" : "Редактирование", '', $app);
            $content[] = $view->getEdit($app, $_GET['edit']);

        } else {
            $content[] = $view->getList($app);
        }


        $panel->setContent(implode('', $content));
        return $panel->render();
    }
}