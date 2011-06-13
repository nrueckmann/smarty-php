<?php

/**
* Project:     Smarty: the PHP compiling template engine
* File:        Smarty.class.php
* SVN:         $Id$
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* For questions, help, comments, discussion, etc., please join the
* Smarty mailing list. Send a blank e-mail to
* smarty-discussion-subscribe@googlegroups.com
*
* @link http://www.smarty.net/
* @copyright 2008 New Digital Group, Inc.
* @author Monte Ohrt <monte at ohrt dot com>
* @author Uwe Tews
* @package Smarty
* @version 3-SVN$Rev: 3286 $
*/

/**
* define shorthand directory separator constant
*/
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/**
* set SMARTY_DIR to absolute path to Smarty library files.
* Sets SMARTY_DIR only if user application has not already defined it.
*/
if (!defined('SMARTY_DIR')) {
	define('SMARTY_DIR', dirname(__FILE__) . DS);
}

/**
* set SMARTY_SYSPLUGINS_DIR to absolute path to Smarty internal plugins.
* Sets SMARTY_SYSPLUGINS_DIR only if user application has not already defined it.
*/
if (!defined('SMARTY_SYSPLUGINS_DIR')) {
	define('SMARTY_SYSPLUGINS_DIR', SMARTY_DIR . 'sysplugins' . DS);
}
if (!defined('SMARTY_PLUGINS_DIR')) {
	define('SMARTY_PLUGINS_DIR', SMARTY_DIR . 'plugins' . DS);
}
if (!defined('SMARTY_MBSTRING')) {
	define('SMARTY_MBSTRING', function_exists('mb_strlen'));
}
if (!defined('SMARTY_RESOURCE_CHAR_SET')) {
	// UTF-8 can only be done properly when mbstring is available!
	define('SMARTY_RESOURCE_CHAR_SET', SMARTY_MBSTRING ? 'UTF-8' : 'ISO-8859-1');
}
if (!defined('SMARTY_RESOURCE_DATE_FORMAT')) {
	define('SMARTY_RESOURCE_DATE_FORMAT', '%b %e, %Y');
}

/**
* register the class autoloader
*/
if (!defined('SMARTY_SPL_AUTOLOAD')) {
	define('SMARTY_SPL_AUTOLOAD', 0);
}

if (SMARTY_SPL_AUTOLOAD && set_include_path(get_include_path() . PATH_SEPARATOR . SMARTY_SYSPLUGINS_DIR) !== false) {
	$registeredAutoLoadFunctions = spl_autoload_functions();
	if (!isset($registeredAutoLoadFunctions['spl_autoload'])) {
		spl_autoload_register();
	}
} else {
	spl_autoload_register('smartyAutoload');
}

/**
* Load always needed external class files
*/
include SMARTY_SYSPLUGINS_DIR.'smarty_internal_data.php';
include SMARTY_SYSPLUGINS_DIR.'smarty_internal_templatebase.php';
include SMARTY_SYSPLUGINS_DIR.'smarty_internal_template.php';
include SMARTY_SYSPLUGINS_DIR.'smarty_resource.php';

/**
* This is the main Smarty class
*/
class Smarty extends Smarty_Internal_TemplateBase {
	/**
	* constant definitions
	*/
	// smarty version
	const SMARTY_VERSION = 'Smarty 3.1-SVN$Rev: 3286 $';
	//define variable scopes
	const SCOPE_LOCAL = 0;
	const SCOPE_PARENT = 1;
	const SCOPE_ROOT = 2;
	const SCOPE_GLOBAL = 3;
	// define caching modes
	const CACHING_OFF = 0;
	const CACHING_LIFETIME_CURRENT = 1;
	const CACHING_LIFETIME_SAVED = 2;
	// define compile check modes
	const COMPILECHECK_OFF = 0;
	const COMPILECHECK_ON = 1;
	const COMPILECHECK_CACHEMISS = 2;
	/** modes for handling of "<?php ... ?>" tags in templates. **/
	const PHP_PASSTHRU = 0; //-> print tags as plain text
	const PHP_QUOTE = 1; //-> escape tags as entities
	const PHP_REMOVE = 2; //-> escape tags as entities
	const PHP_ALLOW = 3; //-> escape tags as entities
	// filter types
	const FILTER_POST = 'post';
	const FILTER_PRE = 'pre';
	const FILTER_OUTPUT = 'output';
	const FILTER_VARIABLE = 'variable';
	// plugin types
	const PLUGIN_FUNCTION = 'function';
	const PLUGIN_BLOCK = 'block';
	const PLUGIN_COMPILER = 'compiler';
	const PLUGIN_MODIFIER = 'modifier';
	const PLUGIN_MODIFIERCOMPILER = 'modifiercompiler';

	/**
	* static variables
	*/
	// assigned global tpl vars
	static $global_tpl_vars = array();

	/**
	* variables
	*/
	// auto literal on delimiters with whitspace
	public $auto_literal = true;
	// display error on not assigned variables
	public $error_unassigned = false;
	// look up relative filepaths in include_path
	public $use_include_path = false;
	// template directory
	protected $template_dir = null;
	// default template handler
	public $default_template_handler_func = null;
	// default config handler
	public $default_config_handler_func = null;
	// default plugin handler
	public $default_plugin_handler_func = null;
	// compile directory
	protected $compile_dir = null;
	// plugins directory
	protected $plugins_dir = null;
	// cache directory
	protected $cache_dir = null;
	// config directory
	protected $config_dir = null;
	// force template compiling?
	public $force_compile = false;
	// check template for modifications?
	public $compile_check = true;
	// locking concurrent compiles
	public $compile_locking = true;
	// use sub dirs for compiled/cached files?
	public $use_sub_dirs = false;
	// compile_error?
	public $compile_error = false;
	// caching enabled
	public $caching = false;
	// merge compiled includes
	public $merge_compiled_includes = false;
	// cache lifetime
	public $cache_lifetime = 3600;
	// force cache file creation
	public $force_cache = false;
	// cache_id
	public $cache_id = null;
	// compile_id
	public $compile_id = null;
	// template delimiters
	public $left_delimiter = "{";
	public $right_delimiter = "}";
	// security
	public $security_class = 'Smarty_Security';
	public $security_policy = null;
	public $php_handling = self::PHP_PASSTHRU;
	public $direct_access_security = true;
	// debug mode
	public $debugging = false;
	public $debugging_ctrl = 'NONE';
	public $smarty_debug_id = 'SMARTY_DEBUG';
	public $debug_tpl = null;
	// When set, smarty does uses this value as error_reporting-level.
	public $error_reporting = null;
	// config var settings
	public $config_overwrite = true; //Controls whether variables with the same name overwrite each other.
	public $config_booleanize = true; //Controls whether config values of on/true/yes and off/false/no get converted to boolean
	public $config_read_hidden = false; //Controls whether hidden config sections/vars are read from the file.
	// global template functions
	public $template_functions = array();
	// resource type used if none given
	public $default_resource_type = 'file';
	// caching type
	public $caching_type = 'file';
	// internal config properties
	public $properties = array();
	// config type
	public $default_config_type = 'file';
	// cached template objects
	public $template_objects = null;
	// check If-Modified-Since headers
	public $cache_modified_check = false;
	// registered plugins
	public $registered_plugins = array();
	// plugin search order
	public $plugin_search_order = array('function', 'block', 'compiler', 'class');
	// registered objects
	public $registered_objects = array();
	// registered classes
	public $registered_classes = array();
	// registered filters
	public $registered_filters = array();
	// registered resources
	public $registered_resources = array();
	// registered cache resources
	public $registered_cache_resources = array();
	// autoload filter
	public $autoload_filters = array();
	// default modifier
	public $default_modifiers = array();
	// autoescape variable output
	public $escape_html = false;
	// global internal smarty  vars
	static $_smarty_vars = array();
	// start time for execution time calculation
	public $start_time = 0;
	// default file permissions
	public $_file_perms = 0644;
	// default dir permissions
	public $_dir_perms = 0771;
	// block tag hierarchy
	public $_tag_stack = array();
	// self pointer to Smarty object
	public $smarty;

    // required by the compiler for BC
	public $_current_file = null;

