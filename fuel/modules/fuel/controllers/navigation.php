<?php
require_once('module.php');

class Navigation extends Module {
	
	function __construct()
	{
		parent::__construct();
	}
	
	function items()
	{
		$this->load->module_model(FUEL_FOLDER, 'navigation_groups_model');
		if (!empty($this->filters['group_id'])) $this->filters['group_id']['options'] = $this->navigation_groups_model->options_list('id', 'name', array(), false);
		parent::items();
	}
	
	function upload()
	{
		$this->load->library('form_builder');
		$this->load->module_model(FUEL_FOLDER, 'navigation_groups_model');
		$this->load->module_model(FUEL_FOLDER, 'navigation_model');
		
		$this->js_controller_params['method'] = 'upload';
		
		if (!empty($_POST))
		{
			$this->load->library('menu');
			
			if (!empty($_FILES['file']['name']))
			{
				$error = FALSE;
				$file_info = $_FILES['file'];
				@include($file_info['tmp_name']);
				if (!empty($nav))
				{
					$nav = $this->menu->normalize_items($nav);
					$group_id = $this->input->post('group_id');
					if (is_true_val($this->input->post('clear_first')))
					{
						$this->navigation_model->delete(array('group_id' => $this->input->post('group_id')));
					}
					
					// save navigation group
					$group = $this->navigation_groups_model->find_by_key($this->input->post('group_id'));
					if (!isset($group->id))
					{
						$save['name'] = 'main';
						$id = $this->navigation_groups_model->save($save);
						$group_id = $id;
					}
					// convert string ids to numbers so we can save
					$ids = array();
					$i = 1;
					foreach($nav as $item)
					{
						$ids[$item['id']] = $i;
						$i++;
					}
					
					// now loop through and save
					$cnt = 0;
					foreach($nav as $key => $item)
					{
						$save = array();
						$save['id'] = $ids[$item['id']];
						$save['nav_key'] = $key;
						$save['group_id'] = $group_id;
						$save['label'] = $item['label'];
						$save['parent_id'] = (empty($ids[$item['parent_id']])) ? 0 : $ids[$item['parent_id']];
						$save['location'] = $item['location'];
						$save['selected'] = (!empty($item['selected'])) ? $item['selected'] : $item['active']; // must be different because "active" has special meaning in FUEL
						$save['hidden'] = (is_true_val($item['hidden'])) ? 'yes' : 'no';
						$save['published'] = 'yes';
						$save['precedence'] = $cnt;
						if (is_array($item['attributes']))
						{
							$attr = '';
							foreach($item['attributes'] as $key => $val)
							{
								$attr .= $key .'="'.$val.'" ';
							}
							$attr = trim($attr);
						}
						else
						{
							$save['attributes'] = $item['attributes'];
						}
						if (!$this->navigation_model->save($save))
						{
							$error = TRUE;
							break;
						}
						$cnt++;
					}
				}
				
				if ($error)
				{
					add_error(lang('error_nav_upload'));
				}
				else
				{
					$this->session->set_flashdata('success', lang('success_nav_upload'));
					redirect($this->uri->uri_string());
				}
				
			}
			else
			{
				add_error(lang('error_nav_upload'));
			}
		}
		
		$fields = array();
		$nav_groups = $this->navigation_groups_model->options_list('id', 'name', array('published' => 'yes'));
		if (empty($nav_groups)) $nav_groups = array('1' => 'main');
		
		$fields['group_id'] = array('type' => 'select', 'options' => $nav_groups, 'label' => 'Navigation Group');
		$fields['file'] = array('type' => 'file');
		$fields['clear_first'] = array('type' => 'enum', 'options' => array('yes' => 'yes', 'no' => 'no'));
		$this->form_builder->set_fields($fields);
		$this->form_builder->submit_value = '';
		$this->form_builder->use_form_tag = FALSE;
		$vars['form'] = $this->form_builder->render();
		$this->_render('navigation_upload', $vars);
	}
	
}