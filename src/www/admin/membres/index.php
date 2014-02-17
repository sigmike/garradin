<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}


// Recherche de membre (pour ceux qui n'ont qu'un accès à la liste des membres)
if (utils::get('r'))
{
	$recherche = trim(utils::get('r'));

	$result = $membres->search($config->get('champ_identite'), $recherche);
    $tpl->assign('liste', $result);
	$tpl->assign('recherche', $recherche);
}
else
{
	$cats = new Membres_Categories;
	$champs = $config->get('champs_membres');

	$membres_cats = $cats->listSimple();
	$membres_cats_cachees = $cats->listHidden();

	$cat_id = (int) utils::get('cat') ?: 0;
	$page = (int) utils::get('p') ?: 1;

	if ($cat_id)
	{
	    if ($user['droits']['membres'] < Membres::DROIT_ECRITURE && array_key_exists($cat_id, $membres_cats_cachees))
	    {
	    	$cat_id = 0;
	    }
	}

	if (!$cat_id)
	{
	    $cat_id = array_diff(array_keys($membres_cats), array_keys($membres_cats_cachees));
	}

	$order = $champs->getFirst();
	$desc = false;

	if (utils::get('o'))
	    $order = utils::get('o');

	if (isset($_GET['d']))
	    $desc = true;

	$tpl->assign('order', $order);
	$tpl->assign('desc', $desc);

	$fields = $champs->getListedFields();

	$tpl->assign('champs', $fields);

	$tpl->assign('liste', $membres->listByCategory($cat_id, array_keys($fields), $page, $order, $desc));
	$tpl->assign('total', $membres->countByCategory($cat_id));

	$tpl->assign('pagination_url', utils::getSelfUrl(true) . '?p=[ID]&amp;o=' . $order . ($desc ? '&amp;d' : ''));

	$tpl->assign('membres_cats', $membres_cats);
	$tpl->assign('membres_cats_cachees', $membres_cats_cachees);
	$tpl->assign('current_cat', $cat_id);

	$tpl->assign('page', $page);
	$tpl->assign('bypage', Membres::ITEMS_PER_PAGE);

}

$tpl->display('admin/membres/index.tpl');

?>