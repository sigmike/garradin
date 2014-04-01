<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
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

$re = new Rappels_Envoyes;
$cm = new Cotisations_Membres;

$error = false;

if (utils::post('save'))
{
    if (!utils::CSRF_check('add_rappel_'.$membre['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
			$re->add([
				'id_cotisation'	=>	utils::post('id_cotisation'),
				'id_membre'		=>	$membre['id'],
				'media'			=>	utils::post('media'),
				'date'			=>	utils::post('date'),
			]);

            utils::redirect('/admin/membres/cotisations/rappels.php?id=' . $membre['id'] . '&ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('ok', isset($_GET['ok']));
$tpl->assign('membre', $membre);
$tpl->assign('cotisations', $cm->listSubscriptionsForMember($membre['id']));
$tpl->assign('default_date', date('Y-m-d'));
$tpl->assign('rappels', $re->listForMember($membre['id']));

$tpl->display('admin/membres/cotisations/rappels.tpl');

?>