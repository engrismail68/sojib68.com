<?php
/**
 * Unicode Bangla  →  Bijoy (SutonnyMJ ANSI) text, for the Word deed download.
 *
 * Calibrated against a real SutonnyMJ deed file (conjunct codes such as ্ত → Í,
 * ক্ষ্ম → ², ল্ল → jø, রু → iæ come from that file, not from generic tables).
 * Pure function, no WordPress dependency, so it can be unit-tested on its own.
 */

if ( ! function_exists( 'sojib68_bijoy_table' ) ) {

	/** Conjunct sequences (Unicode) → Bijoy code. Longest match wins. */
	function sojib68_bijoy_table() {
		static $t = null;
		if ( null !== $t ) {
			return $t;
		}
		$t = array(
			'ক্ষ্ম' => '²', 'ক্ক' => '°', 'ক্ট' => '±', 'ক্ত' => '³', 'ক্ব' => 'K¡', 'ক্র' => 'µ', 'ক্ল' => 'K¬', 'ক্ষ' => '¶', 'ক্স' => '·',
			'গ্ধ' => '»', 'গ্ন' => 'Mœ', 'গ্ম' => 'M¥', 'গ্ল' => 'M­',
			'ঙ্ক' => '¼', 'ঙ্খ' => '•L', 'ঙ্গ' => '½', 'ঙ্ঘ' => '•N',
			'চ্ছ' => '”Q', 'চ্চ' => '”P', 'চ্ঞ' => '”T',
			'জ্জ' => '¾', 'জ্ঝ' => 'À', 'জ্ঞ' => 'Á', 'জ্ব' => 'R¡',
			'ঞ্চ' => 'Â', 'ঞ্ছ' => 'Ã', 'ঞ্জ' => 'Ä', 'ঞ্ঝ' => 'Å',
			'ট্ট' => 'Æ', 'ট্ব' => 'U¡', 'ট্ম' => 'U¥',
			'ড্ড' => 'Ç',
			'ণ্ট' => 'È', 'ণ্ঠ' => 'É', 'ণ্ড' => 'Ê', 'ণ্ব' => 'Y^',
			'ত্ত্ব' => 'Ë¡', 'ত্ত' => 'Ë', 'ত্থ' => 'Ì', 'ত্ন' => 'Zœ', 'ত্ম' => 'Z¥', 'ত্ব' => 'Z¡', 'ত্র' => 'Î',
			'থ্ব' => '_¡',
			'দ্গ' => '˜M', 'দ্ঘ' => '˜N', 'দ্দ' => 'Ï', 'দ্ধ' => '×', 'দ্ব' => 'Ø', 'দ্ভ' => '™¢', 'দ্ম' => 'Ù',
			'ধ্ব' => 'aŸ', 'ধ্ম' => 'a¥',
			'ন্ত্র' => 'š¿', 'ন্ত্ব' => 'šÍ¡', 'ন্তু' => 'šÍy', 'ন্ত' => 'šÍ', 'ন্ট' => '›U', 'ন্ঠ' => 'Ú', 'ন্ড' => 'Û', 'ন্থ' => 'š’',
			'ন্দ্ব' => '›Ø', 'ন্দ' => '›`', 'ন্ধ' => 'Ü', 'ন্ন' => 'bœ', 'ন্ব' => 'š^', 'ন্ম' => 'b¥', 'ন্স' => 'Ý',
			'প্ট' => 'Þ', 'প্ত' => 'ß', 'প্ন' => 'cœ', 'প্প' => 'à', 'প্ল' => 'c­', 'প্স' => 'á',
			'ফ্ল' => 'd¬',
			'ব্জ' => 'â', 'ব্দ' => 'ã', 'ব্ধ' => 'ä', 'ব্ব' => 'eŸ', 'ব্ল' => 'e­',
			'ভ্র' => 'å',
			'ম্ন' => '¤œ', 'ম্প' => '¤ú', 'ম্ফ' => 'ç', 'ম্ব' => '¤^', 'ম্ভ' => '¤¢', 'ম্ম' => '¤§', 'ম্ল' => '¤­',
			'ল্ক' => 'é', 'ল্গ' => 'ê', 'ল্ট' => 'ë', 'ল্ড' => 'ì', 'ল্প' => 'í', 'ল্ফ' => 'î', 'ল্ব' => 'j¦', 'ল্ম' => 'j¥', 'ল্ল' => 'jø',
			'শ্চ' => 'ð', 'শ্ন' => 'kœ', 'শ্ব' => 'k¦', 'শ্ম' => 'k¥', 'শ্ল' => 'k­',
			'ষ্ক' => '®‹', 'ষ্ট' => 'ó', 'ষ্ঠ' => 'ô', 'ষ্ণ' => 'ò', 'ষ্প' => '®ú', 'ষ্ফ' => 'õ', 'ষ্ম' => '®§',
			'স্ক' => '¯‹', 'স্ট' => '÷', 'স্খ' => 'ö', 'স্ত্র' => '¯¿', 'স্তু' => '¯‘', 'স্ত' => '¯Í', 'স্থ' => '¯’', 'স্ন' => 'mœ', 'স্প' => '¯ú', 'স্ফ' => 'ù', 'স্ব' => '¯^', 'স্ম' => '¯§', 'স্ল' => '¯­',
			'হ্ণ' => 'nè', 'হ্ব' => 'nŸ', 'হ্ন' => 'ý', 'হ্ম' => 'þ', 'হ্ল' => 'n¬',
		);
		uksort( $t, function ( $a, $b ) {
			return mb_strlen( $b, 'UTF-8' ) - mb_strlen( $a, 'UTF-8' );
		} );
		return $t;
	}

	/** Single letters. */
	function sojib68_bijoy_letters() {
		return array(
			'অ' => 'A', 'আ' => 'Av', 'ই' => 'B', 'ঈ' => 'C', 'উ' => 'D', 'ঊ' => 'E', 'ঋ' => 'F', 'এ' => 'G', 'ঐ' => 'H', 'ও' => 'I', 'ঔ' => 'J',
			'ক' => 'K', 'খ' => 'L', 'গ' => 'M', 'ঘ' => 'N', 'ঙ' => 'O', 'চ' => 'P', 'ছ' => 'Q', 'জ' => 'R', 'ঝ' => 'S', 'ঞ' => 'T',
			'ট' => 'U', 'ঠ' => 'V', 'ড' => 'W', 'ঢ' => 'X', 'ণ' => 'Y', 'ত' => 'Z', 'থ' => '_', 'দ' => '`', 'ধ' => 'a', 'ন' => 'b',
			'প' => 'c', 'ফ' => 'd', 'ব' => 'e', 'ভ' => 'f', 'ম' => 'g', 'য' => 'h', 'র' => 'i', 'ল' => 'j', 'শ' => 'k', 'ষ' => 'l',
			'স' => 'm', 'হ' => 'n', "\u{09DC}" => 'o', "\u{09DD}" => 'p', "\u{09DF}" => 'q', 'ৎ' => 'r',
		);
	}

	/** Consonant + vowel sign that Bijoy writes as one glyph. */
	function sojib68_bijoy_syllables() {
		return array(
			'গু' => '¸', 'রু' => 'iæ', 'রূ' => 'iƒ', 'শু' => 'ï', 'হু' => 'û', 'হৃ' => 'ü',
		);
	}

	function sojib68_bijoy_is_consonant( $c ) {
		return null !== $c && '' !== $c && ( ( $c >= 'ক' && $c <= 'হ' ) || "\u{09DC}" === $c || "\u{09DD}" === $c || "\u{09DF}" === $c || 'ৎ' === $c );
	}

	/**
	 * Convert Unicode Bangla to Bijoy ANSI text.
	 *
	 * @param string $text Unicode text (may contain line breaks, tabs, digits, Latin punctuation).
	 * @return string Bijoy-encoded text (to be set in the SutonnyMJ font).
	 */
	function sojib68_bijoy_from_unicode( $text ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return '';
		}
		// Remove joiners; use the precomposed letters য় ড় ঢ় (U+09DF, U+09DC, U+09DD).
		$text = str_replace(
			array( "\u{200C}", "\u{200D}", "য\u{09BC}", "ড\u{09BC}", "ঢ\u{09BC}" ),
			array( '', '', "\u{09DF}", "\u{09DC}", "\u{09DD}" ),
			$text
		);

		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$n     = count( $chars );
		$table = sojib68_bijoy_table();
		$let   = sojib68_bijoy_letters();
		$syl   = sojib68_bijoy_syllables();
		$punct = array(
			'।' => '|', 'ঃ' => 't', 'ং' => 's', 'ঁ' => 'u', '‘' => 'Ô', '’' => 'Õ', '“' => 'Ò', '”' => 'Ó',
			'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
		);
		$post_map = array( 'া' => 'v', 'ী' => 'x', 'ু' => 'y', 'ূ' => '~', 'ৃ' => '„', 'ৗ' => 'Š' );
		$out = '';
		$i   = 0;

		while ( $i < $n ) {
			$c = $chars[ $i ];

			if ( sojib68_bijoy_is_consonant( $c ) ) {
				$j    = $i;
				$reph = false;
				// Reph: র + ্ + consonant (and the র starts the cluster).
				if ( 'র' === $c && $j + 2 < $n && '্' === $chars[ $j + 1 ] && sojib68_bijoy_is_consonant( $chars[ $j + 2 ] ) ) {
					$reph = true;
					$j   += 2;
				}
				$cluster = $chars[ $j ];
				$j++;
				while ( $j + 1 < $n && '্' === $chars[ $j ] && sojib68_bijoy_is_consonant( $chars[ $j + 1 ] ) ) {
					$cluster .= '্' . $chars[ $j + 1 ];
					$j       += 2;
				}
				// Vowel signs and marks that follow.
				$pre    = '';
				$post   = '';
				$nukta  = '';
				$tail   = '';
				$matras = array();
				while ( $j < $n ) {
					$m = $chars[ $j ];
					if ( 'ি' === $m ) {
						$pre = 'w' . $pre; $matras[] = $m;
					} elseif ( 'ে' === $m ) {
						$pre = '‡' . $pre;
					} elseif ( 'ৈ' === $m ) {
						$pre = 'ˆ' . $pre;
					} elseif ( 'ো' === $m ) {
						$pre = '‡' . $pre; $post .= 'v';
					} elseif ( 'ৌ' === $m ) {
						$pre = '‡' . $pre; $post .= 'Š';
					} elseif ( isset( $post_map[ $m ] ) ) {
						$post .= $post_map[ $m ]; $matras[] = $m;
					} elseif ( 'ঁ' === $m ) {
						$post .= 'u';
					} elseif ( 'ং' === $m || 'ঃ' === $m ) {
						$tail .= $punct[ $m ];
					} else {
						break;
					}
					$j++;
				}

				// ু is written 'z' (lower) after some letters in SutonnyMJ, 'y' after the rest.
				if ( false !== strpos( $post, 'y' ) && 1 === mb_strlen( $cluster, 'UTF-8' ) && false !== strpos( 'KPQZdf', substr( sojib68_bijoy_letters()[ $cluster ] ?? '', 0, 1 ) ?: '#' ) ) {
					$post = preg_replace( '/y/', 'z', $post, 1 );
				}

				// Single consonant + vowel sign written as one glyph (গু, রু, রূ, শু, হু, হৃ).
				$glyph = null;
				if ( ! $reph && 1 === mb_strlen( $cluster, 'UTF-8' ) && 1 === count( $matras ) ) {
					$key = $cluster . $matras[0];
					if ( isset( $syl[ $key ] ) && ( 'ু' === $matras[0] || 'ূ' === $matras[0] || 'ৃ' === $matras[0] ) ) {
						$glyph = $syl[ $key ];
						$post  = preg_replace( '/' . preg_quote( $post_map[ $matras[0] ], '/' ) . '/u', '', $post, 1 );
					}
				}

				// স্তু / ন্তু are single glyphs too.
				if ( null === $glyph && 1 === count( $matras ) && 'ু' === $matras[0] && in_array( $cluster, array( 'স্ত', 'ন্ত' ), true ) ) {
					$glyph = ( 'স্ত' === $cluster ) ? '¯‘' : 'š‘';
					$post  = preg_replace( '/y/', '', $post, 1 );
				}

				if ( null === $glyph ) {
					$glyph = sojib68_bijoy_cluster( $cluster, $table, $let );
				}
				$out .= $pre . $glyph . ( $reph ? '©' : '' ) . $post . $tail;
				$i    = $j;
				continue;
			}

			if ( isset( $let[ $c ] ) ) {
				$out .= $let[ $c ];
			} elseif ( isset( $punct[ $c ] ) ) {
				$out .= $punct[ $c ];
			} elseif ( isset( $post_map[ $c ] ) ) {
				$out .= $post_map[ $c ];
			} elseif ( 'ি' === $c ) {
				$out .= 'w';
			} elseif ( 'ে' === $c ) {
				$out .= '‡';
			} elseif ( 'ৈ' === $c ) {
				$out .= 'ˆ';
			} elseif ( 'ো' === $c ) {
				$out .= '‡v';
			} elseif ( 'ৌ' === $c ) {
				$out .= '‡Š';
			} else {
				$out .= $c;
			}
			$i++;
		}
		return $out;
	}

	/** One consonant cluster (letters joined by ্) → Bijoy. */
	function sojib68_bijoy_cluster( $cluster, $table, $let ) {
		$chars = preg_split( '//u', $cluster, -1, PREG_SPLIT_NO_EMPTY );
		$n     = count( $chars );
		$out   = '';
		$i     = 0;
		while ( $i < $n ) {
			$matched = false;
			foreach ( $table as $seq => $code ) {
				$len = mb_strlen( $seq, 'UTF-8' );
				if ( $i + $len <= $n && implode( '', array_slice( $chars, $i, $len ) ) === $seq ) {
					$out    .= $code;
					$i      += $len;
					$matched = true;
					break;
				}
			}
			if ( $matched ) {
				continue;
			}
			$c = $chars[ $i ];
			if ( '্' === $c && $i + 1 < $n ) {
				$next = $chars[ $i + 1 ];
				if ( 'য' === $next ) {
					$out .= '¨';
					$i   += 2;
					continue;
				}
				if ( 'র' === $next ) {
					$prev = '' === $out ? '' : mb_substr( $out, -1, 1, 'UTF-8' );
					$out .= ( 'c' === $prev || 'M' === $prev ) ? 'Ö' : 'ª';
					$i   += 2;
					continue;
				}
				$out .= '&';
				$i++;
				continue;
			}
			$out .= isset( $let[ $c ] ) ? $let[ $c ] : $c;
			$i++;
		}
		return $out;
	}
}
