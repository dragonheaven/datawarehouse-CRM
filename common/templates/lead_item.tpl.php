<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	global $_tc_countries;
	$l = null;
	try {
		$l = R::load( 'lead', absint( $more ) );
	}
	catch( Exception $e ) {
		// show exit message
		exit();
	}
	if ( ! isset( $l ) || ! is_object( $l ) ) {
		exit();
	}
	if ( ! is_empty( $l->profile_picture ) ) {
		$profile = $l->profile_picture;
	}
	else {
		$hash = ( is_object( $l->email ) ) ? md5( $l->email->email ) : 'b642b4217b34b1e8d3bd915fc65c4452';
		$profile = sprintf( '//s.gravatar.com/avatar/%s?s=240&d=mm&r=x', $hash );
	}
	$poster = ( ! is_empty( $l->poster_picture ) ) ? $l->poster_picture : '/resources/images/default-poster.jpg?v=5';
	$riskScore = 100 - ( 100 * absint( $l->riskScore ) );
	switch ( true ) {
		case ( $riskScore <= 10 ):
			$riskColor = 'success';
			break;

		case ( $riskScore <= 25 ):
			$riskColor = 'primary';
			break;

		case ( $riskScore <= 50 ):
			$riskColor = 'warning';
			break;

		default:
			$riskColor = 'danger';
			break;
	}

?>
<div class="row">
	<div class="col-xs-12">
		<ol class="breadcrumb">
			<li><a href="/"><?php echo APP; ?></a></li>
			<li><a href="/leads/view/">View Leads</a></li>
			<li class="active hidden-xs">Lead <?php echo absint( $more ); ?></li>
		</ol>
		<h4 class="page-title hidden-xs" data-clipboard-text="<?php echo make_lead_full_name( $l ); ?>"><?php echo absint( $more ); ?> | <?php echo make_lead_full_name( $l ); ?></h4>
	</div>
</div>
<div class="block-area m-b-25">
	<div class="row">
		<div class="col-md-8 col-lg-9">
			<h3 class="visible-xs"><?php echo make_lead_full_name( $l ); ?></h3>
			<div id="lead-poster" style="background-image:url(<?php echo $poster; ?>)">
				<div class="row">
					<div class="col-xs-12 lead-poster-buttons">
						<div>
							<div class="btn-group" role="group">
								<div class="input-group">
									<span class="input-group-btn hidden-xs hidden-sm hidden-md email-color">
										<!-- Email Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-envelope"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md phone-color">
										<!-- Phone Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-phone"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md sms-color">
										<!-- SMS Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-commenting"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md skype-color">
										<!-- Skype Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-skype"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md whatsapp-color">
										<!-- WhatsApp Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-whatsapp"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md facebook-color">
										<!-- FaceBook Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-facebook-square"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md linkedin-color">
										<!-- LinkedIn Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-linkedin-square"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md google-color">
										<!-- Google+ Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-google-plus-square"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md twitter-color">
										<!-- Twitter Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-twitter-square"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									<span class="input-group-btn hidden-xs hidden-sm hidden-md rss-color">
										<!-- Push Notification Buttons -->
										<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<span class="fa fa-rss-square"></span><span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="javascript:void(0);" class="text-danger"><span class="fa fa-ban"></span>Not Yet Implemented</a></li>
										</ul>
									</span>
									 <span class="input-group-addon hidden-xs hidden-sm hidden-md risk-color"><span class="fa fa-exclamation-triangle no-margin"></span></span>
									 <div class="form-control hidden-xs hidden-sm hidden-md risk-color">
									 	<?php if ( 100 !== $riskScore ) { ?>
									 	<small><?php echo absint( $riskScore ); ?>% Chance of Fraud</small>
									 	<div class="progress progress-small">
									 		<a href="#" data-toggle="tooltip" title="" class="progress-bar tooltips progress-bar-<?php echo $riskColor; ?>" style="width: <?php echo absint( $riskScore ); ?>%;" data-original-title="<?php echo absint( $riskScore ); ?>%"></a>
									 	</div>
									 	<?php } else { ?>
									 	<small class="visible-xlg">&nbsp;</small>
									 	<div class="progress progress-small">
									 		<a href="#" data-toggle="tooltip" title="" class="progress-bar tooltips progress-bar-info" style="width: 2em;" data-original-title="0%"></a>
									 	</div>
									 	<?php } ?>
									 </div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-4 col-lg-2 col-xs-6 text-center">
						<img src="<?php echo $profile; ?>" width="150" height="150" class="lead-profile-picture" />
					</div>
					<div class="col-sm-8 col-lg-10 col-xs-6 hidden-xs hidden-sm">
						<h1 class="lead-poster-name" data-clipboard-text="<?php echo make_lead_full_name( $l ); ?>"><?php echo make_lead_full_name( $l ); ?></h1>
					</div>
				</div>
			</div>
			<div class="tile" id="tag-tile">
				<?php
					if ( can_loop( $l->sharedTagList ) ) {
						$printed = array();
						foreach ( $l->sharedTagList as $beanId => $bean ) {
							if ( ! in_array( get_bean_property( 'tag', $bean ), $printed ) ) {
								echo sprintf( '<span><span class="label label-default">%s</span></span>', get_bean_property( 'tag', $bean ) );
								array_push( $printed, get_bean_property( 'tag', $bean ) );
							}
						}
					}
				?>
			</div>
		</div>
		<div class="col-md-4 col-lg-3">
			<div class="tile">
				<h4 class="tile-title">About</h4>
				<div class="listview icon-list">
					<div class="media">
						<span class="icon pull-left fa <?php echo ( 'female' == $l->gender ) ? 'fa-venus' : 'fa-mars'; ?>"></span>
						<span class="media-body">Gender: <?php echo ( 'female' == $l->gender ) ? 'Female' : 'Male'; ?></span>
					</div>
					<div class="media">
						<span class="icon pull-left fa fa-map-marker"></span>
						<span class="media-body" data-clipboard-text="<?php echo strip_tags( make_lead_address( $l ) ); ?>">From: <span class="m-l-5 flag-icon flag-icon-<?php echo strtolower( get_bean_property( 'country', $l ) ); ?>"></span><?php echo nl2br( make_lead_address( $l ) ); ?></span>
					</div>
					<div class="media">
						<span class="icon pull-left fa fa-sign-language"></span>
						<span class="media-body">Speaks: <?php
							if ( can_loop( $l->sharedLanguageList ) ) {
								$languages = array();
								foreach ( $l->sharedLanguageList as $beanId => $bean ) {
									$lang = get_bean_property( 'lang', $bean );
									if ( ! in_array( $lang, $languages ) ) {
										array_push( $languages, $lang );
									}
								}
								echo implode( ', ', $languages );
							}
							else {
								echo 'Unknown';
							}
						?></span>
					</div>
					<div class="media">
						<span class="icon pull-left fa fa-clock-o"></span>
						<span class="media-body">Captured: <?php echo strip_tags( get_bean_property( 'createtimestamp', $l ) ); ?></span>
					</div>
					<div class="media">
						<span class="icon pull-left fa fa-tag"></span>
						<span class="media-body" data-clipboard-text="<?php echo strip_tags( ( is_object( get_bean_property( 'source', $l ) ) ? $l->source->source : 'Unknown' ) ); ?>">Source: <?php echo strip_tags( ( is_object( get_bean_property( 'source', $l ) ) ? $l->source->source : 'Unknown' ) ); ?></span>
					</div>
					<div class="media">
						<span class="icon pull-left fa fa-download"></span>
						<span class="media-body">Exported: <?php echo absint( get_bean_property( 'exportcount', $l ) ); ?> &times;</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">Phone Numbers</h4>
				<div class="listview narrow">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedPhoneList ) ) {
							if ( ! array_key_exists( $l->phone->id, $l->sharedPhoneList ) ) {
								$l->sharedPhoneList[ $l->phone->id ] = $l->phone;
							}
							$played = array();
							foreach ( $l->sharedPhoneList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) ) {
						?>
					<div class="media p-l-5">
						<div class="pull-left">
							<div class="phone-atts-box phone-type phone-type-<?php echo get_bean_property( 'line_type', $bean ); ?>">
								<span class="phone-atts-phone-type">&nbsp;</span>
								<span class="phone-atts-phone-country"><span class="flag-icon flag-icon-<?php echo strtolower( $bean->country_code ); ?>"></span></span>
							</div>
						</div>
						<div class="media-body" data-clipboard-text="<?php echo strip_tags( get_bean_property( 'international_format', $bean ) ); ?>">
							<a class="news-title" href="javascript:void(0);"><?php echo strip_tags( get_bean_property( 'international_format', $bean ) ); ?></a>
							<div class="clearfix"></div>
							<a href="javascript:void(0);"><small class="text-muted"><?php echo ( true == get_bean_property( 'valid', $bean ) ) ? 'Valid' : 'Invalid' ?> <?php echo tc_get_phone_line_type_description( get_bean_property( 'line_type', $bean ) ); ?> phone number from <?php echo get_bean_property( 'country_name', $bean ); ?></small></a>
						</div>
					</div>
						<?php
										array_push( $played, $beanId );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">Email Addresses</h4>
				<div class="listview icon-list">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedEmailList ) ) {
							$played = array();
							foreach ( $l->sharedEmailList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) ) {
						?>
						<div class="media">
							<div class="pull-left">
								<span class="icon pull-left fa fa-<?php echo ( true == get_bean_property( 'validDomain', $bean ) ) ? 'check-circle' : 'times-circle' ?>"></span>
							</div>
							<span class="media-body" data-clipboard-text="<?php echo strip_tags( get_bean_property( 'email', $bean ) ); ?>"><?php echo strip_tags( get_bean_property( 'email', $bean ) ); ?></span>
						</div>
						<?php
										array_push( $played, $beanId );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">IP Addresses</h4>
				<div class="listview narrow">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedIpList ) ) {
							$played = array();
							foreach ( $l->sharedIpList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) ) {
						?>
					<div class="media p-l-5">
						<div class="pull-left d-37-h" data-clipboard-text="<?php echo tc_make_google_url_from_ip( $bean ); ?>">
							<img src="<?php echo tc_make_google_url_from_ip( $bean ); ?>" width="37" height="37" class="ipmap" />
						</div>
						<div class="media-body" data-clipboard-text="<?php echo get_bean_property( 'ip', $bean ); ?>">
							<a class="news-title" href="javascript:void(0);"><?php echo get_bean_property( 'ip', $bean ); ?></a>
							<div class="clearfix"></div>
							<a href="javascript:void(0);"><small class="text-muted">
								Continent: <span title="Continent"><?php echo get_bean_property( 'continent', $bean ); ?></span><br />
								Country: <span title="Country"><span class="m-l-5 flag-icon flag-icon-<?php echo strtolower( get_bean_property( 'country', $bean ) ); ?>"></span><?php echo get_array_key( 'name', get_array_key( get_bean_property( 'country', $bean ), $_tc_countries ) ); ?></span><br />
								City: <span title="City"><?php echo get_bean_property( 'city', $bean ); ?></span><br />
								ISP: <span title="ISP"><?php echo get_bean_property( 'isp', $bean ); ?></span><br />
								Organization: <span title="Organization"><?php echo get_bean_property( 'organization', $bean ); ?></span><br />
								Domain: <span title="Domain"><?php echo get_bean_property( 'domain', $bean ); ?></span><br />
								Map: <span title="Map"><?php echo implode( ', ', array( get_bean_property( 'latitude', $bean ), get_bean_property( 'longitude', $bean ) ) ); ?></span>
							</small></a>
						</div>
					</div>
						<?php
										array_push( $played, $beanId );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">Sources</h4>
				<div class="listview icon-list">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedSourceList ) ) {
							$played = array();
							$printed = array();
							foreach ( $l->sharedSourceList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) && ! in_array( get_bean_property( 'source', $bean ), $printed )) {
						?>
						<div class="media"><span class="media-body" data-clipboard-text="<?php echo strip_tags( get_bean_property( 'source', $bean ) ); ?>"><?php echo strip_tags( get_bean_property( 'source', $bean ) ); ?></span></div>
						<?php
										array_push( $played, $beanId );
										array_push( $printed, get_bean_property( 'source', $bean ) );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">Skypes</h4>
				<div class="listview icon-list">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedSkypeList ) ) {
							$played = array();
							foreach ( $l->sharedSkypeList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) ) {
						?>
						<div class="media">
							<div class="pull-left">
								<span class="icon pull-left fa fa-skype"></span>
							</div>
							<span class="media-body" data-clipboard-text="<?php echo strip_tags( get_bean_property( 'skype', $bean ) ); ?>"><?php echo strip_tags( get_bean_property( 'skype', $bean ) ); ?></span>
						</div>
						<?php
										array_push( $played, $beanId );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
		<div class="col-lg-9 col-sm-6">
			<div class="tile">
				<h4 class="tile-title">Meta Tags</h4>
				<div class="listview narrow">
						<?php
						if ( is_object( $l ) && can_loop( $l->sharedLeadmetaList ) ) {
							$played = array();
							foreach ( $l->sharedLeadmetaList as $beanId => $bean ) {
								if ( ! in_array( $beanId, $played ) ) {
						?>
					<div class="media p-l-5">
						<div class="media-body">
							<a class="news-title" href="javascript:void(0);"><?php echo strip_tags( get_bean_property( 'key', $bean ) ); ?></a>
							<div class="clearfix"></div>
							<a href="javascript:void(0);"><small class="text-muted"><?php echo strip_tags( get_bean_property( 'value', $bean ) ); ?></small></a>
						</div>
					</div>
						<?php
										array_push( $played, $beanId );
									}
								}
							}
						?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	//echo '<pre>';
	//print_r( $l );
	//echo '</pre>';