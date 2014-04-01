{include file="admin/_head.tpl" title="Membres ayant cotisé" current="membres/cotisations"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
    {/if}
</ul>

<dl class="cotisation">
    <dt>Cotisation</dt>
    <dd>{$cotisation.intitule|escape} — 
        {if $cotisation.duree}
            {$cotisation.duree|escape} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape_money} {$config.monnaie|escape}
    </dd>
    <dt>Nombre de membres ayant cotisé</dt>
    <dd>{$cotisation.nb_membres|escape}</dd>
</dl>

{if !empty($liste)}
    <table class="list">
        <thead>
            <tr>
                <td class="{if $order == "id"} cur {if $desc}desc{else}asc{/if}{/if}"><a href="?id={$cotisation.id|escape}&amp;o=id&amp;a">&darr;</a><a href="?id={$cotisation.id|escape}&amp;o=id&amp;d">&uarr;</a></td>
                <th class="{if $order == "identite"} cur {if $desc}desc{else}asc{/if}{/if}">Membre <a href="?id={$cotisation.id|escape}&amp;o=identite&amp;a">&darr;</a><a href="?id={$cotisation.id|escape}&amp;o=identite&amp;d">&uarr;</a></th>
                <td class="{if $order == "a_jour"} cur {if $desc}desc{else}asc{/if}{/if}">Statut <a href="?id={$cotisation.id|escape}&amp;o=a_jour&amp;a">&darr;</a><a href="?id={$cotisation.id|escape}&amp;o=a_jour&amp;d">&uarr;</a></td>
                <td class="{if $order == "date"} cur {if $desc}desc{else}asc{/if}{/if}">Date de cotisation <a href="?id={$cotisation.id|escape}&amp;o=date&amp;a">&darr;</a><a href="?id={$cotisation.id|escape}&amp;o=date&amp;d">&uarr;</a></td>
                <td></td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$liste item="co"}
                <tr>
                    <td class="num"><a class="icn" href="{$admin_url}membres/fiche.php?id={$co.id_membre|escape}">{$co.id_membre|escape}</a></td>
                    <th>{$co.nom|escape}</th>
                    <td>{if $co.a_jour}<b class="confirm">À jour</b>{else}<b class="error">En retard</b>{/if}</td>
                    <td>{$co.date|format_sqlite_date_to_french}</td>
                    <td class="actions">
                        <a href="{$admin_url}membres/cotisations/ajout.php?id={$co.id_membre|escape}&amp;cotisation={$cotisation.id|escape}">Saisir</a>
                        | <a href="{$admin_url}membres/cotisations.php?id={$co.id_membre|escape}" title="Voir toutes les cotisations de ce membre">Cotisations</a>
                        | <a href="{$admin_url}membres/cotisations/rappels.php?id={$co.id_membre|escape}">Rappels</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{/if}


{include file="admin/_foot.tpl"}