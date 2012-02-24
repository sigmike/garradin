{include file="admin/_head.tpl" title="Modifier une catégorie" current="membres/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Informations générales</legend>
        <dl>
            <dt><label for="f_nom">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field data=$cat name=nom}" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" rows="5" cols="50">{form_field data=$cat name=description}</textarea></dd>
            <dt><label for="f_montant_cotisation">Montant cotisation</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant_cotisation" step="0.50" id="f_montant_cotisation" value="{form_field data=$cat name=montant_cotisation}" /></dd>
            <dt><label for="f_duree_cotisation">Duréee cotisation</label> (en mois) <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="duree_cotisation" id="f_duree_cotisation" value="{form_field data=$cat name=duree_cotisation}" /></dd>
            <dt>
                <input type="checkbox" name="cacher" value="1" id="f_cacher" {if $cat.cacher}checked="checked"{/if} />
                <label for="f_cacher">Catégorie cachée</label>
            </dt>
            <dd class="help">
                Cette catégorie ne sera visible qu'aux administrateurs.
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Droits</legend>
        <dl class="droits">
            <dt><label for="f_droit_connexion_aucun">Les membres de cette catégorie peuvent-ils se connecter ?</label></dt>
            <dd>
                <input type="radio" name="droit_connexion" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_connexion_aucun" {if $cat.droit_connexion == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_connexion_aucun"><b class="aucun">C</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_connexion" value="{Garradin_Membres::DROIT_ACCES}" id="f_droit_connexion_acces" {if $cat.droit_connexion == Garradin_Membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_connexion_acces"><b class="acces">C</b> Oui</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_inscription_aucun">Les membres de cette catégorie peuvent-ils s'inscrire d'eux-même ?</label></dt>
            <dd>
                <input type="radio" name="droit_inscription" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_inscription_aucun" {if $cat.droit_inscription == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_inscription_aucun"><b class="aucun">I</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_inscription" value="{Garradin_Membres::DROIT_ACCES}" id="f_droit_inscription_acces" {if $cat.droit_inscription == Garradin_Membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_inscription_acces"><b class="acces">I</b> Oui</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_membres_aucun">Gestion des membres :</label></dt>
            <dd>
                <input type="radio" name="droit_membres" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_membres_aucun" {if $cat.droit_membres == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_membres_aucun"><b class="aucun">M</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{Garradin_Membres::DROIT_ACCES}" id="f_droit_membres_acces" {if $cat.droit_membres == Garradin_Membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_membres_acces"><b class="acces">M</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{Garradin_Membres::DROIT_ECRITURE}" id="f_droit_membres_ecriture" {if $cat.droit_membres == Garradin_Membres::DROIT_ECRITURE}checked="checked"{/if} />
                <label for="f_droit_membres_ecriture"><b class="ecriture">M</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{Garradin_Membres::DROIT_ADMIN}" id="f_droit_membres_admin" {if $cat.droit_membres == Garradin_Membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_membres_admin"><b class="admin">M</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_compta_aucun">Comptabilité :</label></dt>
            <dd>
                <input type="radio" name="droit_compta" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_compta_aucun" {if $cat.droit_compta == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_compta_aucun"><b class="aucun">€</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{Garradin_Membres::DROIT_ACCES}" id="f_droit_compta_acces" {if $cat.droit_compta == Garradin_Membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_compta_acces"><b class="acces">€</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{Garradin_Membres::DROIT_ECRITURE}" id="f_droit_compta_ecriture" {if $cat.droit_compta == Garradin_Membres::DROIT_ECRITURE}checked="checked"{/if} />
                <label for="f_droit_compta_ecriture"><b class="ecriture">€</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{Garradin_Membres::DROIT_ADMIN}" id="f_droit_compta_admin" {if $cat.droit_compta == Garradin_Membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_compta_admin"><b class="admin">€</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_wiki_aucun">Wiki :</label></dt>
            <dd>
                <input type="radio" name="droit_wiki" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_wiki_aucun" {if $cat.droit_wiki == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_wiki_aucun"><b class="aucun">W</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{Garradin_Membres::DROIT_ACCES}" id="f_droit_wiki_acces" {if $cat.droit_wiki == Garradin_Membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_wiki_acces"><b class="acces">W</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{Garradin_Membres::DROIT_ECRITURE}" id="f_droit_wiki_ecriture" {if $cat.droit_wiki == Garradin_Membres::DROIT_ECRITURE}checked="checked"{/if} />
                <label for="f_droit_wiki_ecriture"><b class="ecriture">W</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{Garradin_Membres::DROIT_ADMIN}" id="f_droit_wiki_admin" {if $cat.droit_wiki == Garradin_Membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_wiki_admin"><b class="admin">W</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_config_aucun">Les membres de cette catégorie peuvent-ils modifier la configuration ?</label></dt>
            <dd>
                <input type="radio" name="droit_config" value="{Garradin_Membres::DROIT_AUCUN}" id="f_droit_config_aucun" {if $cat.droit_config == Garradin_Membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_config_aucun"><b class="aucun">&#x2611;</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_config" value="{Garradin_Membres::DROIT_ADMIN}" id="f_droit_config_admin" {if $cat.droit_config == Garradin_Membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_config_admin"><b class="admin">&#x2611;</b> Oui</label>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_cat_"|cat:$cat.id}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}