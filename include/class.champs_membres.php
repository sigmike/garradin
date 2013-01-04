<?php

namespace Garradin;

class Champs_Membres
{
	protected $champs = null;

	protected $types = array(
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'multiple'	=>	'Combinaison de cases à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		'file'		=>	'Fichier',
        'password'  =>  'Mot de passe',
		'number'	=>	'Numéro',
		'tel'		=>	'Numéro de téléphone',
		'select'	=>	'Sélecteur de choix',
		'country'	=>	'Sélecteur de pays',
		'text'		=>	'Texte',
		'textarea'	=>	'Texte multi-lignes',
	);

    protected $config_fields = array(
        'type',
        'title',
        'help',
        'editable',
        'mandatory',
        'private',
        'options'
    );

    static protected $presets = null;

	public function __toString()
	{
		return utils::write_ini_string($this->champs);
	}

    public function toString()
    {
        return utils::write_ini_string($this->champs);
    }

	static public function importInstall()
	{
		$champs = parse_ini_file(GARRADIN_ROOT . '/include/data/champs_membres.ini', true);
        $champs = array_filter($champs, function ($row) { return !empty($row['install']); });
        return new Champs_Membres($champs);
	}

    static public function importPresets()
    {
        if (is_null(self::$presets))
        {
            self::$presets = parse_ini_file(GARRADIN_ROOT . '/include/data/champs_membres.ini', true);
        }

        return self::$presets;
    }

    static public function listUnusedPresets(Champs_Membres $champs)
    {
        return array_diff_key(self::importPresets(), $champs->getAll());
    }

	public function __construct($champs)
	{
		if ($champs instanceOf Champs_Membres)
		{
			$this->champs = $champs->getAll();
		}
        elseif (is_array($champs))
        {
            foreach ($champs as $key=>&$config)
            {
                $this->_checkField($key, $config);
            }

            $this->champs = $champs;
        }
		else
		{
			$champs = parse_ini_string((string)$champs, true);

            foreach ($champs as $key=>&$config)
            {
                $this->_checkField($key, $config);
            }

            $this->champs = $champs;
		}
	}

	public function getTypes()
	{
		return $this->types;
	}

	public function get($champ, $key = null)
	{
        if (!array_key_exists($champ, $this->champs))
            return null;

        if ($key !== null)
        {
            if (array_key_exists($key, $this->champs[$champ]))
                return $this->champs[$champ][$key];
            else
                return null;
        }

		return $this->champs[$champ];
	}

	public function getAll()
	{
		return $this->champs;
	}

    /**
     * Vérifie la cohérence et la présence des bons éléments pour un champ
     * @param  string $name     Nom du champ
     * @param  array $config    Configuration du champ
     * @return boolean true
     */
    protected function _checkField($name, &$config)
    {
        if (!preg_match('!^\w+(_\w+)*$!', $name))
        {
            throw new UserException('Le nom du champ est invalide.');
        }

        foreach ($config as $key=>&$value)
        {
            // Champ install non pris en compte
            if ($key == 'install')
            {
                unset($config[$key]);
                continue;
            }

            if (!in_array($key, $this->config_fields))
            {
                throw new \BadMethodCallException('Champ '.$key.' non valide.');
            }

            if ($key == 'editable' || $key == 'private' || $key == 'mandatory')
            {
                $value = (bool) (int) $value;
            }
            elseif ($key == 'help' || $key == 'title')
            {
                $value = trim((string) $value);
            }
            elseif ($key == 'options')
            {
                $value = (array) $value;
            }
        }

        if (empty($config['title']))
        {
            throw new UserException('Champ "'.$name.'" : Le titre est obligatoire.');
        }

        if (empty($config['type']) || !array_key_exists($config['type'], $this->types))
        {
            throw new UserException('Champ "'.$name.'" : Le type est vide ou non valide.');
        }

        if ($name == 'email' && $config['type'] != 'email')
        {
            throw new UserException('Le champ email ne peut être d\'un type différent de email.');
        }

        if ($name == 'passe' && $config['type'] != 'password')
        {
            throw new UserException('Le champ mot de passe ne peut être d\'un type différent de email.');
        }

        if (!array_key_exists('editable', $config))
        {
            $config['editable'] = false;
        }

        if (!array_key_exists('mandatory', $config))
        {
            $config['mandatory'] = false;
        }

        if (!array_key_exists('private', $config))
        {
            $config['private'] = false;
        }

        return true;
    }

