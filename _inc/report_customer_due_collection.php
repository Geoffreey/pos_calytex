<?php 
ob_start();
session_start();
include ("../_init.php");

// Comprobar, si el usuario ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, devuelva un mensaje de alerta
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, devuelva un mensaje de alerta
if (user_group_id() != 1 && !has_permission('access', 'read_customer_due_collection_report')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

$store_id = store_id();

// Fetch invoice 
if ($request->server['REQUEST_METHOD'] == 'GET' && isset($request->get['invoice_id']))
{
    try {

        if (empty($request->get['invoice_id'])) {
            throw new Exception(trans('error_invoice_id'));
        }

        $invoice_id = $request->get['invoice_id'];

        // Fetch invoice info
        $statement = db()->prepare("SELECT payments.* FROM `payments` 
            LEFT JOIN `selling_price` ON (`payments`.`invoice_id` = `selling_price`.`invoice_id`) 
            WHERE `payments`.`invoice_id` = ? AND `payments`.`store_id` = ?");
        $statement->execute(array($invoice_id, $store_id));
        $invoice = $statement->fetch(PDO::FETCH_ASSOC);
        if (empty($invoice)) {
            throw new Exception(trans('error_payments_not_found'));
        }
        
        // Fetch invoice item
        $statement = db()->prepare("SELECT * FROM `selling_item` WHERE invoice_id = ?");
        $statement->execute(array($invoice_id));
        $selling_items = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (empty($selling_items)) {
            throw new Exception(trans('error_selling_item'));
        }

        $invoice['items'] = $selling_items;

        header('Content-Type: application/json');
        echo json_encode(array('msg' => trans('text_success'), 'invoice' => $invoice));
        exit();

    }
    catch(Exception $e) { 

        header('HTTP/1.1 422 Unprocessable Entity');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('errorMsg' => $e->getMessage()));
        exit();
    }
}

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$where_query = 'payments.store_id = ' . $store_id . ' AND payments.type = "due_paid"';

$from = from();
$to = to();
$where_query .= date_range_sell_payments_filter($from, $to);

// Tabla de base de datos que se va a utilizar
$table = "(SELECT payments.* FROM payments 
  LEFT JOIN selling_price ON (payments.invoice_id = selling_price.invoice_id) 
  WHERE $where_query) as customers";

//  Llave principal de la tabla
$primaryKey = 'id';

$columns = array(
    array( 'db' => 'created_at', 'dt' => 'created_at' ),
    array( 'db' => 'invoice_id', 'dt' => 'invoice_id' ),
    array(
        'db'        => 'pmethod_id',
        'dt'        => 'pmethod_name',
        'formatter' => function($d, $row) {
            return get_the_pmethod($row['pmethod_id'], 'name');
        }
    ),
    array(
        'db'        => 'created_by',
        'dt'        => 'created_by',
        'formatter' => function($d, $row) {
            return get_the_user($row['created_by'], 'username');
        }
    ),
    array(
        'db'        => 'amount',
        'dt'        => 'amount',
        'formatter' => function($d, $row) {
            return currency_format($row['amount']);
        }
    ),
    array(
        'db'        => 'id',
        'dt'        => 'btn_view',
        'formatter' => function($d, $row) {
            return '<a class="btn btn-sm btn-block btn-info" href="view_invoice.php?invoice_id='.$row['invoice_id'].'"><i class="fa fa-eye"></i></a>';
        }
    ),
);

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */