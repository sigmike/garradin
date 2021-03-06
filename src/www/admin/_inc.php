<?php

namespace Garradin;

require_once __DIR__ . '/../../include/init.php';

$tpl = Template::getInstance();
$tpl->assign('admin_url', WWW_URL . 'admin/');

$membres = new Membres;

if (!defined('Garradin\LOGIN_PROCESS'))
{
    if (!$membres->isLogged())
    {
        utils::redirect('/admin/login.php');
    }

    $tpl->assign('config', Config::getInstance()->getConfig());
    $tpl->assign('is_logged', true);
    $tpl->assign('user', $membres->getLoggedUser());
    $user = $membres->getLoggedUser();

    $tpl->assign('current', '');
    $tpl->assign('plugins_menu', Plugin::listMenu());

    if ($user['droits']['membres'] >= Membres::DROIT_ACCES)
    {
        $tpl->assign('nb_membres', $membres->countAllButHidden());
    }
}

?>