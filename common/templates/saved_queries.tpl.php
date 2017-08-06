<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	// this is where we get information on the new query
	if ( absint( $more ) > 0 ) {
		try {
			$q = R::load( 'savedfilterqueries', absint( $more ) );
		}
		catch ( Exception $e ) {
			$q = null;
		}
	}
	else {
		try {
			$q = R::dispense( 'savedfilterqueries' );
		}
		catch ( Exception $e ) {
			$q = null;
		}
	}
?>
<form action="create_saved_query" method="AJAX" class="panel panel-primary" data-saved-query-id="<?php echo absint( get_bean_property( 'id', $q, 0 ) ); ?>" <?php echo ( array_key_exists( 'debug', $_GET ) ) ? 'data-callback="tc_handle_query_debug"' : ''; ?>>
	<!-- ajax_create_saved_query -->
	<input type="hidden" name="queryId" value="<?php echo absint( get_bean_property( 'id', $q, 0 ) ); ?>" />
	<input type="hidden" name="exportfields[]" value="id.value" />
	<?php echo ( array_key_exists( 'debug', $_GET ) ) ? '<input type="hidden" name="debug" value="1" />' : ''; ?>
	<div class="panel-heading">
		<div class="row">
			<div class="col-md-3">
				<h5><?php echo generate_tutorial_tag( 'manage-lead-query' ); ?>Manage Saved Query</h5>
			</div>
			<div class="col-md-1">
				<a href="javascript:void(0);" class="btn btn-danger btn-block" id="delete-saved-query" data-saved-query-id="<?php echo absint( get_bean_property( 'id', $q, 0 ) ); ?>">Delete</a>
			</div>
			<div class="col-md-8">
				<div class="input-group">
					<span class="input-group-addon"><strong>Saved Queries</strong></span>
					<select id="loadsavedquery" class="form-control">
					<?php
						$sq = tc_get_user_saved_queries();
						if ( can_loop( $sq ) ) {
						?>
						<option value="" disabled selected>Choose a Saved Query to Load</option>
						<?php
						foreach ( $sq as $sql ) {
						echo sprintf( '<option value="%d">%s</option>', $sql->id, $sql->name );
						}
						}
						else {
						?>
						<option value="" disabled selected>You have no Saved Queries Available</option>
						<?php
						}
					?>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-primary" type="button" id="load-saved-query">Load</button>
					</span>
				</div>
			</div>
		</div>
	</div>
	<?php if ( ! array_key_exists( 'debug', $_GET ) ) { ?>
	<div class="panel-body">
		<div class="form-group">
			<label for="filtergrouping">Query Name</label>
			<input type="text" name="filtername" id="filtername" class="form-control input-sm" value="<?php echo get_bean_property( 'name', $q, 'New Filter' ); ?>" required />
			<p class="text-info"><small>Name used to identify filter</small></p>
		</div>
		<div class="form-group">
			<label for="description">Query Description</label>
			<textarea name="description" id="description" class="form-control"><?php echo get_bean_property( 'description', $q, '' ); ?></textarea>
			<p class="text-info"><small>English-Language Description of the Filters Used</small></p>
		</div>
		<div class="form-group">
			<label>
				<input type="checkbox" name="public" value="1" <?php echo ( true == get_bean_property( 'public', $q, 0 ) ) ? 'checked' : ''; ?> />
				Public Query
			</label>
			<p class="text-info"><small>If other users can use the query</small></p>
		</div>
		<div class="form-group">
			<label>
				<input type="checkbox" name="showGraph" value="1" <?php echo ( true == get_bean_property( 'showGraph', $q, 0 ) ) ? 'checked' : ''; ?> />
				Show Graph
			</label>
			<p class="text-info"><small>Show data in lead graph</small></p>
		</div>
	</div>
	<?php } ?>
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th width="200"><button role="button" class="no-action btn btn-success btn-block btn-xs tc-add-filter-row">Add Filter</button></th>
				<th width="50">ID</th>
				<th width="200">Field</th>
				<th width="200">Attribute</th>
				<th width="200">Condition</th>
				<th>Filter</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	<div class="panel-body">
		<div class="form-group">
			<label for="filtergrouping">Condition Grouping</label>
			<input type="text" name="filtergrouping" id="filtergrouping" class="form-control input-sm" value="<?php echo get_bean_property( 'grouping', $q, null ); ?>" required />
			<p class="text-info"><small>Example: ( 1 OR 2 ) AND ( 3 OR 4 OR 5 )</small></p>
		</div>
	</div>
	<div class="panel-footer">
		<div class="row">
			<div class="col-xs-6 col-sm-6">
				<div class="ajax-response"></div>
			</div>
			<div class="col-xs-6 col-sm-3">
				<button role="button" class="no-action btn btn-info btn-block tc-preview-export">Preview</button>
			</div>
			<div class="col-xs-6 col-sm-3">
				<input type="submit" disabled class="btn btn-<?php echo ( array_key_exists( 'debug', $_GET ) ) ? 'success' : 'primary'; ?> btn-block" value="<?php echo ( array_key_exists( 'debug', $_GET ) ) ? 'Debug Query' : 'Save Query'; ?>" />
			</div>
		</div>
	</div>
</form>
<?php if ( array_key_exists( 'debug', $_GET ) ) { ?>
<pre id="saved-query-debug-feedback"></pre>
<?php } ?>