<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the MIT license

form_security_validate( 'plugin_MantisCoreFile_manage_config' );
auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

#$f_manage_threshold = gpc_get_int( 'manage_threshold' );
$f_text_preview_rows = gpc_get_int( 'text_preview_rows');
$f_text_preview_cols = gpc_get_int( 'text_preview_cols');
$f_text_encoding = gpc_get_string( 'text_encoding' );

#maybe_set_option( 'manage_threshold', $f_manage_threshold );
plugin_config_set( 'text_preview_rows', $f_text_preview_rows );
plugin_config_set( 'text_preview_cols', $f_text_preview_cols );
plugin_config_set( 'text_encoding', $f_text_encoding );

form_security_purge( 'plugin_MantisCoreFile_manage_config' );

print_successful_redirect( plugin_page( 'config', true ) );

