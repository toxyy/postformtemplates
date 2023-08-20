<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class manage_templates_info
{
	public function module()
	{
		return [
			'filename' => '\toxyy\postformtemplates\acp\manage_templates_module',
			'title'    => 'ACP_PFT_TITLE',
			'modes'    => [
				'manage_templates' => [
					'title' => 'ACP_PFT_MANAGE_TEMPLATES',
					'auth'  => 'ext_toxyy/postformtemplates && acl_a_pft_template',
					'cat'   => ['ACP_PFT_TITLE'],
				],
			],
		];
	}
}