    /**
     * Ajouter un nouveau champ
     * @param string $name Nom du champ
     * @param array $config Configuration du champ
     * @return boolean true
     */
    public function add($name, $config)
    {
        $this->_checkField($name, $config);

        $this->champs[$name] = $config;

        return true;
    }

    /**
     * Modifie un champ particulier
     * @param string $champ Nom du champ
     * @param string $key   Nom de la clé à modifier
     * @param mixed  $value Valeur à affecter
     * @return boolean true
     */
	public function set($champ, $key, $value)
	{
        if (!isset($this->champs[$champ]))
        {
            throw new \LogicException('Champ "'.$champ.'" inconnu.');
        }

        // Vérification
        $config = $this->champs[$champ];
        $config[$key] = $value;
        $this->_checkField($champ, $config);

		$this->champs[$champ] = $config;
		return true;
	}

    /**
     * Modifie les champs en interne en vérifiant que tout va bien
     * @param array $champs Liste des champs
     * @return boolean true
     */
    public function setAll($champs)
    {
        if (!array_key_exists('email', $champs))
        {
            throw new UserException('Le champ E-Mail ne peut être supprimé des fiches membres.');
        }

        if (!array_key_exists('passe', $champs))
        {
            throw new UserException('Le champ Mot de passe ne peut être supprimé des fiches membres.');
        }

        foreach ($champs as $name=>&$config)
        {
            $this->_checkField($name, $config);
        }

        $this->champs = $champs;

        return true;
    }

    public function diff()
    {
    	$db = DB::getInstance();
    	//$config
    }

    /**
     * Enregistre les changements de champs en base de données
     * @param  boolean $copy Recopier les anciennes champs dans les nouveaux ?
     * @return boolean true
     */
    public function save($copy = true)
    {
    	$db = DB::getInstance();
    	$config = Config::getInstance();

    	// Champs à créer
    	$create = array(
    		'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
    		'id_categorie INTEGER NOT NULL, -- Numéro de catégorie',
            'date_connexion TEXT NULL, -- Date de dernière connexion',
    	);

    	// Champs à recopier
    	$copy = array(
    		'id',
    		'id_categorie',
            'date_connexion'
    	);

        $anciens_champs = $config->get('champs_membres');
    	$anciens_champs = is_null($anciens_champs) ? $this->champs : $anciens_champs->getAll();

    	foreach ($this->champs as $key=>$cfg)
    	{
    		if ($cfg['type'] == 'number')
    			$type = 'FLOAT';
    		elseif ($cfg['type'] == 'multiple' || $cfg['type'] == 'checkbox')
    			$type = 'INTEGER';
    		elseif ($cfg['type'] == 'file')
    			$type = 'BLOB';
    		else
    			$type = 'TEXT';

    		$create[] = $key . ' ' . $type . ' ' . ', -- ' . str_replace(array("\n", "\r"), '', $cfg['title']);

    		if (array_key_exists($key, $anciens_champs))
    		{
    			$copy[] = $key;
    		}
    	}

    	$last = count($create) - 1;
    	$create[$last] = str_replace(',', '', $create[$last]);

    	$create = 'CREATE TABLE membres_tmp (' . "\n\t" . implode("\n\t", $create) . "\n);";
    	$copy = 'INSERT INTO membres_tmp (' . implode(', ', $copy) . ') SELECT ' . implode(', ', $copy) . ' FROM membres;';

    	$db->exec('PRAGMA foreign_keys = OFF;');
    	$db->exec('BEGIN;');
    	$db->exec($create);
    	
    	if ($copy) {
    		$db->exec($copy);
    	}
    	
    	$db->exec('DROP TABLE membres;');
    	$db->exec('ALTER TABLE membres_tmp RENAME TO membres;');
    	$db->exec('END;');
    	$db->exec('PRAGMA foreign_keys = ON;');

    	$config->set('champs_membres', $this);
    	$config->save();

    	return true;
    }
}

?>