<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Output;


use Akeeba\RemoteCLI\Input\Input;

interface OutputAdapterInterface
{
	/**
	 * Public constructor.
	 *
	 * @param   OutputOptions  $options  The configuration of the output object
	 */
	public function __construct(OutputOptions $options);

	/**
	 * Write a generic message
	 *
	 * @param   int     $type     One of the Output display types
	 * @param   string  $message  The message to display
	 * @param   bool    $force    Force the display of the message even if the quiet option is set
	 *
	 * @return  void
	 */
	public function writeln($type, $message, $force = false);
}
