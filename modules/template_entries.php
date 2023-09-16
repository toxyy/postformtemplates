<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\modules;

class template_entries
{
	/** @var \phpbb\cache\driver\driver_interface $cache */
	protected $cache;
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\request\request $request */
	protected $request;
	/** @var \phpbb\user $user */
	protected $user;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\template\template $template */
	protected $template;
	/** @var \phpbb\log\log $phpbb_log */
	protected $phpbb_log;
	/** @var \phpbb\textformatter\s9e\factory $textformatter_cache */
	protected $textformatter_cache;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $phpbb_admin_path;
	/** @var string */
	protected $phpEx;
	/** @var string */
	protected $pft_template_entries_table;
	/** @var string */
	protected $pft_entries_text;
	/** @var string */
	protected $pft_entries_radio;
	/** @var string */
	protected $pft_entries_checkbox;
	/** @var string */
	protected $pft_entries_dropdown;
	/** @var string */
	protected $pft_entries_textnote;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface $cache
	 * @param \phpbb\db\driver\driver_interface    $db
	 * @param \phpbb\request\request               $request
	 * @param \phpbb\user                          $user
	 * @param \phpbb\language\language             $language
	 * @param \phpbb\template\template             $template
	 * @param \phpbb\log\log                       $phpbb_log
	 * @param \phpbb\textformatter\s9e\factory     $textformatter_cache
	 * @param string                               $phpbb_root_path            phpBB root path
	 * @param string                               $phpbb_admin_path           phpBB admin path
	 * @param string                               $phpEx                      PHP file extension
	 * @param string \toxyy\postformtemplates\     $pft_template_entries_table
	 * @param string \toxyy\postformtemplates\     $pft_entries_text
	 * @param string \toxyy\postformtemplates\     $pft_entries_radio
	 * @param string \toxyy\postformtemplates\     $pft_entries_checkbox
	 * @param string \toxyy\postformtemplates\     $pft_entries_dropdown
	 * @param string \toxyy\postformtemplates\     $pft_entries_textnote
	 */
	public function __construct(
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\user $user,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\log\log $phpbb_log,
		\phpbb\textformatter\s9e\factory $textformatter_cache,
		$phpbb_root_path,
		$phpbb_admin_path,
		$phpEx,
		$pft_template_entries_table,
		$pft_entries_text,
		$pft_entries_radio,
		$pft_entries_checkbox,
		$pft_entries_dropdown,
		$pft_entries_textnote
	)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->phpbb_log = $phpbb_log;
		$this->textformatter_cache = $textformatter_cache;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpbb_admin_path = $this->phpbb_root_path . $phpbb_admin_path;
		$this->phpEx = $phpEx;
		$this->pft_template_entries_table = $pft_template_entries_table;
		$this->pft_entries_text = $pft_entries_text;
		$this->pft_entries_radio = $pft_entries_radio;
		$this->pft_entries_checkbox = $pft_entries_checkbox;
		$this->pft_entries_dropdown = $pft_entries_dropdown;
		$this->pft_entries_textnote = $pft_entries_textnote;

