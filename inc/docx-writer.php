<?php
/**
 * Minimal .docx writer (no external library): paragraphs and simple tables set in
 * one font, legal-size page by default. Used for the Bijoy/SutonnyMJ deed download.
 * Text passed in must already be in the font's own encoding (see inc/bijoy.php).
 */

if ( ! function_exists( 'sojib68_docx_build' ) ) {

	function sojib68_docx_x( $s ) {
		return htmlspecialchars( (string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/** One run of text; tabs and line breaks become real Word tabs/breaks. */
	function sojib68_docx_run( $text, $font, $size_half_pts, $bold = false ) {
		$rpr = '<w:rPr><w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '" w:cs="' . $font . '" w:eastAsia="' . $font . '"/>'
			. ( $bold ? '<w:b/>' : '' ) . '<w:sz w:val="' . (int) $size_half_pts . '"/><w:szCs w:val="' . (int) $size_half_pts . '"/></w:rPr>';
		$out   = '';
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		foreach ( $lines as $li => $line ) {
			if ( $li > 0 ) {
				$out .= '<w:r>' . $rpr . '<w:br/></w:r>';
			}
			$parts = explode( "\t", $line );
			foreach ( $parts as $pi => $part ) {
				if ( $pi > 0 ) {
					$out .= '<w:r>' . $rpr . '<w:tab/></w:r>';
				}
				if ( '' !== $part ) {
					$out .= '<w:r>' . $rpr . '<w:t xml:space="preserve">' . sojib68_docx_x( $part ) . '</w:t></w:r>';
				}
			}
		}
		return $out;
	}

	function sojib68_docx_paragraph( $text, $opts ) {
		$ppr = '<w:pPr><w:spacing w:before="0" w:after="' . (int) $opts['after'] . '" w:line="' . (int) $opts['line'] . '" w:lineRule="auto"/>'
			. ( ! empty( $opts['align'] ) ? '<w:jc w:val="' . $opts['align'] . '"/>' : '' ) . '</w:pPr>';
		return '<w:p>' . $ppr . sojib68_docx_run( $text, $opts['font'], $opts['size'], ! empty( $opts['bold'] ) ) . '</w:p>';
	}

	/**
	 * @param array $blocks  Each: array( 'type' => 'p', 'text' => ..., 'bold' => bool, 'align' => 'center' )
	 *                       or  array( 'type' => 'table', 'rows' => array( array( cell, cell ... ), ... ) ).
	 * @param array $page    font, size (half-points), width/height/margins in twips.
	 * @return string binary .docx or '' on failure
	 */
	function sojib68_docx_build( $blocks, $page = array() ) {
		$page = array_merge( array(
			'font'   => 'SutonnyMJ',
			'size'   => 28,
			'w'      => 12240,
			'h'      => 20160,
			'top'    => 1440,
			'bottom' => 1440,
			'left'   => 1152,
			'right'  => 1152,
			'line'   => 276,
			'after'  => 80,
		), $page );

		$body = '';
		foreach ( $blocks as $b ) {
			if ( isset( $b['type'] ) && 'table' === $b['type'] ) {
				$cols  = isset( $b['rows'][0] ) ? count( $b['rows'][0] ) : 1;
				$width = (int) floor( ( $page['w'] - $page['left'] - $page['right'] ) / max( 1, $cols ) );
				$body .= '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>';
				foreach ( array( 'top', 'left', 'bottom', 'right', 'insideH', 'insideV' ) as $edge ) {
					$body .= '<w:' . $edge . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
				}
				$body .= '</w:tblBorders><w:tblCellMar><w:left w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
				for ( $c = 0; $c < $cols; $c++ ) {
					$body .= '<w:gridCol w:w="' . $width . '"/>';
				}
				$body .= '</w:tblGrid>';
				foreach ( $b['rows'] as $row ) {
					$body .= '<w:tr>';
					foreach ( $row as $cell ) {
						$cell_text = is_array( $cell ) ? $cell['t'] : $cell;
						$cell_font = is_array( $cell ) && ! empty( $cell['font'] ) ? $cell['font'] : $page['font'];
						$body .= '<w:tc><w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/></w:tcPr>'
							. sojib68_docx_paragraph( $cell_text, array( 'font' => $cell_font, 'size' => $page['size'], 'after' => 0, 'line' => $page['line'] ) )
							. '</w:tc>';
					}
					$body .= '</w:tr>';
				}
				$body .= '</w:tbl>' . sojib68_docx_paragraph( '', array( 'font' => $page['font'], 'size' => $page['size'], 'after' => 0, 'line' => $page['line'] ) );
				continue;
			}
			$body .= sojib68_docx_paragraph(
				isset( $b['text'] ) ? $b['text'] : '',
				array(
					'font'  => isset( $b['font'] ) ? $b['font'] : $page['font'],
					'size'  => isset( $b['size'] ) ? $b['size'] : $page['size'],
					'bold'  => ! empty( $b['bold'] ),
					'align' => isset( $b['align'] ) ? $b['align'] : '',
					'after' => $page['after'],
					'line'  => $page['line'],
				)
			);
		}

		$sect = '<w:sectPr><w:pgSz w:w="' . $page['w'] . '" w:h="' . $page['h'] . '"/><w:pgMar w:top="' . $page['top'] . '" w:right="' . $page['right']
			. '" w:bottom="' . $page['bottom'] . '" w:left="' . $page['left'] . '" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr>';

		$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . $sect . '</w:body></w:document>';

		return sojib68_docx_package( $document );
	}

	/** Zip a finished word/document.xml together with the package skeleton. */
	function sojib68_docx_package( $document ) {
		$tmp = tempnam( sys_get_temp_dir(), 'dx' );
		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			return '';
		}
		// Package parts (styles, settings, theme, relationships) come from a known-good skeleton so Word accepts the file.
		$skeleton = __DIR__ . '/docx-skeleton';
		$zip->addFromString( '[Content_Types].xml', (string) file_get_contents( $skeleton . '/Content_Types.xml' ) );
		$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $skeleton, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			$rel = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $skeleton ) + 1 ) );
			if ( 'Content_Types.xml' === $rel ) {
				continue;
			}
			$zip->addFromString( $rel, (string) file_get_contents( $file->getPathname() ) );
		}
		$zip->addFromString( 'word/document.xml', $document );
		$zip->close();
		$bin = (string) file_get_contents( $tmp );
		@unlink( $tmp );
		return $bin;
	}
}
