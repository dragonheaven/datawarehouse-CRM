<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li class="active hidden-xs">View Leads</li>
		</ol>
		<h4 class="page-title">View Leads</h4>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-xs-12">
			<form class="tile" action="get_lead_search_results" data-callback="tc_generate_lead_cards_from_lead_search_results" data-on-progress="tc_generate_lead_list_loader" method="AJAX">
				<input type="hidden" name="orderby" value="id" />
				<input type="hidden" name="order" value="desc" />
				<input type="hidden" name="page" value="0" />
				<h2 class="tile-title">Search</h2>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<div class="input-group">
								<input type="search" class="form-control" name="searchterm" placeholder="Search" />
								<div class="input-group-btn">
									 <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button>
									 <ul class="dropdown-menu">
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="name" checked />Name</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="email" checked />Email Address</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="phone" checked />Phone Number</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="address" />Address</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="source" />Source</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="ip" />IP Address</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="tag" />Tag</label></li>
										<li><label><input type="checkbox" name="basicsearchobjects[]" value="metavalue" />Meta Data</label></li>
									 </ul>
									 <input type="submit" class="btn btn-default" value="Search" />
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
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
									<input type="submit" class="btn btn-default" value="Load" />
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<a href="javascript:void(0);" id="advanced-search-link" class="hidden-sm hidden-xs">Advanced</a>
					</div>
				</div>
				<div class="row hidden-sm hidden-xs" id="advanced-search">
					<div class="col-xs-12">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th width="50"><button role="button" class="no-action btn btn-success btn-block btn-xs tc-add-filter-row">Add Filter</button></th>
									<th width="50">ID</th>
									<th width="200">Field</th>
									<th width="200">Attribute</th>
									<th width="200">Condition</th>
									<th>Filter</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
						<div class="form-group">
							<label for="filtergrouping">Condition Grouping</label>
							<input type="text" name="filtergrouping" id="filtergrouping" class="form-control input-sm" />
							<p class="text-info"><small>Example: ( 1 OR 2 ) AND ( 3 OR 4 OR 5 )</small></p>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-xs-12">
			<div class="tile lead-panel">
				<h2 class="tile-title">Leads</h2>
				<div class="row">
					<ul class="list-inline pull-right m-t-5 m-b-0">
						<li class="pagin-value"></li>
						<li>
							<a href="javascript:void(0);" title="" class="tooltips" data-page-id="0" data-direction="prev" data-original-title="Previous">
								<span class="fa fa-chevron-left"></span>
							</a>
						</li>
						<li>
							<a href="javascript:void(0);" title="" class="tooltips" data-page-id="0" data-direction="next" data-original-title="Next">
								<span class="fa fa-chevron-right"></span>
							</a>
						</li>
					</ul>
					<div class="col-md-6 hidden-xs">
						<div class="form-group only-form-group">
							<div class="input-group">
								<span class="input-group-addon">Order By</span>
								<select id="orderby" class="form-control">
								</select>
								<span class="input-group-addon">Ordered</span>
								<select id="order" class="form-control">
									<option value="desc">Descending</option>
									<option value="asc">Ascending</option>
								</select>
								<div class="input-group-btn">
									<button id="re-order" class="btn btn-default"><span class="fa fa-refresh no-margin"></span></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<span id="total-leads-wrap"><span id="totalLeads">0</span> Total</span>
				<div class="table-responsive m-b-25">
					<table class="table table-condensed table-hover table-striped">
						<thead>
							<tr>
								<th width="20">&nbsp;</th>
								<th width="60"><strong>ID</strong></th>
								<th><strong>Name</strong></th>
								<th width="175"><strong>Email Address</strong></th>
								<th width="175"><strong>Phone Number</strong></th>
								<th width="175"><strong>Country</strong></th>
								<th width="175"><strong>Source</strong></th>
								<th width="200"><strong>Import Time</strong></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td rowspan="10" colspan="8" class="loading-container">
									<main>
									   <div class="dank-ass-loader">
									      <div class="row">
									         <div class="arrow up outer outer-18"></div>
									         <div class="arrow down outer outer-17"></div>
									         <div class="arrow up outer outer-16"></div>
									         <div class="arrow down outer outer-15"></div>
									         <div class="arrow up outer outer-14"></div>
									      </div>
									      <div class="row">
									         <div class="arrow up outer outer-1"></div>
									         <div class="arrow down outer outer-2"></div>
									         <div class="arrow up inner inner-6"></div>
									         <div class="arrow down inner inner-5"></div>
									         <div class="arrow up inner inner-4"></div>
									         <div class="arrow down outer outer-13"></div>
									         <div class="arrow up outer outer-12"></div>
									      </div>
									      <div class="row">
									         <div class="arrow down outer outer-3"></div>
									         <div class="arrow up outer outer-4"></div>
									         <div class="arrow down inner inner-1"></div>
									         <div class="arrow up inner inner-2"></div>
									         <div class="arrow down inner inner-3"></div>
									         <div class="arrow up outer outer-11"></div>
									         <div class="arrow down outer outer-10"></div>
									      </div>
									      <div class="row">
									         <div class="arrow down outer outer-5"></div>
									         <div class="arrow up outer outer-6"></div>
									         <div class="arrow down outer outer-7"></div>
									         <div class="arrow up outer outer-8"></div>
									         <div class="arrow down outer outer-9"></div>
									      </div>
									   </div>
									</main>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<div class="form-group only-form-group">
							<div class="input-group">
								<span class="input-group-btn">
									<button class="btn btn-default" data-page-id="0" data-direction="first" type="button"><span class="fa fa-fast-backward no-margin"></span></button>
									<button class="btn btn-default" data-page-id="0" data-direction="prev" type="button"><span class="fa fa-step-backward no-margin"></span></button>
								</span>
								<span class="input-group-addon hidden-xs">Jump to Page</span>
								<input type="text" class="form-control" placeholder="Page Number" value="1" min="1" max="1" id="jump" />
								<span class="input-group-btn">
									<button class="btn btn-default" type="button" id="jump-action"><span class="fa fa-share"></span>Jump</button>
								</span>
								<span class="input-group-addon hidden-xs">of</span>
								<span class="input-group-addon hidden-xs total-page-count">1</span>
								<span class="input-group-btn">
									<button class="btn btn-default" data-page-id="0" data-direction="next" type="button"><span class="fa fa-step-forward no-margin"></span></button>
									<button class="btn btn-default" data-page-id="0" data-direction="last" type="button"><span class="fa fa-fast-forward no-margin"></span></button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>