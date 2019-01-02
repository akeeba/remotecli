<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Output;

/**
 * Class for console output
 */
class Console implements OutputAdapterInterface
{
	// Console escape
	const ESC = "\033[";

	// Console color escape sequence
	const ESC_SEQ_PATTERN = "\033[%sm";

	// Special color patterns
	const COLOR_RESET = 0;
	const COLOR_BOLD = 1;
	const COLOR_DARK = 2;
	const COLOR_ITALIC = 3;
	const COLOR_UNDERLINE = 4;
	const COLOR_BLINK = 5;
	const COLOR_REVERSE = 7;
	const COLOR_CONCEALED = 8;

	// Foreground color
	const COLOR_FG_DEFAULT = 39;
	const COLOR_FG_BLACK = 30;
	const COLOR_FG_RED = 31;
	const COLOR_FG_GREEN = 32;
	const COLOR_FG_YELLOW = 33;
	const COLOR_FG_BLUE = 34;
	const COLOR_FG_MAGENTA = 35;
	const COLOR_FG_CYAN = 36;
	const COLOR_FG_LIGHT_GRAY = 37;
	const COLOR_FG_DARK_GRAY = 90;
	const COLOR_FG_LIGHT_RED = 91;
	const COLOR_FG_LIGHT_GREEN = 92;
	const COLOR_FG_LIGHT_YELLOW = 93;
	const COLOR_FG_LIGHT_BLUE = 94;
	const COLOR_FG_LIGHT_MAGENTA = 95;
	const COLOR_FG_LIGHT_CYAN = 96;
	const COLOR_FG_WHITE = 97;

	// Background color
	const COLOR_BG_DEFAULT = 49;
	const COLOR_BG_BLACK = 40;
	const COLOR_BG_RED = 41;
	const COLOR_BG_GREEN = 42;
	const COLOR_BG_YELLOW = 43;
	const COLOR_BG_BLUE = 44;
	const COLOR_BG_MAGENTA = 45;
	const COLOR_BG_CYAN = 46;
	const COLOR_BG_LIGHT_GRAY = 47;
	const COLOR_BG_DARK_GRAY = 100;
	const COLOR_BG_LIGHT_RED = 101;
	const COLOR_BG_LIGHT_GREEN = 102;
	const COLOR_BG_LIGHT_YELLOW = 103;
	const COLOR_BG_LIGHT_BLUE = 104;
	const COLOR_BG_LIGHT_MAGENTA = 105;
	const COLOR_BG_LIGHT_CYAN = 106;
	const COLOR_BG_WHITE = 107;

	/**
	 * Does the console have color support?
	 *
	 * @var   bool
	 */
	private $hasColorSupport = false;

	/**
	 * Output options
	 *
	 * @var   OutputOptions
	 */
	private $options;

	/**
	 * Console constructor.
	 *
	 * @param   OutputOptions  $options  The output configuration options
	 */
	public function __construct(OutputOptions $options)
	{
		$this->options         = $options;
		$this->hasColorSupport = $this->hasColorSupport();

		if ($options->noColor)
		{
			$this->hasColorSupport = false;
		}
	}

	public function writeln($type, $message, $force = false)
	{
		$quiet = $this->options->quiet && !$force;

		if ($quiet)
		{
			return;
		}

		$output = STDOUT;
		$header = '';

		switch ($type)
		{
			case Output::HEADER:
				$message = $this->apply(self::COLOR_BOLD, self::COLOR_UNDERLINE, self::COLOR_FG_GREEN) .
					$message . $this->apply(self::COLOR_RESET);
				break;

			case Output::DEBUG:
				if (!$this->options->debug)
				{
					return;
				}

				$message = $this->apply(self::COLOR_FG_LIGHT_GRAY) .
					$message . $this->apply(self::COLOR_RESET);
				break;

			case Output::INFO:
				$message = $this->apply(self::COLOR_RESET) . $message;
				break;

			case Output::WARNING:
				$message = $this->apply(self::COLOR_ITALIC, self::COLOR_FG_LIGHT_YELLOW) .
					$message . $this->apply(self::COLOR_RESET);

				$output = $this->options->mergeErrorOutput ? STDOUT : STDERR;
				break;

			case Output::ERROR:
				$message = $this->apply(self::COLOR_FG_LIGHT_RED) .
					$message . $this->apply(self::COLOR_RESET);

				$output = $this->options->mergeErrorOutput ? STDOUT : STDERR;

				if (!$this->options->quiet)
				{
					$width  = $this->getConsoleWidth();
					$header = $this->apply(self::COLOR_BOLD, self::COLOR_FG_MAGENTA) .
						str_repeat('=', $width) . PHP_EOL .
						$this->center('E R R O R', $width) . PHP_EOL .
						str_repeat('=', $width) .
						$this->apply(self::COLOR_RESET) .
						PHP_EOL;
				}
				break;
		}

		// Remove colors if we are asked to provide non-color output or if colors are not available on the console.
		if ($this->options->noColor || !$this->hasColorSupport)
		{
			$header  = $this->stripColors($header);
			$message = $this->stripColors($message);
		}

		// If we have an error header and we are asked to output errors in standard output let's display the header.
		if ($header && $this->options->mergeErrorOutput)
		{
			fputs($output, $header);
		}

		// Finally, print out the message itself.
		fputs($output, $message . PHP_EOL);
	}


