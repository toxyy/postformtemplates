<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\event;

/**
 * Event listener
 * All other events
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\request\request $request */
	protected $request;
	/** @var \phpbb\language\language */
	protected $language;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\config\config $config */
	protected $config;
	/** @var \phpbb\user */
	protected $user;
	/** @var \phpbb\auth\auth */
	protected $auth;
	/** @var \phpbb\content_visibility */
	protected $content_visibility;
	/** @var \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper */
	protected $auth_admin_helper;
	/** @var string */
	protected $pft_image_path;
	/** @var string */
	protected $pft_templates_table;
	/** @var string */
	protected $pft_template_forums_table;
	/** @var string */
	protected $pft_template_entries_table;
	/** @var string */
	protected $pft_template_images_table;
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
	 * @param \phpbb\db\driver\driver_interface                     $db
	 * @param \phpbb\request\request                                $request
	 * @param \phpbb\language\language                              $language
	 * @param \phpbb\template\template                              $template
	 * @param \phpbb\config\config                                  $config
	 * @param \phpbb\user                                           $user
	 * @param \phpbb\auth\auth                                      $auth
	 * @param \phpbb\content_visibility                             $content_visibility
	 * @param \toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper
	 * @param string \toxyy\postformtemplates\                      $pft_image_path
	 * @param string \toxyy\postformtemplates\                      $pft_templates_table
	 * @param string \toxyy\postformtemplates\                      $pft_template_forums_table
	 * @param string \toxyy\postformtemplates\                      $pft_template_entries_table
	 * @param string \toxyy\postformtemplates\                      $pft_template_images_table
	 * @param string \toxyy\postformtemplates\                      $pft_image_path
	 * @param string \toxyy\postformtemplates\                      $pft_entries_text
	 * @param string \toxyy\postformtemplates\                      $pft_entries_radio
	 * @param string \toxyy\postformtemplates\                      $pft_entries_checkbox
	 * @param string \toxyy\postformtemplates\                      $pft_entries_dropdown
	 * @param string \toxyy\postformtemplates\                      $pft_entries_textnote
	 *
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\content_visibility $content_visibility,
		\toxyy\postformtemplates\controller\auth_admin_helper $auth_admin_helper,
		$pft_image_path,
		$pft_templates_table,
		$pft_template_forums_table,
		$pft_template_entries_table,
		$pft_template_images_table,
		$pft_entries_text,
		$pft_entries_radio,
		$pft_entries_checkbox,
		$pft_entries_dropdown,
		$pft_entries_textnote
	)
	{
		$this->db = $db;
		$this->request = $request;
		$this->language = $language;
		$this->template = $template;
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->content_visibility = $content_visibility;
		$this->auth_admin_helper = $auth_admin_helper;
		$this->pft_image_path = $pft_image_path;
		$this->pft_templates_table = $pft_templates_table;
		$this->pft_template_forums_table = $pft_template_forums_table;
		$this->pft_template_entries_table = $pft_template_entries_table;
		$this->pft_template_images_table = $pft_template_images_table;
		$this->pft_entries_text = $pft_entries_text;
		$this->pft_entries_radio = $pft_entries_radio;
		$this->pft_entries_checkbox = $pft_entries_checkbox;
		$this->pft_entries_dropdown = $pft_entries_dropdown;
		$this->pft_entries_textnote = $pft_entries_textnote;

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

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                        => 'core_user_setup',
			'core.permissions'                       => 'permissions',
			'core.delete_forum_content_before_query' => 'delete_forum_content_before_query',
			'core.posting_modify_template_vars'      => 'posting_modify_template_vars',
			'core.posting_modify_message_text'          => 'posting_modify_message_text',
			'core.decode_message_before'             => 'decode_message_before',
		];
	}

	public function core_user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'toxyy/postformtemplates',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;

		$this->template->assign_vars([
			'PFT_ENTRIES_TEXT'      => PFT_ENTRIES_TEXT,
			'PFT_ENTRIES_RADIO'     => PFT_ENTRIES_RADIO,
			'PFT_ENTRIES_CHECKBOX'  => PFT_ENTRIES_CHECKBOX,
			'PFT_ENTRIES_DROPDOWN'  => PFT_ENTRIES_DROPDOWN,
			'PFT_ENTRIES_TEXTNOTE' => PFT_ENTRIES_TEXTNOTE,
		]);
	}

	public function permissions($event)
	{
		$categories = $event['categories'];
		$permissions = $event['permissions'];
		$types = $event['types'];
		$types['pft_'] = 'ACL_TYPE_PFT_';
		$categories['post_form_templates'] = 'ACL_CAT_PFT';
		$categories['pft_actions'] = 'ACL_CAT_PFT_ACTIONS';
		$permissions += [
			'a_pftauth'         => ['lang' => 'ACL_A_PFTAUTH', 'cat' => 'post_form_templates'],
			'a_pft_template'    => ['lang' => 'ACL_A_PFT_TEMPLATE', 'cat' => 'post_form_templates'],
			'a_pft_templateadd' => ['lang' => 'ACL_A_PFT_TEMPLATEADD', 'cat' => 'post_form_templates'],
			'a_pft_templatedel' => ['lang' => 'ACL_A_PFT_TEMPLATEDEL', 'cat' => 'post_form_templates'],
			'a_pft_images'      => ['lang' => 'ACL_A_PFT_IMAGES', 'cat' => 'post_form_templates'],
			'pft_view'          => ['lang' => 'ACL_PFT_TVIEW', 'cat' => 'pft_actions'],
			'pft_use'           => ['lang' => 'ACL_PFT_TUSE', 'cat' => 'pft_actions'],
		];
		$event['categories'] = $categories;
		$event['permissions'] = $permissions;
		$event['types'] = $types;
	}

	public function delete_forum_content_before_query($event)
	{
		$table_ary = $event['table_ary'];
		$table_ary[] = PFT_TEMPLATE_FORUMS_TABLE;
		$event['table_ary'] = $table_ary;
	}

	public function posting_modify_template_vars($event)
	{
		$mode = $event['mode'];
		$forum_id = $event['forum_id'];
		$s_action = $event['s_action'];

		$template_id = $this->request->variable('pft_template_id', 0);
		$parent_id = $this->request->variable('pft_parent_id', 0);

		$length_dropdown = $length_cat_dropdown = 0;
		$selected_length = strlen($this->language->lang('PFT_NO_TEMPLATE'));
		$selected_cat_length = strlen($this->language->lang('PFT_NO_CATEGORY'));

		if ($mode == 'post' || $mode == 'reply' || $mode == 'edit')
		{
			$sql = 'SELECT
				t1.template_id,
				t1.parent_id,
				t1.left_id,
				t1.template_name,
				t1.template_type,
				t1.template_status,
				CASE
				WHEN t1.parent_id = 0 THEN 0
				ELSE (
					SELECT COUNT(*)
					FROM ' . PFT_TEMPLATES_TABLE . ' t2
					WHERE t1.left_id > t2.left_id AND t1.left_id < t2.right_id
				)
				END AS nesting_level
			FROM ' . PFT_TEMPLATES_TABLE . ' t1
			JOIN ' . PFT_TEMPLATE_FORUMS_TABLE . " f ON t1.template_id = f.template_id
			WHERE f.forum_id IN (0, $forum_id)
			ORDER BY t1.left_id ASC";
			$result = $this->db->sql_query($sql);

			$add_children = $parent_id;
			$categories_array = $template_array = [];

			$auth2 = new \phpbb\auth\auth();
			$this->auth_admin_helper->acl($this->user->data, $auth2);
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($auth2->acl_get('pft_view', $row['template_id']))
				{
					if ($row['parent_id'] == 0)
					{
						if ($parent_id == $row['template_id'])
						{
							$add_children = $parent_id;
							$selected_cat_length = strlen($row['template_name']);
						}
						else
						{
							$add_children = false;
						}

						$length_cat_dropdown = strlen($row['template_name']) > $length_cat_dropdown ? strlen($row['template_name']) : $length_cat_dropdown;

						$categories_array = [
							'PARENT_ID' => $row['parent_id'],
							'ID'        => $row['template_id'],
							'NAME'      => $row['template_name'],
							'TYPE'      => $row['template_type'],
							'SELECTED'  => $parent_id == $row['template_id'],
							'DISABLED'  => $row['template_status'] == 1 || !$auth2->acl_get('pft_use', $row['template_id']),
							'U_SELECT'  => append_sid($s_action, "pft_parent_id=$row[template_id]"),
						];
						$this->template->assign_block_vars('pft_categories', $categories_array);

						if ($parent_id == 0)
						{
							$this->template->assign_var('PFT_NO_CATEGORY', true);
							$parent_id = $row['template_id'];
						}
					}

					if ($parent_id == $add_children && $row['parent_id'])
					{
						if ($template_id == $row['template_id'])
						{
							$template_id = $row['template_id'];
							$selected_length = strlen($row['template_name']);
						}

						$length_dropdown = strlen($row['template_name']) > $length_dropdown ? strlen($row['template_name']) : $length_dropdown;

						$template_array = [
							'PARENT_ID' => $row['parent_id'],
							'ID'        => $row['template_id'],
							'NAME'      => $row['template_name'],
							'TYPE'      => $row['template_type'],
							'SELECTED'  => $template_id == $row['template_id'],
							'DISABLED'  => $row['template_status'] == 1 || !$auth2->acl_get('pft_use', $row['template_id']),
							'LEVEL'     => $row['nesting_level'],
							'U_SELECT'  => append_sid($s_action, "pft_parent_id=$parent_id&amp;pft_template_id=$row[template_id]"),
						];
						$this->template->assign_block_vars('pft_templates', $template_array);
					}
				}
			}
			$this->db->sql_freeresult($result);
			unset($auth2);

			$sql = 'SELECT *
				FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . "
				WHERE template_id = $template_id
				ORDER BY left_id ASC";
			$result = $this->db->sql_query($sql);

			$entries_array = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($row['display_on_posting'])
				{
					$entry_tag_bitfield = '';
					$entry_tag = $row['entry_tag'];
					$entry_tag_parsed = generate_text_for_display($entry_tag, $row['entry_tag_uid'], $entry_tag_bitfield, OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES, true);
					// remove beginning and ending <br> tags for display
					$entry_tag_parsed = preg_replace('/^(<br\s*\/?>\n?)*|(<br\s*\/?>\n?)*$/i', '', $entry_tag_parsed);
					$entries_array = [
						'ID'             => $row['entry_id'],
						'PARENT_ID'      => $row['parent_id'],
						'ENTRY_TAG'      => $entry_tag_parsed,
						'ENTRY_MATCH'    => $row['entry_match'],
						'ENTRY_HELPLINE' => $row['entry_helpline'],
						'ENTRY_TYPE'     => $row['entry_type'],
						'ENTRY_ROWS'     => $row['entry_rows'],
						'DISPLAY'        => $row['display_on_posting'],
					];
					$this->template->assign_block_vars('pft_entries', $entries_array);

					$type_match_array = [];
					$entry_type_match = unserialize($row['entry_type_match']);
					foreach ($entry_type_match as $match)
					{
						$type_match_array = [
							'MATCH' => $match,
						];
						$this->template->assign_block_vars('pft_entries_type_match_' . $row['entry_id'], $type_match_array);
					}
				}
			}
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars([
			'PFT_MAX_ROW_LENGTH'     => str_repeat('&numsp;', abs($length_dropdown - $selected_length)),
			'PFT_MAX_CAT_ROW_LENGTH' => str_repeat('&numsp;', abs($length_cat_dropdown - $selected_cat_length)),
			'PFT_NO_TEMPLATE'        => $template_id == 0,
			'PFT_SUBMIT_BUTTON'      => $this->config['post_form_templates_add_post'],
			'PFT_HIDE_POSTFIELDS'    => $this->config['post_form_templates_hide_postfields']
		]);
	}

	public function posting_modify_message_text($event)
	{
		$message_parser = $event['message_parser'];
		if($event['submit'])
		{
			decode_message($message_parser->message);
		}
		$event['message_parser'] = $message_parser;
	}

	public function decode_message_before($event)
	{
		$message_text = $event['message_text'];
		$add_template = $this->request->is_set_post('pft_add_template');
		$submit = $this->request->is_set_post('post');

		if ($add_template || $submit)
		{
			$template_id = $this->request->variable('pft_template_id_post', 0);

			$sql = 'SELECT te.*, t.template_images, t.template_image_date, t.template_image_type
				FROM ' . PFT_TEMPLATE_ENTRIES_TABLE . ' te
				JOIN ' . PFT_TEMPLATES_TABLE . " t ON te.template_id = t.template_id
				WHERE te.template_id = $template_id
				ORDER BY left_id ASC";
			$result = $this->db->sql_query($sql);
	
			$entries_array = $template_images = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$entries_array[] = [
					'entry_id'         => $row['entry_id'],
					'parent_id'        => $row['parent_id'],
					'entry_tag'        => $row['entry_tag'],
					'entry_match'      => $row['entry_match'],
					'entry_type'       => $row['entry_type'],
					'entry_type_match' => unserialize($row['entry_type_match']),
					'display'          => $row['display_on_posting'],
				];
				$template_images = ($row['template_images'] === '') ? '' : (($row['template_images'] === '0') ? 0 : unserialize($row['template_images']));
				$template_image_date = str_replace(' ', '', $row['template_image_date']);
				$template_image_type = $row['template_image_type'];
			}
			$this->db->sql_freeresult($result);
			if ($template_images === 0)
			{
				$sql = 'SELECT image_id
					FROM ' . PFT_TEMPLATE_IMAGES_TABLE . '
					ORDER BY image_order ASC';
				$result = $this->db->sql_query($sql);
	
				$template_images = [];
				while ($row = $this->db->sql_fetchrow($result))
				{
					$template_images[] = $row['image_id'];
				}
				$this->db->sql_freeresult($result);
			}
	
			if (!empty($template_images))
			{
				$board_timezone = new \DateTimeZone($this->config['board_timezone']);
				$template_image_date = new \phpbb\datetime($this->user, $template_image_date, $board_timezone);
				$now_date = new \phpbb\datetime($this->user, 'now', $board_timezone);
				$date_diff = $now_date->diff($template_image_date, true)->format('%a');
				$image_count = count($template_images);
				$image_index = array_rand($template_images);
	
				if ($template_image_type == 0)
				{
					$image_index = $date_diff % $image_count;
				}
				else if ($template_image_type == 1)
				{
					$image_index = floor($date_diff / 7) % $image_count;
				}
	
				$image_id = $template_images[$image_index];
	
				$sql = 'SELECT image_url
					FROM ' . PFT_TEMPLATE_IMAGES_TABLE . "
					WHERE image_id = $image_id";
				$result = $this->db->sql_query($sql);
				$image_url = $this->db->sql_fetchfield('image_url');
				$this->db->sql_freeresult($result);
	
				$message_text .= "[img]{$this->pft_image_path}/{$image_url}[/img]" . PHP_EOL . PHP_EOL;
			}
	
			foreach ($entries_array as $entry)
			{
				$default_value = ($entry['entry_type'] == PFT_ENTRIES_CHECKBOX) ? [-1] : '';
				$replace = $this->request->variable('pft_e' . $entry['entry_id'], $default_value);
	
				$value = '';
				switch ($entry['entry_type'])
				{
					case PFT_ENTRIES_TEXTNOTE:
						continue 2;
					case PFT_ENTRIES_CHECKBOX:
						$value = [];
						$replace_array = $replace;
						$count = count($replace_array);
						for ($x = 0; $x < $count; $x++)
						{
							$replace = $entry['entry_type_match'][$replace_array[$x]];
							$value[] = str_replace('{TEXT}', $replace, $entry['entry_match']);
						}
						$value = implode($this->language->lang('PFT_SEPARATOR'), $value);
					break;
					case PFT_ENTRIES_RADIO:
					case PFT_ENTRIES_DROPDOWN:
						$replace = $entry['entry_type_match'][(int) $replace];
					case PFT_ENTRIES_TEXT:
					default:
						$value = str_replace('{TEXT}', $replace, $entry['entry_match']);
					break;
				}
	
				$message_text .= $entry['entry_tag'] . ' ' . $value . PHP_EOL;
			}
		}

		$event['message_text'] = $message_text;
	}
}
