<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class manage_templates_module
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
	/** @var \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper */
	protected $auth_admin_helper;
	/** @var \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper */
	protected $manage_templates_helper;
	/** @var \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper */
	protected $template_permissions_helper;
	/** @var \toxyy\postformtemplates\modules\template_entries $template_entries */
	protected $template_entries;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpbb_admin_path;
	/** @var string */
	protected $phpEx;

	public $u_action;
	public $tpl_name;
	public $page_title;
	private $parent_id;

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
		/** @var \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper */
		$this->auth_admin_helper = $this->phpbb_container->get('toxyy.postformtemplates.auth_admin_helper');
		/** @var \toxyy\postformtemplates\controller\manage_templates_helper $manage_templates_helper */
		$this->manage_templates_helper = $this->phpbb_container->get('toxyy.postformtemplates.manage_templates_helper');
		/** @var \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper */
		$this->template_permissions_helper = $this->phpbb_container->get('toxyy.postformtemplates.template_permissions_helper');
		/** @var \toxyy\postformtemplates\modules\template_entries $template_entries */
		$this->template_entries = $this->phpbb_container->get('toxyy.postformtemplates.modules.template_entries');
		/** @var string */
		$this->phpbb_root_path = $this->phpbb_container->getParameter('core.root_path');
		/** @var string */
		$this->phpbb_admin_path = $this->phpbb_root_path . $this->phpbb_container->getParameter('core.adm_relative_path');
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
		$this->tpl_name = 'acp_manage_templates';
		$this->page_title = $this->language->lang('ACP_PFT_MANAGE_TEMPLATES_TITLE');

		$form_key = 'acp_pfm_templates';
		add_form_key($form_key);

		$update = (isset($_POST['update']))
			? true
			: false;
		$action =           $this->request->variable('action', '');
		$template_id =      $this->request->variable('t', 0);
		$this->parent_id =  $this->request->variable('parent_id', 0);

		$template_data = $errors = [];
		if ($update && !check_form_key($form_key))
		{
			$update = false;
			$errors[] = $this->language->lang('FORM_INVALID');
		}

		// Check additional permissions
		switch ($action)
		{
			case 'delete':
				if (!$this->auth->acl_get('a_pft_templatedel'))
				{
					trigger_error($this->language->lang('NO_PERMISSION_TEMPLATE_DELETE') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
			break;

			case 'add':
				if (!$this->auth->acl_get('a_pft_templateadd'))
				{
					trigger_error($this->language->lang('NO_PERMISSION_TEMPLATE_ADD') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
			break;
		}

		// Major routines
		if ($update)
		{
			switch ($action)
			{
				case 'delete':
					$action_subtemplates    = $this->request->variable('action_subtemplates', '');
					$subtemplates_to_id     = $this->request->variable('subtemplates_to_id', 0);
					$action_entries         = $this->request->variable('action_entries', '');
					$entries_to_id          = $this->request->variable('entries_to_id', 0);

					$errors = $this->manage_templates_helper->delete_template($template_id, $action_entries, $action_subtemplates, $entries_to_id, $subtemplates_to_id);

					if (count($errors))
					{
						break;
					}

					$this->auth->acl_clear_prefetch();
					$this->cache->destroy('sql', PFT_TEMPLATES_TABLE);

					trigger_error($this->language->lang('ACP_PFT_TEMPLATE_DELETED') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));

				break;

				case 'edit':
					$template_data = [
						'template_id' => $template_id,
					];

				// No break here

				case 'add':

					$template_data += [
						'parent_id'              => $this->request->variable('template_parent_id', $this->parent_id),
						'forum_id'               => $this->request->variable('forum_id', [0]),
						'all_forums'             => $this->request->variable('all_forums', 0),
						'template_type'          => $this->request->variable('template_type', TEMPLATE_FORM),
						'type_action'            => $this->request->variable('type_action', ''),
						'template_status'        => $this->request->variable('template_status', ITEM_UNLOCKED),
						'template_parents'       => '',
						'template_name'          => $this->request->variable('template_name', '', true),
						'template_desc'          => $this->request->variable('template_desc', '', true),
						'template_desc_uid'      => '',
						'template_desc_options'  => 7,
						'template_desc_bitfield' => '',
						'template_images'        => $this->request->variable('template_images', [0]),
						'all_images'             => $this->request->variable('all_images', 0),
						'template_image_type'    => $this->request->variable('image_date_type', 0),
						'template_image_date'    => '',
						'image_day'              => $this->request->variable('image_day', 0),
						'image_month'            => $this->request->variable('image_month', 0),
						'image_year'             => $this->request->variable('image_year', 0),
					];

					// Linked templates and categories are not able to be locked...
					if ($template_data['template_type'] == TEMPLATE_CAT)
					{
						$template_data['template_status'] = ITEM_UNLOCKED;
					}

					// Get data for template description if specified
					if ($template_data['template_desc'])
					{
						generate_text_for_storage($template_data['template_desc'], $template_data['template_desc_uid'], $template_data['template_desc_bitfield'], $template_data['template_desc_options'], $this->request->variable('desc_parse_bbcode', false), $this->request->variable('desc_parse_urls', false), $this->request->variable('desc_parse_smilies', false));
					}

					$settings_from = $this->request->variable('settings_from', 0);
					$copied_template = false;
					// Copy template settings?
					if ($settings_from)
					{
						if ($action != 'edit' || ($this->auth->acl_get('a_pftauth') && $this->auth->acl_get('a_authusers') && $this->auth->acl_get('a_authgroups') && $this->auth->acl_get('a_mauth')))
						{
							$copy_parent_id = $this->request->variable('template_copy_parent_id', $this->parent_id);
							$copy_parent_id = ($copy_parent_id != $this->parent_id)
								? 'copy'
								: $this->parent_id;
							$errors = $this->manage_templates_helper->copy_template_settings($settings_from, $this->u_action, $copy_parent_id, $template_data);
							$copied_template = true;
						}
					}
					else
					{
						$errors = $this->manage_templates_helper->update_template_data($template_data, $this->u_action, $this->parent_id);
					}

					if (!count($errors))
					{
						if ($copied_template)
						{
							$template_permissions_from 	= $settings_from;
							$template_entries_from 		= $settings_from;
							$template_keep_entries 		= false;
							$template_contents_from 	= $settings_from;
							$template_keep_contents 	= false;
							$template_forums_from 		= $settings_from;
							$template_images_from 		= $settings_from;
							$template_keep_cycle 		= false;
						}
						else
						{
							$template_permissions_from 	= $this->request->variable('template_permissions_from', 0);
							$template_entries_from 		= $this->request->variable('template_entries_from', 0);
							$template_keep_entries 		= $this->request->variable('template_keep_entries', 0);
							$template_contents_from 	= $this->request->variable('template_contents_from', 0);
							$template_keep_contents 	= $this->request->variable('template_keep_contents', 0);
							$template_forums_from 		= $this->request->variable('template_forums_from', 0);
							$template_images_from 		= $this->request->variable('template_images_from', 0);
							$template_keep_cycle 		= $this->request->variable('template_keep_image_cycle', 0);
						}
						$this->cache->destroy('sql', PFT_TEMPLATES_TABLE);

						$copied_permissions = false;
						// Copy permissions?
						if ($template_permissions_from && $template_permissions_from != $template_data['template_id'] &&
							($action != 'edit' || empty($template_id) || ($this->auth->acl_get('a_pftauth') && $this->auth->acl_get('a_authusers') && $this->auth->acl_get('a_authgroups') && $this->auth->acl_get('a_mauth'))))
						{
							$this->template_permissions_helper->copy_template_permissions($template_permissions_from, $template_data['template_id'], (
									($action == 'edit')
									? true
									: false
								), !$copied_template
							);
							phpbb_cache_moderators($this->db, $this->cache, $this->auth);
							$copied_permissions = true;
						}

						$copied_entries = false;
						// Copy entries?
						if ($template_entries_from && $template_entries_from != $template_data['template_id'] &&
							($action != 'edit' || empty($template_id) || $this->auth->acl_get('a_pftauth')))
						{
							$this->manage_templates_helper->copy_template_entries($template_entries_from, $template_data['template_id'], (
									($template_keep_entries)
									? false
									: true
								), !$copied_template
							);
							$copied_entries = true;
						}

						$copied_contents = false;
						// Copy template contents?
						if ($template_contents_from && $template_contents_from != $template_data['template_id'] &&
							($action != 'edit' || empty($template_id) || $this->auth->acl_get('a_pftauth')))
						{
							$this->manage_templates_helper->copy_template_contents($template_contents_from, $template_data['template_id'], (
									($template_keep_contents)
									? false
									: true
								), !$copied_template, $this->u_action
							);
							$copied_contents = true;
						}

						$copied_display_forums = false;
						// Copy display forums?
						if ($template_forums_from && $template_forums_from != $template_data['template_id'] &&
							($action != 'edit' || empty($template_id) || $this->auth->acl_get('a_pftauth')))
						{
							$this->manage_templates_helper->copy_template_display_forums($template_forums_from, $template_data['template_id'], (
									($action == 'edit')
									? true
									: false
								), !$copied_template
							);
							$copied_display_forums = true;
						}

						$copied_images = false;
						// Copy template images?
						if ($template_images_from && $template_images_from != $template_data['template_id'] &&
							($action != 'edit' || empty($template_id) || $this->auth->acl_get('a_pftauth')))
						{
							$this->manage_templates_helper->copy_template_images($template_images_from, $template_data['template_id'], (
									($template_keep_cycle)
									? false
									: true
								), !$copied_template
							);
							$copied_images = true;
						}

						$this->auth->acl_clear_prefetch();

						$acl_url = '&amp;mode=setting_template_local&amp;template_id[]=' . $template_data['template_id'];

						$message = ($action == 'add')
							? $this->language->lang('ACP_PFT_TEMPLATE_CREATED')
							: $this->language->lang('ACP_PFT_TEMPLATE_UPDATED');

						// redirect directly to permission settings screen if authed
						if ($action == 'add' && (!$copied_template || !$copied_permissions || !$copied_entries || !$copied_display_forums || !$copied_images || !$copied_contents) && $this->auth->acl_get('a_pftauth'))
						{
							$message .= '<br /><br />' . sprintf($this->language->lang('ACP_PFT_REDIRECT_ACL'), '<a href="' . append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", 'i=-toxyy-postformtemplates-acp-template_permissions_module' . $acl_url) . '">', '</a>');

							meta_refresh(4, append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", 'i=-toxyy-postformtemplates-acp-template_permissions_module' . $acl_url));
						}

						trigger_error($message . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
					}
				break;
			}
		}

		switch ($action)
		{
			case 'move_up':
			case 'move_down':
				if (!$template_id)
				{
					trigger_error($this->language->lang('ACP_PFT_NO_TEMPLATE') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}

				$sql = 'SELECT *
					FROM ' . PFT_TEMPLATES_TABLE . "
					WHERE template_id = $template_id";
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->language->lang('ACP_PFT_NO_TEMPLATE') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}

				$move_template_name = $this->manage_templates_helper->move_template_by($row, $action, 1);

				if ($move_template_name !== false)
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_' . strtoupper($action), false, [$row['template_name'], $move_template_name]);
					$this->cache->destroy('sql', PFT_TEMPLATES_TABLE);
				}

				if ($this->request->is_ajax())
				{
					$json_response = new \phpbb\json_response;
					$json_response->send(['success' => ($move_template_name !== false)]);
				}
			break;

			case 'add':
			case 'edit':
				// Initialise $row, so we always have it in the event
				$row = [];

				// Show form to create/modify a template
				if ($action == 'edit')
				{
					$this->page_title = 'ACP_PFT_TEMPLATE_EDIT_TITLE';
					$row = $this->manage_templates_helper->get_template_info($template_id);
					$old_template_type = $row['template_type'];

					if (!$update)
					{
						$template_data = $row;
					}
					else
					{
						$template_data['left_id'] = $row['left_id'];
						$template_data['right_id'] = $row['right_id'];
					}

					// Make sure no direct child templates are able to be selected as parents.
					$exclude_templates = [];
					foreach ($this->manage_templates_helper->get_template_branch($template_id, 'children') as $row)
					{
						$exclude_templates[] = $row['template_id'];
					}

					// only allow 2 levels of cat
					$parent_id = 0;
					if ($this->parent_id)
					{
						$sql = 'SELECT parent_id
							FROM ' . PFT_TEMPLATES_TABLE . '
							WHERE template_id = ' . $this->parent_id;
						$result = $this->db->sql_query($sql);
						$parent_id = $this->db->sql_fetchfield('parent_id');
						$this->db->sql_freeresult($result);

						if (!$parent_id && $this->parent_id)
						{
							$sql = 'SELECT parent_id
								FROM ' . PFT_TEMPLATES_TABLE . '
								WHERE template_id = ' . $this->parent_id;
							$result = $this->db->sql_query($sql);
							$row = $this->db->sql_fetchrow();
							$this->db->sql_freeresult($result);
							foreach ($row as $exclude_template_id)
							{
								$exclude_templates[] = $exclude_template_id;
							}
						}
					}
					else
					{
						$sql = 'SELECT template_id
							FROM ' . PFT_TEMPLATES_TABLE . '
							WHERE parent_id = ' . $template_id . '
							AND template_type = ' . TEMPLATE_CAT;
						$result = $this->db->sql_query_limit($sql, 1);
						$has_cats = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						// is a main category, and has categories itself
						if (!empty($has_cats))
						{
							$sql = 'SELECT template_id
								FROM ' . PFT_TEMPLATES_TABLE . '
								WHERE template_type = ' . TEMPLATE_CAT;
							$result = $this->db->sql_query($sql);
							while ($row = $this->db->sql_fetchrow())
							{
								$exclude_templates[] = $row['template_id'];
							}
							$this->db->sql_freeresult($result);
						}
					}

					$parents_list = $this->manage_templates_helper->make_template_select($template_data['parent_id'], $exclude_templates, false, false, false, false, false, true);
				}
				else
				{
					$this->page_title = 'ACP_PFT_CREATE_TEMPLATE';

					$template_id = $this->parent_id;
					$parents_list = $this->manage_templates_helper->make_template_select($this->parent_id, false, false, false, false, false, false, true);

					// Fill template data with default values
					if (!$update)
					{
						$template_data = [
							'parent_id'       => $this->parent_id,
							'template_type'   => $this->parent_id
								? TEMPLATE_FORM
								: TEMPLATE_CAT,
							'template_status' => ITEM_UNLOCKED,
							'template_name'   => $this->request->variable('template_name', '', true),
							'template_desc'   => '',
							'template_images' => '',
						];
					}
				}

				$template_desc_data = [
					'text'          => $template_data['template_desc'],
					'allow_bbcode'  => true,
					'allow_smilies' => true,
					'allow_urls'    => true,
				];

				// Parse desciption if specified
				if ($template_data['template_desc'])
				{
					if (!isset($template_data['template_desc_uid']))
					{
						// Before we are able to display the preview and plane text, we need to parse our $this->request->variable()'d value...
						$template_data['template_desc_uid'] = '';
						$template_data['template_desc_bitfield'] = '';
						$template_data['template_desc_options'] = 0;

						generate_text_for_storage($template_data['template_desc'], $template_data['template_desc_uid'], $template_data['template_desc_bitfield'], $template_data['template_desc_options'], $this->request->variable('desc_allow_bbcode', false), $this->request->variable('desc_allow_urls', false), $this->request->variable('desc_allow_smilies', false));
					}

					// decode...
					$template_desc_data = generate_text_for_edit($template_data['template_desc'], $template_data['template_desc_uid'], $template_data['template_desc_options']);
				}

				$template_type_options = '';

				// only allow 2 levels of cat
				$parent_id = 0;
				if ($this->parent_id)
				{
					$sql = 'SELECT parent_id
						FROM ' . PFT_TEMPLATES_TABLE . '
						WHERE template_id = ' . $this->parent_id;
					$result = $this->db->sql_query($sql);
					$parent_id = $this->db->sql_fetchfield('parent_id');
					$this->db->sql_freeresult($result);
				}

				$template_type_ary = ($parent_id
					? [TEMPLATE_FORM => 'FORM']
					: ($this->parent_id
						? [TEMPLATE_CAT => 'CAT', TEMPLATE_FORM => 'FORM']
						: [TEMPLATE_CAT => 'CAT']
					)
				);

				foreach ($template_type_ary as $value => $lang)
				{
					$template_type_options .= '<option value="' . $value . '"' . (
						($value == $template_data['template_type'])
						? ' selected="selected"'
						: ''
					) . '>' . $this->language->lang('ACP_PFT_TYPE_' . $lang) . '</option>';
				}

				$statuslist = '<option value="' . ITEM_UNLOCKED . '"' . (
					($template_data['template_status'] == ITEM_UNLOCKED)
					? ' selected="selected"'
					: ''
				) . '>' . $this->language->lang('ACP_PFT_UNLOCKED') . '</option><option value="' . ITEM_LOCKED . '"' . (
					($template_data['template_status'] == ITEM_LOCKED)
					? ' selected="selected"'
					: ''
				) . '>' . $this->language->lang('ACP_PFT_LOCKED') . '</option>';

				$sql = 'SELECT template_id
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_type = ' . TEMPLATE_FORM . "
						AND template_id <> $template_id";
				$result = $this->db->sql_query_limit($sql, 1);

				$usable_form_exists = false;
				if ($this->db->sql_fetchrow($result))
				{
					$usable_form_exists = true;
				}
				$this->db->sql_freeresult($result);

				// Subtemplate move options
				if ($action == 'edit' && $template_data['template_type'] == TEMPLATE_CAT)
				{
					$forms_id = [];
					$forms = $this->manage_templates_helper->get_template_branch($template_id, 'children');

					foreach ($forms as $row)
					{
						$forms_id[] = $row['template_id'];
					}

					$templates_list = $this->manage_templates_helper->make_template_select($template_data['parent_id'], $forms_id);

					if ($usable_form_exists)
					{
						$this->template->assign_vars([
							'S_MOVE_TEMPLATE_OPTIONS' => $this->manage_templates_helper->make_template_select($template_data['parent_id'], $forms_id, false, false, false, false, false, true) // , false, true, false???
						]);
					}

					$this->template->assign_vars([
						'S_HAS_FORMS'      => ($template_data['right_id'] - $template_data['left_id'] > 1)
							? true
							: false,
						'S_TEMPLATES_LIST' => $templates_list,
					]);
				}
				else if ($usable_form_exists)
				{
					$this->template->assign_vars([
						'S_MOVE_TEMPLATE_OPTIONS' => $this->manage_templates_helper->make_template_select($template_data['parent_id'], $template_id, false, true, false),
					]);
				}
				if ($action == 'edit' && $template_data['template_type'] == TEMPLATE_FORM)
				{
					$sql = 'SELECT entry_id
						FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
						WHERE template_id = $template_id";
					$result = $this->db->sql_query_limit($sql, 1);

					$has_entries = false;
					if ($this->db->sql_fetchrow($result))
					{
						$has_entries = true;
					}

					$this->template->assign_vars([
						'S_HAS_ENTRIES' => $has_entries,
					]);
				}
				if ($action != 'add')
				{
					$sql = 'SELECT forum_id
						FROM ' . PFT_TEMPLATE_FORUMS_TABLE . "
						WHERE template_id = {$template_data['template_id']}";
					$result = $this->db->sql_query($sql);

					$forum_ids = [];
					while ($row = $this->db->sql_fetchrow($result))
					{
						$forum_ids[] = (int) $row['forum_id'];
					}
					$this->db->sql_freeresult($result);
				}

				$select_id = ($action == 'add')
					? $template_data['parent_id']
					: false;
				$ignore_id = ($action == 'edit')
					? $template_data['template_id']
					: false;

				$options_no_select_ignore_ids[] = $ignore_id;
				if (!$parent_id && $this->parent_id && $template_data['template_type'] == TEMPLATE_CAT)
				{
					// is not a main category... don't allow copying main categories to keep cat depth at 2
					$sql = 'SELECT template_id
						FROM ' . PFT_TEMPLATES_TABLE . '
						WHERE parent_id = 0';
					$result = $this->db->sql_query($sql);
					while ($row = $this->db->sql_fetchrow())
					{
						$options_no_select_ignore_ids[] = $row['template_id'];
					}
					$this->db->sql_freeresult($result);
				}

				$template_images = [];
				$date_data['image_day'] = $date_data['image_month'] = $date_data['image_year'] = 0;
				if (!empty($template_data['template_images']) || $template_data['template_images'] == 0)
				{
					$template_images = ($template_data['template_images'] == 0)
						? [0]
						: unserialize($template_data['template_images']);
				}
				list($date_data['image_day'], $date_data['image_month'], $date_data['image_year']) = explode('-', $template_data['template_image_date']);

				$template_options_no_select = $this->manage_templates_helper->make_template_select(false, $options_no_select_ignore_ids, false, false, false);

				$s_day_options = '<option value="0"' . (
					(!$date_data['image_day'])
					? ' selected="selected"'
					: ''
				) . '>--</option>';
				for ($i = 1; $i < 32; $i++)
				{
					$selected = ($i == $date_data['image_day'])
						? ' selected="selected"'
						: '';
					$s_day_options .= "<option value=\"$i\"$selected>$i</option>";
				}

				$s_month_options = '<option value="0"' . (
					(!$date_data['image_month'])
					? ' selected="selected"'
					: ''
				) . '>--</option>';
				for ($i = 1; $i < 13; $i++)
				{
					$selected = ($i == $date_data['image_month'])
						? ' selected="selected"'
						: '';
					$s_month_options .= "<option value=\"$i\"$selected>$i</option>";
				}

				$now = getdate();
				$s_year_options = '<option value="0"' . (
					(!$date_data['image_year'])
					? ' selected="selected"'
					: ''
				) . '>--</option>';
				for ($i = $now['year'] - 100; $i <= $now['year']; $i++)
				{
					$selected = ($i == $date_data['image_year'])
						? ' selected="selected"'
						: '';
					$s_year_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				unset($now);

				$template_data = [
					'S_EDIT_TEMPLATE'      => true,
					'S_ERROR'              => (count($errors))
						? true
						: false,
					'S_PARENT_ID'          => $this->parent_id,
					'S_TEMPLATE_PARENT_ID' => $template_data['parent_id'],
					'S_ADD_ACTION'         => ($action == 'add')
						? true
						: false,

					'U_BACK'        => $this->u_action . '&amp;parent_id=' . $this->parent_id,
					'U_EDIT_ACTION' => $this->u_action . "&amp;parent_id={$this->parent_id}&amp;action=$action&amp;t=$template_id",

					'L_COPY_TEMPLATE_EXPLAIN'    => $this->language->lang('ACP_PFT_COPY_TEMPLATE_' . strtoupper($action) . '_EXPLAIN'),
					'L_COPY_PERMISSIONS_EXPLAIN' => $this->language->lang('ACP_PFT_COPY_PERMISSIONS_' . strtoupper($action) . '_EXPLAIN'),
					'L_COPY_ENTRIES_EXPLAIN'     => $this->language->lang('ACP_PFT_COPY_ENTRIES_' . strtoupper($action) . '_EXPLAIN'),
					'L_COPY_CONTENTS_EXPLAIN'    => $this->language->lang('ACP_PFT_COPY_CONTENTS_' . strtoupper($action) . '_EXPLAIN'),
					'L_COPY_FORUMS_EXPLAIN'      => $this->language->lang('ACP_PFT_COPY_FORUMS_' . strtoupper($action) . '_EXPLAIN'),
					'L_COPY_IMAGES_EXPLAIN'      => $this->language->lang('ACP_PFT_COPY_IMAGES_' . strtoupper($action) . '_EXPLAIN'),
					'L_TITLE'                    => $this->language->lang($this->page_title),
					'ERROR_MSG'                  => (count($errors))
						? implode('<br />', $errors)
						: '',

					'TEMPLATE_NAME' => $template_data['template_name'],
					'TEMPLATE_FORM' => TEMPLATE_FORM,
					'TEMPLATE_CAT'  => TEMPLATE_CAT,

					'TEMPLATE_DESC'          => $template_desc_data['text'],
					'S_DESC_BBCODE_CHECKED'  => ($template_desc_data['allow_bbcode'])
						? true
						: false,
					'S_DESC_SMILIES_CHECKED' => ($template_desc_data['allow_smilies'])
						? true
						: false,
					'S_DESC_URLS_CHECKED'    => ($template_desc_data['allow_urls'])
						? true
						: false,

					'S_DAY_OPTIONS'   => $s_day_options,
					'S_MONTH_OPTIONS' => $s_month_options,
					'S_YEAR_OPTIONS'  => $s_year_options,

					'S_TEMPLATE_TYPE_OPTIONS'      => $template_type_options,
					'S_STATUS_OPTIONS'             => $statuslist,
					'S_PARENT_OPTIONS'             => $parents_list,
					'S_FORUM_OPTIONS'              => make_forum_select(
						($action == 'add')
						? false
						: ($action == 'edit'
							? $forum_ids
							: false
						), false, false, false, false
					),
					'S_FORUM_ALL'                  => !empty($forum_ids)
						? $forum_ids[0] == 0
						: false,
					'S_IMAGES_OPTIONS'             => $this->manage_templates_helper->make_template_images_select(
						($action == 'add')
						? false
						: ($action == 'edit'
							? $template_images
							: false
						), false, false, false, false
					),
					'S_IMAGES_ALL'                 => !empty($template_images)
						? $template_images[0] == 0
						: false,
					'S_IMAGE_TYPE'                 => isset($template_data['template_image_type'])
						? $template_data['template_image_type']
						: 0,
					'S_CAT_OR_FORM'                => !$parent_id && $this->parent_id,
					'S_ENTRY_OPTIONS'              => $this->manage_templates_helper->make_template_select(false, $ignore_id, false, true),
					'S_TEMPLATE_OPTIONS_NO_SELECT' => $template_options_no_select,
					'S_TEMPLATE_FORMS_NO_SELECT'   => $this->manage_templates_helper->make_template_select(false, $ignore_id, false, true, false),
					'S_TEMPLATE_CATS_NO_SELECT'    => $this->manage_templates_helper->make_template_select(false, $ignore_id, false, false, false, false, false, true),
					'S_TEMPLATE_OPTIONS'           => $this->manage_templates_helper->make_template_select($select_id, $ignore_id, false, false, false),
					'S_TEMPLATE_FORM'              => ($template_data['template_type'] == TEMPLATE_FORM)
						? true
						: false,
					'S_TEMPLATE_ORIG_FORM'         => (isset($old_template_type) && $old_template_type == TEMPLATE_FORM)
						? true
						: false,
					'S_TEMPLATE_ORIG_CAT'          => (isset($old_template_type) && $old_template_type == TEMPLATE_CAT)
						? true
						: false,
					'S_TEMPLATE_CAT'               => ($template_data['template_type'] == TEMPLATE_CAT)
						? true
						: false,
					'S_CAN_COPY_PERMISSIONS'       => ($action != 'edit' || empty($template_id) || ($this->auth->acl_get('a_pftauth') && $this->auth->acl_get('a_authusers') && $this->auth->acl_get('a_authgroups') && $this->auth->acl_get('a_mauth')))
						? true
						: false,
				];

				$this->template->assign_vars($template_data);

				return;
			break;

			case 'delete':
				if (!$template_id)
				{
					trigger_error($this->language->lang('ACP_PFT_NO_TEMPLATE') . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}

				$template_data = $this->manage_templates_helper->get_template_info($template_id);

				$forms_id = [];
				$forms = $this->manage_templates_helper->get_template_branch($template_id, 'children');

				foreach ($forms as $row)
				{
					$forms_id[] = $row['template_id'];
				}

				$templates_list = $this->manage_templates_helper->make_template_select($template_data['parent_id'], $forms_id);

				$sql = 'SELECT template_id
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_type = ' . TEMPLATE_FORM . "
						AND template_id <> $template_id";
				$result = $this->db->sql_query_limit($sql, 1);

				$move_exclude_ids = $forms_id;
				if ($this->db->sql_fetchrow($result))
				{
					if ($template_data['template_type'] == TEMPLATE_CAT)
					{
						$sql = 'SELECT template_id
							FROM ' . PFT_TEMPLATES_TABLE . '
							WHERE parent_id = ' . $template_id . '
							AND template_type = ' . TEMPLATE_CAT;
						$result2 = $this->db->sql_query_limit($sql, 1);
						$has_cats = $this->db->sql_fetchrow($result2);
						$this->db->sql_freeresult($result2);

						// is a main category, and has categories itself
						if (!empty($has_cats))
						{
							$sql = 'SELECT template_id
								FROM ' . PFT_TEMPLATES_TABLE . '
								WHERE parent_id != 0';
							$result2 = $this->db->sql_query($sql);
							while ($row = $this->db->sql_fetchrow())
							{
								$move_exclude_ids[] = $row['template_id'];
							}
							$this->db->sql_freeresult($result2);
						}
						$this->template->assign_vars([
							'S_MOVE_TEMPLATE_OPTIONS' => $this->manage_templates_helper->make_template_select($template_data['parent_id'], $move_exclude_ids, false, false, false, false, false, true) // , false, true, false???
						]);
					}
					else
					{
						$this->template->assign_vars([
							'S_MOVE_TEMPLATE_OPTIONS' => $this->manage_templates_helper->make_template_select($template_data['parent_id'], $forms_id, false, true) // , false, true, false???
						]);
					}

				}
				$this->db->sql_freeresult($result);

				$parent_id = ($this->parent_id == $template_id)
					? 0
					: $this->parent_id;

				$has_forms = ($template_data['right_id'] - $template_data['left_id'] > 1)
					? true
					: false;
				$this->template->assign_vars([
					'S_DELETE_TEMPLATE' => true,
					'U_ACTION'          => $this->u_action . "&amp;parent_id={$parent_id}&amp;action=delete&amp;t=$template_id",
					'U_BACK'            => $this->u_action . '&amp;parent_id=' . $this->parent_id,

					'TEMPLATE_NAME'    => $template_data['template_name'],
					'S_TEMPLATE_FORM'  => ($template_data['template_type'] == TEMPLATE_FORM)
						? true
						: false,
					'S_HAS_FORMS'      => $has_forms,
					'S_TEMPLATES_LIST' => $has_forms
						? $this->manage_templates_helper->make_template_select($template_data['parent_id'], $forms_id, false, false, false, false, false, true)
						: $templates_list,
					'S_ERROR'          => (count($errors))
						? true
						: false,
					'ERROR_MSG'        => (count($errors))
						? implode('<br />', $errors)
						: '',
				]);

				return;
			break;

			case 'copy_perm':
				$template_permissions_from = $this->request->variable('template_permissions_from', 0);

				// Copy permissions?
				if (!empty($template_permissions_from) && $template_permissions_from != $template_id)
				{
					$this->template_permissions_helper->copy_template_permissions($template_permissions_from, $template_id, true);
					phpbb_cache_moderators($this->db, $this->cache, $this->auth);
					$this->auth->acl_clear_prefetch();
					$this->cache->destroy('sql', PFT_TEMPLATES_TABLE);

					$acl_url = '&amp;mode=setting_template_local&amp;template_id[]=' . $template_id;

					$message = $this->language->lang('TEMPLATE_UPDATED');

					// Redirect to permissions
					if ($this->auth->acl_get('a_pftauth'))
					{
						$message .= '<br /><br />' . sprintf($this->language->lang('REDIRECT_ACL'), '<a href="' . append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", 'i=-toxyy-postformtemplates-acp-template_permissions_module' . $acl_url) . '">', '</a>');
					}

					trigger_error($message . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
				}
			break;
		}

		// Default management page
		if (!$this->parent_id)
		{
			$navigation = $this->language->lang('ACP_PFT_TEMPLATE_INDEX');
		}
		else
		{
			$navigation = '<a href="' . $this->u_action . '">' . $this->language->lang('ACP_PFT_TEMPLATE_INDEX') . '</a>';

			$templates_nav = $this->manage_templates_helper->get_template_branch($this->parent_id, 'parents', 'descending');
			foreach ($templates_nav as $row)
			{
				if ($row['template_id'] == $this->parent_id)
				{
					$navigation .= ' -&gt; ' . $row['template_name'];
				}
				else
				{
					$navigation .= ' -&gt; <a href="' . $this->u_action . '&amp;parent_id=' . $row['template_id'] . '">' . $row['template_name'] . '</a>';
				}
			}
		}

		// Jumpbox
		$template_box = $this->manage_templates_helper->make_template_select($this->parent_id, false, false, false, false); //make_template_select($this->parent_id);

		$sql = 'SELECT *
			FROM ' . PFT_TEMPLATES_TABLE . "
			WHERE parent_id = $this->parent_id
			ORDER BY left_id";
		$result = $this->db->sql_query($sql);

		$rowset = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[(int) $row['template_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		if (!empty($rowset))
		{
			foreach ($rowset as $row)
			{
				$template_type = $row['template_type'];

				if ($row['template_status'] == ITEM_LOCKED)
				{
					$folder_image = '<img src="images/icon_folder_lock.gif" alt="' . $this->language->lang('LOCKED') . '" />';
				}
				else
				{
					switch ($template_type)
					{
						case(TEMPLATE_FORM):
							$folder_image = '<img src="images/icon_folder_link.gif" alt="' . $this->language->lang('ACP_PFT_TYPE_FORM') . '" />';
						break;
						default:
							$folder_image = ($row['left_id'] + 1 != $row['right_id'])
								? '<img src="images/icon_subfolder.gif" alt="' . $this->language->lang('ACP_PFT_TYPE_CAT_NONEMPTY') . '" />'
								: '<img src="images/icon_folder.gif" alt="' . $this->language->lang('ACP_PFT_TYPE_CAT') . '" />';
						break;
					}
				}

				$url = $this->u_action . "&amp;parent_id={$this->parent_id}&amp;t={$row['template_id']}";

				$this->template->assign_block_vars('templates', [
					'FOLDER_IMAGE'         => $folder_image,
					'TEMPLATE_IMAGE'       => ($row['template_images'])
						? '<img src="' . $this->phpbb_root_path . $row['template_images'] . '" alt="" />'
						: '',
					'TEMPLATE_IMAGE_SRC'   => ($row['template_images'])
						? $this->phpbb_root_path . $row['template_images']
						: '',
					'TEMPLATE_NAME'        => $row['template_name'],
					'TEMPLATE_DESCRIPTION' => generate_text_for_display($row['template_desc'], $row['template_desc_uid'], $row['template_desc_bitfield'], $row['template_desc_options']),

					'S_TEMPLATE_FORM' => ($template_type == TEMPLATE_FORM)
						? true
						: false,

					'U_TEMPLATE'  => $this->u_action . '&amp;parent_id=' . $row['template_id'],
					'U_MOVE_UP'   => $url . '&amp;action=move_up',
					'U_MOVE_DOWN' => $url . '&amp;action=move_down',
					'U_EDIT'      => $url . '&amp;action=edit',
					'U_DELETE'    => $url . '&amp;action=delete',
				]);
			}
		}
		else if ($this->parent_id)
		{
			$row = $this->manage_templates_helper->get_template_info($this->parent_id);

			$url = $this->u_action . '&amp;parent_id=' . $this->parent_id . '&amp;t=' . $row['template_id'];

			$this->template->assign_vars([
				'S_NO_TEMPLATES'  => true,
				'S_TEMPLATE_FORM' => ($row['template_type'] == TEMPLATE_FORM)
					? true
					: false,

				'U_EDIT'   => $url . '&amp;action=edit',
				'U_DELETE' => $url . '&amp;action=delete',
			]);
		}

		$this->template->assign_vars([
			'ERROR_MSG'    => (count($errors))
				? implode('<br />', $errors)
				: '',
			'NAVIGATION'   => $navigation,
			'TEMPLATE_BOX' => $template_box,
			'U_SEL_ACTION' => $this->u_action,
			'U_ACTION'     => $this->u_action . '&amp;parent_id=' . $this->parent_id,
		]);

		// checking again to avoid resetting template vars
		if ($this->parent_id)
		{
			$this->template_entries->main('-toxyy-postformtemplates-acp-manage_templates_module', 'manage_templates');
		}
		unset($rowset);
	}
}