	/**
	 * Initialize new Smarty object
	 *
	 */
	public function __construct()
	{
		// selfpointer need by some other class methods
		$this->smarty = $this;
		if (is_callable('mb_internal_encoding')) {
			mb_internal_encoding(SMARTY_RESOURCE_CHAR_SET);
		}
		$this->start_time = microtime(true);
		// set default dirs
		$this->template_dir = array('.' . DS . 'templates' . DS);
		$this->compile_dir = '.' . DS . 'templates_c' . DS;
		$this->plugins_dir = array(SMARTY_PLUGINS_DIR);
		$this->cache_dir = '.' . DS . 'cache' . DS;
		$this->config_dir = array('.' . DS . 'configs' . DS);
		$this->debug_tpl = 'file:' . dirname(__FILE__) . '/debug.tpl';
		if (isset($_SERVER['SCRIPT_NAME'])) {
			$this->assignGlobal('SCRIPT_NAME', $_SERVER['SCRIPT_NAME']);
		}
	}

	/**
	* Class destructor
	*/
	public function __destruct()
	{
	}

    /**
    *  set selfpointer on cloned object
    */
    public function __clone()
    {
    	$this->smarty = $this;
	}

	/**
	 * Check if a template resource exists
	 *
	 * @param string $resource_name template name
	 * @return boolean status
	 */
	function templateExists($resource_name)
	{
		// create template object
		$save = $this->template_objects;
		$tpl = new $this->template_class($resource_name, $this);
		// check if it does exists
		$result = $tpl->source->exists;
		$this->template_objects = $save;
		return $result;
	}

	/**
	 * Returns a single or all global  variables
	 *
	 * @param object $smarty
	 * @param string $varname variable name or null
	 * @return string variable value or or array of variables
	 */
	function getGlobal($varname = null)
	{
		if (isset($varname)) {
			if (isset(self::$global_tpl_vars[$varname])) {
				return self::$global_tpl_vars[$varname]->value;
			} else {
				return '';
			}
		} else {
			$_result = array();
			foreach (self::$global_tpl_vars AS $key => $var) {
				$_result[$key] = $var->value;
			}
			return $_result;
		}
	}


	/**
	 * Empty cache folder
	 *
	 * @param integer $exp_time expiration time
	 * @param string $type resource type
	 * @return integer number of cache files deleted
	 */
	function clearAllCache($exp_time = null, $type = null)
	{
		// load cache resource and call clearAll
		$_cache_resource = Smarty_CacheResource::load($this, $type);
		return $_cache_resource->clearAll($this, $exp_time);
	}

	/**
	 * Empty cache for a specific template
	 *
	 * @param string $template_name template name
	 * @param string $cache_id cache id
	 * @param string $compile_id compile id
	 * @param integer $exp_time expiration time
	 * @param string $type resource type
	 * @return integer number of cache files deleted
	 */
	function clearCache($template_name, $cache_id = null, $compile_id = null, $exp_time = null, $type = null)
	{
		// load cache resource and call clear
		$_cache_resource = Smarty_CacheResource::load($this, $type);
		return $_cache_resource->clear($this, $template_name, $cache_id, $compile_id, $exp_time);
	}


	/**
	 * Loads security class and enables security
	 * @return Smarty current Smarty instance for chaining
	 */
	public function enableSecurity($security_class = null)
	{
		if ($security_class instanceof Smarty_Security) {
			$this->security_policy = $security_class;
			return;
		}
		if ($security_class == null) {
			$security_class = $this->security_class;
		}
		if (class_exists($security_class)) {
			$this->security_policy = new $security_class($this);
		} else {
			throw new SmartyException("Security class '$security_class' is not defined");
		}

		return $this;
	}

	/**
	 * Disable security
	 * @return Smarty current Smarty instance for chaining
	 */
	public function disableSecurity()
	{
		$this->security_policy = null;

		return $this;
	}


	/**
	 * Set template directory
	 *
	 * @param string|array $template_dir directory(s) of template sources
	 * @return Smarty current Smarty instance for chaining
	 */
	public function setTemplateDir($template_dir)
	{
		$this->template_dir = array();
		foreach ((array)$template_dir as $k => $v) {
		    if (!is_dir($v)) {
		        throw new SmartyException("{$v} is not a directory");
		    } elseif (!is_dir($v)) {
		        throw new SmartyException("{$v} is not readable");
		    }

		    $this->template_dir[$k] = rtrim($v, '/\\') . DS;
		}

		return $this;
	}

