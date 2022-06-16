<?php
	
# Ignore inline messages (via @)
if ($v->via_bot) die;

# Start PokeAPI class
$poke = new PokeAPI($db, $user['lang']);

# Get text message by callback query
if (isset($v->query_data)) {
	foreach (['pokemon', 'pokemon-about', 'pokemon-stats', 'pokemon-evolutions', 'pokemon-forms', 'pokemon-sprites', 'berry'] as $string) {
		if (strpos($v->query_data, $string . ' ') === 0) {
			$user['settings']['select'] = $string;
			$v->text = str_replace($string . ' ', '', $v->query_data);
			unset($v->query_data);
		}
	}
}

# Private chat with Bot
if ($v->chat_type == 'private' || $v->inline_message_id) {
	if ($bot->configs['database']['status'] && $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	
	# Set default selection
	if (!isset($user['settings']['select'])) $user['settings']['select'] = '';
	# Edit message by inline messages
	if ($v->inline_message_id) {
		$v->message_id = $v->inline_message_id;
		$v->chat_id = 0;
	}
	# Start message
	if ($v->command == 'start' || $v->query_data == 'start') {
		$t = $tr->getTranslation('startMessage');
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('pokemonButton'), 'pokemon'),
			$bot->createInlineButton($tr->getTranslation('berriesButton'), 'berries')
		];
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('mapsButton'), 'maps'),
			$bot->createInlineButton($tr->getTranslation('pokedexButton'), 'pokedex')
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'lang');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
		if (isset($user['settings']['select'])) {
			$user['settings']['select'] = '';
			$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $v->user_id]);
		}
	}
	# Maps button
	elseif ($v->query_data == 'maps') {
		$regions = $poke->getAllRegions();
		if ($regions['ok']) {
			$t = $tr->getTranslation('loading');
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
			$formenu = 2;
			$mcount = 0;
			foreach ($regions['result']['results'] as $region) {
				if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
				$rname = $poke->getTranslation($poke->getRegion($region['name'])['result']);
				$buttons[$mcount][] = $bot->createInlineButton($rname, 'region ' . $region['name']);
			}
			$t = $tr->getTranslation('searchMaps');
		} else {
			$t = $tr->getTranslation('serviceNotAvailable');
		}
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'start');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
	}
	# Region button
	elseif (strpos($v->query_data, 'region ') === 0) {
		$t = $tr->getTranslation('loading');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		$bot->answerCBQ($v->query_id);
		$region = $poke->getRegion(str_replace('region ', '', $v->query_data));
		if ($region['ok']) {
			$region = $region['result'];
			$t = $bot->bold($poke->getTranslation($region), 1);
			$t .= PHP_EOL . $tr->getTranslation('generation') . ': ' . $poke->getTranslation($poke->getGeneration($region['main_generation']['name'])['result']);
			if (isset($region['version_groups']) && !empty($region['version_groups'])) {
				foreach ($region['version_groups'] as $version_group) {
					if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
					$versionGroup = $poke->getVersionGroup($version_group['name'])['result'];
					foreach ($versionGroup['versions'] as $version) {
						if ($versions) {
							$versions .= ', ' . $poke->getTranslation($poke->getVersion($version['name'])['result']);
						} else {
							$versions .= $poke->getTranslation($poke->getVersion($version['name'])['result']);
						}
					}
				}
				$t .= PHP_EOL . $tr->getTranslation('versionGroups') . ': ' . $versions;
			}
			if (isset($region['pokedexes']) && !empty($region['pokedexes'])) {
				$formenu = 2;
				$mcount = 0;
				foreach ($region['pokedexes'] as $pokedex) {
					if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
					$rname = $poke->getTranslation($poke->getPokedex($pokedex['name'])['result']);
					$buttons[$mcount][] = $bot->createInlineButton($rname, 'pokedex ' . $pokedex['name']);
				}
			}
		} else {
			$t = $tr->getTranslation('serviceNotAvailable');
		}
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'maps');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
	}
	# Pokédexes button
	elseif ($v->query_data == 'pokedex') {
		$pokedexes = $poke->getAllPokedexes();
		if ($pokedexes['ok']) {
			$t = $tr->getTranslation('loading');
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
			$formenu = 2;
			$mcount = 0;
			foreach ($pokedexes['result']['results'] as $pokedex) {
				if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
				$rname = $poke->getTranslation($poke->getPokedex($pokedex['name'])['result']);
				$buttons[$mcount][] = $bot->createInlineButton($rname, 'pokedex ' . $pokedex['name']);
			}
			$t = $tr->getTranslation('searchPokedex');
		} else {
			$t = $tr->getTranslation('serviceNotAvailable');
		}
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'start');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
	}
	# Pokedex button
	elseif (strpos($v->query_data, 'pokedex ') === 0) {
		$t = $tr->getTranslation('loading');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		$bot->answerCBQ($v->query_id);
		$pokedex = $poke->getPokedex(str_replace('pokedex ', '', $v->query_data));
		if ($pokedex['ok']) {
			$pokedex = $pokedex['result'];
			$t = $bot->bold($poke->getTranslation($pokedex), 1);
			$t .= PHP_EOL . $bot->italic($poke->getDescription($pokedex['descriptions']), 1);
			$t .= PHP_EOL . PHP_EOL . '🗃 ' . $tr->getTranslation('pokedexCount', [$bot->bold(count($pokedex['pokemon_entries']))]);
		} else {
			$t = $tr->getTranslation('serviceNotAvailable');
		}
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'pokedex');
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
	}
	# Send Sprites as files
	elseif (strpos($v->query_data, 'sprites ') === 0) {
		$e = explode(' ', $v->query_data);
		$bot->answerCBQ($v->query_id);
		$pokemon = $poke->getPokemon($e[1]);
		if ($pokemon['ok']) {
			$pokemon = $pokemon['result'];
			$t = $bot->bold($poke->getTranslation($pokemon) . ': ', 1);
			if ($e[2] == 'default') {
				$urls[] = $pokemon['sprites']['back_default'];
				$urls[] = $pokemon['sprites']['front_default'];
				$urls[] = $pokemon['sprites']['back_female'];
				$urls[] = $pokemon['sprites']['front_female'];
				$t .= $tr->getTranslation('default');
			} elseif ($e[2] == 'shiny') {
				$urls[] = $pokemon['sprites']['back_shiny'];
				$urls[] = $pokemon['sprites']['front_shiny'];
				$urls[] = $pokemon['sprites']['back_shiny_female'];
				$urls[] = $pokemon['sprites']['front_shiny_female'];
				$t .= $tr->getTranslation('shiny');
			} elseif ($e[2] && isset($pokemon['sprites']['versions'][$e[2]])) {
				$urls = [];
				foreach($pokemon['sprites']['versions'][$e[2]] as $game => $sprites) {
					$urls = array_merge(array_values($sprites), $urls);
				}
				$t .= $poke->getTranslation($poke->getGeneration($e[2])['result']);
			}
			foreach ($urls as $url) {
				if (!is_null($url) && is_string($url)) $media[] = $bot->createDocumentInput($url);
			}
			$last = end(array_keys($media));
			$media[$last]['caption'] = $t;
			$media[$last]['parse_mode'] = $bot->configs['parse_mode'];
			$t = $bot->sendMediaGroup($v->user_id, $media);
		} else {
			$t = $tr->getTranslation('serviceNotAvailable');
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'pokemon ' . $e[1]);
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		}
	}
	# Change language
	elseif ($v->command == 'lang' || $v->query_data == 'lang' || strpos($v->query_data, 'changeLanguage-') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'fr' => '🇫🇷 Français',
			'it' => '🇮🇹 Italiano',
			'pt_br' => '🇧🇷 Português'
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($select);
				$user['lang'] = $select;
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$t = '🔡 Select your language';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Other commands
	else {
		# Unknown command
		if (($v->text || $v->command) && !in_array($user['settings']['select'], ['pokemon', 'pokemon-about', 'pokemon-stats', 'pokemon-evolutions', 'pokemon-forms', 'pokemon-sprites', 'berries'])) {
			if ($v->command) {
				$t = $tr->getTranslation('unknownCommand');
			} elseif ($v->text) {
				$t = $tr->getTranslation('noCommandRun');
			}
			if ($v->query_id) {
				$bot->answerCBQ($v->query_id, $t);
			} else {
				$bot->sendMessage($v->chat_id, $t);
			}
		}
	}
}

