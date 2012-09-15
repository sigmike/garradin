{include file="admin/_head.tpl" title="Éditer une page" current="wiki"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}" id="f_form">

    <fieldset class="wikiMain">
        <legend>Informations générales</legend>
        <dl>
            <dt><label for="f_titre">Titre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="titre" id="f_titre" value="{form_field data=$page name=titre}" /></dd>
            <dt><label for="f_uri">Adresse unique</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">
                Ne peut comporter que des lettres, des chiffres, des tirets et des tirets bas.
            </dd>
            <dd><input type="text" name="uri" id="f_uri" value="{form_field data=$page name=uri}" /></dd>
            <dt><label for="f_browse_parent">Cette page est une sous-rubrique de...</label></dt>
            <dd>
                <input type="hidden" name="parent" id="f_parent" value="{form_field data=$page name=parent}" />
                {if $page.parent == 0}
                    <samp id="current_parent_name">la racine du site</samp>
                {else}
                    <samp id="current_parent_name">{$parent|escape}</samp>
                {/if}
                <input type="button" id="f_browse_parent" onclick="browseWikiForParent();" value="Changer" />
            </dd>
            <dt><label for="f_date">Date</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <input type="date" size="10" name="date" id="f_date" value="{$date|date_fr:'Y-m-d'|escape}" />
                <input type="text" class="time" size="2" name="date_h" value="{$date|date_fr:'H'|escape}" /> h
                <input type="text" class="time" size="2" name="date_min" value="{$date|date_fr:'i'|escape}" />
            </dd>
        </dl>
    </fieldset>

    <fieldset class="wikiRights">
        <legend>Droits d'accès</legend>
        <dl>
            <dt><label for="f_droit_lecture_public">Cette page est visible :</label></dt>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_public" value="{Garradin_Wiki::LECTURE_PUBLIC}" {form_field data=$page name="droit_lecture" checked=Garradin_Wiki::LECTURE_PUBLIC} />
                <label for="f_droit_lecture_public"><strong>Sur le site de l'association</strong></label>
                &mdash; cette page apparaîtra sur le site public de l'association, accessible à tous les visiteurs
            </dd>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_normal" value="{Garradin_Wiki::LECTURE_NORMAL}"  {form_field data=$page name="droit_lecture" checked=Garradin_Wiki::LECTURE_NORMAL} />
                <label for="f_droit_lecture_normal"><strong>Sur le wiki uniquement</strong></label>
                &mdash; seuls les membres ayant accès au wiki pourront la voir
            </dd>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_categorie" value="{$user.id_categorie}"  {if $page.droit_lecture >= Garradin_Wiki::LECTURE_CATEGORIE}checked="checked"{/if} />
                <label for="f_droit_lecture_categorie"><strong>Aux membres de ma catégorie</strong></label>
                &mdash; seuls les membres de la même catégorie que moi pourront voir cette page
            </dd>
            <dt><label for="f_droit_ecriture_normal">Cette page peut être modifiée par :</label></dt>
            <dd>
                <input type="radio" name="droit_ecriture" id="f_droit_ecriture_normal" value="{Garradin_Wiki::ECRITURE_NORMAL}" {form_field data=$page name="droit_ecriture" checked=Garradin_Wiki::ECRITURE_NORMAL} {if $page.droit_lecture >= Garradin_Wiki::LECTURE_CATEGORIE}disabled="disabled"{/if} />
                <label for="f_droit_ecriture_normal">Les membres qui ont accès au wiki en écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_ecriture" id="f_droit_ecriture_categorie" value="{$user.id_categorie}" {if $page.droit_ecriture >= Garradin_Wiki::ECRITURE_CATEGORIE || $page.droit_lecture >= Garradin_Wiki::LECTURE_CATEGORIE}checked="checked"{/if} {if $page.droit_lecture >= Garradin_Wiki::LECTURE_CATEGORIE}disabled="disabled"{/if} />
                <label for="f_droit_ecriture_categorie">Les membres de ma catégorie</label>
            </dd>
        </dl>
    </fieldset>

    <fieldset class="wikiEncrypt">
        <dl>
            <dt>
                <input type="checkbox" name="chiffrement" id="f_chiffrement" {form_field name=chiffrement data=$page default=0 checked=1} value="1" onchange="checkEncryption(this);" />
                <label for="f_chiffrement">Chiffrer le contenu</label> <i>(facultatif)</i>
            </dt>
            <noscript>
            <dd>Nécessite JavaScript activé pour fonctionner !</dd>
            </noscript>
            <dd>Mot de passe : <i id="encryptPasswordDisplay" title="Chiffrement désactivé">désactivé</i></dd>
            <dd class="help">Le mot de passe n'est ni transmis ni enregistré, vous seul le connaissez,
                il n'est pas possible de retrouver le contenu si vous l'oubliez.</dd>
        </dl>
    </fieldset>


    <fieldset class="wikiText">
        <p>
            <textarea name="contenu" id="f_contenu" cols="70" rows="35">{form_field data=$page name=contenu}</textarea>
        </p>
    </fieldset>

    <fieldset class="wikiRevision">
        <dl>
            <dt><label for="f_modification">Résumé des modifications</label>  <i>(facultatif)</i></dt>
            <dd><input type="text" name="modification" id="f_modification" value="{form_field data=$page name=modification}" /></dd>
            {* FIXME
            <dt>
                <input type="checkbox" name="suivi" value="1" id="f_suivi" />
                <label for="f_suivi">Suivre les modifications de cette page</label>
            </dt>
            *}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="wiki_edit_`$page.id`"}
        <input type="hidden" name="revision_edition" value="{form_field name=revision_edition default=$page.revision}" />
        <input type="hidden" name="debut_edition" value="{form_field name=debut_edition default=$time}" />
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

<script type="text/javascript">
var page_id = '{$page.id|escape}';
{literal}
(function() {
    document.getElementById('f_droit_lecture_categorie').onchange = function()
    {
        document.getElementById('f_droit_ecriture_normal').checked = false;
        document.getElementById('f_droit_ecriture_normal').disabled = true;

        document.getElementById('f_droit_ecriture_categorie').checked = true;
        document.getElementById('f_droit_ecriture_categorie').disabled = true;
    };

    document.getElementById('f_droit_lecture_normal').onchange = function() {
        document.getElementById('f_droit_ecriture_normal').disabled = false;
        document.getElementById('f_droit_ecriture_categorie').disabled = false;
    };

    document.getElementById('f_droit_lecture_public').onchange = function() {
        document.getElementById('f_droit_ecriture_normal').disabled = false;
        document.getElementById('f_droit_ecriture_categorie').disabled = false;
    };

    window.changeParent = function(parent, title)
    {
        if (parent == page_id)
        {
            return false;
        }

        document.getElementById('f_parent').value = parent;
        document.getElementById('current_parent_name').innerHTML = title;
        return true;
    };

    window.browseWikiForParent = function()
    {
        window.open('_chercher_parent.php?parent=' + document.getElementById('f_parent').value, 'browseParent',
            'width=500,height=600,top=150,left=150,scrollbars=1,location=false');
    };

    if (document.getElementById('f_chiffrement').checked)
    {
        wikiDecrypt(true);
    }
}());
</script>
{/literal}

{include file="admin/_foot.tpl"}