<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li><a href="/leads/export/">Export Leads</a></li>
			<li class="active hidden-xs">Export Lists</li>
		</ol>
		<h4 class="page-title">Export Lists</h4>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-xs-12">
			<form action="create_export_job" method="AJAX" class="tile" data-callback="tc_add_export_job_to_list">
				<h4 class="tile-title">Create a New Export Job</h4>
				<div class="row">
					<div class="col-xs-12">
						<div class="form-group only-form-group">
							<div class="input-group">
								<span class="input-group-addon">Load Saved Query</span>
								<select id="loadsavedquery" name="savedqueryid" class="form-control">
								<?php
									$sq = tc_get_user_saved_queries();
									if ( can_loop( $sq ) ) {
									?>
									<option value="0">Use Search Query or Advanced Filters</option>
									<?php
									foreach ( $sq as $q ) {
									echo sprintf( '<option value="%d">%s</option>', $q->id, $q->name );
									}
									}
									else {
									?>
									<option value="" disabled selected>You have no Saved Queries Available</option>
									<?php
									}
								?>
								</select>
								<div class="input-group-btn">
									<button class="btn btn-default" type="button" id="load-saved-query">Load</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<table class="table table-striped table-hover">
					<thead>
						<tr>
							<th width="200"><button role="button" class="no-action btn btn-default btn-block btn-xs tc-add-filter-row">Add Filter</button></th>
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
						<input type="text" name="filtergrouping" id="filtergrouping" class="form-control input-sm" required />
						<p class="text-info"><small>Example: ( 1 OR 2 ) AND ( 3 OR 4 OR 5 )</small></p>
					</div>
					<div class="form-group">
						<label for="maxleads">Maximum Leads to Export</label>
						<input type="text" name="maxleads" id="maxleads" class="form-control input-sm" required />
						<p class="text-primary"><small>of <span id="leadcount">indetermined amount of</span> leads</small></p>
					</div>
					<div class="form-group">
						<label for="exportfields">Export Fields</label>
						<select name="exportfields[]" id="exportfields" class="form-control" multiple required>
						<?php
						if ( can_loop( tc_get_exportable_lead_columns() ) ) {
							foreach ( tc_get_exportable_lead_columns() as $colname => $description ) {
								echo sprintf( '<option value="%s" %s>%s</option>', $colname, ( in_array( $colname, array(
									'id.value', 'fname.value', 'lname.value', 'phone.number_numbers_only', 'email.email', 'country.value',
								) ) ) ? 'selected' : '', $description );
							}
						}
						?>
						</select>
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
							<input type="submit" disabled class="btn btn-primary btn-block" value="Create Job" />
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>