<?php
#!/usr/bin/env node

/**
 * Theme checker for child_process
 *
 * @package theme-check
 * @since 1.0.0
 */

define( 'VALIDATOR_THEME_MENTOR', 'theme-mentor' );
define( 'VALIDATOR_THEME_CHECK', 'theme-check' );
define( 'PHP_SCRIPT_BASEDIR', dirname( __DIR__ ) );

require 'wp-functions.php';
require 'class-theme-mentor-validator.php';
require 'class-theme-check-validator.php';

if ( ! isset( $argv[1] )) {
	print 'Invalid JSON';
	exit(1);
}

$json = json_decode( $argv[1], true );
$path = $json['path'];
$validator = $json['validator'];
$excludes = array_merge( array(
	'node_modules',
	'bower_components',
	'.git',
), $json['excludes'] );
$excludes = array_unique( $excludes );

$error_logs = array();
$themechecks = array();
$style = file_get_contents( $path . '/style.css' );
$data = get_theme_data_from_contents( $style );
$themename = $data['Name'];

if ( in_array( VALIDATOR_THEME_CHECK, $validator, true ) ) {
	$theme_check_validator = new Theme_Check_Validator( $path, $excludes );
	$error_logs[] = array(
		'id' => 'THEME_CHECK',
		'type' => VALIDATOR_THEME_CHECK,
		'result' => $theme_check_validator->result(),
	);
}

if ( in_array( VALIDATOR_THEME_MENTOR, $validator, true ) ) {
	$theme_mentor_validator = new Theme_Mentor_Validator( $path, $excludes );
	$error_logs[] = array(
		'id' => 'THEME_MENTOR',
		'type' => VALIDATOR_THEME_MENTOR,
		'result' => $theme_mentor_validator->result(),
	);
}

echo json_encode( $error_logs );
