<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\migrations;

class v_0_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['post_form_templates_version']);
	}

	public function update_schema()
	{
		return [
			'add_tables'  => [
				$this->table_prefix . 'pft_templates'        => [
					'COLUMNS'     => [
						'template_id'            => ['UINT', null, 'auto_increment'],
						'parent_id'              => ['UINT', 0],
						'left_id'                => ['UINT', 0],
						'right_id'               => ['UINT', 0],
						'template_parents'       => ['MTEXT', ''],
						'template_name'          => ['STEXT_UNI', ''],
						'template_desc'          => ['TEXT_UNI', ''],
						'template_desc_bitfield' => ['VCHAR:255', ''],
						'template_desc_options'  => ['UINT:11', 7],
						'template_desc_uid'      => ['VCHAR:8', ''],
						'template_images'        => ['MTEXT', ''],
						'template_image_date'    => ['VCHAR:10', ''],
						'template_image_type'    => ['TINT:4', 0],
						'template_type'          => ['TINT:4', 0],
						'template_status'        => ['TINT:4', 0],
					],
					'PRIMARY_KEY' => 'template_id',
					'KEYS'        => [
						'left_right_id' => ['INDEX', ['left_id', 'right_id']],
					],
				],
				$this->table_prefix . 'pft_template_forums'  => [
					'COLUMNS' => [
						'template_id' => ['UINT', null],
						'forum_id'    => ['UINT', null],
					],
					'KEYS'    => [
						'template_id' => ['INDEX', 'template_id'],
						'forum_id'    => ['INDEX', 'forum_id'],
					],
				],
				$this->table_prefix . 'pft_template_entries' => [
					'COLUMNS'     => [
						'entry_id'           => ['UINT', null, 'auto_increment'],
						'template_id'        => ['UINT', 0],
						'parent_id'          => ['UINT', 0],
						'left_id'            => ['UINT', 0],
						'right_id'           => ['UINT', 0],
						'entry_tag'          => ['MTEXT', ''],
						'entry_tag_bitfield' => ['VCHAR:255', ''],
						'entry_tag_uid'      => ['VCHAR:8', ''],
						'entry_match'        => ['TEXT_UNI', ''],
						'entry_helpline'     => ['VCHAR_UNI', ''],
						'entry_type_match'   => ['MTEXT', ''],
						'entry_type'         => ['TINT:4', 0],
						'entry_rows'         => ['TINT:4', 0],
						'display_on_posting' => ['BOOL', 0],
					],
					'PRIMARY_KEY' => 'entry_id',
					'KEYS'        => [
						'template_id'     => ['INDEX', 'template_id'],
						'parent_id'       => ['INDEX', 'parent_id'],
						'display_on_post' => ['INDEX', 'display_on_posting'],
						'left_right_id'   => ['INDEX', ['left_id', 'right_id']],
					],
				],
				$this->table_prefix . 'pft_template_images'  => [
					'COLUMNS'     => [
						'image_id'    => ['UINT', null, 'auto_increment'],
						'image_url'   => ['VCHAR:50', ''],
						'image_order' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'image_id',
				],
			],
			'add_columns' => [
				$this->table_prefix . 'acl_groups' => [
					'template_id' => ['UINT', 0],
				],
				$this->table_prefix . 'acl_users'  => [
					'template_id' => ['UINT', 0],
				],
				$this->table_prefix . 'log'        => [
					'template_id' => ['UINT', 0],
				],
			],
		];
	}

	public function update_data()
	{
		return [
			// Add configs
			['config.add', ['post_form_templates_version', '0.01']],
			['config.add', ['post_form_templates_add_post', '0']],
			['config.add', ['post_form_templates_hide_postfields', '0']],

			['permission.add', ['a_pftauth', true]],
			['permission.add', ['a_pft_template', true]],
			['permission.add', ['a_pft_templateadd', true]],
			['permission.add', ['a_pft_templatedel', true]],
			['permission.add', ['a_pft_images', true]],
			['permission.permission_set', ['ADMINISTRATORS', ['a_pftauth', 'a_pft_template', 'a_pft_templateadd', 'a_pft_templatedel', 'a_pft_images'], 'group']],
			['if', [
				['permission.role_exists', ['ROLE_ADMIN_FULL']],
				['permission.permission_set', ['ROLE_ADMIN_FULL', ['a_pftauth', 'a_pft_template', 'a_pft_templateadd', 'a_pft_templatedel', 'a_pft_images']]],
			]],
			['if', [
				['permission.role_exists', ['ROLE_ADMIN_STANDARD']],
				['permission.permission_set', ['ROLE_ADMIN_STANDARD', ['a_pftauth', 'a_pft_template', 'a_pft_templateadd', 'a_pft_templatedel', 'a_pft_images']]],
			]],
			['permission.add', ['pft_', false]],
			['permission.add', ['pft_view', false]],
			['permission.add', ['pft_use', false]],
			['permission.permission_set', ['ADMINISTRATORS', ['pft_view', 'pft_use'], 'group']],

			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_PFT_TITLE',
			]],

			['module.add', [
				'acp',
				'ACP_PFT_TITLE',
				[
					'module_basename' => '\toxyy\postformtemplates\acp\general_settings_module',
					'modes'           => ['general_settings'],
				],
			]],

			['module.add', [
				'acp',
				'ACP_PFT_TITLE',
				[
					'module_basename' => '\toxyy\postformtemplates\acp\manage_templates_module',
					'modes'           => ['manage_templates'],
				],
			]],

			['module.add', [
				'acp',
				'ACP_PFT_TITLE',
				[
					'module_basename' => '\toxyy\postformtemplates\acp\template_images_module',
					'modes'           => ['manage_images'],
				],
			]],

			['module.add', [
				'acp',
				'ACP_PFT_TITLE',
				[
					'module_basename' => '\toxyy\postformtemplates\acp\template_permissions_module',
					'modes'           => ['trace', 'setting_template_local', 'view_template_local'],
				],
			]],

			['custom', [
				[$this, 'initial_table_data'],
			]],
		];
	}

	public function initial_table_data()
	{
		$sql = 'INSERT INTO ' . $this->table_prefix . 'pft_templates' . " (template_name, template_desc, left_id, right_id, parent_id, template_type, template_images, template_desc_uid, template_parents) VALUES ('Your first main template category', '', 1, 6, 0, 0, '', '', '')";
		$this->db->sql_query($sql);
		$sql = 'INSERT INTO ' . $this->table_prefix . 'pft_templates' . " (template_name, template_desc, left_id, right_id, parent_id, template_type, template_images, template_desc_uid, template_parents) VALUES ('Your first template category', '', 2, 5, 1, 0, '', '', '')";
		$this->db->sql_query($sql);
		$sql = 'INSERT INTO ' . $this->table_prefix . 'pft_templates' . " (template_name, template_desc, left_id, right_id, parent_id, template_type, template_images, template_desc_uid, template_parents) VALUES ('Your first template', 'Your first post form template', 3, 4, 2, 1, '', '', '')";
		$this->db->sql_query($sql);
		$sql = 'INSERT INTO ' . $this->table_prefix . 'pft_template_entries' . " (entry_tag, entry_match, entry_type_match, left_id, right_id, template_id, parent_id, entry_rows, display_on_posting) VALUES ('<r><I><s>[i]</s>What is your favorite forum software?<e>[/i]</e></I></r>', '[b]{TEXT}[/b]', 'a:1:{i:0;s:0:\"\";}', 1, 2, 3, 0, 2, 1)";
		$this->db->sql_query($sql);
	}

	public function revert_schema()
	{
		return [
			'drop_tables'  => [
				$this->table_prefix . 'pft_templates',
				$this->table_prefix . 'pft_template_forums',
				$this->table_prefix . 'pft_template_entries',
				$this->table_prefix . 'pft_template_images',
			],
			'drop_columns' => [
				$this->table_prefix . 'acl_groups' => [
					'template_id',
				],
				$this->table_prefix . 'acl_users'  => [
					'template_id',
				],
			],
		];
	}
}
