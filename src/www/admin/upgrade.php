<?php
namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../../include/init.php';

$config = Config::getInstance();

$v = $config->getVersion();

if (version_compare($v, garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

$db = DB::getInstance();
$redirect = true;

echo '<!DOCTYPE html>
<meta charset="utf-8" />
<h3>Mise à jour de Garradin '.$config->getVersion().' vers la version '.garradin_version().'...</h3>';

flush();

// versions pré-0.3.0
if (!$v)
{
    $db->exec('ALTER TABLE membres ADD COLUMN lettre_infos INTEGER DEFAULT 0;');
    $v = '0.3.0';
}

if (version_compare($v, '0.4.0', '<'))
{
    $config->set('monnaie', '€');
    $config->set('pays', 'FR');
    $config->save();

    $db->exec(file_get_contents(ROOT . '/include/data/0.4.0.sql'));

    // Mise en place compta
    $comptes = new Compta_Comptes;
    $comptes->importPlan();

    $comptes = new Compta_Categories;
    $comptes->importCategories();
}

if (version_compare($v, '0.4.3', '<'))
{
    $db->exec(file_get_contents(ROOT . '/include/data/0.4.3.sql'));
}

if (version_compare($v, '0.4.5', '<'))
{
    // Mise à jour plan comptable
    $comptes = new Compta_Comptes;
    $comptes->importPlan();

    // Création page wiki connexion
    $wiki = new Wiki;
    $page = Wiki::transformTitleToURI('Bienvenue');
    $config->set('accueil_connexion', $page);

    if (!$wiki->getByUri($page))
    {
        $id_page = $wiki->create([
            'titre' =>  'Bienvenue',
            'uri'   =>  $page,
        ]);

        $wiki->editRevision($id_page, 0, [
            'id_auteur' =>  null,
            'contenu'   =>  "Bienvenue dans l'administration de ".$config->get('nom_asso')." !\n\n"
                .   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
        ]);
    }

    $config->set('accueil_connexion', $page);
    $config->save();
}

if (version_compare($v, '0.5.0', '<'))
{
    // Récupération de l'ancienne config
    $champs_modifiables_membre = $db->querySingle('SELECT valeur FROM config WHERE cle = "champs_modifiables_membre";');
    $champs_modifiables_membre = !empty($champs_modifiables_membre) ? explode(',', $champs_modifiables_membre) : [];

    $champs_obligatoires = $db->querySingle('SELECT valeur FROM config WHERE cle = "champs_obligatoires";');
    $champs_obligatoires = !empty($champs_obligatoires) ? explode(',', $champs_obligatoires) : [];

    // Import des champs membres par défaut
    $champs = Champs_Membres::importInstall();

    // Application de l'ancienne config aux nouveaux champs membres
    foreach ($champs_obligatoires as $name)
    {
        if ($champs->get($name) !== null)
            $champs->set($name, 'mandatory', true);
    }

    foreach ($champs_modifiables_membre as $name)
    {
        if ($champs->get($name) !== null)
            $champs->set($name, 'editable', true);
    }

    $champs->save();

    $config->set('champs_membres', $champs);
    $config->save();

    // Suppression de l'ancienne config
    $db->exec('DELETE FROM config WHERE cle IN ("champs_obligatoires", "champs_modifiables_membre");');
}

if (version_compare($v, '0.6.0-rc1', '<'))
{
    $categories = new Membres_Categories;
    $list = $categories->listComplete();

    $db->exec('PRAGMA foreign_keys = OFF; BEGIN;');

    // Mise à jour base de données
    $db->exec(file_get_contents(ROOT . '/include/data/0.6.0.sql'));

    $id_cat_cotisation = $db->querySingle('SELECT id FROM compta_categories WHERE compte = 756 LIMIT 1;');

    // Conversion des cotisations de catégories en cotisations indépendantes
    foreach ($list as $cat)
    {
        $db->simpleInsert('cotisations', [
            'id_categorie_compta'   =>  null,
            'intitule'              =>  $cat['nom'],
            'montant'               =>  (float) $cat['montant_cotisation'],
            // Convertir un nombre de mois en nombre de jours
            'duree'                 =>  round($cat['duree_cotisation'] * 30.44),
            'description'           =>  'Créé automatiquement depuis les catégories de membres (version 0.5.x)',
        ]);

        $args = [
            'id_cotisation' =>  (int)$db->lastInsertRowId(),
            'id_categorie'  =>  (int)$cat['id'],
        ];

        // import des dates de cotisation existantes comme paiements
        $db->simpleExec('INSERT INTO cotisations_membres 
            (id_membre, id_cotisation, date)
            SELECT id, :id_cotisation, date(date_cotisation) FROM membres
            WHERE date_cotisation IS NOT NULL AND date_cotisation != \'\' AND id_categorie = :id_categorie;',
            $args);

        // Mais on ne crée pas d'écriture comptable, car elles existent probablement déjà
    }

    // Déplacement des squelettes dans le répertoire public
    if (!file_exists(ROOT . '/www/squelettes'))
    {
        mkdir(ROOT . '/www/squelettes');
    }

    if (file_exists(ROOT . '/squelettes'))
    {
        $dir = dir(ROOT . '/squelettes');

        while ($file = $dir->read())
        {
            if ($file == '.' || $file == '..')
                continue;

            rename(ROOT . '/squelettes/' . $file, ROOT . '/www/squelettes/' . $file);
        }

        $dir->close();

        @rmdir(ROOT . '/squelettes');
    }

    $db->exec('END; PRAGMA foreign_keys = ON;');

    // Mise à jour de la table membres, suppression du champ date_cotisation notamment
    $config->get('champs_membres')->save();

    // Possibilité de choisir l'identité et l'identifiant d'un membre
    $config->set('champ_identite', 'nom');
    $config->set('champ_identifiant', 'email');
    $config->save();
}

utils::clearCaches();

$config->setVersion(garradin_version());

echo '<h4>Mise à jour terminée.</h4>
<p><a href="'.WWW_URL.'admin/">Retour</a></p>';

if ($redirect)
{
    echo '
    <script type="text/javascript">
    window.setTimeout(function () { 
        window.location.href = "'.WWW_URL.'admin/"; 
    }, 1000);
    </script>';
}

?>