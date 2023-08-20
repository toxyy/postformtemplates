<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates;

use phpbb\extension\base;

class ext extends base
{
	/**
	 * phpBB 3.3.x and PHP 7+
	 */
	public function is_enableable()
	{
		$ext_manager = $this->container->get('ext.manager');
		$config = $this->container->get('config');

		// don't need this anymore, but i want to keep the code
		$is_enableable = true;

		/*$ext_manager->is_enabled('marttiphpbb/grouptempvars');
		// if not enableable, add our custom install error language keys
		if (!$is_enableable)
		{
			$lang = $this->container->get('language');
			$lang->add_lang('anp_install', 'toxyy/postformtemplates');
		}*/

		// check phpbb and phpbb versions
		$is_enableable = ($is_enableable && (phpbb_version_compare($config['version'], '3.3', '>=') && version_compare(PHP_VERSION, '7', '>=')));

		return $is_enableable;
	}
}
