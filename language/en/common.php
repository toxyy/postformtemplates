<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'LOG_ACP_PFT_TEMPLATE_ADD'                             => '<strong>Created new Post Form Template</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_EDIT'                            => '<strong>Edited Post Form Template details</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_MOVE_UP'                         => '<strong>Moved Post Form Template</strong> %1$s <strong>above</strong>» %2$s',
	'LOG_ACP_PFT_TEMPLATE_MOVE_DOWN'                       => '<strong>Moved Post Form Template</strong> %1$s <strong>below</strong>» %2$s',
	'LOG_ACP_PFT_COPIED_TEMPLATE'                          => '<strong>Copied Post Form Template template settings from</strong> %1$s <strong>To</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_COPIED_PERMISSIONS'              => '<strong>Copied Post Form Template permissions from</strong> %1$s <strong>to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_COPIED_CONTENTS'                 => '<strong>Copied Post Form Template contents from</strong> %1$s <strong>to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_COPIED_ENTRIES'                  => '<strong>Copied Post Form Template entries from</strong> %1$s <strong>to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_COPIED_ENTRIES'                  => '<strong>Copied Post Form Template display forums from</strong> %1$s <strong>to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_COPIED_IMAGES'                   => '<strong>Copied Post Form Template images from</strong> %1$s <strong>to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES_MOVE_TEMPLATES' => '<strong>Deleted Post Form Template</strong> %3$s <strong>and moved entries to</strong><br />» %1$s<br /><strong>And moved subtemplates to</strong><br />» %2$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES_TEMPLATES'      => '<strong>Deleted subtemplates and Post Form Template</strong> %2$s <strong>and moved entries to</strong><br />» %1$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES_MOVE_TEMPLATES'      => '<strong>Deleted entries and Post Form Template</strong> %2$s <strong>and moved subtemplates to</strong><br />» %1$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_TEMPLATES'              => '<strong>Deleted Post Form Template</strong> %2$s <strong>and moved subtemplates to</strong><br />» %1$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES'                => '<strong>Deleted Post Form Template</strong> %2$s <strong>and moved entries to</strong><br />» %1$s',
	'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES_TEMPLATES'           => '<strong>Deleted entries and templates from Post Form Template</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_DEL_TEMPLATES'                   => '<strong>Deleted Post Form Template and subtemplates from</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES'                     => '<strong>Deleted Post Form Template and entries from</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_DEL_TEMPLATE'                    => '<strong>Deleted Post Form Template</strong><br />» %s',

	'LOG_ACL_ADD_TEMPLATE_LOCAL_PFT_'          => '<strong>Edited permissions for Post Form Templates</strong> %1$s<br /><strong>For usergroups</strong><br />» %2$s',
	'LOG_ACL_DEL_TEMPLATE_LOCAL_PFT_'          => '<strong>Deleted permissions for Post Form Templates</strong> %1$s<br /><strong>For usergroups</strong><br />» %2$s',

	'LOG_ACP_PFT_TEMPLATE_MOVE_UP_ENTRY'   => '<strong>Moved Post Form Template Entry</strong> %1$s <strong>above</strong>» %2$s',
	'LOG_ACP_PFT_TEMPLATE_MOVE_DOWN_ENTRY' => '<strong>Moved Post Form Template Entry</strong> %1$s <strong>below</strong>» %2$s',
	'LOG_ACP_PFT_TEMPLATE_ENTRY_ADD'       => '<strong>Added new Post Form Template Entry</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_ENTRY_EDIT'      => '<strong>Edited Post Form Template Entry</strong><br />» %s',
	'LOG_ACP_PFT_TEMPLATE_ENTRY_DELETE'    => '<strong>Deleted Post Form Template Entry</strong><br />» %s',
	'PFT_SELECT_TEMPLATE'                  => 'Select a Post Template:',
	'PFT_NO_TEMPLATE'                      => 'Select a template',
	'PFT_NO_CATEGORY'                      => 'Select a category',
	'PFT_SEPARATOR'                        => ', ',
	'PFT_ADD_TO_POST'                      => 'Add to post',
	'PFT_POST_TEMPLATE'                    => 'Post template',
]);
