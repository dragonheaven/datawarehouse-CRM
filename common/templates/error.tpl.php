<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<h1 class="text-danger">ERROR</h1>
<p>An error occured with the following message: <code><?php echo $title; ?></code></p>
<?php if ( can_loop( $errors ) ) { ?>
<p>Additional Error Information:</p>
<ul>
<?php foreach( $errors as $error ) { ?>
	<?php echo sprintf( '<li><span class="text-danger">%s</span></li>', $error ); ?>
<?php } ?>
</ul>
<?php } ?>