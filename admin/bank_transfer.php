<?php 
ob_start();
session_start();
include ("../_init.php");

// redirect, if user not logged in
if (!is_loggedin()) {
  redirect(store('base_url') . '/index.php?redirect_to=' . url());
}

// redirect, user haven't read permission
if (user_group_id() != 1 && !has_permission('access', 'read_bank_transfer')) {
  redirect(store('base_url') . '/admin/dashboard.php');
}

// Establecer título del documento
$document->setTitle(trans('title_bank_transfer'));

// Agregar script
$document->addScript('../assets/itsolution24/angular/controllers/BankTransferController.js');

include("header.php"); 
include ("left_sidebar.php") ;
?>

<!--Inicio del contenedor de contenido-->
<div class="content-wrapper">

  <!--Inicio del encabezado de contenido-->
  <section class="content-header" ng-controller="BankTransferController">
    <?php include ("../_inc/template/partials/apply_filter.php"); ?>
    <h1>
      <?php echo trans('text_bank_transfer_title'); ?>
      <small>
        <?php echo store('name'); ?>
      </small>
    </h1>
    <ol class="breadcrumb">
      <li>
        <a href="dashboard.php">
          <i class="fa fa-dashboard"></i> 
          <?php echo trans('text_dashboard'); ?>
        </a>
      </li>
      <li class="active">
        <?php echo trans('text_banking_title'); ?>
      </li>
    </ol>
  </section>
  <!--Fin del encabezado de contenido-->

  <!-- Inicio de contenido -->
  <section class="content">
    <div class="row">
      <!-- banking list section start-->
      <div class="col-xs-12">
        <div class="box box-success">
          <div class="box-header">
            <h3 class="box-title">
              <?php echo trans('text_list_bank_transfer_title'); ?>
            </h3>
          </div>
          <div class='box-body'>     
            <?php
              $hide_colums = "";
            ?> 
            <div class="table-responsive">                     
              <table id="invoice-invoice-list" class="table table-bordered table-striped table-hovered"data-hide-colums="<?php echo $hide_colums; ?>">
                <thead>
                  <tr class="bg-gray">
                    <th class="w-15">
                      <?php echo sprintf(trans('label_id'),null); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_date'); ?>
                    </th>
                    <th class="w-25">
                      <?php echo trans('label_from_account'); ?>
                    </th>
                    <th class="w-25">
                      <?php echo trans('label_to_account'); ?>
                    </th>
                    <th class="w-20">
                      <?php echo trans('label_amount'); ?>
                    </th>
                  </tr>
                </thead>
                <tfoot>
                  <tr class="success">
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>    
            </div>
          </div>
        </div>
      </div>
       <!-- banking list section end-->
    </div>
  </section>
  <!-- Fin del contenido -->
</div>
<!-- Fin del contenedor de contenido -->

<?php include ("footer.php"); ?>