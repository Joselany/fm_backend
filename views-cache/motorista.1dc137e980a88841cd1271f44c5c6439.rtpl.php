<?php if(!class_exists('Rain\Tpl')){exit;}?><!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3>Formulário de motoqueiros</h3>
            <strong class="m-0 text-dark">Foram encontrados <?php echo htmlspecialchars( $total_motoristas, ENT_COMPAT, 'UTF-8', FALSE ); ?> registo(s)...</strong>
          </div><!-- /.col -->
          
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<div class="content">
      <div class="container-fluid">
        <div class="row">
  	<div class="col-md-12">
  		<div class="card">
            
            <div class="card-header">
              <div class="card-tools">

                <form action="http://159.89.52.185:8080/motoristas">
                  <div class="input-group input-group-md">
                    <select name="regiao" class="form-control">
                        <?php if( $regiao!='' ){ ?><option value="<?php echo htmlspecialchars( $regiao, ENT_COMPAT, 'UTF-8', FALSE ); ?>"><?php echo htmlspecialchars( $regiao, ENT_COMPAT, 'UTF-8', FALSE ); ?></option><?php }else{ ?><option value="">Todas regiões</option><?php } ?>
                        <option value="">Todas regiões</option>
                        <?php $counter1=-1;  if( isset($regioes) && ( is_array($regioes) || $regioes instanceof Traversable ) && sizeof($regioes) ) foreach( $regioes as $key1 => $value1 ){ $counter1++; ?>
                        <option value="<?php echo htmlspecialchars( $value1["provincia"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"><?php echo htmlspecialchars( $value1["provincia"], ENT_COMPAT, 'UTF-8', FALSE ); ?></option>
                        <?php } ?>
                    </select>
                    <select name="filtro" class="form-control">
                        <?php if( $filtro!='' ){ ?><option value="<?php echo htmlspecialchars( $filtro, ENT_COMPAT, 'UTF-8', FALSE ); ?>"><?php echo htmlspecialchars( $filtro, ENT_COMPAT, 'UTF-8', FALSE ); ?></option><?php }else{ ?><option value="">Todos estados</option><?php } ?>

                        <option value="">Todos estados</option>
                        <option value="Activos">Activos</option> 
                        <option value="Candidaturas">Candidaturas</option>
                        <option value="Rejeitados">Rejeitados</option>    
                    </select>
                    <input type="text" name="search" class="form-control float-right" placeholder="Procurar" value="<?php echo htmlspecialchars( $search, ENT_COMPAT, 'UTF-8', FALSE ); ?>">
                    <div class="input-group-append">
                      <button type="submit" class="btn btn-default"><i class="fas fa-filter"></i></button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <div class="card-body p-2">
              <table class="table table-bordered table-hover dataTable">
                <thead>
                  <tr>
                    <th>&nbsp;</th>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Documentos</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter1=-1;  if( isset($motorista) && ( is_array($motorista) || $motorista instanceof Traversable ) && sizeof($motorista) ) foreach( $motorista as $key1 => $value1 ){ $counter1++; ?>
                  <tr>
                    <td><?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                    <td>
                     
                        <img src="http://159.89.52.185:8080/<?php echo htmlspecialchars( $value1["foto"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" width="75">
                   
                    </td>
                    <td><?php echo htmlspecialchars( $value1["nome"], ENT_COMPAT, 'UTF-8', FALSE ); ?> <?php echo htmlspecialchars( $value1["apelido"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                    <td><?php echo htmlspecialchars( $value1["email"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                    <td><?php echo htmlspecialchars( $value1["telefone"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                    <td><?php if( $value1["status_cadastro"]!=-1 ){ ?><a href="http://159.89.52.185:8080/motoristas/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" class="btn btn-primary btn-md"><i class=""></i> Consultar </a><?php } ?></td>
                    <td></td>
                    
                      <?php if( $value1["status_cadastro"]==0 ){ ?>
                      <td>
                        <a href="http://159.89.52.185:8080/motoristas/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>/activar" onclick="return confirm('Deseja realmente activar este motorista?')" class="btn btn-success btn-sm"><i class=""></i> Activar </a>
                      </td>
                      <td>
                        <form action="http://159.89.52.185:8080/motoristas/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>/excluir" method="get">
                            <input type="text" name="justificativa" required>
                            <input type="submit" onclick="return confirm('Deseja realmente excluir este motorista?')" class="btn btn-danger btn-sm" value="Excluir">
                        </form>
                      </td>
                      <?php } ?>
                    
                      <?php if( $value1["status_cadastro"]==1 ){ ?>
                      <td>
                        <a href="http://159.89.52.185:8080/motoristas/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>/desactivar" onclick="return confirm('Deseja realmente desactivar este motorista?')" class="btn btn-danger btn-sm"><i class=""></i> Desactivar</a>
                      </td><td>&nbsp;</td>
                      <?php } ?>
                      <?php if( $value1["status_cadastro"]==-1 ){ ?>
                      <td>
                        <a href="http://159.89.52.185:8080/motoristas/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>/activar" onclick="return confirm('Deseja realmente reactivar este motorista?')" class="btn btn-success btn-sm"><i class=""></i> Reconsiderar</a>
                      </td><td>&nbsp;</td>
                      <?php } ?>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <!-- /.box-body -->
            <div class="card-footer clearfix">
              <ul class="pagination pagination-md m-1 float-right">
                <?php $counter1=-1;  if( isset($pages) && ( is_array($pages) || $pages instanceof Traversable ) && sizeof($pages) ) foreach( $pages as $key1 => $value1 ){ $counter1++; ?>
                <li><a class="page-link" href="<?php echo htmlspecialchars( $value1["href"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"><?php echo htmlspecialchars( $value1["text"], ENT_COMPAT, 'UTF-8', FALSE ); ?></a></li>
                <?php } ?>
              </ul>
            </div>
          </div>
  	</div>
  </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->