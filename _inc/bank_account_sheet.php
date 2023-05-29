<?php 
ob_start();
session_start();
include ("../_init.php");

// Comprobar, si el usuario ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, devuelve el error
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, devuelve el error
if (user_group_id() != 1 && !has_permission('access', 'read_bank_account_sheet')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

// MODELO DE CARGA DE CAJA 
$bank_account_model = registry()->get('loader')->model('bankaccount');

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$Hooks->do_action('Before_Showing_BankAccountSheet');

$where_query = 'ba2s.store_id = ' . store_id();
 
// Tabla de base de datos que se va a utilizar
$table = "(SELECT bank_accounts.*, ba2s.deposit, ba2s.withdraw, ba2s.transfer_from_other, ba2s.transfer_to_other, ba2s.status, ba2s.sort_order FROM bank_accounts 
  LEFT JOIN bank_account_to_store ba2s ON (bank_accounts.id = ba2s.account_id) 
  WHERE $where_query GROUP by bank_accounts.id
  ) as bank_accounts";
 
//  Llave principal de la tabla
$primaryKey = 'id';

$columns = array(
  array(
      'db' => 'id',
      'dt' => 'DT_RowId',
      'formatter' => function( $d, $row ) {
          return 'row_'.$d;
      }
  ),
  array( 'db' => 'id', 'dt' => 'id' ),
  array( 
    'db' => 'account_name',   
    'dt' => 'account_name' ,
    'formatter' => function($d, $row) {
        return $row['account_name'];
    }
  ),
  array( 
    'db' => 'deposit',   
    'dt' => 'deposit' ,
    'formatter' => function($d, $row) {
        return currency_format($row['deposit']);
    }
  ),
  array( 
    'db' => 'withdraw',   
    'dt' => 'withdraw' ,
    'formatter' => function($d, $row) {
        return currency_format($row['withdraw']);
    }
  ),
  array( 
    'db' => 'transfer_to_other',   
    'dt' => 'transfer_to_other' ,
    'formatter' => function($d, $row) {
        return currency_format($row['transfer_to_other']);
    }
  ),
  array( 
    'db' => 'transfer_from_other',   
    'dt' => 'transfer_from_other' ,
    'formatter' => function($d, $row) {
        return currency_format($row['transfer_from_other']);
    }
  ),
  array( 
    'db' => 'id',   
    'dt' => 'balance' ,
    'formatter' => function($d, $row) {
        return currency_format(($row['deposit'] + $row['transfer_from_other']) - ($row['withdraw'] + $row['transfer_to_other']));
    }
  ),
); 

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

$Hooks->do_action('After_Showing_BankAccountSheet');

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */