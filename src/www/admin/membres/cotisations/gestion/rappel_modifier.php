<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!utils::get('id') || !is_numeric(utils::get('id')))
{
    throw new UserException("Argument du numéro de rappel manquant.");
}

$rappels = new Rappels;

$rappel = $rappels->get(utils::get('id'));

if (!$rappel)
{
    throw new UserException("Ce rappel n'existe pas.");
}

$cotisations = new Cotisations;

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_rappel_' . $rappel['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            if (utils::post('delai_choix') == 0)
               $delai = 0;
            elseif (utils::post('delai_choix') > 0)
                $delai = (int) utils::post('delai_post');
            else
                $delai = -(int) utils::post('delai_pre');

            $rappels->edit($rappel['id'], [
                'sujet'		=>	utils::post('sujet'),
                'texte'		=>	utils::post('texte'),
                'delai'		=>	$delai,
                'id_cotisation'	=>	utils::post('id_cotisation'),
            ]);

            utils::redirect('/admin/membres/cotisations/gestion/rappels.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$rappel['delai_pre'] = $rappel['delai_post'] = abs($rappel['delai']) ?: 30;
$rappel['delai_choix'] = $rappel['delai'] == 0 ? 0 : ($rappel['delai'] > 0 ? 1 : -1);

$tpl->assign('rappel', $rappel);
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->display('admin/membres/cotisations/gestion/rappel_modifier.tpl');

?>