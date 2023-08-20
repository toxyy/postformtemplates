<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class template_permissions_info
{
	public function module()
	{
		return [
			'filename' => '\toxyy\postformtemplates\acp\template_permissions_module',
			'title'    => 'ACP_PFT_TITLE',
			'modes'    => [
				'trace'                  => [
					'title'   => 'ACP_PFT_PERMISSION_TRACE',
					'auth'    => 'ext_toxyy/postformtemplates && acl_a_viewauth',
					'display' => false,
					'cat'     => ['ACP_PFT_TITLE'],
				],
				'setting_template_local' => [
					'title' => 'ACP_PFT_TEMPLATE_PERMISSIONS',
					'auth'  => 'ext_toxyy/postformtemplates && acl_a_pftauth && (acl_a_authusers || acl_a_authgroups)',
					'cat'   => ['ACP_PFT_TITLE'],
				],
				'view_template_local'    => [
					'title' => 'ACP_PFT_VIEW_PERMISSIONS',
					'auth'  => 'ext_toxyy/postformtemplates && acl_a_viewauth',
					'cat'   => ['ACP_PFT_TITLE'],
				],
			],
		];
	}
}