	/**
	 * Write some text, without a newline
	 *
	 * @param   string  $text  The text to write
	 *
	 * @return  void
	 */
	protected function write($text)
	{
		if (!$this->hasColorSupport)
		{
			$text = $this->stripColors($text);
		}

		echo $text;
	}

	/**
	 * Center a line to a specified width.
	 *
	 * @param   string  $line   The line of text to center
	 * @param   int     $width  The width to center to, leave null to auto-determine.
	 *
	 * @return  string
	 */
	protected function center($line, $width = null)
	{
		$width = empty($width) ? $this->getConsoleWidth() : $width;

		/**
		 * When operating on string length I need to take into account that escape sequences are not visible
		 * characters, therefore they should not participate in the character count for padding.
		 */
		$strippedLine     = $this->stripColors($line);
		$colorExcessWidth = strlen($line) - strlen($strippedLine);

		/**
		 * str_pad is not Unicode aware, therefore I have to take into account the Unicode string length difference.
		 * For example "One δύο" is 13 bytes since the Greek characters occupy 3 bytes each, but only 7 characters
		 * wide. If I tell str_pad to pad to a width of 80 characters it will add 33 spaces to the left and 34 to
		 * the right for a total of 74 characters width. Not quite right. We need to tell it to pad to 80 + (13 - 7)
		 * characters for it to end up doing padding with the correct number of spaces.
		 */
		$utfExcessWidth = 0;

		if (function_exists('mb_strlen'))
		{
			$utfExcessWidth = strlen($strippedLine) - mb_strlen($strippedLine, 'UTF-8');
		}

		// Adjust the width due to color and Unicode size differences
		$lineWidth = $width + $utfExcessWidth + $colorExcessWidth;

		return str_pad($line, $lineWidth, ' ', STR_PAD_BOTH);
	}

	/**
	 * Strip color codes from a string
	 *
	 * @param   string  $text
	 *
	 * @return  string
	 */
	protected function stripColors($text)
	{
		return preg_replace('/' . preg_quote(self::ESC) . '(\d+;?)+m/', '', $text);
	}

	/**
	 * Apply a color code, returning its escape sequence. You can pass several of the Console::COLOR_* constants to
	 * combine them into a style.
	 *
	 * @return  string
	 */
	protected function apply()
	{
		$arguments = func_get_args();
		$colorCode = implode(';', $arguments);

		return self::ESC . $colorCode . 'm';
	}

	/**
	 * Does our Terminal have color support?
	 *
	 * Colors are disabled under Windows unless you are using one of ANSIcon, ConEmu, something with xterm emulation,
	 * or if you are using the (old and obsolete) Windows 10 build 10586 which had *accidentally* enabled color support
	 * in the console for one glorious, brief moment.
	 *
	 * @return  boolean
	 *
	 * @see https://github.com/symfony/Console/blob/master/Output/StreamOutput.php
	 */
	protected function hasColorSupport()
	{
		if (DIRECTORY_SEPARATOR === '\\')
		{
			return
				PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD === '10.0.10586'
				|| getenv('ANSICON') !== false
				|| getenv('ConEmuANSI') === 'ON'
				|| getenv('TERM') === 'xterm';
		}

		return function_exists('posix_isatty') && @posix_isatty(STDOUT);
	}

	/**
	 * Gets the width of the console in characters
	 *
	 * @return  int
	 */
	protected function getConsoleWidth()
	{
		// First try the COLUMNS environment variable (not set by Terminal.app on macOS, though)
		$cols = getenv('COLUMNS');

		if (!empty($cols))
		{
			return (int) $cols;
		}

		// Better yet, try to use the ncurses extension
		if (function_exists('ncurses_getmaxyx'))
		{
			ncurses_getmaxyx(STDSCR, $rows, $cols);
		}

		if (!empty($cols))
		{
			return (int) $cols;
		}

		// Maybe I can shell out and run tput on *NIX...?
		if (function_exists('exec') && (DIRECTORY_SEPARATOR != '\\'))
		{
			$cols = exec('tput cols 2>/dev/null');
		}

		if (!empty($cols))
		{
			return (int) $cols;
		}

		// I give up! I have no idea. I will just return 80 and be done with it.
		return 80;
	}
}
