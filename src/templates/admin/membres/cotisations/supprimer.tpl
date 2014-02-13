{include file="admin/_head.tpl" title="Supprimer une cotisation pour le membre n°`$membre.id`" current="membres/cotisations"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/cotisations.php?id={$membre.id|escape}">Suivi des cotisations</a></li>
</ul>

{if $error}
    <p class="error">{$error|escape}</p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Supprimer une cotisation membre</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la cotisation membre
            du {$cotisation.date|format_sqlite_date_to_french}&nbsp;?
        </h3>
    </fieldset>
    </fieldset>

    <p class="submit">
        {csrf_field key="del_cotisation_`$cotisation.id`"}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}