<?php 
/**
 * FUEL CMS
 * http://www.getfuelcms.com
 *
 * An open source Content Management System based on the 
 * Codeigniter framework (http://codeigniter.com)
 *
 * @package		FUEL CMS
 * @author		David McReynolds @ Daylight Studio
 * @copyright	Copyright (c) 2011, Run for Daylight LLC.
 * @license		http://www.getfuelcms.com/user_guide/general/license
 * @link		http://www.getfuelcms.com
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * FUEL Helper
 *
 * @package		FUEL CMS
 * @subpackage	Libraries
 * @category	Libraries
 * @author		David McReynolds @ Daylight Studio
 * @link		http://www.getfuelcms.com/user_guide/helpers/fuel_helper
 */

// --------------------------------------------------------------------

/**
 * Returns the instance of the FUEL object
 *
 * @access	public
 * @param	mixed
 * @return	string
 */
function &fuel_instance()
{
	return Fuel::get_instance();
}

function &FUEL()
{
	$f = & fuel_instance();
	return $f;
}

// --------------------------------------------------------------------

/**
 * Allows you to load a view and pass data to it
 *
 * @access	public
 * @param	mixed
 * @return	string
 */
function fuel_block($params)
{
	$CI =& get_instance();
	return $CI->fuel->blocks->render($params);
}

// --------------------------------------------------------------------

/**
 * Creates a menu structure
 *
 * @access	public
 * @param	mixed
 * @return	string
 */
function fuel_nav($params = array())
{
	$CI =& get_instance();
	return $CI->fuel->navigation->render($params);
}

// --------------------------------------------------------------------

/**
 * Generates a page using the Fuel_page class
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	array
 * @return	string
 */
function fuel_page($location, $vars = array(), $params = array())
{
	$CI =& get_instance();
	return $CI->fuel->pages->render($location, $vars, $params, TRUE);
}

// --------------------------------------------------------------------

/**
 * Creates a form using form builder
 *
 * @access	public
 * @param	mixed
 * @param	array
 * @param	array
 * @return	string
 */
function fuel_form($fields, $values = array(), $params = array())
{
	$CI =& get_instance();
	
	// if a string is provided instead of array, we will assume it is a model
	if (is_string($fields))
	{
		$model = $fields;
		if (substr($model, strlen($model) - 6) != '_model')
		{
			$model = $model.'_model';
		}
		$CI->load->model($model);
		
		// check if the model has a form_fields method on it first
		if (!method_exists('form_fields', $CI->$model))
		{
			return '';
		}
		$fields = $CI->$model->form_fields();
	}
	$CI->load->library('form_builder', $params);
	$CI->form_builder->set_fields($fields);
	$CI->form_builder->set_field_values($values);
	return $CI->form_builder->render();
}

// --------------------------------------------------------------------

/**
 * Loads a module model and creates a variable in the view that you can use to merge data 
 *
 * @access	public
 * @param	string
 * @param	mixed
 * @return	string
 */
function fuel_model($module, $params = array())
{
	$CI =& get_instance();
	return $CI->fuel->modules($module)->find($params);
}

// --------------------------------------------------------------------

/**
 * Sets a variable for all views to use no matter what view it is declared in
 *
 * @access	public
 * @param	string
 * @param	array
 * @return	string
 */
function fuel_set_var($key, $val = NULL)
{
	$CI =& get_instance();
	if (is_array($key))
	{
		$CI->load->vars($key);
	}
	else if (is_string($key))
	{
		$vars[$key] = $val;
		$CI->load->vars($vars);
	}
}

// --------------------------------------------------------------------

/**
 * Appends a value to an array variable
 *
 * @access	public
 * @param	string
 * @param	mixed
 * @return	void
 */
function fuel_var_append($key, $value)
{
	$CI =& get_instance();
	$vars = $CI->load->get_vars();
	if (isset($vars[$key]) AND is_array($vars[$key]))
	{
		if (is_array($value))
		{
			$vars[$key] = array_merge($vars[$key], $value);
		}
		else
		{
			array_push($vars[$key], $value);
		}
		fuel_set_var($key, $vars[$key]);
	}
}

// --------------------------------------------------------------------

/**
 * Returns a variable and allows for a default value
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	string
 * @param	boolean
 * @return	string
 */
function fuel_var($key, $default = '', $edit_module = 'pagevariables', $evaluate = TRUE)
{
	$CI =& get_instance();
	$CI->load->helper('inflector');
	if (isset($GLOBALS[$key]))
	{
		$val = $GLOBALS[$key];
	}
	else if ($CI->load->get_var($key))
	{
		$val = $CI->load->get_var($key);
	}
	else
	{
		$val = $default;
	}
	
	if (is_string($val) AND $evaluate)
	{
		$val = eval_string($val);
	}
	else if (is_array($val) AND $evaluate)
	{
		foreach($val as $k => $v)
		{
			$val[$k] = eval_string($v);
		}
	}
	
	if ($edit_module === TRUE) $edit_module = 'pagevariables';
	if (!empty($edit_module) AND $CI->fuel->config('fuel_mode') != 'views' AND !defined('USE_FUEL_MARKERS') OR (defined('USE_FUEL_MARKERS') AND USE_FUEL_MARKERS))
	{
		$marker = fuel_edit($key, humanize($key), $edit_module);
	}
	else
	{
		$marker = '';
	}
	
	if (is_string($val))
	{
		return $marker.$val;
	}
	else
	{
		if (!empty($marker))
		{
			// used to help with javascript positioning
			$marker = '<span>'.$marker.'</span>';
		}
		return $marker;
	}
}

