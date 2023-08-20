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
	'ACL_TYPE_LOCAL_PFT_'                         => 'Template permissions',
	'ACL_TYPE_PFT_'                               => 'Template permissions',
	'ACP_PFT_PERMISSION_TRACE'                    => 'Trace permissions',
	'ACP_PFT_VIEW_PERMISSIONS'                    => 'View template permissions',
	'ACP_PFT_VIEW_TEMPLATE_PERMISSIONS_EXPLAIN'   => 'Here you can view the template permissions assigned to the selected users/groups and templates.',
	'ACP_PFT_TEMPLATE_PERMISSIONS'                => 'Template permissions',
	'ACP_PFT_TEMPLATE_PERMISSIONS_EXPLAIN'        => 'Here you can alter which users and groups can access which templates.',
	'ACP_PFT_VIEW_TEMPLATE_PERMISSIONS'           => 'View post form template permissions',
	'ACP_PFT_LOOK_UP_TEMPLATE'                    => 'Select a template',
	'ACP_PFT_LOOK_UP_TEMPLATES_EXPLAIN'           => 'You are able to select more than one template.',
	'ACP_PFT_LOOK_UP_CATEGORY'                    => 'Select a template category',
	'ACP_PFT_SELECT_TEMPLATE_SUBTEMPLATE_EXPLAIN' => 'The category you select here will include all templates into the selection.',
	'ACP_PFT_PLUS_TEMPLATES'                      => '+Templates',
	'ACP_PFT_TEMPLATES'                           => 'Templates',
	'ACP_PFT_ALL_TEMPLATES'                       => 'All templates',
]);
