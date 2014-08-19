<div class="wrap" id="go-syncuser-admin-wrap">
	<h2>GO Sync User</h2>

	<table class="form-table">
		<tr valign="top">
			<th scope="row">Enable Debug Slogging</th>
			<td><input id="<?php echo $this->core->slug . '-debug'; ?>" name="<?php echo $this->core->slug . '[debug]'; ?>" type="checkbox" value="1" <?php checked( $this->core->debug() ); ?> /></td>
		</tr>
	</table>
</div>