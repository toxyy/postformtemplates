<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\controller;

class manage_templates_helper
{
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
	/** @var \phpbb\log\log $phpbb_log */
	protected $phpbb_log;
	/** @var \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper */
	protected $template_permissions_helper;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpEx;
	/** @var string */
	protected $pft_templates_table;
	/** @var string */
	protected $pft_template_forums_table;
	/** @var string */
	protected $pft_template_entries_table;
	/** @var string */
	protected $pft_template_images_table;
	/** @var string */
	protected $template_form;
	/** @var string */
	protected $template_cat;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface                            $cache
	 * @param \phpbb\db\driver\driver_interface                               $db
	 * @param \phpbb\request\request                                          $request
	 * @param \phpbb\user                                                     $user
	 * @param \phpbb\auth\auth                                                $auth
	 * @param \phpbb\language\language                                        $language
	 * @param \phpbb\template\template                                        $template
	 * @param \phpbb\log\log                                                  $phpbb_log
	 * @param \toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper
	 * @param string                                                          $phpbb_root_path             phpBB root path
	 * @param string                                                          $phpEx                       PHP file extension
	 * @param string \toxyy\postformtemplates\                                $pft_templates_table
	 * @param string \toxyy\postformtemplates\                                $pft_template_forums_table
	 * @param string \toxyy\postformtemplates\                                $pft_template_entries_table
	 * @param string \toxyy\postformtemplates\                                $pft_template_images_table
	 * @param string \toxyy\postformtemplates\                                $template_form
	 * @param string \toxyy\postformtemplates\                                $template_cat
	 */
	public function __construct(
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\log\log $phpbb_log,
		\toxyy\postformtemplates\controller\template_permissions_helper $template_permissions_helper,
		$phpbb_root_path,
		$phpEx,
		$pft_templates_table,
		$pft_template_forums_table,
		$pft_template_entries_table,
		$pft_template_images_table,
		$template_form,
		$template_cat
	)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
		$this->auth = $auth;
		$this->language = $language;
		$this->template = $template;
		$this->phpbb_log = $phpbb_log;
		$this->template_permissions_helper = $template_permissions_helper;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->pft_templates_table = $pft_templates_table;
		$this->pft_template_forums_table = $pft_template_forums_table;
		$this->pft_template_entries_table = $pft_template_entries_table;
		$this->pft_template_images_table = $pft_template_images_table;
		$this->template_form = $template_form;
		$this->template_cat = $template_cat;

