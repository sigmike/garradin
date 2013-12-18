<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$e = new Compta_Exercices;

$exercice = $e->get((int)utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

$error = false;

if (!empty($_POST['close']))
{
    if (!utils::CSRF_check('compta_cloturer_exercice_'.$exercice['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->close($exercice['id'], utils::post('fin'));
        
            if ($id && utils::post('reports'))
            {
                $e->doReports($exercice['id'], utils::modifyDate(utils::post('fin'), '+1 day'));
            }

            utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('exercice', $exercice);
$tpl->assign('custom_js', array('datepickr.js'));

$tpl->display('admin/compta/exercices/cloturer.tpl');

?>