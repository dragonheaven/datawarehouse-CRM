<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	$queries = tc_get_user_saved_queries();
?>
<h1><span class="fa fa-floppy-o""></span>Saved Queries</h1>
<ol class="breadcrumb">
	<li><a href="/"><?php echo APP; ?></a></li>
	<li class="active">Saved Queries</li>
</ol>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h4>
			<?php echo generate_tutorial_tag( 'saved-queries-tutorial' ); ?>
			Saved Queries
			<a href="/leads/saved-queries/new" class="btn btn-primary btn-sm pull-right">New Query</a>
		</h4>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-striped">
			<thead>
				<tr>
					<th width="75">&nbsp;</th>
					<th><strong>Query Name</strong></th>
					<th width="120" class="text-center"><strong>Public Query</strong></th>
					<th width="120" class="text-center"><strong>Shown in Graph</strong></th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ( can_loop( $queries ) ) {
				foreach ( $queries as $query ) {
			?>
				<tr>
					<td>
					<?php if ( tc_get_session( 'user' ) == $query->owner ) { ?>
						<a href="/leads/saved-queries/<?php echo absint( $query->id ); ?>" class="btn btn-xs btn-warning btn-block">Edit</a>
					<?php } ?>
					</td>
					<td><?php echo strip_tags( $query->name ); ?></td>
					<td class="text-center"><input type="checkbox" readonly disabled <?php echo ( true == $query->public ) ? 'checked' : ''; ?> /></td>
					<td class="text-center"><input type="checkbox" readonly disabled <?php echo ( true == $query->show_graph ) ? 'checked' : ''; ?> /></td>
				</tr>
			<?php
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>