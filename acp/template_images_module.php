<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class template_images_module
{
	/** @var \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container */
	protected $phpbb_container;
	/** @var \phpbb\cache\driver\driver_interface $cache */
	protected $cache;
	/** @var \phpbb\db\driver\driver_interface $db */
	protected $db;
	/** @var \phpbb\request\request $request */
	protected $request;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\template\template $template */
	protected $template;
	/** @var \phpbb\config\config $config */
	protected $config;
	/** @var \phpbb\textformatter\s9e\factory $textformatter_cache */
	protected $textformatter_cache;
	/** @var \phpbb\pagination $pagination */
	protected $pagination;
	/** @var string */
	protected $phpbb_root_path;
	/** @var string */
	protected $image_path;

	public $u_action;
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
		/** @var \phpbb\language\language $language */
		$this->language = $this->phpbb_container->get('language');
		/** @var \phpbb\template\template $template */
		$this->template = $this->phpbb_container->get('template');
		/** @var \phpbb\config\config $config */
		$this->config = $this->phpbb_container->get('config');
		/** @var \phpbb\textformatter\s9e\factory $textformatter_cache */
		$this->textformatter_cache = $this->phpbb_container->get('text_formatter.cache');
		/** @var \phpbb\pagination $pagination */
		$this->pagination = $this->phpbb_container->get('pagination');
		/** @var string */
		$this->phpbb_root_path = $this->phpbb_container->getParameter('core.root_path');
		/** @var string */
		$this->image_path = $this->phpbb_container->getParameter('toxyy.postformtemplates.paths.pft_image_path');

		if (!defined('PFT_TEMPLATES_TABLE'))
		{
			define('PFT_TEMPLATES_TABLE', $this->phpbb_container->getParameter('toxyy.postformtemplates.tables.pft_templates_table'));
		}
		if (!defined('PFT_TEMPLATE_IMAGES_TABLE'))
		{
			define('PFT_TEMPLATE_IMAGES_TABLE', $this->phpbb_container->getParameter('toxyy.postformtemplates.tables.pft_template_images_table'));
		}
	}

	function main($id, $mode)
	{
		$this->language->add_lang('acp/posting');

		// Set up general vars
		$action = $this->request->variable('action', '');
		$action = (isset($_POST['add'])) ? 'add' : $action;
		$action = (isset($_POST['edit'])) ? 'edit' : $action;
		$image_id = $this->request->variable('id', 0);

		$form_key = 'acp_icons';
		add_form_key($form_key);

		$mode = 'manage_images';

		$this->tpl_name = 'acp_template_images';

		$table = PFT_TEMPLATE_IMAGES_TABLE;
		$lang = 'SMILIES';
		$fields = 'image';
		$img_path = 'ext/toxyy/postformtemplates/images';
		$images_per_page = 100;

		$this->page_title = 'ACP_' . $lang;

		// Clear some arrays
		$_images = [];
		$notice = '';

		// Grab file list of paks and images
		if ($action == 'edit' || $action == 'add' || $action == 'import')
		{
			$imglist = filelist($this->phpbb_root_path . $img_path, '');

			foreach ($imglist as $path => $img_ary)
			{
				if (empty($img_ary))
				{
					continue;
				}

				asort($img_ary, SORT_STRING);

				foreach ($img_ary as $img)
				{
					$_images[$path . $img]['file'] = $path . $img;
				}
			}
			unset($imglist);
		}

		// What shall we do today? Oops, I believe that's trademarked ...
		switch ($action)
		{
			case 'edit':
				unset($_images);
				$_images = [];

			// no break;

			case 'add':

				$images = $default_row = [];
				$image_options = '';

				if ($action == 'add' && $mode == 'manage_images')
				{
					$sql = 'SELECT *
						FROM ' . PFT_TEMPLATE_IMAGES_TABLE . '
						ORDER BY image_order';
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						if (empty($images[$row['image_url']]))
						{
							$images[$row['image_url']] = $row;
						}
					}
					$this->db->sql_freeresult($result);

					if (count($images))
					{
						foreach ($images as $row)
						{
							$selected = false;

							if (!$image_options)
							{
								$selected = true;
								$default_row = $row;
							}
							$image_options .= '<option value="' . $row['image_url'] . '"' . (($selected) ? ' selected="selected"' : '') . '>' . $row['image_url'] . '</option>';

							$this->template->assign_block_vars('image', [
								'IMAGE_URL' => addslashes($row['image_url']),
								'ORDER'     => $row['image_order'] + 1,
							]);
						}
					}
				}

				$sql = "SELECT *
					FROM $table
					ORDER BY {$fields}_order " . (($image_id || $action == 'add') ? 'DESC' : 'ASC');
				$result = $this->db->sql_query($sql);

				$data = [];
				$after = false;
				$order_lists = ['', ''];
				$add_order_lists = ['', ''];
				$display_count = 0;

				while ($row = $this->db->sql_fetchrow($result))
				{
					if ($action == 'add')
					{
						unset($_images[$row[$fields . '_url']]);
					}

					if ($row[$fields . '_id'] == $image_id)
					{
						$after = true;
						$data[$row[$fields . '_url']] = $row;
					}
					else
					{
						if ($action == 'edit' && !$image_id)
						{
							$data[$row[$fields . '_url']] = $row;
						}

						$selected = '';
						if (!empty($after))
						{
							$selected = ' selected="selected"';
							$after = false;
						}
						$display_count++;
						$after_txt = $row['image_url'];
						$order_lists[1] = '<option value="' . ($row[$fields . '_order'] + 1) . '"' . $selected . '>' . sprintf($this->language->lang('AFTER_' . $lang), ' -&gt; ' . $after_txt) . '</option>' . $order_lists[1];

						if (!empty($default_row))
						{
							$add_order_lists[1] = '<option value="' . ($row[$fields . '_order'] + 1) . '"' . (($row[$fields . '_id'] == $default_row['image_id']) ? ' selected="selected"' : '') . '>' . sprintf($this->language->lang('AFTER_' . $lang), ' -&gt; ' . $after_txt) . '</option>' . $order_lists[1];
						}
					}
				}
				$this->db->sql_freeresult($result);

				$order_list = '<option value="1"' . ((!isset($after)) ? ' selected="selected"' : '') . '>' . $this->language->lang('FIRST') . '</option>';
				$add_order_list = '<option value="1">' . $this->language->lang('FIRST') . '</option>';

				if ($action == 'add')
				{
					$data = $_images;
				}

				$colspan = (($mode == 'manage_images') ? 7 : 6);
				$colspan += ($image_id) ? 1 : 0;
				$colspan += ($action == 'add') ? 2 : 0;

				$this->template->assign_vars([
					'S_EDIT'    => true,
					'S_SMILIES' => ($mode == 'manage_images') ? true : false,
					'S_ADD'     => ($action == 'add') ? true : false,

					'S_ORDER_LIST_DISPLAY'       => $order_list . $order_lists[1],
					'S_ORDER_LIST_UNDISPLAY'     => $order_list . $order_lists[1],
					'S_ORDER_LIST_DISPLAY_COUNT' => $display_count + 1,

					'L_TITLE'    => $this->language->lang('ACP_' . $lang),
					'L_EXPLAIN'  => $this->language->lang('ACP_' . $lang . '_EXPLAIN'),
					'L_CONFIG'   => $this->language->lang($lang . '_CONFIG'),
					'L_URL'      => $this->language->lang($lang . '_URL'),
					'L_LOCATION' => $this->language->lang($lang . '_LOCATION'),
					'L_ORDER'    => $this->language->lang($lang . '_ORDER'),
					'L_NO_ICONS' => $this->language->lang('NO_' . $lang . '_' . strtoupper($action)),

					'COLSPAN' => $colspan,
					'ID'      => $image_id,

					'U_BACK'   => $this->u_action,
					'U_ACTION' => $this->u_action . '&amp;action=' . (($action == 'add') ? 'create' : 'modify'),
				]);

				foreach ($data as $img => $img_row)
				{
					$this->template->assign_block_vars('items', [
						'IMG'     => $img,
						'A_IMG'   => addslashes($img),
						'IMG_SRC' => $this->phpbb_root_path . $img_path . '/' . $img,

						'S_ID'            => (isset($img_row[$fields . '_id'])) ? true : false,
						'ID'              => (isset($img_row[$fields . '_id'])) ? $img_row[$fields . '_id'] : 0,
						'TEXT_ALT'        => $img,
						'ALT'             => '',
						'POSTING_CHECKED' => ($action == 'add') ? ' checked="checked"' : '',
					]);
				}

				// Ok, another row for adding an addition code for a pre-existing image...
				if ($action == 'add' && $mode == 'manage_images' && count($images))
				{
					$this->template->assign_vars([
						'S_ADD_CODE' => true,

						'S_IMG_OPTIONS' => $image_options,

						'S_ADD_ORDER_LIST_DISPLAY'   => $add_order_list . $add_order_lists[1],
						'S_ADD_ORDER_LIST_UNDISPLAY' => $add_order_list . $add_order_lists[0],

						'IMG_SRC'  => $this->phpbb_root_path . $img_path . '/' . $default_row['image_url'],
						'IMG_PATH' => $img_path,
					]);
				}

				return;

			break;

			case 'create':
			case 'modify':

				if (!check_form_key($form_key))
				{
					trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
				}

				// Get items to create/modify
				$images = (isset($_POST['image'])) ? array_keys($this->request->variable('image', ['' => 0])) : [];

				// Now really get the items
				$image_id = (isset($_POST['id'])) ? $this->request->variable('id', ['' => 0]) : [];
				$image_order = (isset($_POST['order'])) ? $this->request->variable('order', ['' => 0]) : [];
				$image_add = (isset($_POST['add_img'])) ? $this->request->variable('add_img', ['' => 0]) : [];

				// Ok, add the relevant bits if we are adding new images that already exist...
				if ($this->request->variable('add_additional_image', false, false, \phpbb\request\request_interface::POST))
				{
					$add_image = $this->request->variable('add_image', '');

					if ($add_image)
					{
						$images[] = $add_image;
						$image_add[$add_image] = true;

						$image_order[$add_image] = $this->request->variable('add_order', 0);
					}
				}

				if ($mode == 'manage_images' && $action == 'create')
				{
					$image_count = $this->item_count($table);

					$addable_images_count = count($images);
					foreach ($images as $image)
					{
						if (!isset($image_add[$image]))
						{
							--$addable_images_count;
						}
					}

					if ($image_count + $addable_images_count > SMILEY_LIMIT)
					{
						trigger_error($this->language->lang('TOO_MANY_SMILIES', SMILEY_LIMIT) . adm_back_link($this->u_action), E_USER_WARNING);
					}
				}

				$images_updated = 0;
				$errors = [];

				foreach ($images as $image)
				{
					if ($action == 'create' && !isset($image_add[$image]))
					{
						// skip images where add wasn't checked
					}
					else if (!file_exists($this->phpbb_root_path . $img_path . '/' . $image))
					{
						$errors[$image] = 'SMILIE_NO_FILE';
					}
					else
					{

						$img_sql = [
							$fields . '_url' => $image,
						];

						// Image_order holds the 'new' order value
						if (!empty($image_order[$image]))
						{
							$img_sql = array_merge($img_sql, [
								$fields . '_order' => $image_order[$image],
							]);

							// Since we always add 'after' an item, we just need to increase all following + the current by one
							$sql = "UPDATE $table
								SET {$fields}_order = {$fields}_order + 1
								WHERE {$fields}_order >= {$image_order[$image]}";
							$this->db->sql_query($sql);

							// If we adjust the order, we need to adjust all other orders too - they became inaccurate...
							foreach ($image_order as $_image => $_order)
							{
								if ($_image == $image)
								{
									continue;
								}

								if ($_order >= $image_order[$image])
								{
									$image_order[$_image]++;
								}
							}
						}

						if ($action == 'modify' && !empty($image_id[$image]))
						{
							$sql = "UPDATE $table
								SET " . $this->db->sql_build_array('UPDATE', $img_sql) . "
								WHERE {$fields}_id = " . $image_id[$image];
							$this->db->sql_query($sql);
							$images_updated++;
						}
						else if ($action !== 'modify')
						{
							$sql = "INSERT INTO $table " . $this->db->sql_build_array('INSERT', $img_sql);
							$this->db->sql_query($sql);
							$images_updated++;
						}

					}
				}

				$this->cache->destroy('sql', $table);

				$level = ($images_updated) ? E_USER_NOTICE : E_USER_WARNING;
				$errormsgs = '';
				foreach ($errors as $img => $error)
				{
					$errormsgs .= '<br />' . sprintf($this->language->lang($error), $img);
				}
				if ($action == 'modify')
				{
					trigger_error($this->language->lang($lang . '_EDITED', $images_updated) . $errormsgs . adm_back_link($this->u_action), $level);
				}
				else
				{
					trigger_error($this->language->lang($lang . '_ADDED', $images_updated) . $errormsgs . adm_back_link($this->u_action), $level);
				}

			break;

			case 'delete':

				if (confirm_box(true))
				{
					$sql = "DELETE FROM $table
						WHERE {$fields}_id = $image_id";
					$this->db->sql_query($sql);

					$sql = "SELECT template_id, template_images
						FROM " . PFT_TEMPLATES_TABLE . "
						WHERE template_images <> ''";
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						$template_id = $row['template_id'];
						$template_images = unserialize($row['template_images']);
						if (($key = array_search($image_id, $template_images)) !== false)
						{
							unset($template_images[$key]);
							$template_images = array_values($template_images);
							$this->db->sql_query('UPDATE ' . PFT_TEMPLATES_TABLE . "
								SET template_images = '" . serialize($template_images) . "'
								WHERE template_id = $template_id");
						}
					}
					$this->db->sql_freeresult($result);

					$notice = $this->language->lang($lang . '_DELETED');

					$this->cache->destroy('sql', $table);

					if ($this->request->is_ajax())
					{
						$json_response = new \phpbb\json_response;
						$json_response->send([
							'MESSAGE_TITLE' => $this->language->lang('INFORMATION'),
							'MESSAGE_TEXT'  => $notice,
							'REFRESH_DATA'  => [
								'time' => 3,
							],
						]);
					}
				}
				else
				{
					confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
						'id'     => $image_id,
						'i'      => $id,
						'mode'   => $mode,
						'action' => 'delete',
					]));
				}

			break;

			case 'move_up':
			case 'move_down':

				if (!check_link_hash($this->request->variable('hash', ''), 'acp_icons'))
				{
					trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
				}

				// Get current order id...
				$sql = "SELECT {$fields}_order as current_order
					FROM $table
					WHERE {$fields}_id = $image_id";
				$result = $this->db->sql_query($sql);
				$current_order = (int) $this->db->sql_fetchfield('current_order');
				$this->db->sql_freeresult($result);

				if ($current_order == 0 && $action == 'move_up')
				{
					break;
				}

				// on move_down, switch position with next order_id...
				// on move_up, switch position with previous order_id...
				$switch_order_id = ($action == 'move_down') ? $current_order + 1 : $current_order - 1;

				$sql = "SELECT {$fields}_id as image_id
					FROM $table
					WHERE {$fields}_order = $switch_order_id";
				$result = $this->db->sql_query($sql);
				$switch_image_id = (int) $this->db->sql_fetchfield('image_id');
				$this->db->sql_freeresult($result);

				//
				$sql = "UPDATE $table
					SET {$fields}_order = $current_order
					WHERE {$fields}_order = $switch_order_id
						AND {$fields}_id <> $image_id";
				$this->db->sql_query($sql);
				$move_executed = (bool) $this->db->sql_affectedrows();

				// Only update the other entry too if the previous entry got updated
				if ($move_executed)
				{
					$sql = "UPDATE $table
						SET {$fields}_order = $switch_order_id
						WHERE {$fields}_order = $current_order
							AND {$fields}_id = $image_id";
					$this->db->sql_query($sql);

					$sql = "SELECT template_id, template_images
						FROM " . PFT_TEMPLATES_TABLE . "
						WHERE template_images <> ''";
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						$template_id = $row['template_id'];
						$template_images = unserialize($row['template_images']);
						if (($key = array_search($image_id, $template_images)) !== false)
						{
							if ($action == 'move_up' && isset($template_images[$key - 1]))
							{
								$template_image = $template_images[$key];
								$previous_image = $template_images[$key - 1];

								if ($previous_image == $switch_image_id)
								{
									$template_images[$key] = $previous_image;
									$template_images[$key - 1] = $template_image;
								}
							}
							else if (isset($template_images[$key + 1]))
							{
								$template_image = $template_images[$key];
								$next_image = $template_images[$key + 1];

								if ($next_image == $switch_image_id)
								{
									$template_images[$key] = $next_image;
									$template_images[$key + 1] = $template_image;
								}
							}
							$template_images = array_values($template_images);
							$this->db->sql_query('UPDATE ' . PFT_TEMPLATES_TABLE . "
								SET template_images = '" . serialize($template_images) . "'
								WHERE template_id = $template_id");
						}
					}
					$this->db->sql_freeresult($result);
				}

				$this->cache->destroy('sql', $table);

				if ($this->request->is_ajax())
				{
					$json_response = new \phpbb\json_response;
					$json_response->send([
						'success' => $move_executed,
					]);
				}

			break;
		}

		// By default, check that image_order is valid and fix it if necessary
		$sql = "SELECT {$fields}_id AS order_id, {$fields}_order AS fields_order
			FROM $table
			ORDER BY {$fields}_order";
		$result = $this->db->sql_query($sql);

		if ($row = $this->db->sql_fetchrow($result))
		{
			$order = 0;
			do
			{
				++$order;
				if ($row['fields_order'] != $order)
				{
					$this->db->sql_query("UPDATE $table
						SET {$fields}_order = $order
						WHERE {$fields}_id = " . $row['order_id']);
				}
			} while ($row = $this->db->sql_fetchrow($result));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'L_TITLE'         => $this->language->lang('ACP_' . $lang),
			'L_EXPLAIN'       => $this->language->lang('ACP_' . $lang . '_EXPLAIN'),
			'L_IMPORT'        => $this->language->lang('IMPORT_' . $lang),
			'L_EXPORT'        => $this->language->lang('EXPORT_' . $lang),
			'L_NOT_DISPLAYED' => $this->language->lang($lang . '_NOT_DISPLAYED'),
			'L_ICON_ADD'      => $this->language->lang('ADD_' . $lang),
			'L_ICON_EDIT'     => $this->language->lang('EDIT_' . $lang),

			'NOTICE'  => $notice,
			'COLSPAN' => ($mode == 'manage_images') ? 5 : 3,

			'S_SMILIES' => ($mode == 'manage_images') ? true : false,

			'U_ACTION' => $this->u_action,
		]);

		$pagination_start = $this->request->variable('start', 0);

		$item_count = $this->item_count($table);

		$sql = "SELECT *
			FROM $table
			ORDER BY {$fields}_order ASC";
		$result = $this->db->sql_query_limit($sql, $images_per_page, $pagination_start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('items', [
				'ALT_TEXT'    => $row['image_url'],
				'IMG_SRC'     => $this->phpbb_root_path . $img_path . '/' . $row[$fields . '_url'],
				'U_EDIT'      => $this->u_action . '&amp;action=edit&amp;id=' . $row[$fields . '_id'],
				'U_DELETE'    => $this->u_action . '&amp;action=delete&amp;id=' . $row[$fields . '_id'],
				'U_MOVE_UP'   => $this->u_action . '&amp;action=move_up&amp;id=' . $row[$fields . '_id'] . '&amp;start=' . $pagination_start . '&amp;hash=' . generate_link_hash('acp_icons'),
				'U_MOVE_DOWN' => $this->u_action . '&amp;action=move_down&amp;id=' . $row[$fields . '_id'] . '&amp;start=' . $pagination_start . '&amp;hash=' . generate_link_hash('acp_icons'),
			]);
		}
		$this->db->sql_freeresult($result);

		$this->pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $item_count, $images_per_page, $pagination_start);
	}

	/**
	 * Returns the count of images or icons in the database
	 *
	 * @param string $table The table of items to count.
	 * @return int number of items
	 */
	/* private */
	function item_count($table)
	{
		$sql = "SELECT COUNT(*) AS item_count
			FROM $table";
		$result = $this->db->sql_query($sql);
		$item_count = (int) $this->db->sql_fetchfield('item_count');
		$this->db->sql_freeresult($result);

		return $item_count;
	}
}
