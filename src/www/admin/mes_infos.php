<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$membre = $membres->getLoggedUser();

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_me'))
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
            $data = [];

            foreach ($config->get('champs_membres')->getAll() as $key=>$c)
            {
                if (!empty($c['editable']))
                {
                    $data[$key] = utils::post($key);
                }
            }

            $membres->edit($membre['id'], $data, false);
            $membres->updateSessionData();

            utils::redirect('/admin/');
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

$tpl->assign('membre', $membre);

$tpl->display('admin/mes_infos.tpl');

?>