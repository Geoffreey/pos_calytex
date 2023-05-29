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
if (user_group_id() != 1 && !has_permission('access', 'read_purchase_tax_report')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

$store_id = store_id();
$user_id = user_id();

// LOAD INVOICE MODEL
$invoice_model = registry()->get('loader')->model('invoice');


/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$Hooks->do_action('Before_Showing_purchase_Tax_Report');

$where_query = "(purchase_price.item_tax > 0 OR purchase_price.order_tax > 0) AND purchase_info.is_visible = 1 AND purchase_info.store_id = $store_id";
$from = from();
$to = to();
$where_query .= date_range_filter2($from, $to);

// Tabla de base de datos que se va a utilizar
$table = "(SELECT purchase_info.*, purchase_price.item_tax, purchase_price.order_tax FROM `purchase_info` LEFT JOIN `purchase_price` ON (purchase_info.invoice_id = purchase_price.invoice_id) WHERE $where_query) as purchase_info";

//  Llave principal de la tabla
$primaryKey = 'info_id';

$columns = array(
    array(
      'db' => 'info_id',
      'dt' => 'DT_RowId',
      'formatter' => function( $d, $row ) {
          return 'row_'.$d;
      }
    ),
    array( 
      'db' => 'created_at',   
      'dt' => 'created_at' ,
      'formatter' => function($d, $row) {
        return $row['created_at'];
      }
    ),
    array(
        'db' => 'invoice_id',
        'dt' => 'invoice_id',
        'formatter' => function( $d, $row) {
            $o = $row['invoice_id'];         
            return $o;
        }
    ),
    array('db' => 'order_tax','dt' => 'order_tax'),
    array(
        'db' => 'item_tax',
        'dt' => 'tax_amount',
        'formatter' => function($d, $row) {
            return currency_format($row['item_tax']+$row['order_tax']);
        }
    ),
);

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

$Hooks->do_action('After_Showing_purchase_Tax_Report');

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */