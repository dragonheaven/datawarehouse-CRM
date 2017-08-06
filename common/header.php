<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<meta http-equiv="X-UA-Compatible" content="chrome=1">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
		<title><?php echo $_dws_title_tag; ?></title>
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css" type="text/css" media="all">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.min.css" type="text/css" media="all">
		<link href="//fonts.googleapis.com/css?family=Open+Sans+Condensed:300|Open+Sans:300,400,600,700,800|Ubuntu|Ubuntu+Mono" rel="stylesheet">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" type="text/css" media="all">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/weather-icons/2.0.9/css/weather-icons-wind.min.css">
		<link rel="stylesheet" href="/resources/bootstrap-datetimepicker.min.css" type="text/css" media="all">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/flag-icon-css/2.8.0/css/flag-icon.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.0.10/font-awesome-animation.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/intro.js/2.5.0/introjs.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
		<link rel="stylesheet" href="/resources/dash-styles.css?v=<?php echo get_resource_version(); ?>" type="text/css" media="all">
		<link rel="stylesheet" href="/resources/style.css?v=<?php echo get_resource_version(); ?>" type="text/css" media="all">
		<link rel="stylesheet" href="/resources/theme.css?v=<?php echo get_resource_version(); ?>" type="text/css" media="all">
		<link rel="stylesheet" href="/resources/colors.css?v=<?php echo get_resource_version(); ?>" type="text/css" media="all">
		<?php echo tc_get_page_specific_styles( $template ); ?>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
		<link rel="apple-touch-icon-precomposed" sizes="57x57" href="/resources/images/favs/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/resources/images/favs/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/resources/images/favs/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="/resources/images/favs/apple-touch-icon-144x144.png" />
		<link rel="apple-touch-icon-precomposed" sizes="60x60" href="/resources/images/favs/apple-touch-icon-60x60.png" />
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="/resources/images/favs/apple-touch-icon-120x120.png" />
		<link rel="apple-touch-icon-precomposed" sizes="76x76" href="/resources/images/favs/apple-touch-icon-76x76.png" />
		<link rel="apple-touch-icon-precomposed" sizes="152x152" href="/resources/images/favs/apple-touch-icon-152x152.png" />
		<link rel="icon" type="image/png" href="/resources/images/favs/favicon-196x196.png" sizes="196x196" />
		<link rel="icon" type="image/png" href="/resources/images/favs/favicon-96x96.png" sizes="96x96" />
		<link rel="icon" type="image/png" href="/resources/images/favs/favicon-32x32.png" sizes="32x32" />
		<link rel="icon" type="image/png" href="/resources/images/favs/favicon-16x16.png" sizes="16x16" />
		<link rel="icon" type="image/png" href="/resources/images/favs/favicon-128.png" sizes="128x128" />
		<meta name="application-name" content="<?php echo APP; ?>"/>
		<meta name="msapplication-TileColor" content="#FFFFFF" />
		<meta name="msapplication-TileImage" content="/resources/images/favs/mstile-144x144.png" />
		<meta name="msapplication-square70x70logo" content="/resources/images/favs/mstile-70x70.png" />
		<meta name="msapplication-square150x150logo" content="/resources/images/favs/mstile-150x150.png" />
		<meta name="msapplication-wide310x150logo" content="/resources/images/favs/mstile-310x150.png" />
		<meta name="msapplication-square310x310logo" content="/resources/images/favs/mstile-310x310.png" />
	</head>
	<body<?php echo ( 'true' == get_array_key( 'card', $_GET ) ) ? ' class="card"' : ''; ?>>
		<div id="left-nav">
			<header class="text-center">
				<a id="side-menu-toggle"><span class="glyphicon glyphicon-th-list"></span></a>
			</header>
			<ul class="side-menu">
				<?php if ( is_user_login() ) { ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-pie-chart"></span>', '/', array(), '^/$', true, 'Dashboard' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-street-view"></span>', '/leads/view/', array(), '^/leads/view/([^/]*)$', true, 'View Leads' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-user-plus"></span>', '/leads/import-single/', array(), '^/leads/import-single/([^/]*)$', true, 'Import Lead' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-cloud-upload"></span>', '/leads/import/', array(), '^/leads/import/([^/]*)$', true, 'Import Lists' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-cloud-download"></span>', '/leads/export/', array(), '^/leads/export/([^/]*)$', true, 'Export Leads' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-floppy-o"></span>', '/leads/saved-queries/', array(), '^/leads/saved-queries/([^/]*)$', true, 'Saved Queries' ); ?>
				<?php make_side_nav_link( '<span class="no-margin fa fa-sliders"></span>', '/system/settings/', array(), '^/system/settings/([^/]*)$', true, 'Settings' ); ?>
				<li class="visible-xs visible-sm" title="View Console" data-placement="right"><a href="javascript:void(0);" class="open-import-log"><span class="no-margin fa fa-terminal"></span></a>							</li>
				<?php } ?>
			</ul>
		</div>
		<div id="left-nav-secondary">
			<header class="text-center"><h1 class="h5 uppercase">Data Warehouse</h1></header>
			<div class="m-b-25 text-center">
				<a href="javascript:void(0);" class="open-import-log"><img class="profile-pic animated" src="/resources/images/favs/favicon-196x196.png" alt="<?php echo get_current_user_info( 'user' ); ?>" width="120" height="120" /></a>
				<p>@<?php echo get_current_user_info( 'user' ); ?></p>
			</div>
			<ul class="m-b-25">
				<li>
					<a href="javascript:void(0);" class="logout-click-action"><span class="glyphicon glyphicon-log-out"></span> Log Out</a>
				</li>
			</ul>
			<div class="s-widget m-b-25" id="import-jobs-widget">
				<h2 class="tile-title">Importing Files</h2>
			</div>
			<div class="s-widget m-b-25">
				<h2 class="tile-title">Rows per Minute</h2>
				<div id="graph-rpm">
				</div>
			</div>
		</div>
		<div id="content">
			<header>
				<div>
					<div class="row">
						<div class="col-sm-6 col-sm-offset-6 col-md-4 col-md-offset-8">
							<span class="current-time">00:00:00 UTC</span>
						</div>
					</div>
				</div>
			</header>