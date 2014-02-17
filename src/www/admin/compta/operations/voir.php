<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$journal = new Compta_Journal;

$operation = $journal->get(utils::get('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}
$exercices = new Compta_Exercices;

$tpl->assign('operation', $operation);

$credit = $comptes->get($operation['compte_credit']);
$tpl->assign('nom_compte_credit', $credit['libelle']);

$debit = $comptes->get($operation['compte_debit']);
$tpl->assign('nom_compte_debit', $debit['libelle']);

$tpl->assign('exercice', $exercices->get($operation['id_exercice']));

if ($operation['id_categorie'])
{
    $cats = new Compta_Categories;

    $categorie = $cats->get($operation['id_categorie']);
    $tpl->assign('categorie', $categorie);

    if ($categorie['type'] == Compta_Categories::RECETTES)
    {
        $tpl->assign('compte', $debit['libelle']);
    }
    else
    {
        $tpl->assign('compte', $credit['libelle']);
    }

    $tpl->assign('moyen_paiement', $cats->getMoyenPaiement($operation['moyen_paiement']));
}

if ($operation['id_auteur'])
{
    $auteur = $membres->get($operation['id_auteur']);
    $tpl->assign('nom_auteur', $auteur['identite']);
}

$tpl->display('admin/compta/operations/voir.tpl');

?>