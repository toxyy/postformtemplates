<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class template_images_info
{
	public function module()
	{
		return [
			'filename' => '\toxyy\postformtemplates\acp\template_images_module',
			'title'    => 'ACP_PFT_TITLE',
			'modes'    => [
				'manage_images' => [
					'title' => 'ACP_PFT_MANAGE_IMAGES',
					'auth'  => 'ext_toxyy/postformtemplates && acl_a_pft_images',
					'cat'   => ['ACP_PFT_TITLE'],
				],
			],
		];
	}
}
