<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	$queries = tc_get_user_saved_queries();
?>
<h1><span class="fa fa-sliders"></span>System Settings</h1>
<ol class="breadcrumb">
	<li><a href="/"><?php echo APP; ?></a></li>
	<li class="active">System Settings</li>
</ol>
<div class="row">
	<div class="col-md-4">
		<div class="hp-info-panel">
			<div class="row">
				<div class="col-xs-3 text-center hundred-line-height">
					<span class="fa fa-users"></span>
				</div>
				<div class="col-xs-9">
					<div class="block">
						<span data-leadinfo-key="allLeadCount">-</span>
					</div>
					<div class="block">
						Total Unique Leads
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="hp-info-panel">
			<div class="row">
				<div class="col-xs-3 text-center hundred-line-height">
					<span class="fa fa-users"></span>
				</div>
				<div class="col-xs-9">
					<div class="block">
						<span data-sysinfo-key="rlm">&nbsp;</span>
					</div>
					<div class="block">
						Rows Last Minute
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="hp-info-panel">
			<div class="row">
				<div class="col-xs-3 text-center hundred-line-height">
					<span class="fa fa-users"></span>
				</div>
				<div class="col-xs-9">
					<div class="block">
						<span data-sysinfo-key="alt">86400</span> s
					</div>
					<div class="block">
						Average Import Time
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-6"></div>
</div>
<br /><br />
<div class="row">
	<div class="col-md-8 col-sm-12">
		<div class="hp-panel">
			<h4>Concurrent Threads</h4>
			<div class="graph-div" id="graph-threads"></div>
		</div>
	</div>
	<div class="col-md-4 col-sm-12">
		<div class="hp-panel hp-panel-half">
			<h4>CPU Utilization</h4>
			<div class="graph-div" id="graph-cpu"></div>
		</div>
		<div class="hp-panel hp-panel-half">
			<h4>Memory Utilization</h4>
			<div class="graph-div" id="graph-memory"></div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4>NewRelic Charts</h4>
			</div>
			<iframe src="https://rpm.newrelic.com/public/charts/ai9t642obfr" width="100%" height="500" class="panel-body" scrolling="no" frameborder="no"></iframe>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">
	<?php
	$stats = cache_get_status();
	if ( can_loop( $stats ) ) {
?>
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h4>Memcached Server Stats</h4>
		</div>
		<div class="table-responsive">
			<table class="table table-striped table-condensed">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>Server</th>
						<th>Utilization</th>
						<th>Usage</th>
						<th>Max</th>
						<th>Version</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $stats as $server => $info ) {
					$utilization = floatval( intval( get_array_key( 'bytes', $info ) ) / intval( get_array_key( 'limit_maxbytes', $info ) ) );
				?>
				<tr>
					<td>&nbsp;</td>
					<td><?php echo $server; ?></td>
					<td><?php echo round( floatval( $utilization ), 4 ) * 100; ?>%</td>
					<td><?php echo get_array_key( 'bytes', $info ); ?> bytes</td>
					<td><?php echo get_array_key( 'limit_maxbytes', $info ); ?> bytes</td>
					<td><?php echo get_array_key( 'version', $info ); ?></td>
				</tr>
				<?
				<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
<?php
	}
	?>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<form action="update_system_params" method="AJAX" class="panel panel-primary">
			<div class="panel-heading">
				<h5>Import Settings</h5>
			</div>
			<div class="table-responsive">
				<table class="table">
					<tbody>
						<tr>
							<td><strong>Max Simultaneous Import Jobs</strong></td>
						</tr>
						<tr>
							<td>
								<input type="number" name="MAX_SIMULTANOUS_IMPORT_JOBS" min="1" max="100" step="1" steps="1" class="form-control" required value="<?php echo absint( get_option( 'MAX_SIMULTANOUS_IMPORT_JOBS', MAX_SIMULTANOUS_IMPORT_JOBS, true ) ); ?>" />
							</td>
						</tr>
						<tr>
							<td><strong>Max Simultaneous Threads</strong></td>
						</tr>
						<tr>
							<td>
								<input type="number" name="MAX_SIMULTANOUS_THREADS" min="1" max="10000" step="1" steps="1" class="form-control" required value="<?php echo absint( get_option( 'MAX_SIMULTANOUS_THREADS', MAX_SIMULTANOUS_THREADS, true ) ); ?>" />
							</td>
						</tr>
						<tr>
							<td><strong>Max Memory Usage</strong></td>
						</tr>
						<tr>
							<td>
								<div class="input-group">
									<input type="number" name="MAX_MEM_USAGE_PERCENT" min="1" max="90" step="1" steps="1" class="form-control" required value="<?php echo absint( get_option( 'MAX_MEM_USAGE_PERCENT', MAX_MEM_USAGE_PERCENT, true ) ); ?>" />
									<span class="input-group-addon">%</span>
								</div>
							</td>
						</tr>
						<tr>
							<td><strong>Wait before retrying when using too much memory</strong></td>
						</tr>
						<tr>
							<td>
								<div class="input-group">
									<input type="number" name="MAX_MEMORY_SLEEP_WAIT_TIME" min="0" max="120" step="any" steps="any" class="form-control" required value="<?php echo absint( get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ) ); ?>" />
									<span class="input-group-addon">Seconds</span>
								</div>
							</td>
						</tr>
						<tr>
							<td><strong>Wait before retrying when using too many threads</strong></td>
						</tr>
						<tr>
							<td>
								<div class="input-group">
									<input type="number" name="MAX_THREAD_SLEEP_WAIT_TIME" min="0" max="500" step="any" steps="any" class="form-control" required value="<?php echo absint( get_option( 'MAX_THREAD_SLEEP_WAIT_TIME', MAX_THREAD_SLEEP_WAIT_TIME, true ) ); ?>" />
									<span class="input-group-addon">Seconds</span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-xs-6 col-sm-7">
						<div class="ajax-response"></div>
					</div>
					<div class="col-xs-6 col-sm-5">
						<input type="submit" disabled class="btn btn-primary btn-block" value="Update" />
					</div>
				</div>
			</div>
		</form>
	</div>
	<div class="col-md-6">
		<form action="update_default_queries" method="AJAX" class="panel panel-primary">
			<div class="panel-heading">
				<h5>Default Queries</h5>
			</div>
			<div class="table-responsive">
				<table class="table">
					<tbody>
						<tr>
							<td><strong>Total Leads Query</strong></td>
						</tr>
						<tr>
							<td>
								<select name="totalLeadsSavedQuery" class="form-control" placeholder="Choose a saved query">
									<option value="0">Default</option>
								<?php
									if ( can_loop( $queries ) ) {
										foreach ( $queries as $query ) {
											if ( true == $query->public ) {
												echo sprintf(
													'<option value="%s" %s>%s</option>' . "\r\n",
													$query->id,
													( $query->id == get_option( 'totalLeadsSavedQuery', 0 ) ) ? 'selected' : '',
													$query->name
												);
											}
										}
									}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<td><strong>Call Center Ready Query</strong></td>
						</tr>
						<tr>
							<td>
								<select name="callCenterReady" class="form-control" placeholder="Choose a saved query">
								<?php
									if ( can_loop( $queries ) ) {
										foreach ( $queries as $query ) {
											if ( true == $query->public ) {
												echo sprintf(
													'<option value="%s" %s>%s</option>' . "\r\n",
													$query->id,
													( $query->id == get_option( 'callCenterReady', 0 ) ) ? 'selected' : '',
													$query->name
												);
											}
										}
									}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<td><strong>Email Marketing Ready Query</strong></td>
						</tr>
						<tr>
							<td>
								<select name="emailMarketingReady" class="form-control" placeholder="Choose a saved query">
								<?php
									if ( can_loop( $queries ) ) {
										foreach ( $queries as $query ) {
											if ( true == $query->public ) {
												echo sprintf(
													'<option value="%s" %s>%s</option>' . "\r\n",
													$query->id,
													( $query->id == get_option( 'emailMarketingReady', 0 ) ) ? 'selected' : '',
													$query->name
												);
											}
										}
									}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<td><strong>SMS Ready Query</strong></td>
						</tr>
						<tr>
							<td>
								<select name="smsMarketingReady" class="form-control" placeholder="Choose a saved query">
								<?php
									if ( can_loop( $queries ) ) {
										foreach ( $queries as $query ) {
											if ( true == $query->public ) {
												echo sprintf(
													'<option value="%s" %s>%s</option>' . "\r\n",
													$query->id,
													( $query->id == get_option( 'smsMarketingReady', 0 ) ) ? 'selected' : '',
													$query->name
												);
											}
										}
									}
								?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-xs-6 col-sm-7">
						<div class="ajax-response"></div>
					</div>
					<div class="col-xs-6 col-sm-5">
						<input type="submit" disabled class="btn btn-primary btn-block" value="Update" />
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<form action="update_predefined_meta_tags" method="AJAX" class="panel panel-primary">
			<div class="panel-heading">
				<h5>Predefined Meta Tags</h5>
			</div>
			<table class="table">
				<tbody>
					<tr>
						<td><strong>Set System Predefined Meta Tags</strong></td>
					</tr>
					<tr>
						<td>
							<select name="predefinedMetaTags[]" class="form-control searchable" placeholder="Choose a saved query" multiple data-can-add="1">
							<?php
								$pdmt = get_predefined_meta_tags();
								if ( can_loop( $pdmt ) ) {
									foreach ( $pdmt as $t ) {
										echo sprintf( '<option value="%s" selected>%s</option>', $t, $t );
									}
								}
							?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="panel-footer">
				<div class="row">
					<div class="col-xs-6 col-sm-7">
						<div class="ajax-response"></div>
					</div>
					<div class="col-xs-6 col-sm-5">
						<input type="submit" disabled class="btn btn-primary btn-block" value="Update" />
					</div>
				</div>
			</div>
		</form>
	</div>
	<div class="col-md-6">
		<form action="update_predefined_lead_list_tags" method="AJAX" class="panel panel-primary">
			<div class="panel-heading">
				<h5>Lead List Tags</h5>
			</div>
			<table class="table">
				<tbody>
					<tr>
						<td><strong>Set System Predefined Lead List Tags</strong></td>
					</tr>
					<tr>
						<td>
							<select name="predefinedLeadListTags[]" class="form-control searchable" placeholder="Choose a saved query" multiple data-can-add="1">
							<?php
								$pdllt = get_predefined_lead_list_tags();
								if ( can_loop( $pdllt ) ) {
									foreach ( $pdllt as $t ) {
										echo sprintf( '<option value="%s" selected>%s</option>', $t, $t );
									}
								}
							?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="panel-footer">
				<div class="row">
					<div class="col-xs-6 col-sm-7">
						<div class="ajax-response"></div>
					</div>
					<div class="col-xs-6 col-sm-5">
						<input type="submit" disabled class="btn btn-primary btn-block" value="Update" />
					</div>
				</div>
			</div>
		</form>
	</div>
</div>