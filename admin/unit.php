<?php 
ob_start();
session_start();
include ("../_init.php");

//Redirigir, si el usuario no ha iniciado sesión
if (!is_loggedin()) {
  redirect(root_url() . '/index.php?redirect_to=' . url());
}

// Redirigir, si el usuario no tiene permiso de lectura
if (user_group_id() != 1 && !has_permission('access', 'read_unit')) {
  redirect(root_url() . '/'.ADMINDIRNAME.'/dashboard.php');
}

// Establecer título del documento
$document->setTitle(trans('title_unit'));

// Agregar script
$document->addScript('../assets/itsolution24/angular/controllers/UnitController.js');

// Incluir encabezado y pie de página
include("header.php"); 
include ("left_sidebar.php") ;
?>

<!--Inicio del contenedor de contenido-->
<div class="content-wrapper" ng-controller="UnitController">

  <!--Inicio del encabezado de contenido-->
  <section class="content-header">
    <h1>
      <?php echo trans('text_unit_title'); ?>
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
        <?php echo trans('text_unit_title'); ?>
      </li>
    </ol>
  </section>
  <!--Fin del encabezado de contenido-->

  <!-- Inicio de contenido -->
  <section class="content">

    <?php if(DEMO) : ?>
    <div class="box">
      <div class="box-body">
        <div class="alert alert-info mb-0">
          <p><span class="fa fa-fw fa-info-circle"></span> <?php echo $demo_text; ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <?php if (user_group_id() == 1 || has_permission('access', 'create_unit')) : ?>
      <div class="box box-info<?php echo create_box_state(); ?>">
        <div class="box-header with-border">
          <h3 class="box-title">
            <span class="fa fa-fw fa-plus"></span> <?php echo trans('text_new_unit_title'); ?>
          </h3>
          <button type="button" class="btn btn-box-tool add-new-btn" data-widget="collapse" data-collapse="true">
            <i class="fa <?php echo !create_box_state() ? 'fa-minus' : 'fa-plus'; ?>"></i>
          </button>
        </div>
        <?php if (isset($error_message)): ?>
          <div class="alert alert-danger">
              <p>
                <span class="fa fa-warning"></span> 
                <?php echo $error_message ; ?>
              </p>
          </div>
        <?php elseif (isset($success_message)): ?>
          <div class="alert alert-success">
              <p>
                <span class="fa fa-check"></span> 
                <?php echo $success_message ; ?>
              </p>
          </div>
        <?php endif; ?>

        <!-- Add Unit Create Form -->
        <?php include('../_inc/template/unit_create_form.php'); ?>
        
      </div>
    <?php endif; ?>

    <div class="row">

      <!-- Unit List Start -->
      <div class="col-xs-12">
        <div class="box box-success">
          <div class="box-header">
            <h3 class="box-title">
              <?php echo trans('text_unit_list_title'); ?>
            </h3>
          </div>
          <div class="box-body">
            <div class="table-responsive">  
              <?php
                  $hide_colums = "";
                  if (user_group_id() != 1) {
                    if (! has_permission('access', 'update_unit')) {
                      $hide_colums .= "5,";
                    }
                    if (! has_permission('access', 'delete_unit')) {
                      $hide_colums .= "6,";
                    }
                  }
                ?>  
              <table id="unit-unit-list" class="table table-bordered table-striped table-hover" data-hide-colums="<?php echo $hide_colums; ?>">
                <thead>
                  <tr class="bg-gray">
                    <th class="w-5" >
                      <?php echo sprintf(trans('label_id'), null); ?>
                    </th>
                    <th class="w-30" >
                      <?php echo trans('label_unit_name'); ?>
                    </th>
                    <th class="w-5">
                      <?php echo trans('label_product'); ?>
                    </th>
                    <th class="w-40">
                      <?php echo trans('label_unit_details'); ?>
                     </th>
                     <th class="w-10">
                      <?php echo trans('label_status'); ?>
                     </th>
                    <th class="w-5">
                      <?php echo trans('label_edit'); ?>
                    </th>
                    <th class="w-5">
                      <?php echo trans('label_delete'); ?>
                    </th>
                  </tr>
                </thead>
                <tfoot>
                  <tr class="bg-gray">
                    <th>
                      <?php echo sprintf(trans('label_id'), null); ?>
                    </th>
                    <th>
                      <?php echo trans('label_unit_name'); ?>
                    </th>
                    <th>
                      <?php echo trans('label_product'); ?>
                    </th>
                    <th>
                      <?php echo trans('label_unit_details'); ?>
                     </th>
                     <th>
                      <?php echo trans('label_status'); ?>
                     </th>
                    <th>
                      <?php echo trans('label_edit'); ?>
                    </th>
                    <th>
                      <?php echo trans('label_delete'); ?>
                    </th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- Unit List End -->
    </div>
  </section>
  <!-- Fin del contenido -->
  
</div>
<!-- Fin del contenedor de contenido -->

<?php include ("footer.php"); ?>