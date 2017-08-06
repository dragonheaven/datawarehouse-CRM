<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li><a href="/leads/import/">Import Leads</a></li>
			<li class="active hidden-xs">Import Lists</li>
		</ol>
		<h4 class="page-title">Import Lists</h4>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-xs-12">
			<form action="/ajax/" method="POST" id="files-from-filesystem" enctype="multipart/form-data" class="filedropzone tile">
				<h4 class="tile-title">Upload Lists</h4>
				<div class="tile-config dropdown">
					<a data-toggle="dropdown" href="" class="tile-menu"></a>
					<ul class="dropdown-menu pull-right text-right">
						<li><a href="javascript:void(0);" class="delete-selected-files">Delete Selected Files</a></li>
					</ul>
				</div>
				<input type="hidden" name="ajax-action" value="upload-files" />
				<input type="file" name="files[]" accept="csv" multiple />
				<div class="row">
					<div class="col-xs-12 text-center">
						<p class="m-t-15 hidden-xs" id="d-a-d-text">Drag and Drop <kbd>.csv</kbd> files in the window</p>
						<div class="form-group only-form-group">
							<div class="btn-group">
								<a href="javascript:void(0);" class="btn btn-default upload-button">
									<span class="fa fa-cloud-upload"></span> Upload Files
								</a>
								<a href="javascript:void(0);" class="btn btn-default no-action hidden-xs" data-import-wizard="google-drive">
									<span class="fa fa-google"></span> Import from Google Drive
								</a>
								<a href="javascript:void(0);" class="btn btn-default no-action hidden-xs" data-import-wizard="esp">
									<span class="fa fa-share-square-o"></span> Import from ESP
								</a>
								<a href="javascript:void(0);" class="btn btn-default no-action hidden-xs hidden-sm hidden-md" data-import-wizard="database">
									<span class="fa fa-database"></span> Import from Database
								</a>
								<a href="javascript:void(0);" class="btn btn-default no-action hidden-xs hidden-sm hidden-md" data-import-wizard="api">
									<span class="fa fa-cogs"></span> Import from API
								</a>
								<a href="javascript:void(0);" class="btn btn-default no-action hidden-xs hidden-sm" data-import-wizard="pixel">
									<span class="fa fa-code"></span> Get Pixels & Postbacks
								</a>
							</div>
						</div>
					</div>
				</div>
				<h4 class="tile-title">
					<div class="row">
						<div class="col-md-6"><span class="pull-left"><input type="checkbox" id="check-all-delete-boxes" /></span>Uploaded Lists</div>
						<div class="col-md-6 hidden-xs hidden-sm">Total Lead Records</div>
					</div>
				</h4>
				<div class="row">
					<div class="col-md-6">
						<ul class="file-list">
							<li class="loader"><main><div class="dank-ass-loader"><div class="row"><div class="arrow up outer outer-18"></div><div class="arrow down outer outer-17"></div><div class="arrow up outer outer-16"></div><div class="arrow down outer outer-15"></div><div class="arrow up outer outer-14"></div></div><div class="row"><div class="arrow up outer outer-1"></div><div class="arrow down outer outer-2"></div><div class="arrow up inner inner-6"></div><div class="arrow down inner inner-5"></div><div class="arrow up inner inner-4"></div><div class="arrow down outer outer-13"></div><div class="arrow up outer outer-12"></div></div><div class="row"><div class="arrow down outer outer-3"></div><div class="arrow up outer outer-4"></div><div class="arrow down inner inner-1"></div><div class="arrow up inner inner-2"></div><div class="arrow down inner inner-3"></div><div class="arrow up outer outer-11"></div><div class="arrow down outer outer-10"></div></div><div class="row"><div class="arrow down outer outer-5"></div><div class="arrow up outer outer-6"></div><div class="arrow down outer outer-7"></div><div class="arrow up outer outer-8"></div><div class="arrow down outer outer-9"></div></div></div></main></li>
						</ul>
					</div>
					<div class="col-md-6 hidden-xs hidden-sm">
						<h4 class="tile-title visible-xs">Total Lead Records</h4>
						<div class="graph-div" id="graph-leads"></div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-xs-12">
			<div class="tile">
				<h4 class="tile-title">Import Jobs</h4>
				<div class="tile-config dropdown">
					<a data-toggle="dropdown" href="" class="tile-menu"></a>
					<ul class="dropdown-menu pull-right text-right">
						<li><a href="javascript:void(0);" class="start-all-pending-jobs">Start all Import Jobs</a></li>
						<li><a href="javascript:void(0);" class="cancel-all-pending-jobs">Cancel all Import Jobs</a></li>
					</ul>
				</div>
				<div class="table-responsive">
					<table class="table table-hover table-condensed table-striped" id="import-jobs-table">
						<thead>
							<tr>
								<th width="10">&nbsp;</th>
								<th width="250">Actions</th>
								<th>File</th>
								<th width="100">Total Rows</th>
								<th width="100">Valid</th>
								<th width="100">Incomplete</th>
								<th width="100">Duplicate</th>
								<th width="100">Invalid</th>
								<th width="250">Progress</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="loader" colspan="9">
									<main><div class="dank-ass-loader"><div class="row"><div class="arrow up outer outer-18"></div><div class="arrow down outer outer-17"></div><div class="arrow up outer outer-16"></div><div class="arrow down outer outer-15"></div><div class="arrow up outer outer-14"></div></div><div class="row"><div class="arrow up outer outer-1"></div><div class="arrow down outer outer-2"></div><div class="arrow up inner inner-6"></div><div class="arrow down inner inner-5"></div><div class="arrow up inner inner-4"></div><div class="arrow down outer outer-13"></div><div class="arrow up outer outer-12"></div></div><div class="row"><div class="arrow down outer outer-3"></div><div class="arrow up outer outer-4"></div><div class="arrow down inner inner-1"></div><div class="arrow up inner inner-2"></div><div class="arrow down inner inner-3"></div><div class="arrow up outer outer-11"></div><div class="arrow down outer outer-10"></div></div><div class="row"><div class="arrow down outer outer-5"></div><div class="arrow up outer outer-6"></div><div class="arrow down outer outer-7"></div><div class="arrow up outer outer-8"></div><div class="arrow down outer outer-9"></div></div></div></main>
								</td>
							</tr>
						</tbody>
					</table>
			</div>
		</div>
	</div>
</div>