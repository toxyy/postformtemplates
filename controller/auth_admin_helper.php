<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\controller;

class auth_admin_helper
{
	/** @var \phpbb\cache\driver\driver_interface $cache */
	protected $cache;
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\user $user */
	protected $user;
	/** @var \phpbb\auth\auth $auth */
	protected $auth;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\template\template $template */
	protected $template;
	/** @var \phpbb\group\helper $group_helper */
	protected $group_helper;
	/** @var \phpbb\permissions $phpbb_permissions */
	protected $phpbb_permissions;
	/** @var \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper */
	protected $manage_templates_helper;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpbb_admin_path;
	/** @var string */
	protected $phpEx;
	/** @var string */
	protected $pft_templates_table;
	/** @var \includes\acp\auth\auth_admin $auth_admin */
	protected $auth_admin;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface                        $cache
	 * @param \phpbb\db\driver\driver_interface                           $db
	 * @param \phpbb\user                                                 $user
	 * @param \phpbb\auth\auth                                            $auth
	 * @param \phpbb\language\language                                    $language
	 * @param \phpbb\template\template                                    $template
	 * @param \phpbb\group\helper                                         $group_helper
	 * @param \phpbb\permissions                                          $phpbb_permissions
	 * @param \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper
	 * @param string                                                      $phpbb_root_path         phpBB root path
	 * @param string                                                      $phpbb_admin_path        phpBB admin path
	 * @param string                                                      $phpEx                   PHP file extension
	 * @param string \toxyy\postformtemplates\                            $pft_templates_table
	 */
	public function __construct(
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\group\helper $group_helper,
		\phpbb\permissions $phpbb_permissions,
		\toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper,
		$phpbb_root_path,
		$phpbb_admin_path,
		$phpEx,
		$pft_templates_table
	)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->user = $user;
		$this->auth = $auth;
		$this->language = $language;
		$this->template = $template;
		$this->group_helper = $group_helper;
		$this->phpbb_permissions = $phpbb_permissions;
		$this->manage_templates_helper = $manage_templates_helper;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpbb_admin_path = $this->phpbb_root_path . $phpbb_admin_path;
		$this->phpEx = $phpEx;
		$this->pft_templates_table = $pft_templates_table;

		if (!defined('PFT_TEMPLATES_TABLE'))
		{
			define('PFT_TEMPLATES_TABLE', $this->pft_templates_table);
		}

		if (!class_exists('auth_admin'))
		{
			include($this->phpbb_root_path . 'includes/acp/auth.' . $this->phpEx);
		}

