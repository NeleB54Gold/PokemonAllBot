<?php

class PokeAPI {
	# Unofficial API Endpoint [https://pokeapi.co/]
	public $endpoint = 'https://pokeapi.co/api/v2';
	# Cache time
	public $cache_time = 60 * 60 * 24 * 30;
	# Request timeout
	public $r_timeout = 5;
	# Default API language
	public $lang = 'en';
	# Database class
	private $db = [];
	
	# Set configs
	public function __construct ($db = [], $lang = null) {
		if (is_string($lang)) $this->lang = $lang;
		if (is_a($db, 'Database') and $db->configs['redis']['status']) $this->db = $db;
	}
	
	# Get all pokedexes (https://pokeapi.co/docs/v2#games-section)
	public function getAllPokedexes () {
		return $this->request('pokedex');
	}
	
	# Get all regions (https://pokeapi.co/docs/v2#locations-section)
	public function getAllRegions () {
		return $this->request('region');
	}
	
	# Get berry info (https://pokeapi.co/docs/v2#berries-section)
	public function getBerry ($id) {
		return $this->request('berry/' . urlencode($id));
	}
	
	# Get berry firmnesses (https://pokeapi.co/docs/v2#berries-section)
	public function getBerryFirmnesses ($id) {
		return $this->request('berry-firmness/' . urlencode($id));
	}
	
	# Get berry flavor (https://pokeapi.co/docs/v2#berries-section)
	public function getBerryFlavor ($id) {
		return $this->request('berry-flavor/' . urlencode($id));
	}
	
	# Get egg group (https://pokeapi.co/docs/v2#pokemon-section)
	public function getEggGroup ($id) {
		return $this->request('egg-group/' . urlencode($id));
	}
	
	# Get generation (https://pokeapi.co/docs/v2#games-section)
	public function getGeneration ($id) {
		return $this->request('generation/' . urlencode($id));
	}
	
	# Get item (https://pokeapi.co/docs/v2#items-section)
	public function getItem ($id) {
		return $this->request('item/' . urlencode($id));
	}
	
	# Get pokedex (https://pokeapi.co/docs/v2#games-section)
	public function getPokedex ($id) {
		return $this->request('pokedex/' . urlencode($id));
	}
	
	# Get pokémon (https://pokeapi.co/docs/v2#pokemon-section)
	public function getPokemon ($id) {
		return $this->request('pokemon/' . urlencode($id));
	}
	
	# Get pokémon color (https://pokeapi.co/docs/v2#pokemon-section)
	public function getPokemonColor ($id) {
		return $this->request('pokemon-color/' . urlencode($id));
	}
	
	# Get pokémon evolutions (https://pokeapi.co/docs/v2#evolution-section)
	public function getPokemonEvolutions ($id) {
		return $this->request(str_replace($this->endpoint . '/', '', $id));
	}
	
	# Get pokémon habitat (https://pokeapi.co/docs/v2#pokemon-section)
	public function getPokemonHabitat ($id) {
		return $this->request('pokemon-habitat/' . urlencode($id));
	}
	
	# Get pokémon species (https://pokeapi.co/docs/v2#pokemon-section)
	public function getPokemonSpecies ($id) {
		return $this->request('pokemon-species/' . urlencode($id));
	}
	
	# Get pokémon type (https://pokeapi.co/docs/v2#pokemon-section)
	public function getPokemonType ($id) {
		return $this->request('type/' . urlencode($id));
	}
	
	# Get region (https://pokeapi.co/docs/v2#locations-section)
	public function getRegion ($id) {
		return $this->request('region/' . urlencode($id));
	}
	
	# Get pokémon stat (https://pokeapi.co/docs/v2#pokemon-section)
	public function getStat ($id) {
		return $this->request('stat/' . urlencode($id));
	}
	
	# Get games verions (https://pokeapi.co/docs/v2#games-section)
	public function getVersion ($id) {
		return $this->request('version/' . urlencode($id));
	}
	
	# Get games verions group (https://pokeapi.co/docs/v2#games-section)
	public function getVersionGroup ($id) {
		return $this->request('version-group/' . urlencode($id));
	}
	
	/*		Other Functions		*/
	/*		Data management		*/
	
	public function dexNumber ($number) {
		if (is_numeric($number)) {
			if ($number < 10) {
				return '00' . $number;
			} elseif ($number < 100) {
				return '0' . $number;
			} else {
				return $number;
			}
		} else {
			return 'NaN';
		}
	}
	
	public function getDescription ($descriptions) {
		if (is_array($descriptions)) {
			foreach ($descriptions as $tr) {
				if ($this->lang == $tr['language']['name']) return $tr['description'];
				if ($tr['language']['name'] == 'en') $result = $tr['description'];
			}
			return $result;
		} else {
			return '!Unknown resource!';
		}
	}
	
	public function getEvolutionsButtons ($evolutions, $bot, $buttons = []) {
		if (is_array($evolutions)) {
			$buttons[][] = $bot->createInlineButton($this->getTranslation($this->getPokemonSpecies($evolutions['species']['name'])['result']), 'pokemon ' . $evolutions['species']['name']);
			if (isset($evolutions['evolves_to']) and !empty($evolutions['evolves_to'])) {
				foreach ($evolutions['evolves_to'] as $evolution) {
					$buttons = $this->getEvolutionsButtons($evolution, $bot, $buttons);
				}
			}
			return $buttons;
		} else {
			return [];
		}
	}
	
