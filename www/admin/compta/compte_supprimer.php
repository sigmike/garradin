<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$id = utils::get('id');
$compte = $comptes->get($id);

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('compta_delete_compte_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $comptes->delete($compte['id']);
            utils::redirect('/admin/compta/comptes.php?classe='.substr($compte['id'], 0, 1));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/compte_supprimer.tpl');

?>