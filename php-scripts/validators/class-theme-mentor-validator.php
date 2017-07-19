<?php

class Theme_Mentor_Validator {
	public static $validations = array();
	private $templates = array();
	private $excludes = array();
	private $includes = array();
	private $theme_path = '';
	private $result = array();

	/**
	 * Auto initializing
	 *
	 * @param string $path
	 * @param array $excludes
	 */

	public function __construct( $path = '', $excludes = array() ) {
		$this->theme_path = $path;
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
	 * Do the regex for the possibly dangerous snippets
	 *
	 * @param regex $pattern
	 * @param error message text $message
	 * @param path to file when something happened $file_path
	 * @param file to run after $file
	 */
	public function add_result( $pattern, $message, $file_path, $file ) {
		$lines_found = preg_grep( $pattern, $file );

		if ( !empty( $lines_found ) ) {
			foreach ( $lines_found as $line => $snippet ) {
				$this->result[] = array(
					'filename' => $file_path,
					'line' => $line + 1,
					'type' => 'error',
					'message' => $message,
					'snippet' => $snippet,
				);
			}
		}
	}

	/**
	* Iterate theme folder and assign templates and includes
	* @param string $folder folder path
	* @param int $level depth of the nesting
	*/
	public function iterate_folder( $folder, $level = 0 ) {
		// Get all templates.
		$folder = trailingslashit( $folder );
		$directory  = dir( $folder );

		if ( in_array( basename( $folder ), $this->excludes, true ) ) {
			return;
		}

		while ( false !== ( $entry = $directory->read() ) ) {
			// Drop all empty folders, hidden folders/files and parents.
			if ( ( $entry[ 0 ] == "." ) ) {
				continue;
			}

			// Includes should be there.
			if ( is_dir( $folder . $entry ) ) {
				// Iterate the next level.
				$this->iterate_folder( $folder . $entry, $level + 1 );
			} else {
				// Read only PHP files.
				if ( substr( $entry, -4, 4 ) === '.php' ) {
					if ( $level === 0 ) {
						// templates on level 0
						$this->templates[] = $folder . $entry;
					} else {
						// includes
						$this->includes[] = $folder . $entry;
					}
				}
			}
		}
	}

	/**
	 * Run Check
	 *
	 * @return void
	 */
	public function run() {
		$this->iterate_folder( $this->theme_path, 0 );

		// Swap functions.php as it's include-alike.
		$functions_file = $this->theme_path . 'functions.php';
		foreach ( $this->templates as $index => $template ) {
			if ( $template === $functions_file ) {
				unset( $this->templates[ $index ] );
				$this->includes[] = $this->theme_path . 'functions.php';
			}
		}
		// Include check files.
		include PHP_SCRIPT_BASEDIR . '/theme-mentor/inc/general-theme-validations.php';
		$general_validations = new General_Theme_Validations();

		// Include complex checks.
		include PHP_SCRIPT_BASEDIR . '/theme-mentor/theme-mentor-executor.php';
		$dir = 'inc/complex';
		foreach ( glob( dirname( __FILE__ ) . "/{$dir}/*.php" ) as $file ) {
			include $file;
		}

		// Iterate all templates.
		foreach ( $this->templates as $index => $template ) {
			// Only unique theme stuff.
			$template_unique_only = str_replace( $this->theme_path, '', $template );

			// read the files, keep the file number as it matters, you know
			$file = file( $template, FILE_IGNORE_NEW_LINES );
			if ( false === $file ) {
				continue;
			}

			foreach ( $general_validations->common_validations as $pattern => $message ) {
				$this->add_result( $pattern, $message, $template_unique_only, $file );
			}

			foreach ( $general_validations->template_validations as $pattern => $message ) {
				$this->add_result( $pattern, $message, $template_unique_only, $file );
			}

			foreach ( self::$validations as $validation ) {
				$validation->crawl( $template, $file );
			}
		}

		// Iterate includes.
		foreach ( $this->includes as $index => $functional ) {
			// Only unique theme stuff.
			$functional_unique_only = str_replace( $this->theme_path, '', $functional );

			if ( !file_exists( $functional ) ) {
				continue;
			}

			// Read the files, keep the file number as it matters, you know.
			$file = file( $functional, FILE_IGNORE_NEW_LINES );
			if ( false === $file ) {
				continue;
			}

			foreach ( $general_validations->common_validations as $pattern => $message ) {
				$this->add_result( $pattern, $message, $functional_unique_only, $file );
			}

			foreach ( $general_validations->include_validations as $pattern => $message ) {
				$this->add_result( $pattern, $message, $functional_unique_only, $file );
			}
		}

		// Display complex validations errors.
		foreach ( self::$validations as $validation ) {
			$validation->execute();
			$validation_description = $validation->get_description();

			if ( !empty( $validation_description ) ) {
				echo $validation_description;
			}
		}
	}
}
