<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li class="active hidden-xs">Dashboard</li>
		</ol>
		<h4 class="page-title">Dashboard</h4>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-md-6 col-lg-3">
			<div class="tile hp-info-panel">
				<div class="row">
					<div class="col-xs-3 text-center hundred-line-height">
						<span class="fa fa-users"></span>
					</div>
					<div class="col-xs-9">
						<div class="block">
							<span data-leadquery-beanid="<?php echo absint( get_option( 'allLeadCount', 0 ) ); ?>">0</span>
						</div>
						<div class="block">
							Total Unfiltered Unique Leads
						</div>
					</div>
				</div>
			</div>
			<p class="tile description"><?php echo get_decription_for_saved_query( absint( get_option( 'allLeadCount', 0 ) ), 'Unfiltered Unique Lead Objects' ); ?></p>
		</div>
		<div class="col-md-6 col-lg-3">
			<div class="tile hp-info-panel" title="Leads which have all the validated information needed to be placed in a dialer." data-placement="bottom">
				<div class="row">
					<div class="col-xs-3 text-center hundred-line-height">
						<span class="fa fa-volume-control-phone"></span>
					</div>
					<div class="col-xs-9">
						<div class="block">
							<span data-leadinfo-key="callCenterReady" data-leadquery-beanid="<?php echo absint( get_option( 'callCenterReady', 0 ) ); ?>">0</span>
						</div>
						<div class="block">
							Call Center Ready
						</div>
					</div>
				</div>
			</div>
			<p class="tile description"><?php echo get_decription_for_saved_query( absint( get_option( 'callCenterReady', 0 ) ), 'Leads with Name & Valid Mobile & Possible Mobile Phone Numbers from certain countries excluding US' ); ?></p>
		</div>
		<div class="col-md-6 col-lg-3 hidden-sm hidden-xs">
			<div class="tile hp-info-panel" title="Leads which have all the validated information needed to be sent email marketing campaigns." data-placement="bottom">
				<div class="row">
					<div class="col-xs-3 text-center hundred-line-height">
						<span class="fa fa-envelope"></span>
					</div>
					<div class="col-xs-9">
						<div class="block">
							<span data-leadinfo-key="emailMarketingReady" data-leadquery-beanid="<?php echo absint( get_option( 'emailMarketingReady', 0 ) ); ?>">0</span>
						</div>
						<div class="block">
							Email Marketing
						</div>
					</div>
				</div>
			</div>
			<p class="tile description"><?php echo get_decription_for_saved_query( absint( get_option( 'emailMarketingReady', 0 ) ), 'Leads with an email address with a valid domain and an associated IP address from certain countries excluding US.' ); ?></p>
		</div>
		<div class="col-md-6 col-lg-3 hidden-sm hidden-xs">
			<div class="tile hp-info-panel" title="Leads which have verified mobile phone numbers." data-placement="bottom">
				<div class="row">
					<div class="col-xs-3 text-center hundred-line-height">
						<span class="fa fa-commenting"></span>
					</div>
					<div class="col-xs-9">
						<div class="block">
							<span data-leadinfo-key="smsMarketingReady" data-leadquery-beanid="<?php echo absint( get_option( 'smsMarketingReady', 0 ) ); ?>">0</span>
						</div>
						<div class="block">
							SMS Ready
						</div>
					</div>
				</div>
			</div>
			<p class="tile description"><?php echo get_decription_for_saved_query( absint( get_option( 'smsMarketingReady', 0 ) ), 'Leads with a verified mobile phone number from certain countries excluding US.' ); ?></p>
		</div>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-md-6 col-lg-5">
			<div class="tile">
				<h2 class="tile-title">Leads per Country</h2>
				<div class="graph-div" id="graph-leads-by-country"></div>
				<div class="graph-options">
					<div class="row hidden-xs">
						<div class="col-xs-10 col-sm-11 col-md-10 col-lg-11">
							<select id="loadsavedquery-dash" class="form-control">
		                        <?php
		                        $sq = tc_get_graph_queries();
		                        if ( can_loop( $sq ) ) {
		                            ?>
		                            <option value="0">Unfiltered</option>
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
						</div>
						<div class="col-xs-2 col-sm-1 col-md-2 col-lg-1">
							<a href="javascript:void(0);" class="btn btn-primary btn-block btn-sm with-tool-tip text-center" title="Pin Query as Default" data-placement="right" id="save-default-map-query"><span class="fa fa-thumb-tack no-margin"></span></a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6 col-lg-4">
			<div class="tile">
				<h2 class="tile-title">Leads per Country</h2>
				<div class="table-responsive" id="llbc">
					<table class="table table-striped table-hover">
	                    <tbody id="list-leads-by-country"></tbody>
	                </table>
				</div>
			</div>
		</div>
		<div class="col-md-12 col-lg-3">
			<div class="tile tile-dark hidden-xs">
				<h2 class="tile-title">Usability Rates</h2>
				<div class="row">
					<div class="col-xs-4">
						<div class="gauge-div" id="callCenterReadyPercentage"></div>
						<div class="gauge-value" id="callCenterReadyPercentageValue">0%</div>
					</div>
					<div class="col-xs-4">
						<div class="gauge-div" id="emailMarketingReadyPercentage"></div>
						<div class="gauge-value" id="emailMarketingReadyPercentageValue">0%</div>
					</div>
					<div class="col-xs-4">
						<div class="gauge-div" id="smsReadyPercentage"></div>
						<div class="gauge-value" id="smsReadyPercentageValue">0%</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-4 text-center">
						<p class="description smaller">Call Center Ready</p>
					</div>
					<div class="col-xs-4 text-center">
						<p class="description smaller">Email Marketing Ready</p>
					</div>
					<div class="col-xs-4 text-center">
						<p class="description smaller">SMS Ready</p>
					</div>
				</div>
			</div>
			<div class="tile tile-dark">
				<h2 class="tile-title">Running Processes</h2>
				<div class="half-graph-div" id="graph-realtime-processes"></div>
			</div>
		</div>
	</div>
</div>