		if (!defined('PFT_TEMPLATE_ENTRIES_TABLE'))
		{
			define('PFT_TEMPLATE_ENTRIES_TABLE', $this->pft_template_entries_table);
		}
		if (!defined('PFT_ENTRIES_TEXT'))
		{
			define('PFT_ENTRIES_TEXT', $this->pft_entries_text);
		}
		if (!defined('PFT_ENTRIES_RADIO'))
		{
			define('PFT_ENTRIES_RADIO', $this->pft_entries_radio);
		}
		if (!defined('PFT_ENTRIES_CHECKBOX'))
		{
			define('PFT_ENTRIES_CHECKBOX', $this->pft_entries_checkbox);
		}
		if (!defined('PFT_ENTRIES_DROPDOWN'))
		{
			define('PFT_ENTRIES_DROPDOWN', $this->pft_entries_dropdown);
		}
		if (!defined('PFT_ENTRIES_TEXTNOTE'))
		{
			define('PFT_ENTRIES_TEXTNOTE', $this->pft_entries_textnote);
		}
	}

	function main($id, $mode)
	{
		$this->language->add_lang('acp/posting');

		// Set up general vars
		$action = $this->request->variable('action', '');
		$entry_id = $this->request->variable('entry', 0);
		$template_id = $this->request->variable('parent_id', 0);
		$parent_entry_id = $this->request->variable('parent_entry_id', 0);
		$u_action = append_sid("{$this->phpbb_admin_path}index.{$this->phpEx}", "i=$id&amp;mode=manage_templates&amp;parent_id=$template_id");

		//$this->tpl_name = 'acp_bbcodes';
		//$this->page_title = 'ACP_TEMPLATE_ENTRIES';
		$form_key = 'acp_entries';

		add_form_key($form_key);

		// Set up mode-specific vars
		switch ($action)
		{
			case 'add_entry':
				$entry_tag = $entry_match = $entry_helpline = $entry_type_match = '';
				$display_on_posting = $entry_type = 0;
				$entry_rows = 1;
			break;

			case 'edit_entry':
				$sql = 'SELECT entry_tag, entry_tag_uid, entry_match, display_on_posting, entry_helpline, entry_type, entry_type_match, entry_rows
					FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
					WHERE entry_id = ' . $entry_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_NOT_EXIST') . adm_back_link($u_action), E_USER_WARNING);
				}

				$entry_tag_data = generate_text_for_edit($row['entry_tag'], $row['entry_tag_uid'], 0);
				$entry_tag = PHP_EOL . $entry_tag_data['text'];
				$entry_match = PHP_EOL . $row['entry_match'];
				$display_on_posting = $row['display_on_posting'];
				$entry_helpline = $row['entry_helpline'];
				$entry_type = (int) $row['entry_type'];
				$entry_type_match = (empty($row['entry_type_match']) ? '' : implode(PHP_EOL, unserialize($row['entry_type_match'])));
				$entry_rows = (int) $row['entry_rows'];
				$this->template->assign_vars([
					'S_ENTRY_ID'        => $entry_id,
					'S_PARENT_ENTRY_ID' => $parent_entry_id,
				]);
			break;

			case 'modify_entry':
				$sql = 'SELECT entry_id
					FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
					WHERE entry_id = ' . $entry_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_NOT_EXIST') . adm_back_link($u_action), E_USER_WARNING);
				}

			// No break here

			case 'create_entry':
				$display_on_posting = $this->request->variable('display_on_posting', 0);
				$entry_type = $this->request->variable('entry_type', 0);

				$entry_tag = $this->request->untrimmed_variable('entry_tag', '', true);
				$entry_match = $this->request->untrimmed_variable('entry_match', '', true);
				$entry_helpline = $this->request->variable('entry_helpline', '', true);
				$entry_type_match = $this->request->variable('entry_type_match', '', true);
				$entry_rows = $this->request->variable('entry_rows', 1);
			break;
		}

		// Do major work
		switch ($action)
		{
			case 'move_up_entry':
			case 'move_down_entry':

				if (!$entry_id)
				{
					trigger_error($this->language->lang('ACP_PFT_NO_TEMPLATE') . adm_back_link($u_action . '&amp;parent_id=' . $template_id), E_USER_WARNING);
				}

				$sql = 'SELECT *
					FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
					WHERE entry_id = $entry_id";
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					trigger_error($this->language->lang('ACP_PFT_NO_TEMPLATE') . adm_back_link($u_action . '&amp;parent_id=' . $template_id), E_USER_WARNING);
				}

				$move_entry_tag = $this->move_entry_by($row, $action, 1);

				if ($move_entry_tag !== false)
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_' . strtoupper($action), false, [$row['entry_tag'], $move_entry_tag]);
					$this->cache->destroy('sql', PFT_TEMPLATE_ENTRIES_TABLE);
				}

				if ($this->request->is_ajax())
				{
					$json_response = new \phpbb\json_response;
					$json_response->send(['success' => ($move_entry_tag !== false)]);
				}

			break;

			case 'edit_entry':
			case 'add_entry':

				$tpl_ary = [
					'S_EDIT_TEMPLATE_ENTRY' => true,
					'U_BACK'                => $u_action . (($parent_entry_id) ? '&amp;action=edit_entry&amp;entry=' . $parent_entry_id : ''),
					'U_ACTION'              => $u_action . '&amp;action=' . (($action == 'add_entry') ? 'create_entry' : 'modify_entry') . (($entry_id) ? "&amp;entry=$entry_id" : '') . (($parent_entry_id) ? "&amp;parent_entry_id=$parent_entry_id" : ''),

					'L_TEMPLATE_ENTRY_USAGE_EXPLAIN' => sprintf($this->language->lang('TEMPLATE_ENTRY_USAGE_EXPLAIN'), '<a href="#down">', '</a>'),
					'TEMPLATE_ENTRY_TAG'             => $entry_tag,
					'TEMPLATE_ENTRY_MATCH'           => $entry_match,
					'TEMPLATE_ENTRY_HELPLINE'        => $entry_helpline,
					'ENTRY_TYPE'                     => $entry_type,
					'TEMPLATE_ENTRY_TYPE_MATCH'      => $entry_type_match,
					'ENTRY_ROWS'                     => $entry_rows,
					'DISPLAY_ON_POSTING'             => $display_on_posting,
				];

				$bbcode_tokens = ['TEXT', 'SIMPLETEXT', 'INTTEXT', 'IDENTIFIER', 'NUMBER', 'EMAIL', 'URL', 'LOCAL_URL', 'RELATIVE_URL', 'COLOR'];

				$this->template->assign_vars($tpl_ary);

				foreach ($bbcode_tokens as $token)
				{
					$this->template->assign_block_vars('token', [
						'TOKEN'   => '{' . $token . '}',
						'EXPLAIN' => ($token === 'LOCAL_URL') ? $this->language->lang(['tokens', $token], generate_board_url() . '/') : $this->language->lang(['tokens', $token]),
					]);
				}

				$sql_ary = [
					'SELECT'   => 'e.*',
					'FROM'     => [PFT_TEMPLATE_ENTRIES_TABLE => 'e'],
					'WHERE'    => $this->db->sql_in_set('template_id', $template_id) . '
						AND ' . $this->db->sql_in_set('parent_id', $entry_id),
					'ORDER_BY' => 'e.left_id',
				];
				$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql_ary));

				while ($row = $this->db->sql_fetchrow($result))
				{
					$entry_tag_data = generate_text_for_edit($row['entry_tag'], $row['entry_tag_uid'], 0);
					$entries_array = [
						'TEMPLATE_ENTRY_TAG' => $entry_tag_data['text'],
						'U_MOVE_UP'          => $u_action . '&amp;action=move_up_entry&amp;entry=' . $row['entry_id'] . '&amp;parent_entry_id=' . $row['parent_id'],
						'U_MOVE_DOWN'        => $u_action . '&amp;action=move_down_entry&amp;entry=' . $row['entry_id'] . '&amp;parent_entry_id=' . $row['parent_id'],
						'U_EDIT'             => $u_action . '&amp;action=edit_entry&amp;entry=' . $row['entry_id'] . '&amp;parent_entry_id=' . $row['parent_id'],
						'U_DELETE'           => $u_action . '&amp;action=delete_entry&amp;entry=' . $row['entry_id'] . '&amp;parent_entry_id=' . $row['parent_id'],
					];

					$this->template->assign_block_vars('entries', $entries_array);
				}

				return;

			break;

			case 'modify_entry':
			case 'create_entry':

				$sql_ary = [];

				if (!check_form_key($form_key))
				{
					trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($u_action), E_USER_WARNING);
				}

				if (strlen($entry_tag) > 3000)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_TAG_TOO_LONG') . adm_back_link($u_action), E_USER_WARNING);
				}
				if (strlen($entry_match) > 4000)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_TAG_DEF_TOO_LONG') . adm_back_link($u_action), E_USER_WARNING);
				}
				if (strlen($entry_helpline) > 3000)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_HELPLINE_TOO_LONG') . adm_back_link($u_action), E_USER_WARNING);
				}
				if (strlen($entry_type_match) > 4000)
				{
					trigger_error($this->language->lang('TEMPLATE_ENTRY_TYPE_MATCH_DEF_TOO_LONG') . adm_back_link($u_action), E_USER_WARNING);
				}

				/**
				 * Replace Emojis and other 4bit UTF-8 chars not allowed by MySQL to UCR/NCR.
				 * Using their Numeric Character Reference's Hexadecimal notation.
				 */
				$entry_tag = utf8_encode_ucr($entry_tag);
				$entry_helpline = utf8_encode_ucr($entry_helpline);

				$entry_tag_bitfield = $entry_tag_uid = $entry_tag_flags = '';
				if ($entry_tag)
				{
					generate_text_for_storage($entry_tag, $entry_tag_uid, $entry_tag_bitfield, $entry_tag_flags, true, true, true);
				}

				$sql_ary = array_merge($sql_ary, [
					'entry_tag'          => $entry_tag,
					'entry_tag_bitfield' => $entry_tag_bitfield,
					'entry_tag_uid'      => $entry_tag_uid,
					'entry_match'        => $entry_match,
					'display_on_posting' => $display_on_posting,
					'entry_helpline'     => $entry_helpline,
					'entry_type'         => $entry_type,
					'entry_type_match'   => serialize(explode(PHP_EOL, $entry_type_match)),
					'entry_rows'         => $entry_rows,
				]);

				if ($parent_entry_id)
				{
					$u_action .= '&amp;action=edit_entry&amp;entry=' . $parent_entry_id;
				}
				if ($action == 'create_entry')
				{
					$sql_ary['template_id'] = (int) $template_id;
					$sql_ary['parent_id'] = (int) $parent_entry_id;

					if ($parent_entry_id)
					{
						$sql = 'SELECT left_id, right_id
							FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . '
							WHERE entry_id = ' . $sql_ary['parent_id'];
						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						if (!$row)
						{
							trigger_error($this->language->lang('PARENT_NOT_EXIST') . adm_back_link($u_action), E_USER_WARNING);
						}

						$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . '
							SET left_id = left_id + 2, right_id = right_id + 2
							WHERE left_id > ' . $row['right_id'];
						$this->db->sql_query($sql);

						$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . '
							SET right_id = right_id + 2
							WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
						$this->db->sql_query($sql);

						$sql_ary['left_id'] = $row['right_id'];
						$sql_ary['right_id'] = $row['right_id'] + 1;
					}
					else
					{
						$sql = 'SELECT MAX(right_id) AS right_id
							FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
							WHERE template_id = $template_id";
						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						$sql_ary['left_id'] = $row['right_id'] + 1;
						$sql_ary['right_id'] = $row['right_id'] + 2;
					}

					$this->db->sql_query('INSERT INTO ' . PFT_TEMPLATE_ENTRIES_TABLE . $this->db->sql_build_array('INSERT', $sql_ary));
					$this->cache->destroy('sql', PFT_TEMPLATE_ENTRIES_TABLE);
					$this->textformatter_cache->invalidate();

					$lang = 'ACP_PFT_TEMPLATE_ENTRY_ADDED';
					$log_action = 'LOG_ACP_PFT_TEMPLATE_ENTRY_ADD';
				}
				else
				{
					$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE entry_id = ' . $entry_id;
					$this->db->sql_query($sql);
					$this->cache->destroy('sql', PFT_TEMPLATE_ENTRIES_TABLE);
					$this->textformatter_cache->invalidate();

					$lang = 'ACP_PFT_TEMPLATE_ENTRY_EDITED';
					$log_action = 'LOG_ACP_PFT_TEMPLATE_ENTRY_EDIT';
				}

				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_action, false, [$entry_tag]);

				trigger_error($this->language->lang($lang) . adm_back_link($u_action));

			break;

			case 'delete_entry':

				$sql = 'SELECT entry_tag, left_id, right_id, template_id
					FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
					WHERE entry_id = $entry_id";
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($row)
				{
					if (confirm_box(true))
					{
						$entry_tag = $row['entry_tag'];

						$this->db->sql_query('DELETE FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . " WHERE entry_id = $entry_id OR parent_id = $entry_id");

						// Resync tree
						$diff = 2;
						$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . "
							SET right_id = right_id - $diff
							WHERE left_id < {$row['right_id']} AND right_id > {$row['right_id']}";
						$this->db->sql_query($sql);

						$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . "
							SET left_id = left_id - $diff, right_id = right_id - $diff
							WHERE left_id > {$row['right_id']}";
						$this->db->sql_query($sql);

						$this->cache->destroy('sql', PFT_TEMPLATE_ENTRIES_TABLE);
						$this->textformatter_cache->invalidate();
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PFT_TEMPLATE_ENTRY_DELETE', false, [$entry_tag]);

						if ($this->request->is_ajax())
						{
							$json_response = new \phpbb\json_response;
							$json_response->send([
								'MESSAGE_TITLE' => $this->language->lang('INFORMATION'),
								'MESSAGE_TEXT'  => $this->language->lang('ACP_PFT_TEMPLATE_ENTRY_DELETED'),
								'REFRESH_DATA'  => [
									'time' => 3,
								],
							]);
						}
					}
					else
					{
						confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
							'entry'  => $entry_id,
							'i'      => $id,
							'mode'   => $mode,
							'action' => $action,
						]));
					}
				}

			break;
		}

		$template_data = [
			'U_ACTION' => $u_action . '&amp;action=add_entry',
		];

		$sql_ary = [
			'SELECT'   => 'e.*',
			'FROM'     => [PFT_TEMPLATE_ENTRIES_TABLE => 'e'],
			'WHERE'    => $this->db->sql_in_set('template_id', $template_id) . '
				AND parent_id = 0',
			'ORDER_BY' => 'e.left_id',
		];

		$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql_ary));

		$this->template->assign_vars($template_data);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$entry_tag_data = generate_text_for_edit($row['entry_tag'], $row['entry_tag_uid'], 0);
			$entries_array = [
				'TEMPLATE_ENTRY_TAG' => $entry_tag_data['text'],
				'U_MOVE_UP'          => $u_action . '&amp;action=move_up_entry&amp;entry=' . $row['entry_id'],
				'U_MOVE_DOWN'        => $u_action . '&amp;action=move_down_entry&amp;entry=' . $row['entry_id'],
				'U_EDIT'             => $u_action . '&amp;action=edit_entry&amp;entry=' . $row['entry_id'],
				'U_DELETE'           => $u_action . '&amp;action=delete_entry&amp;entry=' . $row['entry_id'],
			];

			$this->template->assign_block_vars('entries', $entries_array);

		}
		$this->db->sql_freeresult($result);
	}

	/**
	 * Move entry position by $steps up/down
	 */
	function move_entry_by($entry_row, $action = 'move_up_entry', $steps = 1)
	{
		/**
		 * Fetch all the siblings between the module's current spot
		 * and where we want to move it to. If there are less than $steps
		 * siblings between the current spot and the target then the
		 * module will move as far as possible
		 */
		$sql = 'SELECT entry_id, entry_tag, left_id, right_id
			FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
			WHERE parent_id = {$entry_row['parent_id']}
				AND " . (($action == 'move_up_entry') ? "right_id < {$entry_row['right_id']}
				AND template_id = {$entry_row['template_id']}
				ORDER BY right_id DESC" : "left_id > {$entry_row['left_id']}
				AND template_id = {$entry_row['template_id']}
				ORDER BY left_id ASC");
		$result = $this->db->sql_query_limit($sql, $steps);

		$target = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$target = $row;
		}
		$this->db->sql_freeresult($result);

		if (!count($target))
		{
			// The entry is already on top or bottom
			return false;
		}

		/**
		 * $left_id and $right_id define the scope of the nodes that are affected by the move.
		 * $diff_up and $diff_down are the values to substract or add to each node's left_id
		 * and right_id in order to move them up or down.
		 * $move_up_left and $move_up_right define the scope of the nodes that are moving
		 * up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
		 */
		if ($action == 'move_up_entry')
		{
			$left_id = $target['left_id'];
			$right_id = $entry_row['right_id'];

			$diff_up = $entry_row['left_id'] - $target['left_id'];
			$diff_down = $entry_row['right_id'] + 1 - $entry_row['left_id'];

			$move_up_left = $entry_row['left_id'];
			$move_up_right = $entry_row['right_id'];
		}
		else
		{
			$left_id = $entry_row['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $entry_row['right_id'] + 1 - $entry_row['left_id'];
			$diff_down = $target['right_id'] - $entry_row['right_id'];

			$move_up_left = $entry_row['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		// Now do the dirty job
		$sql = 'UPDATE ' . PFT_TEMPLATE_ENTRIES_TABLE . "
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END
			WHERE
				left_id BETWEEN {$left_id} AND {$right_id}
				AND right_id BETWEEN {$left_id} AND {$right_id}
				AND template_id = {$entry_row['template_id']}";

		$this->db->sql_query($sql);

		return $target['entry_tag'];
	}
}
