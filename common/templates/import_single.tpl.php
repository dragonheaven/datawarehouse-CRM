<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li><a href="/leads/import/">Import Leads</a></li>
			<li class="active hidden-xs">Import Single Lead</li>
		</ol>
		<h4 class="page-title">Import Single Lead</h4>
	</div>
</div>
<div class="block-area m-b-25">
	<form class="tile" action="import_single_lead" method="AJAX" id="single-lead-import" data-callback="tc_preview_single_lead">
		<?php if ( array_key_exists( 'debug', $_GET ) ) { ?>
		<input type="hidden" name="debug" value="1" />
		<?php } ?>
		<h5 class="tile-title">Import a Lead</h5>
		<div class="table-responsive">
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th width="75"><a href="javascript:void(0);" class="btn btn-block btn-xs btn-success tc-add-field-row">Add Field</a></th>
						<th colspan="2">Field Name</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-xs-6 col-sm-7 col-md-8 col-lg-9">
					<div class="ajax-response"></div>
				</div>
				<div class="col-xs-6 col-sm-5 col-md-4 col-lg-3">
					<input type="submit" disabled class="btn btn-success btn-block" value="Import Lead" />
				</div>
			</div>
		</div>
	</form>
	<?php if ( array_key_exists( 'debug', $_GET ) ) { ?>
	<div class="tile">
		<h5 class="tile-title">Preview Results</h5>
		<pre class="panel-body" id="preview-results"></pre>
	</div>
	<?php } ?>
</div>