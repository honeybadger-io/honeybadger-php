<?php

namespace Honeybadger;

/**
 * Based on [Kohana's exception handler](https://github.com/kohana/core/blob/3.4/develop/classes/Kohana/Kohana/Exception.php#L102:L130).
 */
class Exception {

	private static $previous_handler;

	public static function register_handler()
	{
		self::$previous_handler = set_exception_handler(array(
			__CLASS__, 'handle',
		));
	}

	public static function handle(\Exception $e)
	{
		header('Content-Type: text/plain; charset=utf-8', TRUE, 500);

		try
		{
			// $notice = Notice::factory(array(
			// 	'exception' => $e,
			// ));
			// echo json_encode($notice->as_array(), JSON_PRETTY_PRINT);

			// Attempt to send this exception to Honeybadger.
			echo Honeybadger::notify_or_ignore($e);
			exit;
		}
		catch (Exception $e)
		{
			if (is_callable(self::$previous_handler))
			{
				return call_user_func(self::$previous_handler, $e);
			}
			else
			{
				// Clean the output buffer if one exists.
				ob_get_level() AND ob_clean();

				// Set the Status code to 500, and Content-Type to text/plain.
				header('Content-Type: text/plain; charset=utf-8', TRUE, 500);

				echo 'Someting went terribly wrong.';

				// Exit with a non-zero status.
				exit(1);
			}
		}

		if (is_callable(self::$previous_handler))
		{
			return call_user_func(self::$previous_handler, $e);
		}
	}

} // End Exception