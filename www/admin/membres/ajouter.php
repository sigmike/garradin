<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Membres_Categories;

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('new_member'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (utils::post('passe') != utils::post('repasse'))
    {
        $error = 'La vérification ne correspond pas au mot de passe.';
    }
    else
    {
        try
        {
            if ($user['droits']['membres'] == Membres::DROIT_ADMIN)
            {
                $id_categorie = utils::post('id_categorie');
            }
            else
            {
                $id_categorie = $config->get('categorie_membres');
            }

            $id = $membres->add(array(
                'id_categorie'  =>  $id_categorie,
                'nom'           =>  utils::post('nom'),
                'email'         =>  utils::post('email'),
                'passe'         =>  utils::post('passe'),
                'telephone'     =>  utils::post('telephone'),
                'code_postal'   =>  utils::post('code_postal'),
                'adresse'       =>  utils::post('adresse'),
                'ville'         =>  utils::post('ville'),
                'pays'          =>  utils::post('pays'),
                'date_naissance'=>  utils::post('date_naissance'),
                'notes'         =>  '',
                'lettre_infos'  =>  utils::post('lettre_infos'),
            ));

            utils::redirect('/admin/membres/fiche.php?id='.(int)$id);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('passphrase', utils::suggestPassword());
$tpl->assign('champs', $config->get('champs_membres')->getAll());

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', utils::post('id_categorie') ?: $config->get('categorie_membres'));

$tpl->display('admin/membres/ajouter.tpl');

?>