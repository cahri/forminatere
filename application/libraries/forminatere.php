<?php

	class forminatere {

		protected $current;
		protected $table;
		protected $config;
		protected $model;
		protected $id;
		protected $fields;

		public function __construct($params) {
			$CI =& get_instance();
			$CI->load->config('forminatere');
			$CI->load->helper('form');
			$CI->load->helper('forminatere');
			$this->table = $params['table'];
			$this->config = $CI->config->item('forminatere');
			$model = $this->get_model_name();
			$CI->load->model($model);
			$this->model = $CI->$model;
			if (isset($this->config[$this->table]) && is_array($this->config[$this->table])) {
				foreach($this->config[$this->table] as $k => $v) {
					if (isset($v['type']) && $v['type'] == 'date') {
						$this->set_config($k, 'mask', '99/99/9999');
					}
				}
			}
		}

		private function get_model_name($table = null) {
			if (is_null($table)) $table = $this->table;
			return substr($table, 0, -1).'_model';
		}

		public function get($id) {
			$this->id = $id;
			$this->current = $this->model->get($id);
			return $this->current;
		}
		public function set_current($data) {
			$this->current = $data;
		}

		public function set_fields($fields) {
			$this->fields = $fields;
		}

		public function get_fields() {
			return $this->fields;
		}

		public function set_config($field, $key, $value) {
			$this->config[$this->table][$field][$key] = $value;
		}

		public function get_config($field, $key, $default = '') {
			return (isset($this->config[$this->table][$field][$key])) ? $this->config[$this->table][$field][$key] : $default;
		}
		public function get_value($field) {
			$field_value = '';
			if (isset($this->config[$this->table][$field]['default'])) $field_value = $this->config[$this->table][$field]['default'];
			if (isset($this->current->$field) && $this->current->$field) $field_value = $this->current->$field;
			return $field_value;
		}

		public function input($field) {
			$attrs = array('id' => $field, 'name' => $field);;
			$config = (isset($this->config[$this->table][$field])) ? $this->config[$this->table][$field] : array();
			if (isset($config['class'])) $attrs['class'] = $config['class'];
			if (isset($config['mask'])) $attrs['data-mask'] = $config['mask'];
			$field_type = $this->get_config($field, 'type', 'text');

			$field_value = $this->get_value($field);

			switch ($field_type) {
				case 'select':
					return form_dropdown($field, array('' => '-- Choisissez --') + sinon($config['values'], array()), $field_value, 'id="'.$field.'"');
				case 'password':
					return form_password($attrs);
				case 'textarea':
					$attrs['value'] = $field_value;
					return form_textarea($attrs);
				case 'noinput':
				case 'static':
					if ($values = $this->get_config($field, 'values')) {
						return '<span class="no-input">'.nl2br($values[$field_value]).'</span>';
					} else {
						return '<span class="no-input">'.nl2br($field_value).'</span>';
					}
				default:
					$attrs['value'] = $field_value;
					return form_input($attrs);
			}
		}

		public function insert($data, $extra = array()) {
			$data = $this->sanitize_input($data) + $extra;
			return $this->model->insert($data);
		}

		public function update($data, $id = 0, $extra = array()) {
			$data = $this->sanitize_input($data) + $extra;
			$id = (int)$id;
			if ($id == 0) $id = $this->id;
			return $this->model->update($id, $data);
		}

		private function sanitize_input($data) {
			$fields = $this->get_fields();
			foreach($fields as $k => $field) {
				if ($this->get_config($field, 'type') == 'noinput' || $this->get_config($field, 'type') == 'static') unset($fields[$k]);
			}
			foreach($data as $k => $v) {
				if (!in_array($k, $fields)) {
					unset($data[$k]);
					continue;
				}
				if (empty($data[$k])) $data[$k] = NULL;
			}
			return $data;
		}

		public function get_linked_data($table, $where, $label, $order = null) {
			if (is_null($label)) $label = $order;
			$CI =& get_instance();
			$model = $this->get_model_name($table);
			$CI->load->model($model);

			$result = array();

			foreach ($CI->$model->order_by($order)->get_many_by($where) as $row) {
				$result[$row->{$CI->$model->primary_key}] = $row->$label;
			}

			return $result;
		}

	}