<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="container-fluid">
	<div class="row">
		<div class="col-md-4 col-lg-3">
			<form action="authenticate" method="AJAX" class="panel panel-primary" id="login-form">
				<input type="hidden" name="redirect" value="<?php echo strip_tags( get_array_key( 'return', $_GET ) ); ?>" />
				<div class="panel-heading">
					<h2 class="m-t-0 m-b-15">Data Warehouse Login</h2>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<input type="text" name="user" id="user" class="form-control" placeholder="Username" required/>
					</div>
					<div class="form-group">
						<input type="password" name="pass" id="pass" class="form-control" placeholder="Password" required/>
					</div>
				</div>
				<div class="panel-footer">
					<div class="row">
						<div class="col-xs-6 col-sm-7">
							<div class="ajax-response"></div>
						</div>
						<div class="col-xs-6 col-sm-5">
							<input type="submit" disabled class="btn btn-primary btn-block" value="Authenticate" />
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>