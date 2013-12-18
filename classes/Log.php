<?php
namespace WPAN;


class Log {
	const GENERAL = 1;
	const ACTION = 2;
	const USER = 4;
	const WARNING = 8;
	const ERROR = 16;
	const SECURITY = 32;

	protected static $session_log = array();


	public static function general( $issue ) {
		self::record( $issue, self::GENERAL );
	}

	public static function action( $issue ) {
		self::record( $issue, self::ACTION );
	}

	public static function user( $issue ) {
		self::record( $issue, self::USER );
	}

	public static function warning( $issue ) {
		self::record( $issue, self::WARNING );
	}

	public static function error( $issue ) {
		self::record( $issue, self::ERROR );
	}

	public static function security( $issue ) {
		self::record( $issue, self::SECURITY );
	}

	public static function record( $issue, $type = self::GENERAL ) {
		$timestamp = date('Y-m-d H:i:s');
		self::$session_log[] = array( $timestamp, $type, $issue );
		do_action( 'wpan_log_entry', $issue, $type, $timestamp );
	}

	public static function get_session_log() {
		return self::$session_log;
	}

	public static function clear_session_log() {
		self::$session_log = array();
	}
}