	/**
	 * Add template directory(s)
	 *
	 * @param string|array $template_dir directory(s) of template sources
	 * @param string key of the array element to assign the template dir to
	 * @return Smarty current Smarty instance for chaining
	 */
	public function addTemplateDir($template_dir, $key=null)
	{
	    // make sure we're dealing with an array
	    $this->template_dir = (array) $this->template_dir;

	    if (is_array($template_dir)) {
	        foreach ($template_dir as $k => $v) {
	            if (!is_dir($v)) {
    		        throw new SmartyException("{$v} is not a directory");
    		    } elseif (!is_dir($v)) {
    		        throw new SmartyException("{$v} is not readable");
    		    }

                if (is_int($k)) {
                    // indexes are not merged but appended
    		        $this->template_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
    		        $this->template_dir[$k] = rtrim($v, '/\\') . DS;
                }
    		}
	    } elseif( $key !== null ) {
	        if (!is_dir($template_dir)) {
		        throw new SmartyException("{$template_dir} is not a directory");
		    } elseif (!is_dir($template_dir)) {
		        throw new SmartyException("{$template_dir} is not readable");
		    }

	        // override directory at specified index
		    $this->template_dir[$key] = rtrim($template_dir, '/\\') . DS;
	    } else {
	        if (!is_dir($template_dir)) {
		        throw new SmartyException("{$template_dir} is not a directory");
		    } elseif (!is_dir($template_dir)) {
		        throw new SmartyException("{$template_dir} is not readable");
		    }

	        // append new directory
		    $this->template_dir[] = rtrim($template_dir, '/\\') . DS;
	    }

		$this->template_dir = array_unique($this->template_dir);
		return $this;
	}

	/**
	 * Get template directories
	 *
	 * @param mixed index of directory to get, null to get all
	 * @return array|string list of template directories, or directory of $index
	 */
	public function getTemplateDir($index=null)
	{
	    if ($index !== null) {
	        return isset($this->template_dir[$index]) ? $this->template_dir[$index] : null;
	    }

		return $this->template_dir;
	}


	/**
	 * Set config directory
	 *
	 * @param string|array $template_dir directory(s) of configuration sources
	 * @return Smarty current Smarty instance for chaining
	 */
	public function setConfigDir($config_dir)
	{
		$this->config_dir = array();
		foreach ((array)$config_dir as $k => $v) {
		    if (!is_dir($v)) {
		        throw new SmartyException("{$v} is not a directory");
		    } elseif (!is_dir($v)) {
		        throw new SmartyException("{$v} is not readable");
		    }

		    $this->config_dir[$k] = rtrim($v, '/\\') . DS;
		}

		return $this;
	}

	/**
	 * Add config directory(s)
	 *
	 * @param string|array $config_dir directory(s) of config sources
	 * @param string key of the array element to assign the config dir to
	 * @return Smarty current Smarty instance for chaining
	 */
	public function addConfigDir($config_dir, $key=null)
	{
	    // make sure we're dealing with an array
	    $this->config_dir = (array) $this->config_dir;

	    if (is_array($config_dir)) {
	        foreach ($config_dir as $k => $v) {
	            if (!is_dir($v)) {
    		        throw new SmartyException("{$v} is not a directory");
    		    } elseif (!is_dir($v)) {
    		        throw new SmartyException("{$v} is not readable");
    		    }

                if (is_int($k)) {
                    // indexes are not merged but appended
    		        $this->config_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
    		        $this->config_dir[$k] = rtrim($v, '/\\') . DS;
                }
    		}
	    } elseif( $key !== null ) {
	        if (!is_dir($config_dir)) {
		        throw new SmartyException("{$config_dir} is not a directory");
		    } elseif (!is_dir($config_dir)) {
		        throw new SmartyException("{$config_dir} is not readable");
		    }

	        // override directory at specified index
		    $this->config_dir[$key] = rtrim($config_dir, '/\\') . DS;
	    } else {
	        if (!is_dir($config_dir)) {
		        throw new SmartyException("{$config_dir} is not a directory");
		    } elseif (!is_dir($config_dir)) {
		        throw new SmartyException("{$config_dir} is not readable");
		    }

	        // append new directory
		    $this->config_dir[] = rtrim($config_dir, '/\\') . DS;
	    }

		$this->config_dir = array_unique($this->config_dir);
		return $this;
	}

