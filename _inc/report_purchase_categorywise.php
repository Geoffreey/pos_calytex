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
if (user_group_id() != 1 && !has_permission('access', 'read_purchase_report')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$where_query = "purchase_info.inv_type != 'expense' AND purchase_item.store_id = " . store_id();
$from = from();
$to = to();
$where_query .= date_range_filter2($from, $to);

// Tabla de base de datos que se va a utilizar
$table = "(SELECT purchase_info.*, categorys.category_name, purchase_item.id, purchase_item.category_id, purchase_item.item_quantity, SUM(purchase_item.item_total) as purchase_price, SUM(purchase_item.item_quantity) as total_stock, SUM(purchase_price.paid_amount) as paid_amount FROM purchase_item 
      LEFT JOIN categorys ON (purchase_item.category_id = categorys.category_id)
      LEFT JOIN purchase_info ON (purchase_item.invoice_id = purchase_info.invoice_id)
      LEFT JOIN purchase_price ON (purchase_item.invoice_id = purchase_price.invoice_id)
      WHERE $where_query
      GROUP BY purchase_item.category_id
      ORDER BY total_stock DESC) as purchase_item";

//  Llave principal de la tabla
$primaryKey = 'id';
$columns = array(
    array( 'db' => 'category_id', 'dt' => 'category_id' ),
    array( 
      'db' => 'created_at',  
      'dt' => 'created_at',
      'formatter' => function( $d, $row ) {
        return date('Y-m-d', strtotime($row['created_at']));
      }
    ),
    array( 
      'db' => 'category_name',  
      'dt' => 'category_name',
      'formatter' => function( $d, $row ) {
        return $row['category_name'];
      }
    ),
    array( 
      'db' => 'total_stock',  
      'dt' => 'total_item',
      'formatter' => function( $d, $row ) {
        return currency_format($row['total_stock']);
      }
    ),
    array( 
      'db' => 'purchase_price',  
      'dt' => 'purchase_price',
      'formatter' => function( $d, $row ) {
        $total = $row['purchase_price'];
        return currency_format($total);
      }
    ),
    array( 
      'db' => 'paid_amount',  
      'dt' => 'paid_amount',
      'formatter' => function( $d, $row ) {
        $total = $row['paid_amount'];
        return currency_format($total);
      }
    )
);
 
echo json_encode(
    SSP::simple( $request->get, $sql_details, $table, $primaryKey, $columns )
);

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */