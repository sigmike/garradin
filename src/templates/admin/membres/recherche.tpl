{include file="admin/_head.tpl" title="Recherche de membre" current="membres"}

{if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
<ul class="actions">
    <li><a href="{$admin_url}membres/">Liste des membres</a></li>
    <li class="current"><a href="{$admin_url}membres/recherche.php">Recherche avancée</a></li>
    <li><a href="{$admin_url}membres/recherche_sql.php">Recherche par requête SQL</a></li>
</ul>
{/if}


<form method="get" action="{$admin_url}membres/recherche.php" class="searchMember">
    <fieldset>
        <legend>Rechercher un membre</legend>
        <dl>
            <dt><label for="f_champ">Champ</label></dt>
            <dd>
                <select name="c" id="f_champ">
                    {foreach from=$champs_liste key="k" item="v"}
                    <option value="{$k|escape}"{form_field name="c" default=$champ selected=$k}>{$v.title|escape}</option>
                    {/foreach}
                </select>
            </dd>
            <dt><label for="f_texte">Recherche</label></dt>
            <dd id="f_free"><input id="f_texte" type="text" name="r" value="{$recherche|escape}" /></dd>
            {foreach from=$champs_liste key="k" item="v"}
                {if $v.type == 'select'}
                    <dd class="special" id="f_{$k|escape}">
                        <select name="r" disabled="disabled">
                            {foreach from=$v.options item="opt"}
                            <option value="{$opt|escape}"{form_field name="r" default=$recherche selected=$opt}>{$opt|escape}</option>
                            {/foreach}
                        </select>
                    </dd>
                {elseif $v.type == 'multiple'}
                    <dd class="special" id="f_{$k|escape}">
                        <select name="r" disabled="disabled">
                            {foreach from=$v.options key="opt_k" item="opt"}
                            <option value="{$opt_k|escape}"{form_field name="r" default=$recherche selected=$opt_k}>{$opt|escape}</option>
                            {/foreach}
                        </select>
                    </dd>
                {elseif $v.type == 'checkbox'}
                    <dd class="special" id="f_{$k|escape}">
                        <select name="r" disabled="disabled">
                            <option value="1"{form_field name="r" default=$recherche selected=1}>Oui</option>
                            <option value="0"{form_field name="r" default=$recherche selected=0}>Non</option>
                        </select>
                    </dd>
                {/if}
            {/foreach}
        </dl>
        <p class="submit">
            <input type="submit" value="Chercher &rarr;" />
        </p>
    </fieldset>
</form>

{if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}

    <form method="post" action="{$admin_url}membres/action.php" class="memberList">

    {if !empty($liste)}
    <table class="list search">
        <thead>
            {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}<td class="check"><input type="checkbox" value="Tout cocher / décocher" onclick="checkUncheck();" /></td>{/if}
            <td></td>
            {foreach from=$champs_entete key="c" item="cfg"}
                {if $champ == $c}
                    <th><strong>{$cfg.title|escape}</strong></th>
                {else}
                    <td>{$cfg.title|escape}</td>
                {/if}
            {/foreach}
            <td>Cotisation</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}<td class="check"><input type="checkbox" name="selected[]" value="{$membre.id|escape}" /></td>{/if}
                    <td class="num"><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">{$membre.id|escape}</a></th>
                    {foreach from=$champs_entete key="c" item="cfg"}
                        {if $champ == $c}
                            <th><strong>{$membre[$c]|escape|display_champ_membre:$cfg}</strong></th>
                        {else}
                            <td>{$membre[$c]|escape|display_champ_membre:$cfg}</td>
                        {/if}
                    {/foreach}
                    {if empty($membre.date_cotisation)}
                        <td class="error">jamais réglée</td>
                    {elseif $membre.date_cotisation > strtotime('12 months ago')}
                        <td class="confirm">à jour</td>
                    {else}
                        <td class="alert">en retard</td>
                    {/if}
                    <td class="actions">
                        {if !empty($membre.email)}<a class="icn" href="{$www_url}admin/membres/message.php?id={$membre.id|escape}" title="Envoyer un message">✉</a> {/if}
                        <a class="icn" href="modifier.php?id={$membre.id|escape}">✎</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
    <p class="checkUncheck">
        <input type="button" value="Tout cocher / décocher" onclick="checkUncheck();" />
    </p>
    <p class="actions">
        <em>Pour les membres cochés :</em>
        <input type="submit" name="move" value="Changer de catégorie" />
        <input type="submit" name="delete" value="Supprimer" />
        {csrf_field key="membres_action"}
    </p>
    {/if}

    {elseif $recherche != ''}
    <p class="alert">
        Aucun membre trouvé.
    </p>
    {/if}

    </form>

    <script type="text/javascript">
    {literal}
    (function() {
        var checked = false;

        window.checkUncheck = function()
        {
            var elements = document.getElementsByTagName('input');
            var el_length = elements.length;

            for (i = 0; i < el_length; i++)
            {
                var elm = elements[i];

                if (elm.type == 'checkbox')
                {
                    if (checked)
                        elm.checked = false;
                    else
                        elm.checked = true;
                }
            }

            checked = checked ? false : true;
            return true;
        }
    }())
    {/literal}
    </script>
{else}
    {if !empty($liste)}
    <table class="list">
        <thead>
            <th>Nom</th>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    <th>{$membre.nom|escape}</th>
                    <td class="actions">
                        {if !empty($membre.email)}<a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>{/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <p class="info">
        Aucun membre trouvé.
    </p>
    {/if}
{/if}

<script type="text/javascript">
{literal}
(function() {
    var current = false;

    var selectField = function(elm)
    {
        if (current)
        {
            document.getElementById('f_' + current).style.display = 'none';
            document.getElementById('f_' + current).querySelector('select').disabled = true;
            current = false;
        }
        
        if (document.getElementById('f_' + elm.value))
        {
            document.getElementById('f_' + elm.value).style.display = 'block';
            document.getElementById('f_' + elm.value).querySelector('select').disabled = false;
            document.getElementById('f_free').style.display = 'none';
            document.getElementById('f_texte').disabled = true;
            current = elm.value;
        }
        else
        {
            document.getElementById('f_texte').disabled = false;
            document.getElementById('f_free').style.display = 'block';
        }

        return true;
    }

    document.getElementById('f_champ').onchange = function() { selectField(this); };
    window.onload = selectField(document.getElementById('f_champ'));
}())
{/literal}
</script>

{include file="admin/_foot.tpl"}