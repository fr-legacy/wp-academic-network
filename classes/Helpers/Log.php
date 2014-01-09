<?php
namespace WPAN\Helpers;


class Log {
	const GENERAL = 1;
	const ACTION = 2;
	const USER = 4;
	const WARNING = 8;
	const ERROR = 16;
	const SECURITY = 32;

	/**
	 * Container for all issues raised in the current request.
	 *
	 * Could be obtained and written to disk in a single operation during shutdown.
	 *
	 * @var array
	 */
	protected static $request_log = array();


	/**
	 * Reports a general, non-specific issue.
	 *
	 * @param $issue
	 */
	public static function general( $issue ) {
		self::record( $issue, self::GENERAL );
	}

	/**
	 * Reports that an action took place.
	 *
	 * @param $issue
	 */
	public static function action( $issue ) {
		self::record( $issue, self::ACTION );
	}

	/**
	 * Reports a user-type issue.
	 *
	 * @param $issue
	 */
	public static function user( $issue ) {
		self::record( $issue, self::USER );
	}

	/**
	 * Reports a warning-level issue. Anything that may be useful for developers/sys admins
	 * in determining the flow of events while troubleshooting.
	 *
	 * @param $issue
	 */
	public static function warning( $issue ) {
		self::record( $issue, self::WARNING );
	}

	/**
	 * Reports an error-level issue. Anything that stopped a task from being performed or
	 * completed.
	 *
	 * @param $issue
	 */
	public static function error( $issue ) {
		self::record( $issue, self::ERROR );
	}

	/**
	 * Reports a security centric issue.
	 *
	 * @param $issue
	 */
	public static function security( $issue ) {
		self::record( $issue, self::SECURITY );
	}

	/**
	 * Records an issue. Assumes a general-level issue unless a specific type is provided.
	 *
	 * @param $issue
	 * @param int $type
	 */
	public static function record( $issue, $type = self::GENERAL ) {
		list( $source, $function ) = self::determine_source();
		$timestamp = date('Y-m-d H:i:s');
		self::$request_log[] = array( $timestamp, $type, $issue, $source, $function );
		do_action( 'wpan_log_entry', $issue, $type, $timestamp, $source, $function );
	}

	/**
	 * Tries to determine where the issue was created and returns it as an array consisting of
	 * the source ("file:line#") and function ("class::method()" where appropriate).
	 *
	 * @return array
	 */
	protected static function determine_source() {
		$trace_steps = debug_backtrace();
		$step = array();

		foreach ( $trace_steps as $step )
			if ( ! isset($step['class']) || __CLASS__ !== $step['class'] ) break;

		$class = isset( $step['class'] ) ? $step['class'] : '';
		$class = empty( $class ) ? '' : $class . '::';

		$function = isset( $step['function'] ) ? $step['function'] . '()' : 'Global scope';
		$function = $class . $function;

		$file = isset( $step['file'] ) ? $step['file'] . ':' : 'Unknown source file';
		$line = isset( $step['line'] ) ? $step['line'] : '';
		$line = $file . $line;

		return array( $line, $function );
	}

	/**
	 * Returns the log for all issues raised in the current request.
	 *
	 * @return array
	 */
	public static function get_request_log() {
		return self::$request_log;
	}

	/**
	 * Clears the request log of all issues.
	 */
	public static function clear_request_log() {
		self::$request_log = array();
	}
}