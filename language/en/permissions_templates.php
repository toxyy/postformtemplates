<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, [
	// Adding the permissions
	'ACL_CAT_PFT'           => 'Post Form Templates',
	'ACL_CAT_PFT_ACTIONS'   => 'Actions',
	'ACL_A_PFTAUTH'         => 'Can alter post form templates permission class',
	'ACL_A_PFT_TEMPLATE'    => 'Can manage post form templates',
	'ACL_A_PFT_TEMPLATEADD' => 'Can add new post form templates',
	'ACL_A_PFT_TEMPLATEDEL' => 'Can delete post form templates',
	'ACL_A_PFT_IMAGES'      => 'Can manage post form template images',
	'ACL_PFT_TVIEW'         => 'Can view template',
	'ACL_PFT_TUSE'          => 'Can use template',
]);
