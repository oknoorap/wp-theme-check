<?php

/**
 * Themecheck Interface
 */
interface themecheck
{
	// Should return true for good/okay/acceptable, false for bad/not-okay/unacceptable.
	public function check( $php_files, $css_files, $other_files );

	// Should return an array of strings explaining any problems found.
	public function getError();
}

class Theme_Check_Validator {
	private $excludes = array();
	private $theme_path = '';
	private $result = array();

	/**
	 * Auto initializing
	 *
	 * @param string $path
	 * @param array $excludes
	 */
	public function __construct( $path = '', $excludes = array() ) {
		$this->theme_path = trailingslashit( $path );
		$this->excludes = $excludes;
		$this->run();
	}

	/**
	 * Display Result
	 *
	 * @return array
	 */
	public function result() {
		return $this->result;
	}

	/**
	 * Get file list inside directory
	 *
	 * @param string $dir
	 * @return void
	 */
	private function scandir( $dir = '' ) {
		$files = array();
		$dir_iterator = new RecursiveDirectoryIterator( $dir );
		$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $file) {
			array_push( $files, $file->getPathname() );
		}

		return $files;
	}

	/**
	 * Strip PHP comments
	 *
	 * @param string $codes
	 * @return void
	 */
	private function strip_comments( $codes = '' ) {
		$strip = array( T_COMMENT => true, T_DOC_COMMENT => true);
		$newlines = array( "\n" => true, "\r" => true );
		$tokens = token_get_all($codes);
		reset($tokens);

		$return = '';
		$token = current($tokens);

		while( $token ) {
			if( !is_array($token) ) {
				$return.= $token;
			} elseif( !isset( $strip[ $token[0] ] ) ) {
				$return.= $token[1];
			} else {
				for( $i = 0, $token_length = strlen($token[1]); $i < $token_length; ++$i )
				if( isset($newlines[ $token[1][$i] ]) )
				$return.= $token[1][$i];
			}
			$token = next($tokens);
		}

		return $return;
	}

	/**
	 * Get rule list from themecheckes
	 *
	 * @return array
	 */
	private function rule_list() {
		global $themechecks;
		return $themechecks;
	}

	/**
	 * Theme check iterator
	 *
	 * @param array $php_files
	 * @param array $css_files
	 * @param array $other_files
	 * @return void
	 */
	private function validate( $php_files, $css_files, $other_files ) {
		$pass = true;

		foreach($this->rule_list() as $rule) {
			if ($rule instanceof themecheck) {
				$pass = $pass & $rule->check($php_files, $css_files, $other_files);
			}
		}

		return $pass;
	}

	public function parse_errors( $err ) {
		$trac_left = array( '<strong>', '</strong>' );
		$trac_right= array( "'''", "'''" );
		$html_link = '/<a\s?href\s?=\s?[\'|"]([^"|\']*)[\'|"]>([^<]*)<\/a>/i';
		$html_new = '[$1 $2]';
		$err = preg_replace( $html_link, $html_new, $err );
		$err = str_replace( $trac_left, $trac_right, $err );
		$err = preg_replace( '/<pre.*?>/', "\r\n{{{\r\n", $err );
		$err = str_replace( '</pre>', "\r\n}}}\r\n", $err );

		return $err;
	}

	/**
	 * Display errors from theme check,
	 * and save it to result variable
	 *
	 * @return void
	 */
	private function dump_errors() {
		foreach ($this->rule_list() as $rule) {
			if ($rule instanceof themecheck) {
				$error = $rule->getError();
				$error_arr = (array) $error;

				if ( ! empty( $error ) ) {
					$this->result[] = array(
						'label' => get_class($rule),
						'items' => $error_arr,
					);
				}
			}
		}
	}

	/**
	 * Run and validate scripts
	 *
	 * @return void
	 */
	public function run() {
		global $themechecks;

		$data = get_theme_data_from_contents($this->theme_path . '/style.css');

		foreach( glob( PHP_SCRIPT_BASEDIR . '/theme-check/checks/*.php' ) as $rule ) {
			include $rule;
		}

		// Get files in theme path.
		$files = $this->scandir( $this->theme_path );

		if ( $files ) {
			$php_files = array();
			$css_files = array();

			// Get codes.
			foreach( $files as $index => $filename ) {
				if ( '.php' === substr( $filename, -4 ) && ! is_dir( $filename ) ) {
					$php_files[ $filename ] = $this->strip_comments( file_get_contents( $filename ) );
				} else if ( '.css' === substr( $filename, -4 ) && ! is_dir( $filename ) ) {
					$css_files[ $filename ] = file_get_contents( $filename );
				} else {
					$other_files[ $filename ] = ( ! is_dir($filename) ) ? file_get_contents( $filename ) : '';
				}
			}

			$success = $this->validate($php_files, $css_files, $other_files);
			$this->dump_errors();
		}
	}
}
