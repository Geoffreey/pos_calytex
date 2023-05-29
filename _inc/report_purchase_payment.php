<?php 
ob_start();
session_start();
include ("../_init.php");

// Verifique, si ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, devuelva un mensaje de alerta
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, devuelva un mensaje de alerta
if (user_group_id() != 1 && !has_permission('access', 'read_purchase_payment_report')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

$store_id = store_id();

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$where_query = "purchase_payments.store_id = '$store_id' AND is_hide != 1";
$from = from();
$to = to();
$where_query .= date_range_purchase_payments_filter($from, $to);

// Tabla de base de datos que se va a utilizar
$table = "(SELECT purchase_payments.*, SUM(amount) as totalAmount FROM purchase_payments 
        WHERE $where_query GROUP BY `invoice_id`) as purchase_payments";

//  Llave principal de la tabla
$primaryKey = 'id';
$columns = array(
  array( 'db' => 'id', 'dt' => 'id' ),
  array( 'db' => 'created_at', 'dt' => 'created_at' ),
  array( 
      'db' => 'type',  
      'dt' => 'type',
      'formatter' => function( $d, $row ) {
        return '<span class="label label-warning">'.ucfirst(str_replace('_',' ',$row['type'])).'</span>';
      }
    ),
  array( 'db' => 'invoice_id', 'dt' => 'ref_no' ),
  array( 'db' => 'details', 'dt' => 'details' ),
  array( 
    'db' => 'pmethod_id',   
    'dt' => 'pmethod_name' ,
    'formatter' => function($d, $row) {
      $o = '<b>'.get_the_pmethod($row['pmethod_id'], 'name').'</b>';
      $details = unserialize($row['details']);
      if (!empty($details)) {
        $o .= '<ul>';
        foreach ($details as $key => $value) {
          $o .= '<li>'. str_replace('_',' ', strtoupper($key)) . ' = '.$value.'</li>';
        }
        $o .= '</ul>';
      }
      return $o;
    }
  ),
  array( 
      'db' => 'note',  
      'dt' => 'note',
      'formatter' => function( $d, $row ) {
        return $row['note'];
      }
    ),
  array( 
      'db' => 'totalAmount',  
      'dt' => 'amount',
      'formatter' => function( $d, $row ) {
        return currency_format($row['totalAmount']);
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