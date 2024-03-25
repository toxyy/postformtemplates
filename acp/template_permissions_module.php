<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class template_permissions_module
{
	/** @var \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container */
	protected $phpbb_container;
	/** @var \phpbb\cache\driver\driver_interface $cache */
	protected $cache;
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\request\request $request */
	protected $request;
	/** @var \phpbb\user $user */
	protected $user;
	/** @var \phpbb\auth\auth $auth */
	protected $auth;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\template\template $template */
	protected $template;
	/** @var \phpbb\config\config $config */
	protected $config;
	/** @var \phpbb\log\log $phpbb_log */
	protected $phpbb_log;
	/** @var \phpbb\group\helper $group_helper */
	protected $group_helper;
	/** @var \phpbb\permissions $phpbb_permissions */
	protected $permissions;
	/** @var \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper */
	protected $auth_admin_helper;
	/** @var \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper */
	protected $manage_templates_helper;
	/** @var \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper */
	protected $template_permissions_helper;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpEx;

	public $u_action;
	public $permission_dropdown;
	public $tpl_name;
	public $page_title;

	public function __construct()
	{
		global $phpbb_container;

		/** @var \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container */
		$this->phpbb_container = $phpbb_container;
		/** @var \phpbb\cache\driver\driver_interface $cache */
		$this->cache = $this->phpbb_container->get('cache.driver');
		/** @var \phpbb\db\driver\driver_interface $db */
		$this->db = $this->phpbb_container->get('dbal.conn');
		/** @var \phpbb\request\request $request */
		$this->request = $this->phpbb_container->get('request');
		/** @var \phpbb\user $user */
		$this->user = $this->phpbb_container->get('user');
		/** @var \phpbb\auth\auth $auth */
		$this->auth = $this->phpbb_container->get('auth');
		/** @var \phpbb\language\language $language */
		$this->language = $this->phpbb_container->get('language');
		/** @var \phpbb\template\template $template */
		$this->template = $this->phpbb_container->get('template');
		/** @var \phpbb\config\config $config */
		$this->config = $this->phpbb_container->get('config');
		/** @var \phpbb\log\log $phpbb_log */
		$this->phpbb_log = $this->phpbb_container->get('log');
		/** @var phpbb\group\helper $phpbb_log */
		$this->group_helper = $this->phpbb_container->get('group_helper');
		/** @var \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper */
		$this->auth_admin_helper = $this->phpbb_container->get('toxyy.postformtemplates.auth_admin_helper');
		/** @var \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper */
		$this->manage_templates_helper = $this->phpbb_container->get('toxyy.postformtemplates.manage_templates_helper');
		/** @var \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper */
		$this->template_permissions_helper = $this->phpbb_container->get('toxyy.postformtemplates.template_permissions_helper');
		/** @var string */
		$this->phpbb_root_path = $this->phpbb_container->getParameter('core.root_path');
		/** @var string */
		$this->phpEx = $this->phpbb_container->getParameter('core.php_ext');

		if (!defined('PFT_TEMPLATES_TABLE'))
		{
			define('PFT_TEMPLATES_TABLE', $this->phpbb_container->getParameter('toxyy.postformtemplates.tables.pft_templates_table'));
		}
		if (!defined('TEMPLATE_FORM'))
		{
			define('TEMPLATE_FORM', $this->phpbb_container->getParameter('toxyy.postformtemplates.constants.template_form'));
		}
		if (!defined('TEMPLATE_CAT'))
		{
			define('TEMPLATE_CAT', $this->phpbb_container->getParameter('toxyy.postformtemplates.constants.template_cat'));
		}
	}

	public function main($id, $mode)
	{
		$this->tpl_name = 'acp_template_permissions';
		$this->page_title = $this->language->lang('ACP_PFT_MANAGE_TEMPLATES_TITLE');

		if (!function_exists('user_get_id_name'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->phpEx);
		}

		if (!class_exists('auth_admin'))
		{
			include($this->phpbb_root_path . 'includes/acp/auth.' . $this->phpEx);
		}

		$auth_admin = new \auth_admin();

		$this->language->add_lang('acp/permissions');
		add_permission_language();

		$this->permissions = $this->phpbb_container->get('acl.permissions');

		// Trace has other vars
		if ($mode == 'trace')
		{
			$user_id = $this->request->variable('u', 0);
			$template_id = $this->request->variable('t', 0);
			$permission = $this->request->variable('auth', '');

			$this->tpl_name = 'permission_trace';

			if ($user_id && isset($auth_admin->acl_options['id'][$permission]) && $this->auth->acl_get('a_viewauth'))
			{
				$this->page_title = sprintf($this->language->lang('TRACE_PERMISSION'), $this->permissions->get_permission_lang($permission));
				$this->permission_trace($user_id, $template_id, $permission);
				return;
			}
			trigger_error('NO_MODE', E_USER_ERROR);
		}

		// Copy template_ permissions
		if ($mode == 'setting_template_copy')
		{
			$this->tpl_name = 'permission_template_copy';

			if ($this->auth->acl_get('a_fauth') && $this->auth->acl_get('a_authusers') && $this->auth->acl_get('a_authgroups') && $this->auth->acl_get('a_mauth'))
			{
				$this->page_title = 'ACP_TEMPLATE_PERMISSIONS_COPY';
				$this->manage_templates_helper->copy_template_permissions($this->u_action);
				return;
			}

			trigger_error('NO_MODE', E_USER_ERROR);
		}

		// Set some vars
		$action = $this->request->variable('action', ['' => 0]);
		$action = key($action);
		$action = (isset($_POST['psubmit']))
			? 'apply_permissions'
			: $action;

		$all_templates = $this->request->variable('all_templates', 0);
		$subtemplate_id = $this->request->variable('subtemplate_id', 0);
		$template_id = $this->request->variable('template_id', [0]);

		$username = $this->request->variable('username', [''], true);
		$usernames = $this->request->variable('usernames', '', true);
		$user_id = $this->request->variable('user_id', [0]);

		$group_id = $this->request->variable('group_id', [0]);
		$select_all_groups = $this->request->variable('select_all_groups', 0);

		$form_name = 'acp_permissions';
		add_form_key($form_name);

		// If select all groups is set, we pre-build the group id array (this option is used for other screens to link to the permission settings screen)
		if ($select_all_groups)
		{
			// Add default groups to selection
			$sql_and = (!$this->config['coppa_enable'])
				? " AND group_name <> 'REGISTERED_COPPA'"
				: '';

			$sql = 'SELECT group_id
				FROM ' . GROUPS_TABLE . '
				WHERE group_type = ' . GROUP_SPECIAL . "
				$sql_and";
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$group_id[] = $row['group_id'];
			}
			$this->db->sql_freeresult($result);
		}

		// Map usernames to ids and vice versa
		if ($usernames)
		{
			$username = explode("\n", $usernames);
		}
		unset($usernames);

		if (count($username) && !count($user_id))
		{
			user_get_id_name($user_id, $username);

			if (!count($user_id))
			{
				trigger_error($this->language->lang('SELECTED_USER_NOT_EXIST') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
		unset($username);

		// Build template_ ids (of all template_s are checked or subtemplate listing used)
		if ($all_templates)
		{
			$sql = 'SELECT template_id
				FROM ' . PFT_TEMPLATES_TABLE . '
				ORDER BY left_id';
			$result = $this->db->sql_query($sql);

			$template_id = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$template_id[] = (int) $row['template_id'];
			}
			$this->db->sql_freeresult($result);
		}
		else if ($subtemplate_id)
		{
			$template_id = [];
			foreach ($this->manage_templates_helper->get_template_branch($subtemplate_id, 'children') as $row)
			{
				$template_id[] = (int) $row['template_id'];
			}
		}

		// Define some common variables for every mode
		$permission_scope = (strpos($mode, '_global') !== false)
			? 'global'
			: 'local';

		// Showing introductionary page?
		if ($mode == 'intro')
		{
			$this->page_title = 'ACP_PERMISSIONS';

			$this->template->assign_vars([
				'S_INTRO' => true,
			]);

			return;
		}

		switch ($mode)
		{
			case 'setting_mod_local':
			case 'setting_template_local':
				$this->permission_dropdown = ($mode == 'setting_mod_local')
					? ['m_']
					: ['pft_'];
				$permission_victim = ['templates', 'usergroup'];
				$this->page_title = ($mode == 'setting_mod_local')
					? 'ACP_FORUM_MODERATORS'
					: 'ACP_PFT_TEMPLATE_PERMISSIONS';
			break;

			case 'view_mod_local':
			case 'view_template_local':
				$this->permission_dropdown = ($mode == 'view_mod_local')
					? ['m_']
					: ['pft_'];
				$permission_victim = ['templates', 'usergroup_view'];
				$this->page_title = ($mode == 'view_mod_local')
					? 'ACP_VIEW_FORUM_MOD_PERMISSIONS'
					: 'ACP_PFT_VIEW_TEMPLATE_PERMISSIONS';
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}

		$this->template->assign_vars([
			'L_TITLE'   => $this->language->lang($this->page_title),
			'L_EXPLAIN' => $this->language->lang($this->page_title . '_EXPLAIN'),
		]);

		// Get permission type
		$permission_type = $this->request->variable('type', $this->permission_dropdown[0]);

		if (!in_array($permission_type, $this->permission_dropdown))
		{
			trigger_error($this->language->lang('WRONG_PERMISSION_TYPE') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Handle actions
		if (strpos($mode, 'setting_') === 0 && $action)
		{
			switch ($action)
			{
				case 'delete':
					if (confirm_box(true))
					{
						// All users/groups selected?
						$all_users = (isset($_POST['all_users']))
							? true
							: false;
						$all_groups = (isset($_POST['all_groups']))
							? true
							: false;

						if ($all_users || $all_groups)
						{
							$items = $this->template_permissions_helper->retrieve_defined_user_groups($permission_scope, $template_id, $permission_type);

							if ($all_users && count($items['user_ids']))
							{
								$user_id = $items['user_ids'];
							}
							else if ($all_groups && count($items['group_ids']))
							{
								$group_id = $items['group_ids'];
							}
						}

						if (count($user_id) || count($group_id))
						{
							$this->remove_permissions($mode, $permission_type, $this->auth_admin_helper, $user_id, $group_id, $template_id);
						}
						else
						{
							trigger_error($this->language->lang('NO_USER_GROUP_SELECTED') . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
					else
					{
						if (isset($_POST['cancel']))
						{
							$u_redirect = $this->u_action . '&amp;type=' . $permission_type;
							foreach ($template_id as $fid)
							{
								$u_redirect .= '&amp;template_id[]=' . $fid;
							}
							redirect($u_redirect);
						}

						$s_hidden_fields = [
							'i'           => $id,
							'mode'        => $mode,
							'action'      => [$action => 1],
							'user_id'     => $user_id,
							'group_id'    => $group_id,
							'template_id' => $template_id,
							'type'        => $permission_type,
						];
						if (isset($_POST['all_users']))
						{
							$s_hidden_fields['all_users'] = 1;
						}
						if (isset($_POST['all_groups']))
						{
							$s_hidden_fields['all_groups'] = 1;
						}
						confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields($s_hidden_fields));
					}
				break;

				case 'apply_permissions':
					if (!isset($_POST['setting']))
					{
						send_status_line(403, 'Forbidden');
						trigger_error($this->language->lang('NO_AUTH_SETTING_FOUND') . adm_back_link($this->u_action), E_USER_WARNING);
					}
					if (!check_form_key($form_name))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->set_permissions($mode, $permission_type, $auth_admin, $user_id, $group_id);
				break;

				case 'apply_all_permissions':
					if (!isset($_POST['setting']))
					{
						send_status_line(403, 'Forbidden');
						trigger_error($this->language->lang('NO_AUTH_SETTING_FOUND') . adm_back_link($this->u_action), E_USER_WARNING);
					}
					if (!check_form_key($form_name))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->set_all_permissions($mode, $permission_type, $auth_admin, $user_id, $group_id);
				break;
			}
		}

		// Go through the screens/options needed and present them in correct order
		foreach ($permission_victim as $victim)
		{
			switch ($victim)
			{
				case 'templates':
					if (count($template_id))
					{
						$this->template_permissions_helper->check_existence('template', $template_id, $this->u_action);
						continue 2;
					}

					$template_list = $this->manage_templates_helper->make_template_select(false, false, true, false, false, false, true);

					// Build template options
					$s_template_options = '';
					foreach ($template_list as $t_id => $t_row)
					{
						$s_template_options .= '<option value="' . $t_id . '"' . (
								($t_row['selected'])
								? ' selected="selected"'
								: ''
							) . (($t_row['disabled'])
								? ' disabled="disabled" class="disabled-option"'
								: ''
							) . '>' . $t_row['padding'] . $t_row['template_name'] . '</option>';
					}

					// Build subtemplate options
					$s_subtemplate_options = $this->template_permissions_helper->build_subtemplate_options($template_list);

					$this->template->assign_vars([
						'S_SELECT_TEMPLATE'     => true,
						'S_TEMPLATE_OPTIONS'    => $s_template_options,
						'S_SUBTEMPLATE_OPTIONS' => $s_subtemplate_options,
						'S_TEMPLATE_ALL'        => true,
						'S_TEMPLATE_MULTIPLE'   => true,
					]);
				break;

				case 'usergroup':
				case 'usergroup_view':
					$all_users = (isset($_POST['all_users']))
						? true
						: false;
					$all_groups = (isset($_POST['all_groups']))
						? true
						: false;

					if ((count($user_id) && !$all_users) || (count($group_id) && !$all_groups))
					{
						if (count($user_id))
						{
							$this->template_permissions_helper->check_existence('user', $user_id, $this->u_action);
						}

						if (count($group_id))
						{
							$this->template_permissions_helper->check_existence('group', $group_id, $this->u_action);
						}

						continue 2;
					}

					// Now we check the users... because the "all"-selection is different here (all defined users/groups)
					$items = $this->template_permissions_helper->retrieve_defined_user_groups($permission_scope, $template_id, $permission_type);

					if ($all_users && count($items['user_ids']))
					{
						$user_id = $items['user_ids'];
						continue 2;
					}

					if ($all_groups && count($items['group_ids']))
					{
						$group_id = $items['group_ids'];
						continue 2;
					}

					$this->template->assign_vars([
						'S_SELECT_USERGROUP'      => ($victim == 'usergroup')
							? true
							: false,
						'S_SELECT_USERGROUP_VIEW' => ($victim == 'usergroup_view')
							? true
							: false,
						'S_DEFINED_USER_OPTIONS'  => $items['user_ids_options'],
						'S_DEFINED_GROUP_OPTIONS' => $items['group_ids_options'],
						'S_ADD_GROUP_OPTIONS'     => group_select_options(false, $items['group_ids'], false),    // Show all groups
						'U_FIND_USERNAME'         => append_sid("{$this->phpbb_root_path}memberlist.{$this->phpEx}", 'mode=searchuser&amp;form=add_user&amp;field=username&amp;select_single=true'),
					]);
				break;
			}

			// The S_ALLOW_SELECT parameter below is a measure to lower memory usage.
			// If there are more than 5 templates selected the admin is not able to select all users/groups too.
			// We need to see if the number of templates can be increased or need to be decreased.

			// Setting permissions screen
			$s_hidden_fields = build_hidden_fields([
				'user_id'     => $user_id,
				'group_id'    => $group_id,
				'template_id' => $template_id,
				'type'        => $permission_type,
			]);

			$this->template->assign_vars([
				'U_ACTION'          => $this->u_action,
				'ANONYMOUS_USER_ID' => ANONYMOUS,

				'S_SELECT_VICTIM'    => true,
				'S_ALLOW_ALL_SELECT' => (count($template_id) > 5)
					? false
					: true,
				'S_CAN_SELECT_USER'  => ($this->auth->acl_get('a_authusers'))
					? true
					: false,
				'S_CAN_SELECT_GROUP' => ($this->auth->acl_get('a_authgroups'))
					? true
					: false,
				'S_HIDDEN_FIELDS'    => $s_hidden_fields,
			]);

			// Let the template names being displayed
			if (count($template_id))
			{
				$sql = 'SELECT template_name
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE ' . $this->db->sql_in_set('template_id', $template_id) . '
					ORDER BY left_id ASC';
				$result = $this->db->sql_query($sql);

				$template_names = [];
				while ($row = $this->db->sql_fetchrow($result))
				{
					$template_names[] = $row['template_name'];
				}
				$this->db->sql_freeresult($result);

				$this->template->assign_vars([
					'S_TEMPLATE_NAMES' => (count($template_names))
						? true
						: false,
					'TEMPLATE_NAMES'   => implode($this->language->lang('COMMA_SEPARATOR'), $template_names),
				]);
			}

			return;
		}

		// Setting permissions screen
		$s_hidden_fields = build_hidden_fields([
			'user_id'     => $user_id,
			'group_id'    => $group_id,
			'template_id' => $template_id,
			'type'        => $permission_type,
		]);

		// Do not allow template_ids being set and no other setting defined (will bog down the server too much)
		if (count($template_id) && !count($user_id) && !count($group_id))
		{
			trigger_error($this->language->lang('ONLY_FORUM_DEFINED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$this->template->assign_vars([
			'L_PERMISSION_TYPE' => $this->permissions->get_type_lang($permission_type),

			'U_ACTION'        => $this->u_action,
			'S_HIDDEN_FIELDS' => $s_hidden_fields,
		]);

		if (strpos($mode, 'setting_') === 0)
		{
			$this->template->assign_vars([
				'S_SETTING_PERMISSIONS' => true,
			]);

			$hold_ary = $this->auth_admin_helper->get_mask('set', (
					(count($user_id))
					? $user_id
					: false
				), ((count($group_id))
					? $group_id
					: false
				), ((count($template_id))
					? $template_id
					: false
				), $permission_type, $permission_scope, ACL_NO
			);
			$this->auth_admin_helper->display_mask('set', $permission_type, $hold_ary, (
					(count($user_id))
					? 'user'
					: 'group'
				), (($permission_scope == 'local')
					? true
					: false
				)
			);
		}
		else
		{
			$this->template->assign_vars([
				'S_VIEWING_PERMISSIONS' => true,
			]);

			$hold_ary = $this->auth_admin_helper->get_mask('view', (
					(count($user_id))
					? $user_id
					: false
				), ((count($group_id))
					? $group_id
					: false
				), ((count($template_id))
					? $template_id
					: false
				), $permission_type, $permission_scope, ACL_NEVER);
				$this->auth_admin_helper->display_mask('view', $permission_type, $hold_ary, (
					(count($user_id))
					? 'user'
					: 'group'
				), (($permission_scope == 'local')
					? true
					: false
				)
			);
		}
	}

	/**
	 * Apply permissions
	 */
	function set_permissions($mode, $permission_type, $auth_admin, &$user_id, &$group_id)
	{
		if (!function_exists('check_assigned_role'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_permissions.' . $this->phpEx);
		}

		$acp_permissions = new \acp_permissions();

		$psubmit = $this->request->variable('psubmit', [0 => [0 => 0]]);

		// User or group to be set?
		$ug_type = (count($user_id))
			? 'user'
			: 'group';

		// Check the permission setting again
		if (!$this->auth->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$this->auth->acl_get('a_auth' . $ug_type . 's'))
		{
			send_status_line(403, 'Forbidden');
			trigger_error($this->language->lang('NO_AUTH_OPERATION') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// We loop through the auth settings defined in our submit
		$ug_id = key($psubmit);
		$template_id = key($psubmit[$ug_id]);

		$settings = $this->request->variable('setting', [0 => [0 => ['' => 0]]], false, \phpbb\request\request_interface::POST);
		if (empty($settings) || empty($settings[$ug_id]) || empty($settings[$ug_id][$template_id]))
		{
			trigger_error('WRONG_PERMISSION_SETTING_FORMAT', E_USER_WARNING);
		}

		$auth_settings = $settings[$ug_id][$template_id];

		// Do we have a role we want to set?
		$roles = $this->request->variable('role', [0 => [0 => 0]], false, \phpbb\request\request_interface::POST);
		$assigned_role = (isset($roles[$ug_id][$template_id]))
			? (int) $roles[$ug_id][$template_id]
			: 0;

		// Do the admin want to set these permissions to other items too?
		$inherit = $this->request->variable('inherit', [0 => [0]]);

		$ug_id = [$ug_id];
		$template_id = [$template_id];

		if (count($inherit))
		{
			foreach ($inherit as $_ug_id => $template_id_ary)
			{
				// Inherit users/groups?
				if (!in_array($_ug_id, $ug_id))
				{
					$ug_id[] = $_ug_id;
				}

				// Inherit templates?
				$template_id = array_merge($template_id, array_keys($template_id_ary));
			}
		}

		$template_id = array_unique($template_id);

		// If the auth settings differ from the assigned role, then do not set a role...
		if ($assigned_role)
		{
			if (!$acp_permissions->check_assigned_role($assigned_role, $auth_settings))
			{
				$assigned_role = 0;
			}
		}

		// Update the permission set...
		$this->auth_admin_helper->acl_set($ug_type, $template_id, $ug_id, $auth_settings, $assigned_role);

		// Do we need to recache the moderator lists?
		if ($permission_type == 'm_')
		{
			phpbb_cache_moderators($this->db, $this->cache, $this->auth);
		}

		// Remove users who are now moderators or admins from everyones foes list
		if ($permission_type == 'm_' || $permission_type == 'a_')
		{
			phpbb_update_foes($this->db, $this->auth, $group_id, $user_id);
		}

		$this->log_action($mode, 'add', $permission_type, $ug_type, $ug_id, $template_id);

		meta_refresh(5, $this->u_action);
		trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($this->u_action));
	}

	/**
	 * Apply all permissions
	 */
	function set_all_permissions($mode, $permission_type, $auth_admin, &$user_id, &$group_id)
	{
		if (!function_exists('check_assigned_role'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_permissions.' . $this->phpEx);
		}
		$acp_permissions = new \acp_permissions();

		if (!class_exists('auth_admin'))
		{
			include($this->phpbb_root_path . 'includes/acp/auth.' . $this->phpEx);
		}

		$auth_admin = new \auth_admin();

		// User or group to be set?
		$ug_type = (count($user_id))
			? 'user'
			: 'group';

		// Check the permission setting again
		if (!$this->auth->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$this->auth->acl_get('a_auth' . $ug_type . 's'))
		{
			send_status_line(403, 'Forbidden');
			trigger_error($this->language->lang('NO_AUTH_OPERATION') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$auth_settings = $this->request->variable('setting', [0 => [0 => ['' => 0]]], false, \phpbb\request\request_interface::POST);
		$auth_roles = $this->request->variable('role', [0 => [0 => 0]], false, \phpbb\request\request_interface::POST);
		$ug_ids = $template_ids = [];

		// We need to go through the auth settings
		foreach ($auth_settings as $ug_id => $template_auth_row)
		{
			$ug_id = (int) $ug_id;
			$ug_ids[] = $ug_id;

			foreach ($template_auth_row as $template_id => $auth_options)
			{
				$template_id = (int) $template_id;
				$template_ids[] = $template_id;

				// Check role...
				$assigned_role = (isset($auth_roles[$ug_id][$template_id]))
					? (int) $auth_roles[$ug_id][$template_id]
					: 0;

				// If the auth settings differ from the assigned role, then do not set a role...
				if ($assigned_role)
				{
					if (!$acp_permissions->check_assigned_role($assigned_role, $auth_options))
					{
						$assigned_role = 0;
					}
				}

				// Update the permission set...
				$this->auth_admin_helper->acl_set($ug_type, $template_id, $ug_id, $auth_options, $assigned_role, false);
			}
		}

		$auth_admin->acl_clear_prefetch();

		// Do we need to recache the moderator lists?
		if ($permission_type == 'm_')
		{
			phpbb_cache_moderators($this->db, $this->cache, $this->auth);
		}

		// Remove users who are now moderators or admins from everyones foes list
		if ($permission_type == 'm_' || $permission_type == 'a_')
		{
			phpbb_update_foes($this->db, $this->auth, $group_id, $user_id);
		}

		$this->log_action($mode, 'add', $permission_type, $ug_type, $ug_ids, $template_ids);

		if ($mode == 'setting_template_local' || $mode == 'setting_mod_local')
		{
			meta_refresh(5, $this->u_action . '&amp;template_id[]=' . implode('&amp;template_id[]=', $template_ids));
			trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($this->u_action . '&amp;template_id[]=' . implode('&amp;template_id[]=', $template_ids)));
		}
		else
		{
			meta_refresh(5, $this->u_action);
			trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($this->u_action));
		}
	}

	/**
	 * Remove permissions
	 */
	function remove_permissions($mode, $permission_type, $auth_admin, &$user_id, &$group_id, &$template_id)
	{
		// User or group to be set?
		$ug_type = (count($user_id))
			? 'user'
			: 'group';

		// Check the permission setting again
		if (!$this->auth->acl_get('a_' . str_replace('_', '', $permission_type) . 'auth') || !$this->auth->acl_get('a_auth' . $ug_type . 's'))
		{
			send_status_line(403, 'Forbidden');
			trigger_error($this->language->lang('NO_AUTH_OPERATION') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$auth_admin->acl_delete($ug_type, (
				($ug_type == 'user')
				? $user_id
				: $group_id
			), (count($template_id)
				? $template_id
				: false
			), $permission_type
		);

		// Do we need to recache the moderator lists?
		if ($permission_type == 'm_')
		{
			phpbb_cache_moderators($this->db, $this->cache, $this->auth);
		}

		$this->log_action($mode, 'del', $permission_type, $ug_type, (
				($ug_type == 'user')
				? $user_id
				: $group_id
			), (count($template_id)
				? $template_id
				: [0 => 0]
			)
		);

		if ($mode == 'setting_template_local' || $mode == 'setting_mod_local')
		{
			meta_refresh(5, $this->u_action . '&amp;template_id[]=' . implode('&amp;template_id[]=', $template_id));
			trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($this->u_action . '&amp;template_id[]=' . implode('&amp;template_id[]=', $template_id)));
		}
		else
		{
			meta_refresh(5, $this->u_action);
			trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($this->u_action));
		}
	}

	/**
	 * Log permission changes
	 */
	function log_action($mode, $action, $permission_type, $ug_type, $ug_id, $template_id)
	{
		if (!is_array($ug_id))
		{
			$ug_id = [$ug_id];
		}

		if (!is_array($template_id))
		{
			$template_id = [$template_id];
		}

		// Logging ... first grab user or groupnames ...
		$sql = ($ug_type == 'group')
			? 'SELECT group_name as name, group_type FROM ' . GROUPS_TABLE . ' WHERE '
			: 'SELECT username as name FROM ' . USERS_TABLE . ' WHERE ';
		$sql .= $this->db->sql_in_set(($ug_type == 'group')
			? 'group_id'
			: 'user_id', array_map('intval', $ug_id)
		);
		$result = $this->db->sql_query($sql);

		$l_ug_list = '';
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_name = $this->group_helper->get_name($row['name']);
			$l_ug_list .= ((($l_ug_list != '')
					? ', '
					: ''
				) . ((isset($row['group_type']) && $row['group_type'] == GROUP_SPECIAL)
					? '<span class="sep">' . $group_name . '</span>'
					: $group_name
				)
			);
		}
		$this->db->sql_freeresult($result);

		$mode = str_replace('setting_', '', $mode);

		if ($template_id[0] == 0)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACL_' . strtoupper($action) . '_' . strtoupper($mode) . '_' . strtoupper($permission_type), false, [$l_ug_list]);
		}
		else
		{
			// Grab the template details if non-zero template_id
			$sql = 'SELECT template_name
				FROM ' . PFT_TEMPLATES_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_id);
			$result = $this->db->sql_query($sql);

			$l_template_list = '';
			while ($row = $this->db->sql_fetchrow($result))
			{
				$l_template_list .= (($l_template_list != '')
					? ', '
					: ''
				) . $row['template_name'];
			}
			$this->db->sql_freeresult($result);

			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACL_' . strtoupper($action) . '_' . strtoupper($mode) . '_' . strtoupper($permission_type), false, [$l_template_list, $l_ug_list]);
		}
	}

	/**
	 * Display a complete trace tree for the selected permission to determine where settings are set/unset
	 */
	function permission_trace($user_id, $template_id, $permission)
	{
		if ($user_id != $this->user->data['user_id'])
		{
			$userdata = $this->auth->obtain_user_data($user_id);
		}
		else
		{
			$userdata = $this->user->data;
		}

		if (!$userdata)
		{
			trigger_error('NO_USERS', E_USER_ERROR);
		}
		$template_name = false;

		if ($template_id)
		{
			$sql = 'SELECT template_name
				FROM ' . PFT_TEMPLATES_TABLE . "
				WHERE template_id = $template_id";
			$result = $this->db->sql_query($sql, 3600);
			$template_name = $this->db->sql_fetchfield('template_name');
			$this->db->sql_freeresult($result);
		}

		$back = $this->request->variable('back', 0);

		$this->template->assign_vars([
			'PERMISSION'          => $this->permissions->get_permission_lang($permission),
			'PERMISSION_USERNAME' => $userdata['username'],
			'template_name'       => $template_name,

			'S_GLOBAL_TRACE' => ($template_id)
				? false
				: true,

			'U_BACK' => ($back)
				? build_url(['f', 'back']) . "&amp;f=$back"
				: '',
		]);

		$this->template->assign_block_vars('trace', [
			'WHO'         => $this->language->lang('DEFAULT'),
			'INFORMATION' => $this->language->lang('TRACE_DEFAULT'),

			'S_SETTING_NO' => true,
			'S_TOTAL_NO'   => true,
		]);

		$sql = 'SELECT DISTINCT g.group_name, g.group_id, g.group_type
			FROM ' . GROUPS_TABLE . ' g
				LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.group_id = g.group_id)
			WHERE ug.user_id = ' . $user_id . '
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
			ORDER BY g.group_type DESC, g.group_id DESC';
		$result = $this->db->sql_query($sql);

		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[$row['group_id']] = [
				'auth_setting' => ACL_NO,
				'group_name'   => $this->group_helper->get_name($row['group_name']),
			];
		}
		$this->db->sql_freeresult($result);

		$total = ACL_NO;
		$add_key = (($template_id)
			? '_LOCAL'
			: '');

		if (count($groups))
		{
			// Get group auth settings
			$hold_ary = $this->auth_admin_helper->acl_group_raw_data(array_keys($groups), $permission, $template_id);

			foreach ($hold_ary as $group_id => $template_ary)
			{
				$groups[$group_id]['auth_setting'] = $template_ary[$template_id][$permission];
			}
			unset($hold_ary);

			foreach ($groups as $row)
			{
				switch ($row['auth_setting'])
				{
					case ACL_NO:
						$information = $this->language->lang('TRACE_GROUP_NO' . $add_key);
					break;

					case ACL_YES:
						$information = (($total == ACL_YES)
							? $this->language->lang('TRACE_GROUP_YES_TOTAL_YES' . $add_key)
							: (($total == ACL_NEVER)
								? $this->language->lang('TRACE_GROUP_YES_TOTAL_NEVER' . $add_key)
								: $this->language->lang('TRACE_GROUP_YES_TOTAL_NO' . $add_key)
							)
						);
						$total = ($total == ACL_NO)
							? ACL_YES
							: $total;
					break;

					case ACL_NEVER:
						$information = (($total == ACL_YES)
							? $this->language->lang('TRACE_GROUP_NEVER_TOTAL_YES' . $add_key)
							: (($total == ACL_NEVER)
								? $this->language->lang('TRACE_GROUP_NEVER_TOTAL_NEVER' . $add_key)
								: $this->language->lang('TRACE_GROUP_NEVER_TOTAL_NO' . $add_key)
							)
						);
						$total = ACL_NEVER;
					break;
				}

				$this->template->assign_block_vars('trace', [
					'WHO'         => $row['group_name'],
					'INFORMATION' => $information,

					'S_SETTING_NO'    => ($row['auth_setting'] == ACL_NO)
						? true
						: false,
					'S_SETTING_YES'   => ($row['auth_setting'] == ACL_YES)
						? true
						: false,
					'S_SETTING_NEVER' => ($row['auth_setting'] == ACL_NEVER)
						? true
						: false,
					'S_TOTAL_NO'      => ($total == ACL_NO)
						? true
						: false,
					'S_TOTAL_YES'     => ($total == ACL_YES)
						? true
						: false,
					'S_TOTAL_NEVER'   => ($total == ACL_NEVER)
						? true
						: false,
				]);
			}
		}

		// Get user specific permission... globally or for this template_
		$hold_ary = $this->auth_admin_helper->acl_user_raw_data($user_id, $permission, $template_id);
		$auth_setting = (!count($hold_ary))
			? ACL_NO
			: $hold_ary[$user_id][$template_id][$permission];

		switch ($auth_setting)
		{
			case ACL_NO:
				$information = ($total == ACL_NO)
					? $this->language->lang('TRACE_USER_NO_TOTAL_NO' . $add_key)
					: $this->language->lang('TRACE_USER_KEPT' . $add_key);
				$total = ($total == ACL_NO)
					? ACL_NEVER
					: $total;
			break;

			case ACL_YES:
				$information = (($total == ACL_YES)
					? $this->language->lang('TRACE_USER_YES_TOTAL_YES' . $add_key)
					: (($total == ACL_NEVER)
						? $this->language->lang('TRACE_USER_YES_TOTAL_NEVER' . $add_key)
						: $this->language->lang('TRACE_USER_YES_TOTAL_NO' . $add_key)
					)
				);
				$total = ($total == ACL_NO)
					? ACL_YES
					: $total;
			break;

			case ACL_NEVER:
				$information = (($total == ACL_YES)
					? $this->language->lang('TRACE_USER_NEVER_TOTAL_YES' . $add_key)
					: (($total == ACL_NEVER)
						? $this->language->lang('TRACE_USER_NEVER_TOTAL_NEVER' . $add_key)
						: $this->language->lang('TRACE_USER_NEVER_TOTAL_NO' . $add_key)
					)
				);
				$total = ACL_NEVER;
			break;
		}

		$this->template->assign_block_vars('trace', [
			'WHO'         => $userdata['username'],
			'INFORMATION' => $information,

			'S_SETTING_NO'    => ($auth_setting == ACL_NO)
				? true
				: false,
			'S_SETTING_YES'   => ($auth_setting == ACL_YES)
				? true
				: false,
			'S_SETTING_NEVER' => ($auth_setting == ACL_NEVER)
				? true
				: false,
			'S_TOTAL_NO'      => false,
			'S_TOTAL_YES'     => ($total == ACL_YES)
				? true
				: false,
			'S_TOTAL_NEVER'   => ($total == ACL_NEVER)
				? true
				: false,
		]);

		if ($template_id != 0 && isset($this->auth->acl_options['global'][$permission]))
		{
			if ($user_id != $this->user->data['user_id'])
			{
				$auth2 = new \phpbb\auth\auth();
				$this->auth_admin_helper->acl($userdata, $auth2);
				$auth_setting = $auth2->acl_get($permission);
			}
			else
			{
				$auth_setting = $this->auth->acl_get($permission);
			}

			if ($auth_setting)
			{
				$information = ($total == ACL_YES)
					? $this->language->lang('TRACE_USER_GLOBAL_YES_TOTAL_YES')
					: $this->language->lang('TRACE_USER_GLOBAL_YES_TOTAL_NEVER');
				$total = ACL_YES;
			}
			else
			{
				$information = $this->language->lang('TRACE_USER_GLOBAL_NEVER_TOTAL_KEPT');
			}

			// If there is no auth information we do not need to worry the user by showing non-relevant data.
			if ($auth_setting)
			{
				$this->template->assign_block_vars('trace', [
					'WHO'         => sprintf($this->language->lang('TRACE_GLOBAL_SETTING'), $userdata['username']),
					'INFORMATION' => sprintf($information, '<a href="' . $this->u_action . "&amp;u=$user_id&amp;f=0&amp;auth=$permission&amp;back=$template_id\">", '</a>'),

					'S_SETTING_NO'    => false,
					'S_SETTING_YES'   => $auth_setting,
					'S_SETTING_NEVER' => !$auth_setting,
					'S_TOTAL_NO'      => false,
					'S_TOTAL_YES'     => ($total == ACL_YES)
						? true
						: false,
					'S_TOTAL_NEVER'   => ($total == ACL_NEVER)
						? true
						: false,
				]);
			}
		}

		// Take founder status into account, overwriting the default values
		if ($userdata['user_type'] == USER_FOUNDER && strpos($permission, 'a_') === 0)
		{
			$this->template->assign_block_vars('trace', [
				'WHO'         => $userdata['username'],
				'INFORMATION' => $this->language->lang('TRACE_USER_FOUNDER'),

				'S_SETTING_NO'    => ($auth_setting == ACL_NO)
					? true
					: false,
				'S_SETTING_YES'   => ($auth_setting == ACL_YES)
					? true
					: false,
				'S_SETTING_NEVER' => ($auth_setting == ACL_NEVER)
					? true
					: false,
				'S_TOTAL_NO'      => false,
				'S_TOTAL_YES'     => true,
				'S_TOTAL_NEVER'   => false,
			]);

			$total = ACL_YES;
		}

		// Total value...
		$this->template->assign_vars([
			'S_RESULT_NO'    => ($total == ACL_NO)
				? true
				: false,
			'S_RESULT_YES'   => ($total == ACL_YES)
				? true
				: false,
			'S_RESULT_NEVER' => ($total == ACL_NEVER)
				? true
				: false,
		]);
	}
}