	/**
	 * Get config directory
	 *
	 * @param mixed index of directory to get, null to get all
	 * @return string configuration directory
	 */
	public function getConfigDir($index=null)
	{
	    if ($index !== null) {
	        return isset($this->config_dir[$index]) ? $this->config_dir[$index] : null;
	    }

		return $this->config_dir;
	}


    /**
	 * Set plugins directory
	 *
	 * @param string|array $plugins_dir directory(s) of plugins
	 * @return Smarty current Smarty instance for chaining
	 */
	public function setPluginsDir($plugins_dir)
	{
		$this->plugins_dir = array();
		foreach ((array)$plugins_dir as $k => $v) {
		    if (!is_dir($v)) {
		        throw new SmartyException("{$v} is not a directory");
		    } elseif (!is_dir($v)) {
		        throw new SmartyException("{$v} is not readable");
		    }

		    $this->plugins_dir[$k] = rtrim($v, '/\\') . DS;
		}

		return $this;
	}

	/**
	 * Adds directory of plugin files
	 *
	 * @param object $smarty
	 * @param string $ |array $ plugins folder
	 * @return Smarty current Smarty instance for chaining
	 */
	function addPluginsDir($plugins_dir)
	{
	    // make sure we're dealing with an array
	    $this->plugins_dir = (array) $this->plugins_dir;

	    if (is_array($plugins_dir)) {
	        foreach ($plugins_dir as $k => $v) {
                if (is_int($k)) {
                    // indexes are not merged but appended
    		        $this->plugins_dir[] = rtrim($v, '/\\') . DS;
                } else {
                    // string indexes are overridden
    		        $this->plugins_dir[$k] = rtrim($v, '/\\') . DS;
                }
    		}
	    } else {
	        // append new directory
		    $this->plugins_dir[] = rtrim($plugins_dir, '/\\') . DS;
	    }

		$this->plugins_dir = array_unique($this->plugins_dir);
		return $this;
	}

    /**
	 * Get plugin directories
	 *
	 * @return array list of plugin directories
	 */
	public function getPluginsDir()
	{
		return $this->plugins_dir;
	}


    /**
	 * Set compile directory
	 *
	 * @param string $compile_dir directory to store compiled templates in
	 * @return Smarty current Smarty instance for chaining
	 */
	public function setCompileDir($compile_dir)
	{
	    if (!is_dir($compile_dir)) {
	        throw new SmartyException("{$compile_dir} is not a directory");
	    } elseif (!is_dir($compile_dir)) {
	        throw new SmartyException("{$compile_dir} is not readable");
	    }

	    $this->compile_dir = rtrim($compile_dir, '/\\') . DS;
		return $this;
	}

    /**
	 * Get compiled directory
	 *
	 * @return string path to compiled templates
	 */
	public function getCompileDir()
	{
		return $this->compile_dir;
	}


    /**
	 * Set cache directory
	 *
	 * @param string $cache_dir directory to store cached templates in
	 * @return Smarty current Smarty instance for chaining
	 */
	public function setCacheDir($cache_dir)
	{
	    if (!is_dir($cache_dir)) {
	        throw new SmartyException("{$cache_dir} is not a directory");
	    } elseif (!is_dir($cache_dir)) {
	        throw new SmartyException("{$cache_dir} is not readable");
	    }

	    $this->cache_dir = rtrim($cache_dir, '/\\') . DS;
		return $this;
	}

    /**
	 * Get cache directory
	 *
	 * @return string path of cache directory
	 */
	public function getCacheDir()
	{
		return $this->cache_dir;
	}


    /**
     * Set default modifiers
     *
     * @param array|string $modifiers modifier or list of modifiers to set
     * @return Smarty current Smarty instance for chaining
     */
    public function setDefaultModifiers($modifiers)
    {
        $this->default_modifiers = (array) $modifiers;
        return $this;
    }

    /**
     * Add default modifiers
     *
     * @param array|string $modifiers modifier or list of modifiers to add
     * @return Smarty current Smarty instance for chaining
     */
    public function addDefaultModifiers($modifiers)
    {
        if (is_array($modifiers)) {
            $this->default_modifiers = array_merge($this->default_modifiers, $modifiers);
        } else {
            $this->default_modifiers[] = $modifiers;
        }

        return $this;
    }