# Search pokémon
if ($v->command == 'pokemon' || $v->query_data == 'pokemon') {
	$user['settings']['select'] = 'pokemon';
	$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $user['id']]);
	$t = $tr->getTranslation('sendPokemonID');
	if ($v->chat_type == 'private') $buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'start');
	if ($v->query_id) {
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		$bot->answerCBQ($v->query_id);
	} else {
		$bot->sendMessage($v->chat_id, $t, $buttons);
	}
}
# Search berry
elseif ($v->command == 'berry' || $v->query_data == 'berries') {
	$user['settings']['select'] = 'berries';
	$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $user['id']]);
	$t = $tr->getTranslation('sendBerriesID');
	$buttons[][] = $bot->createInlineButton($tr->getTranslation('backButton'), 'start');
	if ($v->query_id) {
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		$bot->answerCBQ($v->query_id);
	} else {
		$bot->sendMessage($v->chat_id, $t, $buttons);
	}
}
# Search properties
if (!$v->query_data && !$v->command && in_array($user['settings']['select'], ['pokemon', 'pokemon-about', 'pokemon-stats', 'pokemon-evolutions', 'pokemon-forms', 'pokemon-sprites', 'berries'])) {
	$t = $tr->getTranslation('loading');
	if ($v->query_id) {
		$bot->answerCBQ($v->query_id, $t);
	} else {
		$v->message_id = $bot->sendMessage($v->chat_id, $t, $buttons, 'def', 0, 0, 'inline', 1)['result']['message_id'];
	}
	if ($user['settings']['select'] == 'berries') {
		$data = $poke->getBerry($v->text);
		if ($data['ok']) {
			$berry = $data['result'];
			$preview = $poke->getItemSprites($berry);
			$t = $bot->bold($poke->getTranslation($berry), 1);
			$t .= PHP_EOL . $tr->getTranslation('size') . ': ' . number_format($berry['size'] / 10, 0, ',', '.') . ' ' . $tr->getTranslation('centimeters');
			foreach ($berry['flavors'] as $bflavor) {
				$flavor = $poke->getBerryFlavor($bflavor['flavor']['name'])['result'];
				$t .= PHP_EOL;
				$t .= $poke->getTranslation($flavor) . ': ' . round($bflavor['potency'] / 10 * 100) . '%';
			}
		} else {
			$t = $tr->getTranslation('berryNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon') {
		$data = $poke->getPokemon($v->text);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($pokemon);
			$t = $bot->bold($poke->getTranslation($pokemon), 1);
			$t .= PHP_EOL . $tr->getTranslation('id') . ': #' . $poke->dexNumber($pokemon['id']);
			foreach ($pokemon['types'] as $pokemon_type) {
				$type = $poke->getPokemonType($pokemon_type['type']['name'])['result'];
				if ($types) {
					$types .= ', ' . $poke->getTranslation($type, 1);
				} else {
					$types = $poke->getTranslation($type, 1);
				}
			}
			$t .= PHP_EOL . $tr->getTranslation('type') . ': ' . $types;
			$t .= PHP_EOL . $tr->getTranslation('height') . ': ' . number_format($pokemon['height'] / 10, 1, ',' , '.') . ' ' . $tr->getTranslation('meters');
			$t .= PHP_EOL . $tr->getTranslation('weight') . ': ' . number_format($pokemon['weight'] / 10, 1, ',' , '.') . ' ' . $tr->getTranslation('kilograms');
			$buttons = $poke->getPokemonButtons($pokemon, $bot, 0, 0);
		} else {
			$t = $tr->getTranslation('pokemonNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon-about') {
		$data = $poke->getPokemon($v->text);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($pokemon);
			$species = $poke->getPokemonSpecies($pokemon['species']['name'])['result'];
			$t = $bot->bold($poke->getTranslation($pokemon), 1);
			$t .= ': ' . $bot->bold($poke->getGenus($species['genera']), 1);
			$t .= PHP_EOL . $bot->italic($poke->getFlavor($species['flavor_text_entries']), 1);
			$t .= PHP_EOL . PHP_EOL . $tr->getTranslation('generation') . ': ' . $poke->getTranslation($poke->getGeneration($species['generation']['name'])['result']);
			$t .= PHP_EOL . $tr->getTranslation('color') . ': ' . $poke->getTranslation($poke->getPokemonColor($species['color']['name'])['result']);
			$t .= PHP_EOL . $tr->getTranslation('captureRate') . ': ' . number_format($species['capture_rate'] / 255 * 100) . '%';
			$t .= PHP_EOL . $tr->getTranslation('habitat') . ': ' . $poke->getTranslation($poke->getPokemonHabitat($species['habitat']['name'])['result']);
			foreach ($species['egg_groups'] as $egg_group) {
				$eg = $poke->getEggGroup($egg_group['name'])['result'];
				if ($eggGroups) {
					$eggGroups .= ', ' . $poke->getTranslation($eg);
				} else {
					$eggGroups = $poke->getTranslation($eg);
				}
			}
			$t .= PHP_EOL . $tr->getTranslation('eggGroups') . ': ' . $eggGroups;
			$buttons = $poke->getPokemonButtons($pokemon, $bot, 0, 1);
		} else {
			$t = $tr->getTranslation('pokemonNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon-stats') {
		$data = $poke->getPokemon($v->text);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($pokemon);
			$t = $bot->bold($poke->getTranslation($pokemon), 1);
			foreach ($pokemon['stats'] as $stat) {
				$gstat = $poke->getStat($stat['stat']['name'])['result'];
				$t .= PHP_EOL . $poke->getTranslation($gstat) . ': ' . $stat['base_stat'];
			}
			$buttons = $poke->getPokemonButtons($pokemon, $bot, 0, 2);
		} else {
			$t = $tr->getTranslation('pokemonNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon-evolutions') {
		$data = $poke->getPokemonSpecies($poke->getPokemon($v->text)['result']['species']['name']);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($poke->getPokemon($pokemon['name'])['result']);
			$t = $bot->bold($tr->getTranslation('pokemonEvolutions', [$poke->getTranslation($pokemon)]), 1);
			$evolutions = $poke->getPokemonEvolutions($pokemon['evolution_chain']['url'])['result'];
			$buttons = $poke->getEvolutionsButtons($evolutions['chain'], $bot);
			$buttons = array_merge($buttons, $poke->getPokemonButtons($pokemon, $bot, 1, 0));
		} else {
			$t = $tr->getTranslation('pokemonNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon-forms') {
		$data = $poke->getPokemonSpecies($poke->getPokemon($v->text)['result']['species']['name']);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($poke->getPokemon($pokemon['name'])['result']);
			$t = $bot->bold($tr->getTranslation('pokemonForms', [$poke->getTranslation($pokemon)]), 1);
			$buttons = $poke->getVarietiesButtons($pokemon['varieties'], $bot);
			$buttons = array_merge($buttons, $poke->getPokemonButtons($pokemon, $bot, 1, 1));
		} else {
			$t = $tr->getTranslation('pokemonNotFound');
		}
	} elseif ($user['settings']['select'] == 'pokemon-sprites') {
		if ($v->chat_type !== 'private') {
			$bot->answerCBQ($v->query_id, $tr->getTranslation(''), true);
		} else {
			$data = $poke->getPokemon($v->text);
			if ($data['ok']) {
				$pokemon = $data['result'];
				$preview = $poke->getPokemonImage($pokemon);
				$t = $bot->bold($tr->getTranslation('pokemonSprites', [$poke->getTranslation($pokemon)]), 1);
				$buttons[] = [
					$bot->createInlineButton($tr->getTranslation('default'), 'sprites ' . $pokemon['id'] . ' default'),
					$bot->createInlineButton($tr->getTranslation('shiny'), 'sprites ' . $pokemon['id'] . ' shiny')
				];
				foreach ($pokemon['sprites']['versions'] as $generation => $versions) {
					$buttons[][] = $bot->createInlineButton($poke->getTranslation($poke->getGeneration($generation)['result']), 'sprites ' . $pokemon['id'] . ' ' . $generation);
				}
				$buttons = array_merge($buttons, $poke->getPokemonButtons($pokemon, $bot, 1, 2));
			} else {
				$t = $tr->getTranslation('pokemonNotFound');
			}
		}
	} else {
		$t = $tr->getTranslation('worksInProgress');
	}
	if ($preview) $t = $bot->text_link('&#8203;', $preview) . $t;
	$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
	if (isset($user['settings']['select'])) {
		$user['settings']['select'] = '';
		$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $v->user_id]);
	}
}

# Inline commands
if ($v->update['inline_query']) {
	$sw_text = 'Start the Bot!';
	$sw_arg = 'inline'; // The message the bot receive is '/start inline'
	$results = [];
	# Search pokemon with inline mode
	if ($v->query) {
		$data = $poke->getPokemon($v->query);
		if ($data['ok']) {
			$pokemon = $data['result'];
			$preview = $poke->getPokemonImage($pokemon);
			$pokemon_name = $poke->getTranslation($pokemon);
			$t = $bot->bold($pokemon_name, 1);
			$t .= PHP_EOL . $tr->getTranslation('id') . ': #' . $poke->dexNumber($pokemon['id']);
			foreach ($pokemon['types'] as $pokemon_type) {
				$type = $poke->getPokemonType($pokemon_type['type']['name'])['result'];
				if ($types) {
					$types .= ', ' . $poke->getTranslation($type, 1);
				} else {
					$types = $poke->getTranslation($type, 1);
				}
			}
			$t .= PHP_EOL . $tr->getTranslation('type') . ': ' . $types;
			$t .= PHP_EOL . $tr->getTranslation('height') . ': ' . number_format($pokemon['height'] / 10, 1, ',' , '.') . ' ' . $tr->getTranslation('meters');
			$t .= PHP_EOL . $tr->getTranslation('weight') . ': ' . number_format($pokemon['weight'] / 10, 1, ',' , '.') . ' ' . $tr->getTranslation('kilograms');
			$buttons = $poke->getPokemonButtons($pokemon, $bot, 0, 0);
			if ($preview) $t = $bot->text_link('&#8203;', $preview) . $t;
			$results[] = $bot->createInlineArticle(
				$v->query,
				$pokemon_name,
				$tr->getTranslation('type') . ': ' . $types,
				$bot->createTextInput($t, 'def', false),
				$buttons,
				0,
				0,
				$preview
			);
		}
	}
	$bot->answerIQ($v->id, $results, $sw_text, $sw_arg);
}

?>
