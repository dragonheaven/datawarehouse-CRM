<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
					<div id="terminal-wrapper">
						<a href="javascript:void(0);" id="close-terminal" class="pull-right">&times;</a>
						<div id="terminal"></div>
					</div>
				</div>
				<footer>
					<label><?php echo APP; ?> Version <?php echo VERSION; ?> | Licensed for Site <i><?php echo LICENSE_SITE; ?></i></label>
				</footer>
			</div>
		</div>
		<script type="text/javascript">var tcd = <?php echo json_encode( tc_get_client_js_data( $template ) ); ?></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.14.0/jquery.validate.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/sprintf/1.0.3/sprintf.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.10/highcharts.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.10/highcharts-more.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.10/js/modules/map.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.10/js/modules/solid-gauge.js"></script>
		<script type="text/javascript" src="/resources/geo.js?v=<?php echo get_resource_version(); ?>"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/intro.js/2.5.0/intro.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.6.1/clipboard.min.js"></script>
		<script type="text/javascript" src="/resources/bootstrap-datetimepicker.min.js"></script>
		<script type="text/javascript" src="/resources/uploader/jquery.ui.widget.js"></script>
		<script type="text/javascript" src="/resources/uploader/jquery.iframe-transport.js"></script>
		<script type="text/javascript" src="/resources/uploader/jquery.fileupload.js"></script>
		<script type="text/javascript" src="/resources/tcldwstreamer.js?v=<?php echo get_resource_version(); ?>"></script>
		<script type="text/javascript" src="/resources/js.js?v=<?php echo get_resource_version(); ?>"></script>
		<?php echo tc_get_page_specific_scripts( $template ); ?>
	</body>
</html>