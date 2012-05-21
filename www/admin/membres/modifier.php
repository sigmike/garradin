<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de membre manquant.");
}

$id = (int) $_GET['id'];

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

require_once GARRADIN_ROOT . '/include/libs/passphrase/lib.passphrase.french.php';
require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;

// Protection contre la modification des admins par des membres moins puissants
$membre_cat = $cats->get($membre['id_categorie']);
if (($membre_cat['droit_membres'] == Garradin_Membres::DROIT_ADMIN)
    && ($user['droits']['membres'] < Garradin_Membres::DROIT_ADMIN))
{
    throw new UserException("Seul un membre admin peut modifier un autre membre admin.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_member_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (utils::post('passe') != utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try {
            $data = array(
                'nom'           =>  utils::post('nom'),
                'email'         =>  utils::post('email'),
                'passe'         =>  utils::post('passe'),
                'telephone'     =>  utils::post('telephone'),
                'code_postal'   =>  utils::post('code_postal'),
                'adresse'       =>  utils::post('adresse'),
                'ville'         =>  utils::post('ville'),
                'pays'          =>  utils::post('pays'),
                'date_naissance'=>  utils::post('date_naissance'),
                'notes'         =>  utils::post('notes'),
            );

            if ($user['droits']['membres'] == Garradin_Membres::DROIT_ADMIN)
            {
                $data['id_categorie'] = utils::post('id_categorie');
                $data['id'] = utils::post('id');
            }

            $membres->edit($id, $data, false);

            utils::redirect('/admin/membres/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('passphrase', Passphrase::generate());
$tpl->assign('obligatoires', $config->get('champs_obligatoires'));

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', utils::post('id_categorie') ?: $membre['id_categorie']);

$tpl->assign('pays', utils::getCountryList());
$tpl->assign('current_cc', utils::post('pays') ?: $membre['pays']);

$tpl->assign('can_change_id', $user['droits']['membres'] == Garradin_Membres::DROIT_ADMIN);

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/modifier.tpl');

?>