    /**
     * Get default modifiers
     *
     * @return array list of default modifiers
     */
    public function getDefaultModifiers()
    {
        return $this->default_modifiers;
    }


    /**
     * Set autoload filters
     *
     * @param array $filters filters to load automatically
     * @param string $type "pre", "output", … specify the filter type to set. Defaults to none treating $filters' keys as the appropriate types
     * @return Smarty current Smarty instance for chaining
     */
    public function setAutoloadFilters($filters, $type=null)
    {
        if ($type !== null) {
            $this->autoload_filters[$type] = (array) $filters;
        } else {
            $this->autoload_filters = (array) $filters;
        }

        return $this;
    }

    /**
     * Add autoload filters
     *
     * @param array $filters filters to load automatically
     * @param string $type "pre", "output", … specify the filter type to set. Defaults to none treating $filters' keys as the appropriate types
     * @return Smarty current Smarty instance for chaining
     */
    public function addAutoloadFilters($filters, $type=null)
    {
        if ($type !== null) {
            if (!empty($this->autoload_filters[$type])) {
                $this->autoload_filters[$type] = array_merge($this->autoload_filters[$type], (array) $filters);
            } else {
                $this->autoload_filters[$type] = (array) $filters;
            }
        } else {
            foreach ((array) $filters as $key => $value) {
                if (!empty($this->autoload_filters[$key])) {
                    $this->autoload_filters[$key] = array_merge($this->autoload_filters[$key], (array) $value);
                } else {
                    $this->autoload_filters[$key] = (array) $value;
                }
            }
        }

        return $this;
    }

    /**
     * Get autoload filters
     *
     * @param string $type type of filter to get autoloads for. Defaults to all autoload filters
     * @return array array( 'type1' => array( 'filter1', 'filter2', … ) ) or array( 'filter1', 'filter2', …) if $type was specified
     */
    public function getAutoloadFilters($type=null)
    {
        if ($type !== null) {
            return isset($this->autoload_filters[$type]) ? $this->autoload_filters[$type] : array();
        }

        return $this->autoload_filters;
    }


	/**
	 * return name of debugging template
	 *
	 * @return string
	 */
	function getDebugTemplate()
	{
		return $this->debug_tpl;
	}

	/**
	 * set the debug template
	 *
	 * @param string $tpl_name
	 * @return Smarty current Smarty instance for chaining
	 * @throws SmartyException if file is not readable
	 */
	function setDebugTemplate($tpl_name)
	{
	    if (!is_readable($tpl_name)) {
	        throw new SmartyException( "Unknown file '{$tpl_name}'" );
	    }
		$this->debug_tpl = $tpl_name;

		return $this;
	}