	public function getVarietiesButtons ($varieties, $bot, $buttons = []) {
		if (is_array($varieties)) {
			foreach ($varieties as $variety) {
				foreach (explode('-', $variety['pokemon']['name']) as $t) {
					$t[0] = strtoupper($t[0]);
					$name .= ' ' . $t;
				}
				$buttons[][] = $bot->createInlineButton($name, 'pokemon ' . $variety['pokemon']['name']);
				unset($name);
			}
			return $buttons;
		} else {
			return [];
		}
	}
	
	public function getItemSprites ($resource, $noloop = 0) {
		if ($resource['name']) {
			if (isset($resource['sprites']) and $resource['sprites']['default']) {
				return $resource['sprites']['default'];
			} elseif ($resource['item'] and $noloop == 0) {
				return $this->getItemSprites($this->getItem($resource['item']['name'])['result'], 1);
			}
		}
		return '';
	}
	
	public function getPokemonImage ($resource, $noloop = 0) {
		if ($resource['sprites']) {
			if (!is_null($resource['sprites']['other']['official-artwork']['front_default'])) {
				return $resource['sprites']['other']['official-artwork']['front_default'];
			} else {
				return $resource['sprites']['front_default'];
			}
		}
		return '';
	}
	
	public function getPokemonButtons ($pokemon, $bot, $x = 0, $y = 0) {
		$fbuttons[] = $bot->createInlineButton('ℹ️', 'pokemon ' . $pokemon['id']);
		$fbuttons[] = $bot->createInlineButton('📊', 'pokemon-about ' . $pokemon['id']);
		$fbuttons[] = $bot->createInlineButton('⚔️', 'pokemon-stats ' . $pokemon['id']);
		$sbuttons[] = $bot->createInlineButton('🔝', 'pokemon-evolutions ' . $pokemon['id']);
		$sbuttons[] = $bot->createInlineButton('🗂', 'pokemon-forms ' . $pokemon['id']);
		$sbuttons[] = $bot->createInlineButton('👨‍🎨', 'pokemon-sprites ' . $pokemon['id']);
		$buttons = [$fbuttons, $sbuttons];
		$buttons[$x][$y]['text'] .= ' 🔘';
		return $buttons;
	}
	
	public function getTranslation ($resource, $noloop = 0) {
		if ($resource['name']) {
			if ($resource['names']) {
				foreach ($resource['names'] as $name) {
					if ($this->lang == $name['language']['name']) return $name['name'];
					if ($name['language']['name'] == 'en') $result = $name['name'];
				}
				return $result;
			} elseif ($resource['item'] and $noloop == 0) {
				return $this->getTranslation($this->getItem($resource['item']['name'])['result'], 1);
			} else {
				$resource['name'][0] = strtoupper($resource['name'][0]);
				return $resource['name'];
			}
		} else {
			return '!Unknown resource!';
		}
	}
	
	public function getGenus ($genera) {
		if (is_array($genera)) {
			foreach ($genera as $genus) {
				if ($this->lang == $genus['language']['name']) return $genus['genus'];
				if ($genus['language']['name'] == 'en') $result = $genus['genus'];
			}
			return $result;
		} else {
			return '!Unknown resource!';
		}
	}
	
	public function getFlavor ($flavors) {
		if (is_array($flavors)) {
			foreach ($flavors as $flavor) {
				if ($this->lang == $flavor['language']['name']) $onlresult = $flavor['flavor_text'];
				if ($flavor['language']['name'] == 'en') $result = $flavor['flavor_text'];
			}
			if ($onlresult) return $onlresult;
			return $result;
		} else {
			return '!Unknown resource!';
		}
	}
	
	# Custom API requests
	public function request ($src) {
		if (!isset($this->curl))	$this->curl = curl_init();
		$url = $this->endpoint . '/' . $src;
		if (is_a($db, 'Database') and $this->db->configs['redis']['status']) {
			$cache = $this->db->rget($url);
			if ($r = json_decode($cache, 1)) return $r;
		}
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> $url,
			CURLOPT_TIMEOUT			=> $this->r_timeout,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_HTTPHEADER		=> [
				'Accept: application/json'
			]
		]);
		$output = curl_exec($this->curl);
		if ($json_output = json_decode($output, 1)) {
			$return['ok'] = true;
			$return['result'] = $json_output;
			if (is_a($db, 'Database') and $this->db->configs['redis']['status']) $this->db->rset($url, json_encode($return), $this->cache_time);
			return $return;
		} else {
			$return['ok'] = false;
			$return['error'] = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			$return['description'] = $output;
			if (is_a($db, 'Database') and $this->db->configs['redis']['status']) $this->db->rset($url, json_encode($return), $this->cache_time);
			return $return;
		}
		if ($output) return $output;
		if ($error = curl_error($this->curl)) return ['ok' => 0, 'error_code' => 500, 'description' => 'CURL Error: ' . $error];
	}
}

?>