<?php

# Copyright (c) 2012 Alexey Shumkin
# Licensed under the GNU license

class MantisCoreFilePlugin extends MantisPlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.13'
		);

		$this->author = 'Alexey Shumkin';
		$this->contact = 'Alex.Crezoff@gmail.com';
		$this->url = 'http://github.com/ashumkin';
		$this->page = 'config';
	}

	public function config() {
		return array(
			'manage_threshold' => DEVELOPER,
			'text_preview_rows' => 25,
			'text_preview_cols' => 100,
			'text_encoding' => 'cp1251',
		);
	}

	public function hooks() {
		return array(
			'EVENT_LAYOUT_RESOURCES' => 'resources',
			'EVENT_FILE_FILES_GOT' => 'files_got',
		    'EVENT_FILE_FILES_SHOW' => 'show_files',
		    'EVENT_FILE_SHOW' => 'show_file',
			'EVENT_FILE_SHOW_CONTENT' => 'show_file_content',
			'EVENT_FILE_IS_TO_SHOW_CONTENT' => 'is_to_show_content',
			'EVENT_FILE_UPDATE_PREVIEW_STATE' => 'update_preview_state',
			'EVENT_FILE_DOWNLOAD_PREPARE' => 'download_file',
			'EVENT_FILE_CAN_DOWNLOAD' => 'can_download'
		);
	}

	public function files_got( $event, $p_bug_id, $p_attachments ) {
		return array( $p_bug_id, $p_attachments );
	}

	public function can_download( $event, $p_type, $p_file_id, $p_bug_id, $p_user_id, $p_project_id ) {
		# Check access rights
		switch ( $p_type ) {
			case 'bug':
				if ( !file_can_download_bug_attachments( $p_bug_id, (int)$p_user_id ) ) {
					return false;
				}
				return true;
			case 'doc':
				# Check if project documentation feature is enabled.
				if ( OFF == config_get( 'enable_project_documentation' ) ) {
					return false;
				}

				access_ensure_project_level( config_get( 'view_proj_doc_threshold' ), $p_project_id );
				return true;
		}
	}

	public function download_file( $event, $p_type, $p_file_id, $p_bug_id ) {
		# we handle the case where the file is attached to a bug
		# or attached to a project as a project doc.
		$query = '';
		switch ( $p_type ) {
			case 'bug':
				$t_download_file_table = db_get_table( 'mantis_bug_file_table' );
				break;
			case 'doc':
				$t_download_file_table = db_get_table( 'mantis_project_file_table' );
				break;
			default:
				$t_download_file_table = event_signal( 'EVENT_FILE_DOWNLOAD_QUERY', array( $p_type ) );
				if ( is_null( $t_download_file_table ) ) {
					return;
				}
		}
		$query = "SELECT * FROM $t_download_file_table WHERE id=" . db_param();
		$result = db_query_bound( $query, array( $p_file_id ) );
		$row = db_fetch_array( $result );
		return $row;
	}

	public function resources( $p_event ) {
		return '<script type="text/javascript" src="' . plugin_file( 'swap_content.js' ) . '"></script>';
	}

	public static function delTree( $p_dir ) {
		$t_files = array_diff( scandir( $p_dir ), array( '.', '..' ) );
		foreach ( $t_files as $t_file ) {
			$t_file = $p_dir . DIRECTORY_SEPARATOR . $t_file;
			if ( is_dir( $t_file ) ) {
				$t_class = get_class();
				$t_class::delTree( $t_file );
			} else {
				unlink( $t_file );
			}
		}
		if ( is_dir( $p_dir ) ) {
			rmdir( $p_dir );
		}
	}

	public function update_preview_state( $event, $p_attachment ) {
		$t_preview_text_ext = config_get( 'preview_text_extensions' );
		$t_preview_image_ext = config_get( 'preview_image_extensions' );
		$t_ext = $p_attachment['alt'];
		$t_filesize = $p_attachment['size'];

		if ( $p_attachment['exists']
				&& $p_attachment['can_download']
				&& $t_filesize != 0
				&& false !== $p_attachment['preview']
				&& $t_filesize <= config_get( 'preview_attachments_inline_max_size' ) ) {
			if ( in_array( $t_ext, $t_preview_text_ext, true ) ) {
				$p_attachment['preview'] = true;
				$p_attachment['type'] = 'text';
			} else if ( in_array( $t_ext, $t_preview_image_ext, true ) ) {
				$p_attachment['preview'] = true;
				$p_attachment['type'] = 'image';
			}
		}
		return array( $p_attachment );
	}

	public function show_files( $event, $p_attachments ) {
		$t_attachments_count = count( $p_attachments );

		$i = 0;
		$image_previewed = false;

		foreach ( $p_attachments as $t_attachment ) {
			list( $t_attachment, $image_previewed ) = event_signal( 'EVENT_FILE_SHOW', array( $t_attachment, $image_previewed ) );
			if ( $i != ( $t_attachments_count - 1 ) ) {
				echo "<br />\n";
				$i++;
			}
		}
	}

	public function show_file( $event, $p_attachment, $p_image_previewed ) {
		$t_attachment = $p_attachment;
		$image_previewed = $p_image_previewed;
		$t_file_display_name = string_display_line( $t_attachment['display_name'] );
		$t_filesize = number_format( $t_attachment['size'] );
		$t_date_added = date( config_get( 'normal_date_format' ), $t_attachment['date_added'] );

		if ( $image_previewed ) {
			$image_previewed = false;
			echo '<br />';
		}

		if ( $t_attachment['can_download'] ) {
			$t_href_start = '<a href="' . string_attribute( $t_attachment['download_url'] ) . '">';
			$t_href_end = '</a>';

			$t_href_clicket = " [<a href=\"" . $t_attachment['download_url'] . "\" target=\"_blank\">^</a>]";
		} else {
			$t_href_start = '';
			$t_href_end = '';
			$t_href_clicket = '';
		}

		if ( !$t_attachment['exists'] ) {
			print_file_icon( $t_file_display_name );
			echo '&#160;<span class="strike">' . $t_file_display_name . '</span>' . lang_get( 'word_separator' ) . '(' . lang_get( 'attachment_missing' ) . ')';
		} else {
			echo $t_href_start;
			print_file_icon( $t_file_display_name );
			echo $t_href_end . '&#160;' . $t_href_start . $t_file_display_name . $t_href_end . $t_href_clicket . ' (' . $t_filesize . ' ' . lang_get( 'bytes' ) . ') ' . '<span class="italic">' . $t_date_added . '</span>';
		}

		if ( $t_attachment['can_delete'] ) {
			echo '&#160;[';
			print_link( 'bug_file_delete.php?file_id=' . $t_attachment['id'] . form_security_param( 'bug_file_delete' ), lang_get( 'delete_link' ), false, 'small' );
			echo ']';
		}

		if ( $t_attachment['exists'] ) {
			if ( ( FTP == config_get( 'file_upload_method' ) ) ) {
				echo ' (' . lang_get( 'cached' ) . ')';
			}

			if ( event_signal( 'EVENT_FILE_IS_TO_SHOW_CONTENT', array( $t_attachment ) ) ) {
				 $c_id = $t_attachment['id'];
				 $t_bug_file_table = db_get_table( 'mantis_bug_file_table' );

				echo " <span id=\"hideSection_$c_id\">[<a class=\"small\" href='#' id='attmlink_" . $c_id . "' onclick='swap_content(\"hideSection_" . $c_id . "\");swap_content(\"showSection_" . $c_id . "\");return false;'>" . lang_get( 'show_content' ) . "</a>]</span>";
				echo " <span style='display:none' id=\"showSection_$c_id\">[<a class=\"small\" href='#' id='attmlink_" . $c_id . "' onclick='swap_content(\"hideSection_" . $c_id . "\");swap_content(\"showSection_" . $c_id . "\");return false;'>" . lang_get( 'hide_content' ) . "</a>]";

				echo "<pre>";
				 $c_id = db_prepare_int( $c_id );

				/** @todo Refactor into a method that gets contents for download / preview. */
				switch( config_get( 'file_upload_method' ) ) {
					case DISK:
						if ( $t_attachment['exists'] ) {
							$v_content = file_get_contents( $t_attachment['diskfile'] );
						}
						break;
					case FTP:
						if( file_exists( $t_attachment['exists'] ) ) {
							file_get_contents( $t_attachment['diskfile'] );
						} else {
							$ftp = file_ftp_connect();
							file_ftp_get( $ftp, $t_attachment['diskfile'], $t_attachment['diskfile'] );
							file_ftp_disconnect( $ftp );
							$v_content = file_get_contents( $t_attachment['diskfile'] );
						}
						break;
					default:
						$query = "SELECT *
										FROM $t_bug_file_table
										WHERE id=" . db_param();
						$result = db_query_bound( $query, Array( $c_id ) );
						$row = db_fetch_array( $result );
						$v_content = $row['content'];
				}

				event_signal( 'EVENT_FILE_SHOW_CONTENT', array( $t_attachment, $v_content, false ) );
				echo "</pre></span>\n";
			}

			if ( $t_attachment['can_download'] && $t_attachment['preview'] && $t_attachment['type'] == 'image' ) {
				$t_preview_style = 'border: 0;';
				$t_max_width = config_get( 'preview_max_width' );
				if( $t_max_width > 0 ) {
					$t_preview_style .= ' max-width:' . $t_max_width . 'px;';
				}

				$t_max_height = config_get( 'preview_max_height' );
				if( $t_max_height > 0 ) {
					$t_preview_style .= ' max-height:' . $t_max_height . 'px;';
				}

				$t_preview_style = 'style="' . $t_preview_style . '"';
				$t_title = file_get_field( $t_attachment['id'], 'title' );

				$t_image_url = $t_attachment['download_url'] . '&amp;show_inline=1' . form_security_param( 'file_show_inline' );

				echo "\n<br />$t_href_start<img alt=\"$t_title\" $t_preview_style src=\"$t_image_url\" />$t_href_end";
				$image_previewed = true;
			}
		}
		return array( $t_attachment, $image_previewed );
	}

	public function show_file_content( $event, $p_attachment, $p_content, $p_handled ) {
		if ( ( $p_attachment['type'] == 'text' ) && !$p_handled ) {
			$t_content = $p_content;
			$t_encoding = plugin_config_get( 'text_encoding' );
			if ( $t_encoding && extension_loaded('iconv') ) {
				$t_content = iconv( $t_encoding, 'utf-8', $t_content );
			}
			$t_rows = plugin_config_get( 'text_preview_rows' );
			$t_cols = plugin_config_get( 'text_preview_cols' );
			echo "<textarea cols=\"$t_cols\" rows=\"$t_rows\" readonly=\"true\" wrap=\"off\">";
			echo htmlspecialchars( $t_content );
			echo '</textarea>';
			$p_handled = true;
		}
		return array( $p_attachment, $p_content, $p_handled );
	}

	public function is_to_show_content( $event, $p_attachment ) {
		if ( $p_attachment['preview'] && ( $p_attachment['type'] == 'text' ) ) {
			return true;
		}
	}
}
