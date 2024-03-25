<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\controller;

class template_permissions_helper
{
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\user $user */
	protected $user;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\log\log $phpbb_log */
	protected $phpbb_log;
	/** @var \phpbb\group\helper $group_helper */
	protected $group_helper;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpEx;
	/** @var string */
	protected $pft_templates_table;
	/** @var string */
	protected $template_form;
	/** @var string */
	protected $template_cat;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\user                       $user
	 * @param \phpbb\language\language          $language
	 * @param \phpbb\log\log                    $phpbb_log
	 * @param \phpbb\group\helper               $group_helper
	 * @param string                            $phpbb_root_path     phpBB root path
	 * @param string                            $phpEx               PHP file extension
	 * @param string \toxyy\postformtemplates\  $pft_templates_table
	 * @param string \toxyy\postformtemplates\  $template_form
	 * @param string \toxyy\postformtemplates\  $template_cat
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\language\language $language,
		\phpbb\log\log $phpbb_log,
		\phpbb\group\helper $group_helper,
		$phpbb_root_path,
		$phpEx,
		$pft_templates_table,
		$template_form,
		$template_cat
	)
	{
		$this->db = $db;
		$this->user = $user;
		$this->language = $language;
		$this->phpbb_log = $phpbb_log;
		$this->group_helper = $group_helper;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->pft_templates_table = $pft_templates_table;
		$this->template_form = $template_form;
		$this->template_cat = $template_cat;

		if (!defined('PFT_TEMPLATES_TABLE'))
		{
			define('PFT_TEMPLATES_TABLE', $this->pft_templates_table);
		}
		if (!defined('TEMPLATE_FORM'))
		{
			define('TEMPLATE_FORM', $this->template_form);
		}
		if (!defined('TEMPLATE_CAT'))
		{
			define('TEMPLATE_CAT', $this->template_cat);
		}
	}

	/**
	 * Build +subtemplate options
	 */
	function build_subtemplate_options($template_list)
	{
		$s_options = '';

		$template_list = array_merge($template_list);

		foreach ($template_list as $key => $row)
		{
			if ($row['disabled'] || $row['template_type'] == TEMPLATE_FORM)
			{
				continue;
			}

			$s_options .= '<option value="' . $row['template_id'] . '"' . (
				($row['selected'])
				? ' selected="selected"'
				: ''
			) . '>' . $row['padding'] . $row['template_name'];

			// We check if a branch is there...
			$branch_there = false;

			foreach (array_slice($template_list, $key + 1) as $temp_row)
			{
				if ($temp_row['left_id'] > $row['left_id'] && $temp_row['left_id'] < $row['right_id'])
				{
					$branch_there = true;
					break;
				}
				continue;
			}

			if ($branch_there)
			{
				$s_options .= ' [' . $this->language->lang('ACP_PFT_PLUS_TEMPLATES') . ']';
			}

			$s_options .= '</option>';
		}

		return $s_options;
	}

	/**
	 * Check if selected items exist. Remove not found ids and if empty return error.
	 */
	function check_existence($mode, &$ids, $u_action)
	{
		switch ($mode)
		{
			case 'user':
				$table = USERS_TABLE;
				$sql_id = 'user_id';
			break;

			case 'group':
				$table = GROUPS_TABLE;
				$sql_id = 'group_id';
			break;

			case 'template':
				$table = PFT_TEMPLATES_TABLE;
				$sql_id = 'template_id';
			break;
		}

		if (count($ids))
		{
			$sql = "SELECT $sql_id
				FROM $table
				WHERE " . $this->db->sql_in_set($sql_id, $ids);
			$result = $this->db->sql_query($sql);

			$ids = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$ids[] = (int) $row[$sql_id];
			}
			$this->db->sql_freeresult($result);
		}

		if (!count($ids))
		{
			trigger_error($this->language->lang('SELECTED_' . strtoupper($mode) . '_NOT_EXIST') . adm_back_link($u_action), E_USER_WARNING);
		}
	}

	/**
	 * Get already assigned users/groups
	 */
	function retrieve_defined_user_groups($permission_scope, $template_id, $permission_type)
	{
		$sql_template_id = (($permission_scope == 'global')
			? 'AND a.template_id = 0'
			: ((count($template_id))
				? 'AND ' . $this->db->sql_in_set('a.template_id', $template_id)
				: 'AND a.template_id <> 0'
			)
		);

		// Permission options are only able to be a permission set... therefore we will pre-fetch the possible options and also the possible roles
		$option_ids = $role_ids = [];

		$sql = 'SELECT auth_option_id
			FROM ' . ACL_OPTIONS_TABLE . '
			WHERE auth_option ' . $this->db->sql_like_expression($permission_type . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$option_ids[] = (int) $row['auth_option_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($option_ids))
		{
			$sql = 'SELECT DISTINCT role_id
				FROM ' . ACL_ROLES_DATA_TABLE . '
				WHERE ' . $this->db->sql_in_set('auth_option_id', $option_ids);
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$role_ids[] = (int) $row['role_id'];
			}
			$this->db->sql_freeresult($result);
		}

		if (count($option_ids) && count($role_ids))
		{
			$sql_where = 'AND (' . $this->db->sql_in_set('a.auth_option_id', $option_ids) . ' OR ' . $this->db->sql_in_set('a.auth_role_id', $role_ids) . ')';
		}
		else if (count($role_ids))
		{
			$sql_where = 'AND ' . $this->db->sql_in_set('a.auth_role_id', $role_ids);
		}
		else if (count($option_ids))
		{
			$sql_where = 'AND ' . $this->db->sql_in_set('a.auth_option_id', $option_ids);
		}

		// Not ideal, due to the filesort, non-use of indexes, etc.
		$sql = 'SELECT DISTINCT u.user_id, u.username, u.username_clean, u.user_regdate
			FROM ' . USERS_TABLE . ' u, ' . ACL_USERS_TABLE . " a
			WHERE u.user_id = a.user_id
				$sql_template_id
				$sql_where
			ORDER BY u.username_clean, u.user_regdate ASC";
		$result = $this->db->sql_query($sql);

		$s_defined_user_options = '';
		$defined_user_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$s_defined_user_options .= '<option value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			$defined_user_ids[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT DISTINCT g.group_type, g.group_name, g.group_id
			FROM ' . GROUPS_TABLE . ' g, ' . ACL_GROUPS_TABLE . " a
			WHERE g.group_id = a.group_id
				$sql_template_id
				$sql_where
			ORDER BY g.group_type DESC, g.group_name ASC";
		$result = $this->db->sql_query($sql);

		$s_defined_group_options = '';
		$defined_group_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$s_defined_group_options .= '<option' .
				(($row['group_type'] == GROUP_SPECIAL)
					? ' class="sep"'
					: ''
				) . ' value="' . $row['group_id'] . '">' . $this->group_helper->get_name($row['group_name']) . '</option>';
			$defined_group_ids[] = $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		return [
			'group_ids'         => $defined_group_ids,
			'group_ids_options' => $s_defined_group_options,
			'user_ids'          => $defined_user_ids,
			'user_ids_options'  => $s_defined_user_options,
		];
	}

	/**
	 * Copies permissions from one template to others
	 *
	 * @param int   $src_template_id   The source template we want to copy permissions from
	 * @param array $dest_template_ids The destination template(s) we want to copy to
	 * @param bool  $clear_dest_perms  True if destination permissions should be deleted
	 * @param bool  $add_log           True if log entry should be added
	 *
	 * @return bool                    False on error
	 */
	function copy_template_permissions($src_template_id, $dest_template_ids, $clear_dest_perms = true, $add_log = true)
	{
		// Only one template id specified
		if (!is_array($dest_template_ids))
		{
			$dest_template_ids = [$dest_template_ids];
		}

		// Make sure template ids are integers
		$src_template_id = (int) $src_template_id;
		$dest_template_ids = array_map('intval', $dest_template_ids);

		// No source template or no destination templates specified
		if (empty($src_template_id) || empty($dest_template_ids))
		{
			return false;
		}

		// Check if source template exists
		$sql = 'SELECT template_name
			FROM ' . PFT_TEMPLATES_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);
		$src_template_name = $this->db->sql_fetchfield('template_name');
		$this->db->sql_freeresult($result);

		// Source template doesn't exist
		if (empty($src_template_name))
		{
			return false;
		}

		// Check if destination templates exists
		$sql = 'SELECT template_id, template_name
			FROM ' . PFT_TEMPLATES_TABLE . '
			WHERE ' . $this->db->sql_in_set('template_id', $dest_template_ids);
		$result = $this->db->sql_query($sql);

		$dest_template_ids = $dest_template_names = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$dest_template_ids[] = (int) $row['template_id'];
			$dest_template_names[] = $row['template_name'];
		}
		$this->db->sql_freeresult($result);

		// No destination template exists
		if (empty($dest_template_ids))
		{
			return false;
		}

		// From the mysql documentation:
		// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear
		// in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
		// Due to this we stay on the safe side if we do the insertion "the manual way"

		// Rowsets we're going to insert
		$users_sql_ary = $groups_sql_ary = [];

		// Query acl users table for source template data
		$sql = 'SELECT user_id, auth_option_id, auth_role_id, auth_setting
			FROM ' . ACL_USERS_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'user_id'        => (int) $row['user_id'],
				'auth_option_id' => (int) $row['auth_option_id'],
				'auth_role_id'   => (int) $row['auth_role_id'],
				'auth_setting'   => (int) $row['auth_setting'],
			];

			foreach ($dest_template_ids as $dest_template_id)
			{
				$users_sql_ary[] = $row + ['template_id' => $dest_template_id];
			}
		}
		$this->db->sql_freeresult($result);

		// Query acl groups table for source template data
		$sql = 'SELECT group_id, auth_option_id, auth_role_id, auth_setting
			FROM ' . ACL_GROUPS_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'group_id'       => (int) $row['group_id'],
				'auth_option_id' => (int) $row['auth_option_id'],
				'auth_role_id'   => (int) $row['auth_role_id'],
				'auth_setting'   => (int) $row['auth_setting'],
			];

			foreach ($dest_template_ids as $dest_template_id)
			{
				$groups_sql_ary[] = $row + ['template_id' => $dest_template_id];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		// Clear current permissions of destination templates
		if ($clear_dest_perms)
		{
			$sql = 'DELETE FROM ' . ACL_USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $dest_template_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $dest_template_ids);
			$this->db->sql_query($sql);
		}

		$this->db->sql_multi_insert(ACL_USERS_TABLE, $users_sql_ary);
		$this->db->sql_multi_insert(ACL_GROUPS_TABLE, $groups_sql_ary);

		if ($add_log)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_COPIED_PERMISSIONS', false, [$src_template_name, implode(', ', $dest_template_names)]);
		}

		$this->db->sql_transaction('commit');

		return true;
	}
}
