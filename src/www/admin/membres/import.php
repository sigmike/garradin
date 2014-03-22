<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$import = new Membres_Import;

if (isset($_GET['export']))
{
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="Export membres - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
    $import->toCSV();
    exit;
}

$error = false;
$champs = $config->get('champs_membres')->getAll();
$champs['date_inscription'] = ['title' => 'Date inscription', 'type' => 'date'];

if (utils::post('import'))
{
    // FIXME
    if (false && !utils::CSRF_check('membres_import'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (empty($_FILES['upload']['tmp_name']))
    {
        $error = 'Aucun fichier fourni.';
    }
    else
    {
        try
        {
            if (utils::post('type') == 'galette')
            {
                $import->fromGalette($_FILES['upload']['tmp_name'], utils::post('galette_translate'));
            }
            elseif (utils::post('type') == 'garradin')
            {
                $import->fromCSV($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }

            utils::redirect('/admin/membres/import.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('ok', isset($_GET['ok']) ? true : false);

$tpl->assign('garradin_champs', $champs);
$tpl->assign('galette_champs', $import->galette_fields);
$tpl->assign('translate', utils::post('galette_translate'));

$tpl->display('admin/membres/import.tpl');

?>