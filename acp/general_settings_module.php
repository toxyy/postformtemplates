<?php
/**
 *
 * @package       phpBB Extension - Post Form Templates
 * @copyright (c) 2023 toxyy <thrashtek@yahoo.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace toxyy\postformtemplates\acp;

class general_settings_module
{
	protected $phpbb_container;
	/** @var \phpbb\request\request $request */
	protected $request;
	/** @var \phpbb\language\language $language */
	protected $language;
	/** @var \phpbb\template\template $template */
	protected $template;
	/** @var \phpbb\config\config $config */
	protected $config;

	public $u_action;
	public $tpl_name;
	public $page_title;

	public function __construct()
	{
		global $phpbb_container;

		$this->phpbb_container = $phpbb_container;
		/** @var \phpbb\request\request $request */
		$this->request = $this->phpbb_container->get('request');
		/** @var \phpbb\language\language $language */
		$this->language = $this->phpbb_container->get('language');
		/** @var \phpbb\template\template $template */
		$this->template = $this->phpbb_container->get('template');
		/** @var \phpbb\config\config $config */
		$this->config = $this->phpbb_container->get('config');
	}

	public function main($id, $mode)
	{
		$this->tpl_name = 'acp_general_settings';
		$this->page_title = $this->language->lang('ACP_PFT_GENERAL_SETTINGS_TITLE');

		$form_key = 'acp_pfm_general';
		add_form_key($form_key);

		if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('acp_pfm_general'))
            {
                 trigger_error('FORM_INVALID');
            }

            $this->config->set('post_form_templates_add_post', $this->request->variable('post_form_templates_add_post', 0));
            $this->config->set('post_form_templates_hide_postfields', $this->request->variable('post_form_templates_hide_postfields', 0));
            trigger_error($this->language->lang('ACP_PFT_SETTINGS_SAVED') . adm_back_link($this->u_action));
        }

        $this->template->assign_vars([
            'ACP_PFT_ADD_POST'        => $this->config['post_form_templates_add_post'],
            'ACP_PFT_HIDE_POSTFIELDS' => $this->config['post_form_templates_hide_postfields'],
            'U_ACTION'                => $this->u_action,
        ]);
	}
}