	/**
	 * creates a template object
	 *
	 * @param string $template the resource handle of the template file
	 * @param mixed $cache_id cache id to be used with this template
	 * @param mixed $compile_id compile id to be used with this template
	 * @param object $parent next higher level of Smarty variables
	 * @param boolean $do_clone flag is Smarty object shall be cloned
	 * @return object template object
	 */
	public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true)
	{
		if (!empty($cache_id) && (is_object($cache_id) || is_array($cache_id))) {
			$parent = $cache_id;
			$cache_id = null;
		}
		if (!empty($parent) && is_array($parent)) {
			$data = $parent;
			$parent = null;
		} else {
			$data = null;
		}
		// already in template cache?
		$_templateId =  sha1($template . $cache_id . $compile_id);
		if ($do_clone) {
			if (isset($this->template_objects[$_templateId])) {
				// return cached template object
				$tpl = clone $this->template_objects[$_templateId];
				$tpl->smarty = clone $tpl->smarty;
				$tpl->parent = $parent;
			} else {
				$tpl = new $this->template_class($template, clone $this, $parent, $cache_id, $compile_id);
			}
		} else {
			if (isset($this->template_objects[$_templateId])) {
				// return cached template object
				$tpl = $this->template_objects[$_templateId];
			} else {
				$tpl = new $this->template_class($template, $this, $parent, $cache_id, $compile_id);
			}
		}
		// fill data if present
		if (!empty($data) && is_array($data)) {
			// set up variable values
			foreach ($data as $_key => $_val) {
				$tpl->tpl_vars[$_key] = new Smarty_variable($_val);
			}
		}
		return $tpl;
	}

	/**
	 * Takes unknown classes and loads plugin files for them
	 * class name format: Smarty_PluginType_PluginName
	 * plugin filename format: plugintype.pluginname.php
	 *
	 * @param string $plugin_name class plugin name to load
	 * @return string |boolean filepath of loaded file or false
	 */
	public function loadPlugin($plugin_name, $check = true)
	{
		// if function or class exists, exit silently (already loaded)
		if ($check && (is_callable($plugin_name) || class_exists($plugin_name, false)))
		return true;
		// Plugin name is expected to be: Smarty_[Type]_[Name]
		$_plugin_name = strtolower($plugin_name);
		$_name_parts = explode('_', $_plugin_name, 3);
		// class name must have three parts to be valid plugin
		if (count($_name_parts) < 3 || $_name_parts[0] !== 'smarty') {
			throw new SmartyException("plugin {$plugin_name} is not a valid name format");
			return false;
		}
		// if type is "internal", get plugin from sysplugins
		if ($_name_parts[1] == 'internal') {
			$file = SMARTY_SYSPLUGINS_DIR . $_plugin_name . '.php';
			if (file_exists($file)) {
				require_once($file);
				return $file;
			} else {
				return false;
			}
		}
		// plugin filename is expected to be: [type].[name].php
		$_plugin_filename = "{$_name_parts[1]}.{$_name_parts[2]}.php";
		// loop through plugin dirs and find the plugin
		foreach($this->getPluginsDir() as $_plugin_dir) {
			$file = $_plugin_dir . $_plugin_filename;
			if (file_exists($file)) {
				require_once($file);
				return $file;
			}
		}
		// no plugin loaded
		return false;
	}

    /**
     * Compile all template files
     *
     * @param string $extension file extension
     * @param bool $force_compile force all to recompile
     * @param int $time_limit
     * @param int $max_errors
     * @return integer number of template files recompiled
     */
    function compileAllTemplates($extention = '.tpl', $force_compile = false, $time_limit = 0, $max_errors = null)
	{
		return Smarty_Internal_Utility::compileAllTemplates($extention, $force_compile, $time_limit, $max_errors, $this);
	}


    /**
     * Compile all config files
     *
     * @param string $extension file extension
     * @param bool $force_compile force all to recompile
     * @param int $time_limit
     * @param int $max_errors
     * @return integer number of template files recompiled
     */
    function compileAllConfig($extention = '.conf', $force_compile = false, $time_limit = 0, $max_errors = null)
    {
    	return Smarty_Internal_Utility::compileAllConfig($extention, $force_compile, $time_limit, $max_errors, $this);
    }

    /**
     * Delete compiled template file
     *
     * @param string $resource_name template name
     * @param string $compile_id compile id
     * @param integer $exp_time expiration time
     * @return integer number of template files deleted
     */
    function clearCompiledTemplate($resource_name = null, $compile_id = null, $exp_time = null)
    {
    	return Smarty_Internal_Utility::clearCompiledTemplate($resource_name, $compile_id, $exp_time, $this);
    }


    /**
     * Return array of tag/attributes of all tags used by an template
     *
     * @param object $templae template object
     * @return array of tag/attributes
     */
	function getTags(Smarty_Internal_Template $template)
	{
		return Smarty_Internal_Utility::getTags($template);
	}

	/**
	 * Run installation test
	 */
    function testInstall()
    {
		return Smarty_Internal_Utility::testInstall($this);
    }

}

/**
* Smarty exception class
*/
Class SmartyException extends Exception {
}

/**
* Smarty compiler exception class
*/
Class SmartyCompilerException extends SmartyException  {
}

/**
* Autoloader
*/
function smartyAutoload($class)
{
	$_class = strtolower($class);
	if (substr($_class, 0, 16) == 'smarty_internal_'
	|| in_array( $_class, array(
	'smarty_config_source',
	'smarty_config_compiled',
	'smarty_security',
	'smarty_cacheresource',
	'smarty_cacheresource_custom',
	'smarty_cacheresource_keyvaluestore',
	'smarty_resource',
	'smarty_resource_custom',
	'smarty_resource_uncompiled',
	'smarty_resource_recompiled',
	))) {
		include SMARTY_SYSPLUGINS_DIR . $_class . '.php';
	}
}
?>