// --------------------------------------------------------------------

/**
 * Sets a variable marker in a layout which can be used in editing mode
 *
 * @access	public
 * @param	mixed
 * @param	string
 * @param	string
 * @param	int
 * @param	int
 * @return	string
 */
function fuel_edit($id, $label = NULL, $module = 'pagevariables', $xoffset = NULL, $yoffset = NULL)
{
	$CI =& get_instance();
	$page = $CI->fuel->pages->active();
	if (empty($page))
	{
		$page = $CI->fuel->pages->create();
	}
	if (!empty($id) AND (!defined('FUELIFY') OR defined('FUELIFY') AND FUELIFY !== FALSE))
	{
		$marker['id'] = $id;
		$marker['label'] = $label;
		$marker['module'] = $module;
		$marker['xoffset'] = $xoffset;
		$marker['yoffset'] = $yoffset;

		$key = $page->add_marker($marker);

		return '<!--'.$key.'-->';
	}
	return '';
}

// --------------------------------------------------------------------

/**
 * Creates the cache ID for the FUEL page based on the URI
 *
 * @access	public
 * @param	string
 * @return	string
 */
function fuel_cache_id($location = NULL)
{
	$CI =& get_instance();
	return $CI->fuel->cache->create_id($location);
}

// --------------------------------------------------------------------

/**
 * Creates the admin URL for FUEL (e.g. http://localhost/MY_PROJECT/fuel/admin)
 *
 * @access	public
 * @param	string
 * @return	string
 */
function fuel_url($uri = '')
{
	$CI =& get_instance();
	return site_url($CI->fuel->config('fuel_path').$uri);
}

// --------------------------------------------------------------------

/**
 * Returns the FUEL admin URI path
 *
 * @access	public
 * @param	string
 * @return	string
 */
function fuel_uri($uri = '')
{
	$CI =& get_instance();
	return $CI->fuel->config('fuel_path').$uri;
}

// --------------------------------------------------------------------

/**
 * Returns the uri segment based on the FUEL admin path
 *
 * @access	public
 * @param	int
 * @param	boolean
 * @return	string
 */
function fuel_uri_segment($seg_index = 0, $rerouted = FALSE)
{
	$CI =& get_instance();
	if ($rerouted)
	{
		return $CI->uri->rsegment(fuel_uri_index($seg_index));
	}
	else
	{
		return $CI->uri->segment(fuel_uri_index($seg_index));
	}
}

// --------------------------------------------------------------------

/**
 * Returns the uri index number based on the FUEL admin path
 *
 * @access	public
 * @param	int
 * @return	int
 */
function fuel_uri_index($seg_index = 0)
{
	$CI =& get_instance();
	$fuel_path = $CI->fuel->config('fuel_path');
	$start_index = count(explode('/', $fuel_path)) - 1;
	return $start_index + $seg_index;
}

// --------------------------------------------------------------------

/**
 * Returns the uri string based on the FUEL admin path
 *
 * @access	public
 * @param	int
 * @param	int
 * @param	boolean
 * @return	string
 */
function fuel_uri_string($from = 0, $to = NULL, $rerouted = FALSE)
{
	$CI =& get_instance();
	$fuel_index = fuel_uri_index($from);
	
	if ($rerouted)
	{
		$segs = $CI->uri->rsegment_array($fuel_index);
	}
	else
	{
		$segs = $CI->uri->segment_array($fuel_index);
	}
	$from = fuel_uri_index($from);
	if (isset($to)) {
		$to = fuel_uri_index($to);
		if ($from < $to)
		{
			$to = $from;
		}
	}
	$segs = array_slice($segs, $from, $to);
	$uri = implode('/', $segs);
	return $uri;
}

// --------------------------------------------------------------------

/**
 * Check to see if you are logged in and can use inline editing
 *
 * @access	public
 * @return	boolean
 */
function is_fuelified()
{
	$CI =& get_instance();
	$CI->load->helper('cookie');
	return (get_cookie($CI->fuel->auth->get_fuel_trigger_cookie_name()));
}

// --------------------------------------------------------------------

/**
 * Check to see if you are in the FUEL admin
 *
 * @access	public
 * @return	boolean
 */
function in_fuel_admin()
{
	return (defined('FUEL_ADMIN') AND FUEL_ADMIN === TRUE);
}

// --------------------------------------------------------------------

/**
 * Returns the user language of the person logged in... used for inline editing
 *
 * @access	public
 * @return	boolean
 */
function fuel_user_lang()
{
	$CI =& get_instance();
	$CI->load->helper('cookie');
	$cookie_val = get_cookie($CI->fuel->auth->get_fuel_trigger_cookie_name());
	$cookie_val = unserialize($cookie_val);
	if (empty($cookie_val['language']))
	{
		$cookie_val['language'] = $CI->config->item('language');
	}
	return $cookie_val['language'];
}