		$this->auth_admin = new \auth_admin();
	}

	/**
	 * Init permissions
	 */
	function acl(&$userdata, &$auth)
	{
		$auth->acl = $auth->cache = $auth->acl_options = [];
		$auth->acl_forum_ids = false;

		if (($auth->acl_options = $this->cache->get('_acl_options')) === false)
		{
			$sql = 'SELECT auth_option_id, auth_option, is_global, is_local
				FROM ' . ACL_OPTIONS_TABLE . '
				ORDER BY auth_option_id';
			$result = $this->db->sql_query($sql);

			$global = $local = 0;
			$auth->acl_options = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($row['is_global'])
				{
					$auth->acl_options['global'][$row['auth_option']] = $global++;
				}

				if ($row['is_local'])
				{
					$auth->acl_options['local'][$row['auth_option']] = $local++;
				}

				$auth->acl_options['id'][$row['auth_option']] = (int) $row['auth_option_id'];
				$auth->acl_options['option'][(int) $row['auth_option_id']] = $row['auth_option'];
			}
			$this->db->sql_freeresult($result);

			$this->cache->put('_acl_options', $auth->acl_options);
		}

		if (!trim($userdata['user_permissions']))
		{
			$this->acl_cache($userdata, $auth);
		}

		// Fill ACL array
		$auth->_fill_acl($userdata['user_permissions']);

		// Verify bitstring length with options provided...
		$renew = false;
		$global_length = count($auth->acl_options['global']);
		$local_length = count($auth->acl_options['local']);

		// Specify comparing length (bitstring is padded to 31 bits)
		$global_length = ($global_length % 31) ? ($global_length - ($global_length % 31) + 31) : $global_length;
		$local_length = ($local_length % 31) ? ($local_length - ($local_length % 31) + 31) : $local_length;

		// You thought we are finished now? Noooo... now compare them.
		foreach ($auth->acl as $template_id => $bitstring)
		{
			if (($template_id && strlen($bitstring) != $local_length) || (!$template_id && strlen($bitstring) != $global_length))
			{
				$renew = true;
				break;
			}
		}

		// If a bitstring within the list does not match the options, we have a user with incorrect permissions set and need to renew them
		if ($renew || true)
		{
			$this->acl_cache($userdata, $auth);
			$auth->_fill_acl($userdata['user_permissions']);
		}
	}

	/**
	 * Cache data to user_permissions row
	 */
	function acl_cache(&$userdata, &$auth)
	{
		// Empty user_permissions
		$userdata['user_permissions'] = '';

		$hold_ary = $this->acl_raw_data_single_user($userdata['user_id'], $auth);

		// Key 0 in $hold_ary are global options, all others are forum_ids

		// If this user is founder we're going to force fill the admin options ...
		if ($userdata['user_type'] == USER_FOUNDER)
		{
			foreach ($auth->acl_options['global'] as $opt => $id)
			{
				if (strpos($opt, 'a_') === 0)
				{
					$hold_ary[0][$auth->acl_options['id'][$opt]] = ACL_YES;
				}
			}
		}

		$hold_str = $auth->build_bitstring($hold_ary);

		if ($hold_str)
		{
			$userdata['user_permissions'] = $hold_str;

			/*$sql = 'UPDATE ' . USERS_TABLE . "
				SET user_permissions = '" . $this->db->sql_escape($userdata['user_permissions']) . "',
					user_perm_from = 0
				WHERE user_id = " . $userdata['user_id'];
			$this->db->sql_query($sql);*/
		}

		return;
	}

	/**
	 * Get raw acl data based on user for caching user_permissions
	 * This function returns the same data as acl_raw_data(), but without the user id as the first key within the array.
	 */
	function acl_raw_data_single_user($user_id, $auth)
	{
		// Check if the role-cache is there
		if (($role_cache = $this->cache->get('_role_cache')) === false)
		{
			$role_cache = [];

			// We pre-fetch roles
			$sql = 'SELECT *
				FROM ' . ACL_ROLES_DATA_TABLE . '
				ORDER BY role_id ASC';
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$role_cache[$row['role_id']][$row['auth_option_id']] = (int) $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);

			foreach ($role_cache as $role_id => $role_options)
			{
				$role_cache[$role_id] = serialize($role_options);
			}

			$this->cache->put('_role_cache', $role_cache);
		}

		$hold_ary = [];

		// Grab user-specific permission settings
		$sql = 'SELECT template_id, auth_option_id, auth_role_id, auth_setting
			FROM ' . ACL_USERS_TABLE . '
			WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// If a role is assigned, assign all options included within this role. Else, only set this one option.
			if ($row['auth_role_id'])
			{
				$hold_ary[$row['template_id']] = (empty($hold_ary[$row['template_id']])) ? unserialize($role_cache[$row['auth_role_id']]) : $hold_ary[$row['template_id']] + unserialize($role_cache[$row['auth_role_id']]);
			}
			else
			{
				$hold_ary[$row['template_id']][$row['auth_option_id']] = $row['auth_setting'];
			}
		}
		$this->db->sql_freeresult($result);

		// Now grab group-specific permission settings
		$sql = 'SELECT a.template_id, a.auth_option_id, a.auth_role_id, a.auth_setting
			FROM ' . ACL_GROUPS_TABLE . ' a, ' . USER_GROUP_TABLE . ' ug, ' . GROUPS_TABLE . ' g
			WHERE a.group_id = ug.group_id
				AND g.group_id = ug.group_id
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
				AND ug.user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$row['auth_role_id'])
			{
				$auth->_set_group_hold_ary($hold_ary[$row['template_id']], $row['auth_option_id'], $row['auth_setting']);
			}
			else if (!empty($role_cache[$row['auth_role_id']]))
			{
				foreach (unserialize($role_cache[$row['auth_role_id']]) as $option_id => $setting)
				{
					$auth->_set_group_hold_ary($hold_ary[$row['template_id']], $option_id, $setting);
				}
			}
		}
		$this->db->sql_freeresult($result);

		return $hold_ary;
	}

	/**
	 * Get raw group based permission settings
	 */
	function acl_group_raw_data($group_id = false, $opts = false, $template_id = false)
	{
		$sql_group = ($group_id !== false) ? ((!is_array($group_id)) ? 'group_id = ' . (int) $group_id : $this->db->sql_in_set('group_id', array_map('intval', $group_id))) : '';
		$sql_template = ($template_id !== false) ? ((!is_array($template_id)) ? 'AND a.template_id = ' . (int) $template_id : 'AND ' . $this->db->sql_in_set('a.template_id', array_map('intval', $template_id))) : '';

		$sql_opts = '';
		$hold_ary = $sql_ary = [];

		if ($opts !== false)
		{
			$this->auth->build_auth_option_statement('ao.auth_option', $opts, $sql_opts);
		}

		// Grab group settings - non-role specific...
		$sql_ary[] = 'SELECT a.group_id, a.template_id, a.auth_setting, a.auth_option_id, ao.auth_option
			FROM ' . ACL_GROUPS_TABLE . ' a, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE a.auth_role_id = 0
				AND a.auth_option_id = ao.auth_option_id ' .
			(($sql_group) ? 'AND a.' . $sql_group : '') . "
				$sql_template
				$sql_opts
			ORDER BY a.template_id, ao.auth_option";

		// Now grab group settings - role specific...
		$sql_ary[] = 'SELECT a.group_id, a.template_id, r.auth_setting, r.auth_option_id, ao.auth_option
			FROM ' . ACL_GROUPS_TABLE . ' a, ' . ACL_ROLES_DATA_TABLE . ' r, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE a.auth_role_id = r.role_id
				AND r.auth_option_id = ao.auth_option_id ' .
			(($sql_group) ? 'AND a.' . $sql_group : '') . "
				$sql_template
				$sql_opts
			ORDER BY a.template_id, ao.auth_option";

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$hold_ary[$row['group_id']][$row['template_id']][$row['auth_option']] = $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);
		}

		return $hold_ary;
	}

	/**
	 * Get raw acl data based on user/option/template
	 */
	function acl_raw_data($user_id = false, $opts = false, $template_id = false)
	{
		$sql_user = ($user_id !== false) ? ((!is_array($user_id)) ? 'user_id = ' . (int) $user_id : $this->db->sql_in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_template = ($template_id !== false) ? ((!is_array($template_id)) ? 'AND a.template_id = ' . (int) $template_id : 'AND ' . $this->db->sql_in_set('a.template_id', array_map('intval', $template_id))) : '';

		$sql_opts = $sql_opts_select = $sql_opts_from = '';
		$hold_ary = [];

		if ($opts !== false)
		{
			$sql_opts_select = ', ao.auth_option';
			$sql_opts_from = ', ' . ACL_OPTIONS_TABLE . ' ao';
			$this->auth->build_auth_option_statement('ao.auth_option', $opts, $sql_opts);
		}

		$sql_ary = [];

		// Grab non-role settings - user-specific
		$sql_ary[] = 'SELECT a.user_id, a.template_id, a.auth_setting, a.auth_option_id' . $sql_opts_select . '
			FROM ' . ACL_USERS_TABLE . ' a' . $sql_opts_from . '
			WHERE a.auth_role_id = 0 ' .
			(($sql_opts_from) ? 'AND a.auth_option_id = ao.auth_option_id ' : '') .
			(($sql_user) ? 'AND a.' . $sql_user : '') . "
				$sql_template
				$sql_opts";

		// Now the role settings - user-specific
		$sql_ary[] = 'SELECT a.user_id, a.template_id, r.auth_option_id, r.auth_setting, r.auth_option_id' . $sql_opts_select . '
			FROM ' . ACL_USERS_TABLE . ' a, ' . ACL_ROLES_DATA_TABLE . ' r' . $sql_opts_from . '
			WHERE a.auth_role_id = r.role_id ' .
			(($sql_opts_from) ? 'AND r.auth_option_id = ao.auth_option_id ' : '') .
			(($sql_user) ? 'AND a.' . $sql_user : '') . "
				$sql_template
				$sql_opts";

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$option = ($sql_opts_select) ? $row['auth_option'] : $this->auth->acl_options['option'][$row['auth_option_id']];
				$hold_ary[$row['user_id']][$row['template_id']][$option] = $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);
		}

		$sql_ary = [];

		// Now grab group settings - non-role specific...
		$sql_ary[] = 'SELECT ug.user_id, a.template_id, a.auth_setting, a.auth_option_id' . $sql_opts_select . '
			FROM ' . ACL_GROUPS_TABLE . ' a, ' . USER_GROUP_TABLE . ' ug, ' . GROUPS_TABLE . ' g' . $sql_opts_from . '
			WHERE a.auth_role_id = 0 ' .
			(($sql_opts_from) ? 'AND a.auth_option_id = ao.auth_option_id ' : '') . '
				AND a.group_id = ug.group_id
				AND g.group_id = ug.group_id
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . (($sql_user) ? 'AND ug.' . $sql_user : '') . "
				$sql_template
				$sql_opts";

		// Now grab group settings - role specific...
		$sql_ary[] = 'SELECT ug.user_id, a.template_id, r.auth_setting, r.auth_option_id' . $sql_opts_select . '
			FROM ' . ACL_GROUPS_TABLE . ' a, ' . USER_GROUP_TABLE . ' ug, ' . GROUPS_TABLE . ' g, ' . ACL_ROLES_DATA_TABLE . ' r' . $sql_opts_from . '
			WHERE a.auth_role_id = r.role_id ' .
			(($sql_opts_from) ? 'AND r.auth_option_id = ao.auth_option_id ' : '') . '
				AND a.group_id = ug.group_id
				AND g.group_id = ug.group_id
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . (($sql_user) ? 'AND ug.' . $sql_user : '') . "
				$sql_template
				$sql_opts";

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$option = ($sql_opts_select) ? $row['auth_option'] : $this->auth->acl_options['option'][$row['auth_option_id']];

				if (!isset($hold_ary[$row['user_id']][$row['template_id']][$option]) || (isset($hold_ary[$row['user_id']][$row['template_id']][$option]) && $hold_ary[$row['user_id']][$row['template_id']][$option] != ACL_NEVER))
				{
					$hold_ary[$row['user_id']][$row['template_id']][$option] = $row['auth_setting'];

					// If we detect ACL_NEVER, we will unset the flag option (within building the bitstring it is correctly set again)
					if ($row['auth_setting'] == ACL_NEVER)
					{
						$flag = substr($option, 0, strpos($option, '_') + 1);

						if (isset($hold_ary[$row['user_id']][$row['template_id']][$flag]) && $hold_ary[$row['user_id']][$row['template_id']][$flag] == ACL_YES)
						{
							unset($hold_ary[$row['user_id']][$row['template_id']][$flag]);

							/*							if (in_array(ACL_YES, $hold_ary[$row['user_id']][$row['template_id']]))
														{
															$hold_ary[$row['user_id']][$row['template_id']][$flag] = ACL_YES;
														}
							*/
						}
					}
				}
			}
			$this->db->sql_freeresult($result);
		}

		return $hold_ary;
	}

	/**
	 * Get raw user based permission settings
	 */
	function acl_user_raw_data($user_id = false, $opts = false, $template_id = false)
	{
		$sql_user = ($user_id !== false) ? ((!is_array($user_id)) ? 'user_id = ' . (int) $user_id : $this->db->sql_in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_template = ($template_id !== false) ? ((!is_array($template_id)) ? 'AND a.template_id = ' . (int) $template_id : 'AND ' . $this->db->sql_in_set('a.template_id', array_map('intval', $template_id))) : '';

		$sql_opts = '';
		$hold_ary = $sql_ary = [];

		if ($opts !== false)
		{
			$this->auth->build_auth_option_statement('ao.auth_option', $opts, $sql_opts);
		}

		// Grab user settings - non-role specific...
		$sql_ary[] = 'SELECT a.user_id, a.template_id, a.auth_setting, a.auth_option_id, ao.auth_option
			FROM ' . ACL_USERS_TABLE . ' a, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE a.auth_role_id = 0
				AND a.auth_option_id = ao.auth_option_id ' .
			(($sql_user) ? 'AND a.' . $sql_user : '') . "
				$sql_template
				$sql_opts
			ORDER BY a.template_id, ao.auth_option";

		// Now the role settings - user-specific
		$sql_ary[] = 'SELECT a.user_id, a.template_id, r.auth_option_id, r.auth_setting, r.auth_option_id, ao.auth_option
			FROM ' . ACL_USERS_TABLE . ' a, ' . ACL_ROLES_DATA_TABLE . ' r, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE a.auth_role_id = r.role_id
				AND r.auth_option_id = ao.auth_option_id ' .
			(($sql_user) ? 'AND a.' . $sql_user : '') . "
				$sql_template
				$sql_opts
			ORDER BY a.template_id, ao.auth_option";

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$hold_ary[$row['user_id']][$row['template_id']][$row['auth_option']] = $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);
		}

		return $hold_ary;
	}

	/**
	 * Get assigned roles
	 */
	function acl_role_data($user_type, $role_type, $ug_id = false, $template_id = false)
	{
		$roles = [];

		$sql_id = ($user_type == 'user') ? 'user_id' : 'group_id';

		$sql_ug = ($ug_id !== false) ? ((!is_array($ug_id)) ? "AND a.$sql_id = $ug_id" : 'AND ' . $this->db->sql_in_set("a.$sql_id", $ug_id)) : '';
		$sql_template = ($template_id !== false) ? ((!is_array($template_id)) ? "AND a.template_id = $template_id" : 'AND ' . $this->db->sql_in_set('a.template_id', $template_id)) : '';

		// Grab assigned roles...
		$sql = 'SELECT a.auth_role_id, a.' . $sql_id . ', a.template_id
			FROM ' . (($user_type == 'user') ? ACL_USERS_TABLE : ACL_GROUPS_TABLE) . ' a, ' . ACL_ROLES_TABLE . " r
			WHERE a.auth_role_id = r.role_id
				AND r.role_type = '" . $this->db->sql_escape($role_type) . "'
				$sql_ug
				$sql_template
			ORDER BY r.role_order ASC";
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$roles[$row[$sql_id]][$row['template_id']] = $row['auth_role_id'];
		}
		$this->db->sql_freeresult($result);

		return $roles;
	}

	/**
	 * Assign category to template
	 * used by display_mask()
	 */
	function assign_cat_array(&$category_array, $tpl_cat, $tpl_mask, $ug_id, $template_id, $s_view, $show_trace = false)
	{
		$order = array_flip(array_keys($this->phpbb_permissions->get_permissions()));

		foreach ($category_array as $cat => $cat_array)
		{
			if (!$this->phpbb_permissions->category_defined($cat))
			{
				continue;
			}

			$this->template->assign_block_vars($tpl_cat, [
				'S_YES'   => ($cat_array['S_YES'] && !$cat_array['S_NEVER'] && !$cat_array['S_NO']) ? true : false,
				'S_NEVER' => ($cat_array['S_NEVER'] && !$cat_array['S_YES'] && !$cat_array['S_NO']) ? true : false,
				'S_NO'    => ($cat_array['S_NO'] && !$cat_array['S_NEVER'] && !$cat_array['S_YES']) ? true : false,

				'CAT_NAME' => $this->phpbb_permissions->get_category_lang($cat),
			]);

			$permissions = array_filter($cat_array['permissions'], [$this->phpbb_permissions, 'permission_defined'], ARRAY_FILTER_USE_KEY);

			uksort($permissions, function ($a, $b) use ($order) {
				return $order[$a] <=> $order[$b];
			});

			foreach ($permissions as $permission => $allowed)
			{
				if ($s_view)
				{
					$this->template->assign_block_vars($tpl_cat . '.' . $tpl_mask, [
						'S_YES'   => ($allowed == ACL_YES) ? true : false,
						'S_NEVER' => ($allowed == ACL_NEVER) ? true : false,

						'UG_ID'        => $ug_id,
						'TEMPLATE_ID'  => $template_id,
						'FIELD_NAME'   => $permission,
						'S_FIELD_NAME' => 'setting[' . $ug_id . '][' . $template_id . '][' . $permission . ']',

						'U_TRACE'  => ($show_trace) ? append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", "i=-toxyy-postformtemplates-acp-template_permissions_module&amp;mode=trace&amp;u=$ug_id&amp;t=$template_id&amp;auth=$permission") : '',
						'UA_TRACE' => ($show_trace) ? append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", "i=-toxyy-postformtemplates-acp-template_permissions_module&mode=trace&u=$ug_id&t=$template_id&auth=$permission", false) : '',

						'PERMISSION' => $this->phpbb_permissions->get_permission_lang($permission),
					]);
				}
				else
				{
					$this->template->assign_block_vars($tpl_cat . '.' . $tpl_mask, [
						'S_YES'   => ($allowed == ACL_YES) ? true : false,
						'S_NEVER' => ($allowed == ACL_NEVER) ? true : false,
						'S_NO'    => ($allowed == ACL_NO) ? true : false,

						'UG_ID'        => $ug_id,
						'TEMPLATE_ID'  => $template_id,
						'FIELD_NAME'   => $permission,
						'S_FIELD_NAME' => 'setting[' . $ug_id . '][' . $template_id . '][' . $permission . ']',

						'U_TRACE'  => ($show_trace) ? append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", "i=-toxyy-postformtemplates-acp-template_permissions_module&amp;mode=trace&amp;u=$ug_id&amp;t=$template_id&amp;auth=$permission") : '',
						'UA_TRACE' => ($show_trace) ? append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", "i=-toxyy-postformtemplates-acp-template_permissions_module&mode=trace&u=$ug_id&t=$template_id&auth=$permission", false) : '',

						'PERMISSION' => $this->phpbb_permissions->get_permission_lang($permission),
					]);
				}
			}
		}
	}

	/**
	 * Get permission mask
	 * This function only supports getting permissions of one type (for example a_)
	 *
	 * @param set|view                 $mode        defines the permissions we get, view gets effective permissions
	 *                                              (checking user AND group permissions), set only gets the user or
	 *                                              group permission set alone
	 * @param mixed                    $user_id     user ids to search for (a user_id or a group_id has to be specified
	 *                                              at least)
	 * @param mixed                    $group_id    group ids to search for, return group related settings (a user_id
	 *                                              or a group_id has to be specified at least)
	 * @param mixed                    $template_id template_ids to search for. Defining a template id also means
	 *                                              getting local settings
	 * @param string                   $auth_option the auth_option defines the permission setting to look for (a_ for
	 *                                              example)
	 * @param local|global             $scope       the scope defines the permission scope. If local, a template_id is
	 *                                              additionally required
	 * @param ACL_NEVER|ACL_NO|ACL_YES $acl_fill    defines the mode those permissions not set are getting filled with
	 */
	function get_mask($mode, $user_id = false, $group_id = false, $template_id = false, $auth_option = false, $scope = false, $acl_fill = ACL_NEVER)
	{
		$hold_ary = [];
		$view_user_mask = ($mode == 'view' && $group_id === false) ? true : false;

		if ($auth_option === false || $scope === false)
		{
			return [];
		}

		$acl_user_function = ($mode == 'set') ? 'acl_user_raw_data' : 'acl_raw_data';

		if (!$view_user_mask)
		{
			if ($template_id !== false)
			{
				$hold_ary = ($group_id !== false) ? $this->acl_group_raw_data($group_id, $auth_option . '%', $template_id) : $this->$acl_user_function($user_id, $auth_option . '%', $template_id);
			}
			else
			{
				$hold_ary = ($group_id !== false) ? $this->acl_group_raw_data($group_id, $auth_option . '%', ($scope == 'global') ? 0 : false) : $this->$acl_user_function($user_id, $auth_option . '%', ($scope == 'global') ? 0 : false);
			}
		}

		// Make sure hold_ary is filled with every setting (prevents missing templates/users/groups)
		$ug_id = ($group_id !== false) ? ((!is_array($group_id)) ? [$group_id] : $group_id) : ((!is_array($user_id)) ? [$user_id] : $user_id);
		$template_ids = ($template_id !== false) ? ((!is_array($template_id)) ? [$template_id] : $template_id) : (($scope == 'global') ? [0] : []);

		// Only those options we need
		$compare_options = array_diff(preg_replace('/^((?!' . $auth_option . ').+)|(' . $auth_option . ')$/', '', array_keys($this->auth->acl_options[$scope])), ['']);

		// If template_ids is false and the scope is local we actually want to have all templates within the array
		if ($scope == 'local' && !count($template_ids))
		{
			$sql = 'SELECT template_id
				FROM ' . PFT_TEMPLATES_TABLE;
			$result = $this->db->sql_query($sql, 120);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$template_ids[] = (int) $row['template_id'];
			}
			$this->db->sql_freeresult($result);
		}

		if ($view_user_mask)
		{
			$auth2 = null;

			$sql = 'SELECT user_id, user_permissions, user_type
				FROM ' . USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('user_id', $ug_id);
			$result = $this->db->sql_query($sql);

			while ($userdata = $this->db->sql_fetchrow($result))
			{
				$auth2 = new \phpbb\auth\auth();
				$this->acl($userdata, $auth2);

				$hold_ary[$userdata['user_id']] = [];
				foreach ($template_ids as $t_id)
				{
					$hold_ary[$userdata['user_id']][$t_id] = [];
					foreach ($compare_options as $option)
					{
						$hold_ary[$userdata['user_id']][$t_id][$option] = $auth2->acl_get($option, $t_id);
					}
				}
			}
			$this->db->sql_freeresult($result);

			unset($userdata);
			unset($auth2);
		}

		foreach ($ug_id as $_id)
		{
			if (!isset($hold_ary[$_id]))
			{
				$hold_ary[$_id] = [];
			}

			foreach ($template_ids as $t_id)
			{
				if (!isset($hold_ary[$_id][$t_id]))
				{
					$hold_ary[$_id][$t_id] = [];
				}
			}
		}

		// Now, we need to fill the gaps with $acl_fill. ;)

		// Now switch back to keys
		if (count($compare_options))
		{
			$compare_options = array_combine($compare_options, array_fill(1, count($compare_options), $acl_fill));
		}

		// Defining the user-function here to save some memory
		$return_acl_fill = function () use ($acl_fill) {
			return $acl_fill;
		};

		// Actually fill the gaps
		if (count($hold_ary))
		{
			foreach ($hold_ary as $ug_id => $row)
			{
				foreach ($row as $id => $options)
				{
					// Do not include the global auth_option
					unset($options[$auth_option]);

					// Not a "fine" solution, but at all it's a 1-dimensional
					// array_diff_key function filling the resulting array values with zeros
					// The differences get merged into $hold_ary (all permissions having $acl_fill set)
					$hold_ary[$ug_id][$id] = array_merge($options,

						array_map($return_acl_fill,
							array_flip(
								array_diff(
									array_keys($compare_options), array_keys($options)
								)
							)
						)
					);
				}
			}
		}
		else
		{
			$hold_ary[($group_id !== false) ? $group_id : $user_id][(int) $template_id] = $compare_options;
		}

		return $hold_ary;
	}

	/**
	 * Display permission mask (assign to template)
	 */
	function display_mask($mode, $permission_type, &$hold_ary, $user_mode = 'user', $local = false, $group_display = true)
	{
		// Define names for template loops, might be able to be set
		$tpl_pmask = 'p_mask';
		$tlp_tmask = 't_mask';
		$tpl_category = 'category';
		$tpl_mask = 'mask';

		$l_acl_type = $this->phpbb_permissions->get_type_lang($permission_type, (($local) ? 'local' : 'global'));

		// Allow trace for viewing permissions and in user mode
		$show_trace = ($mode == 'view' && $user_mode == 'user') ? true : false;

		// Get names
		if ($user_mode == 'user')
		{
			$sql = 'SELECT user_id as ug_id, username as ug_name
				FROM ' . USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('user_id', array_keys($hold_ary)) . '
				ORDER BY username_clean ASC';
		}
		else
		{
			$sql = 'SELECT group_id as ug_id, group_name as ug_name, group_type
				FROM ' . GROUPS_TABLE . '
				WHERE ' . $this->db->sql_in_set('group_id', array_keys($hold_ary)) . '
				ORDER BY group_type DESC, group_name ASC';
		}
		$result = $this->db->sql_query($sql);

		$ug_names_ary = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ug_names_ary[$row['ug_id']] = ($user_mode == 'user') ? $row['ug_name'] : $this->group_helper->get_name($row['ug_name']);
		}
		$this->db->sql_freeresult($result);

		// Get used templates
		$template_ids = [];
		foreach ($hold_ary as $ug_id => $row)
		{
			$template_ids = array_merge($template_ids, array_keys($row));
		}
		$template_ids = array_unique($template_ids);

		$template_names_ary = [];
		if ($local)
		{
			$template_names_ary = $this->manage_templates_helper->make_template_select(false, false, true, false, false, false, true);

			// Remove the disabled ones, since we do not create an option field here...
			foreach ($template_names_ary as $key => $value)
			{
				if (!$value['disabled'])
				{
					continue;
				}
				unset($template_names_ary[$key]);
			}
		}
		else
		{
			$template_names_ary[0] = $l_acl_type;
		}

		// Get available roles
		$sql = 'SELECT *
			FROM ' . ACL_ROLES_TABLE . "
			WHERE role_type = '" . $this->db->sql_escape($permission_type) . "'
			ORDER BY role_order ASC";
		$result = $this->db->sql_query($sql);

		$roles = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$roles[$row['role_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		$cur_roles = $this->acl_role_data($user_mode, $permission_type, array_keys($hold_ary));

		// Build js roles array (role data assignments)
		$s_role_js_array = '';

		if (count($roles))
		{
			$s_role_js_array = [];

			// Make sure every role (even if empty) has its array defined
			foreach ($roles as $_role_id => $null)
			{
				$s_role_js_array[$_role_id] = "\n" . 'role_options[' . $_role_id . '] = new Array();' . "\n";
			}

			$sql = 'SELECT r.role_id, o.auth_option, r.auth_setting
				FROM ' . ACL_ROLES_DATA_TABLE . ' r, ' . ACL_OPTIONS_TABLE . ' o
				WHERE o.auth_option_id = r.auth_option_id
					AND ' . $this->db->sql_in_set('r.role_id', array_keys($roles));
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$flag = substr($row['auth_option'], 0, strpos($row['auth_option'], '_') + 1);
				if ($flag == $row['auth_option'])
				{
					continue;
				}

				$s_role_js_array[$row['role_id']] .= 'role_options[' . $row['role_id'] . '][\'' . addslashes($row['auth_option']) . '\'] = ' . $row['auth_setting'] . '; ';
			}
			$this->db->sql_freeresult($result);

			$s_role_js_array = implode('', $s_role_js_array);
		}

		$this->template->assign_var('S_ROLE_JS_ARRAY', $s_role_js_array);
		unset($s_role_js_array);

		// Now obtain memberships
		$user_groups_default = $user_groups_custom = [];
		if ($user_mode == 'user' && $group_display)
		{
			$sql = 'SELECT group_id, group_name, group_type
				FROM ' . GROUPS_TABLE . '
				ORDER BY group_type DESC, group_name ASC';
			$result = $this->db->sql_query($sql);

			$groups = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$groups[$row['group_id']] = $row;
			}
			$this->db->sql_freeresult($result);

			$memberships = group_memberships(false, array_keys($hold_ary), false);

			// User is not a member of any group? Bad admin, bad bad admin...
			if ($memberships)
			{
				foreach ($memberships as $row)
				{
					$user_groups_default[$row['user_id']][] = $this->group_helper->get_name($groups[$row['group_id']]['group_name']);
				}
			}
			unset($memberships, $groups);
		}

		// If we only have one template id to display or being in local mode and more than one user/group to display,
		// we switch the complete interface to group by user/usergroup instead of grouping by template
		// To achieve this, we need to switch the array a bit
		if (count($template_ids) == 1 || ($local && count($ug_names_ary) > 1))
		{
			$hold_ary_temp = $hold_ary;
			$hold_ary = [];
			foreach ($hold_ary_temp as $ug_id => $row)
			{
				foreach ($template_names_ary as $template_id => $template_row)
				{
					if (isset($row[$template_id]))
					{
						$hold_ary[$template_id][$ug_id] = $row[$template_id];
					}
				}
			}
			unset($hold_ary_temp);

			foreach ($hold_ary as $template_id => $template_array)
			{
				$content_array = $categories = [];
				$this->auth_admin->build_permission_array($hold_ary[$template_id], $content_array, $categories, array_keys($ug_names_ary));

				$this->template->assign_block_vars($tpl_pmask, [
					'NAME'    => ($template_id == 0) ? $template_names_ary[0] : $template_names_ary[$template_id]['template_name'],
					'PADDING' => ($template_id == 0) ? '' : $template_names_ary[$template_id]['padding'],

					'CATEGORIES' => implode('</th><th>', $categories),

					'L_ACL_TYPE' => $l_acl_type,

					'S_LOCAL'       => ($local) ? true : false,
					'S_GLOBAL'      => (!$local) ? true : false,
					'S_NUM_CATS'    => count($categories),
					'S_VIEW'        => ($mode == 'view') ? true : false,
					'S_NUM_OBJECTS' => count($content_array),
					'S_USER_MODE'   => ($user_mode == 'user') ? true : false,
					'S_GROUP_MODE'  => ($user_mode == 'group') ? true : false,
				]);

				foreach ($content_array as $ug_id => $ug_array)
				{
					// Build role dropdown options
					$current_role_id = (isset($cur_roles[$ug_id][$template_id])) ? $cur_roles[$ug_id][$template_id] : 0;

					$role_options = [];

					$s_role_options = '';
					$current_role_id = (isset($cur_roles[$ug_id][$template_id])) ? $cur_roles[$ug_id][$template_id] : 0;

					foreach ($roles as $role_id => $role_row)
					{
						$role_description = (!empty($this->language->lang($role_row['role_description']))) ? $this->language->lang($role_row['role_description']) : nl2br($role_row['role_description']);
						$role_name = (!empty($this->language->lang($role_row['role_name']))) ? $this->language->lang($role_row['role_name']) : $role_row['role_name'];

						$title = ($role_description) ? ' title="' . $role_description . '"' : '';
						$s_role_options .= '<option value="' . $role_id . '"' . (($role_id == $current_role_id) ? ' selected="selected"' : '') . $title . '>' . $role_name . '</option>';

						$role_options[] = [
							'ID'        => $role_id,
							'ROLE_NAME' => $role_name,
							'TITLE'     => $role_description,
							'SELECTED'  => $role_id == $current_role_id,
						];
					}

					if ($s_role_options)
					{
						$s_role_options = '<option value="0"' . ((!$current_role_id) ? ' selected="selected"' : '') . ' title="' . htmlspecialchars($this->language->lang('NO_ROLE_ASSIGNED_EXPLAIN'), ENT_COMPAT) . '">' . $this->language->lang('NO_ROLE_ASSIGNED') . '</option>' . $s_role_options;
					}

					if (!$current_role_id && $mode != 'view')
					{
						$s_custom_permissions = false;

						foreach ($ug_array as $key => $value)
						{
							if ($value['S_NEVER'] || $value['S_YES'])
							{
								$s_custom_permissions = true;
								break;
							}
						}
					}
					else
					{
						$s_custom_permissions = false;
					}

					$this->template->assign_block_vars($tpl_pmask . '.' . $tlp_tmask, [
						'NAME'           => $ug_names_ary[$ug_id],
						'UG_ID'          => $ug_id,
						'S_ROLE_OPTIONS' => $s_role_options,
						'S_CUSTOM'       => $s_custom_permissions,
						'TEMPLATE_ID'    => $template_id,
						'S_ROLE_ID'      => $current_role_id,
					]);

					$this->template->assign_block_vars_array($tpl_pmask . '.' . $tlp_tmask . '.role_options', $role_options);

					$this->assign_cat_array($ug_array, $tpl_pmask . '.' . $tlp_tmask . '.' . $tpl_category, $tpl_mask, $ug_id, $template_id, ($mode == 'view'), $show_trace);

					unset($content_array[$ug_id]);
				}

				unset($hold_ary[$template_id]);
			}
		}
		else
		{
			foreach ($ug_names_ary as $ug_id => $ug_name)
			{
				if (!isset($hold_ary[$ug_id]))
				{
					continue;
				}

				$content_array = $categories = [];
				$this->auth_admin->build_permission_array($hold_ary[$ug_id], $content_array, $categories, array_keys($template_names_ary));

				$this->template->assign_block_vars($tpl_pmask, [
					'NAME'       => $ug_name,
					'CATEGORIES' => implode('</th><th>', $categories),

					'USER_GROUPS_DEFAULT' => ($user_mode == 'user' && isset($user_groups_default[$ug_id]) && count($user_groups_default[$ug_id])) ? implode($this->language->lang('COMMA_SEPARATOR'), $user_groups_default[$ug_id]) : '',
					'USER_GROUPS_CUSTOM'  => ($user_mode == 'user' && isset($user_groups_custom[$ug_id]) && count($user_groups_custom[$ug_id])) ? implode($this->language->lang('COMMA_SEPARATOR'), $user_groups_custom[$ug_id]) : '',
					'L_ACL_TYPE'          => $l_acl_type,

					'S_LOCAL'       => ($local) ? true : false,
					'S_GLOBAL'      => (!$local) ? true : false,
					'S_NUM_CATS'    => count($categories),
					'S_VIEW'        => ($mode == 'view') ? true : false,
					'S_NUM_OBJECTS' => count($content_array),
					'S_USER_MODE'   => ($user_mode == 'user') ? true : false,
					'S_GROUP_MODE'  => ($user_mode == 'group') ? true : false,
				]);

				foreach ($content_array as $template_id => $template_array)
				{
					// Build role dropdown options
					$current_role_id = (isset($cur_roles[$ug_id][$template_id])) ? $cur_roles[$ug_id][$template_id] : 0;

					$role_options = [];

					$current_role_id = (isset($cur_roles[$ug_id][$template_id])) ? $cur_roles[$ug_id][$template_id] : 0;
					$s_role_options = '';

					foreach ($roles as $role_id => $role_row)
					{
						$role_description = (!empty($this->language->lang($role_row['role_description']))) ? $this->language->lang($role_row['role_description']) : nl2br($role_row['role_description']);
						$role_name = (!empty($this->language->lang($role_row['role_name']))) ? $this->language->lang($role_row['role_name']) : $role_row['role_name'];

						$title = ($role_description) ? ' title="' . $role_description . '"' : '';
						$s_role_options .= '<option value="' . $role_id . '"' . (($role_id == $current_role_id) ? ' selected="selected"' : '') . $title . '>' . $role_name . '</option>';

						$role_options[] = [
							'ID'        => $role_id,
							'ROLE_NAME' => $role_name,
							'TITLE'     => $role_description,
							'SELECTED'  => $role_id == $current_role_id,
						];
					}

					if ($s_role_options)
					{
						$s_role_options = '<option value="0"' . ((!$current_role_id) ? ' selected="selected"' : '') . ' title="' . htmlspecialchars($this->language->lang('NO_ROLE_ASSIGNED_EXPLAIN'), ENT_COMPAT) . '">' . $this->language->lang('NO_ROLE_ASSIGNED') . '</option>' . $s_role_options;
					}

					if (!$current_role_id && $mode != 'view')
					{
						$s_custom_permissions = false;

						foreach ($template_array as $key => $value)
						{
							if ($value['S_NEVER'] || $value['S_YES'])
							{
								$s_custom_permissions = true;
								break;
							}
						}
					}
					else
					{
						$s_custom_permissions = false;
					}

					$this->template->assign_block_vars($tpl_pmask . '.' . $tlp_tmask, [
						'NAME'           => ($template_id == 0) ? $template_names_ary[0] : $template_names_ary[$template_id]['template_name'],
						'PADDING'        => ($template_id == 0) ? '' : $template_names_ary[$template_id]['padding'],
						'S_CUSTOM'       => $s_custom_permissions,
						'UG_ID'          => $ug_id,
						'S_ROLE_OPTIONS' => $s_role_options,
						'TEMPLATE_ID'    => $template_id,
					]);

					$this->template->assign_block_vars_array($tpl_pmask . '.' . $tlp_tmask . '.role_options', $role_options);

					$this->assign_cat_array($template_array, $tpl_pmask . '.' . $tlp_tmask . '.' . $tpl_category, $tpl_mask, $ug_id, $template_id, ($mode == 'view'), $show_trace);
				}

				unset($hold_ary[$ug_id], $ug_names_ary[$ug_id]);
			}
		}
	}

	/**
	 * Get already assigned users/groups
	 */
	function retrieve_defined_user_groups($permission_scope, $template_id, $permission_type)
	{
		$sql_template_id = ($permission_scope == 'global') ? 'AND a.template_id = 0' : ((count($template_id)) ? 'AND ' . $this->db->sql_in_set('a.template_id', $template_id) : 'AND a.template_id <> 0');

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
			$s_defined_group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '">' . $this->group_helper->get_name($row['group_name']) . '</option>';
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
	 * Look up an option
	 * if the option is prefixed with !, then the result becomes negated
	 *
	 * If a forum id is specified the local option will be combined with a global option if one exist.
	 * If a forum id is not specified, only the global option will be checked.
	 */
	function acl_get($opt, $t = 0)
	{
		//echo '<pre>';
		//print_r($this->auth->get_permissions());
		//echo '</pre>';
		$negate = false;

		if (strpos($opt, '!') === 0)
		{
			$negate = true;
			$opt = substr($opt, 1);
		}

		if (!isset($this->auth->cache[$t][$opt]))
		{
			// We combine the global/local option with an OR because some options are global and local.
			// If the user has the global permission the local one is true too and vice versa
			$this->auth->cache[$t][$opt] = false;

			// Is this option a global permission setting?
			if (isset($this->auth->acl_options['global'][$opt]))
			{
				if (isset($this->auth->acl[0]))
				{
					$this->auth->cache[$t][$opt] = $this->auth->acl[0][$this->auth->acl_options['global'][$opt]];
				}
			}

			// Is this option a local permission setting?
			// But if we check for a global option only, we won't combine the options...
			if ($t != 0 && isset($this->auth->acl_options['local'][$opt]))
			{
				if (isset($this->auth->acl[$t]) && isset($this->auth->acl[$t][$this->auth->acl_options['local'][$opt]]))
				{
					$this->auth->cache[$t][$opt] |= $this->auth->acl[$t][$this->auth->acl_options['local'][$opt]];
				}
			}
		}

		// Founder always has all global options set to true...
		return ($negate) ? !$this->auth->cache[$t][$opt] : $this->auth->cache[$t][$opt];
	}

	/**
	 * Set a user or group ACL record
	 */
	function acl_set($ug_type, $template_id, $ug_id, $auth, $role_id = 0, $clear_prefetch = true)
	{
		// One or more templates
		if (!is_array($template_id))
		{
			$template_id = [$template_id];
		}

		// One or more users
		if (!is_array($ug_id))
		{
			$ug_id = [$ug_id];
		}

		$ug_id_sql = $this->db->sql_in_set($ug_type . '_id', array_map('intval', $ug_id));
		$template_sql = $this->db->sql_in_set('template_id', array_map('intval', $template_id));

		// Instead of updating, inserting, removing we just remove all current settings and re-set everything...
		$table = ($ug_type == 'user') ? ACL_USERS_TABLE : ACL_GROUPS_TABLE;
		$id_field = $ug_type . '_id';

		// Get any flags as required
		reset($auth);
		$flag = key($auth);
		$flag = substr($flag, 0, strpos($flag, '_') + 1);

		// This ID (the any-flag) is set if one or more permissions are true...
		$any_option_id = (int) $this->auth->acl_options['id'][$flag];

		// Remove any-flag from auth ary
		if (isset($auth[$flag]))
		{
			unset($auth[$flag]);
		}

		// Remove current auth options...
		$auth_option_ids = [(int) $any_option_id];
		foreach ($auth as $auth_option => $auth_setting)
		{
			$auth_option_ids[] = (int) $this->auth->acl_options['id'][$auth_option];
		}

		$sql = "DELETE FROM $table
			WHERE $template_sql
				AND $ug_id_sql
				AND " . $this->db->sql_in_set('auth_option_id', $auth_option_ids);
		$this->db->sql_query($sql);

		// Remove those having a role assigned... the correct type of course...
		$sql = 'SELECT role_id
			FROM ' . ACL_ROLES_TABLE . "
			WHERE role_type = '" . $this->db->sql_escape($flag) . "'";
		$result = $this->db->sql_query($sql);

		$role_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$role_ids[] = $row['role_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($role_ids))
		{
			$sql = "DELETE FROM $table
				WHERE $template_sql
					AND $ug_id_sql
					AND auth_option_id = 0
					AND " . $this->db->sql_in_set('auth_role_id', $role_ids);
			$this->db->sql_query($sql);
		}

		// Ok, include the any-flag if one or more auth options are set to yes...
		foreach ($auth as $auth_option => $setting)
		{
			if ($setting == ACL_YES && (!isset($auth[$flag]) || $auth[$flag] == ACL_NEVER))
			{
				$auth[$flag] = ACL_YES;
			}
		}

		$sql_ary = [];
		foreach ($template_id as $template)
		{
			$template = (int) $template;

			if ($role_id)
			{
				foreach ($ug_id as $id)
				{
					$sql_ary[] = [
						$id_field        => (int) $id,
						'template_id'    => (int) $template,
						'auth_option_id' => 0,
						'auth_setting'   => 0,
						'auth_role_id'   => (int) $role_id,
					];
				}
			}
			else
			{
				foreach ($auth as $auth_option => $setting)
				{
					$auth_option_id = (int) $this->auth->acl_options['id'][$auth_option];

					if ($setting != ACL_NO)
					{
						foreach ($ug_id as $id)
						{
							$sql_ary[] = [
								$id_field        => (int) $id,
								'template_id'    => (int) $template,
								'auth_option_id' => (int) $auth_option_id,
								'auth_setting'   => (int) $setting,
							];
						}
					}
				}
			}
		}

		$this->db->sql_multi_insert($table, $sql_ary);

		if ($clear_prefetch)
		{
			$this->auth_admin->acl_clear_prefetch();
		}
	}

	/**
	 * Remove local permission
	 */
	function acl_delete($mode, $ug_id = false, $template_id = false, $permission_type = false)
	{
		if ($ug_id === false && $template_id === false)
		{
			return;
		}

		$option_id_ary = [];
		$table = ($mode == 'user') ? ACL_USERS_TABLE : ACL_GROUPS_TABLE;
		$id_field = $mode . '_id';

		$where_sql = [];

		if ($template_id !== false)
		{
			$where_sql[] = (!is_array($template_id)) ? 'template_id = ' . (int) $template_id : $this->db->sql_in_set('template_id', array_map('intval', $template_id));
		}

		if ($ug_id !== false)
		{
			$where_sql[] = (!is_array($ug_id)) ? $id_field . ' = ' . (int) $ug_id : $this->db->sql_in_set($id_field, array_map('intval', $ug_id));
		}

		// There seem to be auth options involved, therefore we need to go through the list and make sure we capture roles correctly
		if ($permission_type !== false)
		{
			// Get permission type
			$sql = 'SELECT auth_option, auth_option_id
				FROM ' . ACL_OPTIONS_TABLE . "
				WHERE auth_option " . $this->db->sql_like_expression($permission_type . $this->db->get_any_char());
			$result = $this->db->sql_query($sql);

			$auth_id_ary = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$option_id_ary[] = $row['auth_option_id'];
				$auth_id_ary[$row['auth_option']] = ACL_NO;
			}
			$this->db->sql_freeresult($result);

			// First of all, lets grab the items having roles with the specified auth options assigned
			$sql = "SELECT auth_role_id, $id_field, template_id
				FROM $table, " . ACL_ROLES_TABLE . " r
				WHERE auth_role_id <> 0
					AND auth_role_id = r.role_id
					AND r.role_type = '{$permission_type}'
					AND " . implode(' AND ', $where_sql) . '
				ORDER BY auth_role_id';
			$result = $this->db->sql_query($sql);

			$cur_role_auth = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$cur_role_auth[$row['auth_role_id']][$row['template_id']][] = $row[$id_field];
			}
			$this->db->sql_freeresult($result);

			// Get role data for resetting data
			if (count($cur_role_auth))
			{
				$sql = 'SELECT ao.auth_option, rd.role_id, rd.auth_setting
					FROM ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_ROLES_DATA_TABLE . ' rd
					WHERE ao.auth_option_id = rd.auth_option_id
						AND ' . $this->db->sql_in_set('rd.role_id', array_keys($cur_role_auth));
				$result = $this->db->sql_query($sql);

				$auth_settings = [];
				while ($row = $this->db->sql_fetchrow($result))
				{
					// We need to fill all auth_options, else setting it will fail...
					if (!isset($auth_settings[$row['role_id']]))
					{
						$auth_settings[$row['role_id']] = $auth_id_ary;
					}
					$auth_settings[$row['role_id']][$row['auth_option']] = $row['auth_setting'];
				}
				$this->db->sql_freeresult($result);

				// Set the options
				foreach ($cur_role_auth as $role_id => $auth_row)
				{
					foreach ($auth_row as $f_id => $ug_row)
					{
						$this->acl_set($mode, $f_id, $ug_row, $auth_settings[$role_id], 0, false);
					}
				}
			}
		}

		// Now, normally remove permissions...
		if ($permission_type !== false)
		{
			$where_sql[] = $this->db->sql_in_set('auth_option_id', array_map('intval', $option_id_ary));
		}

		$sql = "DELETE FROM $table
			WHERE " . implode(' AND ', $where_sql);
		$this->db->sql_query($sql);

		$this->auth_admin->acl_clear_prefetch();
	}
}
