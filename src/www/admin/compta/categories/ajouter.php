<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Compta_Categories;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('compta_ajout_cat'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $cats->add([
                'intitule'      =>  utils::post('intitule'),
                'description'   =>  utils::post('description'),
                'compte'        =>  utils::post('compte'),
                'type'          =>  utils::post('type'),
            ]);

            if (utils::post('type') == Compta_Categories::DEPENSES)
                $type = 'depenses';
            elseif (utils::post('type') == Compta_Categories::AUTRES)
                $type = 'autres';
            else
                $type = 'recettes';

            utils::redirect('/admin/compta/categories/?'.$type);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('type', isset($_POST['type']) ? utils::post('type') : Compta_Categories::RECETTES);
$tpl->assign('comptes', $comptes->listTree());

$tpl->display('admin/compta/categories/ajouter.tpl');

?>