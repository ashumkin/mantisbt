<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the MIT license

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

?>

<br/>
<form action="<?php echo plugin_page( 'manage_config' ) ?>" method="post">
<?php echo form_security_field( 'plugin_MantisCoreFile_manage_config' ) ?>
<table class="width75" align="center" cellspacing="1">

<tr>
	<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?> >
	<td class="category" width="60%">
		<?php echo plugin_lang_get( 'text_preview_rows' ) ?>
	</td>
	<td width="20%">
		<input name="text_preview_rows" size="30" value="<?php echo plugin_config_get('text_preview_rows') ?>">
	</td>
</tr>

<tr <?php echo helper_alternate_class() ?> >
	<td class="category" width="60%">
		<?php echo plugin_lang_get( 'text_preview_cols' ) ?>
	</td>
	<td width="20%">
		<input name="text_preview_cols" size="30" value="<?php echo plugin_config_get('text_preview_cols') ?>">
	</td>
</tr>

<tr <?php echo helper_alternate_class() ?> >
	<td class="category" width="60%">
		<?php echo plugin_lang_get( 'text_encoding' ) ?>
	</td>
	<td width="20%">
		<input name="text_encoding" size="30" value="<?php echo plugin_config_get('text_encoding') ?>">
	</td>
</tr>

<tr>
	<td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update_configuration' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

