<?php

class Languages
{
	# Translations ID on Redis		(Optional)	[String]
	public $project_name = 'PAB';
	# Translations file name		(Required)	[String]
	public $file_name = 'translations.json';
	# The cache time				(Optional, Required for Redis) [false: no cache/int: time in seconds]
	private $cache_time = 60 * 60 * 3;
	# Default user language			(Required)	[String of language ID]
	private $user_language = 'en';
	# NeleBot X Database class		(Optional, Required for Redis) [Database Class]
	private $db = [];
	
	# Load configs
	function __construct($user_language = 'en', $db = []) {
		$this->user_language = $user_language;
		if (isset($db->configs) and $db->configs['redis']['status']) {
			$this->db = $db;
			$this->redisCheck();
		} else {
			if (!$this->translations = json_decode(file_get_contents($this->file_name), true)) {
				return ['ok' => 0, 'error_code' => 500, 'description' => 'Unable to get translations data!'];
			}
		}
	}
	
	# Set the default language
	public function setLanguage($user_language = 'en') {
		return $this->user_language = $user_language;
	}
	
	# Load the translations on Redis for more speed (Redis only)
	private function redisCheck () {
		if (!$this->db->rget('tr-' . $this->project_name . '-status')) {
			$this->db->rset('tr-' . $this->project_name . '-status', true, $this->cache_time);
			$trs = $this->getAllTranslations();
			if ($trs['ok']) {
				$this->db->rdel($this->db->rkeys('tr-' . $this->project_name . '*'));
				$this->db->rset('tr-' . $this->project_name . '-status', true, $this->cache_time);
				foreach ($trs['result'] as $lang => $strings) {
					foreach($strings as $stringn => $translation) {
						$this->db->rset('tr-' . $this->project_name . '-' . $lang . '-' . $stringn, $translation, $this->cache_time);
					}
				}
			} else {
				$this->db->rdel('tr-' . $this->project_name . '-status');
			}
			return 1;
		}
		return;
	}
	
	# Reload translations
	public function reload () {
		if (isset($this->db->configs) and $this->db->configs['redis']['status']) {
			$this->db->rdel('tr-' . $this->project_name . '-status');
			$this->redisCheck();
		}
		return 1;
	}
	
	# Get the translation from string ID
	public function getTranslation($string, $args = [], $user_lang = 'def') {
		if ($user_lang == 'def') {
			$lang = $this->user_language;
		} else {
			$lang = strtolower($user_lang);
		}
		$string = str_replace(' ', '', $string);
		if (isset($this->db->configs)) {
			if ($lang !== 'en' and $t_string = $this->db->rget('tr-' . $this->project_name . '-' . $lang . '-' . $string)) {
			} elseif ($t_string = $this->db->rget('tr-' . $this->project_name . '-en-' . $string)) {
			} else {
				$t_string = '👾: ' . $string;
			}
		} else {
			if ($lang !== 'en' and $t_string = $this->translations[$lang][$string]) {
				
			} elseif ($t_string = $this->translations['en'][$string]) {
				
			} else {
				$t_string = '🤖';
			}
		}
		if (!empty($args) and is_array($args)) {
			$args = array_values($args);
			foreach(range(0, count($args) - 1) as $num) {
				$t_string = str_replace('[' . $num . ']', $args[$num], $t_string);
			}
		}
		return $t_string;
	}
	
	# Get all translations from the file, oneskyapp or from the current script
	public function getAllTranslations () {
		if (isset($this->translations)) {
			return ['ok' => 1, 'result' => $this->translations];
		} elseif (file_exists($this->file_name)) {
			$file = file_get_contents($this->file_name);
			if ($file) {
				if ($translations = json_decode($file, 1)) {
					return ['ok' => 1, 'result' => $translations];
				}
				return ['ok' => 1, 'result' => [], 'notice' => 'Failed to get JSON format from the file!'];
			}
			return ['ok' => 0, 'result' => [], 'notice' => 'The file is empty!'];
		} else {
			return ['ok' => 1, 'result' => [], 'notice' => 'No configs for translations'];
		}
	}

	public function save ($array1, $array2) {
		$array = [];
		foreach ($array1 as $lang => $strings) {
			if (!is_array($array[$lang])) $array[$lang] = [];
			foreach ($strings as $strName => $string) {
				$array[$lang][$strName] = $string;
			}
		}
		foreach ($array2 as $lang => $strings) {
			if (!is_array($array[$lang])) $array[$lang] = [];
			foreach ($strings as $strName => $string) {
				$array[$lang][$strName] = $string;
			}
		}
		return $array;
	}
}

?>
