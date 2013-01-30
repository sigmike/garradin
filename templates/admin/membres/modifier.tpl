{include file="admin/_head.tpl" title="Modifier un membre" current="membres"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations personnelles</legend>
        <dl>
        {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
            <dt><label for="f_id">Numéro de membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="id" id="f_id" value="{form_field data=$membre name=id}" /></dd>
        {/if}
            {foreach from=$champs item="champ" key="nom"}
            {if empty($champ.private) || $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
                {html_champ_membre config=$champ name=$nom data=$membre}
            {/if}
            {/foreach}
        </dl>
    </fieldset>

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN || empty($champs.passe.private)}
    <fieldset>
        <legend>{if $membre.passe}Changer le mot de passe{else}Choisir un mot de passe{/if}</legend>
        <dl>
        {if $membre.passe}
            <dd>Ce membre a déjà un mot de passe, mais vous pouvez le changer si besoin.</dd>
        {else}
            <dd>Ce membre n'a pas encore de mot de passe et ne peut donc se connecter.</dd>
        {/if}
            <dt><label for="f_passe">Nouveau mot de passe</label>{if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd class="help">
                Pas d'idée ? Voici une suggestion choisie au hasard :
                <tt title="Cliquer pour utiliser cette suggestion comme mot de passe" onclick="fillPassword(this);">{$passphrase|escape}</tt>
            </dd>
            <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" /></dd>
            <dt><label for="f_repasse">Encore le mot de passe</label> (vérification){if $champs.passe.mandatory} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="password" name="repasse" id="f_repasse" value="{form_field name=repasse}" /></dd>
        </dl>
    </fieldset>
    {/if}

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
    <fieldset>
        <legend>Général</legend>
        <dl>
            <dt><label for="f_cat">Catégorie du membre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_categorie" id="f_cat">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $current_cat == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>
    {/if}

    <p class="submit">
        {csrf_field key="edit_member_"|cat:$membre.id}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

<script type="text/javascript">
{literal}
function fillPassword(elm)
{
    var pw = elm.textContent || elm.innerText;
    document.getElementById('f_passe').value = pw;
    document.getElementById('f_repasse').value = pw;
}
{/literal}
</script>

{include file="admin/_foot.tpl"}