		if (!defined('PFT_TEMPLATES_TABLE'))
		{
			define('PFT_TEMPLATES_TABLE', $this->pft_templates_table);
		}
		if (!defined('PFT_TEMPLATE_FORUMS_TABLE'))
		{
			define('PFT_TEMPLATE_FORUMS_TABLE', $this->pft_template_forums_table);
		}
		if (!defined('PFT_TEMPLATE_ENTRIES_TABLE'))
		{
			define('PFT_TEMPLATE_ENTRIES_TABLE', $this->pft_template_entries_table);
		}
		if (!defined('PFT_TEMPLATE_IMAGES_TABLE'))
		{
			define('PFT_TEMPLATE_IMAGES_TABLE', $this->pft_template_images_table);
		}
		if (!defined('TEMPLATE_FORM'))
		{
			define('TEMPLATE_FORM', $this->template_form);
		}
		if (!defined('TEMPLATE_CAT'))
		{
			define('TEMPLATE_CAT', $this->template_cat);
		}
		if (!function_exists('validate_data'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->phpEx);
		}
	}

	/**
	 * Get template details
	 */
	function get_template_info($template_id)
	{
		$sql = 'SELECT *
			FROM ' . PFT_TEMPLATES_TABLE . "
			WHERE template_id = $template_id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error("Template #$template_id does not exist", E_USER_ERROR);
		}

		return $row;
	}

	/**
	 * Move template position by $steps up/down
	 */
	function move_template_by($template_row, $action = 'move_up', $steps = 1)
	{
		/**
		 * Fetch all the siblings between the module's current spot
		 * and where we want to move it to. If there are less than $steps
		 * siblings between the current spot and the target then the
		 * module will move as far as possible
		 */
		$sql = 'SELECT template_id, template_name, left_id, right_id
			FROM ' . PFT_TEMPLATES_TABLE . "
			WHERE parent_id = {$template_row['parent_id']}
				AND " . (($action == 'move_up') ? "right_id < {$template_row['right_id']} ORDER BY right_id DESC" : "left_id > {$template_row['left_id']} ORDER BY left_id ASC");
		$result = $this->db->sql_query_limit($sql, $steps);

		$target = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$target = $row;
		}
		$this->db->sql_freeresult($result);

		if (!count($target))
		{
			// The template is already on top or bottom
			return false;
		}

		/**
		 * $left_id and $right_id define the scope of the nodes that are affected by the move.
		 * $diff_up and $diff_down are the values to substract or add to each node's left_id
		 * and right_id in order to move them up or down.
		 * $move_up_left and $move_up_right define the scope of the nodes that are moving
		 * up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
		 */
		if ($action == 'move_up')
		{
			$left_id = $target['left_id'];
			$right_id = $template_row['right_id'];

			$diff_up = $template_row['left_id'] - $target['left_id'];
			$diff_down = $template_row['right_id'] + 1 - $template_row['left_id'];

			$move_up_left = $template_row['left_id'];
			$move_up_right = $template_row['right_id'];
		}
		else
		{
			$left_id = $template_row['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $template_row['right_id'] + 1 - $template_row['left_id'];
			$diff_down = $target['right_id'] - $template_row['right_id'];

			$move_up_left = $template_row['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		// Now do the dirty job
		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			template_parents = ''
			WHERE
				left_id BETWEEN {$left_id} AND {$right_id}
				AND right_id BETWEEN {$left_id} AND {$right_id}";
		$this->db->sql_query($sql);

		return $target['template_name'];
	}

	/**
	 * Get template branch
	 * Copy of get_forum_branch from
	 * https://github.com/phpbb/phpbb/blob/prep-release-3.3.10/phpBB/includes/functions_admin.php#L294
	 */
	function get_template_branch($template_id, $type = 'all', $order = 'descending', $include_template = true)
	{
		switch ($type)
		{
			case 'parents':
				$condition = 't1.left_id BETWEEN t2.left_id AND t2.right_id';
			break;

			case 'children':
				$condition = 't2.left_id BETWEEN t1.left_id AND t1.right_id';
			break;

			default:
				$condition = 't2.left_id BETWEEN t1.left_id AND t1.right_id OR t1.left_id BETWEEN t2.left_id AND t2.right_id';
			break;
		}

		$rows = [];

		$sql = 'SELECT t2.*
            FROM ' . PFT_TEMPLATES_TABLE . ' t1
            LEFT JOIN ' . PFT_TEMPLATES_TABLE . " t2 ON ($condition)
            WHERE t1.template_id = $template_id
            ORDER BY t2.template_id " . (($order == 'descending') ? 'ASC' : 'DESC');
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$include_template && $row['template_id'] == $template_id)
			{
				continue;
			}

			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Simple version of jumpbox, just lists authed forums
	 * Copy of make_forum_select from
	 * https://github.com/phpbb/phpbb/blob/prep-release-3.3.10/phpBB/includes/functions_admin.php#L66
	 */
	function make_template_select($select_id = false, $ignore_id = false, $ignore_acl = false, $ignore_nonform = false, $ignore_emptycat = true, $only_acl_post = false, $return_array = false, $ignore_noncat = false)
	{
		// This query is identical to the jumpbox one
		$sql = 'SELECT template_id, template_name, parent_id, template_type, left_id, right_id
            FROM ' . PFT_TEMPLATES_TABLE . '
            ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, 600);

		$rowset = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[(int) $row['template_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		$right = 0;
		$padding_store = ['0' => ''];
		$padding = '';
		$template_list = ($return_array) ? [] : '';

		// Sometimes it could happen that templates will be displayed here not be displayed within the index page
		// This is the result of templates not displayed at index, having list permissions and a parent of a template with no permissions.
		// If this happens, the padding could be "broken"

		foreach ($rowset as $row)
		{
			if ($row['left_id'] < $right)
			{
				$padding .= '&nbsp; &nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			}
			else if ($row['left_id'] > $right + 1)
			{
				$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];
			$disabled = false;

			if (!$ignore_acl && $this->auth->acl_gets(['pft_view', 'a_pft_template', 'a_pft_templateadd', 'a_pft_templatedel'], $row['template_id']))
			{
				if ($only_acl_post && !$this->auth->acl_get('pft_use', $row['template_id']))
				{
					$disabled = true;
				}
			}
			else if (!$ignore_acl)
			{
				continue;
			}

			if (
				((is_array($ignore_id) && in_array($row['template_id'], $ignore_id)) || $row['template_id'] == $ignore_id)
				||
				// Non-postable template with no forms, don't display
				($row['template_type'] == TEMPLATE_CAT && ($row['left_id'] + 1 == $row['right_id']) && $ignore_emptycat)
				||
				($row['template_type'] != TEMPLATE_FORM && $ignore_nonform)
				||
				($row['template_type'] == TEMPLATE_FORM && $ignore_noncat)
			)
			{
				$disabled = true;
			}

			if ($return_array)
			{
				// Include some more information...
				$selected = (is_array($select_id)) ? ((in_array($row['template_id'], $select_id)) ? true : false) : (($row['template_id'] == $select_id) ? true : false);
				$template_list[$row['template_id']] = array_merge(['padding' => $padding, 'selected' => ($selected && !$disabled), 'disabled' => $disabled], $row);
			}
			else
			{
				$selected = (is_array($select_id)) ? ((in_array($row['template_id'], $select_id)) ? ' selected="selected"' : '') : (($row['template_id'] == $select_id) ? ' selected="selected"' : '');
				$template_list .= '<option value="' . $row['template_id'] . '"' . (($disabled) ? ' disabled="disabled" class="disabled-option"' : $selected) . '>' . $padding . $row['template_name'] . '</option>';
			}
		}
		unset($padding_store, $rowset);

		return $template_list;
	}

	/**
	 * Simple version of jumpbox, just lists authed forums
	 * Copy of make_forum_select from
	 * https://github.com/phpbb/phpbb/blob/prep-release-3.3.10/phpBB/includes/functions_admin.php#L66
	 */
	function make_template_images_select($select_id = false, $ignore_id = false, $ignore_acl = false, $only_acl_post = false, $return_array = false)
	{
		// This query is identical to the jumpbox one
		$sql = 'SELECT image_id, image_url
            FROM ' . PFT_TEMPLATE_IMAGES_TABLE . '
            ORDER BY image_order ASC';
		$result = $this->db->sql_query($sql, 1000);

		$rowset = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[(int) $row['image_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		$image_list = ($return_array) ? [] : '';

		// Sometimes it could happen that templates will be displayed here not be displayed within the index page
		// This is the result of templates not displayed at index, having list permissions and a parent of a template with no permissions.
		// If this happens, the padding could be "broken"

		foreach ($rowset as $row)
		{
			$disabled = false;

			if (!$ignore_acl && !$this->auth->acl_get('a_pft_images'))
			{
				if ($only_acl_post)
				{
					$disabled = true;
				}
			}
			else if (!$this->auth->acl_get('a_pft_images'))
			{
				continue;
			}

			if ((is_array($ignore_id) && in_array($row['image_id'], $ignore_id)) || $row['image_id'] == $ignore_id)
			{
				$disabled = true;
			}

			if ($return_array)
			{
				// Include some more information...
				$selected = (is_array($select_id)) ? ((in_array($row['image_id'], $select_id)) ? true : false) : (($row['image_id'] == $select_id) ? true : false);
				$image_list[$row['image_id']] = array_merge(['selected' => ($selected && !$disabled), 'disabled' => $disabled], $row);
			}
			else
			{
				$selected = (is_array($select_id)) ? ((in_array($row['image_id'], $select_id)) ? ' selected="selected"' : '') : (($row['image_id'] == $select_id) ? ' selected="selected"' : '');
				$image_list .= '<option value="' . $row['image_id'] . '"' . (($disabled) ? ' disabled="disabled" class="disabled-option"' : $selected) . '>' . $row['image_url'] . '</option>';
			}
		}
		unset($rowset);

		return $image_list;
	}

	/**
	 * Update template data
	 * Copy of update_form_data from
	 * https://github.com/phpbb/phpbb/blob/prep-release-3.3.10/phpBB/includes/acp/acp_forums.php#L966
	 */
	function update_template_data(&$template_data_ary, $u_action, $parent_id, $add_log = true)
	{
		$errors = [];

		$template_forum_ids = $template_data_ary['forum_id'];
		unset($template_data_ary['forum_id']);

		if ($template_data_ary['all_forums'])
		{
			$template_forum_ids = [0];
		}
		unset($template_data_ary['all_forums']);

		if ($template_data_ary['all_images'])
		{
			$template_data_ary['template_images'] = 0;
		}
		else if (empty($template_data_ary['template_images']))
		{
			$template_data_ary['template_images'] = '';
		}
		else
		{
			$template_data_ary['template_images'] = serialize($template_data_ary['template_images']);
		}
		unset($template_data_ary['all_images']);

		if ($template_data_ary['template_name'] == '')
		{
			$errors[] = $this->language->lang('TEMPLATE_NAME_EMPTY');
		}

		if (!isset($template_data_ary['image_day']))
		{
			$template_data_ary['image_day'] = 0;
		}
		if (!isset($template_data_ary['image_month']))
		{
			$template_data_ary['image_month'] = 0;
		}
		if (!isset($template_data_ary['image_year']))
		{
			$template_data_ary['image_year'] = 0;
		}

		$date_data = [
			'image_day'   => $template_data_ary['image_day'],
			'image_month' => $template_data_ary['image_month'],
			'image_year'  => $template_data_ary['image_year'],
		];
		unset($template_data_ary['image_day'], $template_data_ary['image_month'], $template_data_ary['image_year']);

		$date_data += [
			'template_image_date' => sprintf('%2d-%2d-%4d', $date_data['image_day'], $date_data['image_month'], $date_data['image_year']),
		];

		$validate_array = [
			'image_day'           => ['num', true, 1, 31],
			'image_month'         => ['num', true, 1, 12],
			'image_year'          => ['num', true, 1901, gmdate('Y', time()) + 50],
			'template_image_date' => ['date', true],
		];

		$date_errors = validate_data($date_data, $validate_array);
		if (!empty($date_errors))
		{
			$errors[] = $this->language->lang('ACP_PFT_DATE_INVALID');
		}

		/**
		 * Replace Emojis and other 4bit UTF-8 chars not allowed by MySql to UCR / NCR.
		 * Using their Numeric Character Reference's Hexadecimal notation.
		 */
		$template_data_ary['template_name'] = utf8_encode_ucr($template_data_ary['template_name']);

		/**
		 * This should never happen again.
		 * Leaving the fallback here just in case there will be the need of it.
		 */
		if (preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $template_data_ary['template_name'], $matches))
		{
			$character_list = implode('<br>', $matches[0]);

			$errors[] = $this->language->lang('TEMPLATE_NAME_EMOJI', $character_list);
		}

		if (utf8_strlen($template_data_ary['template_desc']) > 4000)
		{
			$errors[] = $this->language->lang('TEMPLATE_DESC_TOO_LONG');
		}

		/*if (!empty($template_data_ary['template_images']) && !file_exists($this->phpbb_root_path . $template_data_ary['template_images']))
		{
			$errors[] = $this->language->lang('TEMPLATE_IMAGE_NO_EXIST');
		}*/

		// Unset data that are not database fields
		$template_data_sql = $template_data_ary;

		// What are we going to do tonight Brain? The same thing we do everynight,
		// try to take over the world ... or decide whether to continue update
		// and if so, whether it's a new form/cat or an existing one
		if (count($errors))
		{
			return $errors;
		}

		$template_data_sql['template_image_date'] = $date_data['template_image_date'];
		$is_new_template = !isset($template_data_sql['template_id']);

		if ($is_new_template)
		{
			// no template_id means we're creating a new template
			unset($template_data_sql['type_action']);

			if ($template_data_sql['parent_id'])
			{
				$sql = 'SELECT left_id, right_id, template_type
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_id = ' . $template_data_sql['parent_id'];
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->language->lang('PARENT_NOT_EXIST') . adm_back_link($u_action . '&amp;parent_id=' . $parent_id), E_USER_WARNING);
				}

				$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . '
					SET left_id = left_id + 2, right_id = right_id + 2
					WHERE left_id > ' . $row['right_id'];
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . '
					SET right_id = right_id + 2
					WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$this->db->sql_query($sql);

				$template_data_sql['left_id'] = $row['right_id'];
				$template_data_sql['right_id'] = $row['right_id'] + 1;
			}
			else
			{
				$sql = 'SELECT MAX(right_id) AS right_id
					FROM ' . PFT_TEMPLATES_TABLE;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$template_data_sql['left_id'] = $row['right_id'] + 1;
				$template_data_sql['right_id'] = $row['right_id'] + 2;
			}

			$sql = 'INSERT INTO ' . PFT_TEMPLATES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $template_data_sql);
			$this->db->sql_query($sql);

			$template_data_ary['template_id'] = $this->db->sql_nextid();

			if ($add_log)
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_ADD', false, [$template_data_ary['template_name']]);
			}
		}
		else
		{
			$row = $this->get_template_info($template_data_sql['template_id']);

			if ($row['template_type'] == TEMPLATE_FORM && $row['template_type'] != $template_data_sql['template_type'])
			{
				// we're turning a form into a category
				if ($template_data_sql['type_action'] == 'move')
				{
					$to_template_id = $this->request->variable('to_template_id', 0);

					if ($to_template_id)
					{
						$errors = $this->move_template_content($template_data_sql['template_id'], $to_template_id);
					}
					else
					{
						return [$this->language->lang('ACP_PFT_NO_DESTINATION_TEMPLATE')];
					}
				}
				else if ($template_data_sql['type_action'] == 'delete')
				{
					$errors = $this->delete_template_content($template_data_sql['template_id']);
				}
				else if ($template_data_sql['type_action'] != 'nothing')
				{
					return [$this->language->lang('ACP_PFT_NO_TEMPLATE_ACTION')];
				}
			}
			else if ($row['template_type'] == TEMPLATE_CAT && $row['template_type'] != $template_data_sql['template_type'])
			{
				// we're turning a category into a form
				if ($template_data_sql['type_action'] == 'move')
				{
					$to_template_id = $this->request->variable('to_template_id', 0);

					if ($to_template_id)
					{
						$errors = $this->move_template_content($template_data_sql['template_id'], $to_template_id, true);
					}
					else
					{
						return [$this->language->lang('ACP_PFT_NO_DESTINATION_TEMPLATE')];
					}
				}
				else if ($template_data_sql['type_action'] == 'delete')
				{
					$sql = 'SELECT template_id
						FROM ' . PFT_TEMPLATES_TABLE . "
						WHERE parent_id = {$template_data_sql['template_id']}";
					$result = $this->db->sql_query($sql);

					while ($delete_row = $this->db->sql_fetchrow($result))
					{
						$errors = $this->delete_template($delete_row['template_id']);
					}
					$this->db->sql_freeresult($result);
				}
				else if ($template_data_sql['type_action'] != 'nothing')
				{
					return [$this->language->lang('ACP_PFT_NO_TEMPLATE_ACTION')];
				}
			}

			if (count($errors))
			{
				return $errors;
			}

			if ($row['parent_id'] != $template_data_sql['parent_id'])
			{
				if ($row['template_id'] != $template_data_sql['parent_id'])
				{
					$errors = $this->move_template($template_data_sql['template_id'], $template_data_sql['parent_id']);
				}
				else
				{
					$template_data_sql['parent_id'] = $row['parent_id'];
				}
			}

			if (count($errors))
			{
				return $errors;
			}

			unset($template_data_sql['type_action']);

			if ($row['template_name'] != $template_data_sql['template_name'])
			{
				// the template name has changed, clear the parents list of all templates (for safety)
				$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
					SET template_parents = ''";
				$this->db->sql_query($sql);
			}

			// Setting the template id to the template id is not really received well by some dbs. ;)
			$template_id = $template_data_sql['template_id'];
			unset($template_data_sql['template_id']);

			$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . '
				SET ' . $this->db->sql_build_array('UPDATE', $template_data_sql) . '
				WHERE template_id = ' . $template_id;
			$this->db->sql_query($sql);

			// Add it back
			$template_data_ary['template_id'] = $template_id;

			$delete_forum_data_sql = (empty($template_forum_ids)) ? '' : ($template_forum_ids[0] == 0 ? '' : 'AND forum_id NOT IN (' . implode($template_forum_ids) . ')');
			$sql = 'DELETE FROM ' . PFT_TEMPLATE_FORUMS_TABLE . "
				WHERE template_id = $template_id
				$delete_forum_data_sql";
			$this->db->sql_query($sql);

			if ($add_log)
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_EDIT', false, [$template_data_ary['template_name']]);
			}
		}

		if (!empty($template_forum_ids))
		{
			$forum_ids = [];
			foreach ($template_forum_ids as $forum_id)
			{
				$forum_ids[] = "({$template_data_ary['template_id']}, $forum_id)";
			}

			$forum_data_sql = implode(', ', $forum_ids);

			$sql = 'INSERT INTO ' . PFT_TEMPLATE_FORUMS_TABLE . ' (template_id, forum_id)
				VALUES ' . $forum_data_sql;
			$this->db->sql_query($sql);
		}

		return $errors;
	}

	/**
	 * Move template
	 */
	function move_template($from_id, $to_id)
	{
		$errors = [];

		// Check if we want to move to a parent with link type
		if ($to_id > 0)
		{
			$to_data = $this->get_template_info($to_id);
		}

		// Return if there were errors
		if (!empty($errors))
		{
			return $errors;
		}

		$this->db->sql_transaction('begin');

		$moved_templates = $this->get_template_branch($from_id, 'children', 'descending');
		$from_data = $moved_templates[0];
		$diff = count($moved_templates) * 2;

		$moved_ids = [];
		for ($i = 0, $size = count($moved_templates); $i < $size; ++$i)
		{
			$moved_ids[] = $moved_templates[$i]['template_id'];
		}

		// Resync parents
		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET right_id = right_id - $diff, template_parents = ''
			WHERE left_id < " . $from_data['right_id'] . "
				AND right_id > " . $from_data['right_id'];
		$this->db->sql_query($sql);

		// Resync righthand side of tree
		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET left_id = left_id - $diff, right_id = right_id - $diff, template_parents = ''
			WHERE left_id > " . $from_data['right_id'];
		$this->db->sql_query($sql);

		if ($to_id > 0)
		{
			// Retrieve $to_data again, it may have been changed...
			$to_data = $this->get_template_info($to_id);

			// Resync new parents
			$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
				SET right_id = right_id + $diff, template_parents = ''
				WHERE " . $to_data['right_id'] . ' BETWEEN left_id AND right_id
					AND ' . $this->db->sql_in_set('template_id', $moved_ids, true);
			$this->db->sql_query($sql);

			// Resync the righthand side of the tree
			$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
				SET left_id = left_id + $diff, right_id = right_id + $diff, template_parents = ''
				WHERE left_id > " . $to_data['right_id'] . '
					AND ' . $this->db->sql_in_set('template_id', $moved_ids, true);
			$this->db->sql_query($sql);

			// Resync moved branch
			$to_data['right_id'] += $diff;

			if ($to_data['right_id'] > $from_data['right_id'])
			{
				$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
			}
			else
			{
				$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
			}
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . PFT_TEMPLATES_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $moved_ids, true);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$diff = '+ ' . ($row['right_id'] - $from_data['left_id'] + 1);
		}

		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET left_id = left_id $diff, right_id = right_id $diff, template_parents = ''
			WHERE " . $this->db->sql_in_set('template_id', $moved_ids);
		$this->db->sql_query($sql);

		$this->db->sql_transaction('commit');

		return $errors;
	}

	/**
	 * Move template content from one to another template
	 */
	function move_template_content($from_id, $to_id, $move_subtemplates = false)
	{
		$errors = [];

		// Return if there were errors
		if (!empty($errors))
		{
			return $errors;
		}

		if ($move_subtemplates)
		{
			if (!$to_id)
			{
				$errors[] = $this->language->lang('ACP_PFT_NO_DESTINATION_TEMPLATE');
			}
			else
			{
				$log_action_subtemplates = 'MOVE_TEMPLATES';

				$sql = 'SELECT template_name
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_id = ' . $to_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $this->language->lang('ACP_PFT_NO_TEMPLATE');
				}
				else
				{
					$sql = 'SELECT template_name
						FROM ' . PFT_TEMPLATES_TABLE . '
						WHERE template_id = ' . $from_id;
					$result = $this->db->sql_query($sql);
					$from_template_name = $this->db->sql_fetchfield('template_name');
					$this->db->sql_freeresult($result);

					$subtemplates_to_name = $row['template_name'];

					$sql = 'SELECT template_id
						FROM ' . PFT_TEMPLATES_TABLE . "
						WHERE parent_id = $from_id";
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						if (!empty($row))
						{
							$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
								SET parent_id = $to_id
								WHERE parent_id = $from_id";
							$this->db->sql_query($sql);
							$this->move_template($row['template_id'], $to_id);
						}
						else
						{
							$subtemplates_to_name = false;
						}
					}
					$this->db->sql_freeresult($result);

					if ($subtemplates_to_name !== false)
					{
						$log_action = implode('_', [$log_action_subtemplates]);

						switch ($log_action)
						{
							case '_MOVE_TEMPLATES':
								$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_TEMPLATE_DEL_MOVE_TEMPLATES', false, [$subtemplates_to_name, $from_template_name]);
							break;

							default:
							break;
						}
					}
				}
			}

			if (count($errors))
			{
				return $errors;
			}
		}
		else
		{
			$this->copy_template_entries($from_id, $to_id, false, false);

			$table_ary = [LOG_TABLE, PFT_TEMPLATE_FORUMS_TABLE];

			foreach ($table_ary as $table)
			{
				$sql = "UPDATE $table
					SET template_id = $to_id
					WHERE template_id = $from_id";
				$this->db->sql_query($sql);
			}
			unset($table_ary);
		}

		return $errors;
	}

	/**
	 * Remove complete template
	 */
	function delete_template($template_id, $action_entries = 'delete', $action_subtemplates = 'delete', $entries_to_id = 0, $subtemplates_to_id = 0)
	{
		$template_data = $this->get_template_info($template_id);

		$errors = [];
		$log_action_entries = $log_action_subtemplates = $entries_to_name = $subtemplates_to_name = '';
		$template_ids = [$template_id];

		if ($action_entries == 'delete')
		{
			$log_action_entries = 'ENTRIES';
			$errors = array_merge($errors, $this->delete_template_content($template_id));
		}
		else if ($action_entries == 'move')
		{
			if (!$entries_to_id)
			{
				$errors[] = $this->language->lang('ACP_PFT_NO_DESTINATION_TEMPLATE');
			}
			else
			{
				$log_action_entries = 'MOVE_ENTRIES';

				$sql = 'SELECT template_name
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_id = ' . $entries_to_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $this->language->lang('ACP_PFT_NO_TEMPLATE');
				}
				else
				{
					$entries_to_name = $row['template_name'];
					$errors = array_merge($errors, $this->move_template_content($template_id, $entries_to_id));
				}
			}
		}

		if (count($errors))
		{
			return $errors;
		}

		if ($action_subtemplates == 'delete')
		{
			$log_action_subtemplates = 'TEMPLATES';
			$rows = $this->get_template_branch($template_id, 'children', 'descending', false);

			foreach ($rows as $row)
			{
				$template_ids[] = $row['template_id'];
				$errors = array_merge($errors, $this->delete_template_content($row['template_id']));
			}

			if (count($errors))
			{
				return $errors;
			}

			$diff = count($template_ids) * 2;

			$sql = 'DELETE FROM ' . PFT_TEMPLATES_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . PFT_TEMPLATE_FORUMS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_ids);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . ACL_USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $template_ids);
			$this->db->sql_query($sql);
		}
		else if ($action_subtemplates == 'move')
		{
			if (!$subtemplates_to_id)
			{
				$errors[] = $this->language->lang('ACP_PFT_NO_DESTINATION_TEMPLATE');
			}
			else
			{
				$log_action_subtemplates = 'MOVE_TEMPLATES';

				$sql = 'SELECT template_name
					FROM ' . PFT_TEMPLATES_TABLE . '
					WHERE template_id = ' . $subtemplates_to_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $this->language->lang('ACP_PFT_NO_TEMPLATE');
				}
				else
				{
					$subtemplates_to_name = $row['template_name'];

					$sql = 'SELECT template_id
						FROM ' . PFT_TEMPLATES_TABLE . "
						WHERE parent_id = $template_id";
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						$this->move_template($row['template_id'], $subtemplates_to_id);
					}
					$this->db->sql_freeresult($result);

					// Grab new template data for correct tree updating later
					$template_data = $this->get_template_info($template_id);

					$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
						SET parent_id = $subtemplates_to_id
						WHERE parent_id = $template_id";
					$this->db->sql_query($sql);

					$diff = 2;
					$sql = 'DELETE FROM ' . PFT_TEMPLATES_TABLE . "
						WHERE template_id = $template_id";
					$this->db->sql_query($sql);

					$sql = 'DELETE FROM ' . PFT_TEMPLATE_FORUMS_TABLE . "
						WHERE template_id = $template_id";
					$this->db->sql_query($sql);

					$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . "
						WHERE template_id = $template_id";
					$this->db->sql_query($sql);

					$sql = 'DELETE FROM ' . ACL_USERS_TABLE . "
						WHERE template_id = $template_id";
					$this->db->sql_query($sql);
				}
			}

			if (count($errors))
			{
				return $errors;
			}
		}
		else
		{
			$diff = 2;
			$sql = 'DELETE FROM ' . PFT_TEMPLATES_TABLE . "
				WHERE template_id = $template_id";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
				WHERE template_id = $template_id";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . PFT_TEMPLATE_FORUMS_TABLE . "
				WHERE template_id = $template_id";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . "
				WHERE template_id = $template_id";
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . ACL_USERS_TABLE . "
				WHERE template_id = $template_id";
			$this->db->sql_query($sql);
		}

		// Resync tree
		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET right_id = right_id - $diff
			WHERE left_id < {$template_data['right_id']} AND right_id > {$template_data['right_id']}";
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
			SET left_id = left_id - $diff, right_id = right_id - $diff
			WHERE left_id > {$template_data['right_id']}";
		$this->db->sql_query($sql);

		$log_action = implode('_', [$log_action_entries, $log_action_subtemplates]);

		switch ($log_action)
		{
			case 'MOVE_ENTRIES_MOVE_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES_MOVE_TEMPLATES', false, [$entries_to_name, $subtemplates_to_name, $template_data['template_name']]);
			break;

			case 'MOVE_ENTRIES_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES_TEMPLATES', false, [$entries_to_name, $template_data['template_name']]);
			break;

			case 'ENTRIES_MOVE_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES_MOVE_TEMPLATES', false, [$subtemplates_to_name, $template_data['template_name']]);
			break;

			case '_MOVE_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_TEMPLATES', false, [$subtemplates_to_name, $template_data['template_name']]);
			break;

			case 'MOVE_ENTRIES_':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_MOVE_ENTRIES', false, [$entries_to_name, $template_data['template_name']]);
			break;

			case 'ENTRIES_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES_TEMPLATES', false, [$template_data['template_name']]);
			break;

			case '_TEMPLATES':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_TEMPLATES', false, [$template_data['template_name']]);
			break;

			case 'ENTRIES_':
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_ENTRIES', false, [$template_data['template_name']]);
			break;

			default:
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_DEL_TEMPLATE', false, [$template_data['template_name']]);
			break;
		}

		return $errors;
	}

	/**
	 * Delete template content
	 */
	function delete_template_content($template_id)
	{
		include_once($this->phpbb_root_path . 'includes/functions_posting.' . $this->phpEx);

		$this->db->sql_transaction('begin');

		// Delete shadow topics pointing to topics in this template
		//delete_topic_shadows($template_id);

		switch ($this->db->get_sql_layer())
		{
			case 'mysqli':

				// Delete everything else and thank MySQL for offering multi-table deletion
				$tables_ary = [
					PFT_TEMPLATE_FORUMS_TABLE => 'template_id',
				];

				$sql = 'DELETE ' . PFT_TEMPLATE_ENTRIES_TABLE;
				$sql_using = "\nFROM " . PFT_TEMPLATE_ENTRIES_TABLE;
				$sql_where = "\nWHERE " . PFT_TEMPLATE_ENTRIES_TABLE . ".template_id = $template_id\n";

				foreach ($tables_ary as $table => $field)
				{
					$sql .= ", $table ";
					$sql_using .= ", $table ";
					$sql_where .= "\nAND $table.$field = " . PFT_TEMPLATE_ENTRIES_TABLE . ".$field";
				}

				$this->db->sql_query($sql . $sql_using . $sql_where);

			break;

			default:

				// Delete everything else and curse your DB for not offering multi-table deletion
				$tables_ary = [
					'template_id' => [
						PFT_TEMPLATE_FORUMS_TABLE,
					],
				];

				// Amount of rows we select and delete in one iteration.
				$batch_size = 500;

				foreach ($tables_ary as $field => $tables)
				{
					$start = 0;

					do
					{
						$sql = "SELECT $field
							FROM " . PFT_TEMPLATE_ENTRIES_TABLE . '
							WHERE template_id = ' . $template_id;
						$result = $this->db->sql_query_limit($sql, $batch_size, $start);

						$ids = [];
						while ($row = $this->db->sql_fetchrow($result))
						{
							$ids[] = $row[$field];
						}
						$this->db->sql_freeresult($result);

						if (count($ids))
						{
							$start += count($ids);

							foreach ($tables as $table)
							{
								$this->db->sql_query("DELETE FROM $table WHERE " . $this->db->sql_in_set($field, $ids));
							}
						}
					} while (count($ids) == $batch_size);
				}
				unset($ids);

			break;
		}

		$table_ary = [LOG_TABLE];

		foreach ($table_ary as $table)
		{
			$this->db->sql_query("DELETE FROM $table WHERE template_id = $template_id");
		}

		$this->db->sql_transaction('commit');

		return [];
	}

	/**
	 * Header for all copy functions
	 */
	function copy_initiate(&$src_template_id, &$dest_template_ids)
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

		return [$src_template_name, $dest_template_names];
	}

	/**
	 * Handles copying permissions from one template to others
	 */
	function copy_template_permissions($u_action)
	{
		$submit = isset($_POST['submit']) ? true : false;

		if ($submit)
		{
			$src = $this->request->variable('src_template_id', 0);
			$dest = $this->request->variable('dest_template_ids', [0]);

			if (confirm_box(true))
			{
				if ($this->copy_template_permissions($src, $dest))
				{
					phpbb_cache_moderators($this->db, $this->cache, $this->auth);

					$this->auth->acl_clear_prefetch();
					$this->cache->destroy('sql', PFT_TEMPLATES_TABLE);

					trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($u_action));
				}
				else
				{
					trigger_error($this->language->lang('SELECTED_TEMPLATE_NOT_EXIST') . adm_back_link($u_action), E_USER_WARNING);
				}
			}
			else
			{
				$s_hidden_fields = [
					'submit'            => $submit,
					'src_template_id'   => $src,
					'dest_template_ids' => $dest,
				];

				$s_hidden_fields = build_hidden_fields($s_hidden_fields);

				confirm_box(false, $this->language->lang('COPY_PERMISSIONS_CONFIRM'), $s_hidden_fields);
			}
		}

		$this->template->assign_vars([
			'S_TEMPLATE_OPTIONS' => $this->make_template_select(false, false, false, false, false),
		]);
	}

	/**
	 * Copies entries from one template to others
	 *
	 * @param int   $src_template_id    The source template we want to copy entries from
	 * @param array $dest_template_ids  The destination template(s) we want to copy to
	 * @param bool  $clear_dest_entries True if destination entries should be deleted
	 * @param bool  $add_log            True if log entry should be added
	 *
	 * @return bool                            False on error
	 */
	function copy_template_entries($src_template_id, $dest_template_ids, $clear_dest_entries = true, $add_log = true)
	{
		$src_template_name = '';
		$dest_template_names = [];
		if (($copy_initiate = $this->copy_initiate($src_template_id, $dest_template_ids)) !== false)
		{
			list($src_template_name, $dest_template_names) = $copy_initiate;
		}
		else
		{
			return false;
		}

		// From the mysql documentation:
		// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear
		// in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
		// Due to this we stay on the safe side if we do the insertion "the manual way"

		// Rowsets we're going to insert
		$entries_sql_ary = [];

		// Query pft template entries table for source entry data
		$sql = 'SELECT entry_id, left_id, right_id, parent_id, entry_tag, entry_tag_bitfield, entry_tag_uid, entry_match, entry_helpline, entry_type_match, entry_type, display_on_posting
			FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'entry_id'           => (int) $row['entry_id'],
				'left_id'            => (int) $row['left_id'],
				'right_id'           => (int) $row['right_id'],
				'parent_id'          => (int) $row['parent_id'],
				'entry_tag'          => $row['entry_tag'],
				'entry_tag_bitfield' => $row['entry_tag_bitfield'],
				'entry_tag_uid'      => $row['entry_tag_uid'],
				'entry_match'        => $row['entry_match'],
				'entry_helpline'     => $row['entry_helpline'],
				'entry_type_match'   => $row['entry_type_match'],
				'entry_type'         => (int) $row['entry_type'],
				'display_on_posting' => (int) $row['display_on_posting'],
			];

			foreach ($dest_template_ids as $dest_template_id)
			{
				// get number to offset new left and right ids by
				$sql = 'SELECT MAX(right_id) as diff
					FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
					WHERE template_id = ' . $dest_template_id;
				$result2 = $this->db->sql_query($sql);
				$diff = $this->db->sql_fetchfield('diff');
				$this->db->sql_freeresult($result2);

				$row['left_id'] += $diff;
				$row['right_id'] += $diff;
				$entries_sql_ary[] = $row + ['template_id' => $dest_template_id];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		// Clear current entries of destination templates
		if ($clear_dest_entries)
		{
			$sql = 'DELETE FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $dest_template_ids);
			$this->db->sql_query($sql);
		}

		$count = count($entries_sql_ary);
		for ($i = 0; $i < $count; $i++)
		{
			$old_entry_id = $entries_sql_ary[$i]['entry_id'];
			unset($entries_sql_ary[$i]['entry_id']);

			$sql = 'INSERT INTO ' . PFT_TEMPLATE_ENTRIES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $entries_sql_ary[$i]);
			$this->db->sql_query($sql);

			$entry_id = $this->db->sql_nextid();

			// update subentry parent ids
			if ($entries_sql_ary[$i]['parent_id'] == 0 && $i + 1 < $count)
			{
				for ($x = $i + 1; $x < $count; $x++)
				{
					if ($entries_sql_ary[$x]['parent_id'] == $old_entry_id)
					{
						$entries_sql_ary[$x]['parent_id'] = $entry_id;
					}
				}
			}
		}

		if ($add_log)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_COPIED_ENTRIES', false, [$src_template_name, implode(', ', $dest_template_names)]);
		}

		$this->db->sql_transaction('commit');

		return true;
	}

	/**
	 * Copies contents from one template to others
	 *
	 * @param int   $src_template_id     The source template we want to copy entries from
	 * @param array $dest_template_ids   The destination template(s) we want to copy to
	 * @param bool  $clear_dest_contents True if destination contents should be deleted
	 * @param bool  $add_log             True if log entry should be added
	 *
	 * @return bool                            False on error
	 */
	function copy_template_contents($src_template_id, $dest_template_ids, $clear_dest_contents = true, $add_log = true, $u_action)
	{
		$src_template_name = '';
		$dest_template_names = [];
		if (($copy_initiate = $this->copy_initiate($src_template_id, $dest_template_ids)) !== false)
		{
			list($src_template_name, $dest_template_names) = $copy_initiate;
		}
		else
		{
			return false;
		}

		// From the mysql documentation:
		// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear
		// in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
		// Due to this we stay on the safe side if we do the insertion "the manual way"

		// Rowsets we're going to insert
		$contents_sql_ary = [];

		// Query pft template entries table for source entry data
		$sql = 'SELECT template_id, template_parents, template_name, template_desc, template_desc_bitfield, template_desc_options, template_desc_uid, template_images, template_image_type, template_image_date, template_type, template_status
			FROM ' . PFT_TEMPLATES_TABLE . '
			WHERE parent_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'template_id'            => $row['template_id'],
				'template_parents'       => $row['template_parents'],
				'template_name'          => $row['template_name'],
				'template_desc'          => $row['template_desc'],
				'template_desc_bitfield' => $row['template_desc_bitfield'],
				'template_desc_options'  => (int) $row['template_desc_options'],
				'template_desc_uid'      => $row['template_desc_uid'],
				'template_images'        => $row['template_images'],
				'template_image_type'    => (int) $row['template_image_type'],
				'template_image_date'    => $row['template_image_date'],
				'template_type'          => (int) $row['template_type'],
				'template_status'        => (int) $row['template_status'],
			];

			// to avoid errors in the update template data function
			$row += [
				'forum_id'   => [],
				'all_forums' => 0,
				'all_images' => 0,
			];

			foreach ($dest_template_ids as $dest_template_id)
			{
				$contents_sql_ary[] = $row + ['parent_id' => $dest_template_id];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		// Clear current entries of destination templates
		if ($clear_dest_contents)
		{
			$sql = 'DELETE FROM ' . PFT_TEMPLATES_TABLE . '
				WHERE ' . $this->db->sql_in_set('parent_id', $dest_template_ids);
			$this->db->sql_query($sql);
		}

		foreach ($dest_template_ids as $dest_template_id)
		{
			foreach ($contents_sql_ary as $template_data)
			{
				$old_template_id = $template_data['template_id'];
				unset($template_data['template_id']);
				if ($old_template_id != $dest_template_id)
				{
					$this->update_template_data($template_data, $u_action, $dest_template_id, false);
					$this->template_permissions_helper->copy_template_permissions($old_template_id, $template_data['template_id'], false, false);
					phpbb_cache_moderators($this->db, $this->cache, $this->auth);
					$this->copy_template_display_forums($old_template_id, $template_data['template_id'], false, false);
					if ($template_data['template_type'] == TEMPLATE_FORM)
					{
						$this->copy_template_entries($old_template_id, $template_data['template_id'], false, false);
					}
					else
					{
						$this->copy_template_contents($old_template_id, $template_data['template_id'], false, false, $u_action);
					}
				}
			}
		}

		if ($add_log)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_COPIED_CONTENTS', false, [$src_template_name, implode(', ', $dest_template_names)]);
		}

		$this->db->sql_transaction('commit');

		return true;
	}

	/**
	 * Copies display forums from one template to others
	 *
	 * @param int   $src_template_id   The source template we want to copy display forums from
	 * @param array $dest_template_ids The destination template(s) we want to copy to
	 * @param bool  $clear_dest_forums True if destination display forums should be deleted
	 * @param bool  $add_log           True if log entry should be added
	 *
	 * @return bool                            False on error
	 */
	function copy_template_display_forums($src_template_id, $dest_template_ids, $clear_dest_forums = true, $add_log = true)
	{
		$src_template_name = '';
		$dest_template_names = [];
		if (($copy_initiate = $this->copy_initiate($src_template_id, $dest_template_ids)) !== false)
		{
			list($src_template_name, $dest_template_names) = $copy_initiate;
		}
		else
		{
			return false;
		}

		// From the mysql documentation:
		// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear
		// in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
		// Due to this we stay on the safe side if we do the insertion "the manual way"

		// Rowsets we're going to insert
		$forums_sql_ary = [];

		// Query pft template entries table for source display forum data
		$sql = 'SELECT forum_id
			FROM ' . PFT_TEMPLATE_FORUMS_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row = [
				'forum_id' => (int) $row['forum_id'],
			];

			foreach ($dest_template_ids as $dest_template_id)
			{
				$forums_sql_ary[] = $row + ['template_id' => $dest_template_id];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		// Clear current entries of destination templates
		if ($clear_dest_forums)
		{
			$sql = 'DELETE FROM ' . PFT_TEMPLATE_FORUMS_TABLE . '
				WHERE ' . $this->db->sql_in_set('template_id', $dest_template_ids);
			$this->db->sql_query($sql);
		}

		$this->db->sql_multi_insert(PFT_TEMPLATE_FORUMS_TABLE, $forums_sql_ary);

		if ($add_log)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_COPIED_FORUMS', false, [$src_template_name, implode(', ', $dest_template_names)]);
		}

		$this->db->sql_transaction('commit');

		return true;
	}

	/**
	 * Copies image settings from one template to others
	 *
	 * @param int   $src_template_id     The source template we want to copy entries from
	 * @param array $dest_template_ids   The destination template(s) we want to copy to
	 * @param bool  $clear_dest_contents True if destination image settings should be deleted
	 * @param bool  $add_log             True if log entry should be added
	 *
	 * @return bool                            False on error
	 */
	function copy_template_images($src_template_id, $dest_template_ids, $clear_dest_contents = true, $add_log = true)
	{
		$src_template_name = '';
		$dest_template_names = [];
		if (($copy_initiate = $this->copy_initiate($src_template_id, $dest_template_ids)) !== false)
		{
			list($src_template_name, $dest_template_names) = $copy_initiate;
		}
		else
		{
			return false;
		}

		// From the mysql documentation:
		// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear
		// in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
		// Due to this we stay on the safe side if we do the insertion "the manual way"

		// Rowsets we're going to insert
		$images_sql_ary = [];

		// Query pft template entries table for source entry data
		$sql = 'SELECT template_images, template_image_type, template_image_date
			FROM ' . PFT_TEMPLATES_TABLE . '
			WHERE template_id = ' . $src_template_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$images_sql_ary = [
				'template_images'     => $row['template_images'],
				'template_image_type' => (int) $row['template_image_type'],
				'template_image_date' => $row['template_image_date'],
			];
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		$extra_sql = '';
		// Clear current entries of destination templates
		if ($clear_dest_contents)
		{
			$extra_sql = ", template_image_type = $images_sql_ary[template_image_type], template_image_date = '$images_sql_ary[template_image_date]'";
		}

		foreach ($dest_template_ids as $dest_template_id)
		{
			$sql = 'UPDATE ' . PFT_TEMPLATES_TABLE . "
				SET template_images = '$images_sql_ary[template_images]'$extra_sql
				WHERE template_id = $dest_template_id";
			$this->db->sql_query($sql);
		}

		if ($add_log)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_COPIED_IMAGES', false, [$src_template_name, implode(', ', $dest_template_names)]);
		}

		$this->db->sql_transaction('commit');

		return true;
	}

	/**
	 * Copies template settings from one template to others
	 *
	 * @param int $src_template_id The source template we want to copy display forums from
	 *
	 * @return bool                            False on error
	 */
	function copy_template_settings($src_template_id, $u_action, $parent_id = 'copy', &$template_data)
	{
		// Make sure template ids are integers
		$src_template_id = (int) $src_template_id;

		// No source template or no destination templates specified
		if (empty($src_template_id))
		{
			return [$this->language->lang('ACP_PFT_NO_TEMPLATE')];
		}

		$template_row = $this->get_template_info($src_template_id);
		$template_row['parent_id'] = ($parent_id == 'copy') ? (int) $template_row['parent_id'] : $parent_id;

		if (isset($template_data['template_id']))
		{
			$sql = 'SELECT left_id, right_id
				FROM ' . PFT_TEMPLATES_TABLE . '
				WHERE template_id = ' . $template_data['template_id'];
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$template_row['left_id'] = $row['left_id'];
			$template_row['right_id'] = $row['right_id'];
			$template_row['template_id'] = $template_data['template_id'];
		}
		else
		{
			unset($template_row['template_id']);
		}

		// to avoid errors in the update template data function
		$template_row += [
			'forum_id'    => [],
			'all_forums'  => 0,
			'all_images'  => 0,
			'type_action' => 'nothing',
		];

		$template_data = $template_row;

		return $this->update_template_data($template_data, $u_action, $template_data['parent_id']);
	}
}
