<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	$path = tc_get_path();
	$vars = return_path_vars( $path, '^/leads/([^/]*)/([^/]*)$', array( 'controller', 'item' ) );
	$item = get_array_key( 'item', $vars );
	$fm = tc_get_filemap_for_file( $item );
?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li><a href="/leads/import/">Import Leads</a></li>
			<li><a href="/leads/import/">Import Lists</a></li>
			<li class="active hidden-xs">Map File Fields</li>
		</ol>
		<h4 class="page-title">Map <span class="hidden-xs">File Fields</span></h4>
	</div>
</div>
<div class="block-area">
	<h1><?php echo $item; ?></h1>
</div>
<form action="generate_file_preview" method="AJAX" class="block-area m-b-25 hidden-xs" data-callback="tc_generate_file_preview">
	<input type="hidden" name="headerrow" value="0" />
	<input type="hidden" name="file" value="<?php echo strip_tags( $item ); ?>" />
	<div class="row hidden-xs">
		<div class="col-xs-12">
			<div class="tile">
				<h4 class="tile-title">File Settings</h4>
				<div class="row">
					<div class="col-xs-12 text-center">
						<div class="form-group only-form-group">
							<div class="input-group">
								<span class="input-group-addon">Fields seperated with:</span>
								<input type="text" name="delimiter" id="delimiter" class="form-control input-sm" required value="<?php echo htmlentities( get_bean_property( 'delimiter', $fm, ',' ) ); ?>" />
								<div class="input-group-btn">
									<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Special <span class="caret"></span></button>
									<ul class="dropdown-menu">
										<li><a href="javascript:void(0);" data-delimiter="TAB"><kbd>TAB</kbd></a></li>
										<li><a href="javascript:void(0);" data-delimiter="SPACE"><kbd>SPACE</kbd></a></li>
									</ul>
								</div>
								<span class="input-group-addon">Fields encapsulated with:</span>
								<input type="text" name="encapsulation" id="encapsulation" class="form-control input-sm" required value="<?php echo htmlentities( get_bean_property( 'encapsulation', $fm, '"' ) ); ?>" />
								<div class="input-group-btn">
									<input type="submit" disabled class="btn btn-default" value="Update" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
<form action="other_file_actions" method="AJAX" class="hidden" data-callback="tc_parse_file_actions_return">
	<input type="hidden" name="action" value="none" />
	<input type="hidden" name="file" value="<?php echo strip_tags( $item ); ?>" />
	<?php echo ( true == array_key_exists( 'debug', $_GET ) ) ? '<input type="hidden" name="debug" value="true" />' : ''; ?>
</form>
<form action="save_file_map" method="AJAX" class="block-area m-b-25 hidden-xs" id="field-map-form">
	<input type="hidden" name="file" value="<?php echo strip_tags( $item ); ?>" />
	<input type="hidden" name="map_delimiter" value="" />
	<input type="hidden" name="map_encapsulation" value="" />
	<input type="hidden" name="map_headerrow" value="" />
	<div class="tile">
		<h4 class="tile-title">Map File Fields</h4>
		<div class="tile-config dropdown">
			<a data-toggle="dropdown" href="" class="tile-menu"></a>
			<ul class="dropdown-menu pull-right text-right">
				<li><a href="javascript:void(0);" class="copy-from-other-file">Copy from another file</a></li>
			</ul>
		</div>
		<div class="row hidden-xs">
			<div class="col-md-6">
				<div class="form-group only-form-group">
					<div class="btn-group">
						<?php if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) && $fm->approved == false && can_loop( @unserialize( $fm->columnMap ) ) ) { ?>
						<a href="javascript:void(0);" class="btn btn-default" data-file-action="approve"><span class="fa fa-check"></span>Approve Mapping</a>
						<?php } ?>
						<a href="javascript:void(0);" class="btn btn-default" data-file-action="reset"><span class="fa fa-retweet"></span>Reset Mapping</a>
						<a href="javascript:void(0);" class="btn btn-default hidden-xs hidden-xs" data-file-action="delete"><span class="fa fa-trash"></span>Delete File</a>
						<button class="no-action btn btn-default" id="tc-add-new-column"><span class="fa fa-columns"></span>Add New Column</button>
						<div class="under"><span class="fa fa-download no-margin"></span></div><input type="submit" class="btn btn-default" id="save-mapping-button" value="Save Mapping" />
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group only-form-group">
					<div class="input-group">
						<span class="input-group-addon">List Tags</span>
						<select id="listtags" name="listtags[]" class="form-control searchable" placeholder="Choose a Tag or Multiple Tags" multiple>
						<?php
							$pdllt = get_predefined_lead_list_tags();
							$add = array();
							$tags = array();
							if ( ! is_empty( $fm->additional ) ) {
								$add = @unserialize( $fm->additional );
							}
							if ( can_loop( $add ) ) {
								foreach ( $add as $ck => $cv ) {
									if ( beginning_matches( 'tag_', $ck ) && ! in_array( $cv, $tags ) ) {
										array_push( $tags, $cv );
									}
								}
							}
							if ( can_loop( $pdllt ) ) {
								foreach ( $pdllt as $t ) {
									echo sprintf( '<option value="%s" %s>%s</option>', $t, ( in_array( $t, $tags ) ) ? 'selected' : '', $t );
								}
							}
						?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="ajax-response"></div>
		<div class="row hidden-xs">
			<div class="col-xs-12">
				<div class="table-responsive scroll-overflow-x">
					<table class="table table-bordered table-striped table-hover">
							<thead>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
							</thead>
							<tbody>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
								<tr></tr>
							</tbody>
						</table>
				</div>
			</div>
		</div>
	</div>
</form>
<div class="block-area m-b-25 visible-xs">
	<div class="tile">
		<h4 class="tile-title">Not Available on Mobile</h4>
		<div class="row">
			<div class="col-xs-12">
				<div class="form-group only-form-group">
					<p>Please use a larger screen to map file fields</p>
				</div>
			</div>
		</div>
	</div>
</div>